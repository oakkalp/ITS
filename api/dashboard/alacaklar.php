<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();

try {
    $firma_id = get_firma_id();
    
    if (!$firma_id) {
        json_error('Firma bilgisi bulunamadı', 400);
    }
    
    $query = "SELECT unvan, bakiye FROM cariler 
              WHERE firma_id = $firma_id AND bakiye > 0 
              ORDER BY bakiye DESC LIMIT 5";
    
    $result = $db->query($query);
    $data = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    json_success('Alacaklar yüklendi', $data);
    
} catch (Exception $e) {
    error_log("Alacaklar API hatası: " . $e->getMessage());
    json_error('Alacaklar yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>

