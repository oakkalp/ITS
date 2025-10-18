<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

$firma_id = get_firma_id();
$limit = $_GET['limit'] ?? 10;

$query = "SELECT * FROM cariler WHERE firma_id = ? AND bakiye > 0 ORDER BY bakiye DESC LIMIT ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $firma_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$cariler = [];
while ($row = $result->fetch_assoc()) {
    $cariler[] = $row;
}

json_success('Alacaklılar', $cariler);
?>

