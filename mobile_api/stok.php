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
    echo json_encode(['success' => false, 'message' => 'Config hatası: ' . $e->getMessage()]);
    exit;
}

// Basit authentication (kullanıcı adı kontrolü)
$username = $_GET['username'] ?? $_POST['username'] ?? '';
if (empty($username)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli']);
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
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı']);
        exit;
    }

    $user = $result->fetch_assoc();
    $firma_id = $user['firma_id'];
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı kontrolü hatası: ' . $e->getMessage()]);
    exit;
}

// Method'a göre işlem yap
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Stok listesi
        $query = "SELECT * FROM urunler WHERE firma_id = ? ORDER BY urun_adi ASC";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stoklar = [];
        while ($row = $result->fetch_assoc()) {
            $stoklar[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Stok listelendi',
            'data' => $stoklar
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
        
    case 'POST':
        // Yeni ürün ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Debug için input'u logla
        error_log("POST Request Input: " . print_r($input, true));
        
        if (!$input || empty($input['urun_adi'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün adı gerekli']);
            exit;
        }
        
        try {
            $urun_adi = $input['urun_adi'];
            $kategori = $input['kategori'] ?? '';
            $stok = $input['stok'] ?? 0;
            $min_stok = $input['min_stok'] ?? 0;
            $fiyat = $input['fiyat'] ?? 0;
            
            $stmt = $db->prepare("INSERT INTO urunler (firma_id, urun_adi, kategori, stok_miktari, kritik_stok, satis_fiyati) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issddd", 
                $firma_id,
                $urun_adi,
                $kategori,
                $stok,
                $min_stok,
                $fiyat
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ürün başarıyla eklendi',
                    'data' => ['id' => $db->insert_id]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Ürün eklenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Ürün güncelle
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz JSON: ' . json_last_error_msg()]);
                exit;
            }
            
            // Debug için input'u logla
            error_log("PUT Request Input: " . print_r($input, true));
            
            $id = intval($input['id'] ?? 0);
            
            if ($id <= 0 || empty($input['urun_adi'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID ve ürün adı gerekli']);
                exit;
            }
            
            $urun_adi = $input['urun_adi'];
            $kategori = $input['kategori'] ?? '';
            $stok = $input['stok'] ?? 0;
            $min_stok = $input['min_stok'] ?? 0;
            $fiyat = $input['fiyat'] ?? 0;
            
            $stmt = $db->prepare("UPDATE urunler SET urun_adi = ?, kategori = ?, stok_miktari = ?, kritik_stok = ?, satis_fiyati = ? WHERE id = ? AND firma_id = ?");
            $stmt->bind_param("ssdddii",
                $urun_adi,
                $kategori,
                $stok,
                $min_stok,
                $fiyat,
                $id,
                $firma_id
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ürün başarıyla güncellendi'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Ürün güncellenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Ürün sil
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID gerekli']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM urunler WHERE id = ? AND firma_id = ?");
        $stmt->bind_param("ii", $id, $firma_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Ürün başarıyla silindi'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ürün silinirken hata oluştu']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
