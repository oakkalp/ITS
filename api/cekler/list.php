<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hibrit kimlik doğrulama: Session veya JWT
if (isset($_SESSION['user_id'])) {
    // Web panel - session kullan
    $firma_id = get_firma_id();
    $user_id = $_SESSION['user_id'];
} else {
    // Flutter app - JWT kullan
    require_once '../../includes/jwt.php';
    
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        json_error('Authorization header gerekli', 401);
    }
    
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        
        // Debug log - decoded object'i göster
        error_log("JWT Decoded Object: " . json_encode($decoded));
        
        $firma_id = $decoded->firma_id ?? $decoded->firmaId ?? null;
        $user_id = $decoded->user_id ?? $decoded->userId ?? null;
        
        // Debug log
        error_log("JWT Decoded - firma_id: $firma_id, user_id: $user_id");
        error_log("Available properties: " . implode(', ', array_keys((array)$decoded)));
        
        if (!$firma_id || !$user_id) {
            error_log("Token eksik bilgi - Firma ID: " . ($firma_id ? 'OK' : 'MISSING') . ", User ID: " . ($user_id ? 'OK' : 'MISSING'));
            json_error('Token eksik bilgi içeriyor', 401);
        }
        
        // Session'ı JWT'den doldur (ad_soyad ve firma_adi eksik olsa bile)
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad ?? 'Kullanıcı';
        $_SESSION['firma_adi'] = $decoded->firma_adi ?? 'Firma';
        
    } catch (Exception $e) {
        error_log("JWT Decode Error: " . $e->getMessage());
        json_error('Geçersiz token: ' . $e->getMessage(), 401);
    }
}

// Buffer'ı temizle
ob_clean();

// Debug log
error_log("=== CEKLER LIST API DEBUG ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . json_encode(getallheaders()));
error_log("Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'));
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'Not Set'));

try {
    // Debug log
    error_log("Çekler API - Firma ID: $firma_id, User ID: $user_id");

$query = "SELECT c.*, car.unvan as cari_unvan FROM cekler c 
          LEFT JOIN cariler car ON c.cari_id = car.id 
          WHERE c.firma_id = ? ORDER BY c.vade_tarihi DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$cekler = [];
while ($row = $result->fetch_assoc()) {
    $cekler[] = $row;
}

    // Debug log
    error_log("Çekler API - Bulunan çek sayısı: " . count($cekler));
    if (count($cekler) > 0) {
        error_log("İlk çek: " . json_encode($cekler[0]));
    }

    json_success('Çekler listelendi', $cekler);
    
} catch (Exception $e) {
    error_log("Çekler listesi hatası: " . $e->getMessage());
    json_error('Çekler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>