<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// JWT token kontrolü
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

// Ödenmemiş veya kısmi ödemeli faturaları getir
$query = "SELECT f.*, c.unvan as cari_unvan FROM faturalar f 
          LEFT JOIN cariler c ON f.cari_id = c.id 
          WHERE f.firma_id = ? AND f.odeme_durumu != 'odendi'
          ORDER BY f.fatura_tarihi DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$faturalar = [];
while ($row = $result->fetch_assoc()) {
    $faturalar[] = $row;
}

json_success('Ödemeler listelendi', $faturalar);
?>
