-- ============================================================================
-- AKILLI TARIM PLATFORMU - GELİŞMİŞ VERİTABANI (v2)
-- SQLite 3 | 23 tablo | 3NF | 3 görünüm | 9 tetikleyici | 14 indeks | R*Tree konum indeksi
-- Araştırma dayanakları:
--  * ADAPT (AgGateway) tarım veri modeli: alan/sezon/operasyon ayrımı  -> EKIM, FAALIYET, HASAT
--  * Sentinel-2 NDVI'ın parsel başına zaman serisi + bulut oranıyla saklanması -> NDVI_OLCUM
--  * SQLite R*Tree modülü: poligonun sınırlayıcı kutusunu indeksler      -> PARSEL_KONUM
-- ============================================================================
PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------- 1. TANIM (LOOKUP) TABLOLARI
CREATE TABLE ROL (
    rol_id  INTEGER PRIMARY KEY,
    ad      TEXT NOT NULL UNIQUE CHECK (ad IN ('Çiftçi', 'Danışman'))
);
CREATE TABLE IL (
    il_id   INTEGER PRIMARY KEY,
    ad      TEXT NOT NULL UNIQUE
);
CREATE TABLE ILCE (
    ilce_id INTEGER PRIMARY KEY,
    il_id   INTEGER NOT NULL REFERENCES IL(il_id),
    ad      TEXT NOT NULL,
    UNIQUE (il_id, ad)
);
CREATE TABLE KALEM (
    kalem_id INTEGER PRIMARY KEY,
    ad       TEXT NOT NULL UNIQUE
);
CREATE TABLE FAALIYET_TURU (
    tur_id  INTEGER PRIMARY KEY,
    ad      TEXT NOT NULL UNIQUE,
    birim   TEXT NOT NULL
);
CREATE TABLE KAYNAK (
    kaynak_id   INTEGER PRIMARY KEY,
    ad          TEXT NOT NULL UNIQUE,
    url         TEXT NOT NULL,
    tur         TEXT NOT NULL CHECK (tur IN ('Fiyat','Verim','Gereksinim','Uydu','Hava','Toprak','Konum','Tohum')),
    son_basari  TEXT,
    durum       TEXT NOT NULL DEFAULT 'Çalışıyor' CHECK (durum IN ('Çalışıyor','Önbellekte','Erişilemiyor'))
);

