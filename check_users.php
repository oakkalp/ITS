<?php
// Kullanıcıları kontrol et
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

echo "=== KULLANICI KONTROL ===\n";

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        echo "❌ Database connection failed: " . $db->connect_error . "\n";
        exit;
    }
    
    echo "✅ Database connection successful\n";
    
    // Kullanıcıları listele
    $query = "SELECT id, kullanici_adi, ad_soyad, rol, aktif FROM kullanicilar ORDER BY id";
    $result = $db->query($query);
    
    if ($result) {
        echo "✅ Query successful\n";
        echo "Kullanıcılar:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Kullanıcı: {$row['kullanici_adi']}, Ad: {$row['ad_soyad']}, Rol: {$row['rol']}, Aktif: {$row['aktif']}\n";
        }
    } else {
        echo "❌ Query failed: " . $db->error . "\n";
    }
    
    // Admin kullanıcısını kontrol et
    echo "\n=== ADMIN KULLANICI KONTROL ===\n";
    $query = "SELECT id, kullanici_adi, ad_soyad, rol, aktif FROM kullanicilar WHERE kullanici_adi = 'admin'";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "✅ Admin kullanıcı bulundu:\n";
        echo "- ID: {$admin['id']}\n";
        echo "- Kullanıcı: {$admin['kullanici_adi']}\n";
        echo "- Ad: {$admin['ad_soyad']}\n";
        echo "- Rol: {$admin['rol']}\n";
        echo "- Aktif: {$admin['aktif']}\n";
    } else {
        echo "❌ Admin kullanıcı bulunamadı\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST TAMAMLANDI ===\n";
?>
