import sqlite3, os, json, sys
sys.stdout.reconfigure(encoding='utf-8')
HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, '..', 'src_zip', 'tarim', 'tarim.db')
OUT = os.path.join(HERE, 'akilli_tarim_v2.db')
if os.path.exists(OUT): os.remove(OUT)
d = sqlite3.connect(OUT)
d.execute('PRAGMA foreign_keys = ON')
d.executescript(open(os.path.join(HERE, 'schema_v2.sql'), encoding='utf-8').read())
src = sqlite3.connect(SRC); src.row_factory = sqlite3.Row
def one(q, *a): return d.execute(q, a).fetchone()[0]

# ---------------- tanım tabloları
d.executemany('INSERT INTO ROL(ad) VALUES (?)', [('Çiftçi',), ('Danışman',)])
d.executemany('INSERT INTO IL(ad) VALUES (?)', [('Konya',), ('Hatay',)])
d.executemany('INSERT INTO ILCE(il_id, ad) VALUES (?,?)', [(1, 'Çumra'), (1, 'Karatay'), (2, 'Antakya')])
d.executemany('INSERT INTO KALEM(ad) VALUES (?)', [('Tohum',), ('Gübre',), ('İlaç',), ('Sulama',), ('İşçilik',), ('Makine',)])
d.executemany('INSERT INTO FAALIYET_TURU(ad, birim) VALUES (?,?)',
              [('Toprak işleme', 'dönüm'), ('Ekim', 'kg'), ('Gübreleme', 'kg'), ('Sulama', 'm³'), ('İlaçlama', 'lt')])

# ---------------- kaynaklar (gerçek kaynaklar orijinal projeden)
def kaynak(ad, url, tur, durum='Çalışıyor', son='2026-11-03'):
    d.execute('INSERT INTO KAYNAK(ad,url,tur,durum,son_basari) VALUES (?,?,?,?,?)', (ad, url, tur, durum, son))
    return d.execute('SELECT last_insert_rowid()').fetchone()[0]
k_tmo = kaynak('TMO 2026 Hububat Alım Fiyatları', 'https://www.tmo.gov.tr/Upload/Document/alim/2023/hubmudalimfyt.pdf', 'Fiyat', son='2026-06-09')
k_tuik = kaynak('TÜİK MEDAS Bitkisel Üretim', 'https://biruni.tuik.gov.tr/medas/?kn=92&locale=tr', 'Verim')
k_fao = kaynak('FAO BEFS RA Bitki Kataloğu', 'https://openknowledge.fao.org/server/api/core/bitstreams/ea76cc59-b73f-454b-8765-6aa5882a1a7f/content', 'Gereksinim')
k_hava = kaynak('Open-Meteo', 'https://open-meteo.com/', 'Hava', 'Önbellekte', '2026-11-04')
k_uydu = kaynak('Earth Search (Sentinel-2)', 'https://earth-search.aws.element84.com/v1/search', 'Uydu', 'Erişilemiyor', '2026-11-03')
k_toprak = kaynak('SoilGrids', 'https://rest.isric.org/soilgrids/v2.0/docs', 'Toprak')
k_konum = kaynak('Nominatim (OpenStreetMap)', 'https://www.openstreetmap.org/copyright', 'Konum')
k_tohum = {}
for r in src.execute('select * from seeds'):
    ad = r['kaynak'].split(' (dosya')[0]
    k_tohum[r['id']] = kaynak(ad, r['kaynak_url'], 'Tohum', 'Çalışıyor', '2026-11-03')

# ---------------- ürünler (gereksinimler orijinal projeden, kategori eklendi)
kat = {'Buğday': 'Tahıl', 'Arpa': 'Tahıl', 'Ayçiçeği': 'Yağlı Tohum'}
for r in src.execute('select * from crop_requirements'):
    d.execute('INSERT INTO URUN(ad,kategori,ph_min,ph_max,nem_min,nem_max,tuz_max,fosfor_min,kaynak_id) VALUES (?,?,?,?,?,?,?,?,?)',
              (r['urun'], kat[r['urun']], r['ph_min'], r['ph_max'], r['nem_min'], r['nem_max'], r['tuz_max'], r['fosfor_min'], k_fao))
