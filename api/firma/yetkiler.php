<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['firma_yoneticisi']);

$kullanici_id = $_GET['kullanici_id'] ?? null;
$firma_id = get_firma_id();

if (!$kullanici_id) {
    json_error('Kullanıcı ID gerekli', 400);
}

// Kullanıcının bu firmadan olduğunu kontrol et
$check = $db->query("SELECT COUNT(*) as c FROM kullanicilar WHERE id = $kullanici_id AND firma_id = $firma_id");
if ($check->fetch_assoc()['c'] == 0) {
    json_error('Yetkisiz işlem', 403);
}

// Yetkileri getir
$stmt = $db->prepare("SELECT * FROM kullanici_yetkileri WHERE kullanici_id = ?");
$stmt->bind_param("i", $kullanici_id);
$stmt->execute();
$result = $stmt->get_result();

$yetkiler = [];
while ($row = $result->fetch_assoc()) {
    $yetkiler[] = $row;
}

json_success('Yetkiler listelendi', $yetkiler);
?>

