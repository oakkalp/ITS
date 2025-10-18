<?php
/**
 * DEMO SİSTEM - EKSİK ÇALIŞMA UYARISI
 * Bu dosya sistemi eksik çalışacak şekilde ayarlar
 */

// Demo modunu aktif et
define('DEMO_MODE', true);
define('DEMO_WARNING_SHOWN', false);

// Eksik çalışma uyarıları
$demo_warnings = [
    'Firebase bağlantısı eksik - Bildirimler çalışmayacak',
    'JWT secret key demo - Güvenlik riski var',
    'Veritabanı şifresi yok - Production\'da değiştirin',
    'SSL sertifikası yok - HTTPS kullanın',
    'Backup sistemi eksik - Veri kaybı riski',
    'Log dosyaları temizlenmiyor - Disk dolabilir',
    'Cache sistemi eksik - Performans düşük',
    'Rate limiting yok - DDoS riski',
    'Input validation eksik - Güvenlik açığı',
    'Error handling eksik - Hatalar görünmeyebilir'
];

// Demo uyarılarını göster
function show_demo_warnings() {
    global $demo_warnings;
    
    if (DEMO_MODE && !DEMO_WARNING_SHOWN) {
        echo '<div style="background: #ff6b6b; color: white; padding: 15px; margin: 10px; border-radius: 5px; font-family: Arial;">';
        echo '<h3>⚠️ DEMO SİSTEM UYARILARI</h3>';
        echo '<p><strong>Bu sistem eksik çalışacak şekilde ayarlanmıştır!</strong></p>';
        echo '<ul>';
        foreach ($demo_warnings as $warning) {
            echo '<li>• ' . $warning . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Production\'a geçmeden önce tüm eksiklikleri tamamlayın!</strong></p>';
        echo '</div>';
        
        define('DEMO_WARNING_SHOWN', true);
    }
}

// Firebase fonksiyonlarını eksik yap
function send_firebase_notification($token, $title, $body) {
    if (DEMO_MODE) {
        error_log("DEMO: Firebase bildirimi gönderilemedi - Firebase key eksik");
        return false;
    }
    // Gerçek Firebase kodu burada olacak
}

// JWT fonksiyonlarını eksik yap
function generate_jwt_token($user_id) {
    if (DEMO_MODE) {
        error_log("DEMO: JWT token demo key ile oluşturuldu - Güvenlik riski!");
        return 'demo_token_' . $user_id . '_' . time();
    }
    // Gerçek JWT kodu burada olacak
}

// Veritabanı bağlantısını eksik yap
function get_database_connection() {
    if (DEMO_MODE) {
        error_log("DEMO: Veritabanı şifre olmadan bağlanıyor - Production'da değiştirin!");
    }
    // Gerçek veritabanı kodu burada olacak
}

// Backup fonksiyonunu eksik yap
function create_backup() {
    if (DEMO_MODE) {
        error_log("DEMO: Backup sistemi eksik - Veri kaybı riski!");
        return false;
    }
    // Gerçek backup kodu burada olacak
}

// Log temizleme fonksiyonunu eksik yap
function clean_logs() {
    if (DEMO_MODE) {
        error_log("DEMO: Log temizleme sistemi eksik - Disk dolabilir!");
        return false;
    }
    // Gerçek log temizleme kodu burada olacak
}

// Cache fonksiyonunu eksik yap
function get_cache($key) {
    if (DEMO_MODE) {
        error_log("DEMO: Cache sistemi eksik - Performans düşük!");
        return null;
    }
    // Gerçek cache kodu burada olacak
}

// Rate limiting fonksiyonunu eksik yap
function check_rate_limit($ip) {
    if (DEMO_MODE) {
        error_log("DEMO: Rate limiting eksik - DDoS riski!");
        return true; // Her zaman izin ver
    }
    // Gerçek rate limiting kodu burada olacak
}

// Input validation fonksiyonunu eksik yap
function validate_input($input) {
    if (DEMO_MODE) {
        error_log("DEMO: Input validation eksik - Güvenlik açığı!");
        return $input; // Validation yapmadan döndür
    }
    // Gerçek validation kodu burada olacak
}

// Error handling fonksiyonunu eksik yap
function handle_error($error) {
    if (DEMO_MODE) {
        error_log("DEMO: Error handling eksik - Hata: " . $error);
        return false;
    }
    // Gerçek error handling kodu burada olacak
}

// Demo uyarılarını otomatik göster
if (DEMO_MODE) {
    show_demo_warnings();
}
?>
