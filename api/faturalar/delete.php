<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'silme');

$id = $_POST['id'] ?? $_GET['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Fatura ID gerekli', 400);
}

$db->begin_transaction();

try {
    // Fatura bilgilerini al
    $fatura = $db->query("SELECT * FROM faturalar WHERE id = $id AND firma_id = $firma_id")->fetch_assoc();
    
    if (!$fatura) {
        json_error('Fatura bulunamadı', 404);
    }
    
    // Fatura kalemlerini al ve stok düzeltmesi yap
    $kalemler = $db->query("SELECT * FROM fatura_detaylari WHERE fatura_id = $id");
    
    while ($kalem = $kalemler->fetch_assoc()) {
        if ($fatura['fatura_tipi'] == 'alis') {
            // Alış faturası siliniyor - stok azalt
            $db->query("UPDATE urunler SET stok_miktari = stok_miktari - " . $kalem['miktar'] . " WHERE id = " . $kalem['urun_id']);
        } else {
            // Satış faturası siliniyor - stok artır
            $db->query("UPDATE urunler SET stok_miktari = stok_miktari + " . $kalem['miktar'] . " WHERE id = " . $kalem['urun_id']);
        }
    }
    
    // Cari bakiye düzeltmesi
    if ($fatura['fatura_tipi'] == 'alis') {
        $db->query("UPDATE cariler SET bakiye = bakiye + " . $fatura['toplam_tutar'] . " WHERE id = " . $fatura['cari_id']);
    } else {
        $db->query("UPDATE cariler SET bakiye = bakiye - " . $fatura['toplam_tutar'] . " WHERE id = " . $fatura['cari_id']);
    }
    
    // Fatura kalemlerini sil
    $db->query("DELETE FROM fatura_detaylari WHERE fatura_id = $id");
    
    // Faturayı sil
    $db->query("DELETE FROM faturalar WHERE id = $id");
    
    $db->commit();
    json_success('Fatura başarıyla silindi');
    
} catch (Exception $e) {
    $db->rollback();
    json_error('Fatura silinirken hata: ' . $e->getMessage(), 500);
}
?>

