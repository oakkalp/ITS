<?php
ob_start(); // Output buffering başlat
require_once '../../config.php';
require_once '../../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hybrid authentication: Session or JWT
if (isset($_SESSION['user_id'])) {
    $firma_id = get_firma_id();
    $user_id = $_SESSION['user_id'];
} else {
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
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad;
        $_SESSION['firma_adi'] = $decoded->firma_adi;
    } catch (Exception $e) {
        json_error('Geçersiz token', 401);
    }
}
ob_clean(); // Buffer'ı temizle
try {

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    json_error('Ürün ID gerekli', 400);
}

// Ürüne ait fatura var mı kontrol et
$check = $db->query("SELECT COUNT(*) as c FROM fatura_detaylari WHERE urun_id = $id");
$count = $check->fetch_assoc()['c'];

if ($count > 0) {
    json_error('Bu ürüne ait ' . $count . ' fatura kalemi bulunmaktadır. Önce faturaları silin.', 400);
}

$stmt = $db->prepare("DELETE FROM urunler WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        json_success('Ürün başarıyla silindi');
    } else {
        json_error('Ürün bulunamadı', 404);
    }
} else {
    json_error('Ürün silinirken hata oluştu: ' . $stmt->error, 500);
}
?>