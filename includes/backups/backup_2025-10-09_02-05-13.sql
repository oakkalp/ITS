-- Fidan Takip Sistemi Backup
-- Tarih: 2025-10-09 02:05:13
-- Veritabanı: fidan_takip

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Tablo yapısı: bildirim_gecmisi
DROP TABLE IF EXISTS `bildirim_gecmisi`;
CREATE TABLE `bildirim_gecmisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `kullanici_id` int(11) DEFAULT NULL,
  `bildirim_tipi` enum('cek_vade','tahsilat','genel') NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `icerik` text NOT NULL,
  `fcm_token` text DEFAULT NULL,
  `gonderim_durumu` enum('gonderildi','hata','beklemede') DEFAULT 'beklemede',
  `hata_mesaji` text DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bildirim_firma_id` (`firma_id`),
  KEY `idx_bildirim_kullanici_id` (`kullanici_id`),
  KEY `idx_bildirim_tipi` (`bildirim_tipi`),
  KEY `idx_bildirim_tarih` (`olusturma_tarihi`),
  CONSTRAINT `bildirim_gecmisi_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`),
  CONSTRAINT `bildirim_gecmisi_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablo verileri: bildirim_gecmisi
-- Tablo yapısı: cariler
DROP TABLE IF EXISTS `cariler`;
CREATE TABLE `cariler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `cari_kodu` varchar(50) DEFAULT NULL,
  `unvan` varchar(255) NOT NULL,
  `is_musteri` tinyint(1) DEFAULT 0,
  `is_tedarikci` tinyint(1) DEFAULT 0,
  `vergi_dairesi` varchar(100) DEFAULT NULL,
  `vergi_no` varchar(20) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `yetkili_kisi` varchar(150) DEFAULT NULL,
  `bakiye` decimal(15,2) DEFAULT 0.00 COMMENT 'Pozitif=Alacak, Negatif=Borç',
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_musteri` (`is_musteri`),
  KEY `idx_tedarikci` (`is_tedarikci`),
  KEY `idx_cariler_firma_id` (`firma_id`),
  KEY `idx_cariler_unvan` (`unvan`),
  KEY `idx_cariler_vergi_no` (`vergi_no`),
  KEY `idx_cariler_aktif` (`aktif`),
  KEY `idx_cariler_bakiye` (`bakiye`),
  CONSTRAINT `cariler_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: cariler
INSERT INTO `cariler` VALUES
('14','1','1','Onur AKkalp','1','1','0','','05546585007','onurakkalp@hotmail.com','Süleyman Demirel mah şoförler sitesi küme evleri no1 B a2 blok d 3','ooo','-124.00','1','2025-10-09 00:04:57'),
('15','1','0077','UÇAK BOTANİK','1','1','0','11111111111','05546585007','onurakkalp@hotmail.com','Süleyman Demirel mah şoförler sitesi küme evleri no1 B a2 blok d 3','ooo','84.00','1','2025-10-09 00:06:58'),
('16','2','1','Onur AKkalp','1','1','0','11111111111','05546585007','onurakkalp@hotmail.com','Süleyman Demirel mah şoförler sitesi küme evleri no1 B a2 blok d 3','ooo','-3.00','1','2025-10-09 00:33:47');

