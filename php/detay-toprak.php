<?php
// detay-toprak: prototip/detay-toprak.html dosyasından dönüştürüldü (içerik hâlâ örnek veri)
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';
require_once __DIR__ . '/olcum.php';

$k = rol_gerekli('Çiftçi');
$parsel = parsel_sec($k);
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Akıllı Tarım prototip W5</title><link rel="stylesheet" href="../prototip/assets/stil.css"><link rel="stylesheet" href="tam-ekran.css"></head><body class=""><div class="app"><?php yan_menu($k, 'detay-toprak', $parsel); ?>
<div class="main"><?php ust_bar($k, $parsel['ad'] . ': Toprak ve Öneri'); ?>
<div class="body"><?php parsel_harita($parsel); ?><?php toprak_kartlari($parsel); ?>
</div></div></div>
<div class="wfnote"><b>W5 · Çumra senaryo parseli: Toprak ve Öneri</b> &nbsp;|&nbsp; <b>Amaç:</b> toprak ölçümü girmek, ürün puanı ve münavebe uyarısı görmek &nbsp;|&nbsp; <b>Açtığı sayfa:</b> Ekim ve Hasat</div></body></html>