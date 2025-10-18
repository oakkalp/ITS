<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['firma_yoneticisi']);

$firma_id = get_firma_id();

$stmt = $db->prepare("SELECT * FROM firmalar WHERE id = ?");
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    json_success('Firma bilgileri', $row);
} else {
    json_error('Firma bulunamadı', 404);
}
?>

