import sqlite3, os, json, sys
sys.stdout.reconfigure(encoding='utf-8')
HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, '..', 'src_zip', 'tarim', 'tarim.db')
OUT = os.path.join(HERE, 'akilli_tarim_v1.db')
if os.path.exists(OUT): os.remove(OUT)
d = sqlite3.connect(OUT)
d.execute('PRAGMA foreign_keys = ON')
d.executescript(open(os.path.join(HERE, 'schema_v1.sql'), encoding='utf-8').read())
src = sqlite3.connect(SRC); src.row_factory = sqlite3.Row
def one(q, *a): return d.execute(q, a).fetchone()[0]
lid = lambda: d.execute('SELECT last_insert_rowid()').fetchone()[0]

d.executemany('INSERT INTO KALEM(ad) VALUES (?)', [('Tohum',), ('Gübre',), ('İlaç',), ('Sulama',), ('İşçilik',), ('Makine',)])
def kaynak(ad, url, tur, durum='Çalışıyor', son='2026-11-01'):
    d.execute('INSERT INTO KAYNAK(ad,url,tur,durum,son_basari) VALUES (?,?,?,?,?)', (ad, url, tur, durum, son)); return lid()
k_tmo = kaynak('TMO 2026 Hububat Alım Fiyatları', 'https://www.tmo.gov.tr/Upload/Document/alim/2023/hubmudalimfyt.pdf', 'Fiyat', son='2026-06-09')
k_tuik = kaynak('TÜİK MEDAS Bitkisel Üretim', 'https://biruni.tuik.gov.tr/medas/?kn=92&locale=tr', 'Verim')
k_fao = kaynak('FAO BEFS RA Bitki Kataloğu', 'https://openknowledge.fao.org/server/api/core/bitstreams/ea76cc59-b73f-454b-8765-6aa5882a1a7f/content', 'Gereksinim')
k_hava = kaynak('Open-Meteo', 'https://open-meteo.com/', 'Hava', 'Önbellekte', '2026-11-02')
k_uydu = kaynak('Earth Search (Sentinel-2)', 'https://earth-search.aws.element84.com/v1/search', 'Uydu', 'Erişilemiyor', '2026-11-01')
k_toprak = kaynak('SoilGrids', 'https://rest.isric.org/soilgrids/v2.0/docs', 'Toprak')
k_konum = kaynak('Nominatim (OpenStreetMap)', 'https://www.openstreetmap.org/copyright', 'Konum')
k_tohum = {}
for r in src.execute('select * from seeds'):
    k_tohum[r['id']] = kaynak(r['kaynak'].split(' (dosya')[0], r['kaynak_url'], 'Tohum')

kat = {'Buğday': 'Tahıl', 'Arpa': 'Tahıl', 'Ayçiçeği': 'Yağlı Tohum'}
for r in src.execute('select * from crop_requirements'):
    d.execute('INSERT INTO URUN(ad,kategori,ph_min,ph_max,nem_min,nem_max,tuz_max,fosfor_min,kaynak_id) VALUES (?,?,?,?,?,?,?,?,?)',
              (r['urun'], kat[r['urun']], r['ph_min'], r['ph_max'], r['nem_min'], r['nem_max'], r['tuz_max'], r['fosfor_min'], k_fao))
urun = lambda ad: one('SELECT urun_id FROM URUN WHERE ad=?', ad)
for r in src.execute('select * from price_data'):
    d.execute('INSERT INTO FIYAT(urun_id,kaynak_id,fiyat_tl_ton,tarih) VALUES (?,?,?,?)', (urun(r['urun']), k_tmo, r['fiyat'], r['tarih']))
for r in src.execute('select * from yield_data'):
    d.execute('INSERT INTO VERIM(urun_id,kaynak_id,il,yil,verim_kg_da) VALUES (?,?,?,?,?)', (urun(r['urun']), k_tuik, r['il'], r['yil'], r['verim_kg_da']))
