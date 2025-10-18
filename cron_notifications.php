<?php
/**
 * Çek ve Tahsilat Bildirim Cron Job
 * Bu dosya günde bir kez çalıştırılmalıdır
 * 
 * Kullanım:
 * php cron_notifications.php
 * 
 * Crontab örneği (her gün saat 09:00'da çalıştır):
 * 0 9 * * * /usr/bin/php /path/to/fidan/cron_notifications.php
 */

require_once 'config.php';
require_once 'includes/firebase_notification.php';

// Sadece CLI'den çalıştırılabilir
if (php_sapi_name() !== 'cli') {
    die('Bu script sadece komut satırından çalıştırılabilir');
}

echo "=== Çek ve Tahsilat Bildirim Cron Job Başlatılıyor ===\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Tüm aktif firmaları al
    $firmalar_query = "SELECT id, firma_adi FROM firmalar WHERE aktif = 1";
    $firmalar_result = $db->query($firmalar_query);
    
    $toplam_firma = 0;
    $toplam_bildirim = 0;
    
    while ($firma = $firmalar_result->fetch_assoc()) {
        $firma_id = $firma['id'];
        $firma_adi = $firma['firma_adi'];
        
        echo "Firma işleniyor: $firma_adi (ID: $firma_id)\n";
        
        // Çek vade takibi
        echo "  - Çek vade takibi kontrol ediliyor...\n";
        $cek_url = "http://localhost/fidan/api/mobile/cek-vade-takip.php";
        $cek_response = file_get_contents($cek_url);
        
        if ($cek_response) {
            $cek_data = json_decode($cek_response, true);
            if ($cek_data && $cek_data['success']) {
                $cek_bildirim = $cek_data['data']['ozet']['bildirim_gonderilen_kullanici'];
                echo "    ✓ Çek bildirimi: $cek_bildirim kullanıcıya gönderildi\n";
                $toplam_bildirim += $cek_bildirim;
            }
        }
        
        // Tahsilat takibi
        echo "  - Tahsilat takibi kontrol ediliyor...\n";
        $tahsilat_url = "http://localhost/fidan/api/mobile/tahsilat-takip.php";
        $tahsilat_response = file_get_contents($tahsilat_url);
        
        if ($tahsilat_response) {
            $tahsilat_data = json_decode($tahsilat_response, true);
            if ($tahsilat_data && $tahsilat_data['success']) {
                $tahsilat_bildirim = $tahsilat_data['data']['ozet']['bildirim_gonderilen_kullanici'];
                echo "    ✓ Tahsilat bildirimi: $tahsilat_bildirim kullanıcıya gönderildi\n";
                $toplam_bildirim += $tahsilat_bildirim;
            }
        }
        
        $toplam_firma++;
        echo "\n";
    }
    
    echo "=== Cron Job Tamamlandı ===\n";
    echo "İşlenen firma sayısı: $toplam_firma\n";
    echo "Toplam bildirim gönderilen kullanıcı: $toplam_bildirim\n";
    echo "Bitiş zamanı: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    error_log("Cron job hatası: " . $e->getMessage());
    exit(1);
}
?>
