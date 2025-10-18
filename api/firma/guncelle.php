<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['firma_yoneticisi']);

$data = json_decode(file_get_contents('php://input'), true);
$firma_id = get_firma_id();

$stmt = $db->prepare("UPDATE firmalar SET firma_adi = ?, vergi_no = ?, vergi_dairesi = ?, telefon = ?, email = ?, adres = ? WHERE id = ?");

$stmt->bind_param("ssssssi",
    $data['firma_adi'],
    $data['vergi_no'],
    $data['vergi_dairesi'],
    $data['telefon'],
    $data['email'],
    $data['adres'],
    $firma_id
);

if ($stmt->execute()) {
    json_success('Firma bilgileri başarıyla güncellendi');
} else {
    json_error('Güncelleme sırasında hata oluştu: ' . $stmt->error, 500);
}
?>