for r in src.execute('select * from seeds'):
    d.execute('INSERT INTO TOHUM_CESIDI(urun_id,kaynak_id,ad,bolge,aciklama) VALUES (?,?,?,?,?)', (urun('Buğday'), k_tohum[r['id']], r['name'], r['region_note'], r['description']))

def kullanici(ad, email, rol):
    d.execute('INSERT INTO KULLANICI(ad,email,parola_tuz,parola_hash,rol) VALUES (?,?,?,?,?)', (ad, email, 'ornek-tuz-0123456789ab', 'ornek-hash-' + email.split('@')[0], rol)); return lid()
mehmet = kullanici('Mehmet Usta', 'mehmet@ornek.com', 'Çiftçi')
ali = kullanici('Ali Çiftçi', 'ali@ornek.com', 'Çiftçi')
zeynep = kullanici('Zeynep Danışman', 'zeynep.danisman@ornek.com', 'Danışman')
def parsel(kid, il, ilce, ad, alan, lat, lon, dlat=0.003, dlon=0.0037):
    ring = [[lon, lat], [lon + dlon, lat], [lon + dlon, lat + dlat], [lon, lat + dlat], [lon, lat]]
    d.execute('INSERT INTO PARSEL(kullanici_id,ad,il,ilce,alan_da,sinir_geojson) VALUES (?,?,?,?,?,?)', (kid, ad, il, ilce, alan, json.dumps({'type': 'Polygon', 'coordinates': [ring]}))); return lid()
p1 = parsel(mehmet, 'Konya', 'Çumra', 'Çumra senaryo parseli', 109.4, 37.570, 32.850)
p2 = parsel(mehmet, 'Konya', 'Karatay', 'Kuzey tarla', 120.0, 37.905, 32.585, 0.0032, 0.0040)
p3 = parsel(ali, 'Konya', 'Çumra', 'Dere kenarı', 80.5, 37.585, 32.870, 0.0027, 0.0033)
p4 = parsel(ali, 'Hatay', 'Antakya', 'Bağ yanı', 45.0, 36.215, 36.160, 0.002, 0.0025)

d.executemany('INSERT INTO TOPRAK_OLCUM(parsel_id,kaynak_id,yontem,ph,nem,tuz,fosfor,olcum_tarihi) VALUES (?,?,?,?,?,?,?,?)', [
    (p1, k_toprak, 'Tahmini', 7.1, 20, 'Düşük', None, '2026-10-01'), (p1, None, 'Laboratuvar', 6.4, 22, 'Düşük', 'Orta', '2026-10-20'),
    (p2, k_toprak, 'Tahmini', 7.6, 17, 'Orta', None, '2026-10-05'), (p3, None, 'Laboratuvar', 6.4, 25, 'Düşük', 'Yüksek', '2026-10-08')])
d.executemany('INSERT INTO NDVI_OLCUM(parsel_id,kaynak_id,goruntu_tarihi,ort_ndvi,bulut_orani) VALUES (?,?,?,?,?)', [
    (p1, k_uydu, '2026-09-24', 0.33, 6.0), (p1, k_uydu, '2026-10-04', 0.27, 12.0), (p1, k_uydu, '2026-10-14', 0.22, 3.0), (p1, k_uydu, '2026-10-24', 0.1852, 1.36),
    (p2, k_uydu, '2026-10-14', 0.19, 5.0), (p3, k_uydu, '2026-10-24', 0.24, 2.0)])
hava = [{'tarih': t, 'min': mn, 'maks': mx, 'yagis': y} for t, mn, mx, y in
        [('2026-11-03', 4, 17, 0), ('2026-11-04', 3, 16, 0), ('2026-11-05', 2, 15, 1), ('2026-11-06', 1, 14, 0), ('2026-11-07', -1, 12, 0), ('2026-11-08', 0, 11, 2), ('2026-11-09', 2, 13, 0)]]
d.execute('INSERT INTO ONBELLEK(kaynak_id,parsel_id,anahtar,ham_json,cekilme_zamani) VALUES (?,?,?,?,?)', (k_hava, p1, 'tahmin-7gun', json.dumps({'gunler': hava}), '2026-11-02 22:10:00'))

