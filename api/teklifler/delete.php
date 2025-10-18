<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
// require_permission('teklifler', 'silme'); // Geçici olarak kapatıldı

// POST verisini oku
$id = $_POST['id'] ?? 0;
$firma_id = get_firma_id();

// Debug log
error_log("Delete Teklif Debug: ID=$id, Firma_ID=$firma_id, POST=" . json_encode($_POST));

if (!$id) {
    json_error('Geçersiz teklif ID', 400);
}

// Teklifin sahibi kontrolü
$stmt_check = $db->prepare("SELECT id FROM teklifler WHERE id = ? AND firma_id = ?");
$stmt_check->bind_param("ii", $id, $firma_id);
$stmt_check->execute();
if (!$stmt_check->get_result()->fetch_assoc()) {
    json_error('Teklif bulunamadı veya yetkiniz yok', 404);
}

$db->begin_transaction();

try {
    // Önce detayları sil
    $stmt_detay = $db->prepare("DELETE FROM teklif_detaylari WHERE teklif_id = ?");
    $stmt_detay->bind_param("i", $id);
    $stmt_detay->execute();

    // Sonra teklifi sil
    $stmt = $db->prepare("DELETE FROM teklifler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $db->commit();
    json_success('Teklif başarıyla silindi');

} catch (Exception $e) {
    $db->rollback();
    json_error('Teklif silinirken hata: ' . $e->getMessage(), 500);
}
?>