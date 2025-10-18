<?php
require_once 'config.php';

echo "<h2>Flutter API Test</h2>";

// Test 1: Veritabanı bağlantısı
echo "<h3>1. Veritabanı Bağlantısı</h3>";
try {
    $test_db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($test_db->connect_error) {
        echo "<p style='color: red;'>❌ Veritabanı bağlantı hatası: " . $test_db->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Veritabanı bağlantısı başarılı</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Veritabanı hatası: " . $e->getMessage() . "</p>";
}

// Test 2: Admin kullanıcısı kontrolü
echo "<h3>2. Admin Kullanıcısı</h3>";
$admin_query = "SELECT id, kullanici_adi, ad_soyad, rol FROM kullanicilar WHERE kullanici_adi = 'admin'";
$admin_result = $test_db->query($admin_query);

if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    echo "<p style='color: green;'>✅ Admin kullanıcısı mevcut: " . $admin['ad_soyad'] . " (ID: " . $admin['id'] . ")</p>";
} else {
    echo "<p style='color: orange;'>⚠ Admin kullanıcısı bulunamadı, oluşturuluyor...</p>";
    
    // Admin kullanıcısı oluştur
    require_once 'api/flutter/auth.php';
    createAdminUser($test_db);
    
    // Tekrar kontrol et
    $admin_result = $test_db->query($admin_query);
    if ($admin_result && $admin_result->num_rows > 0) {
        $admin = $admin_result->fetch_assoc();
        echo "<p style='color: green;'>✅ Admin kullanıcısı oluşturuldu: " . $admin['ad_soyad'] . " (ID: " . $admin['id'] . ")</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin kullanıcısı oluşturulamadı</p>";
    }
}

// Test 3: JWT Token oluşturma
echo "<h3>3. JWT Token Test</h3>";
require_once 'api/flutter/flutter_api.php';

$api = new FlutterAPI();
$token = $api->generateJWT(1, 1, 'super_admin');
echo "<p style='color: green;'>✅ JWT Token oluşturuldu: " . substr($token, 0, 50) . "...</p>";

// Test 4: Token doğrulama
$payload = $api->validateJWT($token);
if ($payload) {
    echo "<p style='color: green;'>✅ JWT Token doğrulandı: User ID " . $payload['user_id'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ JWT Token doğrulanamadı</p>";
}

// Test 5: Flutter Auth API endpoint testi
echo "<h3>4. Flutter Auth API Test</h3>";
echo "<p>API URL: " . BASE_URL . "/api/flutter/auth.php</p>";

// Test 6: Tablo yapıları kontrolü
echo "<h3>5. Tablo Yapıları</h3>";
$tables = ['kullanicilar', 'firmalar', 'cariler', 'faturalar', 'urunler', 'cekler', 'kasa', 'teklifler'];
foreach ($tables as $table) {
    $desc_query = "DESCRIBE $table";
    $desc_result = $test_db->query($desc_query);
    if ($desc_result) {
        echo "<p style='color: green;'>✅ $table tablosu mevcut</p>";
    } else {
        echo "<p style='color: red;'>❌ $table tablosu bulunamadı: " . $test_db->error . "</p>";
    }
}

// Test 7: Config sabitleri
echo "<h3>6. Config Sabitleri</h3>";
echo "<p>BASE_URL: " . BASE_URL . "</p>";
echo "<p>API_URL: " . API_URL . "</p>";
echo "<p>SITE_NAME: " . SITE_NAME . "</p>";
echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";

$test_db->close();

echo "<h3>Test Tamamlandı!</h3>";
echo "<p><a href='flutter_project/lib/main.dart' target='_blank'>Flutter Uygulamasını İncele</a></p>";
echo "<p><a href='api/flutter/auth.php' target='_blank'>Auth API'yi Test Et</a></p>";
?>