def ekim(p, ad, yil, tarih, durum):
    d.execute('INSERT INTO EKIM(parsel_id,urun_id,sezon_yil,ekim_tarihi,durum) VALUES (?,?,?,?,?)', (p, urun(ad), yil, tarih, durum)); return lid()
e25a = ekim(p1, 'Arpa', 2025, '2025-10-25', 'Ekildi'); e25b = ekim(p3, 'Buğday', 2025, '2025-10-30', 'Ekildi')
d.execute('INSERT INTO HASAT VALUES (?,?,?,?)', (e25a, '2026-06-18', 32500, 11.90)); d.execute('INSERT INTO HASAT VALUES (?,?,?,?)', (e25b, '2026-06-25', 21200, 15.80))
e26a = ekim(p1, 'Buğday', 2026, '2026-10-28', 'Ekildi'); e26b = ekim(p2, 'Ayçiçeği', 2026, None, 'Planlandı')
e26c = ekim(p3, 'Buğday', 2026, '2026-11-02', 'Ekildi'); e26d = ekim(p4, 'Arpa', 2026, None, 'Planlandı')
gider = {e25a: [380, 420, 300, 350, 400, 400], e25b: [420, 450, 320, 360, 420, 430], e26a: [500] * 6, e26c: [450, 480, 400, 420, 480, 470], e26b: [650, 520, 300, 400, 400, 330]}
for e, vals in gider.items():
    for kal, v in enumerate(vals, start=1): d.execute('INSERT INTO EKIM_GIDER VALUES (?,?,?)', (e, kal, v))
d.executemany('INSERT INTO PAYLASIM(parsel_id,danisman_id,paylasim_tarihi) VALUES (?,?,?)', [(p1, zeynep, '2026-11-01 10:00:00'), (p3, zeynep, '2026-11-01 10:05:00')])
d.executemany('INSERT INTO DANISMAN_NOTU(paylasim_id,metin,tarih) VALUES (?,?,?)', [
    (1, 'Gübre dozu için bölge tarım müdürlüğüne danışın.', '2026-11-02 09:30:00'), (1, 'Fosfor “Orta”; ekimden önce toprak analizi yeniden doğrulansın.', '2026-11-02 14:20:00')])
d.commit()

log = []
def say(s=''): print(s); log.append(s)
def expect_error(b, sql, a=()):
    try: d.execute(sql, a); d.commit(); say('BAŞARISIZ (hata beklenirdi): ' + b)
    except sqlite3.Error as e: d.rollback(); say('OK  reddedildi  | %s -> %s' % (b, e))
