<?php
// Çıkış: oturumu ve oturum çerezini siler
declare(strict_types=1);
require_once __DIR__ . '/oturum.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Çıkış durum değiştirir; GET ile tetiklenmesin (başka siteden görsel/link ile çıkış attırılamasın)
    header('Location: index.php');
    exit;
}

oturum_baslat();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $c['path'],
        'domain'   => $c['domain'],
        'secure'   => $c['secure'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
session_destroy();

header('Location: index.php');
exit;
