<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// Buffer'ı temizle
ob_clean();

// Debug için
error_log("Stoklar Flutter API çağrıldı");

// Web paneli için session kontrolü
if (isset($_SESSION['user_id'])) {
    // Web panelinden gelen istek - session kullan
    $firma_id = get_firma_id();
    error_log("Web paneli session kullanılıyor, Firma ID: " . $firma_id);
} else {
    // Flutter uygulamasından gelen istek - JWT token kontrolü
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
        error_log("Authorization header bulunamadı: " . print_r($headers, true));
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header gerekli']);
        exit;
    }

    $token = substr($auth_header, 7); // "Bearer " kısmını çıkar
    error_log("Token alındı: " . substr($token, 0, 20) . "...");

    // Token'ı decode et ve kullanıcı bilgilerini al
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        error_log("Token decode edildi: " . print_r($decoded, true));
        
        // Session'ı manuel olarak ayarla
        $_SESSION['user_id'] = $decoded->user_id;
        $_SESSION['firma_id'] = $decoded->firma_id;
        $_SESSION['rol'] = $decoded->rol;
        
        $firma_id = $decoded->firma_id;
        error_log("Firma ID token'dan alındı: " . $firma_id);
        
    } catch (Exception $e) {
        error_log("Token decode hatası: " . $e->getMessage());
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

error_log("Method: $method, Action: $action");

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                handleGetStoklar();
                break;
            case 'detail':
                handleGetStokDetail();
                break;
            case 'generate_code':
                handleGenerateCode();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz action']);
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'create':
                handleCreateStok();
                break;
            case 'update':
                handleUpdateStok();
                break;
            case 'delete':
                handleDeleteStok();
                break;
            case 'manuel_hareket':
                handleManuelHareket();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz action']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function handleGetStoklar() {
    global $db, $firma_id;
    
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 25);
        $offset = ($page - 1) * $limit;
        
        // Sayfalama için toplam kayıt sayısını al
        $countQuery = "SELECT COUNT(*) as total FROM urunler WHERE firma_id = ?";
        $countStmt = $db->prepare($countQuery);
        $countStmt->bind_param("i", $firma_id);
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        
        // Ürünleri getir
        $query = "SELECT * FROM urunler WHERE firma_id = ? ORDER BY urun_adi ASC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $firma_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stoklar = [];
        while ($row = $result->fetch_assoc()) {
            $stoklar[] = [
                'id' => (int)$row['id'],
                'urun_kodu' => $row['urun_kodu'],
                'urun_adi' => $row['urun_adi'],
                'kategori' => $row['kategori'],
                'birim' => $row['birim'],
                'stok_miktari' => floatval($row['stok_miktari']),
                'kritik_stok' => floatval($row['kritik_stok']),
                'alis_fiyati' => floatval($row['alis_fiyati']),
                'satis_fiyati' => floatval($row['satis_fiyati']),
                'kdv_orani' => (int)$row['kdv_orani'],
                'aktif' => (int)$row['aktif'],
                'olusturma_tarihi' => $row['olusturma_tarihi']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Stoklar getirildi',
            'data' => [
                'stoklar' => $stoklar,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Stoklar getirme hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}

function handleGetStokDetail() {
    global $db, $firma_id;
    
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli']);
            return;
        }
        
        $query = "SELECT * FROM urunler WHERE id = ? AND firma_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $id, $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'message' => 'Ürün detayı getirildi',
                'data' => [
                    'id' => (int)$row['id'],
                    'urun_kodu' => $row['urun_kodu'],
                    'urun_adi' => $row['urun_adi'],
                    'kategori' => $row['kategori'],
                    'birim' => $row['birim'],
                    'stok_miktari' => floatval($row['stok_miktari']),
                    'kritik_stok' => floatval($row['kritik_stok']),
                    'alis_fiyati' => floatval($row['alis_fiyati']),
                    'satis_fiyati' => floatval($row['satis_fiyati']),
                    'kdv_orani' => (int)$row['kdv_orani'],
                    'aktif' => (int)$row['aktif'],
                    'olusturma_tarihi' => $row['olusturma_tarihi']
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
        }
        
    } catch (Exception $e) {
        error_log("Ürün detay getirme hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}

function handleGenerateCode() {
    global $db, $firma_id;
    
    try {
        // Mevcut kodları al
        $query = "SELECT urun_kodu FROM urunler WHERE firma_id = ? AND urun_kodu IS NOT NULL AND urun_kodu != ''";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $mevcutKodlar = [];
        while ($row = $result->fetch_assoc()) {
            $mevcutKodlar[] = $row['urun_kodu'];
        }
        
        // Yeni kod oluştur
        $urunKodu = generateUrunKodu($mevcutKodlar);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ürün kodu oluşturuldu',
            'data' => [
                'urun_kodu' => $urunKodu
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Ürün kodu oluşturma hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}

function generateUrunKodu($mevcutKodlar) {
    $prefix = 'U';
    $counter = 1;
    
    do {
        $urunKodu = $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
        $counter++;
    } while (in_array($urunKodu, $mevcutKodlar));
    
    return $urunKodu;
}

function handleCreateStok() {
    global $db, $firma_id;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'JSON parse hatası: ' . json_last_error_msg()]);
            return;
        }
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Veri gönderilmedi']);
            return;
        }
        
        // Zorunlu alan kontrolü
        if (empty($data['urun_adi'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün adı zorunludur']);
            return;
        }
        
        if (empty($data['birim'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Birim zorunludur']);
            return;
        }
        
        // Değerleri al
        $urun_kodu = $data['urun_kodu'] ?? null;
        $urun_adi = $data['urun_adi'];
        $kategori = $data['kategori'] ?? null;
        $birim = $data['birim'];
        $stok_miktari = floatval($data['stok_miktari'] ?? 0);
        $kritik_stok = floatval($data['kritik_stok'] ?? 0);
        $alis_fiyati = floatval($data['alis_fiyati'] ?? 0);
        $satis_fiyati = floatval($data['satis_fiyati'] ?? 0);
        $kdv_orani = intval($data['kdv_orani'] ?? 20);
        $aktif = intval($data['aktif'] ?? 1);
        
        // Ürün kodu yoksa otomatik oluştur
        if (empty($urun_kodu)) {
            $query = "SELECT urun_kodu FROM urunler WHERE firma_id = ? AND urun_kodu IS NOT NULL AND urun_kodu != ''";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $mevcutKodlar = [];
            while ($row = $result->fetch_assoc()) {
                $mevcutKodlar[] = $row['urun_kodu'];
            }
            
            $urun_kodu = generateUrunKodu($mevcutKodlar);
        }
        
        // INSERT sorgusu
        $query = "INSERT INTO urunler (firma_id, urun_kodu, urun_adi, kategori, birim, stok_miktari, kritik_stok, alis_fiyati, satis_fiyati, kdv_orani, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("issssddddii", $firma_id, $urun_kodu, $urun_adi, $kategori, $birim, $stok_miktari, $kritik_stok, $alis_fiyati, $satis_fiyati, $kdv_orani, $aktif);
        
        if ($stmt->execute()) {
            $urunId = $db->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Ürün başarıyla eklendi',
                'data' => [
                    'id' => $urunId,
                    'urun_kodu' => $urun_kodu
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ürün eklenirken hata oluştu: ' . $db->error]);
        }
        
    } catch (Exception $e) {
        error_log("Ürün oluşturma hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}

function handleUpdateStok() {
    global $db, $firma_id;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'JSON parse hatası: ' . json_last_error_msg()]);
            return;
        }
        
        $id = $data['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli']);
            return;
        }
        
        // Zorunlu alanları kontrol et
        if (empty($data['urun_adi'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün adı gerekli']);
            return;
        }
        
        // Değerleri al
        $urun_kodu = $data['urun_kodu'] ?? '';
        $urun_adi = $data['urun_adi'];
        $kategori = $data['kategori'] ?? '';
        $birim = $data['birim'] ?? '';
        $stok_miktari = floatval($data['stok_miktari'] ?? 0);
        $kritik_stok = floatval($data['kritik_stok'] ?? 0);
        $alis_fiyati = floatval($data['alis_fiyati'] ?? 0);
        $satis_fiyati = floatval($data['satis_fiyati'] ?? 0);
        $kdv_orani = intval($data['kdv_orani'] ?? 20);
        $aktif = intval($data['aktif'] ?? 1);
        
        // UPDATE sorgusu
        $query = "UPDATE urunler SET urun_kodu = ?, urun_adi = ?, kategori = ?, birim = ?, stok_miktari = ?, kritik_stok = ?, alis_fiyati = ?, satis_fiyati = ?, kdv_orani = ?, aktif = ? WHERE id = ? AND firma_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssssdddiisi", $urun_kodu, $urun_adi, $kategori, $birim, $stok_miktari, $kritik_stok, $alis_fiyati, $satis_fiyati, $kdv_orani, $aktif, $id, $firma_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ürün başarıyla güncellendi'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ürün güncellenirken hata oluştu: ' . $db->error]);
        }
        
    } catch (Exception $e) {
        error_log("Ürün güncelleme hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}

function handleDeleteStok() {
    global $db, $firma_id;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'JSON parse hatası: ' . json_last_error_msg()]);
            return;
        }
        
        $id = $data['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli']);
            return;
        }
        
        // DELETE sorgusu
        $query = "DELETE FROM urunler WHERE id = ? AND firma_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $id, $firma_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ürün başarıyla silindi'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ürün silinirken hata oluştu: ' . $db->error]);
        }
        
    } catch (Exception $e) {
        error_log("Ürün silme hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}

function handleManuelHareket() {
    global $db, $firma_id;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'JSON parse hatası: ' . json_last_error_msg()]);
            return;
        }
        
        // Zorunlu alan kontrolü
        if (empty($data['urun_id']) || empty($data['hareket_tipi']) || empty($data['miktar'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ürün ID, hareket tipi ve miktar zorunludur']);
            return;
        }
        
        $urun_id = intval($data['urun_id']);
        $hareket_tipi = $data['hareket_tipi'];
        $miktar = floatval($data['miktar']);
        $birim_fiyat = floatval($data['birim_fiyat'] ?? 0);
        $belge_no = $data['belge_no'] ?? '';
        $aciklama = $data['aciklama'] ?? '';
        
        // Ürünün mevcut stok miktarını al
        $stmt = $db->prepare("SELECT stok_miktari FROM urunler WHERE id = ? AND firma_id = ?");
        $stmt->bind_param("ii", $urun_id, $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı']);
            return;
        }
        
        $urun = $result->fetch_assoc();
        $eski_stok = floatval($urun['stok_miktari']);
        
        // Yeni stok miktarını hesapla
        if ($hareket_tipi === 'manuel_giris') {
            $yeni_stok = $eski_stok + $miktar;
        } elseif ($hareket_tipi === 'manuel_cikis') {
            $yeni_stok = $eski_stok - $miktar;
            if ($yeni_stok < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Stok miktarı negatif olamaz']);
                return;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz hareket tipi']);
            return;
        }
        
        $db->begin_transaction();
        
        try {
            // Ürün stok miktarını güncelle
            $stmt = $db->prepare("UPDATE urunler SET stok_miktari = ? WHERE id = ? AND firma_id = ?");
            $stmt->bind_param("dii", $yeni_stok, $urun_id, $firma_id);
            $stmt->execute();
            
            // Stok hareketi kaydı ekle (eğer tablo varsa)
            $result = $db->query("SHOW TABLES LIKE 'stok_hareketleri'");
            if ($result->num_rows > 0) {
                $stmt = $db->prepare("INSERT INTO stok_hareketleri (firma_id, urun_id, hareket_tipi, miktar, birim_fiyat, belge_no, aciklama, eski_stok, yeni_stok, tarih) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("iisddssdd", $firma_id, $urun_id, $hareket_tipi, $miktar, $birim_fiyat, $belge_no, $aciklama, $eski_stok, $yeni_stok);
                $stmt->execute();
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Manuel stok hareketi başarıyla kaydedildi',
                'data' => [
                    'eski_stok' => $eski_stok,
                    'yeni_stok' => $yeni_stok,
                    'hareket_miktari' => $miktar
                ]
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Manuel stok hareketi hatası: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sunucu hatası: ' . $e->getMessage()]);
    }
}
?>