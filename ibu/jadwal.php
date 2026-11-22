<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();
page_start('Jadwal kunjungan', 'jadwal');
?>
<div class="page-head"><div><h1>Jadwal kunjungan</h1><div class="sub">K1 sampai K6, dihitung dari HPHT <?= e(tgl_indo($I['hpht'])) ?></div></div></div>

<ol class="tl">
<?php foreach ($I['jadwal'] as $j):
    $kelas = $j['status'] === 'selesai' ? 'done' : ($j['status'] === 'terlambat' ? 'late' : ($j['status'] === 'perlu' ? 'now' : '')); ?>
  <li class="<?= $kelas ?>">
    <span class="dot"><?= $j['status'] === 'selesai' ? icon('check', 20) : 'K' . (int) $j['kunjungan_ke'] ?></span>
    <a class="item" href="<?= e(url('ibu/kunjungan.php?id=' . (int) $j['id'])) ?>">
      <div class="row between">
        <h3>Kunjungan K<?= (int) $j['kunjungan_ke'] ?></h3>
        <?= chip_status($j['status']) ?>
      </div>
      <div class="muted small">
        <?= $j['selesai'] ? 'Dilakukan ' . e(tgl_indo($j['tgl_kunjungan'])) : 'Periode ' . e(tgl_indo($j['tanggal_mulai'], false, true)) . ' sampai ' . e(tgl_indo($j['tanggal_akhir'], false, true)) ?>
      </div>
      <div class="muted small">Trimester <?= (int) $j['trimester'] ?><?= $j['dokter'] ? ' &middot; <span class="chip chip-dokter">' . icon('stetho', 14) . 'Periksa dokter</span>' : '' ?></div>
    </a>
  </li>
<?php endforeach; ?>
</ol>
<?php page_end(); ?>
