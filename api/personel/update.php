<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('personel', 'guncelleme');

$data = json_decode(file_get_contents('php://input'), true);
$firma_id = get_firma_id();
$id = $data['id'] ?? null;

error_log("Personel update - Gelen data: " . print_r($data, true));
error_log("Personel update - Gorev değeri: " . ($data['gorev'] ?? 'YOK'));

if (!$id) {
    json_error('Personel ID gerekli', 400);
}

$stmt = $db->prepare("UPDATE personel SET ad_soyad = ?, telefon = ?, tc_no = ?, gorev = ?, maas = ?, ise_giris_tarihi = ?, adres = ?, aktif = ? WHERE id = ? AND firma_id = ?");

$stmt->bind_param("ssssdssiii",
    $data['ad_soyad'],
    $data['telefon'],
    $data['tc_no'],
    $data['gorev'], // gorev field'ı kullan
    $data['maas'],
    $data['ise_giris_tarihi'],
    $data['adres'],
    $data['aktif'],
    $id,
    $firma_id
);

if ($stmt->execute()) {
    json_success('Personel başarıyla güncellendi');
} else {
    json_error('Personel güncellenirken hata oluştu: ' . $stmt->error, 500);
}
?>

