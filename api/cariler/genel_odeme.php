<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('cariler', 'yazma');

$data = json_decode(file_get_contents('php://input'), true);
$firma_id = get_firma_id();

if (!$data || !isset($data['cari_id']) || !isset($data['tutar']) || !isset($data['tip'])) {
    json_error('Gerekli veriler eksik', 400);
}

$cari_id = $data['cari_id'];
$tutar = floatval($data['tutar']);
$tip = $data['tip']; // 'odeme' veya 'tahsilat'
$tarih = $data['tarih'] ?? date('Y-m-d');
$odeme_yontemi = $data['odeme_yontemi'] ?? 'nakit';
$aciklama = $data['aciklama'] ?? '';

// Cari kontrolü
$cari = $db->query("SELECT * FROM cariler WHERE id = $cari_id AND firma_id = $firma_id")->fetch_assoc();
if (!$cari) {
    json_error('Cari bulunamadı', 404);
}

$db->begin_transaction();

try {
    // Kasa hareketi ekle
    $islem_tipi = ($tip == 'tahsilat') ? 'gelir' : 'gider';
    $kategori = ($tip == 'tahsilat') ? 'Genel Tahsilat' : 'Genel Ödeme';
    $aciklama_kasa = "Cari: " . $cari['unvan'] . " - " . $aciklama;
    
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
        $tarih,
        $kategori,
        $tutar,
        $odeme_yontemi,
        $aciklama_kasa,
        $yeni_bakiye
    );
    $stmt_kasa->execute();
    
    // Cari bakiyeyi güncelle
    if ($tip == 'tahsilat') {
        // Tahsilat - alacak azalır (bakiye azalır)
        $db->query("UPDATE cariler SET bakiye = bakiye - $tutar WHERE id = $cari_id");
    } else {
        // Ödeme - borç azalır (bakiye artar)
        $db->query("UPDATE cariler SET bakiye = bakiye + $tutar WHERE id = $cari_id");
    }
    
    $db->commit();
    
    $mesaj = ($tip == 'tahsilat') ? 'Tahsilat başarıyla kaydedildi' : 'Ödeme başarıyla kaydedildi';
    json_success($mesaj);
    
} catch (Exception $e) {
    error_log("Genel ödeme/tahsilat hatası: " . $e->getMessage());
    $db->rollback();
    json_error('İşlem sırasında hata oluştu: ' . $e->getMessage(), 500);
}
?>
