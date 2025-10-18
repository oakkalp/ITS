<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// JWT token kontrolü
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
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
} catch (Exception $e) {
    json_error('Geçersiz token', 401);
}

// Action parametresi
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleGetKasaHareketleri();
        break;
    case 'stats':
        handleGetKasaStats();
        break;
    case 'create':
        handleCreateKasaHareketi();
        break;
    case 'delete':
        handleDeleteKasaHareketi();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetKasaHareketleri() {
    global $db, $firma_id;
    
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
}

function handleGetKasaStats() {
    global $db, $firma_id;
    
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
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Gelir hesapla
        $gelir_query = "SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE $where_clause AND islem_tipi = 'gelir'";
        $stmt = $db->prepare($gelir_query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $gelir = $stmt->get_result()->fetch_assoc()['total'];
        
        // Gider hesapla
        $gider_query = "SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE $where_clause AND islem_tipi = 'gider'";
        $stmt = $db->prepare($gider_query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $gider = $stmt->get_result()->fetch_assoc()['total'];
        
        $bakiye = $gelir - $gider;
        
        // Bugün hareket sayısı (filtre uygulanmadan)
        $bugun = date('Y-m-d');
        $bugun_hareket_query = "SELECT COUNT(*) as c FROM kasa_hareketleri WHERE firma_id = ? AND tarih = ?";
        $stmt = $db->prepare($bugun_hareket_query);
        $stmt->bind_param("is", $firma_id, $bugun);
        $stmt->execute();
        $bugun_hareket = $stmt->get_result()->fetch_assoc()['c'];
        
        // Filtrelenmiş hareket sayısı
        $hareket_sayisi_query = "SELECT COUNT(*) as c FROM kasa_hareketleri WHERE $where_clause";
        $stmt = $db->prepare($hareket_sayisi_query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $hareket_sayisi = $stmt->get_result()->fetch_assoc()['c'];
        
        $stats = [
            'toplam_gelir' => $gelir,
            'toplam_gider' => $gider,
            'bakiye' => $bakiye,
            'bugun_hareket' => $bugun_hareket,
            'filtrelenmis_hareket' => $hareket_sayisi
        ];
        
        json_success('İstatistikler', $stats);
        
    } catch (Exception $e) {
        error_log("Kasa istatistikleri hatası: " . $e->getMessage());
        json_error('İstatistikler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleCreateKasaHareketi() {
    global $db, $firma_id, $kullanici_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $tarih = $data['tarih'] ?? date('Y-m-d');
        $islem_tipi = $data['islem_tipi'] ?? 'gelir';
        $kategori = $data['kategori'] ?? '';
        $tutar = floatval($data['tutar'] ?? 0);
        $odeme_yontemi = $data['odeme_yontemi'] ?? 'nakit';
        $aciklama = $data['aciklama'] ?? '';
        
        if (empty($kategori) || $tutar <= 0 || empty($aciklama)) {
            json_error('Kategori, tutar ve açıklama alanları gerekli', 400);
        }
        
        // Mevcut kasa bakiyesini hesapla
        $gelir_stmt = $db->prepare("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = ? AND islem_tipi = 'gelir'");
        $gelir_stmt->bind_param("i", $firma_id);
        $gelir_stmt->execute();
        $gelir_result = $gelir_stmt->get_result();
        $gelir = $gelir_result->fetch_assoc()['total'];
        
        $gider_stmt = $db->prepare("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = ? AND islem_tipi = 'gider'");
        $gider_stmt->bind_param("i", $firma_id);
        $gider_stmt->execute();
        $gider_result = $gider_stmt->get_result();
        $gider = $gider_result->fetch_assoc()['total'];
        
        $mevcut_bakiye = $gelir - $gider;
        
        // Yeni bakiye hesapla
        if ($islem_tipi == 'gelir') {
            $yeni_bakiye = $mevcut_bakiye + $tutar;
        } else {
            $yeni_bakiye = $mevcut_bakiye - $tutar;
        }
        
        $db->begin_transaction();
        
        // Kasa hareketi kaydet
        $stmt = $db->prepare("INSERT INTO kasa_hareketleri (firma_id, kullanici_id, islem_tipi, tarih, kategori, tutar, odeme_yontemi, aciklama, bakiye) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssdssd",
            $firma_id,
            $kullanici_id,
            $islem_tipi,
            $tarih,
            $kategori,
            $tutar,
            $odeme_yontemi,
            $aciklama,
            $yeni_bakiye
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Kasa hareketi kaydedilemedi: ' . $stmt->error);
        }
        
        $hareket_id = $db->insert_id;
        
        $db->commit();
        
        json_success('Kasa hareketi başarıyla eklendi', [
            'id' => $hareket_id,
            'bakiye' => $yeni_bakiye
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Kasa hareketi oluşturma hatası: " . $e->getMessage());
        json_error('Kasa hareketi kaydedilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleDeleteKasaHareketi() {
    global $db, $firma_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        json_error('Hareket ID gerekli', 400);
    }
    
    try {
        $hareket_id = intval($data['id']);
        
        // Hareketi kontrol et
        $check_stmt = $db->prepare("SELECT * FROM kasa_hareketleri WHERE id = ? AND firma_id = ?");
        $check_stmt->bind_param("ii", $hareket_id, $firma_id);
        $check_stmt->execute();
        $hareket = $check_stmt->get_result()->fetch_assoc();
        
        if (!$hareket) {
            json_error('Kasa hareketi bulunamadı', 404);
        }
        
        $db->begin_transaction();
        
        // Hareketi sil
        $delete_stmt = $db->prepare("DELETE FROM kasa_hareketleri WHERE id = ? AND firma_id = ?");
        $delete_stmt->bind_param("ii", $hareket_id, $firma_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception('Kasa hareketi silinemedi: ' . $delete_stmt->error);
        }
        
        $db->commit();
        
        json_success('Kasa hareketi başarıyla silindi');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Kasa hareketi silme hatası: " . $e->getMessage());
        json_error('Kasa hareketi silinirken hata oluştu: ' . $e->getMessage(), 500);
    }
}
?>