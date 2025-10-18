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

    if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
        error_log("Authorization header bulunamadı: " . print_r($headers, true));
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header gerekli']);
        exit;
    }

    $token = substr($auth_header, 7); // "Bearer " kısmını çıkar

    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        $firma_id = $decoded->firma_id ?? $decoded->firmaId ?? null;
        $user_id = $decoded->user_id ?? $decoded->userId ?? null;
        
        if (!$firma_id || !$user_id) {
            error_log("Token eksik bilgi içeriyor: " . json_encode($decoded));
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token eksik bilgi içeriyor']);
            exit;
        }
        
        // Session'ı JWT'den doldur
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad ?? '';
        $_SESSION['firma_adi'] = $decoded->firma_adi ?? '';
        
    } catch (Exception $e) {
        error_log("JWT Decode Error: " . $e->getMessage());
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Geçersiz token: ' . $e->getMessage()]);
        exit;
    }
}

// Buffer'ı temizle
ob_clean();

// Debug log
error_log("=== CARILER UPDATE API DEBUG ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . json_encode(getallheaders()));
error_log("Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'));
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'Not Set'));
error_log("Firma ID: " . ($firma_id ?? 'Not Set'));

$data = json_decode(file_get_contents('php://input'), true);
error_log("Input Data: " . json_encode($data));

$id = $data['id'] ?? null;
error_log("Cari ID: " . ($id ?? 'Not Set'));

if (!$id) {
    json_error('Cari ID gerekli', 400);
}

// Cari'nin bu firmaya ait olduğunu kontrol et
$stmt_check = $db->prepare("SELECT id FROM cariler WHERE id = ? AND firma_id = ?");
$stmt_check->bind_param("ii", $id, $firma_id);
$stmt_check->execute();
if (!$stmt_check->get_result()->fetch_assoc()) {
    json_error('Cari bulunamadı veya yetkiniz yok', 404);
}

$stmt = $db->prepare("UPDATE cariler SET cari_kodu = ?, unvan = ?, telefon = ?, email = ?, adres = ?, vergi_dairesi = ?, vergi_no = ?, yetkili_kisi = ?, is_musteri = ?, is_tedarikci = ? WHERE id = ?");

// Prepare parameters
$cari_kodu = $data['cari_kodu'] ?? '';
$unvan = $data['unvan'] ?? '';
$telefon = $data['telefon'] ?? '';
$email = $data['email'] ?? '';
$adres = $data['adres'] ?? '';
$vergi_dairesi = $data['vergi_dairesi'] ?? '';
$vergi_no = $data['vergi_no'] ?? '';
$yetkili_kisi = $data['yetkili_kisi'] ?? '';
$is_musteri = $data['is_musteri'] ?? 0;
$is_tedarikci = $data['is_tedarikci'] ?? 0;

$stmt->bind_param("sssssssssii", 
    $cari_kodu,
    $unvan,
    $telefon,
    $email,
    $adres,
    $vergi_dairesi,
    $vergi_no,
    $yetkili_kisi,
    $is_musteri,
    $is_tedarikci,
    $id
);

if ($stmt->execute()) {
    json_success('Cari başarıyla güncellendi');
} else {
    json_error('Cari güncellenirken hata oluştu', 500);
}
?>