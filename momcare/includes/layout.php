<?php
/** Kerangka halaman: sidebar (desktop), tab bawah (ponsel), pesan flash. */

function layout_state(?array $set = null): array
{
    static $s = ['area' => 'ibu', 'aktif' => ''];
    if ($set !== null) {
        $s = $set;
    }
    return $s;
}

/** Menu utama per area. [kunci, label, ikon, tautan] */
function menu_utama(string $area): array
{
    if ($area === 'ibu') {
        return [
            ['beranda',    'Beranda',   'home',      'ibu/beranda.php'],
            ['jadwal',     'Jadwal',    'calendar',  'ibu/jadwal.php'],
            ['riwayat',    'Riwayat',   'clipboard', 'ibu/riwayat.php'],
            ['persalinan', 'Persiapan', 'heart',     'ibu/persalinan.php'],
            ['profil',     'Profil',    'user',      'ibu/profil.php'],
        ];
    }
    $u = user_saat_ini();
    $m = [['dashboard', 'Dasbor', 'home', 'nakes/index.php']];
    if ($u && in_array($u['role'], ['nakes', 'admin'], true)) {
        $m[] = ['ibu-baru', 'Daftarkan ibu', 'plus', 'nakes/ibu-baru.php'];
        $m[] = ['kunjungan-baru', 'Catat kunjungan', 'clipboard', 'nakes/kunjungan-baru.php'];
    }
    return $m;
}

/** Menu tambahan di sidebar ibu. */
function menu_tambahan(string $area): array
{
    if ($area !== 'ibu') {
        return [];
    }
    return [
        ['hasil-dokter', 'Hasil dokter',        'stetho', 'ibu/hasil-dokter.php'],
        ['nifas',        'Setelah melahirkan',  'baby',   'ibu/nifas.php'],
        ['tanda-bahaya', 'Tanda bahaya',        'alert',  'ibu/tanda-bahaya.php'],
    ];
}

function page_start(string $judul, string $aktif = '', string $area = 'ibu'): void
{
    layout_state(['area' => $area, 'aktif' => $aktif]);
    $u    = user_saat_ini();
    $css  = __DIR__ . '/../assets/css/style.css';
    $ver  = is_file($css) ? filemtime($css) : 1;
    ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($judul) ?> | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= (int) $ver ?>">
</head>
<body class="area-<?= e($area) ?>">
<a class="skip" href="#isi">Lewati ke isi</a>
<div class="app">
  <aside class="sidebar" aria-label="Navigasi samping">
    <a class="brand" href="<?= e(url($u ? beranda_untuk($u['role']) : 'index.php')) ?>">
      <span class="brand-mark"><?= icon('heart', 22) ?></span>
      <span class="brand-name"><?= e(APP_NAME) ?></span>
    </a>
    <nav class="side-nav">
      <?php foreach (menu_utama($area) as [$k, $label, $ikon, $link]): ?>
        <a href="<?= e(url($link)) ?>" class="<?= $k === $aktif ? 'on' : '' ?>"<?= $k === $aktif ? ' aria-current="page"' : '' ?>><?= icon($ikon, 20) ?><span><?= e($label) ?></span></a>
      <?php endforeach; ?>
      <?php $tambahan = menu_tambahan($area); if ($tambahan): ?>
        <span class="side-sep">Lainnya</span>
        <?php foreach ($tambahan as [$k, $label, $ikon, $link]): ?>
          <a href="<?= e(url($link)) ?>" class="<?= $k === $aktif ? 'on' : '' ?>"<?= $k === $aktif ? ' aria-current="page"' : '' ?>><?= icon($ikon, 20) ?><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>
    <?php if ($u): ?>
    <div class="side-user">
      <span class="avatar"><?= e(mb_strtoupper(mb_substr($u['nama'], 0, 1))) ?></span>
      <span class="who"><strong><?= e($u['nama']) ?></strong><small><?= e($u['jabatan'] ?? ($u['role'] === 'ibu' ? 'Ibu hamil' : ucfirst($u['role']))) ?></small></span>
      <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Keluar"><?= icon('logout', 20) ?></button></form>
    </div>
    <?php endif; ?>
    <?= deco('kaki', 90, 'side-deco') ?>
  </aside>

  <main class="main" id="isi">
    <header class="topbar">
      <a class="brand" href="<?= e(url($u ? beranda_untuk($u['role']) : 'index.php')) ?>">
        <span class="brand-mark"><?= icon('heart', 20) ?></span><span class="brand-name"><?= e(APP_NAME) ?></span>
      </a>
      <?php if ($u): ?>
      <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Keluar"><?= icon('logout', 20) ?></button></form>
      <?php endif; ?>
    </header>
    <div class="content">
<?php foreach (flash_pull() as [$tipe, $pesan]): ?>
      <div class="flash flash-<?= e($tipe) ?>" role="status"><?= icon($tipe === 'ok' ? 'check' : 'alert', 20) ?><span><?= e($pesan) ?></span></div>
<?php endforeach; ?>
<?php
}

function page_end(?string $area = null, ?string $aktif = null): void
{
    $s     = layout_state();
    $area  = $area  ?? $s['area'];
    $aktif = $aktif ?? $s['aktif'];
    ?>
    </div>
  </main>

  <nav class="bottom-nav" aria-label="Menu utama">
    <?php foreach (menu_utama($area) as [$k, $label, $ikon, $link]): ?>
      <a href="<?= e(url($link)) ?>" class="<?= $k === $aktif ? 'on' : '' ?>"<?= $k === $aktif ? ' aria-current="page"' : '' ?>>
        <span class="pill"><?= icon($ikon, 22) ?></span><?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</div>
</body>
</html>
<?php
}

/** Chip status jadwal: ikon + teks supaya tidak bergantung pada warna saja. */
function chip_status(string $status): string
{
    $ikon = ['selesai' => 'check', 'perlu' => 'clipboard', 'akan' => 'calendar', 'terlambat' => 'alert'][$status] ?? 'calendar';
    return '<span class="chip chip-' . e($status) . '">' . icon($ikon, 14) . e(STATUS_LABEL[$status] ?? $status) . '</span>';
}

/** Kembali ke halaman sebelumnya (subhalaman). */
function tombol_kembali(string $link, string $label): string
{
    return '<a class="back" href="' . e(url($link)) . '" aria-label="Kembali ke ' . e($label) . '">' . icon('chev-l', 22) . '</a>';
}
