<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

try {
    $firma_id = get_firma_id();
    
    // Son 6 ayın verilerini al
    $labels = [];
    $gelirler = [];
    $giderler = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $tarih = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M Y', strtotime("-$i months"));
        
        // O ayın gelirlerini hesapla
        $gelir_query = "
            SELECT COALESCE(SUM(toplam_tutar), 0) as toplam
            FROM faturalar 
            WHERE firma_id = ? AND fatura_tipi = 'satis' 
            AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = ?
        ";
        
        $stmt = $db->prepare($gelir_query);
        $stmt->bind_param("is", $firma_id, $tarih);
        $stmt->execute();
        $gelir = $stmt->get_result()->fetch_assoc()['toplam'];
        
        // Kasa gelirleri
        $kasa_gelir_query = "
            SELECT COALESCE(SUM(tutar), 0) as toplam
            FROM kasa_hareketleri 
            WHERE firma_id = ? AND islem_tipi = 'gelir' 
            AND DATE_FORMAT(tarih, '%Y-%m') = ?
        ";
        
        $stmt = $db->prepare($kasa_gelir_query);
        $stmt->bind_param("is", $firma_id, $tarih);
        $stmt->execute();
        $kasa_gelir = $stmt->get_result()->fetch_assoc()['toplam'];
        
        $gelirler[] = $gelir + $kasa_gelir;
        
        // O ayın giderlerini hesapla
        $gider_query = "
            SELECT COALESCE(SUM(toplam_tutar), 0) as toplam
            FROM faturalar 
            WHERE firma_id = ? AND fatura_tipi = 'alis' 
            AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = ?
        ";
        
        $stmt = $db->prepare($gider_query);
        $stmt->bind_param("is", $firma_id, $tarih);
        $stmt->execute();
        $gider = $stmt->get_result()->fetch_assoc()['toplam'];
        
        // Kasa giderleri
        $kasa_gider_query = "
            SELECT COALESCE(SUM(tutar), 0) as toplam
            FROM kasa_hareketleri 
            WHERE firma_id = ? AND islem_tipi = 'gider' 
            AND DATE_FORMAT(tarih, '%Y-%m') = ?
        ";
        
        $stmt = $db->prepare($kasa_gider_query);
        $stmt->bind_param("is", $firma_id, $tarih);
        $stmt->execute();
        $kasa_gider = $stmt->get_result()->fetch_assoc()['toplam'];
        
        $giderler[] = $gider + $kasa_gider;
    }
    
    json_success('Gelir-gider grafik verisi yüklendi', [
        'labels' => $labels,
        'gelirler' => $gelirler,
        'giderler' => $giderler
    ]);
    
} catch (Exception $e) {
    error_log("Gelir-gider grafik hatası: " . $e->getMessage());
    json_error('Grafik verisi yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
