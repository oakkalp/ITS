<?php
// Error reporting'i kapat
error_reporting(0);
ini_set('display_errors', 0);

// JSON header'ı ayarla
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

// OPTIONS request'i handle et
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Config'i yükle
require_once '../../config.php';

try {
    // Database bağlantısı
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception('Database connection failed: ' . $db->connect_error);
    }
    
    $db->set_charset('utf8mb4');
    
    // Request method ve path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? '';
    
    // Basit JWT token oluşturucu
    function generateJWT($user_id, $firma_id, $rol) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user_id,
            'firma_id' => $firma_id,
            'rol' => $rol,
            'iat' => time(),
            'exp' => time() + 86400 // 24 saat
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, 'flutter_jwt_secret', true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    // Response gönderici
    function sendResponse($data = null, $message = '', $success = true, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Login endpoint
    if ($path === '/login' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['kullanici_adi']) || !isset($input['sifre'])) {
            sendResponse(null, 'Kullanıcı adı ve şifre gerekli', false, 400);
        }
        
        $kullanici_adi = $input['kullanici_adi'];
        $sifre = $input['sifre'];
        
        // Kullanıcıyı bul
        $query = "SELECT id, firma_id, ad_soyad, kullanici_adi, sifre, rol, aktif FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $kullanici_adi);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(null, 'Kullanıcı adı veya şifre hatalı', false, 401);
        }
        
        $user = $result->fetch_assoc();
        
        // Şifre kontrolü
        if (!password_verify($sifre, $user['sifre'])) {
            sendResponse(null, 'Kullanıcı adı veya şifre hatalı', false, 401);
        }
        
        // JWT Token oluştur
        $token = generateJWT($user['id'], $user['firma_id'], $user['rol']);
        
        // Kullanıcı bilgilerini hazırla
        $user_data = [
            'id' => $user['id'],
            'firma_id' => $user['firma_id'],
            'ad_soyad' => $user['ad_soyad'],
            'kullanici_adi' => $user['kullanici_adi'],
            'rol' => $user['rol']
        ];
        
        // Firma bilgilerini al
        $firma_query = "SELECT id, firma_adi, logo FROM firmalar WHERE id = ?";
        $firma_stmt = $db->prepare($firma_query);
        $firma_stmt->bind_param('i', $user['firma_id']);
        $firma_stmt->execute();
        $firma = $firma_stmt->get_result()->fetch_assoc();
        
        sendResponse([
            'user' => $user_data,
            'firma' => $firma,
            'token' => $token,
            'expires_in' => 86400
        ], 'Giriş başarılı');
    }
    
    // Dashboard stats endpoint
    elseif ($path === '/dashboard/stats' && $method === 'GET') {
        // Token kontrolü (basit)
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        if (empty($token)) {
            sendResponse(null, 'Token gerekli', false, 401);
        }
        
        // Basit token kontrolü (gerçek uygulamada JWT decode edilmeli)
        if (strlen($token) < 50) {
            sendResponse(null, 'Geçersiz token', false, 401);
        }
        
        // İstatistikleri al (firma_id = 1 varsayımı)
        $firma_id = 1;
        
        // Cari sayısı
        $cari_query = "SELECT COUNT(*) as count FROM cariler WHERE firma_id = ? AND aktif = 1";
        $stmt = $db->prepare($cari_query);
        $stmt->bind_param('i', $firma_id);
        $stmt->execute();
        $cari_count = $stmt->get_result()->fetch_assoc()['count'];
        
        // Ürün sayısı
        $urun_query = "SELECT COUNT(*) as count FROM urunler WHERE firma_id = ? AND aktif = 1";
        $stmt = $db->prepare($urun_query);
        $stmt->bind_param('i', $firma_id);
        $stmt->execute();
        $urun_count = $stmt->get_result()->fetch_assoc()['count'];
        
        // Fatura sayısı (bu ay)
        $fatura_query = "SELECT COUNT(*) as count FROM faturalar WHERE firma_id = ? AND MONTH(fatura_tarihi) = MONTH(CURRENT_DATE()) AND YEAR(fatura_tarihi) = YEAR(CURRENT_DATE())";
        $stmt = $db->prepare($fatura_query);
        $stmt->bind_param('i', $firma_id);
        $stmt->execute();
        $fatura_count = $stmt->get_result()->fetch_assoc()['count'];
        
        // Toplam ciro (bu ay)
        $ciro_query = "SELECT COALESCE(SUM(toplam_tutar), 0) as toplam FROM faturalar WHERE firma_id = ? AND MONTH(fatura_tarihi) = MONTH(CURRENT_DATE()) AND YEAR(fatura_tarihi) = YEAR(CURRENT_DATE()) AND fatura_tipi = 'satis'";
        $stmt = $db->prepare($ciro_query);
        $stmt->bind_param('i', $firma_id);
        $stmt->execute();
        $ciro = $stmt->get_result()->fetch_assoc()['toplam'];
        
        $stats = [
            'cari_sayisi' => (int)$cari_count,
            'urun_sayisi' => (int)$urun_count,
            'aylik_fatura_sayisi' => (int)$fatura_count,
            'aylik_ciro' => (float)$ciro,
            'bekleyen_cek_sayisi' => 0,
            'yaklasan_cek_sayisi' => 0,
            'kritik_stok_sayisi' => 0
        ];
        
        sendResponse($stats, 'İstatistikler alındı');
    }
    
    // Diğer endpoint'ler için placeholder
    else {
        sendResponse(null, 'Endpoint bulunamadı', false, 404);
    }
    
} catch (Exception $e) {
    sendResponse(null, 'Sunucu hatası: ' . $e->getMessage(), false, 500);
}
?>
