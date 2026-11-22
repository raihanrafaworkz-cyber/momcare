<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();
$tanda = [
    'Keluar darah dari jalan lahir',
    'Kejang',
    'Sakit kepala hebat yang tidak hilang, pandangan kabur',
    'Bengkak di wajah, tangan, atau kaki disertai nyeri kepala',
    'Demam tinggi',
    'Ketuban pecah sebelum waktunya',
    'Gerakan janin berkurang atau tidak terasa',
    'Nyeri perut hebat',
    'Muntah terus-menerus dan tidak bisa makan atau minum',
];
page_start('Tanda bahaya', 'tanda-bahaya');
?>
<div class="page-head"><div><h1>Tanda bahaya kehamilan</h1><div class="sub">Jangan menunggu jadwal kontrol</div></div></div>
<div class="notice bad"><?= icon('alert', 20) ?><span>Bila Ibu mengalami salah satu tanda ini, segera ke puskesmas atau rumah sakit terdekat.</span></div>
<div class="card tanda">
  <ul>
    <?php foreach ($tanda as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
  </ul>
</div>
<div class="card">
  <h3>Hubungi <?= e($I['puskesmas']) ?></h3>
  <p class="muted"><?= e($I['puskesmas_alamat'] ?? '') ?></p>
  <?php if ($I['puskesmas_telepon']): ?>
    <a class="btn btn-primary btn-block" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $I['puskesmas_telepon'])) ?>"><?= icon('phone', 20) ?>Telepon <?= e($I['puskesmas_telepon']) ?></a>
  <?php endif; ?>
  <p class="muted small" style="margin-top:12px">Darurat: hubungi 119 atau minta keluarga mengantar ke fasilitas kesehatan terdekat.</p>
</div>
<?php page_end(); ?>
