<?php
// Uzak sunucudaki veritabanı içeriğini kontrol et
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

echo "=== UZAK SUNUCU VERİTABANI KONTROL ===\n";

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        echo "❌ Database connection failed: " . $db->connect_error . "\n";
        exit;
    }
    
    echo "✅ Database connection successful\n";
    
    // Kullanıcıları listele
    echo "\n=== KULLANICILAR ===\n";
    $query = "SELECT id, kullanici_adi, ad_soyad, rol, aktif, firma_id FROM kullanicilar ORDER BY id";
    $result = $db->query($query);
    
    if ($result) {
        echo "✅ Query successful\n";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Kullanıcı: {$row['kullanici_adi']}, Ad: {$row['ad_soyad']}, Rol: {$row['rol']}, Aktif: {$row['aktif']}, Firma: {$row['firma_id']}\n";
        }
    } else {
        echo "❌ Query failed: " . $db->error . "\n";
    }
    
    // Firmaları listele
    echo "\n=== FİRMALAR ===\n";
    $query = "SELECT id, firma_adi, yetkili_kisi, aktif FROM firmalar ORDER BY id";
    $result = $db->query($query);
    
    if ($result) {
        echo "✅ Query successful\n";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Firma: {$row['firma_adi']}, Yetkili: {$row['yetkili_kisi']}, Aktif: {$row['aktif']}\n";
        }
    } else {
        echo "❌ Query failed: " . $db->error . "\n";
    }
    
    // Cariler tablosunu kontrol et
    echo "\n=== CARİLER TABLOSU ===\n";
    $query = "SHOW TABLES LIKE 'cariler'";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Cariler tablosu mevcut\n";
        
        // Cariler tablosundaki kayıtları say
        $count_query = "SELECT COUNT(*) as count FROM cariler";
        $count_result = $db->query($count_query);
        if ($count_result) {
            $count = $count_result->fetch_assoc()['count'];
            echo "📊 Toplam cari sayısı: {$count}\n";
        }
    } else {
        echo "❌ Cariler tablosu bulunamadı\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== KONTROL TAMAMLANDI ===\n";
?>
