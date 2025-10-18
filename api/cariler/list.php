<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// Buffer'ı temizle
ob_clean();

// Debug için
error_log("Cariler list API çağrıldı");

// Web paneli için session kontrolü
if (isset($_SESSION['user_id'])) {
    // Web panelinden gelen istek - session kullan
    $firma_id = get_firma_id();
    error_log("Web paneli session kullanılıyor, Firma ID: " . $firma_id);
} else {
    // Flutter uygulamasından gelen istek - JWT token kontrolü
    $auth_header = null;
    
    // IIS için Authorization header kontrolü
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['Authorization'])) {
        $auth_header = $_SERVER['Authorization'];
    } else {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
        error_log("Authorization header bulunamadı. SERVER vars: " . print_r(array_filter($_SERVER, function($key) {
            return strpos($key, 'AUTH') !== false || strpos($key, 'HTTP') !== false;
        }, ARRAY_FILTER_USE_KEY), true));
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header gerekli']);
        exit;
    }

    $token = substr($auth_header, 7); // "Bearer " kısmını çıkar
    error_log("Token alındı: " . substr($token, 0, 20) . "...");

    // Token'ı decode et ve kullanıcı bilgilerini al
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        error_log("Token decode edildi: " . print_r($decoded, true));
        
        // Session'ı manuel olarak ayarla
        $_SESSION['user_id'] = $decoded->user_id;
        $_SESSION['firma_id'] = $decoded->firma_id;
        $_SESSION['rol'] = $decoded->rol;
        
        $firma_id = $decoded->firma_id;
        error_log("Firma ID token'dan alındı: " . $firma_id);
        
    } catch (Exception $e) {
        error_log("Token decode hatası: " . $e->getMessage());
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
        exit;
    }
}

$query = "SELECT *, 
    COALESCE(cari_kodu, '') as cari_kodu,
    COALESCE(is_tedarikci, 0) as is_tedarikci,
    COALESCE(is_musteri, 1) as is_musteri
    FROM cariler WHERE firma_id = ? ORDER BY unvan ASC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$cariler = [];
while ($row = $result->fetch_assoc()) {
    $cariler[] = $row;
}

error_log("Bulunan cari sayısı: " . count($cariler));
json_success('Cariler listelendi', $cariler);
?>

