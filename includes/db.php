<?php
/**
 * Koneksi PDO (MySQL / MariaDB). Variabel lingkungan MOMCARE_DSN hanya
 * dipakai untuk pengujian otomatis dan boleh diabaikan.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn  = getenv('MOMCARE_DSN') ?: 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $user = getenv('MOMCARE_DSN') ? null : DB_USER;
    $pass = getenv('MOMCARE_DSN') ? null : DB_PASS;

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $ex) {
        http_response_code(500);
        exit('<h1>Basis data tidak terhubung</h1><p>Pastikan MySQL di XAMPP sudah Start, database <b>momcare</b> sudah di-import, '
            . 'dan DB_USER / DB_PASS di <code>config.php</code> benar.</p>');
    }
    return $pdo;
}

/** Ambil satu baris, atau null. */
function db_row(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

/** Ambil semua baris. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Jalankan INSERT / UPDATE / DELETE. */
function db_run(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Ambil satu nilai. */
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}
