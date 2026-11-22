<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();
$rows = db_all('SELECT * FROM nifas WHERE kehamilan_id = ? ORDER BY kf_ke', [$I['kehamilan_id']]);
$per = [];
foreach ($rows as $r) { $per[(int) $r['kf_ke']] = $r; }
$kf = [
    1 => ['6 jam sampai 2 hari setelah melahirkan', 'Periksa perdarahan, tekanan darah, dan menyusui.'],
    2 => ['Hari ke-3 sampai ke-7', 'Periksa luka, rahim, dan kondisi bayi.'],
    3 => ['Hari ke-8 sampai ke-28', 'Pantau pemulihan dan konseling KB.'],
    4 => ['Hari ke-29 sampai ke-42', 'Pemeriksaan akhir masa nifas dan pilihan KB.'],
];
page_start('Setelah melahirkan', 'nifas');
?>
<div class="page-head"><div><h1>Setelah melahirkan</h1><div class="sub">Empat kali kontrol nifas (KF1 sampai KF4)</div></div></div>
<?php if (!$I['tanggal_persalinan'] && !$rows): ?>
  <div class="notice"><?= icon('baby', 20) ?><span>Bagian ini aktif setelah persalinan. Ibu bisa melihat jadwalnya lebih dulu.</span></div>
<?php endif; ?>
<ol class="tl">
<?php foreach ($kf as $n => [$kapan, $isi]): $r = $per[$n] ?? null; ?>
  <li class="<?= $r ? 'done' : '' ?>">
    <span class="dot"><?= $r ? icon('check', 20) : 'KF' . $n ?></span>
    <div class="card" style="margin:0">
      <div class="row between"><h3 style="margin:0">KF<?= $n ?></h3><?= $r ? chip_status('selesai') : '<span class="chip chip-akan">Belum</span>' ?></div>
      <div class="muted small"><?= e($kapan) ?></div>
      <p style="margin:6px 0 0"><?= e($isi) ?></p>
      <?php if ($r): ?>
        <dl class="kv" style="margin-top:8px">
          <dt>Tanggal</dt><dd><?= e(tgl_indo($r['tanggal'], false, true)) ?></dd>
          <dt>Keadaan ibu</dt><dd><?= e($r['keadaan_ibu'] ?? '-') ?></dd>
          <dt>Vitamin A</dt><dd><?= $r['vitamin_a'] ? 'Diberikan' : '-' ?></dd>
          <dt>KB</dt><dd><?= e($r['kb'] ?? '-') ?></dd>
        </dl>
      <?php endif; ?>
    </div>
  </li>
<?php endforeach; ?>
</ol>
<?php page_end(); ?>
