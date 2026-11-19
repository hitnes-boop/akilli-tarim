<?php
// M2 toprak ölçümü kuralları testi: veritabanı kısıtları geçersiz değerleri reddetmeli.
// Çalıştırma (proje kök klasöründen): php testler/toprak_testi.php
// Testler bir işlem içinde çalışır ve sonunda geri alınır, veritabanı değişmez.
declare(strict_types=1);
require_once DIR . '/../php/db.php';

$pdo = db();
$pdo->beginTransaction();
$basarisiz = 0;

function dene(PDO $pdo, string $ad, array $olcum, bool $reddedilmeli): void
{
    global $basarisiz;
    $reddedildi = false;
    try {
        $st = $pdo->prepare(
            'INSERT INTO TOPRAK_OLCUM (parsel_id, yontem, ph, nem, tuz, fosfor, olcum_tarihi)
             VALUES (1, :y, :ph, :nem, :tuz, :fos, :t)'
        );
        $st->execute($olcum);
    } catch (PDOException) {
        $reddedildi = true;
    }
    $tamam = $reddedildi === $reddedilmeli;
    if (!$tamam) $basarisiz++;
    echo ($tamam ? 'OK   ' : 'HATA ') . $ad . ' -> ' . ($reddedildi ? 'reddedildi' : 'kabul edildi') . PHP_EOL;
}

$gecerli = [':y' => 'Laboratuvar', ':ph' => 6.5, ':nem' => 22.0, ':tuz' => 'Düşük', ':fos' => 'Orta', ':t' => '2026-11-01'];

dene($pdo, 'geçerli ölçüm',                 $gecerli, false);
dene($pdo, 'pH 15 (üst sınır aşıldı)',      [':ph' => 15.0] + $gecerli, true);
dene($pdo, 'pH -1 (alt sınır aşıldı)',      [':ph' => -1.0] + $gecerli, true);
dene($pdo, 'nem 120 (üst sınır aşıldı)',    [':nem' => 120.0] + $gecerli, true);
dene($pdo, 'geçersiz tuz seviyesi',         [':tuz' => 'Çok'] + $gecerli, true);
dene($pdo, 'geçersiz ölçüm yöntemi',        [':y' => 'Tahmin'] + $gecerli, true);

$pdo->rollBack();
echo PHP_EOL . ($basarisiz === 0 ? 'Tüm testler geçti.' : $basarisiz . ' test başarısız.') . PHP_EOL;
exit($basarisiz === 0 ? 0 : 1);