urun = lambda ad: one('SELECT urun_id FROM URUN WHERE ad=?', ad)
for r in src.execute('select * from price_data'):
    d.execute('INSERT INTO FIYAT(urun_id,kaynak_id,fiyat_tl_ton,tarih) VALUES (?,?,?,?)', (urun(r['urun']), k_tmo, r['fiyat'], r['tarih']))
il_id = {'Konya': 1}
for r in src.execute('select * from yield_data'):
    d.execute('INSERT INTO VERIM(urun_id,kaynak_id,il_id,yil,verim_kg_da) VALUES (?,?,?,?,?)', (urun(r['urun']), k_tuik, il_id[r['il']], r['yil'], r['verim_kg_da']))
for r in src.execute('select * from seeds'):
    d.execute('INSERT INTO TOHUM_CESIDI(urun_id,kaynak_id,ad,bolge,aciklama) VALUES (?,?,?,?,?)',
              (urun('Buğday'), k_tohum[r['id']], r['name'], r['region_note'], r['description']))

# ---------------- ÖRNEK veri (prototipteki ekranlarla aynı)
def kullanici(ad, email, rol):
    d.execute('INSERT INTO KULLANICI(ad,email,parola_tuz,parola_hash,rol_id) VALUES (?,?,?,?,?)',
              (ad, email, 'ornek-tuz-0123456789ab', 'ornek-hash-' + email.split('@')[0], rol))
    return d.execute('SELECT last_insert_rowid()').fetchone()[0]
mehmet = kullanici('Mehmet Usta', 'mehmet@ornek.com', 1)
ali = kullanici('Ali Çiftçi', 'ali@ornek.com', 1)
zeynep = kullanici('Zeynep Danışman', 'zeynep.danisman@ornek.com', 2)

def kutu(lat, lon, dlat, dlon):
    return [[lon, lat], [lon + dlon, lat], [lon + dlon, lat + dlat], [lon, lat + dlat], [lon, lat]]
def parsel(kid, ilce, ad, alan, lat, lon, dlat=0.003, dlon=0.0037):
    ring = kutu(lat, lon, dlat, dlon)
    gj = json.dumps({'type': 'Polygon', 'coordinates': [ring]})
    d.execute('INSERT INTO PARSEL(kullanici_id,ilce_id,ad,alan_da,sinir_geojson,min_enlem,max_enlem,min_boylam,max_boylam) VALUES (?,?,?,?,?,?,?,?,?)',
              (kid, ilce, ad, alan, gj, lat, lat + dlat, lon, lon + dlon))
    return d.execute('SELECT last_insert_rowid()').fetchone()[0]
p1 = parsel(mehmet, 1, 'Çumra senaryo parseli', 109.4, 37.570, 32.850)
p2 = parsel(mehmet, 2, 'Kuzey tarla', 120.0, 37.905, 32.585, 0.0032, 0.0040)
p3 = parsel(ali, 1, 'Dere kenarı', 80.5, 37.585, 32.870, 0.0027, 0.0033)
p4 = parsel(ali, 3, 'Bağ yanı', 45.0, 36.215, 36.160, 0.002, 0.0025)

d.executemany('INSERT INTO TOPRAK_OLCUM(parsel_id,kaynak_id,yontem,ph,nem,tuz,fosfor,olcum_tarihi) VALUES (?,?,?,?,?,?,?,?)', [
    (p1, k_toprak, 'Tahmini', 7.1, 20, 'Düşük', None, '2026-10-01'),
    (p1, None, 'Laboratuvar', 6.4, 22, 'Düşük', 'Orta', '2026-10-20'),
    (p2, k_toprak, 'Tahmini', 7.6, 17, 'Orta', None, '2026-10-05'),
    (p3, None, 'Laboratuvar', 6.4, 25, 'Düşük', 'Yüksek', '2026-10-08')])
