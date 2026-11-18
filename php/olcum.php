<?php
// NDVI (uydu) ve toprak kartları: NDVI_OLCUM, TOPRAK_OLCUM, URUN tablolarından beslenir
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';

const SEVIYE = ['Düşük' => 1, 'Orta' => 2, 'Yüksek' => 3];
// Ürün puanı ceza ağırlıkları (kaynak: FAO BEFS RA kataloğu kullanımı)
const CEZA = ['ph' => 30, 'nem' => 25, 'tuz' => 25, 'fosfor' => 15];

function tarih_tr(?string $iso): string
{
    $t = $iso ? date_create($iso) : false;
    return $t ? $t->format('d.m.Y') : '—';
}

function ondalik(?float $v, int $n = 2): string
{
    return $v === null ? '—' : number_format($v, $n, ',', '.');
}

/* ------------------------------------------------------------------ NDVI */

function ndvi_seri(int $parsel_id): array
{
    $st = db()->prepare(
        'SELECT n.goruntu_tarihi, n.ort_ndvi, n.bulut_orani, k.ad AS kaynak
           FROM NDVI_OLCUM n JOIN KAYNAK k ON k.kaynak_id = n.kaynak_id
          WHERE n.parsel_id = :p ORDER BY n.goruntu_tarihi'
    );
    $st->execute([':p' => $parsel_id]);
    return $st->fetchAll();
}

// [renk, açıklama]
function ndvi_sinifi(float $v): array
{
    return match (true) {
        $v < 0.2 => ['#D98A3A', 'Çıplak toprak / çok seyrek bitki örtüsü'],
        $v < 0.4 => ['#E3C24B', 'Seyrek bitki örtüsü'],
        $v < 0.6 => ['#B7C95A', 'Orta yoğunlukta bitki örtüsü'],
        default  => ['#7DB35A', 'Yoğun bitki örtüsü'],
    };
}

function ndvi_grafik(array $seri): string
{
    $maks = max(0.5, ceil(max(array_column($seri, 'ort_ndvi')) / 0.25) * 0.25);
    // 0 → y=98, $maks → y=18
    $y = fn(float $v): float => 98 - 80 * $v / $maks;
    $n = count($seri);
    $x = fn(int $i): float => $n === 1 ? 160.0 : 34 + (284 - 34) * $i / ($n - 1);

    $o = '<svg viewBox="0 0 300 120" width="100%"><line x1="30" y1="98" x2="292" y2="98" stroke="#8C8C7A"/>'
       . '<line x1="30" y1="8" x2="30" y2="98" stroke="#8C8C7A"/>';
    foreach ([0, $maks / 2, $maks] as $t) {
        $o .= '<text x="26" y="' . round($y($t) + 3, 1) . '" font-size="9" text-anchor="end" fill="#55645B">' . ondalik($t, $t == 0 ? 0 : 2) . '</text>'
            . '<line x1="30" y1="' . round($y($t), 1) . '" x2="292" y2="' . round($y($t), 1) . '" stroke="#E6DFCF"/>';
    }
    $pts = [];
    foreach ($seri as $i => $r) {
        $pts[] = round($x($i), 1) . ',' . round($y((float) $r['ort_ndvi']), 1);
    }
    if ($n > 1) {
        $o .= '<polyline points="' . implode(' ', $pts) . '" fill="none" stroke="#2F6B45" stroke-width="2"/>';
    }
    foreach ($seri as $i => $r) {
        $cx = round($x($i), 1);
        $cy = round($y((float) $r['ort_ndvi']), 1);
        $o .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="3.2" fill="#E3A93B"/>'
            . '<text x="' . $cx . '" y="111" font-size="9" text-anchor="middle" fill="#55645B">' . h(date_create($r['goruntu_tarihi'])->format('d.m')) . '</text>'
            . '<text x="' . $cx . '" y="' . round($cy - 6, 1) . '" font-size="9" text-anchor="middle" fill="#12281E">' . ondalik((float) $r['ort_ndvi']) . '</text>';
    }
    return $o . '</svg>';
}

function ndvi_kartlari(array $parsel): void
{
    $seri = ndvi_seri((int) $parsel['parsel_id']);
    echo '<div class="row" style="grid-template-columns:1.1fr 1fr">';
    if (!$seri) {
        echo '<div class="card"><h2>Bitki örtüsü (NDVI)</h2><div class="note w">Bu parsel için henüz uydu (NDVI) verisi yok.</div></div></div>';
        return;
    }
    $son = end($seri);
    [$renk, $aciklama] = ndvi_sinifi((float) $son['ort_ndvi']);

    echo '<div class="card"><h2>Bitki örtüsü (NDVI)</h2>'
       . '<div style="display:flex;gap:12px;align-items:center">'
       . '<div style="width:96px;height:96px;border-radius:10px;background:' . $renk . ';display:flex;align-items:center;justify-content:center;'
       . 'font:bold 26px Georgia;color:#12281E">' . ondalik((float) $son['ort_ndvi']) . '</div>'
       . '<div><b>' . h($aciklama) . '</b><div class="sm" style="margin-top:4px">Son görüntü ' . h(tarih_tr($son['goruntu_tarihi']))
       . ' · bulut %' . ondalik((float) $son['bulut_orani']) . '</div>'
       . '<div class="sm">Parsel ortalaması; hücre bazlı değer saklanmıyor.</div></div></div>'
       . '<div class="sm" style="margin-top:8px">Kaynak: ' . h($son['kaynak']) . '</div></div>';

    echo '<div class="card"><h2>NDVI zaman serisi</h2>' . ndvi_grafik($seri)
       . '<div class="sm">' . count($seri) . ' görüntü · Kaynak: ' . h($son['kaynak']) . '</div></div></div>';
}