-- Tablo yapısı: cekler
DROP TABLE IF EXISTS `cekler`;
CREATE TABLE `cekler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `cek_tipi` enum('alinan','verilen') NOT NULL,
  `cari_id` int(11) DEFAULT NULL,
  `cek_no` varchar(50) NOT NULL,
  `banka_adi` varchar(100) NOT NULL,
  `sube` varchar(100) DEFAULT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `vade_tarihi` date NOT NULL,
  `durum` enum('portfoy','ciro','tahsil','odendi','iade','iptal') DEFAULT 'portfoy',
  `aciklama` text DEFAULT NULL,
  `kullanici_id` int(11) NOT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `banka` varchar(100) DEFAULT NULL,
  `cari_disi_cek` tinyint(1) DEFAULT 0,
  `cari_disi_kisi` varchar(255) DEFAULT NULL,
  `cek_kaynagi` varchar(100) DEFAULT NULL COMMENT 'takas,ciro,verilen',
  PRIMARY KEY (`id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_tip` (`cek_tipi`),
  KEY `idx_vade` (`vade_tarihi`),
  KEY `idx_durum` (`durum`),
  KEY `idx_cekler_firma_id` (`firma_id`),
  KEY `idx_cekler_cari_id` (`cari_id`),
  KEY `idx_cekler_vade_tarihi` (`vade_tarihi`),
  KEY `idx_cekler_durum` (`durum`),
  KEY `idx_cekler_cek_no` (`cek_no`),
  CONSTRAINT `cekler_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cekler_ibfk_2` FOREIGN KEY (`cari_id`) REFERENCES `cariler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cekler_ibfk_3` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: cekler
INSERT INTO `cekler` VALUES
('5','2','verilen',NULL,'1','3432','1','666666.00','2025-10-26','tahsil','ödendi','5','2025-10-09 01:20:03',NULL,'1','BURAK YILMAZ','verilen'),
('6','2','alinan','16','18','CEMAL','1','666666.00','2025-10-30','portfoy','','5','2025-10-09 01:20:21',NULL,'0',NULL,NULL);

-- Tablo yapısı: fatura_detaylari
DROP TABLE IF EXISTS `fatura_detaylari`;
CREATE TABLE `fatura_detaylari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fatura_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `miktar` decimal(15,3) NOT NULL,
  `birim_fiyat` decimal(15,2) NOT NULL,
  `kdv_orani` int(11) DEFAULT 20,
  `kdv_tutari` decimal(15,2) DEFAULT 0.00,
  `toplam` decimal(15,2) DEFAULT 0.00,
  `ara_toplam` decimal(15,2) DEFAULT 0.00,
  `kdv_tutar` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_fatura` (`fatura_id`),
  KEY `idx_fatura_detay_fatura_id` (`fatura_id`),
  KEY `idx_fatura_detay_urun_id` (`urun_id`),
  CONSTRAINT `fatura_detaylari_ibfk_1` FOREIGN KEY (`fatura_id`) REFERENCES `faturalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fatura_detaylari_ibfk_2` FOREIGN KEY (`urun_id`) REFERENCES `urunler` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: fatura_detaylari
INSERT INTO `fatura_detaylari` VALUES
('15','21','5','10.000','1.00','20','0.00','12.00','10.00','2.00'),
('16','22','5','5.000','2.00','20','0.00','12.00','10.00','2.00'),
('17','23','5','100.000','1.00','20','0.00','120.00','100.00','20.00'),
('18','24','5','12.000','5.00','20','0.00','72.00','60.00','12.00'),
('19','25','6','10.000','10.00','20','0.00','120.00','100.00','20.00'),
('20','26','6','15.000','35.00','20','0.00','630.00','525.00','105.00');

-- Tablo yapısı: faturalar
DROP TABLE IF EXISTS `faturalar`;
CREATE TABLE `faturalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `cari_id` int(11) NOT NULL,
  `fatura_tipi` enum('alis','satis') NOT NULL,
  `fatura_no` varchar(50) DEFAULT NULL,
  `fatura_tarihi` date NOT NULL,
  `vade_tarihi` date DEFAULT NULL,
  `odeme_tipi` enum('pesin','vadeli') DEFAULT 'pesin',
  `ara_toplam` decimal(15,2) DEFAULT 0.00,
  `kdv_tutari` decimal(15,2) DEFAULT 0.00,
  `genel_toplam` decimal(15,2) DEFAULT 0.00,
  `odenen_tutar` decimal(15,2) DEFAULT 0.00 COMMENT 'Kısmi ödeme takibi',
  `kalan_tutar` decimal(15,2) DEFAULT 0.00,
  `odeme_durumu` enum('odenmedi','kismi','odendi') DEFAULT 'odenmedi',
  `aciklama` text DEFAULT NULL,
  `kullanici_id` int(11) NOT NULL COMMENT 'Faturayı oluşturan kullanıcı',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `kdv_toplam` decimal(15,2) DEFAULT 0.00,
  `toplam_tutar` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_cari` (`cari_id`),
  KEY `idx_tip` (`fatura_tipi`),
  KEY `idx_tarih` (`fatura_tarihi`),
  KEY `idx_faturalar_firma_tarih` (`firma_id`,`fatura_tarihi`),
  KEY `idx_faturalar_cari_tarih` (`cari_id`,`fatura_tarihi`),
  KEY `idx_faturalar_tip_tarih` (`fatura_tipi`,`fatura_tarihi`),
  KEY `idx_faturalar_vade_tarihi` (`vade_tarihi`),
  KEY `idx_faturalar_odeme_durumu` (`odeme_durumu`),
  CONSTRAINT `faturalar_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `faturalar_ibfk_2` FOREIGN KEY (`cari_id`) REFERENCES `cariler` (`id`),
  CONSTRAINT `faturalar_ibfk_3` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: faturalar
