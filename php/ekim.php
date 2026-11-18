<?php
// ekim: prototip/ekim.html dosyasından dönüştürüldü (içerik hâlâ örnek veri)
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';

$k = rol_gerekli('Çiftçi');
$parsel = parsel_sec($k);
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Akıllı Tarım prototip W6</title><link rel="stylesheet" href="../prototip/assets/stil.css"></head><body class=""><div class="app"><?php yan_menu($k, 'ekim', $parsel); ?>
<div class="main"><?php ust_bar($k, $parsel['ad'] . ': Ekim ve Hasat'); ?>
<div class="body"><?php parsel_harita($parsel); ?><div class="row" style="grid-template-columns:1fr 1.25fr">
<div class="card"><h2>Ekim kaydı</h2><div class="f"><div class="g2"><div ><label>Ürün</label><div class="in">Buğday ▾</div></div><div ><label>Sezon</label><div class="in">2026</div></div></div><div class="g2"><div ><label>Ekim tarihi</label><div class="in">28.10.2026</div></div><div ><label>Durum</label><div class="in">Ekildi ▾</div></div></div>
<a class="btn" href="ekim.php?id=<?= (int) $parsel['parsel_id'] ?>" style="text-align:center">Ekimi kaydet</a><div class="note e">Aynı parsel, ürün ve sezon için ikinci kayıt oluşturulamaz.</div></div>
<h2 style="margin-top:10px">Hasat kaydı (2026 Buğday)</h2><div class="g3"><div ><label>Hasat tarihi</label><div class="in">—</div></div><div ><label>Miktar (kg)</label><div class="in">—</div></div><div ><label>Satış TL/kg</label><div class="in">—</div></div></div>
<div style="margin-top:6px"><a class="btn s" href="ekim.php?id=<?= (int) $parsel['parsel_id'] ?>">Hasadı kaydet</a></div>
<div class="sm" style="margin-top:4px">Hasat girilince ekim durumu otomatik “Hasat edildi” olur (tetikleyici) ve gerçekleşen kâr hesaplanır.</div></div>
<div class="card"><h2>Ekimler ve sonuçlar</h2><table><tr><th>Sezon</th><th>Ürün</th><th>Durum</th><th>Ekim</th><th class="num">Hasat</th><th class="num">Gerç. kâr (TL)</th></tr><tr><td>2026</td><td><b>Buğday</b></td><td><span class="chip ">Ekildi</span></td><td>2026-10-28</td><td class="num">—</td><td class="num">—</td></tr><tr><td>2025</td><td><b>Arpa</b></td><td><span class="chip ">Hasat edildi</span></td><td>2025-10-25</td><td class="num">32.500 kg</td><td class="num">140.600</td></tr></table>
<div class="note w" style="margin-top:8px"><b>Münavebe:</b> 2025 Arpa → 2026 Buğday (ikisi de Tahıl). Ürün sırasını değiştirmek düşünülebilir.</div>
<div class="sm" style="margin-top:6px">2025 Arpa hasadı: 18.06.2026 · 32.500 kg · 11,90 TL/kg</div></div></div></div></div></div>
<div class="wfnote"><b>W6 · Çumra senaryo parseli: Ekim ve Hasat</b> &nbsp;|&nbsp; <b>Amaç:</b> ekim ve hasat kaydı, gerçekleşen kâr &nbsp;|&nbsp; <b>Açtığı sayfa:</b> Maliyet ve Kâr</div></body></html>