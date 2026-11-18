# Akıllı Tarım Platformu

BİL 2107 / BİL 2119 İnternet Programcılığı 2 dönem projesi. Parsel tabanlı ürün önerisi ve maliyet–kâr karar destek sistemi (PHP 8, PDO, SQLite, HTML, CSS; harita için Leaflet.js).

## Ekip ve görev dağılımı
| Üye | GitHub | Modüller |
|---|---|---|
| Cahit Enes HOSSANLI (2516013049) | hitnes-boop | M1 Parsel ve Kullanıcı Yönetimi, M3 Ekim, Gider, Hasat ve Kâr |
| Hüseyin KESKİN (2516013042) | twonxd | M2 Gözlem ve Ürün Önerisi, M4 Kaynak Yönetimi ve Danışman Paylaşımı |

Danışman: Öğr. Gör. Dr. Semih GENÇAY

## Klasörler
- `php/` çalışan uygulama (giriş, panel, parsel, ölçüm, ekim, maliyet, raporlar, kaynaklar, danışman)
- `veritabani/` 16 tablolu SQLite şeması (`schema_v1.sql`), örnek veri, testler; `ileri_asama/` dönem sonrası 23 tablolu tasarım
- `prototip/`, `wireframe/`, `gorseller/` tıklanabilir prototip, wireframe ve mockup görselleri
- `belgeler/` proje öneri formu
- `on_calisma/` v0.1 ön çalışması (C#)

## Çalıştırma
PHP 8 ve `pdo_sqlite` eklentisi gerekir. Proje kök klasöründe:

    php -S localhost:8082 -t .

Sonra tarayıcıda http://localhost:8082/php/index.php adresini açın. (Windows'ta `php/php.ini` içindeki `extension_dir` yolunu kendi kurulumunuza göre düzenleyin.)

Demo hesapları (parola: 1234): `mehmet@ornek.com` (Çiftçi), `zeynep.danisman@ornek.com` (Danışman).

## Veritabanını yeniden kurma
    python veritabani/seed_v1.py
    python veritabani/demo_parola.py
