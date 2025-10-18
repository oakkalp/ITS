<?php
// Error reporting'i aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Config'i yükle
require_once '../../config.php';

// Flutter API base sınıfını yükle
require_once 'flutter_api.php';

/**
 * Flutter Authentication API
 * Login, logout, token refresh işlemleri
 */

class FlutterAuthAPI extends FlutterAPI {
    
    /**
     * POST /api/flutter/auth/login
     * Kullanıcı girişi
     */
    public function login() {
        error_log("Flutter Auth API - Login request received");
        error_log("Flutter Auth API - Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("Flutter Auth API - Input: " . file_get_contents('php://input'));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Flutter Auth API - Decoded input: " . print_r($input, true));
        
        // Input validation
        $this->validateInput($input, [
            'kullanici_adi' => 'required',
            'sifre' => 'required'
        ]);
        
        $kullanici_adi = $input['kullanici_adi'];
        $password = $input['sifre'];
        
        // Kullanıcıyı bul (kullanici_adi ile)
        $query = "SELECT id, firma_id, ad_soyad, kullanici_adi, sifre, rol, aktif, fcm_token FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1";
        $stmt = $this->executeQuery($query, [$kullanici_adi]);
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Admin kullanıcısı yoksa oluştur
            if ($kullanici_adi === 'admin' && $password === 'admin') {
                createAdminUser($this->db);
                // Tekrar sorgula
                $stmt = $this->executeQuery($query, [$kullanici_adi]);
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    $this->sendError('Admin kullanıcı oluşturulamadı', 500);
                }
            } else {
                $this->sendError('Kullanıcı adı veya şifre hatalı', 401);
            }
        }
        
        $user = $result->fetch_assoc();
        
        // Şifre kontrolü
        if (!password_verify($password, $user['sifre'])) {
            $this->sendError('Kullanıcı adı veya şifre hatalı', 401);
        }
        
        // JWT Token oluştur
        $token = $this->generateJWT($user['id'], $user['firma_id'], $user['rol']);
        
        // FCM Token güncelle (varsa)
        $fcm_token = $input['fcm_token'] ?? null;
        if ($fcm_token) {
            $update_query = "UPDATE kullanicilar SET fcm_token = ? WHERE id = ?";
            $this->executeQuery($update_query, [$fcm_token, $user['id']]);
        }
        
        // Son giriş tarihini güncelle (kolon yoksa atla)
        // $update_query = "UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?";
        // $this->executeQuery($update_query, [$user['id']]);
        
        // Kullanıcı bilgilerini hazırla
        $user_data = [
            'id' => $user['id'],
            'firma_id' => $user['firma_id'],
            'ad_soyad' => $user['ad_soyad'],
            'kullanici_adi' => $user['kullanici_adi'],
            'rol' => $user['rol'],
            'fcm_token' => $user['fcm_token']
        ];
        
        // Firma bilgilerini al
        $firma_query = "SELECT id, firma_adi, logo FROM firmalar WHERE id = ?";
        $firma_stmt = $this->executeQuery($firma_query, [$user['firma_id']]);
        $firma = $firma_stmt->get_result()->fetch_assoc();
        
        $this->sendSuccess([
            'user' => $user_data,
            'firma' => $firma,
            'token' => $token,
            'expires_in' => JWT_EXPIRY
        ], 'Giriş başarılı');
    }
    
    /**
     * POST /api/flutter/auth/logout
     * Kullanıcı çıkışı
     */
    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        
        // FCM Token'ı temizle
        $query = "UPDATE kullanicilar SET fcm_token = NULL WHERE id = ?";
        $this->executeQuery($query, [$payload['user_id']]);
        
        $this->sendSuccess(null, 'Çıkış başarılı');
    }
    
    /**
     * POST /api/flutter/auth/refresh
     * Token yenileme
     */
    public function refresh() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        
        // Yeni token oluştur
        $new_token = $this->generateJWT($payload['user_id'], $payload['firma_id'], $payload['rol']);
        
        $this->sendSuccess([
            'token' => $new_token,
            'expires_in' => JWT_EXPIRY
        ], 'Token yenilendi');
    }
    
    /**
     * GET /api/flutter/auth/profile
     * Kullanıcı profil bilgileri
     */
    public function getProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        
        // Kullanıcı bilgilerini al
        $query = "SELECT id, firma_id, ad_soyad, kullanici_adi, rol, aktif, olusturma_tarihi FROM kullanicilar WHERE id = ?";
        $stmt = $this->executeQuery($query, [$payload['user_id']]);
        $user = $stmt->get_result()->fetch_assoc();
        
        // Firma bilgilerini al
        $firma_query = "SELECT id, firma_adi, logo FROM firmalar WHERE id = ?";
        $firma_stmt = $this->executeQuery($firma_query, [$user['firma_id']]);
        $firma = $firma_stmt->get_result()->fetch_assoc();
        
        // Kullanıcı yetkilerini al
        $yetki_query = "SELECT m.modul_adi, ky.okuma, ky.yazma, ky.guncelleme, ky.silme 
                        FROM kullanici_yetkileri ky 
                        JOIN moduller m ON ky.modul_id = m.id 
                        WHERE ky.kullanici_id = ?";
        $yetki_stmt = $this->executeQuery($yetki_query, [$payload['user_id']]);
        $yetkiler = $yetki_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $this->sendSuccess([
            'user' => $user,
            'firma' => $firma,
            'yetkiler' => $yetkiler
        ], 'Profil bilgileri alındı');
    }
    
    /**
     * PUT /api/flutter/auth/profile
     * Profil güncelleme
     */
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Güncellenebilir alanlar
        $allowed_fields = ['ad_soyad', 'sifre', 'yeni_sifre'];
        $update_fields = [];
        $update_values = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field]) && !empty($input[$field])) {
                if ($field === 'sifre') {
                    // Mevcut şifre kontrolü
                    $user_query = "SELECT sifre FROM kullanicilar WHERE id = ?";
                    $user_stmt = $this->executeQuery($user_query, [$payload['user_id']]);
                    $user = $user_stmt->get_result()->fetch_assoc();
                    
                    if (!password_verify($input[$field], $user['sifre'])) {
                        $this->sendError('Mevcut şifre hatalı');
                    }
                    
                    // Yeni şifre varsa onu kullan
                    if (isset($input['yeni_sifre']) && !empty($input['yeni_sifre'])) {
                        $update_fields[] = "sifre = ?";
                        $update_values[] = password_hash($input['yeni_sifre'], PASSWORD_DEFAULT);
                    }
                } elseif ($field === 'yeni_sifre') {
                    // Bu alan zaten sifre kontrolünde işlendi
                    continue;
                } else {
                    $update_fields[] = "$field = ?";
                    $update_values[] = $input[$field];
                }
            }
        }
        
        if (empty($update_fields)) {
            $this->sendError('Güncellenecek alan bulunamadı');
        }
        
        $update_values[] = $payload['user_id'];
        
        $query = "UPDATE kullanicilar SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $this->executeQuery($query, $update_values);
        
        $this->sendSuccess(null, 'Profil güncellendi');
    }
    
    /**
     * POST /api/flutter/auth/fcm-token
     * FCM Token güncelleme
     */
    public function updateFCMToken() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $payload = $this->authenticateUser();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $this->validateInput($input, [
            'fcm_token' => 'required'
        ]);
        
        $query = "UPDATE kullanicilar SET fcm_token = ? WHERE id = ?";
        $this->executeQuery($query, [$input['fcm_token'], $payload['user_id']]);
        
        $this->sendSuccess(null, 'FCM Token güncellendi');
    }
}

