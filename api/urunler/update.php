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

$stmt = $db->prepare("UPDATE urunler SET urun_adi = ?, urun_kodu = ?, birim = ?, satis_fiyati = ?, stok_miktari = ?, kdv_orani = ?, aktif = ? WHERE id = ? AND firma_id = ?");

$stmt->bind_param("sssddiiii",
    $data['urun_adi'],
    $data['urun_kodu'] ?? '',
    $data['birim'] ?? 'adet',
    $data['satis_fiyati'] ?? 0,
    $data['stok_miktari'] ?? 0,
    $data['kdv_orani'] ?? 18,
    $data['aktif'] ?? 1,
    $id,
    $firma_id
);

if ($stmt->execute()) {
    json_success('Ürün başarıyla güncellendi');
} else {
    json_error('Ürün güncellenirken hata oluştu: ' . $stmt->error, 500);
}
?>