/* ---------------------------------------------------------------- TOPRAK */

function toprak_gecmisi(int $parsel_id): array
{
    $st = db()->prepare(
        'SELECT * FROM TOPRAK_OLCUM WHERE parsel_id = :p ORDER BY olcum_tarihi DESC, olcum_id DESC'
    );
    $st->execute([':p' => $parsel_id]);
    return $st->fetchAll();
}

// Her ürün için [puan, [gerekçeler]]; ölçümde olmayan değer ceza dışı bırakılır
function urun_puanla(array $urun, array $olcum): array
{
    $puan = 100;
    $not = [];
    $ph = $olcum['ph'] !== null ? (float) $olcum['ph'] : null;
    $aralik = fn($a, $b) => ondalik($a === null ? null : (float) $a, 1) . '–' . ondalik($b === null ? null : (float) $b, 1);

    if ($ph !== null && $urun['ph_min'] !== null && $urun['ph_max'] !== null) {
        if ($ph < $urun['ph_min'] || $ph > $urun['ph_max']) {
            $puan -= CEZA['ph'];
            $not[] = 'pH ' . ondalik($ph, 1) . ' aralık dışı (' . $aralik($urun['ph_min'], $urun['ph_max']) . '): −' . CEZA['ph'] . ' puan';
        } else {
            $not[] = 'pH aralıkta (' . $aralik($urun['ph_min'], $urun['ph_max']) . ')';
        }
    }
    if ($olcum['nem'] !== null && ($urun['nem_min'] !== null || $urun['nem_max'] !== null)) {
        $nem = (float) $olcum['nem'];
        if (($urun['nem_min'] !== null && $nem < $urun['nem_min']) || ($urun['nem_max'] !== null && $nem > $urun['nem_max'])) {
            $puan -= CEZA['nem'];
            $not[] = 'nem aralık dışı: −' . CEZA['nem'] . ' puan';
        }
    }
    if ($olcum['tuz'] !== null && $urun['tuz_max'] !== null) {
        if (SEVIYE[$olcum['tuz']] > SEVIYE[$urun['tuz_max']]) {
            $puan -= CEZA['tuz'];
            $not[] = 'tuzluluk sınırın üstünde: −' . CEZA['tuz'] . ' puan';
        } else {
            $not[] = 'tuzluluk sınır içinde';
        }
    }
    if ($olcum['fosfor'] !== null && $urun['fosfor_min'] !== null) {
        if (SEVIYE[$olcum['fosfor']] < SEVIYE[$urun['fosfor_min']]) {
            $puan -= CEZA['fosfor'];
            $not[] = 'fosfor yetersiz: −' . CEZA['fosfor'] . ' puan';
        }
    }
    return [max(0, $puan), $not];
}

