<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_super_admin();

try {
    // Toplam firmalar
    $firma_query = "SELECT COUNT(*) as toplam FROM firmalar";
    $firma_result = $db->query($firma_query)->fetch_assoc();
    
    // Toplam kullanıcılar
    $kullanici_query = "SELECT COUNT(*) as toplam FROM kullanicilar";
    $kullanici_result = $db->query($kullanici_query)->fetch_assoc();
    
    // Aktif firmalar
    $aktif_firma_query = "SELECT COUNT(*) as toplam FROM firmalar WHERE aktif = 1";
    $aktif_firma_result = $db->query($aktif_firma_query)->fetch_assoc();
    
    // Bugünkü işlemler
    $bugun_query = "
        SELECT COUNT(*) as toplam
        FROM (
            SELECT id FROM faturalar WHERE DATE(fatura_tarihi) = CURDATE()
            UNION ALL
            SELECT id FROM kasa_hareketleri WHERE DATE(tarih) = CURDATE()
        ) as bugun_islemler
    ";
    $bugun_result = $db->query($bugun_query)->fetch_assoc();
    
    json_success('Admin istatistikleri yüklendi', [
        'total_firms' => $firma_result['toplam'],
        'total_users' => $kullanici_result['toplam'],
        'active_firms' => $aktif_firma_result['toplam'],
        'today_transactions' => $bugun_result['toplam']
    ]);
    
} catch (Exception $e) {
    error_log("Admin stats hatası: " . $e->getMessage());
    json_error('İstatistikler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
