<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'guncelleme');

// Buffer'ı temizle
ob_clean();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug log
    error_log("Update API çağrıldı - Data: " . print_r($data, true));
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_error('Geçersiz JSON verisi', 400);
    }
    
    $firma_id = get_firma_id();
    $id = $data['id'] ?? null;

    if (!$id) {
        json_error('Ürün ID gerekli', 400);
    }

    // Zorunlu alanları kontrol et
    if (empty($data['urun_adi'])) {
        json_error('Ürün adı gerekli', 400);
    }

    // Değerleri değişkenlere ata
    $urun_kodu = $data['urun_kodu'] ?? '';
    $urun_adi = $data['urun_adi'];
    $kategori = $data['kategori'] ?? '';
    $birim = $data['birim'] ?? '';
    $stok_miktari = floatval($data['stok_miktari'] ?? 0);
    $alis_fiyati = floatval($data['alis_fiyati'] ?? 0);
    $satis_fiyati = floatval($data['satis_fiyati'] ?? 0);
    $barkod = $data['barkod'] ?? '';
    $aciklama = $data['aciklama'] ?? '';
    $aktif = intval($data['aktif'] ?? 1);

    // Debug log
    error_log("Update parametreleri - ID: $id, Firma ID: $firma_id, Ürün Adı: $urun_adi");

    // Önce tabloda barkod ve aciklama sütunları var mı kontrol et
    $result = $db->query("SHOW COLUMNS FROM urunler LIKE 'barkod'");
    $hasBarkod = $result->num_rows > 0;

    $result = $db->query("SHOW COLUMNS FROM urunler LIKE 'aciklama'");
    $hasAciklama = $result->num_rows > 0;

    // SQL sorgusunu dinamik olarak oluştur
    $setClause = "urun_kodu = ?, urun_adi = ?, kategori = ?, birim = ?, stok_miktari = ?, alis_fiyati = ?, satis_fiyati = ?, aktif = ?";
    $bindTypes = "ssssdddi";
    $bindParams = [$urun_kodu, $urun_adi, $kategori, $birim, $stok_miktari, $alis_fiyati, $satis_fiyati, $aktif];

    if ($hasBarkod) {
        $setClause .= ", barkod = ?";
        $bindTypes .= "s";
        $bindParams[] = $barkod;
    }

    if ($hasAciklama) {
        $setClause .= ", aciklama = ?";
        $bindTypes .= "s";
        $bindParams[] = $aciklama;
    }

    $bindTypes .= "ii"; // id ve firma_id için
    $bindParams[] = $id;
    $bindParams[] = $firma_id;

    $sql = "UPDATE urunler SET $setClause WHERE id = ? AND firma_id = ?";
    error_log("Update SQL: $sql");
    error_log("Bind types: $bindTypes");

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        error_log("Prepare hatası: " . $db->error);
        json_error('SQL prepare hatası: ' . $db->error, 500);
    }

    // bind_param'ı dinamik olarak çağır
    $stmt->bind_param($bindTypes, ...$bindParams);

    if ($stmt->execute()) {
        json_success('Ürün başarıyla güncellendi');
    } else {
        error_log("Execute hatası: " . $stmt->error);
        json_error('Ürün güncellenirken hata oluştu: ' . $stmt->error, 500);
    }
    
} catch (Exception $e) {
    error_log("Ürün update hatası: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    json_error('Ürün güncellenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>

