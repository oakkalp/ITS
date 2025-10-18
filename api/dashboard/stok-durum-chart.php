<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'okuma');

try {
    $firma_id = get_firma_id();
    
    // Stok durumlarını hesapla
    $stok_query = "
        SELECT 
            COUNT(CASE WHEN stok_miktari > kritik_stok THEN 1 END) as normal_stok,
            COUNT(CASE WHEN stok_miktari <= kritik_stok AND stok_miktari > 0 THEN 1 END) as kritik_stok,
            COUNT(CASE WHEN stok_miktari <= 0 THEN 1 END) as tükenen_stok
        FROM urunler 
        WHERE firma_id = ? AND aktif = 1
    ";
    
    $stmt = $db->prepare($stok_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $labels = ['Normal Stok', 'Kritik Stok', 'Tükenen Stok'];
    $values = [
        $result['normal_stok'],
        $result['kritik_stok'],
        $result['tükenen_stok']
    ];
    
    json_success('Stok durumu grafik verisi yüklendi', [
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch (Exception $e) {
    error_log("Stok durumu grafik hatası: " . $e->getMessage());
    json_error('Grafik verisi yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