d.executemany('INSERT INTO NDVI_OLCUM(parsel_id,kaynak_id,goruntu_tarihi,ort_ndvi,bulut_orani,hucre_sayisi) VALUES (?,?,?,?,?,?)', [
    (p1, k_uydu, '2026-09-24', 0.33, 6.0, 1094), (p1, k_uydu, '2026-10-04', 0.27, 12.0, 1094),
    (p1, k_uydu, '2026-10-14', 0.22, 3.0, 1094), (p1, k_uydu, '2026-10-24', 0.1852, 1.36, 1094),
    (p2, k_uydu, '2026-10-14', 0.19, 5.0, 1200), (p3, k_uydu, '2026-10-24', 0.24, 2.0, 805)])
hava = [('2026-11-18', 4, 17, 0), ('2026-11-19', 3, 16, 0), ('2026-11-20', 2, 15, 1), ('2026-11-21', 1, 14, 0),
        ('2026-11-22', -1, 12, 0), ('2026-11-23', 0, 11, 2), ('2026-11-24', 2, 13, 0)]
d.executemany('INSERT INTO HAVA_TAHMINI(parsel_id,kaynak_id,tarih,min_c,maks_c,yagis_mm,cekilme_zamani) VALUES (?,?,?,?,?,?,?)',
              [(p1, k_hava, t, mn, mx, y, '2026-11-17 22:10:00') for t, mn, mx, y in hava])

# 2025 sezonu (hasat edilmiş) -> sonra 2026 ekimleri münavebe tetikleyicisini çalıştırır
def ekim(p, urun_ad, yil, tarih, durum):
    d.execute('INSERT INTO EKIM(parsel_id,urun_id,sezon_yil,ekim_tarihi,durum) VALUES (?,?,?,?,?)', (p, urun(urun_ad), yil, tarih, durum))
    return d.execute('SELECT last_insert_rowid()').fetchone()[0]
e25a = ekim(p1, 'Arpa', 2025, '2025-10-25', 'Ekildi')
e25b = ekim(p3, 'Buğday', 2025, '2025-10-30', 'Ekildi')
d.execute('INSERT INTO HASAT(ekim_id,hasat_tarihi,miktar_kg,satis_fiyati_tl_kg) VALUES (?,?,?,?)', (e25a, '2026-06-18', 32500, 11.90))
d.execute('INSERT INTO HASAT(ekim_id,hasat_tarihi,miktar_kg,satis_fiyati_tl_kg) VALUES (?,?,?,?)', (e25b, '2026-06-25', 21200, 15.80))
e26a = ekim(p1, 'Buğday', 2026, '2026-10-28', 'Ekildi')
e26b = ekim(p2, 'Ayçiçeği', 2026, None, 'Planlandı')
e26c = ekim(p3, 'Buğday', 2026, '2026-11-02', 'Ekildi')
e26d = ekim(p4, 'Arpa', 2026, None, 'Planlandı')
gider = {e25a: [380, 420, 300, 350, 400, 400], e25b: [420, 450, 320, 360, 420, 430], e26a: [500, 500, 500, 500, 500, 500], e26c: [450, 480, 400, 420, 480, 470], e26b: [650, 520, 300, 400, 400, 330]}
for e, vals in gider.items():
    for kal, v in enumerate(vals, start=1):
        d.execute('INSERT INTO EKIM_GIDER(ekim_id,kalem_id,tutar) VALUES (?,?,?)', (e, kal, v))
d.executemany('INSERT INTO FAALIYET(ekim_id,tur_id,tarih,miktar,aciklama) VALUES (?,?,?,?,?)', [
    (e26a, 1, '2026-10-20', 109.4, 'Sonbahar sürümü'), (e26a, 2, '2026-10-28', 1970, 'Tohum ekimi (18 kg/dönüm)'),
    (e26a, 3, '2026-11-05', 5470, 'Taban gübresi'), (e26a, 4, '2026-11-12', 440, 'İlk sulama'),
    (e26c, 2, '2026-11-02', 1450, 'Tohum ekimi')])
