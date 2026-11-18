<?php
// M1 parsel kuralları testi: veritabanı kısıtları ve tetikleyiciler.
// Çalıştırma (proje kök klasöründen): php testler/parsel_testi.php
// Testler bir işlem içinde çalışır ve sonunda geri alınır, veritabanı değişmez.
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

$pdo = db();
$pdo->beginTransaction();
$basarisiz = 0;
const SINIR = '{"type":"Polygon","coordinates":[[[32.85,37.57],[32.8537,37.57],[32.8537,37.573],[32.85,37.573],[32.85,37.57]]]}';

function parsel_ekle(PDO $pdo, int $kullanici, string $ad, ?float $alan, ?string $sinir): void
{
    $st = $pdo->prepare('INSERT INTO PARSEL (kullanici_id, ad, alan_da, sinir_geojson) VALUES (:k, :ad, :alan, :s)');
    $st->execute([':k' => $kullanici, ':ad' => $ad, ':alan' => $alan, ':s' => $sinir]);
}

function sonuc(string $ad, bool $tamam, string $not = ''): void
{
    global $basarisiz;
    if (!$tamam) $basarisiz++;
    echo ($tamam ? 'OK   ' : 'HATA ') . $ad . ($not !== '' ? ' -> ' . $not : '') . PHP_EOL;
}

function reddedilmeli(PDO $pdo, string $ad, callable $islem): void
{
    try {
        $islem();
        sonuc($ad, false, 'kabul edildi');
    } catch (PDOException $e) {
        sonuc($ad, true, 'reddedildi');
    }
}

// 1) Geçerli kayıt kabul edilir (kullanıcı 1 = Çiftçi)
try {
    parsel_ekle($pdo, 1, 'Test parseli', 100.0, SINIR);
    sonuc('çiftçiye geçerli parsel', true, 'kabul edildi');
} catch (PDOException $e) {
    sonuc('çiftçiye geçerli parsel', false, $e->getMessage());
}

// 2) Reddedilmesi gerekenler
reddedilmeli($pdo, 'aynı kullanıcıda aynı ad (UNIQUE)',      fn() => parsel_ekle($pdo, 1, 'Test parseli', 50.0, SINIR));
reddedilmeli($pdo, 'danışmana parsel eklemek (tetikleyici)',  fn() => parsel_ekle($pdo, 3, 'Danışman parseli', 50.0, SINIR));
reddedilmeli($pdo, 'olmayan kullanıcı (FOREIGN KEY)',         fn() => parsel_ekle($pdo, 999, 'Hayalet', 50.0, SINIR));
reddedilmeli($pdo, 'alan sıfır (CHECK alan_da > 0)',          fn() => parsel_ekle($pdo, 1, 'Sıfır alan', 0.0, SINIR));
reddedilmeli($pdo, 'sınırsız parsel (NOT NULL)',              fn() => parsel_ekle($pdo, 1, 'Sınırsız', 50.0, null));

// 3) CASCADE: parsel silinince ölçümleri de silinir
$pid = (int) $pdo->query("SELECT parsel_id FROM PARSEL WHERE ad = 'Test parseli' AND kullanici_id = 1")->fetchColumn();
$pdo->exec("INSERT INTO TOPRAK_OLCUM (parsel_id, yontem, ph, olcum_tarihi) VALUES ($pid, 'Laboratuvar', 6.5, '2026-11-01')");
$once = (int) $pdo->query("SELECT COUNT(*) FROM TOPRAK_OLCUM WHERE parsel_id = $pid")->fetchColumn();
$pdo->exec("DELETE FROM PARSEL WHERE parsel_id = $pid");
$sonra = (int) $pdo->query("SELECT COUNT(*) FROM TOPRAK_OLCUM WHERE parsel_id = $pid")->fetchColumn();
sonuc('parsel silinince ölçümü de silinir (CASCADE)', $once === 1 && $sonra === 0, "önce $once, sonra $sonra");

$pdo->rollBack();
echo PHP_EOL . ($basarisiz === 0 ? 'Tüm testler geçti.' : $basarisiz . ' test başarısız.') . PHP_EOL;
exit($basarisiz === 0 ? 0 : 1);
