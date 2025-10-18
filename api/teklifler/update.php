<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'yazma');

$data = json_decode(file_get_contents('php://input'), true);
$firma_id = get_firma_id();

if (!$data || !isset($data['id'])) {
    json_error('Geçersiz veri', 400);
}

$teklif_id = $data['id'];

// Teklifin sahibi kontrolü
$stmt_check = $db->prepare("SELECT id FROM teklifler WHERE id = ? AND firma_id = ?");
$stmt_check->bind_param("ii", $teklif_id, $firma_id);
$stmt_check->execute();
if (!$stmt_check->get_result()->fetch_assoc()) {
    json_error('Teklif bulunamadı veya yetkiniz yok', 404);
}

$db->begin_transaction();

try {
    // Teklif kaydını güncelle
    $stmt = $db->prepare("UPDATE teklifler SET teklif_no = ?, teklif_basligi = ?, cari_id = ?, teklif_tarihi = ?, gecerlilik_tarihi = ?, toplam_tutar = ?, durum = ? WHERE id = ?");
    $stmt->bind_param("sssssdsi", 
        $data['teklif_no'],
        $data['teklif_basligi'],
        $data['cari_id'],
        $data['teklif_tarihi'],
        $data['gecerlilik_tarihi'],
        $data['toplam_tutar'],
        $data['durum'] ?? 'aktif',
        $teklif_id
    );
    $stmt->execute();

    // Mevcut detayları sil
    $stmt_delete = $db->prepare("DELETE FROM teklif_detaylari WHERE teklif_id = ?");
    $stmt_delete->bind_param("i", $teklif_id);
    $stmt_delete->execute();

    // Yeni detayları ekle
    if (isset($data['detaylar']) && is_array($data['detaylar'])) {
        $stmt_detay = $db->prepare("INSERT INTO teklif_detaylari (teklif_id, urun_id, miktar, birim_fiyat, toplam) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($data['detaylar'] as $detay) {
            $stmt_detay->bind_param("iiddd", 
                $teklif_id,
                $detay['urun_id'],
                $detay['miktar'],
                $detay['birim_fiyat'],
                $detay['toplam']
            );
            $stmt_detay->execute();
        }
    }

    $db->commit();
    json_success('Teklif başarıyla güncellendi');

} catch (Exception $e) {
    $db->rollback();
    json_error('Teklif güncellenirken hata: ' . $e->getMessage(), 500);
}
?>
