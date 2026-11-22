<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = wajib_masuk(['nakes', 'admin']);

$galat = [];
$v = ['nama' => '', 'no_hp' => '', 'tanggal_lahir' => '', 'no_jkn' => '', 'alamat' => '', 'gol_darah' => '', 'rhesus' => '',
      'hpht' => '', 'gravida' => '1', 'paritas' => '0', 'abortus' => '0', 'bb' => '', 'k_nama' => '', 'k_hubungan' => 'Suami', 'k_hp' => ''];

if (is_post()) {
    csrf_check();
    foreach ($v as $k => $_) { $v[$k] = post_teks($k, 200); }
    $hp = preg_replace('/[^0-9]/', '', $v['no_hp']);
    $v['no_hp'] = $hp;

    if ($v['nama'] === '') { $galat[] = 'Nama ibu wajib diisi.'; }
    if (strlen($hp) < 9 || strlen($hp) > 15) { $galat[] = 'Nomor HP harus 9 sampai 15 angka.'; }
    elseif (db_val('SELECT 1 FROM users WHERE no_hp = ?', [$hp])) { $galat[] = 'Nomor HP ini sudah terdaftar.'; }
    if (!valid_tanggal($v['hpht'])) { $galat[] = 'Isi HPHT (hari pertama haid terakhir) dengan tanggal yang benar.'; }
    elseif ($v['hpht'] > hari_ini()) { $galat[] = 'HPHT tidak boleh di masa depan.'; }
    elseif (selisih_hari($v['hpht'], hari_ini()) > 300) { $galat[] = 'HPHT lebih dari 300 hari lalu. Periksa kembali tanggalnya.'; }
    if ($v['tanggal_lahir'] !== '' && !valid_tanggal($v['tanggal_lahir'])) { $galat[] = 'Tanggal lahir tidak valid.'; }
    if (!in_array($v['gol_darah'], ['', 'A', 'B', 'AB', 'O'], true)) { $galat[] = 'Golongan darah tidak valid.'; }
    if (!in_array($v['rhesus'], ['', '+', '-'], true)) { $galat[] = 'Rhesus tidak valid.'; }
    $g = (int) $v['gravida']; $p = (int) $v['paritas']; $a = (int) $v['abortus'];
    if ($g < 1 || $g > 20 || $p < 0 || $p > 20 || $a < 0 || $a > 20) { $galat[] = 'Isian G / P / A tidak wajar.'; }
    $bb = null;
    if ($v['bb'] !== '') { [$bb, $eb] = angka_rentang('bb', 'Berat badan sebelum hamil', 25, 200); if ($eb) { $galat[] = $eb; } }
    $kHp = preg_replace('/[^0-9+]/', '', $v['k_hp']);
    if ($v['k_nama'] !== '' && strlen($kHp) < 8) { $galat[] = 'Nomor HP keluarga tidak valid.'; }

    if (!$galat) {
        $abjad = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $sandi = '';
        for ($i = 0; $i < 8; $i++) { $sandi .= $abjad[random_int(0, strlen($abjad) - 1)]; }
        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_run('INSERT INTO users (puskesmas_id, role, nama, no_hp, password_hash) VALUES (?, \'ibu\', ?, ?, ?)',
                [$u['puskesmas_id'] ?: 1, $v['nama'], $hp, password_hash($sandi, PASSWORD_DEFAULT)]);
            $uid = (int) $pdo->lastInsertId();
            db_run('INSERT INTO ibu_hamil (user_id, no_jkn, tanggal_lahir, alamat, gol_darah, rhesus, persetujuan_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$uid, $v['no_jkn'] ?: null, $v['tanggal_lahir'] ?: null, $v['alamat'] ?: null, $v['gol_darah'] ?: null, $v['rhesus'] ?: null, date('Y-m-d H:i:s')]);
            $ibuId = (int) $pdo->lastInsertId();
            if ($v['k_nama'] !== '') {
                db_run('INSERT INTO kontak_keluarga (ibu_id, nama, hubungan, no_hp) VALUES (?, ?, ?, ?)', [$ibuId, $v['k_nama'], $v['k_hubungan'] ?: 'Suami', $kHp]);
            }
            db_run('INSERT INTO kehamilan (ibu_id, puskesmas_id, hpht, hpl, gravida, paritas, abortus, bb_sebelum_hamil, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'aktif\')',
                [$ibuId, $u['puskesmas_id'] ?: 1, $v['hpht'], hitung_hpl($v['hpht']), $g, $p, $a, $bb]);
            $kid = (int) $pdo->lastInsertId();
            buat_jadwal($kid, $v['hpht']);
            db_run('INSERT INTO persiapan_persalinan (kehamilan_id) VALUES (?)', [$kid]);
            db_run('INSERT INTO pengaturan_pengingat (user_id) VALUES (?)', [$uid]);
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $galat[] = 'Data gagal disimpan. Coba lagi. (' . $ex->getMessage() . ')';
        }
        if (!$galat) {
            flash('ok', 'Ibu ' . $v['nama'] . ' terdaftar. Login: ' . $hp . ' / kata sandi awal ' . $sandi . ' (catat dan berikan ke ibu, tidak ditampilkan lagi).');
            redirect('nakes/ibu.php?id=' . $kid);
        }
    }
}
page_start('Daftarkan ibu', 'ibu-baru', 'nakes');
?>
<div class="page-head"><div><h1>Daftarkan ibu hamil</h1><div class="sub">Cukup data inti. Jadwal K1 sampai K6 dibuat otomatis dari HPHT.</div></div></div>
<?php if ($galat): ?><div class="flash flash-err" role="alert"><?= icon('alert', 20) ?><span><?= implode('<br>', array_map('e', $galat)) ?></span></div><?php endif; ?>
<form method="post" novalidate>
  <?= csrf_field() ?>
  <fieldset><legend>Data ibu</legend>
    <div class="cols cols-2">
      <div class="field"><label for="nama">Nama lengkap</label><input class="input" id="nama" name="nama" required maxlength="100" value="<?= e($v['nama']) ?>"></div>
      <div class="field"><label for="no_hp">Nomor HP (untuk login)</label><input class="input" id="no_hp" name="no_hp" inputmode="tel" required value="<?= e($v['no_hp']) ?>"></div>
      <div class="field"><label for="tanggal_lahir">Tanggal lahir</label><input class="input" type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($v['tanggal_lahir']) ?>"></div>
      <div class="field"><label for="no_jkn">No. JKN / BPJS</label><input class="input" id="no_jkn" name="no_jkn" maxlength="20" value="<?= e($v['no_jkn']) ?>"></div>
    </div>
    <div class="field"><label for="alamat">Alamat</label><input class="input" id="alamat" name="alamat" maxlength="200" value="<?= e($v['alamat']) ?>"></div>
    <div class="cols cols-2">
      <div class="field"><label for="gol_darah">Golongan darah</label><select class="select" id="gol_darah" name="gol_darah"><?php foreach (['' => '-', 'A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O'] as $k => $l): ?><option value="<?= $k ?>" <?= $v['gol_darah'] === (string) $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="rhesus">Rhesus</label><select class="select" id="rhesus" name="rhesus"><?php foreach (['' => '-', '+' => 'Positif (+)', '-' => 'Negatif (-)'] as $k => $l): ?><option value="<?= $k ?>" <?= $v['rhesus'] === (string) $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
  </fieldset>

  <fieldset><legend>Kehamilan</legend>
    <div class="field"><label for="hpht">HPHT (hari pertama haid terakhir)</label><input class="input" type="date" id="hpht" name="hpht" required max="<?= e(hari_ini()) ?>" value="<?= e($v['hpht']) ?>">
      <div class="hint">HPL dihitung otomatis: HPHT + 280 hari.</div></div>
    <div class="cols cols-3">
      <div class="field"><label for="gravida">Gravida (G)</label><input class="input" type="number" min="1" max="20" id="gravida" name="gravida" value="<?= e($v['gravida']) ?>"></div>
      <div class="field"><label for="paritas">Paritas (P)</label><input class="input" type="number" min="0" max="20" id="paritas" name="paritas" value="<?= e($v['paritas']) ?>"></div>
      <div class="field"><label for="abortus">Abortus (A)</label><input class="input" type="number" min="0" max="20" id="abortus" name="abortus" value="<?= e($v['abortus']) ?>"></div>
    </div>
    <div class="field"><label for="bb">Berat badan sebelum hamil (kg)</label><input class="input" id="bb" name="bb" inputmode="decimal" value="<?= e($v['bb']) ?>"></div>
  </fieldset>

  <fieldset><legend>Kontak keluarga (opsional)</legend>
    <div class="cols cols-3">
      <div class="field"><label for="k_nama">Nama</label><input class="input" id="k_nama" name="k_nama" maxlength="100" value="<?= e($v['k_nama']) ?>"></div>
      <div class="field"><label for="k_hubungan">Hubungan</label><input class="input" id="k_hubungan" name="k_hubungan" maxlength="30" value="<?= e($v['k_hubungan']) ?>"></div>
      <div class="field"><label for="k_hp">Nomor HP</label><input class="input" id="k_hp" name="k_hp" inputmode="tel" value="<?= e($v['k_hp']) ?>"></div>
    </div>
  </fieldset>

  <div class="notice"><?= icon('lock', 20) ?><span>Pastikan ibu sudah setuju datanya disimpan di MOMCARE. Kata sandi awal dibuat otomatis dan hanya ditampilkan sekali setelah menyimpan.</span></div>
  <button class="btn btn-primary btn-block" type="submit">Simpan dan buat jadwal</button>
</form>
<?php page_end(); ?>
