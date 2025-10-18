<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('personel', 'yazma');

// Debug için
error_log("Personel create çağrıldı");

$data = json_decode(file_get_contents('php://input'), true);

// JSON decode hatası kontrolü
if (json_last_error() !== JSON_ERROR_NONE) {
    json_error('JSON parse hatası: ' . json_last_error_msg(), 400);
}

if (!$data) {
    json_error('Veri gönderilmedi', 400);
}

error_log("Gelen data: " . print_r($data, true));
error_log("Gorev değeri: " . ($data['gorev'] ?? 'YOK'));

$firma_id = get_firma_id();

// Zorunlu alan kontrolü
if (empty($data['ad_soyad'])) {
    json_error('Ad Soyad zorunludur', 400);
}

// Null değerleri kontrol et
$telefon = $data['telefon'] ?? null;
$tc_no = $data['tc_no'] ?? null;
$gorev = $data['gorev'] ?? null; // gorev field'ı kullan
$maas = isset($data['maas']) ? floatval($data['maas']) : 0;
$ise_giris_tarihi = $data['ise_giris_tarihi'] ?? null;
$adres = $data['adres'] ?? null;
$aktif = isset($data['aktif']) ? intval($data['aktif']) : 1;

$stmt = $db->prepare("INSERT INTO personel (firma_id, ad_soyad, telefon, tc_no, gorev, maas, ise_giris_tarihi, adres, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("issssdssi",
    $firma_id,
    $data['ad_soyad'],
    $telefon,
    $tc_no,
    $gorev,
    $maas,
    $ise_giris_tarihi,
    $adres,
    $aktif
);

if ($stmt->execute()) {
    json_success('Personel başarıyla eklendi', ['id' => $db->insert_id], 201);
} else {
    json_error('Personel eklenirken hata oluştu: ' . $stmt->error, 500);
}
?>

