<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('odemeler', 'yazma');

// Debug için
error_log("Ödeme create API çağrıldı");

$data = json_decode(file_get_contents('php://input'), true);
error_log("Gelen data: " . print_r($data, true));

$firma_id = get_firma_id();
error_log("Firma ID: " . $firma_id);
$fatura_id = $data['fatura_id'];
$tutar = floatval($data['tutar']);

// Faturayı kontrol et
$fatura = $db->query("SELECT * FROM faturalar WHERE id = $fatura_id AND firma_id = $firma_id")->fetch_assoc();

if (!$fatura) {
    json_error('Fatura bulunamadı', 404);
}

$kalan = floatval($fatura['toplam_tutar']) - floatval($fatura['odenen_tutar']);

if ($tutar > $kalan) {
    json_error('Ödeme tutarı kalan tutardan fazla olamaz', 400);
}

$db->begin_transaction();

try {
    // Ödeme kaydını ekle (odemeler tablosu oluşturmadık, faturayı güncelleyeceğiz)
    $yeni_odenen = floatval($fatura['odenen_tutar']) + $tutar;
    
    // Durum belirle
    if ($yeni_odenen >= floatval($fatura['toplam_tutar'])) {
        $durum = 'odendi';
    } else {
        $durum = 'kismi';
    }
    
    // Faturayı güncelle
    $stmt = $db->prepare("UPDATE faturalar SET odenen_tutar = ?, odeme_durumu = ? WHERE id = ?");
    $stmt->bind_param("dsi", $yeni_odenen, $durum, $fatura_id);
    $stmt->execute();
    
    // Kasa kaydı ekle (gelir veya gider)
    $islem_tipi = ($fatura['fatura_tipi'] == 'satis') ? 'gelir' : 'gider';
    $kategori = ($fatura['fatura_tipi'] == 'satis') ? 'Fatura Tahsilatı' : 'Fatura Ödemesi';
    $aciklama = "Fatura No: " . $fatura['fatura_no'] . " - " . ($data['aciklama'] ?? '');
    
    // Mevcut kasa bakiyesini hesapla
    $gelir = $db->query("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = $firma_id AND islem_tipi = 'gelir'")->fetch_assoc()['total'];
    $gider = $db->query("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = $firma_id AND islem_tipi = 'gider'")->fetch_assoc()['total'];
    
    $yeni_bakiye = $gelir - $gider;
    
    if ($islem_tipi == 'gelir') {
        $yeni_bakiye += $tutar;
    } else {
        $yeni_bakiye -= $tutar;
    }
    
    $stmt_kasa = $db->prepare("INSERT INTO kasa_hareketleri (firma_id, kullanici_id, islem_tipi, tarih, kategori, tutar, odeme_yontemi, aciklama, bakiye) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_kasa->bind_param("iisssdssd",
        $firma_id,
        $_SESSION['user_id'],
        $islem_tipi,
        $data['odeme_tarihi'],
        $kategori,
        $tutar,
        $data['odeme_yontemi'],
        $aciklama,
        $yeni_bakiye
    );
    $stmt_kasa->execute();
    
    // Cari bakiyeyi güncelle
    if ($fatura['fatura_tipi'] == 'alis') {
        // Alış faturası ödemesi - borç azalır (bakiye artar)
        $db->query("UPDATE cariler SET bakiye = bakiye + $tutar WHERE id = " . $fatura['cari_id']);
    } else {
        // Satış faturası tahsilatı - alacak azalır (bakiye azalır)
        $db->query("UPDATE cariler SET bakiye = bakiye - $tutar WHERE id = " . $fatura['cari_id']);
    }
    
    $db->commit();
    json_success('Ödeme başarıyla kaydedildi');
    
} catch (Exception $e) {
    error_log("Ödeme create hatası: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $db->rollback();
    json_error('Ödeme kaydedilirken hata: ' . $e->getMessage(), 500);
}
?>

