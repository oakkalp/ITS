<?php
/**
 * =====================================================
 * FLUTTER MOBİL UYGULAMA - KİMLİK DOĞRULAMA API
 * =====================================================
 * Web panel ile aynı veritabanını kullanır
 * JWT token tabanlı kimlik doğrulama
 */

require_once '../../config.php';
require_once '../../includes/auth.php';

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'POST':
        case 'PUT':
            switch ($action) {
                case 'login':
                    handleLogin();
                    break;
                case 'logout':
                    handleLogout();
                    break;
                case 'refresh':
                    handleRefreshToken();
                    break;
                case 'update_profile':
                    handleUpdateProfile();
                    break;
                default:
                    json_error('Geçersiz işlem', 400);
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'profile':
                    handleGetProfile();
                    break;
                case 'permissions':
                    handleGetPermissions();
                    break;
                default:
                    json_error('Geçersiz işlem', 400);
            }
            break;
            
        default:
            json_error('Desteklenmeyen HTTP metodu', 405);
    }
    
} catch (Exception $e) {
    error_log("Flutter Auth API Hatası: " . $e->getMessage());
    json_error('Sunucu hatası: ' . $e->getMessage(), 500);
}

/**
 * Kullanıcı girişi
 */
function handleLogin() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['kullanici_adi']) || !isset($input['sifre'])) {
        json_error('Kullanıcı adı ve şifre gerekli', 400);
    }
    
    $kullanici_adi = trim($input['kullanici_adi']);
    $sifre = $input['sifre'];
    
    // Kullanıcıyı bul
    $stmt = $db->prepare("
        SELECT k.*, f.firma_adi, f.aktif as firma_aktif
        FROM kullanicilar k
        LEFT JOIN firmalar f ON k.firma_id = f.id
        WHERE k.kullanici_adi = ? AND k.aktif = 1
    ");
    $stmt->bind_param("s", $kullanici_adi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_error('Kullanıcı adı veya şifre hatalı!', 401);
    }
    
    $user = $result->fetch_assoc();
    
    // Şifre kontrolü
    if (!verify_password($sifre, $user['sifre'])) {
        json_error('Kullanıcı adı veya şifre hatalı!', 401);
    }
    
    // Firma aktif mi kontrol et
    if (!$user['firma_aktif']) {
        json_error('Firmanız aktif değil!', 403);
    }
    
    // Kullanıcı yetkilerini yükle
    $permissions = loadUserPermissions($user['id']);
    
    // JWT token oluştur
    $token = createJWTToken([
        'user_id' => $user['id'],
        'kullanici_adi' => $user['kullanici_adi'],
        'ad_soyad' => $user['ad_soyad'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'firma_id' => $user['firma_id'],
        'firma_adi' => $user['firma_adi']
    ]);
    
    // Kullanıcı bilgilerini hazırla
    $userData = [
        'id' => $user['id'],
        'kullanici_adi' => $user['kullanici_adi'],
        'ad_soyad' => $user['ad_soyad'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'firma_id' => $user['firma_id'],
        'firma_adi' => $user['firma_adi'],
        'permissions' => $permissions
    ];
    
    write_log("Flutter giriş: " . $kullanici_adi . " (ID: " . $user['id'] . ")", 'auth');
    
    json_success('Giriş başarılı!', [
        'token' => $token,
        'user' => $userData
    ]);
}

/**
 * Kullanıcı çıkışı
 */
function handleLogout() {
    // JWT token tabanlı sistemde server-side logout gerekli değil
    // Token'ın süresi dolduğunda otomatik olarak geçersiz olur
    json_success('Çıkış başarılı!');
}

/**
 * Token yenileme
 */
function handleRefreshToken() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        json_error('Token gerekli!', 401);
    }
    
    $payload = verify_jwt_token($token);
    
    if (!$payload) {
        json_error('Geçersiz token!', 401);
    }
    
    // Yeni token oluştur
    $newToken = createJWTToken([
        'user_id' => $payload['user_id'],
        'kullanici_adi' => $payload['kullanici_adi'],
        'ad_soyad' => $payload['ad_soyad'],
        'email' => $payload['email'],
        'rol' => $payload['rol'],
        'firma_id' => $payload['firma_id'],
        'firma_adi' => $payload['firma_adi']
    ]);
    
    json_success('Token yenilendi!', [
        'token' => $newToken
    ]);
}

/**
 * Kullanıcı profil bilgilerini getir
 */
