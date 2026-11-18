BEGIN TRANSACTION;
CREATE TABLE DANISMAN_NOTU (
    not_id       INTEGER PRIMARY KEY,
    paylasim_id  INTEGER NOT NULL REFERENCES PAYLASIM(paylasim_id) ON DELETE CASCADE,
    metin        TEXT NOT NULL CHECK (length(trim(metin)) > 0),
    tarih        TEXT NOT NULL DEFAULT (datetime('now'))
);
INSERT INTO "DANISMAN_NOTU" VALUES(1,1,'Gübre dozu için bölge tarım müdürlüğüne danışın.','2026-11-02 09:30:00');
INSERT INTO "DANISMAN_NOTU" VALUES(2,1,'Fosfor “Orta”; ekimden önce toprak analizi yeniden doğrulansın.','2026-11-02 14:20:00');
CREATE TABLE EKIM (
    ekim_id      INTEGER PRIMARY KEY,
    parsel_id    INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    urun_id      INTEGER NOT NULL REFERENCES URUN(urun_id),
    sezon_yil    INTEGER NOT NULL CHECK (sezon_yil BETWEEN 2000 AND 2100),
    ekim_tarihi  TEXT,
    durum        TEXT NOT NULL DEFAULT 'Planlandı' CHECK (durum IN ('Planlandı','Ekildi','Hasat edildi','İptal')),
    UNIQUE (parsel_id, urun_id, sezon_yil)
);
INSERT INTO "EKIM" VALUES(1,1,2,2025,'2025-10-25','Hasat edildi');
INSERT INTO "EKIM" VALUES(2,3,1,2025,'2025-10-30','Hasat edildi');
INSERT INTO "EKIM" VALUES(3,1,1,2026,'2026-10-28','Ekildi');
INSERT INTO "EKIM" VALUES(4,2,3,2026,NULL,'Planlandı');
INSERT INTO "EKIM" VALUES(5,3,1,2026,'2026-11-02','Ekildi');
INSERT INTO "EKIM" VALUES(6,4,2,2026,NULL,'Planlandı');
CREATE TABLE EKIM_GIDER (
    ekim_id   INTEGER NOT NULL REFERENCES EKIM(ekim_id) ON DELETE CASCADE,
    kalem_id  INTEGER NOT NULL REFERENCES KALEM(kalem_id),
    tutar     REAL NOT NULL CHECK (tutar >= 0),          -- TL/dönüm
    PRIMARY KEY (ekim_id, kalem_id)
);
INSERT INTO "EKIM_GIDER" VALUES(1,1,380.0);
INSERT INTO "EKIM_GIDER" VALUES(1,2,420.0);
INSERT INTO "EKIM_GIDER" VALUES(1,3,300.0);
INSERT INTO "EKIM_GIDER" VALUES(1,4,350.0);
INSERT INTO "EKIM_GIDER" VALUES(1,5,400.0);
INSERT INTO "EKIM_GIDER" VALUES(1,6,400.0);
INSERT INTO "EKIM_GIDER" VALUES(2,1,420.0);
INSERT INTO "EKIM_GIDER" VALUES(2,2,450.0);
INSERT INTO "EKIM_GIDER" VALUES(2,3,320.0);
INSERT INTO "EKIM_GIDER" VALUES(2,4,360.0);
INSERT INTO "EKIM_GIDER" VALUES(2,5,420.0);
INSERT INTO "EKIM_GIDER" VALUES(2,6,430.0);
INSERT INTO "EKIM_GIDER" VALUES(3,1,500.0);
INSERT INTO "EKIM_GIDER" VALUES(3,2,500.0);
INSERT INTO "EKIM_GIDER" VALUES(3,3,500.0);
INSERT INTO "EKIM_GIDER" VALUES(3,4,500.0);
INSERT INTO "EKIM_GIDER" VALUES(3,5,500.0);
INSERT INTO "EKIM_GIDER" VALUES(3,6,500.0);
INSERT INTO "EKIM_GIDER" VALUES(5,1,450.0);
INSERT INTO "EKIM_GIDER" VALUES(5,2,480.0);
INSERT INTO "EKIM_GIDER" VALUES(5,3,400.0);
INSERT INTO "EKIM_GIDER" VALUES(5,4,420.0);
INSERT INTO "EKIM_GIDER" VALUES(5,5,480.0);
INSERT INTO "EKIM_GIDER" VALUES(5,6,470.0);
INSERT INTO "EKIM_GIDER" VALUES(4,1,650.0);
INSERT INTO "EKIM_GIDER" VALUES(4,2,520.0);
INSERT INTO "EKIM_GIDER" VALUES(4,3,300.0);
INSERT INTO "EKIM_GIDER" VALUES(4,4,400.0);
INSERT INTO "EKIM_GIDER" VALUES(4,5,400.0);
INSERT INTO "EKIM_GIDER" VALUES(4,6,330.0);
CREATE TABLE FIYAT (
    fiyat_id      INTEGER PRIMARY KEY,
    urun_id       INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id     INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    fiyat_tl_ton  REAL NOT NULL CHECK (fiyat_tl_ton > 0),
    tarih         TEXT NOT NULL
);
INSERT INTO "FIYAT" VALUES(1,1,1,16500.0,'2026-06-09');
INSERT INTO "FIYAT" VALUES(2,2,1,12750.0,'2026-06-09');
CREATE TABLE HASAT (                                      -- EKIM ile 1:1
    ekim_id             INTEGER PRIMARY KEY REFERENCES EKIM(ekim_id) ON DELETE CASCADE,
    hasat_tarihi        TEXT NOT NULL,
    miktar_kg           REAL NOT NULL CHECK (miktar_kg > 0),
    satis_fiyati_tl_kg  REAL NOT NULL CHECK (satis_fiyati_tl_kg >= 0)
);
INSERT INTO "HASAT" VALUES(1,'2026-06-18',32500.0,11.9);
INSERT INTO "HASAT" VALUES(2,'2026-06-25',21200.0,15.8);
CREATE TABLE KALEM (
    kalem_id INTEGER PRIMARY KEY,
    ad       TEXT NOT NULL UNIQUE
);
INSERT INTO "KALEM" VALUES(1,'Tohum');
INSERT INTO "KALEM" VALUES(2,'Gübre');
INSERT INTO "KALEM" VALUES(3,'İlaç');
INSERT INTO "KALEM" VALUES(4,'Sulama');
INSERT INTO "KALEM" VALUES(5,'İşçilik');
INSERT INTO "KALEM" VALUES(6,'Makine');
CREATE TABLE KAYNAK (
    kaynak_id   INTEGER PRIMARY KEY,
    ad          TEXT NOT NULL UNIQUE,
    url         TEXT NOT NULL,
    tur         TEXT NOT NULL CHECK (tur IN ('Fiyat','Verim','Gereksinim','Uydu','Hava','Toprak','Konum','Tohum')),
    son_basari  TEXT,
    durum       TEXT NOT NULL DEFAULT 'Çalışıyor' CHECK (durum IN ('Çalışıyor','Önbellekte','Erişilemiyor'))
);
INSERT INTO "KAYNAK" VALUES(1,'TMO 2026 Hububat Alım Fiyatları','https://www.tmo.gov.tr/Upload/Document/alim/2023/hubmudalimfyt.pdf','Fiyat','2026-06-09','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(2,'TÜİK MEDAS Bitkisel Üretim','https://biruni.tuik.gov.tr/medas/?kn=92&locale=tr','Verim','2026-11-01','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(3,'FAO BEFS RA Bitki Kataloğu','https://openknowledge.fao.org/server/api/core/bitstreams/ea76cc59-b73f-454b-8765-6aa5882a1a7f/content','Gereksinim','2026-11-01','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(4,'Open-Meteo','https://open-meteo.com/','Hava','2026-11-02','Önbellekte');
INSERT INTO "KAYNAK" VALUES(5,'Earth Search (Sentinel-2)','https://earth-search.aws.element84.com/v1/search','Uydu','2026-11-01','Erişilemiyor');
INSERT INTO "KAYNAK" VALUES(6,'SoilGrids','https://rest.isric.org/soilgrids/v2.0/docs','Toprak','2026-11-01','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(7,'Nominatim (OpenStreetMap)','https://www.openstreetmap.org/copyright','Konum','2026-11-01','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(8,'Karabak vd. 2019, Toprak Su Dergisi','https://dergipark.org.tr/en/download/article-file/873425','Tohum','2026-11-01','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(9,'Özkan ve Deniz 2025, Gaziantep Üniversitesi Sosyal Bilimler Dergisi','https://dergipark.org.tr/en/download/article-file/4491167','Tohum','2026-11-01','Çalışıyor');
INSERT INTO "KAYNAK" VALUES(10,'Yücel, Karakılçık Buğdayı Rehberi, Buğday Derneği','https://iklimdostuciftlikler.bugday.org/kaynaklar/hatay/Hatay%20-%20Karak%C4%B1l%C3%A7%C4%B1k%20Bu%C4%9Fday%C4%B1.pdf','Tohum','2026-11-01','Çalışıyor');
CREATE TABLE KULLANICI (
    kullanici_id  INTEGER PRIMARY KEY,
    ad            TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    parola_tuz    TEXT NOT NULL,
    parola_hash   TEXT NOT NULL,
    rol           TEXT NOT NULL CHECK (rol IN ('Çiftçi','Danışman')),
    aktif         INTEGER NOT NULL DEFAULT 1 CHECK (aktif IN (0, 1)),
    kayit_tarihi  TEXT NOT NULL DEFAULT (datetime('now'))
);
INSERT INTO "KULLANICI" VALUES(1,'Mehmet Usta','mehmet@ornek.com','ornek-tuz-0123456789ab','ornek-hash-mehmet','Çiftçi',1,'2026-11-18 21:07:46');
INSERT INTO "KULLANICI" VALUES(2,'Ali Çiftçi','ali@ornek.com','ornek-tuz-0123456789ab','ornek-hash-ali','Çiftçi',1,'2026-11-18 21:07:46');
INSERT INTO "KULLANICI" VALUES(3,'Zeynep Danışman','zeynep.danisman@ornek.com','ornek-tuz-0123456789ab','ornek-hash-zeynep.danisman','Danışman',1,'2026-11-18 21:07:46');
CREATE TABLE NDVI_OLCUM (
    ndvi_id         INTEGER PRIMARY KEY,
    parsel_id       INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    kaynak_id       INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    goruntu_tarihi  TEXT NOT NULL,
    ort_ndvi        REAL NOT NULL CHECK (ort_ndvi BETWEEN -1 AND 1),
    bulut_orani     REAL NOT NULL CHECK (bulut_orani BETWEEN 0 AND 100),
    UNIQUE (parsel_id, goruntu_tarihi)
);
INSERT INTO "NDVI_OLCUM" VALUES(1,1,5,'2026-09-24',0.33,6.0);
INSERT INTO "NDVI_OLCUM" VALUES(2,1,5,'2026-10-04',0.27,12.0);
INSERT INTO "NDVI_OLCUM" VALUES(3,1,5,'2026-10-14',0.22,3.0);
INSERT INTO "NDVI_OLCUM" VALUES(4,1,5,'2026-10-24',0.1852,1.36);
INSERT INTO "NDVI_OLCUM" VALUES(5,2,5,'2026-10-14',0.19,5.0);
INSERT INTO "NDVI_OLCUM" VALUES(6,3,5,'2026-10-24',0.24,2.0);
CREATE TABLE ONBELLEK (                                   -- dış servis yanıtları (hava, uydu, toprak)
    onbellek_id     INTEGER PRIMARY KEY,
    kaynak_id       INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    parsel_id       INTEGER REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    anahtar         TEXT NOT NULL,
    ham_json        TEXT NOT NULL,
    cekilme_zamani  TEXT NOT NULL,
    UNIQUE (kaynak_id, parsel_id, anahtar)
);
INSERT INTO "ONBELLEK" VALUES(1,4,1,'tahmin-7gun','{"gunler": [{"tarih": "2026-11-03", "min": 4, "maks": 17, "yagis": 0}, {"tarih": "2026-11-04", "min": 3, "maks": 16, "yagis": 0}, {"tarih": "2026-11-05", "min": 2, "maks": 15, "yagis": 1}, {"tarih": "2026-11-06", "min": 1, "maks": 14, "yagis": 0}, {"tarih": "2026-11-07", "min": -1, "maks": 12, "yagis": 0}, {"tarih": "2026-11-08", "min": 0, "maks": 11, "yagis": 2}, {"tarih": "2026-11-09", "min": 2, "maks": 13, "yagis": 0}]}','2026-11-02 22:10:00');
CREATE TABLE PARSEL (
    parsel_id       INTEGER PRIMARY KEY,
    kullanici_id    INTEGER NOT NULL REFERENCES KULLANICI(kullanici_id) ON DELETE CASCADE,
    ad              TEXT NOT NULL,
    il              TEXT,
    ilce            TEXT,
    alan_da         REAL NOT NULL CHECK (alan_da > 0),
    sinir_geojson   TEXT NOT NULL,
    olusturma_tarihi TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (kullanici_id, ad)
);
INSERT INTO "PARSEL" VALUES(1,1,'Çumra senaryo parseli','Konya','Çumra',109.4,'{"type": "Polygon", "coordinates": [[[32.85, 37.57], [32.8537, 37.57], [32.8537, 37.573], [32.85, 37.573], [32.85, 37.57]]]}','2026-11-18 21:07:46');
INSERT INTO "PARSEL" VALUES(2,1,'Kuzey tarla','Konya','Karatay',120.0,'{"type": "Polygon", "coordinates": [[[32.585, 37.905], [32.589, 37.905], [32.589, 37.9082], [32.585, 37.9082], [32.585, 37.905]]]}','2026-11-18 21:07:46');
INSERT INTO "PARSEL" VALUES(3,2,'Dere kenarı','Konya','Çumra',80.5,'{"type": "Polygon", "coordinates": [[[32.87, 37.585], [32.8733, 37.585], [32.8733, 37.5877], [32.87, 37.5877], [32.87, 37.585]]]}','2026-11-18 21:07:46');
INSERT INTO "PARSEL" VALUES(4,2,'Bağ yanı','Hatay','Antakya',45.0,'{"type": "Polygon", "coordinates": [[[36.16, 36.215], [36.162499999999994, 36.215], [36.162499999999994, 36.217000000000006], [36.16, 36.217000000000006], [36.16, 36.215]]]}','2026-11-18 21:07:46');
CREATE TABLE PAYLASIM (
    paylasim_id      INTEGER PRIMARY KEY,
    parsel_id        INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    danisman_id      INTEGER NOT NULL REFERENCES KULLANICI(kullanici_id) ON DELETE CASCADE,
    paylasim_tarihi  TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (parsel_id, danisman_id)
);
INSERT INTO "PAYLASIM" VALUES(1,1,3,'2026-11-01 10:00:00');
INSERT INTO "PAYLASIM" VALUES(2,3,3,'2026-11-01 10:05:00');
CREATE TABLE TOHUM_CESIDI (
    tohum_id   INTEGER PRIMARY KEY,
    urun_id    INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id  INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    ad         TEXT NOT NULL,
    bolge      TEXT,
    aciklama   TEXT
);
INSERT INTO "TOHUM_CESIDI" VALUES(1,1,8,'Siyez buğdayı','Kastamonu / İhsangazi','Yerel bir buğday çeşidi; kaynaktaki 2014 anketinde ortalama verim 228 kg/da. Üretilenin %68''i hayvan beslemede ve %32''si bulgur yapımında kullanılıyor.');
INSERT INTO "TOHUM_CESIDI" VALUES(2,1,9,'Kavılca buğdayı','Bölge bilgisi bu kaynakta doğrulanmadı','Triticum dicoccum; kaynakta siyez ve dinkel ile birlikte ilkel buğday çeşitleri arasında sayılıyor. Yerel buğday çeşitlerinin üretim alanlarının giderek daraldığı belirtiliyor.');
INSERT INTO "TOHUM_CESIDI" VALUES(3,1,10,'Karakılçık buğdayı','Hatay','Yerel buğday çeşidi; adını siyah renkli başaklarından alıyor. İri ve sert taneli. Heterojen yapısı nedeniyle resmî tescilden geçmemiş.');
CREATE TABLE TOPRAK_OLCUM (
    olcum_id      INTEGER PRIMARY KEY,
    parsel_id     INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    kaynak_id     INTEGER REFERENCES KAYNAK(kaynak_id),
    yontem        TEXT NOT NULL CHECK (yontem IN ('Laboratuvar','Tahmini')),
    ph            REAL CHECK (ph BETWEEN 0 AND 14),
    nem           REAL CHECK (nem BETWEEN 0 AND 100),
    tuz           TEXT CHECK (tuz IN ('Düşük','Orta','Yüksek')),
    fosfor        TEXT CHECK (fosfor IN ('Düşük','Orta','Yüksek')),
    olcum_tarihi  TEXT NOT NULL
);
INSERT INTO "TOPRAK_OLCUM" VALUES(1,1,6,'Tahmini',7.1,20.0,'Düşük',NULL,'2026-10-01');
INSERT INTO "TOPRAK_OLCUM" VALUES(2,1,NULL,'Laboratuvar',6.4,22.0,'Düşük','Orta','2026-10-20');
INSERT INTO "TOPRAK_OLCUM" VALUES(3,2,6,'Tahmini',7.6,17.0,'Orta',NULL,'2026-10-05');
INSERT INTO "TOPRAK_OLCUM" VALUES(4,3,NULL,'Laboratuvar',6.4,25.0,'Düşük','Yüksek','2026-10-08');
CREATE TABLE URUN (
    urun_id     INTEGER PRIMARY KEY,
    ad          TEXT NOT NULL UNIQUE,
    kategori    TEXT NOT NULL CHECK (kategori IN ('Tahıl','Baklagil','Yağlı Tohum','Diğer')),
    ph_min REAL, ph_max REAL, nem_min REAL, nem_max REAL,
    tuz_max     TEXT CHECK (tuz_max IN ('Düşük','Orta','Yüksek')),
    fosfor_min  TEXT CHECK (fosfor_min IN ('Düşük','Orta','Yüksek')),
    kaynak_id   INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    CHECK (ph_min <= ph_max)
);
INSERT INTO "URUN" VALUES(1,'Buğday','Tahıl',6.0,7.0,NULL,NULL,'Düşük',NULL,3);
INSERT INTO "URUN" VALUES(2,'Arpa','Tahıl',6.5,7.5,NULL,NULL,'Düşük',NULL,3);
INSERT INTO "URUN" VALUES(3,'Ayçiçeği','Yağlı Tohum',6.0,7.5,NULL,NULL,'Düşük',NULL,3);
CREATE TABLE VERIM (
    verim_id     INTEGER PRIMARY KEY,
    urun_id      INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id    INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    il           TEXT NOT NULL,
    yil          INTEGER NOT NULL,
    verim_kg_da  REAL NOT NULL CHECK (verim_kg_da > 0),
    UNIQUE (urun_id, il, yil)
);
INSERT INTO "VERIM" VALUES(1,1,2,'Konya',2016,261.0);
INSERT INTO "VERIM" VALUES(2,1,2,'Konya',2017,284.0);
INSERT INTO "VERIM" VALUES(3,1,2,'Konya',2018,300.0);
INSERT INTO "VERIM" VALUES(4,1,2,'Konya',2019,309.0);
INSERT INTO "VERIM" VALUES(5,1,2,'Konya',2020,307.0);
INSERT INTO "VERIM" VALUES(6,1,2,'Konya',2021,276.0);
INSERT INTO "VERIM" VALUES(7,1,2,'Konya',2022,335.0);
INSERT INTO "VERIM" VALUES(8,1,2,'Konya',2023,386.0);
INSERT INTO "VERIM" VALUES(9,1,2,'Konya',2024,323.0);
INSERT INTO "VERIM" VALUES(10,1,2,'Konya',2025,263.0);
INSERT INTO "VERIM" VALUES(11,2,2,'Konya',2016,270.0);
INSERT INTO "VERIM" VALUES(12,2,2,'Konya',2017,328.0);
INSERT INTO "VERIM" VALUES(13,2,2,'Konya',2018,314.0);
INSERT INTO "VERIM" VALUES(14,2,2,'Konya',2019,330.0);
INSERT INTO "VERIM" VALUES(15,2,2,'Konya',2020,326.0);
INSERT INTO "VERIM" VALUES(16,2,2,'Konya',2021,234.0);
INSERT INTO "VERIM" VALUES(17,2,2,'Konya',2022,317.0);
INSERT INTO "VERIM" VALUES(18,2,2,'Konya',2023,348.0);
INSERT INTO "VERIM" VALUES(19,2,2,'Konya',2024,287.0);
INSERT INTO "VERIM" VALUES(20,2,2,'Konya',2025,214.0);
CREATE INDEX idx_parsel_kullanici      ON PARSEL(kullanici_id);
CREATE INDEX idx_toprak_parsel_tarih   ON TOPRAK_OLCUM(parsel_id, olcum_tarihi DESC);
CREATE INDEX idx_ndvi_parsel_tarih     ON NDVI_OLCUM(parsel_id, goruntu_tarihi DESC);
CREATE INDEX idx_ekim_parsel           ON EKIM(parsel_id, sezon_yil);
CREATE INDEX idx_ekim_urun             ON EKIM(urun_id);
CREATE INDEX idx_verim_urun_il         ON VERIM(urun_id, il);
CREATE INDEX idx_fiyat_urun_tarih      ON FIYAT(urun_id, tarih DESC);
CREATE INDEX idx_paylasim_danisman     ON PAYLASIM(danisman_id);
CREATE INDEX idx_onbellek_parsel       ON ONBELLEK(parsel_id);
CREATE VIEW v_son_toprak AS
SELECT t.*
FROM TOPRAK_OLCUM t
WHERE t.olcum_id = (SELECT t2.olcum_id FROM TOPRAK_OLCUM t2
                    WHERE t2.parsel_id = t.parsel_id
                    ORDER BY t2.olcum_tarihi DESC, t2.olcum_id DESC LIMIT 1);
CREATE VIEW v_parsel_ozet AS
SELECT p.parsel_id, p.ad AS parsel, k.ad AS sahibi, p.il, p.ilce, p.alan_da,
       s.ph AS son_ph,
       (SELECT n.ort_ndvi FROM NDVI_OLCUM n WHERE n.parsel_id = p.parsel_id
         ORDER BY n.goruntu_tarihi DESC LIMIT 1) AS son_ndvi
FROM PARSEL p
JOIN KULLANICI k ON k.kullanici_id = p.kullanici_id
LEFT JOIN v_son_toprak s ON s.parsel_id = p.parsel_id;
CREATE VIEW v_ekim_kar AS
SELECT e.ekim_id, p.ad AS parsel, u.ad AS urun, e.sezon_yil, e.durum, p.alan_da,
       ROUND(COALESCE(g.gider_da, 0) * p.alan_da, 2) AS toplam_gider_tl,
       ROUND(p.alan_da * v.son_verim * f.fiyat_tl_ton / 1000.0, 2) AS tahmini_gelir_tl,
       ROUND(p.alan_da * v.son_verim * f.fiyat_tl_ton / 1000.0 - COALESCE(g.gider_da, 0) * p.alan_da, 2) AS tahmini_kar_tl,
       ROUND(h.miktar_kg * h.satis_fiyati_tl_kg, 2) AS gerceklesen_gelir_tl,
       ROUND(h.miktar_kg * h.satis_fiyati_tl_kg - COALESCE(g.gider_da, 0) * p.alan_da, 2) AS gerceklesen_kar_tl
FROM EKIM e
JOIN PARSEL p ON p.parsel_id = e.parsel_id
JOIN URUN u ON u.urun_id = e.urun_id
LEFT JOIN (SELECT ekim_id, SUM(tutar) AS gider_da FROM EKIM_GIDER GROUP BY ekim_id) g ON g.ekim_id = e.ekim_id
LEFT JOIN (SELECT v1.urun_id, v1.il, v1.verim_kg_da AS son_verim FROM VERIM v1
           WHERE v1.yil = (SELECT MAX(v2.yil) FROM VERIM v2 WHERE v2.urun_id = v1.urun_id AND v2.il = v1.il)) v
       ON v.urun_id = e.urun_id AND v.il = p.il
LEFT JOIN (SELECT urun_id, fiyat_tl_ton FROM FIYAT f1
           WHERE f1.tarih = (SELECT MAX(f2.tarih) FROM FIYAT f2 WHERE f2.urun_id = f1.urun_id)) f
       ON f.urun_id = e.urun_id
LEFT JOIN HASAT h ON h.ekim_id = e.ekim_id;
CREATE TRIGGER tr_parsel_sahip_rol BEFORE INSERT ON PARSEL
BEGIN
    SELECT RAISE(ABORT, 'Parsel yalnızca Çiftçi rolündeki kullanıcıya eklenebilir')
    WHERE (SELECT rol FROM KULLANICI WHERE kullanici_id = NEW.kullanici_id) <> 'Çiftçi';
END;
CREATE TRIGGER tr_paylasim_danisman_rol BEFORE INSERT ON PAYLASIM
BEGIN
    SELECT RAISE(ABORT, 'Parsel yalnızca Danışman rolündeki kullanıcıyla paylaşılabilir')
    WHERE (SELECT rol FROM KULLANICI WHERE kullanici_id = NEW.danisman_id) <> 'Danışman';
END;
CREATE TRIGGER tr_hasat_durum AFTER INSERT ON HASAT
BEGIN
    UPDATE EKIM SET durum = 'Hasat edildi' WHERE ekim_id = NEW.ekim_id;
END;
COMMIT;