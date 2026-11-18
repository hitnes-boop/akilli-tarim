<?php
// parsel-ciz: prototip/parsel-ciz.html dosyasından dönüştürüldü (içerik hâlâ örnek veri)
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';

$k = rol_gerekli('Çiftçi');

const PARSEL_HATALARI = [
    'ad'    => 'Parsel adı boş olamaz (en çok 80 karakter).',
    'alan'  => 'Çizilen alan çok küçük ya da geçersiz.',
    'sinir' => 'Sınır geçerli bir GeoJSON Polygon olmalı.',
    'var'   => 'Bu adda bir parseliniz zaten var.',
];
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>Akıllı Tarım prototip W3</title><link rel="stylesheet" href="../prototip/assets/stil.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css"></head><body class=""><div class="app"><?php yan_menu($k, 'parsel-ciz'); ?>
<div class="main"><?php ust_bar($k, 'Yeni Tarla Ekle'); ?>
<div class="body"><div class="row" style="grid-template-columns:1.9fr 1fr;align-items:stretch">
<div id="harita" style="height:430px;border-radius:10px;border:1px solid var(--girdi);z-index:0"></div>
<div class="card"><h2>Parsel bilgisi</h2>
<form class="f" method="post" action="parsel_ekle.php">
<div><label for="ad">Parsel adı</label><input class="in" id="ad" name="ad" required maxlength="80" style="width:100%"></div>
<div><label for="il">İl</label><input class="in" id="il" name="il" style="width:100%"></div>
<div><label for="ilce">İlçe</label><input class="in" id="ilce" name="ilce" style="width:100%"></div>
<div><div class="sm">Alan (kesin değer kayıtta sunucuda hesaplanır)</div>
<b id="alan" style="font:bold 20px Georgia">—</b><div class="sm" id="koseSayisi">0 köşe</div></div>
<div><label>Köşe listesi</label><div class="sm" id="koseListe">Haritada köşelere tıklayın (en az 3).</div></div>
<input type="hidden" id="sinir" name="sinir_geojson">
<div style="display:flex;gap:6px"><button class="btn g" type="button" id="geri" style="flex:1;cursor:pointer">Son köşeyi geri al</button>
<button class="btn g" type="button" id="temizle" style="flex:1;cursor:pointer">Temizle</button></div>
<button class="btn" type="submit" id="kaydet" style="text-align:center;cursor:pointer" disabled>Parseli kaydet</button>
<?php if (isset($_GET['hata'])): ?><div class="note e" role="alert"><?= h(PARSEL_HATALARI[$_GET['hata']] ?? 'Parsel kaydedilemedi.') ?></div><?php endif; ?>
</form></div></div></div></div></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
(function () {
  var harita = L.map('harita').setView([37.57, 32.85], 14);   // Çumra
  var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '© OpenStreetMap'}).addTo(harita);
  var uydu = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom: 19, attribution: 'Esri World Imagery'});
  L.control.layers({'Harita': osm, 'Uydu': uydu}).addTo(harita);

  var noktalar = [], isaretler = [], cokgen = null;

  // Alan: yerel düzlemsel yaklaşım (php/parsel_ekle.php ile aynı formül), dönüm = 1000 m²
  function alanDonum(p) {
    if (p.length < 3) return 0;
    var R = 6371008.8, lat0 = p.reduce(function (t, q) { return t + q[0]; }, 0) / p.length * Math.PI / 180;
    var xy = p.map(function (q) { return [q[1] * Math.PI / 180 * R * Math.cos(lat0), q[0] * Math.PI / 180 * R]; });
    var t = 0;
    for (var i = 0; i < xy.length; i++) { var j = (i + 1) % xy.length; t += xy[i][0] * xy[j][1] - xy[j][0] * xy[i][1]; }
    return Math.abs(t) / 2 / 1000;
  }

  function guncelle() {
    if (cokgen) { harita.removeLayer(cokgen); cokgen = null; }
    if (noktalar.length >= 3) cokgen = L.polygon(noktalar, {color: '#2F6B45', fillColor: '#E3A93B', fillOpacity: 0.35}).addTo(harita);
    var d = alanDonum(noktalar);
    document.getElementById('alan').textContent = noktalar.length >= 3 ? d.toLocaleString('tr-TR', {maximumFractionDigits: 1}) + ' dönüm' : '—';
    document.getElementById('koseSayisi').textContent = noktalar.length + ' köşe';
    document.getElementById('koseListe').innerHTML = noktalar.length ? noktalar.map(function (q, i) {
      return (i + 1) + ') ' + q[0].toFixed(5) + ' N ' + q[1].toFixed(5) + ' E';
    }).join('<br>') : 'Haritada köşelere tıklayın (en az 3).';
    var gecerli = noktalar.length >= 3 && d > 0;
    document.getElementById('kaydet').disabled = !gecerli;
    // GeoJSON: [boylam, enlem], halka kapalı olmalı
    var halka = noktalar.map(function (q) { return [+q[1].toFixed(6), +q[0].toFixed(6)]; });
    if (halka.length) halka.push(halka[0]);
    document.getElementById('sinir').value = gecerli ? JSON.stringify({type: 'Polygon', coordinates: [halka]}) : '';
  }

  harita.on('click', function (e) {
    noktalar.push([e.latlng.lat, e.latlng.lng]);
    isaretler.push(L.circleMarker(e.latlng, {radius: 5, color: '#12281E', fillColor: '#E3A93B', fillOpacity: 1}).addTo(harita));
    guncelle();
  });
  document.getElementById('geri').addEventListener('click', function () {
    if (noktalar.length) { noktalar.pop(); harita.removeLayer(isaretler.pop()); guncelle(); }
  });
  document.getElementById('temizle').addEventListener('click', function () {
    noktalar = []; isaretler.forEach(function (m) { harita.removeLayer(m); }); isaretler = []; guncelle();
  });
})();
</script>
<div class="wfnote"><b>W3 · Yeni Tarla Ekle</b> &nbsp;|&nbsp; <b>Amaç:</b> haritada sınır çizip alan hesaplamak &nbsp;|&nbsp; <b>Açtığı sayfa:</b> Ana Sayfa (kayıttan sonra)</div></body></html>