<?php
// danisman: prototip/danisman.html dosyasından dönüştürüldü (içerik hâlâ örnek veri)
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';

$k = rol_gerekli('Danışman');
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Akıllı Tarım prototip W10</title><link rel="stylesheet" href="../prototip/assets/stil.css"></head><body class=""><div class="app"><?php yan_menu($k, 'danisman'); ?>
<div class="main"><?php ust_bar($k, 'Danışman Paneli'); ?>
<div class="body"><div class="row" style="grid-template-columns:.85fr 1.6fr">
<div class="card"><h2>Bana paylaşılan</h2>
<div style="padding:7px 8px;background:var(--acik);border:1px solid var(--yesil);border-radius:7px;margin-bottom:6px"><b>Çumra senaryo parseli</b><br><span class="sm">Mehmet Usta · 01.11.2026</span></div>
<div style="padding:7px 8px;background:#fff;border:1px solid var(--cizgi);border-radius:7px"><b>Dere kenarı</b><br><span class="sm">Ali Çiftçi · 01.11.2026</span></div></div>
<div class="card"><h2>Parsel özeti (salt okunur)</h2><table style="margin-bottom:6px">
<tr><td class="sm">Alan</td><td><b>109,4 dönüm · Konya / Çumra</b></td></tr><tr><td class="sm">Son ölçüm</td><td><b>pH 6,4 · nem %22 · tuz Düşük · fosfor Orta</b></td></tr>
<tr><td class="sm">Son NDVI</td><td><b>0,19 (24.10.2026)</b></td></tr><tr><td class="sm">En uygun ürün</td><td><b>Buğday %100 · Ayçiçeği %100 · Arpa %70</b></td></tr>
<tr><td class="sm">Tahmini kâr</td><td><b>146.541 TL (2026 Buğday)</b></td></tr></table>
<h2>Danışman notları</h2><div style="margin:4px 0;padding:5px 8px;background:#fff;border:1px solid var(--cizgi);border-radius:6px"><span class="sm">02.11 2026</span><br>Gübre dozu için bölge tarım müdürlüğüne danışın.</div><div style="margin:4px 0;padding:5px 8px;background:#fff;border:1px solid var(--cizgi);border-radius:6px"><span class="sm">02.11 2026</span><br>Fosfor “Orta”; ekimden önce toprak analizi yeniden doğrulansın.</div>
<div class="in" style="min-height:44px;margin-top:6px;color:var(--soluk)">Yeni not…</div><div style="margin-top:6px"><a class="btn s" href="danisman.php">Not ekle</a></div>
<div class="note w" style="margin-top:6px">Paylaşılmamış parsele erişim denemesi yetkisiz uyarısı verir.</div></div></div></div></div></div>
<div class="wfnote"><b>W10 · Danışman Paneli</b> &nbsp;|&nbsp; <b>Amaç:</b> paylaşılan parsel raporunu okumak ve not eklemek &nbsp;|&nbsp; <b>Açtığı sayfa:</b> Veri Kaynakları</div></body></html>