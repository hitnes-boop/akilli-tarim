<?php
// Giriş formunun gönderildiği dosya (POST: eposta, parola)
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oturum.php';
require_once __DIR__ . '/parola.php';

function hata_ile_don(): never
{
    header('Location: index.php?hata=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$eposta = mb_strtolower(trim((string) ($_POST['eposta'] ?? '')));
$parola = (string) ($_POST['parola'] ?? '');

if ($eposta === '' || $parola === '') {
    hata_ile_don();
}

$st = db()->prepare(
    'SELECT kullanici_id, ad, rol, parola_tuz, parola_hash
       FROM KULLANICI WHERE email = :e AND aktif = 1'
);
$st->execute([':e' => $eposta]);
$k = $st->fetch();

// Kullanıcı yoksa da özet hesapla: yanıt süresi e-postanın varlığını sızdırmasın
$tuz   = $k['parola_tuz']  ?? 'bos-tuz-bos-tuz';
$hash  = $k['parola_hash'] ?? str_repeat('0', 64);
$dogru = hash_equals($hash, parola_ozeti($parola, $tuz));

if ($k === false || !$dogru) {
    hata_ile_don();
}

oturum_ac($k);
header('Location: ' . (ROL_SAYFA[$k['rol']] ?? 'panel.php'));
exit;
