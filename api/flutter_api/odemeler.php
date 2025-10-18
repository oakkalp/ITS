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
        handleGetFaturalar();
        break;
    case 'stats':
        handleGetStats();
        break;
    case 'create':
        handleCreateOdeme();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetFaturalar() {
    global $db, $firma_id;
    
    try {
        // Ödenmemiş veya kısmi ödemeli faturaları getir
        $query = "SELECT f.*, c.unvan as cari_unvan FROM faturalar f 
                  LEFT JOIN cariler c ON f.cari_id = c.id 
                  WHERE f.firma_id = ? AND f.odeme_durumu != 'odendi'
                  ORDER BY f.fatura_tarihi DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $faturalar = [];
        while ($row = $result->fetch_assoc()) {
            $faturalar[] = $row;
        }

        json_success('Faturalar listelendi', $faturalar);
        
    } catch (Exception $e) {
        error_log("Faturalar listesi hatası: " . $e->getMessage());
        json_error('Faturalar listelenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleGetStats() {
    global $db, $firma_id;
    
    try {
        $odenmemiş = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND odeme_durumu = 'bekliyor'")->fetch_assoc()['c'];
        $kismi = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND odeme_durumu = 'kismi'")->fetch_assoc()['c'];
        $odenen = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND odeme_durumu = 'odendi'")->fetch_assoc()['c'];

        json_success('İstatistikler', [
            'odenmemiş' => $odenmemiş,
            'kismi' => $kismi,
            'odenen' => $odenen
        ]);
        
    } catch (Exception $e) {
        error_log("İstatistik hatası: " . $e->getMessage());
        json_error('İstatistikler alınırken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleCreateOdeme() {
    global $db, $firma_id, $kullanici_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $fatura_id = $data['fatura_id'] ?? null;
        $tutar = floatval($data['tutar'] ?? 0);
        $odeme_tarihi = $data['odeme_tarihi'] ?? date('Y-m-d');
        $odeme_yontemi = $data['odeme_yontemi'] ?? 'nakit';
        $aciklama = $data['aciklama'] ?? '';
        
        if (!$fatura_id || $tutar <= 0) {
            json_error('Geçersiz fatura ID veya tutar', 400);
        }
        
        // Faturayı kontrol et
        $fatura_stmt = $db->prepare("SELECT * FROM faturalar WHERE id = ? AND firma_id = ?");
        $fatura_stmt->bind_param("ii", $fatura_id, $firma_id);
        $fatura_stmt->execute();
        $fatura_result = $fatura_stmt->get_result();
        $fatura = $fatura_result->fetch_assoc();
        
        if (!$fatura) {
            json_error('Fatura bulunamadı', 404);
        }
        
        $kalan = floatval($fatura['toplam_tutar']) - floatval($fatura['odenen_tutar']);
        
        if ($tutar > $kalan) {
            json_error('Ödeme tutarı kalan tutardan fazla olamaz', 400);
        }
        
        $db->begin_transaction();
        
        // Ödeme kaydını ekle
        $yeni_odenen = floatval($fatura['odenen_tutar']) + $tutar;
        
        // Durum belirle
        if ($yeni_odenen >= floatval($fatura['toplam_tutar'])) {
            $durum = 'odendi';
        } else {
            $durum = 'kismi';
        }
        
        // Faturayı güncelle
        $update_stmt = $db->prepare("UPDATE faturalar SET odenen_tutar = ?, odeme_durumu = ? WHERE id = ?");
        $update_stmt->bind_param("dsi", $yeni_odenen, $durum, $fatura_id);
        $update_stmt->execute();
        
        // Kasa kaydı ekle (gelir veya gider)
        $islem_tipi = ($fatura['fatura_tipi'] == 'satis') ? 'gelir' : 'gider';
        $kategori = ($fatura['fatura_tipi'] == 'satis') ? 'Fatura Tahsilatı' : 'Fatura Ödemesi';
        $kasa_aciklama = "Fatura No: " . $fatura['fatura_no'] . " - " . $aciklama;
        
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
        
        $yeni_bakiye = $gelir - $gider;
        
        if ($islem_tipi == 'gelir') {
            $yeni_bakiye += $tutar;
        } else {
            $yeni_bakiye -= $tutar;
        }
        
        $kasa_stmt = $db->prepare("INSERT INTO kasa_hareketleri (firma_id, kullanici_id, islem_tipi, tarih, kategori, tutar, odeme_yontemi, aciklama, bakiye) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $kasa_stmt->bind_param("iisssdssd",
            $firma_id,
            $kullanici_id,
            $islem_tipi,
            $odeme_tarihi,
            $kategori,
            $tutar,
            $odeme_yontemi,
            $kasa_aciklama,
            $yeni_bakiye
        );
        $kasa_stmt->execute();
        
        // Cari bakiyeyi güncelle
        if ($fatura['fatura_tipi'] == 'alis') {
            // Alış faturası ödemesi - borç azalır (bakiye artar)
            $cari_stmt = $db->prepare("UPDATE cariler SET bakiye = bakiye + ? WHERE id = ?");
            $cari_stmt->bind_param("di", $tutar, $fatura['cari_id']);
            $cari_stmt->execute();
        } else {
            // Satış faturası tahsilatı - alacak azalır (bakiye azalır)
            $cari_stmt = $db->prepare("UPDATE cariler SET bakiye = bakiye - ? WHERE id = ?");
            $cari_stmt->bind_param("di", $tutar, $fatura['cari_id']);
            $cari_stmt->execute();
        }
        
        $db->commit();
        json_success('Ödeme başarıyla kaydedildi');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Ödeme create hatası: " . $e->getMessage());
        json_error('Ödeme kaydedilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}
?>