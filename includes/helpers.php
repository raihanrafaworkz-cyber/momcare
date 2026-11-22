<?php
/** Fungsi bantu umum: escape, URL, flash, CSRF, tanggal Indonesia. */

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['_csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['_csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Kembali ke halaman sebelumnya, muat ulang, lalu coba lagi.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Nilai POST berupa teks, dipangkas dan dibatasi panjangnya. */
function post_teks(string $key, int $maks = 120): string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    return mb_substr($v, 0, $maks);
}

/** Nilai POST berupa angka desimal, atau null bila kosong / tidak valid. */
function post_angka(string $key): ?float
{
    $v = str_replace(',', '.', trim((string) ($_POST[$key] ?? '')));
    return ($v !== '' && is_numeric($v)) ? (float) $v : null;
}

/* ------------------------------ Tanggal ------------------------------ */

const BULAN        = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const BULAN_PENDEK = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const HARI         = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function hari_ini(): string
{
    return date('Y-m-d');
}

/** 2026-11-30 -> "30 November 2026" (opsional dengan nama hari / singkat). */
function tgl_indo(?string $tgl, bool $denganHari = false, bool $pendek = false): string
{
    if (!$tgl) {
        return '-';
    }
    $t = new DateTimeImmutable($tgl);
    $bulan = $pendek ? BULAN_PENDEK[(int) $t->format('n')] : BULAN[(int) $t->format('n')];
    $s = $t->format('j') . ' ' . $bulan . ' ' . $t->format('Y');
    return $denganHari ? HARI[(int) $t->format('w')] . ', ' . $s : $s;
}

/** Selisih hari bertanda: positif bila $ke setelah $dari. */
function selisih_hari(string $dari, string $ke): int
{
    return (int) (new DateTimeImmutable($dari))->diff(new DateTimeImmutable($ke))->format('%r%a');
}

function tambah_hari(string $tgl, int $n): string
{
    return (new DateTimeImmutable($tgl))->modify(sprintf('%+d days', $n))->format('Y-m-d');
}

function angka(?float $v, int $desimal = 1): string
{
    return $v === null ? '-' : number_format($v, $desimal, ',', '.');
}

function valid_tanggal(string $tgl): bool
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $tgl);
    return $d !== false && $d->format('Y-m-d') === $tgl;
}
