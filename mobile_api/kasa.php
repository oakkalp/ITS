<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 0); // HTML çıktısını engelle
ini_set('log_errors', 1);
ini_set('html_errors', 0); // HTML hata formatını kapat

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
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // Kullanıcıyı bul
    $stmt = $db->prepare("SELECT id, firma_id, rol FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $user = $result->fetch_assoc();
    $firma_id = $user['firma_id'];
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı kontrolü hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Method'a göre işlem yap
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Kasa hareketleri listesi
        $query = "SELECT * FROM kasa_hareketleri WHERE firma_id = ? ORDER BY tarih DESC";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $hareketler = [];
        while ($row = $result->fetch_assoc()) {
            $hareketler[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Kasa hareketleri listelendi',
            'data' => $hareketler
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
        
    case 'POST':
        // Yeni kasa hareketi ekle
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (!$input || empty($input['aciklama']) || !isset($input['tutar']) || empty($input['tip'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Açıklama, tutar ve tip gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO kasa_hareketleri (firma_id, aciklama, tutar, tarih, islem_tipi) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", 
                $firma_id,
                $input['aciklama'],
                $input['tutar'],
                $input['tarih'] ?? date('Y-m-d'),
                $input['tip']
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Kasa hareketi başarıyla eklendi',
                    'data' => ['id' => $db->insert_id]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Kasa hareketi eklenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'PUT':
        // Kasa hareketi güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        if (!$id || empty($input['aciklama']) || !isset($input['tutar'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID, açıklama ve tutar gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE kasa_hareketleri SET aciklama = ?, tutar = ?, tarih = ?, islem_tipi = ? WHERE id = ? AND firma_id = ?");
        $stmt->bind_param("sdssii",
            $input['aciklama'],
            $input['tutar'],
            $input['tarih'],
            $input['tip'],
            $id,
            $firma_id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Kasa hareketi başarıyla güncellendi'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kasa hareketi güncellenirken hata oluştu'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'DELETE':
        // Kasa hareketi sil
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM kasa_hareketleri WHERE id = ? AND firma_id = ?");
        $stmt->bind_param("ii", $id, $firma_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Kasa hareketi başarıyla silindi'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kasa hareketi silinirken hata oluştu'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
