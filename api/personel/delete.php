<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('personel', 'silme');

// GET veya POST metodunu destekle
$id = $_GET['id'] ?? $_POST['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Personel ID gerekli', 400);
}

$stmt = $db->prepare("DELETE FROM personel WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        json_success('Personel başarıyla silindi');
    } else {
        json_error('Personel bulunamadı', 404);
    }
} else {
    json_error('Personel silinirken hata oluştu: ' . $stmt->error, 500);
}
?>

