<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'okuma');

$fatura_id = $_GET['fatura_id'] ?? null;
$firma_id = get_firma_id();

if (!$fatura_id) {
    json_error('Fatura ID gerekli', 400);
}

try {
    $fatura_detaylari = $db->query("
        SELECT 
            fd.*,
            u.urun_adi
        FROM fatura_detaylari fd
        LEFT JOIN urunler u ON fd.urun_id = u.id
        WHERE fd.fatura_id = $fatura_id
        ORDER BY fd.id
    ")->fetch_all(MYSQLI_ASSOC);

    json_success('Fatura detayları başarıyla getirildi', $fatura_detaylari);
    
} catch (Exception $e) {
    error_log("Fatura detay hatası: " . $e->getMessage());
    json_error('Fatura detayları getirilirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
