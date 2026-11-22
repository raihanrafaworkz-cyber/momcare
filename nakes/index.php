<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = wajib_masuk(['nakes', 'kader', 'admin']);

$semua = daftar_kehamilan_aktif($u['puskesmas_id'] ? (int) $u['puskesmas_id'] : null);
$cnt = ['semua' => count($semua), 'terlambat' => 0, 'perlu' => 0, 'akan' => 0, 'selesai' => 0];
foreach ($semua as $b) { $cnt[$b['status']]++; }

$awalBulan = date('Y-m-01');
$bulanIni  = (int) db_val(
    'SELECT COUNT(*) FROM kunjungan WHERE tanggal >= ?' . ($u['puskesmas_id'] ? ' AND puskesmas_id = ?' : ''),
    $u['puskesmas_id'] ? [$awalBulan, $u['puskesmas_id']] : [$awalBulan]
);

$f = $_GET['f'] ?? 'semua';
if (!isset($cnt[$f])) { $f = 'semua'; }
$q = trim((string) ($_GET['q'] ?? ''));
$tampil = array_values(array_filter($semua, function ($b) use ($f, $q) {
    if ($f !== 'semua' && $b['status'] !== $f) { return false; }
    return $q === '' || mb_stripos($b['nama'], $q) !== false || str_contains($b['no_hp'], $q);
}));
// Urutkan: terlambat dulu, lalu perlu, lalu paling dekat HPL
$urut = ['terlambat' => 0, 'perlu' => 1, 'akan' => 2, 'selesai' => 3];
usort($tampil, fn($a, $b) => [$urut[$a['status']], $a['hpl']] <=> [$urut[$b['status']], $b['hpl']]);

page_start('Dasbor', 'dashboard', 'nakes');
?>
<div class="page-head row between">
  <div><h1>Dasbor puskesmas</h1><div class="sub"><?= e($u['puskesmas'] ?? 'Semua puskesmas') ?> &middot; <?= e(tgl_indo(hari_ini(), true)) ?></div></div>
  <?php if (bisa_catat($u)): ?><a class="btn btn-primary btn-sm" href="<?= e(url('nakes/ibu-baru.php')) ?>"><?= icon('plus', 18) ?>Daftarkan ibu</a><?php endif; ?>
</div>

<div class="stats">
  <div class="stat"><b><?= $cnt['semua'] ?></b><span>Ibu hamil aktif</span></div>
  <div class="stat"><b><?= $cnt['perlu'] ?></b><span>Perlu kunjungan</span></div>
  <div class="stat bad"><b><?= $cnt['terlambat'] ?></b><span>Terlambat</span></div>
  <div class="stat"><b><?= $bulanIni ?></b><span>Kunjungan bulan ini</span></div>
</div>

<?php if ($u['role'] === 'kader'): ?>
  <div class="notice"><?= icon('users', 20) ?><span>Akun kader hanya melihat status kunjungan agar bisa mengingatkan ibu. Hasil pemeriksaan diisi bidan.</span></div>
<?php endif; ?>

<div class="card">
  <form class="row no-print" method="get" style="margin-bottom:12px">
    <input type="hidden" name="f" value="<?= e($f) ?>">
    <input class="input" style="flex:1;min-width:180px" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau nomor HP" aria-label="Cari ibu">
    <button class="btn btn-ghost btn-sm" type="submit">Cari</button>
  </form>
  <div class="filters no-print">
    <?php foreach (['semua' => 'Semua', 'terlambat' => 'Terlambat', 'perlu' => 'Perlu dilakukan', 'akan' => 'Akan datang', 'selesai' => 'Selesai'] as $k => $l): ?>
      <a href="?f=<?= $k ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="<?= $k === $f ? 'on' : '' ?>"><?= e($l) ?> (<?= $cnt[$k] ?>)</a>
    <?php endforeach; ?>
  </div>

  <?php if (!$tampil): ?>
    <div class="empty"><?= deco('hati', 84) ?><p>Tidak ada ibu yang cocok dengan filter ini.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Usia hamil</th><th>HPL</th><th>Berikutnya</th><th>Status</th><th>K selesai</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($tampil as $b): ?>
        <tr>
          <td><a href="<?= e(url('nakes/ibu.php?id=' . (int) $b['kehamilan_id'])) ?>"><?= e($b['nama']) ?></a><div class="muted small"><?= e($b['no_hp']) ?></div></td>
          <td><?= (int) $b['usia']['minggu'] ?> mgg <?= (int) $b['usia']['hari'] ?> hr<div class="muted small">Trimester <?= (int) $b['tri'] ?></div></td>
          <td><?= e(tgl_indo($b['hpl'], false, true)) ?></td>
          <td><?php if ($b['berikut']): ?>K<?= (int) $b['berikut']['kunjungan_ke'] ?><div class="muted small"><?= e(tgl_indo($b['berikut']['rencana'], false, true)) ?></div><?php else: ?>-<?php endif; ?></td>
          <td><?= chip_status($b['status']) ?></td>
          <td><?= (int) $b['selesai'] ?>/6</td>
          <td class="no-print"><?php if (bisa_catat($u) && $b['berikut']): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('nakes/kunjungan-baru.php?k=' . (int) $b['kehamilan_id'])) ?>">Catat</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php page_end(); ?>
