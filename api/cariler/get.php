<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('cariler', 'okuma');

$id = $_GET['id'] ?? null;

if (!$id) {
    json_error('Cari ID gerekli', 400);
}

$firma_id = get_firma_id();

$stmt = $db->prepare("SELECT * FROM cariler WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // NULL değerleri boş string ile değiştir
    $row['vergi_dairesi'] = $row['vergi_dairesi'] ?? '';
    $row['vergi_no'] = $row['vergi_no'] ?? '';
    $row['yetkili_kisi'] = $row['yetkili_kisi'] ?? '';
    $row['telefon'] = $row['telefon'] ?? '';
    $row['email'] = $row['email'] ?? '';
    $row['adres'] = $row['adres'] ?? '';
    
    json_success('Cari bulundu', $row);
} else {
    json_error('Cari bulunamadı', 404);
}
?>

