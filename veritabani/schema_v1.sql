-- ============================================================================
-- AKILLI TARIM PLATFORMU - DÖNEM İÇİ VERİTABANI (v1.0 hedefi)
-- SQLite 3 | 16 tablo | 3NF | 3 görünüm | 3 tetikleyici | 9 indeks
-- Kapsam: 21.12.2026 teslimine kadar kalan ~5 hafta. Gelişmiş 23 tablolu tasarım
-- (faaliyet defteri, uyarı/günlük tabloları, il-ilçe tanımı, R*Tree) "ileri aşama" olarak ayrıca saklanır.
-- Tablolar: KULLANICI, PARSEL, TOPRAK_OLCUM, NDVI_OLCUM, ONBELLEK, URUN, KALEM, EKIM, EKIM_GIDER,
--           HASAT, KAYNAK, FIYAT, VERIM, TOHUM_CESIDI, PAYLASIM, DANISMAN_NOTU
-- ============================================================================
PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------- KAYNAK VE TANIMLAR
CREATE TABLE KAYNAK (
    kaynak_id   INTEGER PRIMARY KEY,
    ad          TEXT NOT NULL UNIQUE,
    url         TEXT NOT NULL,
    tur         TEXT NOT NULL CHECK (tur IN ('Fiyat','Verim','Gereksinim','Uydu','Hava','Toprak','Konum','Tohum')),
    son_basari  TEXT,
    durum       TEXT NOT NULL DEFAULT 'Çalışıyor' CHECK (durum IN ('Çalışıyor','Önbellekte','Erişilemiyor'))
);
CREATE TABLE KALEM (
    kalem_id INTEGER PRIMARY KEY,
    ad       TEXT NOT NULL UNIQUE
);

-- ---------------------------------------------------------------- KULLANICI VE PARSEL
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

-- ---------------------------------------------------------------- GÖZLEM
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
    UNIQUE (parsel_id, goruntu_tarihi)
);
CREATE TABLE ONBELLEK (                                   -- dış servis yanıtları (hava, uydu, toprak)
    onbellek_id     INTEGER PRIMARY KEY,
    kaynak_id       INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    parsel_id       INTEGER REFERENCES PARSEL(parsel_id) ON DELETE CASCADE,
    anahtar         TEXT NOT NULL,
    ham_json        TEXT NOT NULL,
    cekilme_zamani  TEXT NOT NULL,
    UNIQUE (kaynak_id, parsel_id, anahtar)
);

-- ---------------------------------------------------------------- ÜRÜN, EKİM, GİDER, HASAT
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
CREATE TABLE HASAT (                                      -- EKIM ile 1:1
    ekim_id             INTEGER PRIMARY KEY REFERENCES EKIM(ekim_id) ON DELETE CASCADE,
    hasat_tarihi        TEXT NOT NULL,
    miktar_kg           REAL NOT NULL CHECK (miktar_kg > 0),
    satis_fiyati_tl_kg  REAL NOT NULL CHECK (satis_fiyati_tl_kg >= 0)
);

-- ---------------------------------------------------------------- PİYASA VE İSTATİSTİK (KAYNAKLI)
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
    il           TEXT NOT NULL,
    yil          INTEGER NOT NULL,
    verim_kg_da  REAL NOT NULL CHECK (verim_kg_da > 0),
    UNIQUE (urun_id, il, yil)
);
CREATE TABLE TOHUM_CESIDI (
    tohum_id   INTEGER PRIMARY KEY,
    urun_id    INTEGER NOT NULL REFERENCES URUN(urun_id),
    kaynak_id  INTEGER NOT NULL REFERENCES KAYNAK(kaynak_id),
    ad         TEXT NOT NULL,
    bolge      TEXT,
    aciklama   TEXT
);

-- ---------------------------------------------------------------- DANIŞMAN İŞBİRLİĞİ
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

-- ---------------------------------------------------------------- İNDEKSLER
CREATE INDEX idx_parsel_kullanici      ON PARSEL(kullanici_id);
CREATE INDEX idx_toprak_parsel_tarih   ON TOPRAK_OLCUM(parsel_id, olcum_tarihi DESC);
CREATE INDEX idx_ndvi_parsel_tarih     ON NDVI_OLCUM(parsel_id, goruntu_tarihi DESC);
CREATE INDEX idx_ekim_parsel           ON EKIM(parsel_id, sezon_yil);
CREATE INDEX idx_ekim_urun             ON EKIM(urun_id);
CREATE INDEX idx_verim_urun_il         ON VERIM(urun_id, il);
CREATE INDEX idx_fiyat_urun_tarih      ON FIYAT(urun_id, tarih DESC);
CREATE INDEX idx_paylasim_danisman     ON PAYLASIM(danisman_id);
CREATE INDEX idx_onbellek_parsel       ON ONBELLEK(parsel_id);

-- ---------------------------------------------------------------- GÖRÜNÜMLER
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

-- ---------------------------------------------------------------- TETİKLEYİCİLER (iş kuralları)
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
