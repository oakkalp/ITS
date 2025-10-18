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
        // Fatura ID parametresi var mı kontrol et
        $faturaId = $_GET['id'] ?? null;
        
        if ($faturaId) {
            // Tek fatura detayı
            try {
                $query = "SELECT f.*, c.unvan as cari_unvan 
                          FROM faturalar f 
                          LEFT JOIN cariler c ON f.cari_id = c.id 
                          WHERE f.id = ? AND f.firma_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("ii", $faturaId, $firma_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Fatura bulunamadı'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                
                $fatura = $result->fetch_assoc();
                
                // Fatura detaylarını da al (ürünler)
                $detayQuery = "SELECT fd.*, u.urun_adi 
                               FROM fatura_detaylari fd 
                               LEFT JOIN urunler u ON fd.urun_id = u.id 
                               WHERE fd.fatura_id = ?";
                $detayStmt = $db->prepare($detayQuery);
                $detayStmt->bind_param("i", $faturaId);
                $detayStmt->execute();
                $detayResult = $detayStmt->get_result();
                
                $detaylar = [];
                while ($detay = $detayResult->fetch_assoc()) {
                    $detaylar[] = $detay;
                }
                
                $fatura['detaylar'] = $detaylar;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura detayı getirildi',
                    'data' => $fatura
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } else {
            // Fatura listesi
            try {
                $query = "SELECT f.*, c.unvan as cari_unvan 
                          FROM faturalar f 
                          LEFT JOIN cariler c ON f.cari_id = c.id 
                          WHERE f.firma_id = ? 
                          ORDER BY f.fatura_tarihi DESC";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $firma_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $faturalar = [];
                while ($row = $result->fetch_assoc()) {
                    $faturalar[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Faturalar listelendi',
                    'data' => $faturalar
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        break;
        
    case 'POST':
        // Yeni fatura ekle
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (empty($input['fatura_no']) || empty($input['fatura_tarihi'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Fatura numarası ve tarihi gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $fatura_no = $input['fatura_no'];
            $fatura_tarihi = $input['fatura_tarihi'];
            $fatura_tipi = $input['fatura_tipi'] ?? 'Satış';
            $cari_id = $input['cari_id'] ?? null;
            $toplam_tutar = floatval($input['toplam_tutar'] ?? 0);
            $kdv_toplam = floatval($input['kdv_toplam'] ?? 0);
            $genel_toplam = floatval($input['genel_toplam'] ?? 0);
            $odeme_durumu = $input['odeme_durumu'] ?? 'Bekliyor';
            $aciklama = $input['aciklama'] ?? '';
            
            $stmt = $db->prepare("INSERT INTO faturalar (firma_id, fatura_no, fatura_tarihi, fatura_tipi, cari_id, toplam_tutar, kdv_toplam, genel_toplam, odeme_durumu, aciklama, kullanici_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssidddssi", 
                $firma_id,
                $fatura_no,
                $fatura_tarihi,
                $fatura_tipi,
                $cari_id,
                $toplam_tutar,
                $kdv_toplam,
                $genel_toplam,
                $odeme_durumu,
                $aciklama,
                $user['id']
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura başarıyla eklendi',
                    'data' => ['id' => $db->insert_id]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatura eklenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'PUT':
        // Fatura güncelle
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $id = intval($input['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçerli ID gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $fatura_no = $input['fatura_no'] ?? '';
            $fatura_tarihi = $input['fatura_tarihi'] ?? '';
            $fatura_tipi = $input['fatura_tipi'] ?? 'Satış';
            $cari_id = $input['cari_id'] ?? null;
            $toplam_tutar = floatval($input['toplam_tutar'] ?? 0);
            $kdv_toplam = floatval($input['kdv_toplam'] ?? 0);
            $genel_toplam = floatval($input['genel_toplam'] ?? 0);
            $odeme_durumu = $input['odeme_durumu'] ?? 'Bekliyor';
            $aciklama = $input['aciklama'] ?? '';
            
            $stmt = $db->prepare("UPDATE faturalar SET fatura_no = ?, fatura_tarihi = ?, fatura_tipi = ?, cari_id = ?, toplam_tutar = ?, kdv_toplam = ?, genel_toplam = ?, odeme_durumu = ?, aciklama = ? WHERE id = ? AND firma_id = ?");
            $stmt->bind_param("ssssidddssii",
                $fatura_no,
                $fatura_tarihi,
                $fatura_tipi,
                $cari_id,
                $toplam_tutar,
                $kdv_toplam,
                $genel_toplam,
                $odeme_durumu,
                $aciklama,
                $id,
                $firma_id
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura başarıyla güncellendi'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatura güncellenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'DELETE':
        // Fatura sil
        try {
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçerli ID gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM faturalar WHERE id = ? AND firma_id = ?");
            $stmt->bind_param("ii", $id, $firma_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Fatura başarıyla silindi'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Fatura silinirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen metod'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
}
?>