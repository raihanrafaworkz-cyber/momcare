<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = wajib_masuk(['nakes', 'admin']);

$kid = (int) ($_GET['k'] ?? $_POST['k'] ?? 0);
$k   = $kid ? kehamilan_boleh($u, $kid) : null;

// Langkah 1: pilih ibu
if (!$k) {
    $daftar = daftar_kehamilan_aktif($u['puskesmas_id'] ? (int) $u['puskesmas_id'] : null);
    page_start('Catat kunjungan', 'kunjungan-baru', 'nakes');
    ?>
    <div class="page-head"><div><h1>Catat kunjungan</h1><div class="sub">Pilih ibu yang sedang diperiksa</div></div></div>
    <div class="card">
      <?php if (!$daftar): ?><div class="empty"><?= deco('hati', 84) ?><p>Belum ada ibu hamil aktif.</p></div><?php endif; ?>
      <?php foreach ($daftar as $b): if (!$b['berikut']) continue; ?>
        <a class="row between" style="padding:10px 0;border-bottom:1px dashed var(--line);text-decoration:none" href="<?= e(url('nakes/kunjungan-baru.php?k=' . (int) $b['kehamilan_id'])) ?>">
          <span><strong><?= e($b['nama']) ?></strong><br><span class="muted small"><?= (int) $b['usia']['minggu'] ?> minggu &middot; berikutnya K<?= (int) $b['berikut']['kunjungan_ke'] ?></span></span>
          <?= chip_status($b['status']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php
    page_end();
    exit;
}

$jadwal  = jadwal_lengkap((int) $k['id']);
$belum   = array_values(array_filter($jadwal, fn($j) => !$j['selesai']));
if (!$belum) {
    flash('ok', 'Semua kunjungan K1 sampai K6 untuk ' . $k['nama'] . ' sudah tercatat.');
    redirect('nakes/ibu.php?id=' . (int) $k['id']);
}
$adalahDokter = ($u['jabatan'] ?? '') === 'Dokter';

$v = ['jadwal_id' => (string) $belum[0]['id'], 'tanggal' => hari_ini(), 'tanggal_kembali' => '', 'catatan_nakes' => '', 'catatan_rujukan' => '',
      'risiko' => '', 'usg_status' => '', 'hpl_usg' => '', 'tempat' => ''];
$galat = [];

if (is_post()) {
    csrf_check();
    foreach ($v as $key => $_) { $v[$key] = post_teks($key, $key === 'catatan_nakes' ? 500 : 255); }

    $j = null;
    foreach ($belum as $x) { if ((string) $x['id'] === $v['jadwal_id']) { $j = $x; } }
    if (!$j) { $galat[] = 'Pilih kunjungan yang belum dicatat.'; }

    if (!valid_tanggal($v['tanggal'])) { $galat[] = 'Tanggal kunjungan tidak valid.'; }
    elseif ($v['tanggal'] > hari_ini()) { $galat[] = 'Tanggal kunjungan tidak boleh di masa depan.'; }
    elseif ($v['tanggal'] < $k['hpht']) { $galat[] = 'Tanggal kunjungan sebelum HPHT.'; }
    if ($v['tanggal_kembali'] !== '' && (!valid_tanggal($v['tanggal_kembali']) || $v['tanggal_kembali'] <= $v['tanggal'])) {
        $galat[] = 'Tanggal kembali harus setelah tanggal kunjungan.';
    }

    [$sis, $e1] = angka_rentang('td_sistol', 'Sistolik', 50, 260);
    [$dia, $e2] = angka_rentang('td_diastol', 'Diastolik', 30, 160);
    [$bbv, $e3] = angka_rentang('berat_badan', 'Berat badan', 20, 200);
    [$lila, $e4] = angka_rentang('lila', 'LILA', 10, 50);
    [$tfu, $e5] = angka_rentang('tfu', 'Tinggi fundus', 5, 50);
    [$djj, $e6] = angka_rentang('djj', 'DJJ', 60, 220);
    [$hb, $e7]  = angka_rentang('hb', 'Hemoglobin', 3, 20);
    foreach ([$e1, $e2, $e3, $e4, $e5, $e6, $e7] as $e) { if ($e) { $galat[] = $e; } }
    if (($sis === null) !== ($dia === null)) { $galat[] = 'Isi sistolik dan diastolik sekaligus.'; }
    if ($sis !== null && $dia !== null && $sis <= $dia) { $galat[] = 'Sistolik harus lebih besar dari diastolik.'; }

    $risiko = in_array($v['risiko'], ['rendah', 'sedang', 'tinggi'], true) ? $v['risiko'] : null;
    $usg    = in_array($v['usg_status'], ['normal', 'curiga'], true) ? $v['usg_status'] : null;
    $tempat = in_array($v['tempat'], ['FKTP', 'FKRTL'], true) ? $v['tempat'] : null;
    if ($v['hpl_usg'] !== '' && !valid_tanggal($v['hpl_usg'])) { $galat[] = 'HPL menurut USG tidak valid.'; }

    if (!$galat && $j) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_run(
                'INSERT INTO kunjungan (kehamilan_id, jadwal_id, tanggal, nakes_id, puskesmas_id, td_sistol, td_diastol, berat_badan, lila, tfu, djj, hb,
                    periksa_protein_urin, skrining_jiwa, ttd_diberikan, usg_dilakukan, rujukan, catatan_rujukan, catatan_nakes, tanggal_kembali)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$k['id'], $j['id'], $v['tanggal'], $u['id'], $k['puskesmas_id'], $sis, $dia, $bbv, $lila, $tfu, $djj, $hb,
                 isset($_POST['protein']) ? 1 : 0, isset($_POST['jiwa']) ? 1 : 0, isset($_POST['ttd']) ? 1 : 0, isset($_POST['usg']) ? 1 : 0,
                 isset($_POST['rujukan']) ? 1 : 0, $v['catatan_rujukan'] ?: null, $v['catatan_nakes'] ?: null, $v['tanggal_kembali'] ?: null]
            );
            if ($j['dokter'] && ($risiko || $usg || $tempat || $v['hpl_usg'] || isset($_POST['jiwa_rujuk']) || isset($_POST['dm_tindak']))) {
                $tri  = ANC_JENDELA[(int) $j['kunjungan_ke']]['tri'];
                $vals = [$v['tanggal'], $u['id'], $risiko, $usg, $v['hpl_usg'] ?: null, isset($_POST['jiwa_rujuk']) ? 1 : 0, isset($_POST['dm_tindak']) ? 1 : 0, $tempat];
                if (db_val('SELECT 1 FROM ringkasan_dokter WHERE kehamilan_id = ? AND trimester = ?', [$k['id'], $tri])) {
                    db_run('UPDATE ringkasan_dokter SET tanggal=?, dokter_id=?, risiko_preeklampsia=?, usg_status=?, hpl_usg=?, skrining_jiwa_rujukan=?, skrining_dm_tindak_lanjut=?, rekomendasi_tempat=?
                            WHERE kehamilan_id=? AND trimester=?', [...$vals, $k['id'], $tri]);
                } else {
                    db_run('INSERT INTO ringkasan_dokter (tanggal, dokter_id, risiko_preeklampsia, usg_status, hpl_usg, skrining_jiwa_rujukan, skrining_dm_tindak_lanjut, rekomendasi_tempat, kehamilan_id, trimester)
                            VALUES (?,?,?,?,?,?,?,?,?,?)', [...$vals, $k['id'], $tri]);
                }
            }
            $pdo->commit();
            flash('ok', 'Kunjungan K' . $j['kunjungan_ke'] . ' untuk ' . $k['nama'] . ' tersimpan.');
            redirect('nakes/ibu.php?id=' . (int) $k['id']);
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $galat[] = ((string) $ex->getCode() === '23000') ? 'Kunjungan ini sudah tercatat (mungkin terkirim dua kali).' : 'Data gagal disimpan: ' . $ex->getMessage();
        }
    }
}

