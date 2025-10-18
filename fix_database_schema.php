<?php
require_once 'config.php';

echo "<h2>Veritabanı Şema Düzeltme</h2>";

try {
    // Ürünler tablosuna stok kolonu ekle
    $query = "ALTER TABLE urunler ADD COLUMN IF NOT EXISTS stok DECIMAL(10,2) DEFAULT 0";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Ürünler tablosuna stok kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Stok kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    // Kasa tablosuna islem_tipi kolonu ekle
    $query = "ALTER TABLE kasa ADD COLUMN IF NOT EXISTS islem_tipi ENUM('giris', 'cikis') DEFAULT 'giris'";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Kasa tablosuna islem_tipi kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ İşlem tipi kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    // Çekler tablosuna banka kolonu ekle
    $query = "ALTER TABLE cekler ADD COLUMN IF NOT EXISTS banka VARCHAR(100) DEFAULT ''";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Çekler tablosuna banka kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Banka kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    // Faturalar tablosuna toplam_tutar kolonu ekle
    $query = "ALTER TABLE faturalar ADD COLUMN IF NOT EXISTS toplam_tutar DECIMAL(10,2) DEFAULT 0";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Faturalar tablosuna toplam_tutar kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Toplam tutar kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    // Teklifler tablosunu kontrol et ve eksik kolonları ekle
    $query = "ALTER TABLE teklifler ADD COLUMN IF NOT EXISTS birim VARCHAR(50) DEFAULT 'adet'";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Teklifler tablosuna birim kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Birim kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    // Kullanıcılar tablosuna eksik kolonları ekle
    $query = "ALTER TABLE kullanicilar ADD COLUMN IF NOT EXISTS fcm_token TEXT DEFAULT NULL";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Kullanıcılar tablosuna fcm_token kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ FCM token kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    $query = "ALTER TABLE kullanicilar ADD COLUMN IF NOT EXISTS olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Kullanıcılar tablosuna olusturma_tarihi kolonu eklendi</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Oluşturma tarihi kolonu zaten mevcut veya eklenemedi: " . $db->error . "</p>";
    }
    
    // Modüller tablosunu oluştur
    $query = "CREATE TABLE IF NOT EXISTS moduller (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modul_adi VARCHAR(100) NOT NULL,
        modul_aciklamasi TEXT,
        aktif TINYINT(1) DEFAULT 1,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Modüller tablosu oluşturuldu/kontrol edildi</p>";
        
        // Varsayılan modülleri ekle
        $moduller = [
            ['Cariler', 'Cari hesap yönetimi'],
            ['Faturalar', 'Fatura işlemleri'],
            ['Stok', 'Stok yönetimi'],
            ['Çekler', 'Çek takibi'],
            ['Kasa', 'Kasa işlemleri'],
            ['Ödemeler', 'Ödeme takibi'],
            ['Raporlar', 'Raporlar'],
            ['Teklifler', 'Teklif yönetimi']
        ];
        
        foreach ($moduller as $modul) {
            $check_query = "SELECT id FROM moduller WHERE modul_adi = ?";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param("s", $modul[0]);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $insert_query = "INSERT INTO moduller (modul_adi, modul_aciklamasi) VALUES (?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bind_param("ss", $modul[0], $modul[1]);
                $insert_stmt->execute();
                echo "<p style='color: blue;'>✓ Modül eklendi: " . $modul[0] . "</p>";
            }
        }
    }
    
    // Kullanıcı yetkileri tablosunu oluştur
    $query = "CREATE TABLE IF NOT EXISTS kullanici_yetkileri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NOT NULL,
        modul_id INT NOT NULL,
        okuma TINYINT(1) DEFAULT 1,
        yazma TINYINT(1) DEFAULT 0,
        guncelleme TINYINT(1) DEFAULT 0,
        silme TINYINT(1) DEFAULT 0,
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
        FOREIGN KEY (modul_id) REFERENCES moduller(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_module (kullanici_id, modul_id)
    )";
    if ($db->query($query)) {
        echo "<p style='color: green;'>✓ Kullanıcı yetkileri tablosu oluşturuldu/kontrol edildi</p>";
    }
    
    // Admin kullanıcısı için varsayılan yetkileri ekle
    $admin_query = "SELECT id FROM kullanicilar WHERE kullanici_adi = 'admin' LIMIT 1";
    $admin_result = $db->query($admin_query);
    if ($admin_result && $admin_result->num_rows > 0) {
        $admin = $admin_result->fetch_assoc();
        $admin_id = $admin['id'];
        
        $modul_query = "SELECT id FROM moduller";
        $modul_result = $db->query($modul_query);
        
        while ($modul = $modul_result->fetch_assoc()) {
            $check_yetki = "SELECT id FROM kullanici_yetkileri WHERE kullanici_id = ? AND modul_id = ?";
            $stmt = $db->prepare($check_yetki);
            $stmt->bind_param("ii", $admin_id, $modul['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $insert_yetki = "INSERT INTO kullanici_yetkileri (kullanici_id, modul_id, okuma, yazma, guncelleme, silme) VALUES (?, ?, 1, 1, 1, 1)";
                $insert_stmt = $db->prepare($insert_yetki);
                $insert_stmt->bind_param("ii", $admin_id, $modul['id']);
                $insert_stmt->execute();
                echo "<p style='color: blue;'>✓ Admin yetkisi eklendi: Modül ID " . $modul['id'] . "</p>";
            }
        }
    }
    
    echo "<h3>Veritabanı şema düzeltme tamamlandı!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
