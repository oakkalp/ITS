<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_super_admin();

try {
    // Kullanıcı rol dağılımı
    $rol_query = "
        SELECT 
            rol,
            COUNT(*) as sayi
        FROM kullanicilar 
        GROUP BY rol
    ";
    
    $result = $db->query($rol_query);
    
    $labels = [];
    $values = [];
    
    while ($row = $result->fetch_assoc()) {
        $rol_display = '';
        switch ($row['rol']) {
            case 'super_admin':
                $rol_display = 'Süper Admin';
                break;
            case 'firma_yoneticisi':
                $rol_display = 'Firma Yöneticisi';
                break;
            case 'firma_kullanici':
                $rol_display = 'Firma Kullanıcısı';
                break;
            default:
                $rol_display = ucfirst($row['rol']);
        }
        
        $labels[] = $rol_display;
        $values[] = $row['sayi'];
    }
    
    json_success('Kullanıcı dağılım grafik verisi yüklendi', [
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch (Exception $e) {
    error_log("Kullanıcı dağılım grafik hatası: " . $e->getMessage());
    json_error('Grafik verisi yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
