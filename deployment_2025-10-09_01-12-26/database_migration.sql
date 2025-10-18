-- Production Database Migration Script
-- Tarih: 2025-10-09 01:12:26
-- Hedef: muhasebedemo veritabanı

CREATE TABLE IF NOT EXISTS firmalar (
        id int(11) NOT NULL AUTO_INCREMENT,
        firma_adi varchar(255) NOT NULL,
        logo varchar(255) DEFAULT NULL,
        aktif tinyint(1) DEFAULT 1,
        olusturma_tarihi timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kullanicilar (
        id int(11) NOT NULL AUTO_INCREMENT,
        firma_id int(11) NOT NULL,
        ad_soyad varchar(150) NOT NULL,
        kullanici_adi varchar(50) NOT NULL,
        sifre varchar(255) NOT NULL,
        rol enum('super_admin','firma_yoneticisi','firma_kullanici') NOT NULL,
        aktif tinyint(1) DEFAULT 1,
        fcm_token text DEFAULT NULL,
        son_giris timestamp NULL DEFAULT NULL,
        olusturma_tarihi timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY kullanici_adi (kullanici_adi),
        KEY firma_id (firma_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cariler (
        id int(11) NOT NULL AUTO_INCREMENT,
        firma_id int(11) NOT NULL,
        unvan varchar(255) NOT NULL,
        vergi_no varchar(20) DEFAULT NULL,
        telefon varchar(20) DEFAULT NULL,
        email varchar(100) DEFAULT NULL,
        adres text DEFAULT NULL,
        bakiye decimal(15,2) DEFAULT 0.00,
        aktif tinyint(1) DEFAULT 1,
        olusturma_tarihi timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY firma_id (firma_id),
        KEY unvan (unvan),
        KEY vergi_no (vergi_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index'ler
CREATE INDEX IF NOT EXISTS idx_faturalar_firma_tarih ON faturalar(firma_id, fatura_tarihi);
CREATE INDEX IF NOT EXISTS idx_cariler_firma_id ON cariler(firma_id);
CREATE INDEX IF NOT EXISTS idx_urunler_firma_id ON urunler(firma_id);
CREATE INDEX IF NOT EXISTS idx_cekler_firma_id ON cekler(firma_id);

-- Admin kullanıcısı
INSERT IGNORE INTO kullanicilar (firma_id, ad_soyad, kullanici_adi, sifre, rol) VALUES (1, 'Sistem Yöneticisi', 'admin', '$2y$10$OKTrVGvAB401nqVi2PXK8O.oKiqRMpf2AZXVT11iH2EsvJ1yzHIYW', 'super_admin');
