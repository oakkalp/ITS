<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 0); // HTML çıktısını engelle
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../config.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Config hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Basit authentication (kullanıcı adı kontrolü)
$username = $_GET['username'] ?? $_POST['username'] ?? '';
if (empty($username)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
    exit;
}

// Kullanıcıyı bul
$stmt = $db->prepare("SELECT id, firma_id, rol FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
    exit;
}

$user = $result->fetch_assoc();
$firma_id = $user['firma_id'];

// Method'a göre işlem yap
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Cariler listesi
        $query = "SELECT * FROM cariler WHERE firma_id = ? ORDER BY unvan ASC";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cariler = [];
        while ($row = $result->fetch_assoc()) {
            $cariler[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cariler listelendi',
            'data' => $cariler
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
        
    case 'POST':
        // Yeni cari ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['unvan'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ünvan gerekli']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO cariler (firma_id, unvan, is_musteri, is_tedarikci, telefon, email, adres) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiisss", 
            $firma_id,
            $input['unvan'],
            $input['is_musteri'] ?? 0,
            $input['is_tedarikci'] ?? 0,
            $input['telefon'] ?? null,
            $input['email'] ?? null,
            $input['adres'] ?? null
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Cari başarıyla eklendi',
                'data' => ['id' => $db->insert_id]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cari eklenirken hata oluştu']);
        }
        break;
        
    case 'PUT':
        // Cari güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        if (!$id || empty($input['unvan'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID ve ünvan gerekli']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE cariler SET unvan = ?, is_musteri = ?, is_tedarikci = ?, telefon = ?, email = ?, adres = ? WHERE id = ? AND firma_id = ?");
        $stmt->bind_param("siiisssii",
            $input['unvan'],
            $input['is_musteri'] ?? 0,
            $input['is_tedarikci'] ?? 0,
            $input['telefon'] ?? null,
            $input['email'] ?? null,
            $input['adres'] ?? null,
            $id,
            $firma_id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Cari başarıyla güncellendi'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cari güncellenirken hata oluştu']);
        }
        break;
        
    case 'DELETE':
        // Cari sil
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID gerekli']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM cariler WHERE id = ? AND firma_id = ?");
        $stmt->bind_param("ii", $id, $firma_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Cari başarıyla silindi'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cari silinirken hata oluştu']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
