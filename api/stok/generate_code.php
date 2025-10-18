<?php
require_once '../../config.php';
require_once '../../includes/auth.php';

// Sadece GET isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece GET istekleri kabul edilir']);
    exit;
}

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['firma_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
    exit;
}

// Buffer'ı temizle
while (ob_get_level()) {
    ob_end_clean();
}

// JSON header'ı ayarla
header('Content-Type: application/json; charset=utf-8');

try {
    // Mevcut ürün kodlarını al
    $query = "SELECT urun_kodu FROM urunler WHERE firma_id = ? AND urun_kodu IS NOT NULL AND urun_kodu != '' ORDER BY id DESC LIMIT 100";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $_SESSION['firma_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $urunKodlari = [];
    while ($row = $result->fetch_assoc()) {
        $urunKodlari[] = $row['urun_kodu'];
    }
    
    // Debug log
    error_log("Generate Code Debug - Firma ID: " . $_SESSION['firma_id'] . ", Kod sayısı: " . count($urunKodlari));
    
    // Yeni ürün kodu oluştur
    $yeniUrunKodu = generateUrunKodu($_SESSION['firma_id'], $urunKodlari);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'urun_kodu' => $yeniUrunKodu,
            'mevcut_kodlar' => $urunKodlari
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Ürün kodu oluşturma hatası: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Ürün kodu oluşturulamadı: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function generateUrunKodu($firmaId, $mevcutKodlar = null) {
    // Firma ID'sine göre prefix oluştur
    $prefix = 'U' . str_pad($firmaId, 3, '0', STR_PAD_LEFT);
    
    // Bugünün tarihini al (YYMMDD formatında)
    $tarih = date('ymd');
    
    // Mevcut kodları güvenli hale getir
    if (!is_array($mevcutKodlar)) {
        $mevcutKodlar = [];
    }
    
    // Bugün oluşturulan ürün sayısını bul
    $bugunKodlari = array_filter($mevcutKodlar, function($kod) use ($prefix, $tarih) {
        return is_string($kod) && strpos($kod, $prefix . $tarih) === 0;
    });
    
    $siraNo = count($bugunKodlari) + 1;
    
    // Format: U00120250115001 (U + FirmaID + Tarih + SıraNo)
    return $prefix . $tarih . str_pad($siraNo, 3, '0', STR_PAD_LEFT);
}
?>
