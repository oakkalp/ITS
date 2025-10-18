<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();

try {
    $firma_id = get_firma_id();
    
    if (!$firma_id) {
        json_error('Firma bilgisi bulunamadı', 400);
    }
    
    // Toplam cari sayısı
    $total_cariler_result = $db->query("SELECT COUNT(*) as c FROM cariler WHERE firma_id = $firma_id");
    $total_cariler = $total_cariler_result ? $total_cariler_result->fetch_assoc()['c'] : 0;
    
    // Toplam ürün sayısı
    $total_urunler_result = $db->query("SELECT COUNT(*) as c FROM urunler WHERE firma_id = $firma_id");
    $total_urunler = $total_urunler_result ? $total_urunler_result->fetch_assoc()['c'] : 0;
    
    // Bu ay fatura sayısı
    $this_month = date('Y-m');
    $this_month_faturalar_result = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = '$this_month'");
    $this_month_faturalar = $this_month_faturalar_result ? $this_month_faturalar_result->fetch_assoc()['c'] : 0;
    
    // Kasa bakiyesi (gelir - gider)
    $gelir_result = $db->query("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = $firma_id AND islem_tipi = 'gelir'");
    $gelir = $gelir_result ? $gelir_result->fetch_assoc()['total'] : 0;
    
    $gider_result = $db->query("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = $firma_id AND islem_tipi = 'gider'");
    $gider = $gider_result ? $gider_result->fetch_assoc()['total'] : 0;
    
    $kasa_bakiye = $gelir - $gider;
    
    json_success('İstatistikler yüklendi', [
        'total_cariler' => $total_cariler,
        'total_urunler' => $total_urunler,
        'this_month_faturalar' => $this_month_faturalar,
        'kasa_bakiye' => $kasa_bakiye
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard stats hatası: " . $e->getMessage());
    json_error('İstatistikler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>