// API endpoint routing
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = '';

// URL'den path'i çıkar
if (preg_match('/\/api\/flutter\/auth\.php\/(.*)$/', $request_uri, $matches)) {
    $path = '/' . $matches[1];
} elseif (preg_match('/\/api\/flutter\/auth\/(.*)$/', $request_uri, $matches)) {
    $path = '/' . $matches[1];
} elseif (strpos($request_uri, '/api/flutter/auth.php') !== false) {
    // Direkt auth.php çağrısı için login endpoint'i varsayılan
    $path = '/login';
}

$auth = new FlutterAuthAPI();

switch ($path) {
    case '/login':
        $auth->login();
        break;
        
    case '/logout':
        $auth->logout();
        break;
        
    case '/refresh':
        $auth->refresh();
        break;
        
    case '/profile':
        if ($method === 'GET') {
            $auth->profile();
        } elseif ($method === 'PUT') {
            $auth->updateProfile();
        } else {
            $auth->sendError('Method not allowed', 405);
        }
        break;
        
    case '/fcm-token':
        $auth->updateFCMToken();
        break;
        
    default:
        // Eğer path bulunamazsa ve POST ise login varsayılan
        if ($method === 'POST' && empty($path)) {
            $auth->login();
        } else {
            $auth->sendError('Endpoint bulunamadı: ' . $path, 404);
        }
}

