<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

try {
    $firma_id = get_firma_id();
    $uyarilar = [];
    
    // Kritik stok uyarıları
    $kritik_stok_query = "
        SELECT COUNT(*) as sayi
        FROM urunler 
        WHERE firma_id = ? AND stok_miktari <= 0 AND aktif = 1
    ";
    
    $stmt = $db->prepare($kritik_stok_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $tukenen_stok = $stmt->get_result()->fetch_assoc()['sayi'];
    
    if ($tukenen_stok > 0) {
        $uyarilar[] = [
            'tip' => 'kritik',
            'mesaj' => "$tukenen_stok ürünün stoku tükenmiş!"
        ];
    }
    
    // Kritik seviye uyarıları
    $kritik_seviye_query = "
        SELECT COUNT(*) as sayi
        FROM urunler 
        WHERE firma_id = ? AND stok_miktari <= kritik_stok AND stok_miktari > 0 AND aktif = 1
    ";
    
    $stmt = $db->prepare($kritik_seviye_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $kritik_seviye = $stmt->get_result()->fetch_assoc()['sayi'];
    
    if ($kritik_seviye > 0) {
        $uyarilar[] = [
            'tip' => 'uyari',
            'mesaj' => "$kritik_seviye ürünün stoku kritik seviyede!"
        ];
    }
    
    // Vadesi yaklaşan çekler
    $vadesi_yaklasan_query = "
        SELECT COUNT(*) as sayi
        FROM cekler 
        WHERE firma_id = ? AND vade_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND durum = 'beklemede'
    ";
    
    $stmt = $db->prepare($vadesi_yaklasan_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $vadesi_yaklasan = $stmt->get_result()->fetch_assoc()['sayi'];
    
    if ($vadesi_yaklasan > 0) {
        $uyarilar[] = [
            'tip' => 'uyari',
            'mesaj' => "$vadesi_yaklasan çekin vadesi yaklaşıyor!"
        ];
    }
    
    // Vadesi geçen çekler
    $vadesi_gecen_query = "
        SELECT COUNT(*) as sayi
        FROM cekler 
        WHERE firma_id = ? AND vade_tarihi < CURDATE()
        AND durum = 'beklemede'
    ";
    
    $stmt = $db->prepare($vadesi_gecen_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $vadesi_gecen = $stmt->get_result()->fetch_assoc()['sayi'];
    
    if ($vadesi_gecen > 0) {
        $uyarilar[] = [
            'tip' => 'kritik',
            'mesaj' => "$vadesi_gecen çekin vadesi geçmiş!"
        ];
    }
    
    // Yüksek borçlu cariler
    $yuksek_borc_query = "
        SELECT COUNT(*) as sayi
        FROM cariler 
        WHERE firma_id = ? AND bakiye < -10000 AND aktif = 1
    ";
    
    $stmt = $db->prepare($yuksek_borc_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $yuksek_borc = $stmt->get_result()->fetch_assoc()['sayi'];
    
    if ($yuksek_borc > 0) {
        $uyarilar[] = [
            'tip' => 'uyari',
            'mesaj' => "$yuksek_borc carinin borcu 10.000₺'yi aşmış!"
        ];
    }
    
    // Uyarı yoksa bilgi mesajı
    if (empty($uyarilar)) {
        $uyarilar[] = [
            'tip' => 'bilgi',
            'mesaj' => 'Tüm sistemler normal çalışıyor.'
        ];
    }
    
    json_success('Uyarılar yüklendi', $uyarilar);
    
} catch (Exception $e) {
    error_log("Uyarılar hatası: " . $e->getMessage());
    json_error('Uyarılar yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