INSERT INTO `faturalar` VALUES
('21','1','14','alis','13','2025-10-09','2025-10-09','','10.00','0.00','0.00','0.00','0.00','','','2','2025-10-09 00:05:21','2.00','12.00'),
('22','1','15','satis','11','2025-10-09','2025-10-09','','10.00','0.00','0.00','0.00','0.00','','','2','2025-10-09 00:07:19','2.00','12.00'),
('23','1','14','alis','118','2025-10-09','2025-10-09','','100.00','0.00','0.00','0.00','0.00','','','2','2025-10-09 00:13:28','20.00','120.00'),
('24','1','15','satis','2','2025-10-09','0000-00-00','','60.00','0.00','0.00','0.00','0.00','','','2','2025-10-09 00:13:58','12.00','72.00'),
('25','2','16','alis','2','2025-10-09','2025-10-09','','100.00','0.00','0.00','0.00','0.00','','','5','2025-10-09 00:34:42','20.00','120.00'),
('26','2','16','satis','11','2025-10-09','2025-10-09','','525.00','0.00','0.00','0.00','0.00','','','5','2025-10-09 00:35:15','105.00','630.00');

-- Tablo yapısı: firmalar
DROP TABLE IF EXISTS `firmalar`;
CREATE TABLE `firmalar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_adi` varchar(200) NOT NULL,
  `vergi_dairesi` varchar(100) DEFAULT NULL,
  `vergi_no` varchar(20) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aktif` (`aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: firmalar
INSERT INTO `firmalar` VALUES
('1','EVİN DİZAYN SÜS BİTKİLERİ','ÖDEMİŞ','11111111111','05546585007','Süleyman Demirel mah şoförler sitesi küme evleri no1 B a2 blok d 3','onurakkalp@hotmail.com','1','2025-10-08 21:52:14','logo_1_1759958359.png'),
('2','VURUCU TİM AŞ','ÖDEMİŞ','11111111111','05546585007','Süleyman Demirel mah şoförler sitesi küme evleri no1 B a2 blok d 3','onurakkalp@hotmail.com','1','2025-10-09 00:31:41','logo_2_1759959197.png');

-- Tablo yapısı: kasa_hareketleri
DROP TABLE IF EXISTS `kasa_hareketleri`;
CREATE TABLE `kasa_hareketleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `hareket_tipi` enum('gelir','gider') NOT NULL,
  `kategori` varchar(100) NOT NULL COMMENT 'Maaş, Kira, Fatura, vs.',
  `tutar` decimal(15,2) NOT NULL,
  `odeme_yontemi` enum('nakit','banka','cek','kredi_karti') NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` text DEFAULT NULL,
  `fatura_id` int(11) DEFAULT NULL COMMENT 'Fatura ile ilişkili ise',
  `personel_id` int(11) DEFAULT NULL COMMENT 'Personel gideri ise',
  `kullanici_id` int(11) NOT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `islem_tipi` enum('gelir','gider') NOT NULL,
  `bakiye` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fatura_id` (`fatura_id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_tip` (`hareket_tipi`),
  KEY `idx_tarih` (`tarih`),
  KEY `fk_kasa_personel` (`personel_id`),
  KEY `idx_kasa_firma_tarih` (`firma_id`,`tarih`),
  KEY `idx_kasa_islem_tipi` (`islem_tipi`),
  KEY `idx_kasa_kategori` (`kategori`),
  KEY `idx_kasa_tarih` (`tarih`),
  CONSTRAINT `fk_kasa_personel` FOREIGN KEY (`personel_id`) REFERENCES `personel` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kasa_hareketleri_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kasa_hareketleri_ibfk_2` FOREIGN KEY (`fatura_id`) REFERENCES `faturalar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kasa_hareketleri_ibfk_3` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: kasa_hareketleri
