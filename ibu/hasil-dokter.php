<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();
$rs = db_all('SELECT r.*, u.nama AS dokter FROM ringkasan_dokter r LEFT JOIN users u ON u.id = r.dokter_id WHERE r.kehamilan_id = ? ORDER BY r.trimester', [$I['kehamilan_id']]);
$per = [];
foreach ($rs as $r) { $per[(int) $r['trimester']] = $r; }
$risiko = ['rendah' => 'Rendah', 'sedang' => 'Sedang', 'tinggi' => 'Tinggi'];
page_start('Hasil dokter', 'hasil-dokter');
?>
<div class="page-head"><div><h1>Hasil pemeriksaan dokter</h1><div class="sub">Ringkasan per trimester</div></div></div>
<?php foreach ([1, 2, 3] as $t): $r = $per[$t] ?? null; ?>
  <section class="card">
    <div class="card-head"><h2>Trimester <?= $t ?></h2>
      <?php if ($r): ?><span class="chip chip-selesai"><?= icon('check', 14) ?>Sudah diperiksa</span>
      <?php elseif ($t <= $I['tri']): ?><span class="chip chip-perlu">Belum tercatat</span>
      <?php else: ?><span class="chip chip-akan">Akan datang</span><?php endif; ?></div>
    <?php if ($r): ?>
      <dl class="kv">
        <dt>Tanggal</dt><dd><?= e(tgl_indo($r['tanggal'])) ?></dd>
        <dt>Dokter</dt><dd><?= e($r['dokter'] ?? '-') ?></dd>
        <dt>Risiko preeklamsia</dt><dd><?= e($risiko[$r['risiko_preeklampsia']] ?? '-') ?></dd>
        <dt>Hasil USG</dt><dd><?= $r['usg_status'] === 'normal' ? 'Normal' : ($r['usg_status'] === 'curiga' ? 'Perlu pemeriksaan lanjut' : '-') ?></dd>
        <dt>HPL menurut USG</dt><dd><?= e(tgl_indo($r['hpl_usg'])) ?></dd>
        <dt>Skrining jiwa dirujuk</dt><dd><?= $r['skrining_jiwa_rujukan'] ? 'Ya' : 'Tidak' ?></dd>
        <dt>Skrining diabetes ditindaklanjuti</dt><dd><?= $r['skrining_dm_tindak_lanjut'] ? 'Ya' : 'Tidak' ?></dd>
        <dt>Rekomendasi tempat bersalin</dt><dd><?= $r['rekomendasi_tempat'] === 'FKRTL' ? 'Rumah sakit (FKRTL)' : ($r['rekomendasi_tempat'] === 'FKTP' ? 'Puskesmas / klinik (FKTP)' : '-') ?></dd>
      </dl>
    <?php else: ?>
      <p class="muted" style="margin:0">Ringkasan dokter muncul di sini setelah pemeriksaan dokter pada trimester ini tercatat (kunjungan K1 dan K5).</p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php page_end(); ?>
