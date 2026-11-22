<?php
/** Fungsi bantu untuk sisi tenaga kesehatan / kader / admin. */

function bisa_catat(array $u): bool
{
    return in_array($u['role'], ['nakes', 'admin'], true);
}

/** Daftar kehamilan aktif beserta jadwal, status, dan kunjungan berikutnya. */
function daftar_kehamilan_aktif(?int $puskesmasId): array
{
    $sql = 'SELECT k.id AS kehamilan_id, k.hpht, k.hpl, k.gravida, k.paritas, k.abortus, i.id AS ibu_id, i.tanggal_lahir,
                   u.nama, u.no_hp
            FROM kehamilan k JOIN ibu_hamil i ON i.id = k.ibu_id JOIN users u ON u.id = i.user_id
            WHERE k.status = \'aktif\'';
    $par = [];
    if ($puskesmasId) {
        $sql .= ' AND k.puskesmas_id = ?';
        $par[] = $puskesmasId;
    }
    $sql .= ' ORDER BY u.nama';
    $ibu = db_all($sql, $par);
    if (!$ibu) {
        return [];
    }
    $ids = array_column($ibu, 'kehamilan_id');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $jadwal = [];
    foreach (db_all(
        "SELECT j.id, j.kehamilan_id, j.kunjungan_ke, j.tanggal_mulai, j.tanggal_akhir, kj.id AS kunjungan_id, kj.tanggal AS tgl_kunjungan
         FROM jadwal_anc j LEFT JOIN kunjungan kj ON kj.jadwal_id = j.id
         WHERE j.kehamilan_id IN ($in) ORDER BY j.kehamilan_id, j.kunjungan_ke", $ids) as $r) {
        $r['selesai'] = $r['kunjungan_id'] !== null;
        $r['status']  = status_jadwal($r['tanggal_mulai'], $r['tanggal_akhir'], $r['selesai']);
        $r['dokter']  = ANC_JENDELA[(int) $r['kunjungan_ke']]['dokter'];
        $jadwal[$r['kehamilan_id']][] = $r;
    }
    $kembali = [];
    foreach (db_all("SELECT kehamilan_id, tanggal_kembali FROM kunjungan WHERE kehamilan_id IN ($in) ORDER BY tanggal, id", $ids) as $r) {
        $kembali[$r['kehamilan_id']] = $r['tanggal_kembali'];
    }

    foreach ($ibu as &$b) {
        $b['jadwal']  = $jadwal[$b['kehamilan_id']] ?? [];
        $b['usia']    = usia_kehamilan($b['hpht']);
        $b['tri']     = trimester($b['usia']['minggu']);
        $b['berikut'] = kunjungan_berikutnya($b['jadwal'], $kembali[$b['kehamilan_id']] ?? null);
        $b['selesai'] = count(array_filter($b['jadwal'], fn($j) => $j['selesai']));
        $status = 'selesai';
        foreach ($b['jadwal'] as $j) {
            if ($j['status'] === 'terlambat') { $status = 'terlambat'; break; }
            if ($j['status'] === 'perlu') { $status = 'perlu'; }
            elseif ($j['status'] === 'akan' && $status === 'selesai') { $status = 'akan'; }
        }
        $b['status'] = $status;
    }
    unset($b);
    return $ibu;
}

/** Pastikan kehamilan ini milik puskesmas pengguna. */
function kehamilan_boleh(array $u, int $kehamilanId): ?array
{
    $k = db_row(
        'SELECT k.*, u.nama, u.no_hp, i.id AS ibu_id, i.tanggal_lahir, i.no_jkn, i.gol_darah, i.rhesus, i.alamat
         FROM kehamilan k JOIN ibu_hamil i ON i.id = k.ibu_id JOIN users u ON u.id = i.user_id WHERE k.id = ?',
        [$kehamilanId]
    );
    if (!$k) {
        return null;
    }
    if ($u['puskesmas_id'] && (int) $u['puskesmas_id'] !== (int) $k['puskesmas_id']) {
        return null;
    }
    return $k;
}

/** Validasi angka dalam rentang; kembalikan [nilai|null, pesanGalat|null]. */
function angka_rentang(string $key, string $label, float $min, float $maks): array
{
    $v = post_angka($key);
    if ($v === null) {
        return [null, (trim((string) ($_POST[$key] ?? '')) !== '') ? "$label harus berupa angka." : null];
    }
    if ($v < $min || $v > $maks) {
        return [null, "$label di luar rentang wajar ($min sampai $maks)."];
    }
    return [$v, null];
}
