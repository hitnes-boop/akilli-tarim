<?php
// detay-uydu: prototip/detay-uydu.html dosyasından dönüştürüldü (içerik hâlâ örnek veri)
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';
require_once __DIR__ . '/olcum.php';

$k = rol_gerekli('Çiftçi');
$parsel = parsel_sec($k);
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Akıllı Tarım prototip W4</title><link rel="stylesheet" href="../prototip/assets/stil.css"></head><body class=""><div class="app"><?php yan_menu($k, 'detay-uydu', $parsel); ?>
<div class="main"><?php ust_bar($k, $parsel['ad'] . ': Uydu ve Hava'); ?>
<div class="body"><?php parsel_harita($parsel); ?><?php ndvi_kartlari($parsel); ?>
<div class="row" style="grid-template-columns:1fr 1fr">
<div class="card"><h2>Hava (7 gün)</h2><table><tr><th>Gün</th><th class="num">Min °C</th><th class="num">Maks °C</th><th class="num">Yağış</th></tr><tr><td>03.11</td><td class="num" style="">4</td><td class="num">17</td><td class="num">0 mm</td></tr><tr><td>04.11</td><td class="num" style="">3</td><td class="num">16</td><td class="num">0 mm</td></tr><tr><td>05.11</td><td class="num" style="">2</td><td class="num">15</td><td class="num">1 mm</td></tr><tr><td>06.11</td><td class="num" style="">1</td><td class="num">14</td><td class="num">0 mm</td></tr><tr><td>07.11</td><td class="num" style="color:var(--hata);font-weight:700">-1</td><td class="num">12</td><td class="num">0 mm</td></tr><tr><td>08.11</td><td class="num" style="">0</td><td class="num">11</td><td class="num">2 mm</td></tr><tr><td>09.11</td><td class="num" style="">2</td><td class="num">13</td><td class="num">0 mm</td></tr></table></div>
<div class="card"><h2>Veri durumu</h2><table><tr><th>Kaynak</th><th>Durum</th><th>Son</th></tr>
<tr><td>Earth Search</td><td><span class="chip e">Erişilemiyor</span></td><td>24.10.2026</td></tr><tr><td>Open-Meteo</td><td><span class="chip w">Önbellekte</span></td><td>02.11.2026</td></tr><tr><td>SoilGrids</td><td><span class="chip ">Çalışıyor</span></td><td>01.11.2026</td></tr></table>
<div class="note w" style="margin-top:6px">Don riski: 07 Kasım, -1 °C. NDVI son çekilen veriden gösteriliyor.</div></div></div></div></div></div>
<div class="wfnote"><b>W4 · Çumra senaryo parseli: Uydu ve Hava</b> &nbsp;|&nbsp; <b>Amaç:</b> NDVI, hava ve veri durumunu göstermek &nbsp;|&nbsp; <b>Açtığı sayfa:</b> Toprak ve Öneri, Ekim ve Hasat</div></body></html>