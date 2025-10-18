<?php
// Test database connection
header('Content-Type: application/json');

// Config'i yükle
require_once 'config.php';

echo "=== DATABASE CONNECTION TEST ===\n";

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        echo "❌ Database connection failed: " . $db->connect_error . "\n";
        exit;
    }
    
    echo "✅ Database connection successful\n";
    
    // Kullanıcıları listele
    $query = "SELECT id, kullanici_adi, ad_soyad, rol, aktif FROM kullanicilar LIMIT 5";
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
    
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST TAMAMLANDI ===\n";
?>
