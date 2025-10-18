<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'okuma');

$firma_id = get_firma_id();
$id = $_GET['id'];

// Önce teklifin bu firmaya ait olduğunu kontrol et
$check_query = "SELECT id FROM teklifler WHERE id = ? AND firma_id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("ii", $id, $firma_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    json_error('Teklif bulunamadı veya yetkiniz yok', 404);
}

// Teklif detaylarını al
$query = "SELECT 
            td.*,
            u.urun_adi,
            u.birim
          FROM teklif_detaylari td
          LEFT JOIN urunler u ON td.urun_id = u.id
          WHERE td.teklif_id = ?
          ORDER BY td.id";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$detaylar = [];
while ($row = $result->fetch_assoc()) {
    $detaylar[] = $row;
}

json_success('Teklif detayları alındı', $detaylar);
?>