d.executemany('INSERT INTO UYARI(kullanici_id,parsel_id,tur,mesaj,okundu) VALUES (?,?,?,?,?)', [
    (mehmet, p1, 'Don', '22 Kasım için tahmini en düşük sıcaklık −1 °C: don riski.', 0),
    (mehmet, p1, 'Kaynak', 'Earth Search erişilemiyor; NDVI son çekilen veriden gösteriliyor.', 0)])
d.executemany('INSERT INTO PAYLASIM(parsel_id,danisman_id,paylasim_tarihi) VALUES (?,?,?)', [(p1, zeynep, '2026-11-15 10:00:00'), (p3, zeynep, '2026-11-15 10:05:00')])
d.executemany('INSERT INTO DANISMAN_NOTU(paylasim_id,metin,tarih) VALUES (?,?,?)', [
    (1, 'Gübre dozu için bölge tarım müdürlüğüne danışın.', '2026-11-16 09:30:00'),
    (1, 'Fosfor “Orta”; ekimden önce toprak analizi yeniden doğrulansın.', '2026-11-17 14:20:00')])
d.commit()

# ---------------- TESTLER
log = []
def say(s=''):
    print(s); log.append(s)
def expect_error(baslik, sql, args=()):
    try:
        d.execute(sql, args); d.commit(); say('BAŞARISIZ (hata beklenirdi): ' + baslik)
    except sqlite3.Error as e:
        d.rollback(); say('OK  reddedildi  | %s -> %s' % (baslik, e))
say('=== TABLO / OBJE SAYILARI ===')
say('tablo: %d' % one("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'PARSEL_KONUM_%'"))
for t, n in [('view', 'görünüm'), ('trigger', 'tetikleyici'), ('index', 'indeks')]:
    extra = " AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'PARSEL_KONUM_%'" if t == 'index' else ''
    say('%s: %d' % (n, one("SELECT COUNT(*) FROM sqlite_master WHERE type=? " + extra, t)))
