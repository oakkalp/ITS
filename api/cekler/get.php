<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('cekler', 'okuma');

try {
    $id = $_GET['id'] ?? null;
    $firma_id = get_firma_id();
    
    if (!$id) {
        json_error('Çek ID gerekli', 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM cekler WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        json_success('Çek bulundu', $row);
    } else {
        json_error('Çek bulunamadı', 404);
    }
    
} catch (Exception $e) {
    error_log("Çek get hatası: " . $e->getMessage());
    json_error('Çek bilgileri alınırken hata oluştu: ' . $e->getMessage(), 500);
}
?>