$cek = fn(string $n) => isset($_POST[$n]) ? 'checked' : '';
page_start('Catat kunjungan', 'kunjungan-baru', 'nakes');
?>
<div class="page-head">
  <?= tombol_kembali('nakes/ibu.php?id=' . (int) $k['id'], 'detail ibu') ?>
  <div><h1>Catat kunjungan</h1><div class="sub"><?= e($k['nama']) ?> &middot; <?= (int) usia_kehamilan($k['hpht'])['minggu'] ?> minggu</div></div>
</div>
<?php if ($galat): ?><div class="flash flash-err" role="alert"><?= icon('alert', 20) ?><span><?= implode('<br>', array_map('e', $galat)) ?></span></div><?php endif; ?>

<form method="post" novalidate>
  <?= csrf_field() ?><input type="hidden" name="k" value="<?= (int) $k['id'] ?>">
  <fieldset><legend>Kunjungan</legend>
    <div class="cols cols-2">
      <div class="field"><label for="jadwal_id">Kunjungan ke</label>
        <select class="select" id="jadwal_id" name="jadwal_id">
          <?php foreach ($belum as $j): ?><option value="<?= (int) $j['id'] ?>" <?= (string) $j['id'] === $v['jadwal_id'] ? 'selected' : '' ?>>K<?= (int) $j['kunjungan_ke'] ?> &middot; <?= e(STATUS_LABEL[$j['status']]) ?><?= $j['dokter'] ? ' &middot; dokter' : '' ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label for="tanggal">Tanggal periksa</label><input class="input" type="date" id="tanggal" name="tanggal" max="<?= e(hari_ini()) ?>" value="<?= e($v['tanggal']) ?>"></div>
    </div>
  </fieldset>

  <fieldset><legend>Pengukuran (data penting saja)</legend>
    <div class="cols cols-3">
      <div class="field"><label for="td_sistol">Sistolik (mmHg)</label><input class="input" id="td_sistol" name="td_sistol" inputmode="numeric" value="<?= e($_POST['td_sistol'] ?? '') ?>"></div>
      <div class="field"><label for="td_diastol">Diastolik (mmHg)</label><input class="input" id="td_diastol" name="td_diastol" inputmode="numeric" value="<?= e($_POST['td_diastol'] ?? '') ?>"></div>
      <div class="field"><label for="berat_badan">Berat badan (kg)</label><input class="input" id="berat_badan" name="berat_badan" inputmode="decimal" value="<?= e($_POST['berat_badan'] ?? '') ?>"></div>
      <div class="field"><label for="lila">LILA (cm)</label><input class="input" id="lila" name="lila" inputmode="decimal" value="<?= e($_POST['lila'] ?? '') ?>"></div>
      <div class="field"><label for="tfu">Tinggi fundus (cm)</label><input class="input" id="tfu" name="tfu" inputmode="decimal" value="<?= e($_POST['tfu'] ?? '') ?>"></div>
      <div class="field"><label for="djj">DJJ (x/menit)</label><input class="input" id="djj" name="djj" inputmode="numeric" value="<?= e($_POST['djj'] ?? '') ?>"></div>
      <div class="field"><label for="hb">Hemoglobin (g/dL)</label><input class="input" id="hb" name="hb" inputmode="decimal" value="<?= e($_POST['hb'] ?? '') ?>"></div>
    </div>
  </fieldset>

  <fieldset><legend>Sudah dilakukan</legend>
    <label class="check"><input type="checkbox" name="protein" <?= $cek('protein') ?>>Periksa protein urin</label>
    <label class="check"><input type="checkbox" name="jiwa" <?= $cek('jiwa') ?>>Skrining kesehatan jiwa</label>
    <label class="check"><input type="checkbox" name="ttd" <?= $cek('ttd') ?>>Tablet tambah darah diberikan</label>
    <label class="check"><input type="checkbox" name="usg" <?= $cek('usg') ?>>USG</label>
    <label class="check"><input type="checkbox" name="rujukan" <?= $cek('rujukan') ?>>Dirujuk</label>
    <div class="field"><label for="catatan_rujukan">Catatan rujukan</label><input class="input" id="catatan_rujukan" name="catatan_rujukan" maxlength="255" value="<?= e($v['catatan_rujukan']) ?>"></div>
  </fieldset>

  <fieldset><legend>Pesan untuk ibu</legend>
    <div class="field"><label for="catatan_nakes">Catatan singkat (tampil di aplikasi ibu)</label><textarea class="input" id="catatan_nakes" name="catatan_nakes" maxlength="500"><?= e($v['catatan_nakes']) ?></textarea></div>
    <div class="field"><label for="tanggal_kembali">Kembali periksa pada</label><input class="input" type="date" id="tanggal_kembali" name="tanggal_kembali" value="<?= e($v['tanggal_kembali']) ?>"><div class="hint">Tanggal ini dipakai untuk pengingat H-7, H-1, dan hari-H.</div></div>
  </fieldset>

  <?php if ($adalahDokter || $u['role'] === 'admin'): ?>
  <fieldset><legend>Ringkasan dokter (khusus K1 dan K5)</legend>
    <div class="cols cols-2">
      <div class="field"><label for="risiko">Risiko preeklamsia</label><select class="select" id="risiko" name="risiko"><?php foreach ([''=>'-','rendah'=>'Rendah','sedang'=>'Sedang','tinggi'=>'Tinggi'] as $a => $l): ?><option value="<?= $a ?>" <?= $v['risiko'] === (string) $a ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="usg_status">Hasil USG</label><select class="select" id="usg_status" name="usg_status"><?php foreach ([''=>'-','normal'=>'Normal','curiga'=>'Perlu pemeriksaan lanjut'] as $a => $l): ?><option value="<?= $a ?>" <?= $v['usg_status'] === (string) $a ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="hpl_usg">HPL menurut USG</label><input class="input" type="date" id="hpl_usg" name="hpl_usg" value="<?= e($v['hpl_usg']) ?>"></div>
      <div class="field"><label for="tempat">Rekomendasi tempat bersalin</label><select class="select" id="tempat" name="tempat"><?php foreach ([''=>'-','FKTP'=>'Puskesmas / klinik (FKTP)','FKRTL'=>'Rumah sakit (FKRTL)'] as $a => $l): ?><option value="<?= $a ?>" <?= $v['tempat'] === (string) $a ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <label class="check"><input type="checkbox" name="jiwa_rujuk" <?= $cek('jiwa_rujuk') ?>>Skrining jiwa: perlu dirujuk</label>
    <label class="check"><input type="checkbox" name="dm_tindak" <?= $cek('dm_tindak') ?>>Skrining diabetes: perlu tindak lanjut</label>
    <div class="hint" style="margin-bottom:12px">Hanya tersimpan bila kunjungan yang dipilih adalah K1 atau K5.</div>
  </fieldset>
  <?php endif; ?>

  <button class="btn btn-primary btn-block" type="submit">Simpan kunjungan</button>
</form>
<?php page_end(); ?>
