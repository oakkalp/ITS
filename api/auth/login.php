<?php
/**
 * Login API
 */

// Hata gösterimi
error_reporting(E_ALL);
ini_set('display_errors', 0);

// JSON header
header('Content-Type: application/json; charset=utf-8');

try {
    // Config ve Auth yükle
    require_once '../../config.php';
    require_once '../../includes/auth.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_error('Sadece POST istekleri kabul edilir', 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $kullanici_adi = $data['kullanici_adi'] ?? '';
    $sifre = $data['sifre'] ?? '';

    if (empty($kullanici_adi) || empty($sifre)) {
        json_error('Kullanıcı adı ve şifre gerekli!', 400);
    }

    // Login işlemi
    $result = login_user($kullanici_adi, $sifre);

    if ($result['success']) {
        // JWT token için user bilgilerini hazırla
        $user_for_jwt = [
            'id' => $result['user']['id'],
            'firma_id' => $result['user']['firma_id'],
            'ad_soyad' => $result['user']['ad_soyad'],
            'firma_adi' => $result['user']['firma_adi'],
            'rol' => $result['user']['rol']
        ];
        
        // Debug log
        error_log("User for JWT: " . json_encode($user_for_jwt));
        
        // JWT token oluştur
        $token = generate_jwt_token($user_for_jwt);
        
        // Debug log
        error_log("Generated Token: " . $token);
        
        json_success($result['message'], [
            'user' => $result['user'],
            'token' => $token,
            'expires_in' => 86400 // 24 saat
        ]);
    } else {
        json_error($result['message'], 401);
    }
    
} catch (Exception $e) {
    json_error('Sistem hatası: ' . $e->getMessage(), 500);
}
?>