say('=== TABLO / OBJE SAYILARI ===')
say('tablo: %d' % one("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
say('görünüm: %d' % one("SELECT COUNT(*) FROM sqlite_master WHERE type='view'"))
say('tetikleyici: %d' % one("SELECT COUNT(*) FROM sqlite_master WHERE type='trigger'"))
say('indeks (el ile tanımlı): %d' % one("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%'"))
say('foreign_key_check: %s' % d.execute('PRAGMA foreign_key_check').fetchall())
say(); say('=== KURAL TESTLERİ (hepsi reddedilmeli) ===')
expect_error('olmayan kullanıcıya parsel (FK)', "INSERT INTO PARSEL(kullanici_id,ad,alan_da,sinir_geojson) VALUES (999,'x',1,'{}')")
expect_error('danışmana parsel eklemek (tetikleyici)', "INSERT INTO PARSEL(kullanici_id,ad,alan_da,sinir_geojson) VALUES (%d,'x',1,'{}')" % zeynep)
expect_error('çiftçiyle parsel paylaşmak (tetikleyici)', 'INSERT INTO PAYLASIM(parsel_id,danisman_id) VALUES (?,?)', (p2, ali))
expect_error('pH = 15 (CHECK)', "INSERT INTO TOPRAK_OLCUM(parsel_id,yontem,ph,olcum_tarihi) VALUES (?, 'Laboratuvar', 15, '2026-11-01')", (p1,))
expect_error('aynı parsel+ürün+sezon (UNIQUE)', 'INSERT INTO EKIM(parsel_id,urun_id,sezon_yil) VALUES (?,?,2026)', (p1, urun('Buğday')))
expect_error('negatif gider (CHECK)', 'INSERT INTO EKIM_GIDER(ekim_id,kalem_id,tutar) VALUES (?,?,-5)', (e26d, 1))
expect_error('aynı e-posta (UNIQUE)', "INSERT INTO KULLANICI(ad,email,parola_tuz,parola_hash,rol) VALUES ('Y','mehmet@ornek.com','t','h','Çiftçi')")
expect_error('geçersiz rol (CHECK)', "INSERT INTO KULLANICI(ad,email,parola_tuz,parola_hash,rol) VALUES ('Y','y@ornek.com','t','h','Yönetici')")
expect_error('boş danışman notu (CHECK)', "INSERT INTO DANISMAN_NOTU(paylasim_id,metin) VALUES (1,'   ')")
expect_error('kaynaksız ürün (NOT NULL)', "INSERT INTO URUN(ad,kategori) VALUES ('Nohut','Baklagil')")
say(); say('=== TETİKLEYİCİ: hasat sonrası ekim durumu ===')
say(str(d.execute('SELECT ekim_id, durum FROM EKIM WHERE ekim_id IN (?,?)', (e25a, e25b)).fetchall()))
say(); say('=== MÜNAVEBE KONTROLÜ (uygulama sorgusu): önceki sezonla aynı kategori ===')
for r in d.execute('''SELECT p.ad, u1.ad AS onceki, u2.ad AS yeni, e2.sezon_yil FROM EKIM e2 JOIN EKIM e1 ON e1.parsel_id=e2.parsel_id AND e1.sezon_yil=e2.sezon_yil-1
  JOIN URUN u1 ON u1.urun_id=e1.urun_id JOIN URUN u2 ON u2.urun_id=e2.urun_id JOIN PARSEL p ON p.parsel_id=e2.parsel_id WHERE u1.kategori=u2.kategori'''): say(str(tuple(r)))
say(); say('=== GÖRÜNÜM v_parsel_ozet ===')
for r in d.execute('SELECT * FROM v_parsel_ozet'): say(str(r))
say(); say('=== GÖRÜNÜM v_ekim_kar ===')
for r in d.execute('SELECT * FROM v_ekim_kar ORDER BY ekim_id'): say(str(r))
say(); say('=== SORGU PLANI ===')
for r in d.execute('EXPLAIN QUERY PLAN SELECT goruntu_tarihi, ort_ndvi FROM NDVI_OLCUM WHERE parsel_id=1 ORDER BY goruntu_tarihi DESC'): say(str(r[-1]))
say(); say('=== CASCADE TESTİ (geri alınır) ===')
d.execute('BEGIN'); T = ('PARSEL', 'EKIM', 'EKIM_GIDER', 'HASAT', 'NDVI_OLCUM', 'ONBELLEK', 'PAYLASIM', 'DANISMAN_NOTU')
b4 = [one('SELECT COUNT(*) FROM ' + t) for t in T]; d.execute('DELETE FROM PARSEL WHERE parsel_id=?', (p1,)); af = [one('SELECT COUNT(*) FROM ' + t) for t in T]
say('%s\n önce: %s\n sonra: %s' % (T, b4, af)); d.execute('ROLLBACK')
say(); say('=== SATIR SAYILARI ===')
for (t,) in d.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name").fetchall(): say('%-16s %d' % (t, one('SELECT COUNT(*) FROM ' + t)))
open(os.path.join(HERE, 'test_sonuclari.txt'), 'w', encoding='utf-8').write('\n'.join(log))
open(os.path.join(HERE, 'akilli_tarim_v1_tam.sql'), 'w', encoding='utf-8').write('\n'.join(d.iterdump()))
