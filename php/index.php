<?php
// Giriş / Kayıt sayfası
declare(strict_types=1);
require_once __DIR__ . '/oturum.php';

$k = kullanici();
if ($k !== null) {                       // zaten girişliyse kendi sayfasına
    header('Location: ' . (ROL_SAYFA[$k['rol']] ?? 'panel.php'));
    exit;
}

const KAYIT_HATALARI = [
    'rol'        => 'Geçerli bir rol seçin.',
    'ad'         => 'Ad boş olamaz (en çok 80 karakter).',
    'eposta'     => 'Geçerli bir e-posta girin.',
    'kisa'       => 'Parola en az 6 karakter olmalı.',
    'eslesmiyor' => 'Parolalar eşleşmiyor.',
    'var'        => 'Bu e-posta ile zaten bir hesap var.',
];
$giris_hata = isset($_GET['hata']);
$kayit_hata = KAYIT_HATALARI[$_GET['kayit_hata'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title><?= h(UYGULAMA_ADI) ?> · Giriş</title>
<link rel="stylesheet" href="../prototip/assets/stil.css"><link rel="stylesheet" href="tam-ekran.css">
</head>
<body>
<div class="login">
  <div class="l">
    <div class="logo"><i></i><?= h(UYGULAMA_ADI) ?></div>
    <h1 style="margin-top:80px;font-size:26px">Toprağını tanı, ona göre ek.</h1>
    <p style="color:#D5E0D8;font-size:13px">Parselini çiz; uydu, hava ve toprak verisini kaynağıyla birlikte gör.</p>
    <ul class="soz">
      <li>Tarlanı haritada tutup sürükleyerek bul</li>
      <li>Sentinel-2 uydu görüntüsüyle bitki örtüsünü gör</li>
      <li>Her sayının kaynağını yanında oku</li>
    </ul>
    <p class="sm" style="color:#8FA698;margin-top:50px">Veri kaynakları: TMO, TÜİK, FAO, Open-Meteo, Sentinel-2, SoilGrids</p>
  </div>
  <div class="r"><div class="box card" style="padding:18px">
    <h2 style="font-size:19px;margin-bottom:10px">Giriş yap</h2>
    <form class="f" method="post" action="giris.php">
      <div><label for="eposta">E-posta</label>
        <input class="in" id="eposta" name="eposta" type="email" required autocomplete="username" style="width:100%"></div>
      <div><label for="parola">Parola</label>
        <input class="in" id="parola" name="parola" type="password" required autocomplete="current-password" style="width:100%"></div>
      <button class="btn" type="submit" style="padding:8px;cursor:pointer">Giriş yap</button>
      <?php if ($giris_hata): ?><div class="note e" role="alert">E-posta veya parola hatalı. Tekrar deneyin.</div><?php endif; ?>
    </form>

    <h2 style="font-size:15px;margin:16px 0 8px">Hesabın yok mu? Kayıt ol</h2>
    <form class="f" method="post" action="kayit.php">
      <div><label for="k_ad">Ad soyad</label><input class="in" id="k_ad" name="ad" required maxlength="80" style="width:100%"></div>
      <div><label for="k_eposta">E-posta</label><input class="in" id="k_eposta" name="eposta" type="email" required style="width:100%"></div>
      <div><label for="k_parola">Parola (en az 6 karakter)</label><input class="in" id="k_parola" name="parola" type="password" required minlength="6" autocomplete="new-password" style="width:100%"></div>
      <div><label for="k_parola2">Parola tekrar</label><input class="in" id="k_parola2" name="parola2" type="password" required minlength="6" autocomplete="new-password" style="width:100%"></div>
      <div><label>Rol</label>
        <label class="chip" style="cursor:pointer"><input type="radio" name="rol" value="Çiftçi" checked> Çiftçi</label>
        <label class="chip" style="cursor:pointer"><input type="radio" name="rol" value="Danışman"> Danışman</label></div>
      <button class="btn g" type="submit" style="padding:8px;cursor:pointer">Kayıt ol</button>
      <?php if ($kayit_hata !== null): ?><div class="note e" role="alert"><?= h($kayit_hata) ?></div><?php endif; ?>
    </form>
  </div></div>
</div>
</body>
</html>
