<?php
declare(strict_types=1);

define('DB_HOST', getenv('POS_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('POS_DB_PORT') ?: 3306));
define('DB_NAME', getenv('POS_DB_NAME') ?: 'kasir_toko');
define('DB_USER', getenv('POS_DB_USER') ?: 'root');
define('DB_PASS', getenv('POS_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'",
        ]);
    } catch (PDOException $e) {
        error_log('[DB] Koneksi gagal: ' . $e->getMessage());
        http_response_code(500);
        exit('Tidak dapat terhubung ke database. Periksa pengaturan di config/database.php.');
    }

    return $pdo;
}
