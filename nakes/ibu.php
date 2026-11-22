<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = wajib_masuk(['nakes', 'kader', 'admin']);
$k = kehamilan_boleh($u, (int) ($_GET['id'] ?? 0));
if (!$k) {
    http_response_code(404);
    page_start('Tidak ditemukan', 'dashboard', 'nakes');
    echo '<div class="card"><h2>Data tidak ditemukan</h2><a href="' . e(url('nakes/index.php')) . '">Kembali ke dasbor</a></div>';
    page_end();
    exit;
}
$jadwal = jadwal_lengkap((int) $k['id']);
$usia   = usia_kehamilan($k['hpht']);
$lihatKlinis = bisa_catat($u);
$kunj = $lihatKlinis ? db_all('SELECT kj.*, j.kunjungan_ke FROM kunjungan kj JOIN jadwal_anc j ON j.id = kj.jadwal_id WHERE kj.kehamilan_id = ? ORDER BY j.kunjungan_ke', [$k['id']]) : [];
$perlu = array_filter($jadwal, fn($j) => !$j['selesai']);

page_start($k['nama'], 'dashboard', 'nakes');
?>
<div class="page-head row between">
  <div class="row"><?= tombol_kembali('nakes/index.php', 'dasbor') ?><div><h1><?= e($k['nama']) ?></h1><div class="sub"><?= e($k['no_hp']) ?></div></div></div>
  <?php if ($lihatKlinis && $perlu): ?><a class="btn btn-primary btn-sm" href="<?= e(url('nakes/kunjungan-baru.php?k=' . (int) $k['id'])) ?>">Catat kunjungan</a><?php endif; ?>
</div>

<div class="card">
  <dl class="kv">
    <dt>Usia kehamilan</dt><dd><?= (int) $usia['minggu'] ?> minggu <?= (int) $usia['hari'] ?> hari</dd>
    <dt>HPHT / HPL</dt><dd><?= e(tgl_indo($k['hpht'], false, true)) ?> / <?= e(tgl_indo($k['hpl'], false, true)) ?></dd>
    <dt>G / P / A</dt><dd>G<?= (int) $k['gravida'] ?> P<?= (int) $k['paritas'] ?> A<?= (int) $k['abortus'] ?></dd>
    <?php if ($lihatKlinis): ?>
    <dt>Golongan darah</dt><dd><?= e(($k['gol_darah'] ?? '-') . ($k['gol_darah'] ? $k['rhesus'] : '')) ?></dd>
    <dt>No. JKN</dt><dd><?= e($k['no_jkn'] ?? '-') ?></dd>
    <?php endif; ?>
  </dl>
</div>

<div class="card">
  <h2>Status kunjungan</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>K</th><th>Periode</th><th>Status</th><th>Dilakukan</th></tr></thead>
    <tbody>
    <?php foreach ($jadwal as $j): ?>
      <tr><td>K<?= (int) $j['kunjungan_ke'] ?><?= $j['dokter'] ? ' ' . icon('stetho', 14) : '' ?></td>
        <td><?= e(tgl_indo($j['tanggal_mulai'], false, true)) ?> &ndash; <?= e(tgl_indo($j['tanggal_akhir'], false, true)) ?></td>
        <td><?= chip_status($j['status']) ?></td>
        <td><?= $j['selesai'] ? e(tgl_indo($j['tgl_kunjungan'], false, true)) : '-' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php if ($lihatKlinis && $kunj): ?>
<div class="card">
  <h2>Hasil kunjungan</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>K</th><th>TD</th><th>BB</th><th>LILA</th><th>TFU</th><th>DJJ</th><th>Hb</th><th>Rujuk</th></tr></thead>
    <tbody>
    <?php foreach ($kunj as $r): ?>
      <tr><td>K<?= (int) $r['kunjungan_ke'] ?></td>
        <td><?= $r['td_sistol'] ? (int) $r['td_sistol'] . '/' . (int) $r['td_diastol'] : '-' ?></td>
        <td><?= e(angka($r['berat_badan'] !== null ? (float) $r['berat_badan'] : null)) ?></td>
        <td><?= e(angka($r['lila'] !== null ? (float) $r['lila'] : null)) ?></td>
        <td><?= e(angka($r['tfu'] !== null ? (float) $r['tfu'] : null)) ?></td>
        <td><?= $r['djj'] ? (int) $r['djj'] : '-' ?></td>
        <td><?= e(angka($r['hb'] !== null ? (float) $r['hb'] : null)) ?></td>
        <td><?= $r['rujukan'] ? 'Ya' : '-' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php page_end(); ?>
