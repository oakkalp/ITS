<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('personel', 'okuma');

$id = $_GET['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Personel ID gerekli', 400);
}

$stmt = $db->prepare("SELECT * FROM personel WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    json_success('Personel bulundu', $row);
} else {
    json_error('Personel bulunamadı', 404);
}
?>

