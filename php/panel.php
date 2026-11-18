<?php
// Ana Sayfa (Çiftçi): kullanıcının parselleri ve özet kartları
declare(strict_types=1);
require_once __DIR__ . '/yerlesim.php';

$k = rol_gerekli('Çiftçi');

// v_parsel_ozet'te kullanici_id yok; sahiplik PARSEL ile birleştirilerek süzülür
$st = db()->prepare(
    'SELECT o.* FROM v_parsel_ozet o
       JOIN PARSEL p ON p.parsel_id = o.parsel_id
      WHERE p.kullanici_id = :id
      ORDER BY o.parsel'
);
$st->execute([':id' => $k['id']]);
$parseller = $st->fetchAll();

$toplam_da = array_sum(array_column($parseller, 'alan_da'));

$kaynak = kaynak_ozeti();

?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title><?= h(UYGULAMA_ADI) ?> · Ana Sayfa</title>
<link rel="stylesheet" href="../prototip/assets/stil.css">
</head>
<body>
<div class="app">
  <?php yan_menu($k, 'panel'); ?>

  <div class="main">
    <?php ust_bar($k, 'Ana Sayfa'); ?>
    <div class="body">
      <div class="hero">
        <div><h2>Merhaba <?= h($k['ad']) ?></h2>
          <p>Tarlalarınız için bugün yapılacaklar burada. Her sayının kaynağını yanında görürsünüz.</p></div>
        <a class="btn" href="parsel-ciz.php">+ Yeni tarla ekle</a>
      </div>

      <div class="row" style="grid-template-columns:1fr 1fr 1fr">
        <div class="card stat"><small>Tarla</small><b><?= count($parseller) ?></b><br><span><?= sayi((float) $toplam_da) ?> dönüm</span></div>
        <div class="card stat"><small>Çalışan kaynak</small><b><?= (int) $kaynak['calisan'] ?> / <?= (int) $kaynak['toplam'] ?></b><br><span>Durumu gör</span></div>
      </div>

      <div class="card">
        <h2>Parsellerim</h2>
        <?php if (!$parseller): ?>
          <p class="sm">Henüz tarla eklemediniz.</p>
        <?php else: ?>
        <table>
          <tr><th>Ad</th><th>İl / ilçe</th><th class="num">Dönüm</th><th class="num">Son pH</th><th class="num">Son NDVI</th><th>Durum</th><th></th></tr>
          <?php foreach ($parseller as $p): ?>
          <tr>
            <td><b><?= h($p['parsel']) ?></b></td>
            <td><?= h(trim(($p['il'] ?? '') . ' / ' . ($p['ilce'] ?? ''), ' /')) ?></td>
            <td class="num"><?= sayi((float) $p['alan_da']) ?></td>
            <td class="num"><?= sayi($p['son_ph'] === null ? null : (float) $p['son_ph']) ?></td>
            <td class="num"><?= sayi($p['son_ndvi'] === null ? null : (float) $p['son_ndvi'], 2) ?></td>
            <td><?= $p['son_ph'] === null
                  ? '<span class="chip w">Ölçüm bekliyor</span>'
                  : '<span class="chip">Ölçüm girildi</span>' ?></td>
            <td>
              <a class="btn s g" href="detay-uydu.php?id=<?= (int) $p['parsel_id'] ?>">Aç</a>
              <form method="post" action="parsel_sil.php" style="display:inline"
                    onsubmit="return confirm('Bu tarla silinsin mi?')">
                <input type="hidden" name="parsel_id" value="<?= (int) $p['parsel_id'] ?>">
                <button class="btn s g" type="submit">Sil</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <div style="margin-top:8px"><a class="btn" href="parsel-ciz.php">+ Yeni tarla ekle</a></div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