say('foreign_key_check: %s' % d.execute('PRAGMA foreign_key_check').fetchall())
say(); say('=== KURAL TESTLERİ (hepsi reddedilmeli) ===')
expect_error('olmayan kullanıcıya parsel (FK)', "INSERT INTO PARSEL(kullanici_id,ad,alan_da,sinir_geojson,min_enlem,max_enlem,min_boylam,max_boylam) VALUES (999,'x',1,'{}',0,1,0,1)")
expect_error('danışmana parsel eklemek (trigger)', "INSERT INTO PARSEL(kullanici_id,ad,alan_da,sinir_geojson,min_enlem,max_enlem,min_boylam,max_boylam) VALUES (%d,'x',1,'{}',0,1,0,1)" % zeynep)
expect_error('çiftçiyle parsel paylaşmak (trigger)', 'INSERT INTO PAYLASIM(parsel_id,danisman_id) VALUES (?,?)', (p2, ali))
expect_error('pH = 15 (CHECK)', "INSERT INTO TOPRAK_OLCUM(parsel_id,yontem,ph,olcum_tarihi) VALUES (?, 'Laboratuvar', 15, '2026-11-01')", (p1,))
expect_error('aynı parsel+ürün+sezon (UNIQUE)', "INSERT INTO EKIM(parsel_id,urun_id,sezon_yil) VALUES (?,?,2026)", (p1, urun('Buğday')))
expect_error('negatif gider (CHECK)', 'INSERT INTO EKIM_GIDER(ekim_id,kalem_id,tutar) VALUES (?,?,-5)', (e26d, 1))
expect_error('aynı e-posta (UNIQUE)', "INSERT INTO KULLANICI(ad,email,parola_tuz,parola_hash,rol_id) VALUES ('Y','mehmet@ornek.com','t','h',1)")
expect_error('boş danışman notu (CHECK)', "INSERT INTO DANISMAN_NOTU(paylasim_id,metin) VALUES (1,'   ')")
expect_error('NDVI > 1 (CHECK)', "INSERT INTO NDVI_OLCUM(parsel_id,kaynak_id,goruntu_tarihi,ort_ndvi,bulut_orani) VALUES (?,?, '2026-11-01', 1.5, 0)", (p1, k_uydu))
expect_error('kaynaksız ürün (NOT NULL)', "INSERT INTO URUN(ad,kategori) VALUES ('Nohut','Baklagil')")
say(); say('=== TETİKLEYİCİ SONUÇLARI ===')
say('Münavebe uyarısı (otomatik): %s' % d.execute("SELECT parsel_id, tur FROM UYARI WHERE tur='Münavebe' ORDER BY parsel_id").fetchall())
say('Hasat sonrası EKIM durumu: %s' % d.execute('SELECT ekim_id, durum FROM EKIM WHERE ekim_id IN (?,?)', (e25a, e25b)).fetchall())
say('Denetim günlüğü: %s' % d.execute('SELECT tablo_adi, islem, COUNT(*) FROM ISLEM_GUNLUGU GROUP BY 1,2').fetchall())
say(); say('=== R*TREE: Çumra çevresindeki (32.84–32.87 E, 37.56–37.59 N) parseller ===')
say(str(d.execute('SELECT p.ad FROM PARSEL_KONUM k JOIN PARSEL p ON p.parsel_id=k.parsel_id WHERE k.min_boylam<=32.87 AND k.max_boylam>=32.84 AND k.min_enlem<=37.59 AND k.max_enlem>=37.56 ORDER BY p.ad').fetchall()))
say(); say('=== GÖRÜNÜM v_parsel_ozet ===')
for r in d.execute('SELECT * FROM v_parsel_ozet'): say(str(r))
say(); say('=== GÖRÜNÜM v_ekim_kar ===')
for r in d.execute('SELECT * FROM v_ekim_kar ORDER BY ekim_id'): say(str(r))
say(); say('=== SORGU PLANI (NDVI zaman serisi) ===')
for r in d.execute('EXPLAIN QUERY PLAN SELECT goruntu_tarihi, ort_ndvi FROM NDVI_OLCUM WHERE parsel_id=1 ORDER BY goruntu_tarihi DESC'): say(str(r[-1]))
say(); say('=== CASCADE TESTİ (geri alınır) ===')
d.execute('BEGIN')
before = [one('SELECT COUNT(*) FROM ' + t) for t in ('PARSEL', 'EKIM', 'EKIM_GIDER', 'NDVI_OLCUM', 'FAALIYET', 'PAYLASIM', 'DANISMAN_NOTU')]
d.execute('DELETE FROM PARSEL WHERE parsel_id=?', (p1,))
after = [one('SELECT COUNT(*) FROM ' + t) for t in ('PARSEL', 'EKIM', 'EKIM_GIDER', 'NDVI_OLCUM', 'FAALIYET', 'PAYLASIM', 'DANISMAN_NOTU')]
say('PARSEL, EKIM, EKIM_GIDER, NDVI_OLCUM, FAALIYET, PAYLASIM, DANISMAN_NOTU  önce: %s  sonra: %s' % (before, after))
d.execute('ROLLBACK')
say(); say('=== SATIR SAYILARI ===')
for (t,) in d.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'PARSEL_KONUM_%' ORDER BY name").fetchall():
    say('%-16s %d' % (t, one('SELECT COUNT(*) FROM ' + t)))
open(os.path.join(HERE, 'test_sonuclari.txt'), 'w', encoding='utf-8').write('\n'.join(log))
# tam SQL dökümü (DB Browser / Word ekinde kullanım için)
open(os.path.join(HERE, 'akilli_tarim_v2_tam.sql'), 'w', encoding='utf-8').write('\n'.join(d.iterdump()))
