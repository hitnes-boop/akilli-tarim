<?php
// Yeni parsel kaydı (POST: ad, il, ilce, sinir_geojson); alan sınırdan sunucuda hesaplanır
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oturum.php';

$k = rol_gerekli('Çiftçi');

// Yerel düzlemsel yaklaşım (tarla ölçeği için yeterli); 1 dönüm = 1000 m²
function alan_donum(array $halka): float
{
    $R = 6371008.8;
    $lat0 = deg2rad(array_sum(array_map(fn($n) => (float) $n[1], $halka)) / count($halka));
    $xy = array_map(fn($n) => [deg2rad((float) $n[0]) * $R * cos($lat0), deg2rad((float) $n[1]) * $R], $halka);
    $t = 0.0;
    for ($i = 0, $c = count($xy); $i < $c; $i++) {
        $j = ($i + 1) % $c;
        $t += $xy[$i][0] * $xy[$j][1] - $xy[$j][0] * $xy[$i][1];
    }
    return round(abs($t) / 2 / 1000, 1);
}

function ekle_hatasi(string $kod): never
{
    header('Location: parsel-ciz.php?hata=' . $kod);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}

$ad    = trim((string) ($_POST['ad'] ?? ''));
$il    = trim((string) ($_POST['il'] ?? ''));
$ilce  = trim((string) ($_POST['ilce'] ?? ''));
$sinir = (string) ($_POST['sinir_geojson'] ?? '');

if ($ad === '' || mb_strlen($ad) > 80) ekle_hatasi('ad');

// Sınır geçerli bir GeoJSON çokgeni olmalı (Polygon ya da Polygon içeren Feature)
$g = json_decode($sinir, true);
if (is_array($g) && ($g['type'] ?? '') === 'Feature') {
    $g = $g['geometry'] ?? null;
}
if (!is_array($g) || ($g['type'] ?? '') !== 'Polygon' || empty($g['coordinates'][0])) {
    ekle_hatasi('sinir');
}

// Halka: en az 4 nokta (3 köşe + kapanış), her biri geçerli [boylam, enlem]
$halka = $g['coordinates'][0];
if (!is_array($halka) || count($halka) < 4 || count($halka) > 5000) ekle_hatasi('sinir');
foreach ($halka as $n) {
    if (!is_array($n) || count($n) < 2 || !is_numeric($n[0]) || !is_numeric($n[1])
        || abs((float) $n[0]) > 180 || abs((float) $n[1]) > 90) {
        ekle_hatasi('sinir');
    }
}
$alan = alan_donum($halka);                       // alan her zaman sunucuda hesaplanır
if ($alan < 0.1 || $alan > 100000) ekle_hatasi('alan');

try {
    $st = db()->prepare(
        'INSERT INTO PARSEL (kullanici_id, ad, il, ilce, alan_da, sinir_geojson)
         VALUES (:k, :ad, :il, :ilce, :alan, :sinir)'
    );
    $st->execute([
        ':k'     => $k['id'],           // sahip her zaman oturumdan; formdan alınmaz
        ':ad'    => $ad,
        ':il'    => $il !== '' ? $il : null,
        ':ilce'  => $ilce !== '' ? $ilce : null,
        ':alan'  => (float) $alan,
        ':sinir' => $sinir,
    ]);
} catch (PDOException $e) {
    // UNIQUE (kullanici_id, ad): aynı adlı parsel zaten var
    if (($e->errorInfo[1] ?? 0) === 19) ekle_hatasi('var');
    throw $e;
}

header('Location: panel.php');
exit;
