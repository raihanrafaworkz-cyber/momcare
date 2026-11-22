<?php
require __DIR__ . '/../includes/bootstrap.php';
$I = ibu_ctx();

if (is_post()) {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'pengaturan') {
        $kanal = in_array($_POST['kanal'] ?? '', ['whatsapp', 'sms', 'notifikasi'], true) ? $_POST['kanal'] : 'whatsapp';
        $jam   = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_POST['jam_ttd'] ?? '') ? $_POST['jam_ttd'] . ':00' : '07:00:00';
        $v     = [isset($_POST['pengingat_kunjungan']) ? 1 : 0, isset($_POST['pengingat_ttd']) ? 1 : 0, $jam, isset($_POST['kirim_ke_keluarga']) ? 1 : 0, $kanal];
        if (db_val('SELECT 1 FROM pengaturan_pengingat WHERE user_id = ?', [$I['user_id']])) {
            db_run('UPDATE pengaturan_pengingat SET pengingat_kunjungan=?, pengingat_ttd=?, jam_ttd=?, kirim_ke_keluarga=?, kanal=? WHERE user_id=?', [...$v, $I['user_id']]);
        } else {
            db_run('INSERT INTO pengaturan_pengingat (pengingat_kunjungan, pengingat_ttd, jam_ttd, kirim_ke_keluarga, kanal, user_id) VALUES (?,?,?,?,?,?)', [...$v, $I['user_id']]);
        }
        flash('ok', 'Pengaturan pengingat tersimpan.');
    } elseif ($aksi === 'keluarga') {
        $nama = post_teks('k_nama', 100); $hub = post_teks('k_hubungan', 30) ?: 'Suami'; $hp = preg_replace('/[^0-9+]/', '', post_teks('k_hp', 20));
        if ($nama === '' || strlen($hp) < 8) {
            flash('err', 'Isi nama dan nomor HP keluarga dengan benar.');
        } else {
            $ada = db_val('SELECT id FROM kontak_keluarga WHERE ibu_id = ? ORDER BY id LIMIT 1', [$I['ibu_id']]);
            if ($ada) {
                db_run('UPDATE kontak_keluarga SET nama=?, hubungan=?, no_hp=? WHERE id=?', [$nama, $hub, $hp, $ada]);
            } else {
                db_run('INSERT INTO kontak_keluarga (nama, hubungan, no_hp, ibu_id) VALUES (?,?,?,?)', [$nama, $hub, $hp, $I['ibu_id']]);
            }
            flash('ok', 'Kontak keluarga tersimpan.');
        }
    } elseif ($aksi === 'sandi') {
        $lama = (string) ($_POST['lama'] ?? ''); $baru = (string) ($_POST['baru'] ?? '');
        $hash = db_val('SELECT password_hash FROM users WHERE id = ?', [$I['user_id']]);
        if (!password_verify($lama, (string) $hash)) {
            flash('err', 'Kata sandi lama tidak cocok.');
        } elseif (mb_strlen($baru) < 8) {
            flash('err', 'Kata sandi baru minimal 8 karakter.');
        } else {
            db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($baru, PASSWORD_DEFAULT), $I['user_id']]);
            flash('ok', 'Kata sandi diganti.');
        }
    }
    redirect('ibu/profil.php');
}

$set = db_row('SELECT * FROM pengaturan_pengingat WHERE user_id = ?', [$I['user_id']]) ?? ['pengingat_kunjungan' => 1, 'pengingat_ttd' => 1, 'jam_ttd' => '07:00:00', 'kirim_ke_keluarga' => 1, 'kanal' => 'whatsapp'];
$kel = db_row('SELECT * FROM kontak_keluarga WHERE ibu_id = ? ORDER BY id LIMIT 1', [$I['ibu_id']]) ?? ['nama' => '', 'hubungan' => 'Suami', 'no_hp' => ''];

page_start('Profil', 'profil');
?>
<div class="page-head"><div><h1>Profil</h1><div class="sub">Data diri dan pengingat</div></div></div>

