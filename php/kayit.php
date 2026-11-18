<?php
// Kayıt formunun gönderildiği dosya (POST: ad, eposta, parola, parola2, rol)
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oturum.php';
require_once __DIR__ . '/parola.php';

function kayit_hatasi(string $kod): never
{
    header('Location: index.php?kayit_hata=' . $kod);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$ad     = trim((string) ($_POST['ad'] ?? ''));
$eposta = mb_strtolower(trim((string) ($_POST['eposta'] ?? '')));
$parola = (string) ($_POST['parola'] ?? '');
$parola2 = (string) ($_POST['parola2'] ?? '');
$rol    = (string) ($_POST['rol'] ?? '');

// Güvenlik için rol sunucuda da sınırlanır (veritabanı CHECK'i ikinci savunma)
if (!array_key_exists($rol, ROL_SAYFA))          kayit_hatasi('rol');
if ($ad === '' || mb_strlen($ad) > 80)           kayit_hatasi('ad');
if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) kayit_hatasi('eposta');
if (mb_strlen($parola) < 6)                      kayit_hatasi('kisa');
if ($parola !== $parola2)                        kayit_hatasi('eslesmiyor');

$tuz = yeni_tuz();
try {
    $st = db()->prepare(
        'INSERT INTO KULLANICI (ad, email, parola_tuz, parola_hash, rol)
         VALUES (:ad, :e, :t, :h, :r)'
    );
    $st->execute([
        ':ad' => $ad,
        ':e'  => $eposta,
        ':t'  => $tuz,
        ':h'  => parola_ozeti($parola, $tuz),
        ':r'  => $rol,
    ]);
} catch (PDOException $e) {
    // email UNIQUE kısıtı: SQLite kod 19 (constraint)
    if (($e->errorInfo[1] ?? 0) === 19) kayit_hatasi('var');
    throw $e;
}

oturum_ac([
    'kullanici_id' => (int) db()->lastInsertId(),
    'ad'           => $ad,
    'rol'          => $rol,
]);
header('Location: ' . ROL_SAYFA[$rol]);
exit;
