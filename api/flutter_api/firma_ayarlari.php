<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// Sadece POST veya GET isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Geçersiz istek metodu', 405);
}

// JWT token kontrolü
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['authorization'])) {
    $token = str_replace('Bearer ', '', $headers['authorization']);
}

if (!$token) {
    json_error('Token gerekli', 401);
}

try {
    $decoded = JWT::decode($token, JWT_SECRET_KEY, ['HS256']);
    if (is_array($decoded)) {
        $decoded = (object) $decoded;
    }
    
    $firma_id = $decoded->firma_id;
    $kullanici_id = $decoded->user_id;
    
    // Sadece firma yöneticisi erişebilir
    $stmt_role = $db->prepare("SELECT rol FROM kullanicilar WHERE id = ? AND firma_id = ?");
    $stmt_role->bind_param("ii", $kullanici_id, $firma_id);
    $stmt_role->execute();
    $role_result = $stmt_role->get_result();
    
    if ($role_result->num_rows === 0) {
        json_error('Kullanıcı bulunamadı', 404);
    }
    
    $user = $role_result->fetch_assoc();
    if ($user['rol'] !== 'firma_yoneticisi') {
        json_error('Bu işlemi yapmaya yetkiniz yok. Sadece firma yöneticisi erişebilir.', 403);
    }

} catch (Exception $e) {
    json_error('Geçersiz token: ' . $e->getMessage(), 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        handleGetFirmaBilgileri();
        break;
    case 'update':
        handleUpdateFirmaBilgileri();
        break;
    case 'upload_logo':
        handleUploadLogo();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetFirmaBilgileri() {
    global $db, $firma_id;
    
    $stmt = $db->prepare("SELECT * FROM firmalar WHERE id = ?");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        json_success('Firma bilgileri getirildi', $row);
    } else {
        json_error('Firma bulunamadı', 404);
    }
}

function handleUpdateFirmaBilgileri() {
    global $db, $firma_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['firma_adi'])) {
        json_error('Firma adı gerekli', 400);
    }
    
    $firma_adi = $data['firma_adi'];
    $vergi_no = $data['vergi_no'] ?? '';
    $vergi_dairesi = $data['vergi_dairesi'] ?? '';
    $telefon = $data['telefon'] ?? '';
    $email = $data['email'] ?? '';
    $adres = $data['adres'] ?? '';
    
    $stmt = $db->prepare("UPDATE firmalar SET firma_adi = ?, vergi_no = ?, vergi_dairesi = ?, telefon = ?, email = ?, adres = ? WHERE id = ?");
    $stmt->bind_param("ssssssi",
        $firma_adi,
        $vergi_no,
        $vergi_dairesi,
        $telefon,
        $email,
        $adres,
        $firma_id
    );
    
    if ($stmt->execute()) {
        json_success('Firma bilgileri başarıyla güncellendi');
    } else {
        json_error('Güncelleme sırasında hata oluştu: ' . $stmt->error, 500);
    }
}

function handleUploadLogo() {
    global $db, $firma_id;
    
    if (!isset($_FILES['logo'])) {
        json_error('Logo dosyası gerekli', 400);
    }
    
    $file = $_FILES['logo'];
    
    // Dosya boyutu kontrolü (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        json_error('Dosya boyutu 2MB\'dan büyük olamaz', 400);
    }
    
    // Dosya tipi kontrolü
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        json_error('Sadece JPG, JPEG ve PNG formatları desteklenir', 400);
    }
    
    // Upload dizini
    $upload_dir = '../../uploads/logos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Dosya adı oluştur
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . $firma_id . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // Dosyayı taşı
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Veritabanını güncelle
        $stmt = $db->prepare("UPDATE firmalar SET logo = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $firma_id);
        
        if ($stmt->execute()) {
            json_success('Logo başarıyla yüklendi', ['filename' => $filename]);
        } else {
            // Dosyayı sil
            unlink($file_path);
            json_error('Veritabanı güncellenirken hata oluştu', 500);
        }
    } else {
        json_error('Dosya yüklenirken hata oluştu', 500);
    }
}
?>