INSERT INTO `kasa_hareketleri` VALUES
('1','1','gelir','Genel Ödeme','5.00','nakit','2025-10-08','Cari: Onur AKkalp - ',NULL,NULL,'2','2025-10-09 00:05:38','gider','-5.00'),
('2','1','gelir','Genel Ödeme','3.00','nakit','2025-10-08','Cari: Onur AKkalp - ',NULL,NULL,'2','2025-10-09 00:05:57','gider','-8.00'),
('5','1','gelir','Satış Tahsilatı','444.00','nakit','2025-10-09','',NULL,NULL,'2','2025-10-09 00:30:49','gelir','436.00'),
('6','2','gelir','Genel Ödeme','200.00','nakit','2025-10-08','Cari: Onur AKkalp - ',NULL,NULL,'5','2025-10-09 00:35:54','gider','-200.00'),
('7','2','gelir','Genel Tahsilat','500.00','nakit','2025-10-08','Cari: Onur AKkalp - ',NULL,NULL,'5','2025-10-09 00:36:04','gelir','300.00'),
('8','2','gelir','Genel Tahsilat','555.00','nakit','2025-10-08','Cari: Onur AKkalp - ',NULL,NULL,'5','2025-10-09 00:36:16','gelir','855.00'),
('9','2','gelir','Genel Ödeme','345.00','nakit','2025-10-08','Cari: Onur AKkalp - ',NULL,NULL,'5','2025-10-09 00:36:23','gider','510.00'),
('10','2','gelir','Diğer Gelir','1000.00','nakit','2025-10-09','',NULL,NULL,'5','2025-10-09 00:36:49','gelir','1510.00'),
('11','2','gelir','Diğer Gider','100.00','nakit','2025-10-09','',NULL,NULL,'5','2025-10-09 00:36:58','gider','1410.00'),
('12','2','gelir','Genel Tahsilat','3.00','nakit','2025-10-08','Cari: Onur AKkalp - e',NULL,NULL,'5','2025-10-09 00:40:41','gelir','1413.00');

-- Tablo yapısı: kullanici_yetkileri
DROP TABLE IF EXISTS `kullanici_yetkileri`;
CREATE TABLE `kullanici_yetkileri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `modul_id` int(11) NOT NULL,
  `okuma` tinyint(1) DEFAULT 0,
  `yazma` tinyint(1) DEFAULT 0,
  `guncelleme` tinyint(1) DEFAULT 0,
  `silme` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kullanici_modul` (`kullanici_id`,`modul_id`),
  KEY `modul_id` (`modul_id`),
  CONSTRAINT `kullanici_yetkileri_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kullanici_yetkileri_ibfk_2` FOREIGN KEY (`modul_id`) REFERENCES `moduller` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: kullanici_yetkileri
INSERT INTO `kullanici_yetkileri` VALUES
('1','4','1','1','1','1','0'),
('2','4','2','1','1','1','0'),
('3','4','3','1','1','1','0'),
('4','6','1','1','1','1','1'),
('5','6','2','1','1','1','1'),
('6','6','3','1','1','1','1'),
('7','6','6','1','0','0','0');

