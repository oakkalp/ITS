<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

$firma_id = get_firma_id();

// Son 12 ay
$aylar = [];
for ($i = 11; $i >= 0; $i--) {
    $tarih = date('Y-m', strtotime("-$i months"));
    
    $satislar = $db->query("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = $firma_id AND fatura_tipi = 'satis' AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = '$tarih'")->fetch_assoc()['total'];
    
    $alislar = $db->query("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = $firma_id AND fatura_tipi = 'alis' AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = '$tarih'")->fetch_assoc()['total'];
    
    $aylar[] = [
        'ay' => $tarih,
        'satislar' => $satislar,
        'alislar' => $alislar
    ];
}

json_success('Aylık rapor', $aylar);
?>

