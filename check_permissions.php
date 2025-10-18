<?php
// Uzak sunucudaki yetki sistemi kontrolü
require_once 'config.php';

echo "=== YETKİ SİSTEMİ KONTROLÜ ===\n";

// Modüller tablosunu kontrol et
$result = mysqli_query($db, "SHOW TABLES LIKE 'moduller'");
if (mysqli_num_rows($result) == 0) {
    echo "❌ moduller tablosu yok, oluşturuluyor...\n";
    
    $create_moduller = "CREATE TABLE IF NOT EXISTS `moduller` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `modul_kodu` varchar(50) NOT NULL,
        `modul_adi` varchar(100) NOT NULL,
        `sira` int(11) DEFAULT 0,
        `aktif` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `modul_kodu` (`modul_kodu`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (mysqli_query($db, $create_moduller)) {
        echo "✅ moduller tablosu oluşturuldu\n";
        
        // Varsayılan modülleri ekle
        $moduller = [
            ['cariler', 'Cariler', 1],
            ['stok', 'Stok', 2],
            ['faturalar', 'Faturalar', 3],
            ['cekler', 'Çekler', 4],
            ['kasa', 'Kasa', 5],
            ['odemeler', 'Ödemeler', 6],
            ['teklifler', 'Teklifler', 7],
            ['personel', 'Personel', 8],
            ['raporlar', 'Raporlar', 9],
            ['kullanicilar', 'Kullanıcılar', 10],
            ['firma_ayarlari', 'Firma Ayarları', 11],
        ];
        
        foreach ($moduller as $modul) {
            $insert = "INSERT INTO moduller (modul_kodu, modul_adi, sira) VALUES ('{$modul[0]}', '{$modul[1]}', {$modul[2]})";
            mysqli_query($db, $insert);
        }
        echo "✅ Varsayılan modüller eklendi\n";
    } else {
        echo "❌ moduller tablosu oluşturulamadı: " . mysqli_error($db) . "\n";
    }
} else {
    echo "✅ moduller tablosu mevcut\n";
}

// Kullanıcı yetkileri tablosunu kontrol et
$result = mysqli_query($db, "SHOW TABLES LIKE 'kullanici_yetkileri'");
if (mysqli_num_rows($result) == 0) {
    echo "❌ kullanici_yetkileri tablosu yok, oluşturuluyor...\n";
    
    $create_yetkiler = "CREATE TABLE IF NOT EXISTS `kullanici_yetkileri` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `kullanici_id` int(11) NOT NULL,
        `modul_id` int(11) NOT NULL,
        `okuma` tinyint(1) DEFAULT 0,
        `yazma` tinyint(1) DEFAULT 0,
        `guncelleme` tinyint(1) DEFAULT 0,
        `silme` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `kullanici_modul` (`kullanici_id`, `modul_id`),
        KEY `kullanici_id` (`kullanici_id`),
        KEY `modul_id` (`modul_id`),
        FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`modul_id`) REFERENCES `moduller` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (mysqli_query($db, $create_yetkiler)) {
        echo "✅ kullanici_yetkileri tablosu oluşturuldu\n";
    } else {
        echo "❌ kullanici_yetkileri tablosu oluşturulamadı: " . mysqli_error($db) . "\n";
    }
} else {
    echo "✅ kullanici_yetkileri tablosu mevcut\n";
}

echo "\n=== KULLANICI ROL KONTROLÜ ===\n";
$result = mysqli_query($db, 'SELECT DISTINCT rol FROM kullanicilar');
while($row = mysqli_fetch_assoc($result)) {
    echo "- Rol: " . $row['rol'] . "\n";
}

echo "\n=== ÖRNEK KULLANICILAR ===\n";
$result = mysqli_query($db, 'SELECT id, kullanici_adi, rol, firma_id FROM kullanicilar LIMIT 5');
while($row = mysqli_fetch_assoc($result)) {
    echo "- ID: {$row['id']}, Kullanıcı: {$row['kullanici_adi']}, Rol: {$row['rol']}, Firma: {$row['firma_id']}\n";
}

echo "\n=== MODÜLLER ===\n";
$result = mysqli_query($db, 'SELECT id, modul_kodu, modul_adi FROM moduller ORDER BY sira');
while($row = mysqli_fetch_assoc($result)) {
    echo "- ID: {$row['id']}, Kod: {$row['modul_kodu']}, Ad: {$row['modul_adi']}\n";
}

echo "\n=== YETKİLER ===\n";
$result = mysqli_query($db, 'SELECT ky.kullanici_id, k.kullanici_adi, ky.modul_id, m.modul_adi, ky.okuma FROM kullanici_yetkileri ky JOIN kullanicilar k ON ky.kullanici_id = k.id JOIN moduller m ON ky.modul_id = m.id LIMIT 10');
while($row = mysqli_fetch_assoc($result)) {
    echo "- {$row['kullanici_adi']} -> {$row['modul_adi']}: Okuma=" . ($row['okuma'] ? 'Evet' : 'Hayır') . "\n";
}

echo "\n=== KONTROL TAMAMLANDI ===\n";
?>