function toprak_kartlari(array $parsel): void
{
    $pid = (int) $parsel['parsel_id'];
    $gecmis = toprak_gecmisi($pid);
    $hata = ['tarih' => 'Ölçüm tarihi geçersiz.', 'ph' => 'pH 0 ile 14 arasında olmalı.', 'nem' => 'Nem 0 ile 100 arasında olmalı.',
             'bos' => 'En az bir ölçüm değeri girin.', 'yontem' => 'Yöntem geçersiz.', 'deger' => 'Tuz ya da fosfor değeri geçersiz.'];

    echo '<div class="row" style="grid-template-columns:1fr 1.1fr"><div class="card"><h2>Toprak ölçümü ekle</h2>';
    echo '<form class="f" method="post" action="toprak_ekle.php"><input type="hidden" name="parsel_id" value="' . $pid . '">';
    echo '<div class="g2"><div><label for="yontem">Yöntem</label><select class="in" id="yontem" name="yontem" style="width:100%">'
       . '<option>Laboratuvar</option><option>Tahmini</option></select></div>'
       . '<div><label for="tarih">Ölçüm tarihi</label><input class="in" type="date" id="tarih" name="olcum_tarihi" value="' . date('Y-m-d') . '" max="' . date('Y-m-d') . '" required style="width:100%"></div></div>';
    echo '<div class="g2"><div><label for="ph">pH (0–14)</label><input class="in" id="ph" name="ph" inputmode="decimal" style="width:100%"></div>'
       . '<div><label for="nem">Nem %</label><input class="in" id="nem" name="nem" inputmode="decimal" style="width:100%"></div></div>';
    echo '<div class="g2">';
    foreach (['tuz' => 'Tuz', 'fosfor' => 'Fosfor'] as $ad => $etiket) {
        echo '<div><label for="' . $ad . '">' . $etiket . '</label><select class="in" id="' . $ad . '" name="' . $ad . '" style="width:100%">'
           . '<option value="">—</option><option>Düşük</option><option>Orta</option><option>Yüksek</option></select></div>';
    }
    echo '</div><button class="btn" type="submit" style="text-align:center;cursor:pointer">Ölçümü kaydet</button>';
    if (isset($_GET['hata'])) {
        echo '<div class="note e" role="alert">' . h($hata[$_GET['hata']] ?? 'Ölçüm kaydedilemedi.') . '</div>';
    } elseif (isset($_GET['tamam'])) {
        echo '<div class="note ok">Ölçüm kaydedildi.</div>';
    }
    echo '</form><h2 style="margin-top:10px">Ölçüm geçmişi</h2>';

    if (!$gecmis) {
        echo '<div class="sm">Henüz ölçüm girilmedi.</div>';
    } else {
        echo '<table><tr><th>Tarih</th><th>Yöntem</th><th class="num">pH</th><th class="num">Nem</th><th>Tuz</th><th>Fosfor</th></tr>';
        foreach ($gecmis as $r) {
            echo '<tr><td>' . h(tarih_tr($r['olcum_tarihi'])) . '</td>'
               . '<td><span class="chip ' . ($r['yontem'] === 'Tahmini' ? 'w' : '') . '">' . h($r['yontem']) . '</span></td>'
               . '<td class="num">' . ondalik($r['ph'] === null ? null : (float) $r['ph'], 1) . '</td>'
               . '<td class="num">' . ondalik($r['nem'] === null ? null : (float) $r['nem'], 0) . '</td>'
               . '<td>' . h($r['tuz'] ?? '-') . '</td><td>' . h($r['fosfor'] ?? '-') . '</td></tr>';
        }
        echo '</table>';
    }
    echo '</div>';

    // ---- ürün önerisi: en güncel ölçüme göre
    echo '<div class="card"><h2>Ürün önerisi</h2>';
    if (!$gecmis) {
        echo '<div class="note w">Öneri için önce bir toprak ölçümü girin.</div></div></div>';
        return;
    }
    $son = $gecmis[0];
    $sonuc = [];
    foreach (db()->query('SELECT * FROM URUN ORDER BY ad')->fetchAll() as $u) {
        [$puan, $not] = urun_puanla($u, $son);
        $sonuc[] = ['ad' => $u['ad'], 'kategori' => $u['kategori'], 'puan' => $puan, 'not' => $not];
    }
    usort($sonuc, fn($a, $b) => $b['puan'] <=> $a['puan'] ?: strcmp($a['ad'], $b['ad']));
    echo '<div class="sm" style="margin-bottom:4px">' . h(tarih_tr($son['olcum_tarihi'])) . ' tarihli ' . h(mb_strtolower($son['yontem'])) . ' ölçüme göre</div>';
    foreach ($sonuc as $s) {
        echo '<div style="margin:5px 0"><div style="display:flex;justify-content:space-between"><b>' . h($s['ad']) . '</b><b>%' . $s['puan'] . '</b></div>'
           . '<div class="bar ' . ($s['puan'] < 100 ? 'a' : '') . '"><i style="width:' . $s['puan'] . '%"></i></div>'
           . '<div class="sm">' . h($s['not'] ? implode('; ', $s['not']) : 'ölçülen değerlerde ceza yok') . '</div></div>';
    }
    echo '<div class="sm" style="margin-top:4px">Ceza ağırlıkları: pH ' . CEZA['ph'] . ', nem ' . CEZA['nem'] . ', tuz ' . CEZA['tuz']
       . ', fosfor ' . CEZA['fosfor'] . '. Ölçülmeyen değerler cezalandırılmaz. Kaynak: FAO BEFS RA kataloğu.</div>';

    // münavebe: bu parseldeki en son kayıtlı ekim
    $st = db()->prepare(
        'SELECT e.sezon_yil, u.ad, u.kategori FROM EKIM e JOIN URUN u ON u.urun_id = e.urun_id
          WHERE e.parsel_id = :p AND e.durum <> \'İptal\' ORDER BY e.sezon_yil DESC, e.ekim_id DESC LIMIT 1'
    );
    $st->execute([':p' => $pid]);
    if ($e = $st->fetch()) {
        echo '<div class="note w" style="margin-top:8px"><b>Münavebe:</b> son kayıtlı ekim (' . (int) $e['sezon_yil'] . ') '
           . h($e['ad']) . ' (' . h($e['kategori']) . '); aynı kategoriden ürün seçiyorsanız ürün sırasını değiştirmek düşünülebilir.</div>';
    }
    echo '<div style="margin-top:8px"><a class="btn" href="ekim.php?id=' . $pid . '">Ekim planla →</a></div></div></div>';
}
