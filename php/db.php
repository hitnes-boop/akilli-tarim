<?php
// PDO / SQLite bağlantısı
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!is_file(DB_YOLU)) {
            http_response_code(500);
            exit('Veritabanı dosyası bulunamadı.');
        }
        $pdo = new PDO('sqlite:' . DB_YOLU, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON'); // şema bunu bağlantı başına bekliyor
    }
    return $pdo;
}
