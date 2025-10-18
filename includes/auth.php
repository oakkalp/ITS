<?php
/**
 * =====================================================
 * KİMLİK DOĞRULAMA VE YETKİLENDİRME SİSTEMİ
 * =====================================================
 * 3 Seviyeli Yetki: Super Admin, Firma Yöneticisi, Kullanıcı
 */

require_once __DIR__ . '/../config.php';

/**
 * Kullanıcı oturum açmış mı kontrol et
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Oturum açmış kullanıcı bilgilerini al
 */
function get_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'kullanici_adi' => $_SESSION['kullanici_adi'] ?? '',
        'ad_soyad' => $_SESSION['ad_soyad'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'rol' => $_SESSION['rol'] ?? '',
        'firma_id' => $_SESSION['firma_id'] ?? null,
        'firma_adi' => $_SESSION['firma_adi'] ?? ''
    ];
}

/**
 * Kullanıcı ID'sini al
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Firma ID'sini al
 */
function get_firma_id() {
    return $_SESSION['firma_id'] ?? null;
}

/**
 * Kullanıcı rolünü al
 */
function get_user_role() {
    return $_SESSION['rol'] ?? null;
}

/**
 * Super Admin mi kontrol et
 */
function is_super_admin() {
    return get_user_role() === 'super_admin';
}

/**
 * Firma Yöneticisi mi kontrol et
 */
function is_firma_yoneticisi() {
    return get_user_role() === 'firma_yoneticisi';
}

/**
 * Normal Kullanıcı mı kontrol et
 */
function is_kullanici() {
    return get_user_role() === 'kullanici';
}

/**
 * Kullanıcı girişi yap
 */
function login_user($kullanici_adi, $sifre) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT k.*, f.firma_adi 
        FROM kullanicilar k
        LEFT JOIN firmalar f ON k.firma_id = f.id
        WHERE k.kullanici_adi = ? AND k.aktif = 1
    ");
    $stmt->bind_param("s", $kullanici_adi);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı!'];
    }
    
    $user = $result->fetch_assoc();
    
    // Debug log - DB'den gelen user bilgileri
    error_log("User from DB: " . json_encode($user));
    
    // Şifre kontrolü
    if (!verify_password($sifre, $user['sifre'])) {
        return ['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı!'];
    }
    
    // Session bilgilerini kaydet
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
    $_SESSION['ad_soyad'] = $user['ad_soyad'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['firma_id'] = $user['firma_id'];
    $_SESSION['firma_adi'] = $user['firma_adi'] ?? '';
    
    // Session cookie ayarlarını güçlendir
    session_regenerate_id(true); // Session ID'yi yenile
    
    // Cookie ayarlarını manuel olarak ayarla
    $cookie_params = session_get_cookie_params();
    setcookie(
        session_name(),
        session_id(),
        time() + SESSION_LIFETIME,
        $cookie_params['path'],
        $cookie_params['domain'],
        $cookie_params['secure'],
        $cookie_params['httponly']
    );
    
    // Kullanıcının yetkilerini yükle (normal kullanıcı ise)
    if ($user['rol'] === 'kullanici') {
        load_user_permissions($user['id']);
    } else {
        // Diğer roller için de yetkileri yükle (güvenlik için)
        load_user_permissions($user['id']);
    }
    
    write_log("Kullanıcı giriş yaptı: " . $kullanici_adi . " (ID: " . $user['id'] . ")", 'auth');
    
    // Debug log
    error_log("User from DB: " . json_encode($user));
    
    // User bilgilerini hazırla
    $user_data = [
        'id' => $user['id'],
        'kullanici_adi' => $user['kullanici_adi'],
        'ad_soyad' => $user['ad_soyad'],
        'email' => $user['email'],
        'rol' => $user['rol'],
        'firma_id' => $user['firma_id'],
        'firma_adi' => $user['firma_adi']
    ];
    
    // Debug log
    error_log("User data prepared: " . json_encode($user_data));
    
    return [
        'success' => true, 
        'message' => 'Giriş başarılı!',
        'user' => $user_data
    ];
}

/**
 * Kullanıcının modül yetkilerini yükle
 */
function load_user_permissions($user_id) {
    global $db;
    
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
    
    $_SESSION['permissions'] = $permissions;
}

/**
 * Kullanıcı çıkışı yap
 */
function logout_user() {
    $user_id = get_user_id();
    write_log("Kullanıcı çıkış yaptı (ID: $user_id)", 'auth');
    
    session_destroy();
    session_start();
}

/**
 * Modül yetkisi kontrol et
 */
function has_permission($modul_kodu, $yetki_tipi = 'okuma') {
    // Super Admin her şeye erişebilir
    if (is_super_admin()) {
        return true;
    }
    
    // Firma Yöneticisi her şeye erişebilir (kendi firmasında)
    if (is_firma_yoneticisi()) {
        return true;
    }
    
    // Normal kullanıcı için yetki kontrolü
    if (isset($_SESSION['permissions'][$modul_kodu])) {
        return $_SESSION['permissions'][$modul_kodu][$yetki_tipi] ?? false;
    }
    
    return false;
}

