<?php
/**
 * Logika inti MOMCARE: usia kehamilan, jadwal ANC K1..K6, status kunjungan,
 * pengingat. Rumus usia kehamilan = (tanggal saat ini - HPHT) / 7.
 */

/** Periode pelaksanaan tiap kunjungan, dalam minggu sejak HPHT. */
const ANC_JENDELA = [
    1 => ['mulai' => 0,  'akhir' => 12, 'tri' => 1, 'dokter' => true],
    2 => ['mulai' => 12, 'akhir' => 24, 'tri' => 2, 'dokter' => false],
    3 => ['mulai' => 24, 'akhir' => 28, 'tri' => 2, 'dokter' => false],
    4 => ['mulai' => 28, 'akhir' => 36, 'tri' => 3, 'dokter' => false],
    5 => ['mulai' => 36, 'akhir' => 38, 'tri' => 3, 'dokter' => true],
    6 => ['mulai' => 38, 'akhir' => 42, 'tri' => 3, 'dokter' => false],
];

const STATUS_LABEL = [
    'selesai'   => 'Selesai',
    'perlu'     => 'Perlu dilakukan',
    'akan'      => 'Akan datang',
    'terlambat' => 'Terlambat',
];

function hitung_hpl(string $hpht): string
{
    return tambah_hari($hpht, 280);
}

/** @return array{minggu:int,hari:int,total:int} */
function usia_kehamilan(string $hpht, ?string $pada = null): array
{
    $pada ??= hari_ini();
    $total = max(0, selisih_hari($hpht, $pada));
    return ['minggu' => intdiv($total, 7), 'hari' => $total % 7, 'total' => $total];
}

/** Trimester 1: sampai 12 minggu, trimester 2: 13-27, trimester 3: 28 ke atas. */
function trimester(int $minggu): int
{
    return $minggu <= 12 ? 1 : ($minggu <= 27 ? 2 : 3);
}

/** Perbandingan ukuran janin yang umum dipakai (perkiraan, mulai minggu 12). */
function ukuran_janin(int $minggu): ?string
{
    $peta = [40 => 'semangka', 36 => 'selada romaine', 32 => 'bengkuang', 28 => 'terong',
             24 => 'jagung', 20 => 'pisang', 16 => 'apel', 12 => 'jeruk nipis'];
    foreach ($peta as $min => $nama) {
        if ($minggu >= $min) {
            return $nama;
        }
    }
    return null;
}

/** Buat 6 baris jadwal untuk kehamilan baru. */
function buat_jadwal(int $kehamilanId, string $hpht): void
{
    foreach (ANC_JENDELA as $ke => $j) {
        db_run(
            'INSERT INTO jadwal_anc (kehamilan_id, kunjungan_ke, tanggal_mulai, tanggal_akhir) VALUES (?, ?, ?, ?)',
            [$kehamilanId, $ke, tambah_hari($hpht, $j['mulai'] * 7), tambah_hari($hpht, $j['akhir'] * 7 - 1)]
        );
    }
}

/** selesai | perlu | akan | terlambat */
function status_jadwal(string $mulai, string $akhir, bool $selesai, ?string $hariIni = null): string
{
    $hariIni ??= hari_ini();
    if ($selesai) {
        return 'selesai';
    }
    if ($hariIni < $mulai) {
        return 'akan';
    }
    return $hariIni <= $akhir ? 'perlu' : 'terlambat';
}

/** Jadwal K1..K6 beserta status dan info kunjungan (bila sudah dilakukan). */
function jadwal_lengkap(int $kehamilanId): array
{
    $rows = db_all(
        'SELECT j.id, j.kehamilan_id, j.kunjungan_ke, j.tanggal_mulai, j.tanggal_akhir,
                kj.id AS kunjungan_id, kj.tanggal AS tgl_kunjungan
         FROM jadwal_anc j
         LEFT JOIN kunjungan kj ON kj.jadwal_id = j.id
         WHERE j.kehamilan_id = ?
         ORDER BY j.kunjungan_ke',
        [$kehamilanId]
    );
    foreach ($rows as &$r) {
        $r['selesai']  = $r['kunjungan_id'] !== null;
        $r['status']   = status_jadwal($r['tanggal_mulai'], $r['tanggal_akhir'], $r['selesai']);
        $r['trimester'] = ANC_JENDELA[(int) $r['kunjungan_ke']]['tri'];
        $r['dokter']   = ANC_JENDELA[(int) $r['kunjungan_ke']]['dokter'];
    }
    unset($r);
    return $rows;
}

/**
 * Kunjungan berikutnya = K terendah yang belum dilakukan.
 * Tanggal rencana memakai "tanggal kembali" dari bidan bila masih di dalam periode.
 */
function kunjungan_berikutnya(array $jadwal, ?string $tanggalKembali, ?string $hariIni = null): ?array
{
    $hariIni ??= hari_ini();
    foreach ($jadwal as $j) {
        if ($j['selesai']) {
            continue;
        }
        if ($tanggalKembali && $tanggalKembali <= $j['tanggal_akhir']) {
            $rencana = max($tanggalKembali, $j['tanggal_mulai']);
        } else {
            $rencana = max($j['tanggal_mulai'], $hariIni);
        }
        $j['rencana']    = $rencana;
        $j['sisa_hari']  = selisih_hari($hariIni, $rencana);
        return $j;
    }
    return null;
}

/** Catat pengingat H-7, H-1, dan hari-H (sekali per jadwal) untuk ditampilkan di aplikasi. */
function sinkron_pengingat(int $userId, ?array $berikut): void
{
    if (!$berikut || $berikut['sisa_hari'] < 0 || $berikut['sisa_hari'] > 7) {
        return;
    }
    $sisa  = $berikut['sisa_hari'];
    $jenis = $sisa === 0 ? 'H0' : ($sisa === 1 ? 'H-1' : 'H-7');
    $ada   = db_val('SELECT 1 FROM notifikasi WHERE jadwal_id = ? AND jenis = ?', [$berikut['id'], $jenis]);
    if ($ada) {
        return;
    }
    $judul = $sisa === 0 ? 'Hari ini jadwal periksa kehamilan' : 'Jadwal periksa kehamilan sebentar lagi';
    $pesan = 'Kunjungan K' . $berikut['kunjungan_ke'] . ' pada ' . tgl_indo($berikut['rencana'], true)
           . '. Bawa Buku KIA dan Kartu JKN.';
    db_run(
        'INSERT INTO notifikasi (user_id, jadwal_id, jenis, judul, pesan, tanggal_kirim) VALUES (?, ?, ?, ?, ?, ?)',
        [$userId, $berikut['id'], $jenis, $judul, $pesan, hari_ini()]
    );
}

/** Teks hitung mundur untuk kunjungan berikutnya. */
function teks_sisa_hari(int $sisa): string
{
    if ($sisa > 1) {
        return $sisa . ' hari lagi';
    }
    return $sisa === 1 ? 'Besok' : ($sisa === 0 ? 'Hari ini' : 'Sudah lewat ' . abs($sisa) . ' hari');
}

/** Hitung berapa isian persiapan persalinan yang sudah terisi (dari 6). */
function hitung_persiapan(?array $p): int
{
    if (!$p) {
        return 0;
    }
    $n = 0;
    foreach (['tempat', 'penolong', 'pendamping', 'biaya', 'transportasi', 'donor_darah'] as $k) {
        if (!empty($p[$k])) {
            $n++;
        }
    }
    return $n;
}
