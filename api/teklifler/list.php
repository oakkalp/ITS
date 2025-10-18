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
        $firma_id = $decoded->firma_id;
        $user_id = $decoded->user_id;
        
        // Session'ı JWT'den doldur
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad;
        $_SESSION['firma_adi'] = $decoded->firma_adi;
        
    } catch (Exception $e) {
        json_error('Geçersiz token', 401);
    }
}

// Buffer'ı temizle
ob_clean();

try {

$query = "SELECT t.*, c.unvan as cari_unvan FROM teklifler t 
          LEFT JOIN cariler c ON t.cari_id = c.id 
          WHERE t.firma_id = ? ORDER BY t.teklif_tarihi DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$teklifler = [];
while ($row = $result->fetch_assoc()) {
    $teklifler[] = $row;
}

    json_success('Teklifler listelendi', $teklifler);
    
} catch (Exception $e) {
    error_log("Teklifler listesi hatası: " . $e->getMessage());
    json_error('Teklifler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>