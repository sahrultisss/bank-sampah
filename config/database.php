<?php
/**
 * Konfigurasi & koneksi database menggunakan PDO
 *
 * LOKAL (XAMPP/Laragon):  host 'localhost', user 'root', pass ''.
 * INFINITYFREE: isi dengan data dari menu "MySQL Databases" di panel hosting
 *   - DB_HOST : mis. sql112.infinityfree.com (BUKAN localhost)
 *   - DB_NAME : mis. if0_43056269_bank_sampah
 *   - DB_USER : mis. if0_43056269
 *   - DB_PASS : pjp0fKQPgpnN (vPanel)
 */

date_default_timezone_set('Asia/Jakarta');

// Deteksi otomatis: localhost = XAMPP, selain itu = hosting
$host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');

if (in_array($host, ['localhost', '127.0.0.1'], true)) {
    // LOKAL (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bank_sampah');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // HOSTING (InfinityFree)
    define('DB_HOST', 'sql112.infinityfree.com');
    define('DB_NAME', 'if0_43056269_bank_sampah');
    define('DB_USER', 'if0_43056269');
    define('DB_PASS', 'pjp0fKQPgpnN');
}

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Samakan zona waktu MySQL (NOW(), CURDATE()) dengan WIB
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            error_log('DB error: ' . $e->getMessage());
            die('Koneksi database gagal. Periksa pengaturan di config/database.php.');
        }
    }

    return $pdo;
}
