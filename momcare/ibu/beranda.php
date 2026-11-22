<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();

$terakhir  = db_row('SELECT tanggal_kembali FROM kunjungan WHERE kehamilan_id = ? ORDER BY tanggal DESC, id DESC LIMIT 1', [$I['kehamilan_id']]);
$berikut   = kunjungan_berikutnya($I['jadwal'], $terakhir['tanggal_kembali'] ?? null);
sinkron_pengingat((int) $I['user_id'], $berikut);

$notif = db_all('SELECT judul, pesan, tanggal_kirim FROM notifikasi WHERE user_id = ? ORDER BY tanggal_kirim DESC, id DESC LIMIT 3', [$I['user_id']]);
$selesai = count(array_filter($I['jadwal'], fn($j) => $j['selesai']));
$persen  = min(100, round($I['usia']['total'] / 280 * 100));
$sisaHpl = selisih_hari(hari_ini(), $I['hpl']);
$ukuran  = ukuran_janin($I['usia']['minggu']);
$nama    = explode(' ', trim($I['nama']))[0];

page_start('Beranda', 'beranda');
?>
<section class="hero" aria-label="Usia kehamilan">
  <div class="bubble b1"></div><div class="bubble b2"></div>
  <div class="text">
    <div class="eyebrow">Halo, Bu <?= e($nama) ?></div>
    <h1>Usia kehamilan Ibu</h1>
    <div class="big"><?= (int) $I['usia']['minggu'] ?><small>minggu</small> <?= (int) $I['usia']['hari'] ?><small>hari</small></div>
    <p>Trimester <?= (int) $I['tri'] ?> &middot; Perkiraan lahir <?= e(tgl_indo($I['hpl'], false, true)) ?></p>
  </div>
  <div class="art"><?= ilustrasi_ibu() ?></div>
  <div class="progress progress-hero" role="progressbar" aria-valuenow="<?= (int) $persen ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Kemajuan kehamilan"><i style="width:<?= (int) $persen ?>%"></i></div>
  <div class="tri-marks"><span>Minggu 0</span><span>Minggu 13</span><span>Minggu 28</span><span>Minggu 40</span></div>
  <div class="pills">
    <span class="pill-w"><?= $sisaHpl >= 0 ? $sisaHpl . ' hari menuju HPL' : 'Lewat ' . abs($sisaHpl) . ' hari dari HPL' ?></span>
    <?php if ($ukuran): ?><span class="pill-w">Seukuran <?= e($ukuran) ?></span><?php endif; ?>
  </div>
</section>

<?php if ($berikut): ?>
<section class="card" aria-label="Kunjungan berikutnya">
  <div class="card-head">
    <span class="eyebrow">Kunjungan berikutnya</span>
    <?= chip_status($berikut['status']) ?>
  </div>
  <div class="next">
    <div class="date" aria-hidden="true"><b><?= e((new DateTimeImmutable($berikut['rencana']))->format('j')) ?></b><span><?= e(BULAN_PENDEK[(int) (new DateTimeImmutable($berikut['rencana']))->format('n')]) ?></span></div>
    <div class="info">
      <h2>Kunjungan K<?= (int) $berikut['kunjungan_ke'] ?><?= $berikut['dokter'] ? ' (bersama dokter)' : '' ?></h2>
      <div><strong><?= e(teks_sisa_hari((int) $berikut['sisa_hari'])) ?></strong> &middot; <?= e(tgl_indo($berikut['rencana'], true)) ?></div>
      <div class="muted small"><?= e($I['puskesmas']) ?></div>
    </div>
  </div>
  <?php if ($berikut['status'] === 'terlambat'): ?>
    <div class="notice bad" style="margin-top:12px"><?= icon('alert', 20) ?><span>Periode K<?= (int) $berikut['kunjungan_ke'] ?> sudah lewat. Segera datang ke puskesmas agar bidan bisa memeriksa Ibu dan janin.</span></div>
  <?php endif; ?>
  <div class="row" style="margin-top:14px">
    <a class="btn btn-primary" href="<?= e(url('ibu/kunjungan.php?id=' . (int) $berikut['id'])) ?>">Lihat detail</a>
    <a class="btn btn-ghost" href="<?= e(url('ibu/jadwal.php')) ?>">Semua jadwal</a>
  </div>
</section>
<?php else: ?>
<section class="card"><div class="empty"><?= deco('hati', 84) ?><h2>Semua kunjungan sudah selesai</h2><p>Terima kasih sudah rutin memeriksakan kehamilan, Bu.</p></div></section>
<?php endif; ?>

<div class="grid-2">
  <section class="card">
    <div class="card-head"><h3>Kemajuan pemeriksaan</h3><span class="chip chip-selesai"><?= $selesai ?> dari 6</span></div>
    <div class="progress" role="progressbar" aria-valuenow="<?= $selesai ?>" aria-valuemin="0" aria-valuemax="6"><i style="width:<?= round($selesai / 6 * 100) ?>%"></i></div>
    <p class="muted small" style="margin-top:8px">Minimal 6 kali periksa: 2 kali di trimester 1, 1 kali di trimester 2, dan 3 kali di trimester 3.</p>
    <a href="<?= e(url('ibu/riwayat.php')) ?>">Lihat riwayat <?= icon('chev-r', 16) ?></a>
  </section>

  <section class="card tanda">
    <div class="card-head"><h3>Ada keluhan?</h3><?= icon('alert', 22) ?></div>
    <p>Perdarahan, kejang, nyeri kepala hebat, atau gerakan janin berkurang perlu segera ditangani.</p>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('ibu/tanda-bahaya.php')) ?>">Kenali tanda bahaya</a>
  </section>
</div>

<?php if ($notif): ?>
<section class="card" style="margin-top:14px">
  <div class="card-head"><h3>Pengingat terbaru</h3><?= icon('bell', 20) ?></div>
  <?php foreach ($notif as $n): ?>
    <div style="padding:8px 0;border-top:1px dashed var(--line)">
      <strong><?= e($n['judul']) ?></strong>
      <div class="muted small"><?= e($n['pesan']) ?> &middot; <?= e(tgl_indo($n['tanggal_kirim'], false, true)) ?></div>
    </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php page_end(); ?>
