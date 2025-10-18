<?php
/**
 * PRODUCTION CONFIGURATION
 * IP adresi tabanlı yapılandırma
 */

// =====================================================
// PRODUCTION AYARLARI
// =====================================================

// Site bilgileri
define('SITE_NAME', 'Fidan Takip Sistemi');
define('SITE_URL', 'http://192.168.1.137/muhasebedemo');
define('SITE_DESCRIPTION', 'İşletme yönetim sistemi');

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // XAMPP için root kullanıcısı
define('DB_PASS', ''); // XAMPP için şifre yok
define('DB_NAME', 'muhasebedemo');

// Güvenlik ayarları
define('ENCRYPTION_KEY', 'prod_key_' . md5('prokonstarim2024'));
define('SESSION_LIFETIME', 7200); // 2 saat
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 dakika

// =====================================================
// FIREBASE AYARLARI (PRODUCTION)
// =====================================================

// Firebase Server Key (Production)
define('FIREBASE_SERVER_KEY', 'DEMO_FIREBASE_KEY_CHANGE_IN_PRODUCTION');

// Firebase Project ID
define('FIREBASE_PROJECT_ID', 'demo_project_change_in_production');

// Firebase Service Account Key Dosya Yolu
define('FIREBASE_SERVICE_ACCOUNT_KEY', __DIR__ . '/demo-firebase-key.json');

// =====================================================
// MOBİL UYGULAMA AYARLARI
// =====================================================

// Mobil uygulama icon
define('MOBILE_APP_ICON', 'mobiluygulamaiconu.png');

// Push notification ayarları
define('NOTIFICATION_ENABLED', true);
define('CEK_NOTIFICATION_DAYS_BEFORE', 3); // Çek vadesinden kaç gün önce bildirim
define('TAHSILAT_NOTIFICATION_DAYS_BEFORE', 2); // Tahsilattan kaç gün önce bildirim

// =====================================================
// API AYARLARI (FLUTTER İÇİN)
// =====================================================

// API Base URL
define('API_BASE_URL', 'http://192.168.1.137/muhasebedemo/api');

// API Versiyonu
define('API_VERSION', 'v1');

// JWT Token ayarları
define('JWT_SECRET', 'flutter_jwt_secret_' . md5('onmuhasebe2024'));
define('JWT_EXPIRY', 86400); // 24 saat

// CORS ayarları (Flutter için)
define('CORS_ENABLED', true);
define('CORS_ALLOWED_ORIGINS', [
    'http://192.168.1.137',
    'http://localhost:3000', // Flutter web
    'http://127.0.0.1:3000'  // Flutter web
]);

// =====================================================
// PRODUCTION ÖZELLİKLERİ
// =====================================================

// Debug modu (Production'da kapalı)
define('DEBUG_MODE', false);

// Error reporting (Production'da sadece log)
define('ERROR_REPORTING', false);
define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/logs/production.log');

// Cache ayarları
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 saat

// Backup ayarları
define('AUTO_BACKUP_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 30);

// =====================================================
// FLUTTER UYGULAMA AYARLARI
// =====================================================

// Flutter app bilgileri
define('FLUTTER_APP_NAME', 'On Muhasebe');
define('FLUTTER_APP_VERSION', '1.0.0');
define('FLUTTER_APP_BUILD', '1');

// Android ayarları
define('ANDROID_PACKAGE_NAME', 'com.prokonstarim.onmuhasebe');
define('ANDROID_MIN_SDK', 21);
define('ANDROID_TARGET_SDK', 34);

// iOS ayarları
define('IOS_BUNDLE_ID', 'com.prokonstarim.onmuhasebe');
define('IOS_MIN_VERSION', '12.0');

// =====================================================
// VERİTABANI BAĞLANTISI
// =====================================================

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception('Veritabanı bağlantı hatası: ' . $db->connect_error);
    }
    
    $db->set_charset('utf8mb4');
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die('Veritabanı Hatası: ' . $e->getMessage());
    } else {
        error_log('Database Error: ' . $e->getMessage());
        die('Sistem geçici olarak kullanılamıyor. Lütfen daha sonra tekrar deneyin.');
    }
}

// =====================================================
// HELPER FONKSİYONLAR
// =====================================================

/**
 * URL oluşturucu
 */
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

/**
 * API URL oluşturucu
 */
function api_url($endpoint = '') {
    return API_BASE_URL . '/' . API_VERSION . '/' . ltrim($endpoint, '/');
}

/**
 * Production log fonksiyonu
 */
function log_message($message, $level = 'INFO') {
    if (LOG_ERRORS) {
        $log_entry = date('Y-m-d H:i:s') . " [$level] $message" . PHP_EOL;
        file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * CORS header'ları ekle
 */
function set_cors_headers() {
    if (CORS_ENABLED) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// CORS header'ları otomatik ekle
set_cors_headers();

// =====================================================
// SİSTEM HAZIR
// =====================================================

// Production log başlat
log_message('Production config loaded - ' . SITE_URL);

?>
