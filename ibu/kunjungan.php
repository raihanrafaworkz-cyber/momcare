<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();

$j = null;
foreach ($I['jadwal'] as $x) {
    if ((int) $x['id'] === (int) ($_GET['id'] ?? 0)) { $j = $x; break; }
}
if (!$j) {
    http_response_code(404);
    page_start('Tidak ditemukan', 'jadwal');
    echo '<div class="card"><h2>Jadwal tidak ditemukan</h2><a href="' . e(url('ibu/jadwal.php')) . '">Kembali ke jadwal</a></div>';
    page_end();
    exit;
}
$k = $j['selesai'] ? db_row('SELECT kj.*, u.nama AS nakes FROM kunjungan kj LEFT JOIN users u ON u.id = kj.nakes_id WHERE kj.id = ?', [$j['kunjungan_id']]) : null;

function ya_tidak(string $label, $v): string
{
    return '<li class="' . ($v ? 'yes' : 'no') . '">' . icon($v ? 'check' : 'plus', 18) . e($label) . ($v ? '' : ' (tidak dilakukan)') . '</li>';
}

page_start('Kunjungan K' . $j['kunjungan_ke'], 'jadwal');
?>
<div class="page-head">
  <?= tombol_kembali('ibu/jadwal.php', 'jadwal') ?>
  <div><h1>Kunjungan K<?= (int) $j['kunjungan_ke'] ?></h1><div class="sub">Trimester <?= (int) $j['trimester'] ?></div></div>
</div>

<div class="card">
  <div class="row between"><strong><?= $j['selesai'] ? 'Sudah dilakukan' : 'Periode kunjungan' ?></strong><?= chip_status($j['status']) ?></div>
  <p style="margin:6px 0 0"><?= $j['selesai'] ? e(tgl_indo($j['tgl_kunjungan'], true)) : e(tgl_indo($j['tanggal_mulai'])) . ' sampai ' . e(tgl_indo($j['tanggal_akhir'])) ?></p>
  <?php if ($j['dokter']): ?><p class="muted small" style="margin:6px 0 0"><?= icon('stetho', 16) ?> Kunjungan ini termasuk pemeriksaan oleh dokter, termasuk USG.</p><?php endif; ?>
</div>

<?php if ($k): ?>
  <div class="card">
    <h2>Hasil pemeriksaan</h2>
    <div class="metrics">
      <div class="metric"><span>Tekanan darah</span><b><?= $k['td_sistol'] ? (int) $k['td_sistol'] . '/' . (int) $k['td_diastol'] : '-' ?></b> <small>mmHg</small></div>
      <div class="metric"><span>Berat badan</span><b><?= e(angka($k['berat_badan'] !== null ? (float) $k['berat_badan'] : null)) ?></b> <small>kg</small></div>
      <div class="metric"><span>Lingkar lengan</span><b><?= e(angka($k['lila'] !== null ? (float) $k['lila'] : null)) ?></b> <small>cm</small></div>
      <div class="metric"><span>Tinggi rahim</span><b><?= e(angka($k['tfu'] !== null ? (float) $k['tfu'] : null)) ?></b> <small>cm</small></div>
      <div class="metric"><span>Detak jantung janin</span><b><?= $k['djj'] ? (int) $k['djj'] : '-' ?></b> <small>x/menit</small></div>
      <div class="metric"><span>Hemoglobin</span><b><?= e(angka($k['hb'] !== null ? (float) $k['hb'] : null)) ?></b> <small>g/dL</small></div>
    </div>
    <ul class="checks">
      <?= ya_tidak('Periksa protein urin', $k['periksa_protein_urin']) ?>
      <?= ya_tidak('Skrining kesehatan jiwa', $k['skrining_jiwa']) ?>
      <?= ya_tidak('Tablet tambah darah diberikan', $k['ttd_diberikan']) ?>
      <?= ya_tidak('USG', $k['usg_dilakukan']) ?>
    </ul>
  </div>
  <?php if ($k['rujukan']): ?>
    <div class="notice warn"><?= icon('alert', 20) ?><span>Ibu dirujuk. <?= e($k['catatan_rujukan'] ?? '') ?></span></div>
  <?php endif; ?>
  <?php if ($k['catatan_nakes']): ?>
    <div class="card"><h3>Pesan dari <?= e($k['nakes'] ?? 'tenaga kesehatan') ?></h3><p><?= e($k['catatan_nakes']) ?></p></div>
  <?php endif; ?>
  <?php if ($k['tanggal_kembali']): ?>
    <div class="card"><h3>Kembali periksa</h3><p style="margin:0"><?= icon('calendar', 18) ?> <strong><?= e(tgl_indo($k['tanggal_kembali'], true)) ?></strong></p></div>
  <?php endif; ?>
<?php else: ?>
  <div class="card">
    <h2>Yang perlu dibawa</h2>
    <ul class="checks">
      <li class="yes"><?= icon('check', 18) ?>Buku KIA</li>
      <li class="yes"><?= icon('check', 18) ?>Kartu JKN / BPJS (bila ada)</li>
      <li class="yes"><?= icon('check', 18) ?>Hasil pemeriksaan sebelumnya</li>
    </ul>
  </div>
  <div class="card">
    <h3>Tempat periksa</h3>
    <p style="margin-bottom:4px"><?= icon('pin', 18) ?> <strong><?= e($I['puskesmas']) ?></strong></p>
    <p class="muted small" style="margin:0"><?= e($I['puskesmas_alamat'] ?? '') ?></p>
  </div>
<?php endif; ?>
<?php page_end(); ?>