function handleGetProfile() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        json_error('Token gerekli!', 401);
    }
    
    $payload = verify_jwt_token($token);
    
    if (!$payload) {
        json_error('Geçersiz token!', 401);
    }
    
    // Kullanıcı bilgilerini veritabanından al
    global $db;
    $stmt = $db->prepare("
        SELECT k.*, f.firma_adi
        FROM kullanicilar k
        LEFT JOIN firmalar f ON k.firma_id = f.id
        WHERE k.id = ?
    ");
    $stmt->bind_param("i", $payload['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_error('Kullanıcı bulunamadı!', 404);
    }
    
    $user = $result->fetch_assoc();
    
    // Yetkileri yükle
    $permissions = loadUserPermissions($user['id']);
    
    $userData = [
        'id' => $user['id'],
        'kullanici_adi' => $user['kullanici_adi'],
        'ad_soyad' => $user['ad_soyad'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'firma_id' => $user['firma_id'],
        'firma_adi' => $user['firma_adi'],
        'permissions' => $permissions
    ];
    
    json_success('Profil bilgileri getirildi', $userData);
}

/**
 * Kullanıcı profil bilgilerini güncelle
 */
function handleUpdateProfile() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        json_error('Token gerekli!', 401);
    }
    
    $payload = verify_jwt_token($token);
    
    if (!$payload) {
        json_error('Geçersiz token!', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    global $db;
    
    try {
        $db->begin_transaction();
        
        $user_id = $payload['user_id'];
        $update_fields = [];
        $update_values = [];
        $types = '';
        
        // Ad Soyad güncelleme
        if (isset($input['ad_soyad']) && !empty(trim($input['ad_soyad']))) {
            $update_fields[] = 'ad_soyad = ?';
            $update_values[] = trim($input['ad_soyad']);
            $types .= 's';
        }
        
        // Şifre güncelleme
        if (isset($input['yeni_sifre']) && !empty($input['yeni_sifre'])) {
            if (!isset($input['sifre']) || empty($input['sifre'])) {
                json_error('Mevcut şifre gerekli', 400);
            }
            
            // Mevcut şifreyi kontrol et
            $stmt_check = $db->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $user_data = $result_check->fetch_assoc();
            
            if (!verify_password($input['sifre'], $user_data['sifre'])) {
                json_error('Mevcut şifre hatalı', 400);
            }
            
            $update_fields[] = 'sifre = ?';
            $update_values[] = hash_password($input['yeni_sifre']);
            $types .= 's';
        }
        
        if (empty($update_fields)) {
            json_error('Güncellenecek veri bulunamadı', 400);
        }
        
        // Güncelleme sorgusu
        $update_values[] = $user_id;
        $types .= 'i';
        
        $sql = "UPDATE kullanicilar SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$update_values);
        
        if (!$stmt->execute()) {
            throw new Exception('Profil güncellenemedi: ' . $stmt->error);
        }
        
        $db->commit();
        
        write_log("Flutter profil güncelleme: " . $payload['kullanici_adi'] . " (ID: " . $user_id . ")", 'auth');
        
        json_success('Profil başarıyla güncellendi');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Profil güncelleme hatası: " . $e->getMessage());
        json_error('Profil güncellenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

/**
 * Kullanıcı yetkilerini getir
 */
function handleGetPermissions() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        json_error('Token gerekli!', 401);
    }
    
    $payload = verify_jwt_token($token);
    
    if (!$payload) {
        json_error('Geçersiz token!', 401);
    }
    
    $permissions = loadUserPermissions($payload['user_id']);
    
    json_success('Yetkiler getirildi', $permissions);
}

/**
 * Kullanıcının modül yetkilerini yükle
 */
function loadUserPermissions($user_id) {
    global $db;
    
    // Kullanıcı rolünü kontrol et
    $stmt = $db->prepare("SELECT rol FROM kullanicilar WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Super Admin ve Firma Yöneticisi her şeye erişebilir
    if ($user['rol'] === 'super_admin' || $user['rol'] === 'firma_yoneticisi') {
        return [
            'cariler' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'urunler' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'faturalar' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'teklifler' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'odemeler' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'kasa' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'cekler' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'personel' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'raporlar' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'kullanicilar' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true],
            'firma_ayarlari' => ['okuma' => true, 'yazma' => true, 'guncelleme' => true, 'silme' => true]
        ];
    }
    
    // Normal kullanıcı için yetkileri veritabanından al
    $stmt = $db->prepare("
        SELECT m.modul_kodu, y.okuma, y.yazma, y.guncelleme, y.silme
        FROM kullanici_yetkileri y
        JOIN moduller m ON y.modul_id = m.id
        WHERE y.kullanici_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['modul_kodu']] = [
            'okuma' => (bool)$row['okuma'],
            'yazma' => (bool)$row['yazma'],
            'guncelleme' => (bool)$row['guncelleme'],
            'silme' => (bool)$row['silme']
        ];
    }
    
    return $permissions;
}

/**
 * JWT Token oluştur
 */
function createJWTToken($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $payload = json_encode($payload);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
?>
