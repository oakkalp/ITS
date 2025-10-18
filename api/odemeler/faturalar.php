<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('odemeler', 'okuma');

$firma_id = get_firma_id();

// Ödenmemiş veya kısmi ödemeli faturaları getir
$query = "SELECT f.*, c.unvan as cari_unvan FROM faturalar f 
          LEFT JOIN cariler c ON f.cari_id = c.id 
          WHERE f.firma_id = ? AND f.odeme_durumu != 'odendi'
          ORDER BY f.fatura_tarihi DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$faturalar = [];
while ($row = $result->fetch_assoc()) {
    $faturalar[] = $row;
}

json_success('Faturalar listelendi', $faturalar);
?>