-- ---------------------------------------------------------------- 2. KULLANICI VE PARSEL
CREATE TABLE KULLANICI (
    kullanici_id  INTEGER PRIMARY KEY,
    ad            TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    parola_tuz    TEXT NOT NULL,
    parola_hash   TEXT NOT NULL,
    rol_id        INTEGER NOT NULL REFERENCES ROL(rol_id),
    aktif         INTEGER NOT NULL DEFAULT 1 CHECK (aktif IN (0, 1)),
    kayit_tarihi  TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE PARSEL (
    parsel_id       INTEGER PRIMARY KEY,
    kullanici_id    INTEGER NOT NULL REFERENCES KULLANICI(kullanici_id) ON DELETE CASCADE,
    ilce_id         INTEGER REFERENCES ILCE(ilce_id),
    ad              TEXT NOT NULL,
    alan_da         REAL NOT NULL CHECK (alan_da > 0),
    sinir_geojson   TEXT NOT NULL,
    min_enlem       REAL NOT NULL, max_enlem  REAL NOT NULL,
    min_boylam      REAL NOT NULL, max_boylam REAL NOT NULL,
    olusturma_tarihi TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (kullanici_id, ad),
    CHECK (min_enlem <= max_enlem AND min_boylam <= max_boylam)
);
-- Konumsal arama için R*Tree (bir noktayı/kutuyu kesen parselleri hızlı bulur)
CREATE VIRTUAL TABLE PARSEL_KONUM USING rtree(parsel_id, min_boylam, max_boylam, min_enlem, max_enlem);

-- ---------------------------------------------------------------- 3. GÖZLEM (SENSÖR / UYDU / LAB) TABLOLARI
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
CREATE TABLE NDVI_OLCUM (
    ndvi_id         INTEGER PRIMARY KEY,
    parsel_id       INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    kaynak_id       INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    goruntu_tarihi  TEXT NOT NULL,
    ort_ndvi        REAL NOT NULL CHECK (ort_ndvi BETWEEN -1 AND 1),
    bulut_orani     REAL NOT NULL CHECK (bulut_orani BETWEEN 0 AND 100),
    hucre_sayisi    INTEGER CHECK (hucre_sayisi > 0),
    UNIQUE (parsel_id, goruntu_tarihi)
);
CREATE TABLE HAVA_TAHMINI (
    hava_id         INTEGER PRIMARY KEY,
    parsel_id       INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    kaynak_id       INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    tarih           TEXT NOT NULL,
    min_c           REAL NOT NULL,
    maks_c          REAL NOT NULL,
    yagis_mm        REAL NOT NULL DEFAULT 0 CHECK (yagis_mm >= 0),
    cekilme_zamani  TEXT NOT NULL,
    CHECK (min_c <= maks_c),
    UNIQUE (parsel_id, tarih)
);

-- ---------------------------------------------------------------- 4. ÜRÜN, EKİM, FAALİYET, HASAT
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
CREATE TABLE EKIM (
    ekim_id      INTEGER PRIMARY KEY,
    parsel_id    INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    urun_id      INTEGER NOT NULL REFERENCES URUN(urun_id),
    sezon_yil    INTEGER NOT NULL CHECK (sezon_yil BETWEEN 2000 AND 2100),
    ekim_tarihi  TEXT,
    durum        TEXT NOT NULL DEFAULT 'Planlandı' CHECK (durum IN ('Planlandı','Ekildi','Hasat edildi','İptal')),
    UNIQUE (parsel_id, urun_id, sezon_yil)
);
CREATE TABLE EKIM_GIDER (
    ekim_id   INTEGER NOT NULL REFERENCES EKIM(ekim_id) ON DELETE CASCADE,
    kalem_id  INTEGER NOT NULL REFERENCES KALEM(kalem_id),
    tutar     REAL NOT NULL CHECK (tutar >= 0),          -- TL/dönüm
    PRIMARY KEY (ekim_id, kalem_id)
);
CREATE TABLE FAALIYET (
    faaliyet_id  INTEGER PRIMARY KEY,
    ekim_id      INTEGER NOT NULL REFERENCES EKIM(ekim_id) ON DELETE CASCADE,
    tur_id       INTEGER NOT NULL REFERENCES FAALIYET_TURU(tur_id),
    tarih        TEXT NOT NULL,
    miktar       REAL CHECK (miktar IS NULL OR miktar >= 0),
    aciklama     TEXT
);
CREATE TABLE HASAT (                                      -- EKIM ile 1:1
    ekim_id             INTEGER PRIMARY KEY REFERENCES EKIM(ekim_id) ON DELETE CASCADE,
    hasat_tarihi        TEXT NOT NULL,
    miktar_kg           REAL NOT NULL CHECK (miktar_kg > 0),
    satis_fiyati_tl_kg  REAL NOT NULL CHECK (satis_fiyati_tl_kg >= 0)
);

-- ---------------------------------------------------------------- 5. PİYASA VE İSTATİSTİK (KAYNAKLI)
CREATE TABLE FIYAT (
    fiyat_id      INTEGER PRIMARY KEY,
    urun_id       INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id     INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    fiyat_tl_ton  REAL NOT NULL CHECK (fiyat_tl_ton > 0),
    tarih         TEXT NOT NULL
);
CREATE TABLE VERIM (
    verim_id     INTEGER PRIMARY KEY,
    urun_id      INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id    INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    il_id        INTEGER NOT NULL REFERENCES IL(il_id),
    yil          INTEGER NOT NULL,
    verim_kg_da  REAL NOT NULL CHECK (verim_kg_da > 0),
    UNIQUE (urun_id, il_id, yil)
);
CREATE TABLE TOHUM_CESIDI (
    tohum_id   INTEGER PRIMARY KEY,
    urun_id    INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id  INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    ad         TEXT NOT NULL,
    bolge      TEXT,
    aciklama   TEXT
);

-- ---------------------------------------------------------------- 6. İŞBİRLİĞİ, UYARI, DENETİM
CREATE TABLE PAYLASIM (
    paylasim_id      INTEGER PRIMARY KEY,
    parsel_id        INTEGER NOT NULL REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    danisman_id      INTEGER NOT NULL REFERENCES KULLANICI(kullanici_id) ON DELETE CASCADE,
    paylasim_tarihi  TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (parsel_id, danisman_id)
);
CREATE TABLE DANISMAN_NOTU (
    not_id       INTEGER PRIMARY KEY,
    paylasim_id  INTEGER NOT NULL REFERENCES PAYLASIM(paylasim_id) ON DELETE CASCADE,
    metin        TEXT NOT NULL CHECK (length(trim(metin)) > 0),
    tarih        TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE UYARI (
    uyari_id       INTEGER PRIMARY KEY,
    kullanici_id   INTEGER NOT NULL REFERENCES KULLANICI(kullanici_id) ON DELETE CASCADE,
    parsel_id      INTEGER REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    tur            TEXT NOT NULL CHECK (tur IN ('Don','Münavebe','Kaynak','Toprak')),
    mesaj          TEXT NOT NULL,
    olusma_zamani  TEXT NOT NULL DEFAULT (datetime('now')),
    okundu         INTEGER NOT NULL DEFAULT 0 CHECK (okundu IN (0, 1))
);
CREATE TABLE ISLEM_GUNLUGU (
    log_id        INTEGER PRIMARY KEY,
    kullanici_id  INTEGER REFERENCES KULLANICI(kullanici_id) ON DELETE SET NULL,
    tablo_adi     TEXT NOT NULL,
    kayit_id      INTEGER,
    islem         TEXT NOT NULL CHECK (islem IN ('EKLE','GUNCELLE','SIL')),
    zaman         TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------- 7. İNDEKSLER (FK + sık sorgular)
CREATE INDEX idx_parsel_kullanici      ON PARSEL(kullanici_id);
CREATE INDEX idx_parsel_ilce           ON PARSEL(ilce_id);
CREATE INDEX idx_toprak_parsel_tarih   ON TOPRAK_OLCUM(parsel_id, olcum_tarihi DESC);
CREATE INDEX idx_ndvi_parsel_tarih     ON NDVI_OLCUM(parsel_id, goruntu_tarihi DESC);
CREATE INDEX idx_hava_parsel_tarih     ON HAVA_TAHMINI(parsel_id, tarih);
CREATE INDEX idx_ekim_parsel           ON EKIM(parsel_id, sezon_yil);
CREATE INDEX idx_ekim_urun             ON EKIM(urun_id);
CREATE INDEX idx_faaliyet_ekim         ON FAALIYET(ekim_id, tarih);
CREATE INDEX idx_verim_urun_il         ON VERIM(urun_id, il_id);
CREATE INDEX idx_fiyat_urun_tarih      ON FIYAT(urun_id, tarih DESC);
CREATE INDEX idx_paylasim_danisman     ON PAYLASIM(danisman_id);
CREATE INDEX idx_uyari_kullanici       ON UYARI(kullanici_id, okundu);
CREATE INDEX idx_log_zaman             ON ISLEM_GUNLUGU(zaman);
CREATE INDEX idx_kullanici_rol         ON KULLANICI(rol_id);

-- ---------------------------------------------------------------- 8. GÖRÜNÜMLER (VIEW)
CREATE VIEW v_son_toprak AS
SELECT t.*
FROM TOPRAK_OLCUM t
WHERE t.olcum_id = (SELECT t2.olcum_id FROM TOPRAK_OLCUM t2
                    WHERE t2.parsel_id = t.parsel_id
                    ORDER BY t2.olcum_tarihi DESC, t2.olcum_id DESC LIMIT 1);

CREATE VIEW v_parsel_ozet AS
SELECT p.parsel_id, p.ad AS parsel, k.ad AS sahibi, il.ad AS il, ic.ad AS ilce, p.alan_da,
       s.ph AS son_ph,
       (SELECT n.ort_ndvi FROM NDVI_OLCUM n WHERE n.parsel_id = p.parsel_id
         ORDER BY n.goruntu_tarihi DESC LIMIT 1) AS son_ndvi,
       (SELECT COUNT(*) FROM UYARI u WHERE u.parsel_id = p.parsel_id AND u.okundu = 0) AS okunmamis_uyari
FROM PARSEL p
JOIN KULLANICI k ON k.kullanici_id = p.kullanici_id
LEFT JOIN ILCE ic ON ic.ilce_id = p.ilce_id
LEFT JOIN IL il ON il.il_id = ic.il_id
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
LEFT JOIN ILCE ic ON ic.ilce_id = p.ilce_id
LEFT JOIN (SELECT v1.urun_id, v1.il_id, v1.verim_kg_da AS son_verim FROM VERIM v1
           WHERE v1.yil = (SELECT MAX(v2.yil) FROM VERIM v2 WHERE v2.urun_id = v1.urun_id AND v2.il_id = v1.il_id)) v
       ON v.urun_id = e.urun_id AND v.il_id = ic.il_id
LEFT JOIN (SELECT urun_id, fiyat_tl_ton FROM FIYAT f1
           WHERE f1.tarih = (SELECT MAX(f2.tarih) FROM FIYAT f2 WHERE f2.urun_id = f1.urun_id)) f
       ON f.urun_id = e.urun_id
LEFT JOIN HASAT h ON h.ekim_id = e.ekim_id;

-- ---------------------------------------------------------------- 9. TETİKLEYİCİLER (İŞ KURALLARI)
-- 9.1 Parsel yalnızca Çiftçi rolündeki kullanıcıya ait olabilir
CREATE TRIGGER tr_parsel_sahip_rol BEFORE INSERT ON PARSEL
BEGIN
    SELECT RAISE(ABORT, 'Parsel yalnızca Çiftçi rolündeki kullanıcıya eklenebilir')
    WHERE (SELECT r.ad FROM KULLANICI k JOIN ROL r ON r.rol_id = k.rol_id
           WHERE k.kullanici_id = NEW.kullanici_id) <> 'Çiftçi';
END;
-- 9.2 Parsel paylaşımı yalnızca Danışman rolündeki kullanıcıyla yapılabilir
CREATE TRIGGER tr_paylasim_danisman_rol BEFORE INSERT ON PAYLASIM
BEGIN
    SELECT RAISE(ABORT, 'Parsel yalnızca Danışman rolündeki kullanıcıyla paylaşılabilir')
    WHERE (SELECT r.ad FROM KULLANICI k JOIN ROL r ON r.rol_id = k.rol_id
           WHERE k.kullanici_id = NEW.danisman_id) <> 'Danışman';
END;
-- 9.3 R*Tree ile PARSEL senkronu
CREATE TRIGGER tr_parsel_konum_ekle AFTER INSERT ON PARSEL
BEGIN
    INSERT INTO PARSEL_KONUM VALUES (NEW.parsel_id, NEW.min_boylam, NEW.max_boylam, NEW.min_enlem, NEW.max_enlem);
END;
CREATE TRIGGER tr_parsel_konum_sil AFTER DELETE ON PARSEL
BEGIN
    DELETE FROM PARSEL_KONUM WHERE parsel_id = OLD.parsel_id;
END;
-- 9.4 Münavebe uyarısı: önceki sezonla aynı kategoride ürün ekilirse uyarı üretir
CREATE TRIGGER tr_ekim_munavebe AFTER INSERT ON EKIM
WHEN EXISTS (SELECT 1 FROM EKIM e2 JOIN URUN u2 ON u2.urun_id = e2.urun_id
             JOIN URUN u1 ON u1.urun_id = NEW.urun_id
             WHERE e2.parsel_id = NEW.parsel_id AND e2.sezon_yil = NEW.sezon_yil - 1
               AND u2.kategori = u1.kategori)
BEGIN
    INSERT INTO UYARI (kullanici_id, parsel_id, tur, mesaj)
    SELECT p.kullanici_id, p.parsel_id, 'Münavebe',
           'Önceki sezon aynı kategoride ürün ekildi; münavebe (ürün sırası) değişimi düşünülebilir.'
    FROM PARSEL p WHERE p.parsel_id = NEW.parsel_id;
END;
-- 9.5 Hasat kaydı eklenince ekim durumu güncellenir
CREATE TRIGGER tr_hasat_durum AFTER INSERT ON HASAT
BEGIN
    UPDATE EKIM SET durum = 'Hasat edildi' WHERE ekim_id = NEW.ekim_id;
END;
-- 9.6 Denetim günlüğü
CREATE TRIGGER tr_log_parsel_ekle AFTER INSERT ON PARSEL
BEGIN
    INSERT INTO ISLEM_GUNLUGU (kullanici_id, tablo_adi, kayit_id, islem) VALUES (NEW.kullanici_id, 'PARSEL', NEW.parsel_id, 'EKLE');
END;
CREATE TRIGGER tr_log_parsel_sil AFTER DELETE ON PARSEL
BEGIN
    INSERT INTO ISLEM_GUNLUGU (kullanici_id, tablo_adi, kayit_id, islem) VALUES (OLD.kullanici_id, 'PARSEL', OLD.parsel_id, 'SIL');
END;
CREATE TRIGGER tr_log_ekim_ekle AFTER INSERT ON EKIM
BEGIN
    INSERT INTO ISLEM_GUNLUGU (kullanici_id, tablo_adi, kayit_id, islem)
    SELECT p.kullanici_id, 'EKIM', NEW.ekim_id, 'EKLE' FROM PARSEL p WHERE p.parsel_id = NEW.parsel_id;
END;
