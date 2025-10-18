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

// ID'yi al (POST, GET veya JSON body'den)
$id = null;

// Önce POST parametresini kontrol et
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    error_log("Cari delete - ID from POST: " . $id);
}
// Sonra JSON body'yi kontrol et
else {
    $input = file_get_contents('php://input');
    error_log("Cari delete - Raw input: " . $input);
    
    if (!empty($input)) {
        $data = json_decode($input, true);
        error_log("Cari delete - Decoded data: " . print_r($data, true));
        
        if ($data && isset($data['id'])) {
            $id = $data['id'];
            error_log("Cari delete - ID from JSON: " . $id);
        }
    }
}
// Son olarak GET parametresini kontrol et
if (!$id) {
    $id = $_GET['id'] ?? null;
    error_log("Cari delete - ID from GET: " . $id);
}

// $firma_id zaten JWT'den alındı
error_log("Cari delete - Firma ID: " . $firma_id);

if (!$id) {
    error_log("Cari delete - ID bulunamadı");
    json_error('Cari ID gerekli', 400);
}

// Cariye ait fatura var mı kontrol et
$check = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE cari_id = $id");
$count = $check->fetch_assoc()['c'];

if ($count > 0) {
    json_error('Bu cariye ait ' . $count . ' fatura bulunmaktadır. Önce faturaları silin.', 400);
}

$stmt = $db->prepare("DELETE FROM cariler WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        json_success('Cari başarıyla silindi');
    } else {
        json_error('Cari bulunamadı', 404);
    }
} else {
    json_error('Cari silinirken hata oluştu: ' . $stmt->error, 500);
}
?>

