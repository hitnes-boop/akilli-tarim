# Akıllı Tarım Platformu

**Parsel tabanlı ürün önerisi ve maliyet–kâr karar destek sistemi.**
Manisa Celal Bayar Üniversitesi, Manisa Teknik Bilimler MYO · BİL 2107 / BİL 2119 İnternet Programcılığı 2 · 2026-2027 Güz dönemi projesi.
Danışman: Öğr. Gör. Dr. Semih GENÇAY

## Proje nedir?

Çiftçi parselini haritada çizer, toprak ölçümünü girer. Sistem aynı parsel için uydu verisinden (Sentinel-2 NDVI) bitki örtüsünü, 7 günlük hava tahminini ve tahmini toprak bilgisini gösterir; ürünleri **puan, gerekçe ve kaynak bağlantısıyla** önerir. Çiftçi gider kalemlerini girince dönüm başına tahmini kârı, hasatta da gerçekleşen kârı görür. Parsel, ziraat mühendisi/danışman rolündeki kullanıcıyla paylaşılabilir; danışman yalnızca kendisine paylaşılan parseli görür ve not ekler.

Hesaplamalar tahmindir, garanti verilmez; gübre dozu önerilmez. Her veri kaynağıyla birlikte gösterilir (TMO, TÜİK, FAO, Open-Meteo, Sentinel-2, SoilGrids).

## Ekip ve görev dağılımı

| Üye | Öğrenci no | GitHub | Modüller |
|---|---|---|---|
| Cahit Enes HOSSANLI | 2516013049 | [hitnes-boop](https://github.com/hitnes-boop) | M1 Parsel ve Kullanıcı Yönetimi, M3 Ekim, Gider, Hasat ve Kâr |
| Hüseyin KESKİN | 2516013042 | [twonxd](https://github.com/twonxd) | M2 Gözlem ve Ürün Önerisi, M4 Kaynak Yönetimi ve Danışman Paylaşımı |

| Modül | Sayfalar | Tablolar |
|---|---|---|
| M1 Parsel ve Kullanıcı Yönetimi | `panel.php`, `parsel-ciz.php`, `parsel_ekle.php`, `parsel_sil.php`, `oturum.php` | KULLANICI, PARSEL |
| M2 Gözlem ve Ürün Önerisi | `detay-uydu.php`, `detay-toprak.php`, `olcum.php`, `toprak_ekle.php` | TOPRAK_OLCUM, NDVI_OLCUM, ONBELLEK, URUN |
| M3 Ekim, Gider, Hasat ve Kâr | `ekim.php`, `maliyet.php` | EKIM, EKIM_GIDER, KALEM, HASAT |
| M4 Kaynak Yönetimi ve Danışman Paylaşımı | `kaynaklar.php`, `danisman.php` | KAYNAK, FIYAT, VERIM, TOHUM_CESIDI, PAYLASIM, DANISMAN_NOTU |

Giriş, kayıt ve raporlar (`raporlar.php`) modül sayısına dahil değildir. Her sayfanın tek sahibi vardır.

## Kullanılan teknolojiler

PHP 8 (PDO), SQLite, HTML, CSS, harita için Leaflet.js. Tasarım: Figma, wireframe ve tıklanabilir HTML prototip.

## Proje durumu

Proje 8 haftalıktır (26.10–21.12.2026). Tasarım çıktıları 02.11.2026'da tamamlanmıştır; modüller 16 Kasım'dan itibaren haftalık planla geliştirilmektedir.

| Özellik | Durum |
|---|---|
| Kayıt, giriş, çıkış, rol yönlendirmesi (Çiftçi / Danışman) | Çalışıyor |
| Ana sayfa: parsel listesi | Çalışıyor |
| Haritada parsel çizme, alan hesabı, kaydetme, silme | Çalışıyor |
| NDVI zaman serisi, 7 günlük hava, tahmini toprak (veritabanından) | Çalışıyor |
| Toprak ölçümü ekleme, ürün uygunluk puanı | Çalışıyor |
| Ekim ve hasat kaydı, maliyet ve kâr | Prototip ekranı, veritabanına bağlanacak (Hafta 6–7) |
| Raporlar, CSV dışa aktarma, grafikler | Planlandı (Hafta 7) |
| Kaynak durumu, CSV içe aktarma | Planlandı (Hafta 7) |
| Parsel paylaşımı ve danışman notu | Planlandı (Hafta 7) |

## Klasör yapısı

```
php/                  Çalışan PHP uygulaması
veritabani/           SQLite şeması (16 tablo), örnek veri, testler
  ileri_asama/        Dönem sonrası için 23 tablolu gelişmiş tasarım
prototip/             Tıklanabilir HTML prototip (index.html'den başlayın)
wireframe/            Aynı 10 sayfanın wireframe sürümü
gorseller/            Mockup, wireframe, ER ve akış şeması görselleri
belgeler/             Proje öneri formu (docx, pdf)
on_calisma/           v0.1 ön çalışması (C#, SQLite)
```

## Kurulum ve çalıştırma

Gerekenler: PHP 8 ve `pdo_sqlite` eklentisi.

```bash
git clone https://github.com/hitnes-boop/akilli-tarim.git
cd akilli-tarim
php -S localhost:8082 -t .
```

Sonra tarayıcıda http://localhost:8082/php/index.php adresini açın.

Windows'ta `php/php.ini` içindeki `extension_dir` yolunu kendi PHP kurulumunuza göre düzenleyin ve şu satırlarla çalıştırın:

```bash
php -c php/php.ini -S localhost:8082 -t .
```

### Demo hesapları (parola: `1234`)

| E-posta | Rol |
|---|---|
| mehmet@ornek.com | Çiftçi |
| ali@ornek.com | Çiftçi |
| zeynep.danisman@ornek.com | Danışman |

## Veritabanı

16 tablo, 3 görünüm, 3 tetikleyici, 9 indeks. Şema 3. normal formdadır. Kısıtlar (FOREIGN KEY, UNIQUE, CHECK, NOT NULL, ON DELETE CASCADE) veritabanı düzeyindedir.

- Şema: `veritabani/schema_v1.sql`
- Hazır veritabanı: `veritabani/akilli_tarim_v1.db` · tam döküm: `akilli_tarim_v1_tam.sql`
- Test sonuçları: `veritabani/test_sonuclari.txt` (10 kural testi reddedildi; hasat tetikleyicisi, CASCADE silme ve münavebe sorgusu denendi)

Veritabanını sıfırdan kurmak için (Python 3 gerekir):

```bash
python veritabani/seed_v1.py
python veritabani/demo_parola.py
```

İlk komut şemayı ve örnek veriyi kurar, ikincisi demo hesaplarının parolasını `1234` yapar.

## Veri kaynakları

TMO 2026 hububat alım fiyatları, TÜİK MEDAS bitkisel üretim istatistikleri, FAO "Catalogue of Crops used in BEFS RA" ürün gereksinimi kataloğu, Open-Meteo (hava), Sentinel-2 / Earth Search (NDVI), SoilGrids (tahmini toprak), Nominatim (il/ilçe).

## Çalışma düzeni

Her görev ayrı dalda yapılır, Pull Request ile birleştirilir; haftalık PR incelemesi diğer ekip üyesi tarafından yapılır.

```bash
git checkout main && git pull
git checkout -b m2-toprak-olcumu
git add . && git commit -m "M2: toprak ölçümü formu"
git push -u origin m2-toprak-olcumu
```
