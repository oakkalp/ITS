<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

$firma_id = get_firma_id();
$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

// Satışlar
$satislar = $db->query("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = $firma_id AND fatura_tipi = 'satis' AND fatura_tarihi BETWEEN '$baslangic' AND '$bitis'")->fetch_assoc()['total'];

// Alışlar
$alislar = $db->query("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = $firma_id AND fatura_tipi = 'alis' AND fatura_tarihi BETWEEN '$baslangic' AND '$bitis'")->fetch_assoc()['total'];

// Kasa bakiye
$gelir = $db->query("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = $firma_id AND islem_tipi = 'gelir'")->fetch_assoc()['total'];
$gider = $db->query("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = $firma_id AND islem_tipi = 'gider'")->fetch_assoc()['total'];

$data = [
    'satislar' => $satislar,
    'alislar' => $alislar,
    'kar' => $satislar - $alislar,
    'kasa_bakiye' => $gelir - $gider
];

json_success('Genel rapor', $data);
?>

