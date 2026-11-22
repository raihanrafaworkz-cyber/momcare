<?php
require __DIR__ . '/includes/bootstrap.php';

if (user_saat_ini()) {
    redirect('index.php');
}

$galat = '';
$identitas = '';
if (is_post()) {
    csrf_check();
    $identitas = post_teks('identitas', 120);
    $sandi     = (string) ($_POST['sandi'] ?? '');
    if ($identitas === '' || $sandi === '') {
        $galat = 'Isi nomor HP / email dan kata sandi.';
    } elseif (masuk($identitas, $sandi)) {
        redirect('index.php');
    } else {
        $galat = 'Nomor HP / email atau kata sandi salah.';
    }
}
$css = filemtime(__DIR__ . '/assets/css/style.css');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= (int) $css ?>">
</head>
<body class="login-body">
<?= deco('kaki', 120, 'float-deco fd1') ?>
<style>
  .fd1{left:4%;top:8%;transform:rotate(-18deg);opacity:.7}
  .fd2{right:5%;top:14%;width:90px;height:90px;transform:rotate(14deg)}
  .fd3{left:6%;bottom:9%;width:100px;height:100px;transform:rotate(-8deg)}
  .fd4{right:7%;bottom:12%;width:110px;height:110px;transform:rotate(10deg)}
  @media (max-width:700px){.float-deco{display:none}}
</style>
<?= deco('botol', 90, 'float-deco fd2') ?>
<?= deco('dot', 100, 'float-deco fd3') ?>
<?= deco('kereta', 110, 'float-deco fd4') ?>
<main class="login">
  <div class="hero">
    <div class="bubble b1"></div>
    <?= ilustrasi_ibu() ?>
    <h1><?= e(APP_NAME) ?></h1>
    <p>Pengingat kunjungan dan catatan kehamilan Ibu, tersimpan aman.</p>
  </div>

  <form class="card" method="post" novalidate>
    <?= csrf_field() ?>
    <h2>Masuk</h2>
    <?php if ($galat): ?><div class="flash flash-err" role="alert"><?= icon('alert', 20) ?><span><?= e($galat) ?></span></div><?php endif; ?>
    <div class="field">
      <label for="identitas">Nomor HP atau email</label>
      <input class="input" id="identitas" name="identitas" value="<?= e($identitas) ?>" autocomplete="username" inputmode="email" required>
    </div>
    <div class="field">
      <label for="sandi">Kata sandi</label>
      <input class="input" id="sandi" name="sandi" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit"><?= icon('lock', 20) ?>Masuk</button>
  </form>

  <?php if (APP_DEMO): ?>
  <div class="demo">
    <strong>Akun demo</strong> (kata sandi semua: <code>momcare123</code>)
    <table>
      <tr><td>Ibu hamil</td><td><code>081234567890</code> Sari (K4 berjalan)</td></tr>
      <tr><td></td><td><code>081234567891</code> Dewi (K3 perlu)</td></tr>
      <tr><td></td><td><code>081234567892</code> Rina (K3 terlambat)</td></tr>
      <tr><td>Bidan</td><td><code>bidan@momcare.test</code></td></tr>
      <tr><td>Dokter</td><td><code>dokter@momcare.test</code></td></tr>
      <tr><td>Kader</td><td><code>kader@momcare.test</code></td></tr>
      <tr><td>Admin</td><td><code>admin@momcare.test</code></td></tr>
    </table>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