-- Tablo yapısı: kullanicilar
DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) DEFAULT NULL COMMENT 'NULL ise Super Admin',
  `kullanici_adi` varchar(50) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `ad_soyad` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `rol` enum('super_admin','firma_yoneticisi','kullanici') NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `fcm_token` text DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_rol` (`rol`),
  KEY `idx_kullanicilar_firma_id` (`firma_id`),
  KEY `idx_kullanicilar_rol` (`rol`),
  KEY `idx_kullanicilar_aktif` (`aktif`),
  KEY `idx_kullanicilar_kullanici_adi` (`kullanici_adi`),
  CONSTRAINT `fk_kullanici_firma` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: kullanicilar
INSERT INTO `kullanicilar` VALUES
('1',NULL,'admin','$2y$10$7GaJqsakVF5yrDEEY95fbO86FD6/XOZ2zKJ28PkbY.ZU.Gw80cgyu','Sistem Yöneticisi',NULL,NULL,'super_admin','1',NULL,'2025-10-08 21:00:22','2025-10-08 21:16:51'),
('2','1','oakkalp','$2y$10$T9cN7ws7sUwMNOMS4BJbLeL7a8FRbobmZRb6Bs/518xS.RaSW2r2i','Onur Akkalp','onurakkalp@hotmail.com','05546585007','firma_yoneticisi','1',NULL,'2025-10-08 21:52:35','2025-10-08 21:52:35'),
('4','1','onur','$2y$10$sQLjs4laFknLV.bgDyMSeusKnZZT8EXohKEca34mcYsVKucCsZtpq','Onur Akkalp','onurakkalp@hotmail.com','05546585007','kullanici','1',NULL,'2025-10-08 22:22:02','2025-10-08 22:22:02'),
('5','2','melih','$2y$10$RY/gwH8.YaW/amwskZaviuBEVQT12upXN0/9xFVHqJZFuKz2ReiPC','MELİH DALAR','onurakkalp@hotmail.com','05546585007','firma_yoneticisi','1','{\"endpoint\":\"https://fcm.googleapis.com/fcm/send/da3BMSmgHqI:APA91bHnGW3KP6OgX6AdOZ48k052je516UZgIj9PejZajeKUJMXyz7NhBWvQ_K23eeE0FFLlC4kFkGFSzOd4p_dzJFlXAdDFexL5uwxLuwRZGFcl16g36vhbBGEH5h7CkSMbHrPfg9S_\",\"expirationTime\":null,\"keys\":{\"p256dh\":\"BAyKABCkdnqjJMR-Ov3pzQ1CaZiuMSVE_v955yJxhxsVaaiH_odBvgiLTIjrMdkO20AyXbviEixwz6dD5pjpyXs\",\"auth\":\"8vY0EVmTeDEdXkewfK6X3w\"}}','2025-10-09 00:32:02','2025-10-09 02:01:39'),
('6','2','melih2','$2y$10$pqOShfxV/FJ89djTxvWYz.IUG6AOpDTgAx9GyGmOPe62FacENdBWy','Melih VURUCU','onurakkalp@hotmail.com','05546585007','kullanici','1',NULL,'2025-10-09 00:33:00','2025-10-09 00:33:00');

-- Tablo yapısı: moduller
DROP TABLE IF EXISTS `moduller`;
CREATE TABLE `moduller` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modul_kodu` varchar(50) NOT NULL,
  `modul_adi` varchar(100) NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `sira` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modul_kodu` (`modul_kodu`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: moduller
INSERT INTO `moduller` VALUES
('1','dashboard','Ana Panel','Özet bilgiler ve raporlar','1'),
('2','cariler','Cari Yönetimi','Müşteri ve tedarikçi yönetimi','2'),
('3','urunler','Stok Yönetimi','Ürün ve stok takibi','3'),
('4','faturalar','Faturalar','Alış ve satış faturaları','4'),
('5','odemeler','Ödemeler','Ödeme ve tahsilat işlemleri','5'),
('6','kasa','Kasa','Gelir ve gider yönetimi','6'),
('7','cekler','Çek Yönetimi','Çek takibi','7'),
('8','personel','Personel','Personel ve maaş yönetimi','8'),
('9','raporlar','Raporlar','Detaylı raporlar ve analizler','9'),
('10','ayarlar','Ayarlar','Firma ve kullanıcı ayarları','10');

-- Tablo yapısı: odemeler
DROP TABLE IF EXISTS `odemeler`;
CREATE TABLE `odemeler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `fatura_id` int(11) DEFAULT NULL COMMENT 'NULL ise fatura dışı ödeme',
  `cari_id` int(11) DEFAULT NULL,
  `odeme_tipi` enum('odeme','tahsilat') NOT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `odeme_yontemi` enum('nakit','banka','cek','kredi_karti') NOT NULL,
  `cek_id` int(11) DEFAULT NULL COMMENT 'Çek ile ödeme ise',
  `odeme_tarihi` date NOT NULL,
  `aciklama` text DEFAULT NULL,
  `kullanici_id` int(11) NOT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cari_id` (`cari_id`),
  KEY `kullanici_id` (`kullanici_id`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_fatura` (`fatura_id`),
  KEY `idx_tarih` (`odeme_tarihi`),
  CONSTRAINT `odemeler_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `odemeler_ibfk_2` FOREIGN KEY (`fatura_id`) REFERENCES `faturalar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `odemeler_ibfk_3` FOREIGN KEY (`cari_id`) REFERENCES `cariler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `odemeler_ibfk_4` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: odemeler
-- Tablo yapısı: personel
DROP TABLE IF EXISTS `personel`;
CREATE TABLE `personel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `ad_soyad` varchar(150) NOT NULL,
  `tc_no` varchar(11) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `gorev` varchar(100) DEFAULT NULL,
  `maas` decimal(15,2) DEFAULT 0.00,
  `ise_giris_tarihi` date DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_firma` (`firma_id`),
  CONSTRAINT `personel_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: personel
