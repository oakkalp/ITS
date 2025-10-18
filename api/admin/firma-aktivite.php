<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_super_admin();

try {
    // Son 6 ayın firma aktivitesi
    $labels = [];
    $values = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $tarih = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M Y', strtotime("-$i months"));
        
        // O ay kayıt olan firmalar
        $aktivite_query = "
            SELECT COUNT(*) as sayi
            FROM firmalar 
            WHERE DATE_FORMAT(olusturma_tarihi, '%Y-%m') = ?
        ";
        
        $stmt = $db->prepare($aktivite_query);
        $stmt->bind_param("s", $tarih);
        $stmt->execute();
        $sayi = $stmt->get_result()->fetch_assoc()['sayi'];
        
        $values[] = $sayi;
    }
    
    json_success('Firma aktivite grafik verisi yüklendi', [
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch (Exception $e) {
    error_log("Firma aktivite grafik hatası: " . $e->getMessage());
    json_error('Grafik verisi yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
