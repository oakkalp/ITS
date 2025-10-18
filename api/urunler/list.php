<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// Web paneli için session kontrolü
if (isset($_SESSION['user_id'])) {
    // Web panelinden gelen istek - session kullan
    $firma_id = get_firma_id();
    error_log("Web paneli session kullanılıyor, Firma ID: " . $firma_id);
} else {
    // Flutter uygulamasından gelen istek - JWT token kontrolü
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
        error_log("Authorization header bulunamadı: " . print_r($headers, true));
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

// Ürünleri getir
$query = "SELECT 
            u.id,
            u.urun_adi,
            u.urun_kodu,
            u.birim,
            u.satis_fiyati,
            u.stok_miktari,
            u.kdv_orani,
            u.aktif
          FROM urunler u
          WHERE u.firma_id = ? AND u.aktif = 1
          ORDER BY u.urun_adi ASC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$urunler = [];
while ($row = $result->fetch_assoc()) {
    $urunler[] = $row;
}

json_success('Ürünler listelendi', $urunler);
?>