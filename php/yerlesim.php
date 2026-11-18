<?php
// Ortak sayfa parçaları: yan menü, üst çubuk, parsel seçimi
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/oturum.php';

const LOGO_SVG = '<svg width="20" height="20" viewBox="0 0 28 28"><rect width="28" height="28" rx="7" fill="#E3A93B"/><path d="M8 20C8 12 14 8 21 8c0 7-4 13-12 13M8 21l7-7" fill="none" stroke="#12281E" stroke-width="2.4" stroke-linecap="round"/></svg>';

const ALT_SAYFALAR = [
    'detay-uydu'   => 'Uydu ve hava',
    'detay-toprak' => 'Toprak ve Ürün Önerisi',
    'ekim'         => 'Ekim ve hasat',
    'maliyet'      => 'Maliyet ve kâr',
    'raporlar'     => 'Mühendis raporu',
];

function sayi(?float $v, int $ondalik = 1): string
{
    return $v === null ? '—' : number_format($v, $ondalik, ',', '.');
}

function parsel_listesi(array $k): array
{
    $st = db()->prepare(
        'SELECT parsel_id, ad, alan_da FROM PARSEL WHERE kullanici_id = :k ORDER BY ad'
    );
    $st->execute([':k' => $k['id']]);
    return $st->fetchAll();
}

// ?id= ile seçili parsel; yoksa ilk parsel. Başkasının parseli → panele döner.
function parsel_sec(array $k): array
{
    $liste = parsel_listesi($k);
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    foreach ($liste as $p) {
        if ($id === false || $id === null ? $p === $liste[0] : (int) $p['parsel_id'] === $id) {
            $st = db()->prepare('SELECT il, ilce, sinir_geojson FROM PARSEL WHERE parsel_id = :p AND kullanici_id = :k');
            $st->execute([':p' => $p['parsel_id'], ':k' => $k['id']]);
            return $p + $st->fetch();
        }
    }
    header('Location: panel.php');
    exit;
}

function kaynak_ozeti(): array
{
    return db()->query(
        "SELECT COUNT(*) AS toplam, COALESCE(SUM(durum = 'Çalışıyor'), 0) AS calisan FROM KAYNAK"
    )->fetch();
}

function yan_menu(array $k, string $aktif, ?array $parsel = null): void
{
    $kaynak = kaynak_ozeti();
    $on = fn(bool $b): string => $b ? 'on' : '';
    echo '<nav class="side"><div class="logo">' . LOGO_SVG . h(UYGULAMA_ADI) . '</div>';
    echo '<div class="sec">GENEL</div>';

    if ($k['rol'] === 'Danışman') {
        echo '<a href="danisman.php" class="' . $on($aktif === 'danisman') . '"><b>Paylaşılan Parseller</b></a>';
    } else {
        echo '<a href="panel.php" class="' . $on($aktif === 'panel') . '"><b>Ana Sayfa</b><small>Bugün yapılacaklar</small></a>';
        echo '<a href="parsel-ciz.php" class="' . $on($aktif === 'parsel-ciz') . '"><b>+ Yeni Tarla Ekle</b><small>Haritada çiz</small></a>';

        $liste = parsel_listesi($k);
        echo '<div class="sec">TARLALARIM (' . count($liste) . ')</div>';
        foreach ($liste as $p) {
            $secili = $parsel !== null && (int) $p['parsel_id'] === (int) $parsel['parsel_id'];
            $id = (int) $p['parsel_id'];
            echo '<a href="detay-uydu.php?id=' . $id . '" class="' . $on($secili) . '"><b>' . h($p['ad']) . '</b>'
               . '<small>' . sayi((float) $p['alan_da']) . ' dönüm</small></a>';
            if ($secili) {
                echo '<div class="alt">';
                foreach (ALT_SAYFALAR as $anahtar => $etiket) {
                    echo '<a href="' . $anahtar . '.php?id=' . $id . '" class="' . $on($aktif === $anahtar) . '">' . h($etiket) . '</a>';
                }
                echo '</div>';
            }
        }
    }

    echo '<div class="sec">BİLGİ</div>';
    echo '<a href="kaynaklar.php" class="' . $on($aktif === 'kaynaklar') . '"><b>Veriler Nereden Geliyor?</b>'
       . '<small>' . (int) $kaynak['calisan'] . ' / ' . (int) $kaynak['toplam'] . ' kaynak çalışıyor</small></a>';
    echo '<div class="rol">Rol: ' . h($k['rol']) . '</div></nav>';
}

// $baslik güvenilir HTML değil, düz metindir; burada kaçışlanır
function ust_bar(array $k, string $baslik): void
{
    $kelimeler = preg_split('/\s+/u', trim($k['ad']));
    $ilk = '';
    foreach (array_slice($kelimeler, 0, 2) as $w) {
        $ilk .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    echo '<div class="top"><h1>' . h($baslik) . '</h1>'
       . '<span style="font-size:11.5px">' . h($k['ad']) . '</span><span class="av">' . h($ilk) . '</span>'
       . '<form method="post" action="cikis.php" style="display:inline"><button class="cik" type="submit">Çıkış</button></form></div>';
}

// Parsel sınırını gösteren harita kartı (detay sayfaları)
function parsel_harita(array $parsel): void
{
    $geo = json_decode((string) ($parsel['sinir_geojson'] ?? ''), true);
    $konum = trim(($parsel['il'] ?? '') . ' / ' . ($parsel['ilce'] ?? ''), ' /');
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">';
    echo '<div class="card"><h2>Parsel sınırı</h2>';
    echo '<div class="sm" style="margin-bottom:6px">' . h($parsel['ad']) . ' · ' . sayi((float) $parsel['alan_da']) . ' dönüm'
       . ($konum !== '' ? ' · ' . h($konum) : '') . '</div>';
    if (!is_array($geo)) {
        echo '<div class="note w">Bu parselin sınırı okunamadı.</div></div>';
        return;
    }
    echo '<div id="parselHarita" style="height:240px;border-radius:10px;border:1px solid var(--girdi);z-index:0"></div></div>';
    $json = json_encode($geo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    echo <<<HTML
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
(function () {
  var harita = L.map('parselHarita', {scrollWheelZoom: false});
  var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '© OpenStreetMap'}).addTo(harita);
  var uydu = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom: 19, attribution: 'Esri World Imagery'});
  L.control.layers({'Harita': osm, 'Uydu': uydu}).addTo(harita);
  var kat = L.geoJSON($json, {style: {color: '#2F6B45', fillColor: '#E3A93B', fillOpacity: 0.35, weight: 3}}).addTo(harita);
  harita.fitBounds(kat.getBounds(), {padding: [20, 20]});
})();
</script>
HTML;
}
