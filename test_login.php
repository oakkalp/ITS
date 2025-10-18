<?php
// Test login API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Config'i yükle
require_once 'config.php';

// Flutter API base sınıfını yükle
require_once 'api/flutter/flutter_api.php';

echo "=== LOGIN API TEST ===\n";

// Test verileri
$test_data = [
    'kullanici_adi' => 'admin',
    'sifre' => 'admin'
];

echo "Test verileri: " . json_encode($test_data) . "\n";

// FlutterAuthAPI sınıfını test et
class TestAuthAPI extends FlutterAPI {
    public function testLogin($data) {
        try {
            // Kullanıcıyı bul
            $query = "SELECT id, firma_id, ad_soyad, kullanici_adi, sifre, rol, aktif, fcm_token FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $data['kullanici_adi']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Kullanıcı bulunamadı'];
            }
            
            $user = $result->fetch_assoc();
            
            // Şifre kontrolü
            if (!password_verify($data['sifre'], $user['sifre'])) {
                return ['success' => false, 'message' => 'Şifre hatalı'];
            }
            
            // JWT Token oluştur
            $token = $this->generateJWT($user['id'], $user['firma_id'], $user['rol']);
            
            return [
                'success' => true,
                'message' => 'Login başarılı',
                'data' => [
                    'user' => [
                        'id' => $user['id'],
                        'firma_id' => $user['firma_id'],
                        'ad_soyad' => $user['ad_soyad'],
                        'kullanici_adi' => $user['kullanici_adi'],
                        'rol' => $user['rol']
                    ],
                    'token' => $token
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Hata: ' . $e->getMessage()];
        }
    }
}

$test_api = new TestAuthAPI();
$result = $test_api->testLogin($test_data);

echo "Sonuç: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

// JWT Token doğrulama testi
if ($result['success'] && isset($result['data']['token'])) {
    $token = $result['data']['token'];
    echo "\n=== JWT TOKEN DOĞRULAMA TEST ===\n";
    echo "Token: " . substr($token, 0, 50) . "...\n";
    
    $validated = $test_api->validateJWT($token);
    if ($validated) {
        echo "✅ JWT Token doğrulandı: " . json_encode($validated) . "\n";
    } else {
        echo "❌ JWT Token doğrulanamadı\n";
    }
}

echo "\n=== TEST TAMAMLANDI ===\n";
?>
