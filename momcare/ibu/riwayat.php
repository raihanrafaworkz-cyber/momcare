<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();

$rows = db_all(
    'SELECT kj.*, j.kunjungan_ke FROM kunjungan kj JOIN jadwal_anc j ON j.id = kj.jadwal_id
     WHERE kj.kehamilan_id = ? ORDER BY j.kunjungan_ke',
    [$I['kehamilan_id']]
);
// Grafik berat badan sederhana (SVG)
$bb = [];
if ($I['bb_sebelum_hamil'] !== null) {
    $bb[] = ['x' => 'Awal', 'y' => (float) $I['bb_sebelum_hamil']];
}
foreach ($rows as $r) {
    if ($r['berat_badan'] !== null) {
        $bb[] = ['x' => 'K' . $r['kunjungan_ke'], 'y' => (float) $r['berat_badan']];
    }
}
page_start('Riwayat', 'riwayat');
?>
<div class="page-head row between">
  <div><h1>Riwayat pemeriksaan</h1><div class="sub">Salinan catatan penting dari Buku KIA</div></div>
  <button class="btn btn-ghost btn-sm no-print" type="button" onclick="window.print()"><?= icon('print', 18) ?>Cetak</button>
</div>

<?php if (!$rows): ?>
  <div class="card"><div class="empty"><?= deco('kaki', 84) ?><h2>Belum ada kunjungan tercatat</h2><p>Setelah periksa di puskesmas, hasilnya muncul di sini.</p></div></div>
<?php else: ?>

<?php if (count($bb) >= 2):
    $w = 320; $h = 130; $px = 28; $py = 18;
    $ys = array_column($bb, 'y'); $min = floor(min($ys) - 1); $max = ceil(max($ys) + 1);
    $n = count($bb); $pts = [];
    foreach ($bb as $i => $p) {
        $pts[] = [$px + ($w - 2 * $px) * ($n > 1 ? $i / ($n - 1) : 0), $h - $py - ($h - 2 * $py) * (($p['y'] - $min) / max(1, $max - $min)), $p];
    }
    $garis = implode(' ', array_map(fn($p) => round($p[0], 1) . ',' . round($p[1], 1), $pts));
?>
<div class="card">
  <div class="card-head"><h3>Berat badan</h3><span class="muted small">kg, dari sebelum hamil</span></div>
  <svg class="chart" viewBox="0 0 <?= $w ?> <?= $h + 22 ?>" role="img" aria-label="Grafik berat badan: <?= e(implode(', ', array_map(fn($p) => $p['x'] . ' ' . angka($p['y']) . ' kg', $bb))) ?>">
    <polyline points="<?= $garis ?>" fill="none" stroke="#FF2E93" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
    <?php foreach ($pts as [$x, $y, $p]): ?>
      <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="6" fill="#fff" stroke="#E0187A" stroke-width="3"/>
      <text x="<?= round($x, 1) ?>" y="<?= round($y - 11, 1) ?>" text-anchor="middle" font-size="11" font-weight="800" fill="#8E0B4A"><?= e(angka($p['y'])) ?></text>
      <text x="<?= round($x, 1) ?>" y="<?= $h + 14 ?>" text-anchor="middle" font-size="11" font-weight="700" fill="#6E4361"><?= e($p['x']) ?></text>
    <?php endforeach; ?>
  </svg>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Kunjungan</th><th>Tanggal</th><th>TD</th><th>BB (kg)</th><th>TFU (cm)</th><th>DJJ</th><th>Hb</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= e(url('ibu/kunjungan.php?id=' . (int) $r['jadwal_id'])) ?>">K<?= (int) $r['kunjungan_ke'] ?></a></td>
          <td><?= e(tgl_indo($r['tanggal'], false, true)) ?></td>
          <td><?= $r['td_sistol'] ? (int) $r['td_sistol'] . '/' . (int) $r['td_diastol'] : '-' ?></td>
          <td><?= e(angka($r['berat_badan'] !== null ? (float) $r['berat_badan'] : null)) ?></td>
          <td><?= e(angka($r['tfu'] !== null ? (float) $r['tfu'] : null)) ?></td>
          <td><?= $r['djj'] ? (int) $r['djj'] : '-' ?></td>
          <td><?= e(angka($r['hb'] !== null ? (float) $r['hb'] : null)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<p class="muted small">Catatan ini pelengkap, bukan pengganti Buku KIA. Tetap bawa Buku KIA setiap periksa.</p>
<?php page_end(); ?>
