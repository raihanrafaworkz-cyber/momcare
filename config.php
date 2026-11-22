<?php
/**
 * Pengaturan MOMCARE. Sesuaikan dengan XAMPP Anda.
 * Bawaan XAMPP: user "root" tanpa kata sandi.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'momcare';
const DB_USER = 'root';
const DB_PASS = '';

/** Alamat folder aplikasi di browser: http://localhost/momcare */
const BASE_URL = '/momcare';

const APP_NAME = 'MOMCARE';

/** true = tampilkan kotak "Akun demo" di halaman login. Ubah ke false saat dipakai sungguhan. */
const APP_DEMO = true;

date_default_timezone_set('Asia/Jakarta');