INSERT INTO `personel` VALUES
('3','1','onur','','3432432432','Süleyman Demirel mah şoförler sitesi küme evleri no1 B a2 blok d 3','','0.00','2025-10-01','1','2025-10-08 22:45:36'),
('4','1','Test Personel 1759953387720',NULL,'0555 123 4567',NULL,'Test','15000.00',NULL,'1','2025-10-08 22:56:27');

-- Tablo yapısı: urunler
DROP TABLE IF EXISTS `urunler`;
CREATE TABLE `urunler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_id` int(11) NOT NULL,
  `urun_kodu` varchar(50) DEFAULT NULL,
  `urun_adi` varchar(255) NOT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `birim` varchar(20) DEFAULT 'Adet',
  `stok_miktari` decimal(15,3) DEFAULT 0.000,
  `kritik_stok` decimal(15,3) DEFAULT 0.000,
  `alis_fiyati` decimal(15,2) DEFAULT 0.00 COMMENT 'Ortalama alış fiyatı',
  `satis_fiyati` decimal(15,2) DEFAULT 0.00,
  `kdv_orani` int(11) DEFAULT 20,
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_firma` (`firma_id`),
  KEY `idx_stok` (`stok_miktari`),
  KEY `idx_urunler_firma_id` (`firma_id`),
  KEY `idx_urunler_urun_kodu` (`urun_kodu`),
  KEY `idx_urunler_kategori` (`kategori`),
  KEY `idx_urunler_aktif` (`aktif`),
  KEY `idx_urunler_stok_miktari` (`stok_miktari`),
  CONSTRAINT `urunler_ibfk_1` FOREIGN KEY (`firma_id`) REFERENCES `firmalar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Tablo verileri: urunler
INSERT INTO `urunler` VALUES
('3','1',NULL,'Test Ürün 1759952651660',NULL,'Adet','10.000','0.000','50.00','100.00','20','1','2025-10-08 22:44:11'),
('4','1',NULL,'Test Ürün 1759953385493',NULL,'Adet','10.000','0.000','50.00','100.00','20','1','2025-10-08 22:56:25'),
('5','1',NULL,'Leylandi 2 M',NULL,'Adet','93.000','0.000','1.00','0.00','20','1','2025-10-08 23:04:08'),
('6','2',NULL,'Leylandi 2 M',NULL,'Adet','15.000','0.000','10.00','0.00','20','1','2025-10-09 00:34:32');

COMMIT;
