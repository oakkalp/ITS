<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('kasa', 'silme');

$id = $_POST['id'] ?? $_GET['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Hareket ID gerekli', 400);
}

$stmt = $db->prepare("DELETE FROM kasa_hareketleri WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        json_success('Kasa hareketi başarıyla silindi');
    } else {
        json_error('Hareket bulunamadı', 404);
    }
} else {
    json_error('Hareket silinirken hata oluştu: ' . $stmt->error, 500);
}
?>

