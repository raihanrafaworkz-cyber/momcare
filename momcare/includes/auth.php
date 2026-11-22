<?php
/** Autentikasi dan pemuatan data ibu yang sedang masuk. */

function user_saat_ini(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    if (empty($_SESSION['uid'])) {
        return $cache = null;
    }
    $u = db_row(
        'SELECT u.id, u.role, u.jabatan, u.nama, u.no_hp, u.puskesmas_id, p.nama AS puskesmas
         FROM users u LEFT JOIN puskesmas p ON p.id = u.puskesmas_id
         WHERE u.id = ? AND u.aktif = 1',
        [(int) $_SESSION['uid']]
    );
    return $cache = $u;
}

/** Wajib masuk. $roles kosong = semua peran boleh. */
function wajib_masuk(array $roles = []): array
{
    $u = user_saat_ini();
    if (!$u) {
        redirect('login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        page_start('Akses ditolak', '', $u['role'] === 'ibu' ? 'ibu' : 'nakes');
        echo '<div class="card"><h2>Halaman ini bukan untuk peran Anda</h2><p>Kembali ke <a href="'
            . e(url(beranda_untuk($u['role']))) . '">beranda</a>.</p></div>';
        page_end($u['role'] === 'ibu' ? 'ibu' : 'nakes', '');
        exit;
    }
    return $u;
}

function beranda_untuk(string $role): string
{
    return $role === 'ibu' ? 'ibu/beranda.php' : 'nakes/index.php';
}

function masuk(string $identitas, string $sandi): bool
{
    $u = db_row('SELECT id, role, password_hash FROM users WHERE (no_hp = ? OR email = ?) AND aktif = 1', [$identitas, $identitas]);
    if (!$u || !password_verify($sandi, $u['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return true;
}

function keluar(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], false, true);
    }
    session_destroy();
}

/**
 * Data ibu yang sedang masuk: profil, kehamilan aktif, puskesmas, jadwal, usia.
 * Bila belum punya kehamilan aktif, tampilkan pesan dan hentikan halaman.
 */
function ibu_ctx(): array
{
    $u = wajib_masuk(['ibu']);
    $I = db_row(
        'SELECT i.id AS ibu_id, i.no_jkn, i.tanggal_lahir, i.alamat, i.gol_darah, i.rhesus, i.persetujuan_at,
                u.id AS user_id, u.nama, u.no_hp,
                k.id AS kehamilan_id, k.hpht, k.hpl, k.gravida, k.paritas, k.abortus, k.bb_sebelum_hamil, k.tanggal_persalinan,
                p.id AS puskesmas_id, p.nama AS puskesmas, p.alamat AS puskesmas_alamat, p.telepon AS puskesmas_telepon
         FROM ibu_hamil i
         JOIN users u ON u.id = i.user_id
         JOIN kehamilan k ON k.ibu_id = i.id AND k.status = \'aktif\'
         JOIN puskesmas p ON p.id = k.puskesmas_id
         WHERE i.user_id = ?
         ORDER BY k.id DESC LIMIT 1',
        [$u['id']]
    );
    if (!$I) {
        page_start('Belum ada data kehamilan', '', 'ibu');
        echo '<div class="card"><h2>Data kehamilan belum ada</h2>'
           . '<p>Minta bidan atau kader di puskesmas untuk mendaftarkan kehamilan Ibu.</p>'
           . '<form method="post" action="' . e(url('logout.php')) . '">' . csrf_field()
           . '<button class="btn btn-ghost" type="submit">Keluar</button></form></div>';
        page_end('ibu', '');
        exit;
    }
    $I['usia']   = usia_kehamilan($I['hpht']);
    $I['tri']    = trimester($I['usia']['minggu']);
    $I['jadwal'] = jadwal_lengkap((int) $I['kehamilan_id']);
    return $I;
}
