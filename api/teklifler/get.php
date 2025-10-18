<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'okuma');

$id = $_GET['id'] ?? 0;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Geçersiz teklif ID', 400);
}

$query = "SELECT t.*, c.unvan as cari_unvan, c.telefon as cari_telefon, c.email as cari_email, c.adres as cari_adres 
          FROM teklifler t 
          LEFT JOIN cariler c ON t.cari_id = c.id 
          WHERE t.id = ? AND t.firma_id = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();
$teklif = $result->fetch_assoc();

if (!$teklif) {
    json_error('Teklif bulunamadı', 404);
}

// Teklif detaylarını al
$query_detay = "SELECT td.*, u.urun_adi FROM teklif_detaylari td 
                LEFT JOIN urunler u ON td.urun_id = u.id 
                WHERE td.teklif_id = ?";
$stmt_detay = $db->prepare($query_detay);
$stmt_detay->bind_param("i", $id);
$stmt_detay->execute();
$detaylar = $stmt_detay->get_result()->fetch_all(MYSQLI_ASSOC);

$teklif['detaylar'] = $detaylar;

json_success('Teklif detayı', $teklif);
?>