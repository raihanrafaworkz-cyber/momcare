<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();
$kolom = [
    'tempat'       => ['Tempat bersalin', 'Contoh: Puskesmas Melati'],
    'penolong'     => ['Penolong persalinan', 'Contoh: Bidan Ratna'],
    'pendamping'   => ['Pendamping', 'Contoh: Suami'],
    'biaya'        => ['Biaya / jaminan', 'Contoh: Kartu JKN aktif'],
    'transportasi' => ['Kendaraan ke tempat bersalin', 'Contoh: Motor tetangga, Pak RT'],
    'donor_darah'  => ['Calon pendonor darah', 'Contoh: Kakak, golongan darah sama'],
];
if (is_post()) {
    csrf_check();
    $nilai = [];
    foreach ($kolom as $k => $_) { $nilai[$k] = post_teks($k, 120) ?: null; }
    $ada = db_val('SELECT 1 FROM persiapan_persalinan WHERE kehamilan_id = ?', [$I['kehamilan_id']]);
    if ($ada) {
        db_run('UPDATE persiapan_persalinan SET tempat=?, penolong=?, pendamping=?, biaya=?, transportasi=?, donor_darah=? WHERE kehamilan_id=?',
            [...array_values($nilai), $I['kehamilan_id']]);
    } else {
        db_run('INSERT INTO persiapan_persalinan (tempat, penolong, pendamping, biaya, transportasi, donor_darah, kehamilan_id) VALUES (?,?,?,?,?,?,?)',
            [...array_values($nilai), $I['kehamilan_id']]);
    }
    flash('ok', 'Rencana persalinan tersimpan.');
    redirect('ibu/persalinan.php');
}
$p = db_row('SELECT * FROM persiapan_persalinan WHERE kehamilan_id = ?', [$I['kehamilan_id']]) ?? [];
$n = hitung_persiapan($p);
page_start('Persiapan persalinan', 'persalinan');
?>
<div class="page-head"><div><h1>Persiapan persalinan</h1><div class="sub">Rencanakan dari sekarang, Bu</div></div></div>
<div class="card">
  <div class="card-head"><h3>Sudah disiapkan</h3><span class="chip chip-<?= $n === 6 ? 'selesai' : 'perlu' ?>"><?= $n ?> dari 6</span></div>
  <div class="progress" role="progressbar" aria-valuenow="<?= $n ?>" aria-valuemin="0" aria-valuemax="6"><i style="width:<?= round($n / 6 * 100) ?>%"></i></div>
</div>
<form class="card" method="post">
  <?= csrf_field() ?>
  <?php foreach ($kolom as $k => [$label, $contoh]): ?>
    <div class="field">
      <label for="<?= $k ?>"><?= e($label) ?></label>
      <input class="input" id="<?= $k ?>" name="<?= $k ?>" maxlength="120" value="<?= e($p[$k] ?? '') ?>" placeholder="<?= e($contoh) ?>">
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary btn-block" type="submit">Simpan rencana</button>
</form>
<?php page_end(); ?>