/**
 * Yetki kontrolü yap - Yetkisizse hata ver
 */
function require_permission($modul_kodu, $yetki_tipi = 'okuma') {
    if (!has_permission($modul_kodu, $yetki_tipi)) {
        if (defined('IS_API') && IS_API) {
            json_error('Bu işlem için yetkiniz yok!', 403);
        } else {
            die('
                <div style="text-align:center; margin-top:100px; font-family:Arial;">
                    <h1 style="color:#d32f2f;">⛔ Yetki Hatası</h1>
                    <p style="font-size:18px;">Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
                    <a href="' . url('dashboard.php') . '" style="display:inline-block; margin-top:20px; padding:10px 20px; background:#1976d2; color:#fff; text-decoration:none; border-radius:5px;">Ana Sayfaya Dön</a>
                </div>
            ');
        }
    }
}

/**
 * Oturum kontrolü - Giriş yapmamışsa login sayfasına yönlendir
 */
function require_login() {
    if (!is_logged_in()) {
        if (defined('IS_API') && IS_API) {
            json_error('Oturum açmanız gerekiyor!', 401);
        } else {
            redirect(url('login.php'));
        }
    }
}

/**
 * Rol kontrolü
 */
function require_role($required_roles = []) {
    require_login();
    
    $user_role = get_user_role();
    
    if (!in_array($user_role, $required_roles)) {
        if (defined('IS_API') && IS_API) {
            json_error('Bu işlem için yetkiniz yok!', 403);
        } else {
            die('
                <div style="text-align:center; margin-top:100px; font-family:Arial;">
                    <h1 style="color:#d32f2f;">⛔ Yetki Hatası</h1>
                    <p style="font-size:18px;">Bu işlem için gerekli role sahip değilsiniz.</p>
                    <a href="' . url('dashboard.php') . '" style="display:inline-block; margin-top:20px; padding:10px 20px; background:#1976d2; color:#fff; text-decoration:none; border-radius:5px;">Ana Sayfaya Dön</a>
                </div>
            ');
        }
    }
}

/**
 * Firma kontrolü - Kullanıcı sadece kendi firmasının verilerine erişebilir
 */
function check_firma_access($record_firma_id) {
    // Super Admin her firmaya erişebilir
    if (is_super_admin()) {
        return true;
    }
    
    // Diğer kullanıcılar sadece kendi firmalarına
    $user_firma_id = get_firma_id();
    
    if ($user_firma_id != $record_firma_id) {
        if (defined('IS_API') && IS_API) {
            json_error('Bu veriye erişim yetkiniz yok!', 403);
        } else {
            return false;
        }
    }
    
    return true;
}

/**
 * JWT Token Oluştur (Mobil API için)
 */
function create_jwt_token($user_data) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    $payload = json_encode([
        'user_id' => $user_data['id'],
        'kullanici_adi' => $user_data['kullanici_adi'],
        'rol' => $user_data['rol'],
        'firma_id' => $user_data['firma_id'],
        'iat' => time(),
        'exp' => time() + JWT_EXPIRY
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * JWT Token Doğrula (Mobil API için)
 */
function verify_jwt_token($token) {
    if (!$token) {
        return false;
    }
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return false;
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
    $signatureProvided = $tokenParts[2];
    
    $signature = hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], JWT_SECRET_KEY, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($base64UrlSignature !== $signatureProvided) {
        return false;
    }
    
    $payload = json_decode($payload, true);
    
    // Token süresi dolmuş mu?
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * API Token Kontrolü (Header'dan al)
 */
function require_api_auth() {
    define('IS_API', true);
    
    $headers = getallheaders();
    $token = null;
    
    // Authorization header'dan token al
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
        json_error('Geçersiz veya süresi dolmuş token!', 401);
    }
    
    // Session bilgilerini JWT'den yükle
    $_SESSION['user_id'] = $payload['user_id'];
    $_SESSION['kullanici_adi'] = $payload['kullanici_adi'];
    $_SESSION['rol'] = $payload['rol'];
    $_SESSION['firma_id'] = $payload['firma_id'];
    
    // Kullanıcı yetkilerini yükle
    if ($payload['rol'] === 'kullanici') {
        load_user_permissions($payload['user_id']);
    }
    
    return $payload;
}

/**
 * JWT Token oluştur
 */
function generate_jwt_token($user) {
    try {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'firma_id' => $user['firma_id'],
            'ad_soyad' => $user['ad_soyad'],
            'firma_adi' => $user['firma_adi'],
            'rol' => $user['rol'],
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET_KEY, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    } catch (Exception $e) {
        error_log("JWT Token oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * JWT Token doğrula
 */
function validate_jwt_token($token) {
    try {
        if (empty($token)) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Base64 padding ekle
        $base64Header .= str_repeat('=', (4 - strlen($base64Header) % 4) % 4);
        $base64Payload .= str_repeat('=', (4 - strlen($base64Payload) % 4) % 4);
        
        $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET_KEY, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64Signature !== $expectedSignature) {
            return false;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    } catch (Exception $e) {
        error_log("JWT Token doğrulama hatası: " . $e->getMessage());
        return false;
    }
}

?>

