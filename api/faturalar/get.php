<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'okuma');

$id = $_GET['id'] ?? null;
$firma_id = get_firma_id();

if (!$id) {
    json_error('Fatura ID gerekli', 400);
}

try {
    $fatura = $db->query("
        SELECT 
            f.*,
            c.unvan as cari_unvan
        FROM faturalar f
        LEFT JOIN cariler c ON f.cari_id = c.id
        WHERE f.id = $id AND f.firma_id = $firma_id
    ")->fetch_assoc();

    if (!$fatura) {
        json_error('Fatura bulunamadı', 404);
    }

    json_success('Fatura başarıyla getirildi', $fatura);
    
} catch (Exception $e) {
    error_log("Fatura get hatası: " . $e->getMessage());
    json_error('Fatura getirilirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
