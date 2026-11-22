<?php
/**
 * Dimuat di awal setiap halaman: sesi, konfigurasi, basis data, fungsi bantu.
 */
if (PHP_VERSION_ID < 80100) {
    exit('MOMCARE membutuhkan PHP 8.1 atau lebih baru. Perbarui XAMPP Anda.');
}

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/anc.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/nakes.php';
