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
    // HTTP Method Override kontrolü
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }
    
    if ($method !== 'DELETE' && $method !== 'POST') {
        json_error('Sadece DELETE ve POST istekleri kabul edilir', 405);
    }
    
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        json_error('ID gerekli', 400);
    }
    
    // Çekin firma ID'sini kontrol et
    $check_query = "SELECT id FROM cekler WHERE id = ? AND firma_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("ii", $id, $firma_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows == 0) {
        json_error('Çek bulunamadı veya silme yetkiniz yok', 404);
    }
    
    // Çeki sil
    $delete_query = "DELETE FROM cekler WHERE id = ? AND firma_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bind_param("ii", $id, $firma_id);
    
    if ($delete_stmt->execute()) {
        json_success('Çek başarıyla silindi');
    } else {
        json_error('Çek silinirken hata oluştu', 500);
    }
    
} catch (Exception $e) {
    error_log("Çek silme hatası: " . $e->getMessage());
    json_error('Çek silinirken hata oluştu: ' . $e->getMessage(), 500);
}
?>