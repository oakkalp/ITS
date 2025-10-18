<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('cariler', 'okuma');

$cari_id = $_GET['cari_id'] ?? null;
$firma_id = get_firma_id();

if (!$cari_id) {
    json_error('Cari ID gerekli', 400);
}

$query = "SELECT * FROM faturalar WHERE firma_id = ? AND cari_id = ? ORDER BY fatura_tarihi DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $firma_id, $cari_id);
$stmt->execute();
$result = $stmt->get_result();

$faturalar = [];
while ($row = $result->fetch_assoc()) {
    $faturalar[] = $row;
}

json_success('Faturalar listelendi', $faturalar);
?>

