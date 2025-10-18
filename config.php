<?php
// Config yüklendiğini işaretle
define('CONFIG_LOADED', true);

/**
 * =====================================================
 * FİDAN TAKİP SİSTEMİ - MERKEZI YAPILANDIRMA
 * =====================================================
 * 
 * Localhost'tan Canlıya Geçiş:
 * Sadece bu dosyayı düzenleyin!
 * 
 * Web Panel + Mobil Uygulama (Flutter) için tek yapılandırma
 */

// =====================================================
// VERİTABANI AYARLARI
// =====================================================
// Canlı sunucuya yüklerken bu bilgileri değiştirin
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // XAMPP için root kullanıcısı
define('DB_PASS', ''); // XAMPP için şifre yok
define('DB_NAME', 'muhasebedemo');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// URL AYARLARI
// =====================================================
// IP adresi ve klasör adını buradan değiştirin
// Örnek: define('BASE_URL', 'http://192.168.1.137/muhasebedemo');
define('BASE_URL', 'http://192.168.1.137/muhasebedemo');          // Web panel için
define('API_URL', 'http://192.168.1.137/muhasebedemo/api');       // Mobil için (Flutter)
define('SITE_URL', 'http://192.168.1.137/muhasebedemo');

// =====================================================
// SİTE BİLGİLERİ
// =====================================================
define('SITE_NAME', 'Fidan Takip Sistemi');
define('SITE_SLOGAN', 'İşletme Yönetim Sistemi');
define('COMPANY_NAME', 'ProKonstarim');

// =====================================================
// GÜVENLİK AYARLARI
// =====================================================
define('SESSION_NAME', 'fidan_session');
define('SESSION_LIFETIME', 86400); // 24 saat (saniye cinsinden)
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// JWT Token için (Mobil API)
define('JWT_SECRET_KEY', 'demo_key_change_in_production'); // ÜRETİMDE MUTLAKA DEĞİŞTİRİN!
define('JWT_EXPIRY', 86400); // 24 saat

// =====================================================
// ZAMAN DİLİMİ
// =====================================================
date_default_timezone_set('Europe/Istanbul');

// =====================================================
// HATA RAPORLAMA
// =====================================================
// Geliştirme aşamasında hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// =====================================================
// DOSYA YOLU AYARLARI
// =====================================================
define('ROOT_PATH', __DIR__);
define('API_PATH', ROOT_PATH . '/api');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// =====================================================
// YÜKLEME AYARLARI
// =====================================================
define('MAX_FILE_SIZE', 5242880); // 5MB (byte cinsinden)
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// =====================================================
// SAYFALAMA AYARLARI
// =====================================================
define('PER_PAGE', 25); // Sayfa başına kayıt

// =====================================================
// CORS AYARLARI (Mobil API için)
// =====================================================
define('CORS_ALLOWED_ORIGINS', '*'); // Production'da belirli domain'ler yazın
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// =====================================================
// VERİTABANI BAĞLANTISI
// =====================================================
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($db->connect_error) {
        throw new Exception("Veritabanı bağlantı hatası: " . $db->connect_error);
    }
    
    // Veritabanı yoksa oluştur
    $db->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET " . DB_CHARSET . " COLLATE utf8mb4_turkish_ci");
    
    // Veritabanını seç
    $db->select_db(DB_NAME);
    $db->set_charset(DB_CHARSET);
    
    // Global olarak kullanılabilir
    $GLOBALS['db'] = $db;
    
} catch (Exception $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}

// =====================================================
// SESSION BAŞLATMA
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.name', SESSION_NAME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    session_start();
}

// =====================================================
// HELPER FONKSİYONLAR
// =====================================================

/**
 * URL oluştur (Web için)
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * API URL oluştur (Mobil için)
 */
function api_url($endpoint = '') {
    return API_URL . '/' . ltrim($endpoint, '/');
}

/**
 * Asset URL oluştur (CSS, JS, Resim için)
 */
function asset($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Yönlendirme
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }
}

/**
 * JSON Response (API için)
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * API Hata Response
 */
function json_error($message, $status_code = 400, $errors = []) {
    json_response([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $status_code);
}

/**
 * API Başarı Response
 */
function json_success($message, $data = [], $status_code = 200) {
    json_response([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], $status_code);
}

/**
 * CORS Headers Ayarla (Mobil API için)
 */
function set_cors_headers() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: " . CORS_ALLOWED_ORIGINS);
        header("Access-Control-Allow-Methods: " . CORS_ALLOWED_METHODS);
        header("Access-Control-Allow-Headers: " . CORS_ALLOWED_HEADERS);
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 3600");
    }
    
    // OPTIONS request için
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Güvenli String Temizleme
 */
function clean_input($data) {
    global $db;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $db->real_escape_string($data);
}

/**
 * Şifre Hash
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_HASH_ALGO);
}

/**
 * Şifre Doğrulama
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log Yaz
 */
function write_log($message, $type = 'info') {
    if (!file_exists(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $log_file = LOG_PATH . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$type] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Debug
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

// =====================================================
// FIREBASE AYARLARI
// =====================================================

// Firebase Server Key (Firebase Console > Project Settings > Cloud Messaging)
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
// DEMO SİSTEM UYARILARI
// =====================================================
require_once __DIR__ . '/demo_warnings.php';