/**
 * Admin kullanıcısı oluştur
 */
function createAdminUser($db) {
    try {
        // Önce firma oluştur
        $firma_query = "INSERT INTO firmalar (firma_adi, yetkili_kisi, telefon, email, adres, aktif) VALUES (?, ?, ?, ?, ?, ?)";
        $firma_stmt = $db->prepare($firma_query);
        $firma_name = 'Admin Firma';
        $firma_person = 'Admin Yetkili';
        $firma_phone = '0000000000';
        $firma_email = 'admin@admin.com';
        $firma_address = 'Admin Adres';
        $firma_active = 1;
        
        $firma_stmt->bind_param("sssssi", $firma_name, $firma_person, $firma_phone, $firma_email, $firma_address, $firma_active);
        $firma_stmt->execute();
        $firma_id = $db->insert_id;
        
        // Admin kullanıcısı oluştur
        $user_query = "INSERT INTO kullanicilar (firma_id, kullanici_adi, sifre, ad_soyad, rol, aktif) VALUES (?, ?, ?, ?, ?, ?)";
        $user_stmt = $db->prepare($user_query);
        $username = 'admin';
        $password_hash = password_hash('admin', PASSWORD_DEFAULT);
        $fullname = 'Admin User';
        $role = 'super_admin';
        $active = 1;
        
        $user_stmt->bind_param("issssi", $firma_id, $username, $password_hash, $fullname, $role, $active);
        $user_stmt->execute();
        
        file_put_contents('debug_auth.txt', "Admin user created: ID=" . $db->insert_id . ", Firma ID=" . $firma_id . "\n", FILE_APPEND);
        
    } catch (Exception $e) {
        file_put_contents('debug_auth.txt', "Admin user creation failed: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// API endpoint routing
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = '';

// URL'den path'i çıkar
if (preg_match('/\/api\/flutter\/auth\.php\/(.*)$/', $request_uri, $matches)) {
    $path = '/' . $matches[1];
} elseif (strpos($request_uri, '/api/flutter/auth.php') !== false) {
    $path = '/login'; // Varsayılan endpoint
}

$api = new FlutterAuthAPI();

switch ($path) {
    case '/login':
        if ($method === 'POST') {
            $api->login();
        } else {
            $api->sendError('Method not allowed', 405);
        }
        break;
        
    case '/logout':
        if ($method === 'POST') {
            $api->logout();
        } else {
            $api->sendError('Method not allowed', 405);
        }
        break;
        
    case '/refresh':
        if ($method === 'POST') {
            $api->refresh();
        } else {
            $api->sendError('Method not allowed', 405);
        }
        break;
        
    case '/profile':
        if ($method === 'GET') {
            $api->getProfile();
        } elseif ($method === 'PUT') {
            $api->updateProfile();
        } else {
            $api->sendError('Method not allowed', 405);
        }
        break;
        
    default:
        // Eğer path bulunamazsa ve POST ise login varsayılan
        if ($method === 'POST' && empty($path)) {
            $api->login();
        } else {
            $api->sendError('Endpoint bulunamadı: ' . $path, 404);
        }
}
?>
