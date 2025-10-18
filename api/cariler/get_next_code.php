<?php
ob_start();
require_once "../../config.php";
require_once "../../includes/auth.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hibrit kimlik doğrulama: Session veya JWT
$firma_id = null;
$user_id = null;

if (isset($_SESSION['user_id'])) {
    // Web panel - session kullan
    $user_id = $_SESSION['user_id'];
    $firma_id = $_SESSION['firma_id'] ?? null;
    
    // Eğer session'da firma_id yoksa, kullanıcıdan al
    if (!$firma_id) {
        $stmt = $db->prepare("SELECT firma_id FROM kullanicilar WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $firma_id = $row['firma_id'];
            $_SESSION['firma_id'] = $firma_id;
        }
    }
} else {
    // Flutter app - JWT kullan
    require_once '../../includes/jwt.php';
    
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = $matches[1];
        
        try {
            $decoded = JWT::decode($token, JWT_SECRET_KEY);
            $firma_id = $decoded->firma_id ?? $decoded->firmaId ?? null;
            $user_id = $decoded->user_id ?? $decoded->userId ?? null;
            
            if (!$firma_id || !$user_id) {
                json_error('Token eksik bilgi içeriyor', 401);
            }
            
            // Session'ı JWT'den doldur
            $_SESSION['user_id'] = $user_id;
            $_SESSION['firma_id'] = $firma_id;
            $_SESSION['ad_soyad'] = $decoded->ad_soyad ?? '';
            $_SESSION['firma_adi'] = $decoded->firma_adi ?? '';
            
        } catch (Exception $e) {
            error_log("JWT Decode Error: " . $e->getMessage());
            json_error('Geçersiz token: ' . $e->getMessage(), 401);
        }
    } else {
        json_error('Authorization header gerekli', 401);
    }
}

ob_clean();

try {
    // En yüksek cari kodunu bul ve bir artır
    $max_result = $db->query("SELECT MAX(CAST(SUBSTRING(cari_kodu, 1, 10) AS UNSIGNED)) as max_code FROM cariler WHERE cari_kodu REGEXP '^[0-9]+$' AND firma_id = $firma_id");
    $max_row = $max_result->fetch_assoc();
    $next_code = ($max_row['max_code'] ?? 0) + 1;
    $next_code_formatted = str_pad($next_code, 6, '0', STR_PAD_LEFT); // 6 haneli kod
    
    json_success('Sonraki cari kodu alındı', [
        'next_code' => $next_code_formatted
    ]);
    
} catch (Exception $e) {
    error_log("Sonraki cari kodu alma hatası: " . $e->getMessage());
    json_error('Sonraki cari kodu alınırken hata oluştu: ' . $e->getMessage(), 500);
}
?>
