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

// Filtre parametrelerini al
$baslangic = $_GET['baslangic'] ?? '';
$bitis = $_GET['bitis'] ?? '';
$islem_tipi = $_GET['islem_tipi'] ?? '';

// WHERE koşullarını oluştur
$where_conditions = ["firma_id = ?"];
$params = [$firma_id];
$param_types = "i";

if (!empty($baslangic)) {
    $where_conditions[] = "tarih >= ?";
    $params[] = $baslangic;
    $param_types .= "s";
}

if (!empty($bitis)) {
    $where_conditions[] = "tarih <= ?";
    $params[] = $bitis;
    $param_types .= "s";
}

if (!empty($islem_tipi)) {
    $where_conditions[] = "islem_tipi = ?";
    $params[] = $islem_tipi;
    $param_types .= "s";
}

$query = "SELECT * FROM kasa_hareketleri WHERE " . implode(" AND ", $where_conditions) . " ORDER BY tarih DESC, id DESC";

$stmt = $db->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$hareketler = [];
while ($row = $result->fetch_assoc()) {
    $hareketler[] = $row;
}

    json_success('Kasa hareketleri listelendi', $hareketler);
    
} catch (Exception $e) {
    error_log("Kasa hareketleri listesi hatası: " . $e->getMessage());
    json_error('Kasa hareketleri yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>

