<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'silme');

// Buffer'ı temizle
ob_clean();

// HTTP metodunu kontrol et
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'DELETE' && $method !== 'POST' && $method !== 'GET') {
    json_error('Method not allowed', 405);
}

// ID'yi farklı yöntemlerle al
$id = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
} elseif (isset($_POST['id'])) {
    $id = $_POST['id'];
} elseif (isset($_FILES['id'])) {
    $id = $_FILES['id'];
}

// Debug için log
error_log("Delete API - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Delete API - GET ID: " . ($_GET['id'] ?? 'null'));
error_log("Delete API - POST ID: " . ($_POST['id'] ?? 'null'));
error_log("Delete API - Final ID: " . $id);
$firma_id = get_firma_id();

if (!$id) {
    json_error('Ürün ID gerekli', 400);
}

// Ürüne ait fatura kalemi var mı kontrol et
$check_query = "SELECT COUNT(*) as c FROM fatura_detaylari WHERE urun_id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$count = $check_stmt->get_result()->fetch_assoc()['c'];

if ($count > 0) {
    json_error('Bu ürüne ait ' . $count . ' fatura kalemi bulunmaktadır. Önce faturaları silin.', 400);
}

$stmt = $db->prepare("DELETE FROM urunler WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $id, $firma_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        json_success('Ürün başarıyla silindi');
    } else {
        json_error('Ürün bulunamadı', 404);
    }
} else {
    json_error('Ürün silinirken hata oluştu: ' . $stmt->error, 500);
}
?>

