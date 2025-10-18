<?php
// Veritabanı senkronizasyon scripti
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

echo "=== VERİTABANI SENKRONİZASYON ===\n";

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        echo "❌ Database connection failed: " . $db->connect_error . "\n";
        exit;
    }
    
    echo "✅ Database connection successful\n";
    
    // Eksik kullanıcıları oluştur
    $users_to_create = [
        [
            'firma_id' => 2,
            'kullanici_adi' => 'firma_yoneticisi',
            'sifre' => password_hash('123456', PASSWORD_DEFAULT),
            'ad_soyad' => 'Firma Yöneticisi',
            'rol' => 'firma_yoneticisi',
            'aktif' => 1
        ],
        [
            'firma_id' => 2,
            'kullanici_adi' => 'normal_kullanici',
            'sifre' => password_hash('123456', PASSWORD_DEFAULT),
            'ad_soyad' => 'Normal Kullanıcı',
            'rol' => 'kullanici',
            'aktif' => 1
        ]
    ];
    
    foreach ($users_to_create as $user) {
        // Kullanıcı var mı kontrol et
        $check_query = "SELECT id FROM kullanicilar WHERE kullanici_adi = ? AND firma_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bind_param("si", $user['kullanici_adi'], $user['firma_id']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Kullanıcıyı oluştur
            $insert_query = "INSERT INTO kullanicilar (firma_id, kullanici_adi, sifre, ad_soyad, rol, aktif) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bind_param("issssi", $user['firma_id'], $user['kullanici_adi'], $user['sifre'], $user['ad_soyad'], $user['rol'], $user['aktif']);
            
            if ($insert_stmt->execute()) {
                echo "✅ Kullanıcı oluşturuldu: {$user['kullanici_adi']} (ID: {$db->insert_id})\n";
            } else {
                echo "❌ Kullanıcı oluşturulamadı: {$user['kullanici_adi']} - {$insert_stmt->error}\n";
            }
        } else {
            echo "ℹ️ Kullanıcı zaten var: {$user['kullanici_adi']}\n";
        }
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== SENKRONİZASYON TAMAMLANDI ===\n";
?>
