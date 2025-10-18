<?php
/**
 * Cariler tablosuna eksik kolonları ekle
 */

require_once 'config.php';

try {
    // cari_tipi kolonu var mı kontrol et
    $checkQuery = "SHOW COLUMNS FROM cariler LIKE 'cari_tipi'";
    $result = $db->query($checkQuery);
    
    if ($result->num_rows == 0) {
        // cari_tipi kolonu yok, ekle
        $alterQuery = "ALTER TABLE cariler ADD COLUMN cari_tipi ENUM('musteri', 'tedarikci') DEFAULT 'musteri' AFTER bakiye";
        if ($db->query($alterQuery)) {
            echo "✅ cari_tipi kolonu başarıyla eklendi\n";
        } else {
            echo "❌ cari_tipi kolonu eklenirken hata: " . $db->error . "\n";
        }
    } else {
        echo "✅ cari_tipi kolonu zaten mevcut\n";
    }
    
    // cari_kodu kolonu var mı kontrol et
    $checkQuery2 = "SHOW COLUMNS FROM cariler LIKE 'cari_kodu'";
    $result2 = $db->query($checkQuery2);
    
    if ($result2->num_rows == 0) {
        // cari_kodu kolonu yok, ekle
        $alterQuery2 = "ALTER TABLE cariler ADD COLUMN cari_kodu VARCHAR(20) AFTER firma_id";
        if ($db->query($alterQuery2)) {
            echo "✅ cari_kodu kolonu başarıyla eklendi\n";
        } else {
            echo "❌ cari_kodu kolonu eklenirken hata: " . $db->error . "\n";
        }
    } else {
        echo "✅ cari_kodu kolonu zaten mevcut\n";
    }
    
    // Mevcut cariler için otomatik kod oluştur
    $updateQuery = "UPDATE cariler SET cari_kodu = CONCAT('CAR', LPAD(id, 4, '0')) WHERE cari_kodu IS NULL OR cari_kodu = ''";
    if ($db->query($updateQuery)) {
        echo "✅ Mevcut cariler için otomatik kodlar oluşturuldu\n";
    } else {
        echo "❌ Otomatik kod oluşturulurken hata: " . $db->error . "\n";
    }
    
    echo "\n🎉 Cariler tablosu güncellemesi tamamlandı!\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