<section class="card">
  <div class="row"><span class="avatar" style="width:56px;height:56px;font-size:1.4rem"><?= e(mb_strtoupper(mb_substr($I['nama'], 0, 1))) ?></span>
    <div><h2 style="margin:0"><?= e($I['nama']) ?></h2><div class="muted"><?= e($I['no_hp']) ?></div></div></div>
  <dl class="kv" style="margin-top:10px">
    <dt>Tanggal lahir</dt><dd><?= e(tgl_indo($I['tanggal_lahir'])) ?></dd>
    <dt>Golongan darah</dt><dd><?= e(($I['gol_darah'] ?? '-') . ($I['gol_darah'] ? $I['rhesus'] : '')) ?></dd>
    <dt>No. JKN</dt><dd><?= e($I['no_jkn'] ?? '-') ?></dd>
    <dt>HPHT</dt><dd><?= e(tgl_indo($I['hpht'])) ?></dd>
    <dt>HPL</dt><dd><?= e(tgl_indo($I['hpl'])) ?></dd>
    <dt>Kehamilan (G/P/A)</dt><dd>G<?= (int) $I['gravida'] ?> P<?= (int) $I['paritas'] ?> A<?= (int) $I['abortus'] ?></dd>
    <dt>Puskesmas</dt><dd><?= e($I['puskesmas']) ?></dd>
  </dl>
  <p class="muted small" style="margin:8px 0 0">Data klinis diisi petugas. Untuk koreksi, sampaikan ke bidan.</p>
</section>

<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="aksi" value="pengaturan">
  <div class="card-head"><h2>Pengingat</h2><?= icon('bell', 22) ?></div>
  <label class="check"><input type="checkbox" name="pengingat_kunjungan" <?= $set['pengingat_kunjungan'] ? 'checked' : '' ?>>Ingatkan jadwal kunjungan (H-7, H-1, hari-H)</label>
  <label class="check"><input type="checkbox" name="pengingat_ttd" <?= $set['pengingat_ttd'] ? 'checked' : '' ?>>Ingatkan minum tablet tambah darah</label>
  <div class="cols cols-2" style="margin-top:8px">
    <div class="field"><label for="jam_ttd">Jam minum tablet</label><input class="input" type="time" id="jam_ttd" name="jam_ttd" value="<?= e(substr((string) $set['jam_ttd'], 0, 5)) ?>"></div>
    <div class="field"><label for="kanal">Kirim lewat</label>
      <select class="select" id="kanal" name="kanal">
        <?php foreach (['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'notifikasi' => 'Notifikasi aplikasi'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $set['kanal'] === $k ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
  </div>
  <label class="check"><input type="checkbox" name="kirim_ke_keluarga" <?= $set['kirim_ke_keluarga'] ? 'checked' : '' ?>>Kirim juga ke keluarga</label>
  <p class="muted small">Catatan: versi lokal ini menyimpan pengaturan saja. Pengiriman WhatsApp / SMS memerlukan layanan pesan tambahan.</p>
  <button class="btn btn-primary btn-block" type="submit">Simpan pengaturan</button>
</form>

<form class="card" method="post">
  <?= csrf_field() ?><input type="hidden" name="aksi" value="keluarga">
  <div class="card-head"><h2>Kontak keluarga</h2><?= icon('users', 22) ?></div>
  <div class="cols cols-2">
    <div class="field"><label for="k_nama">Nama</label><input class="input" id="k_nama" name="k_nama" maxlength="100" value="<?= e($kel['nama']) ?>"></div>
    <div class="field"><label for="k_hubungan">Hubungan</label><input class="input" id="k_hubungan" name="k_hubungan" maxlength="30" value="<?= e($kel['hubungan']) ?>"></div>
  </div>
  <div class="field"><label for="k_hp">Nomor HP</label><input class="input" id="k_hp" name="k_hp" inputmode="tel" maxlength="20" value="<?= e($kel['no_hp']) ?>"></div>
  <button class="btn btn-ghost btn-block" type="submit">Simpan kontak</button>
</form>

<form class="card" method="post" autocomplete="off">
  <?= csrf_field() ?><input type="hidden" name="aksi" value="sandi">
  <div class="card-head"><h2>Ganti kata sandi</h2><?= icon('lock', 22) ?></div>
  <div class="field"><label for="lama">Kata sandi lama</label><input class="input" type="password" id="lama" name="lama" autocomplete="current-password"></div>
  <div class="field"><label for="baru">Kata sandi baru (min. 8 karakter)</label><input class="input" type="password" id="baru" name="baru" autocomplete="new-password"></div>
  <button class="btn btn-ghost btn-block" type="submit">Ganti kata sandi</button>
</form>
<?php page_end(); ?>
