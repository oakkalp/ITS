<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'okuma');

// Buffer'ı temizle
ob_clean();

$id = $_GET['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Ürün ID gerekli', 400);
}

$stmt = $db->prepare("SELECT * FROM urunler WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    json_success('Ürün bulundu', $row);
} else {
    json_error('Ürün bulunamadı', 404);
}
?>

