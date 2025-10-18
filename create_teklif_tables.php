<?php
require_once 'config.php';

echo "<h2>Teklif Tabloları Oluşturma</h2>";

try {
    // Teklifler tablosu
    $sql = "CREATE TABLE IF NOT EXISTS teklifler (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firma_id INT NOT NULL,
        teklif_no VARCHAR(50) NOT NULL,
        teklif_basligi VARCHAR(255) NOT NULL,
        teklif_tarihi DATE NOT NULL,
        gecerlilik_tarihi DATE NOT NULL,
        cari_id INT NULL,
        cari_disi_kisi VARCHAR(255) NULL,
        cari_disi_adres TEXT NULL,
        cari_disi_telefon VARCHAR(50) NULL,
        cari_disi_email VARCHAR(100) NULL,
        ara_toplam DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        kdv_tutari DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        genel_toplam DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        aciklama TEXT NULL,
        kullanici_id INT NOT NULL,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (firma_id) REFERENCES firmalar(id) ON DELETE CASCADE,
        FOREIGN KEY (cari_id) REFERENCES cariler(id) ON DELETE SET NULL,
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
    )";
    
    if ($db->query($sql)) {
        echo "<p style='color: green;'>✓ teklifler tablosu oluşturuldu</p>";
    } else {
        echo "<p style='color: red;'>✗ teklifler tablosu oluşturulamadı: " . $db->error . "</p>";
    }
    
    // Teklif detayları tablosu
    $sql = "CREATE TABLE IF NOT EXISTS teklif_detaylari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teklif_id INT NOT NULL,
        urun_id INT NULL,
        aciklama VARCHAR(255) NULL,
        miktar DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        birim VARCHAR(50) NULL,
        birim_fiyat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        kdv_orani DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        kdv_tutari DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        toplam DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (teklif_id) REFERENCES teklifler(id) ON DELETE CASCADE,
        FOREIGN KEY (urun_id) REFERENCES urunler(id) ON DELETE SET NULL
    )";
    
    if ($db->query($sql)) {
        echo "<p style='color: green;'>✓ teklif_detaylari tablosu oluşturuldu</p>";
    } else {
        echo "<p style='color: red;'>✗ teklif_detaylari tablosu oluşturulamadı: " . $db->error . "</p>";
    }
    
    // Örnek teklif verisi ekle
    $firma_id = 1; // Varsayılan firma ID
    
    // Mevcut kullanıcıları kontrol et
    $kullanici_check = $db->query("SELECT id FROM kullanicilar WHERE firma_id = $firma_id LIMIT 1");
    if ($kullanici_check->num_rows > 0) {
        $kullanici_id = $kullanici_check->fetch_assoc()['id'];
    } else {
        echo "<p style='color: red;'>✗ Firma için kullanıcı bulunamadı</p>";
        exit;
    }
    
    // Önce teklif ekle
    $sql = "INSERT INTO teklifler (
        firma_id, teklif_no, teklif_basligi, teklif_tarihi, gecerlilik_tarihi,
        cari_id, ara_toplam, kdv_tutari, genel_toplam, kullanici_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $teklif_no = 'TKL-001';
    $teklif_basligi = 'Test Teklifi';
    $teklif_tarihi = date('Y-m-d');
    $gecerlilik_tarihi = date('Y-m-d', strtotime('+30 days'));
    
    // Mevcut carileri kontrol et
    $cari_check = $db->query("SELECT id FROM cariler WHERE firma_id = $firma_id LIMIT 1");
    if ($cari_check->num_rows > 0) {
        $cari_id = $cari_check->fetch_assoc()['id'];
    } else {
        echo "<p style='color: red;'>✗ Firma için cari bulunamadı</p>";
        exit;
    }
    $ara_toplam = 1000.00;
    $kdv_tutari = 180.00;
    $genel_toplam = 1180.00;
    
    $stmt->bind_param("issssidddi", $firma_id, $teklif_no, $teklif_basligi, $teklif_tarihi, $gecerlilik_tarihi, $cari_id, $ara_toplam, $kdv_tutari, $genel_toplam, $kullanici_id);
    
    if ($stmt->execute()) {
        $teklif_id = $db->insert_id;
        echo "<p style='color: green;'>✓ Örnek teklif eklendi (ID: $teklif_id)</p>";
        
        // Teklif detayı ekle
        $sql = "INSERT INTO teklif_detaylari (
            teklif_id, urun_id, miktar, birim, birim_fiyat, kdv_orani, kdv_tutari, toplam
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        
        // Mevcut ürünleri kontrol et
        $urun_check = $db->query("SELECT id FROM urunler WHERE firma_id = $firma_id LIMIT 1");
        if ($urun_check->num_rows > 0) {
            $urun_id = $urun_check->fetch_assoc()['id'];
        } else {
            echo "<p style='color: red;'>✗ Firma için ürün bulunamadı</p>";
            exit;
        }
        $miktar = 2.00;
        $birim = 'adet';
        $birim_fiyat = 500.00;
        $kdv_orani = 18.00;
        $kdv_tutari = 180.00;
        $toplam = 1180.00;
        
        $stmt->bind_param("iidssddd", $teklif_id, $urun_id, $miktar, $birim, $birim_fiyat, $kdv_orani, $kdv_tutari, $toplam);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Örnek teklif detayı eklendi</p>";
        } else {
            echo "<p style='color: red;'>✗ Örnek teklif detayı eklenemedi: " . $stmt->error . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Örnek teklif eklenemedi: " . $stmt->error . "</p>";
    }
    
    echo "<h3>Sonuç:</h3>";
    echo "<p>Tablolar oluşturuldu ve örnek veriler eklendi.</p>";
    echo "<p><a href='modules/teklifler/list.php'>Teklifler sayfasına git</a></p>";
    echo "<p><a href='api/teklifler/pdf_real.php?id=1&debug=1'>PDF debug test</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
