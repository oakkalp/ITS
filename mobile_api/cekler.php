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
        // Çek listesi
        try {
            $query = "SELECT c.*, car.unvan as cari_unvan 
                      FROM cekler c 
                      LEFT JOIN cariler car ON c.cari_id = car.id 
                      WHERE c.firma_id = ? 
                      ORDER BY c.vade_tarihi ASC";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cekler = [];
            while ($row = $result->fetch_assoc()) {
                $cekler[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Çekler listelendi',
                'data' => $cekler
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'POST':
        // Yeni çek ekle
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (empty($input['cek_tipi']) || empty($input['cek_no']) || empty($input['tutar'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Çek tipi, çek numarası ve tutar gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $cek_tipi = $input['cek_tipi']; // 'alinan' veya 'verilen'
            $cek_no = $input['cek_no'];
            $banka = $input['banka'] ?? '';
            $sube = $input['sube'] ?? '';
            $tutar = floatval($input['tutar'] ?? 0);
            $vade_tarihi = $input['vade_tarihi'] ?? null;
            $durum = $input['durum'] ?? 'portfoy';
            $aciklama = $input['aciklama'] ?? '';
            
            // Cari dışı çek kontrolü
            $cari_disi_cek = isset($input['cari_disi_cek']) && $input['cari_disi_cek'] == 1 ? 1 : 0;
            $cari_id = null;
            $cari_disi_kisi = null;
            $cek_kaynagi = null;
            
            if ($cari_disi_cek) {
                // Cari dışı çek
                $cari_disi_kisi = $input['cari_disi_kisi'] ?? '';
                $cek_kaynagi = $input['cek_kaynagi'] ?? '';
            } else {
                // Normal cari çek
                $cari_id = !empty($input['cari_id']) ? $input['cari_id'] : null;
            }
            
            $stmt = $db->prepare("INSERT INTO cekler (firma_id, cek_tipi, cek_no, cari_id, tutar, banka_adi, sube, vade_tarihi, durum, aciklama, cari_disi_cek, cari_disi_kisi, cek_kaynagi, kullanici_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississsssssssi", 
                $firma_id,
                $cek_tipi,
                $cek_no,
                $cari_id,
                $tutar,
                $banka,
                $sube,
                $vade_tarihi,
                $durum,
                $aciklama,
                $cari_disi_cek,
                $cari_disi_kisi,
                $cek_kaynagi,
                $user['id']
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Çek başarıyla eklendi',
                    'data' => ['id' => $db->insert_id]
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Çek eklenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'PUT':
        // Çek güncelle
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
            
            $cek_tipi = $input['cek_tipi'] ?? '';
            $cek_no = $input['cek_no'] ?? '';
            $banka = $input['banka'] ?? '';
            $sube = $input['sube'] ?? '';
            $tutar = floatval($input['tutar'] ?? 0);
            $vade_tarihi = $input['vade_tarihi'] ?? '';
            $durum = $input['durum'] ?? 'portfoy';
            $aciklama = $input['aciklama'] ?? '';
            
            // Cari dışı çek kontrolü
            $cari_disi_cek = isset($input['cari_disi_cek']) && $input['cari_disi_cek'] == 1 ? 1 : 0;
            $cari_id = null;
            $cari_disi_kisi = null;
            $cek_kaynagi = null;
            
            if ($cari_disi_cek) {
                // Cari dışı çek
                $cari_disi_kisi = $input['cari_disi_kisi'] ?? '';
                $cek_kaynagi = $input['cek_kaynagi'] ?? '';
            } else {
                // Normal cari çek
                $cari_id = !empty($input['cari_id']) ? $input['cari_id'] : null;
            }
            
            $stmt = $db->prepare("UPDATE cekler SET cek_tipi = ?, cek_no = ?, cari_id = ?, tutar = ?, banka_adi = ?, sube = ?, vade_tarihi = ?, durum = ?, aciklama = ?, cari_disi_cek = ?, cari_disi_kisi = ?, cek_kaynagi = ? WHERE id = ? AND firma_id = ?");
            $stmt->bind_param("ssisssssssssii",
                $cek_tipi,
                $cek_no,
                $cari_id,
                $tutar,
                $banka,
                $sube,
                $vade_tarihi,
                $durum,
                $aciklama,
                $cari_disi_cek,
                $cari_disi_kisi,
                $cek_kaynagi,
                $id,
                $firma_id
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Çek başarıyla güncellendi'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Çek güncellenirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'DELETE':
        // Çek sil
        try {
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçerli ID gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM cekler WHERE id = ? AND firma_id = ?");
            $stmt->bind_param("ii", $id, $firma_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Çek başarıyla silindi'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Çek silinirken hata oluştu: ' . $stmt->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
