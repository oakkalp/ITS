<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('personel', 'okuma');

$firma_id = get_firma_id();

$query = "SELECT * FROM personel WHERE firma_id = ? ORDER BY ad_soyad ASC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $firma_id);
$stmt->execute();
$result = $stmt->get_result();

$personel = [];
while ($row = $result->fetch_assoc()) {
    $personel[] = $row;
}

json_success('Personel listelendi', $personel);
?>

