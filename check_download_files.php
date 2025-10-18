<?php
require_once 'config.php';
require_once 'includes/auth.php';

echo "<h1>İndirme Dosyaları Kontrol Listesi</h1>";

// Kontrol edilecek dosyalar
$files_to_check = [
    'api/teklifler/download.php',
    'api/teklifler/pdf_real.php',
    'modules/teklifler/list.php'
];

echo "<h2>1. Dosya Varlık Kontrolü</h2>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file - Mevcut<br>";
    } else {
        echo "❌ $file - Bulunamadı<br>";
    }
}

echo "<h2>2. download.php İçerik Kontrolü</h2>";
if (file_exists('api/teklifler/download.php')) {
    $content = file_get_contents('api/teklifler/download.php');
    
    // Header kontrolü
    if (strpos($content, 'Content-Disposition: attachment') !== false) {
        echo "✅ Content-Disposition header mevcut<br>";
    } else {
        echo "❌ Content-Disposition header eksik<br>";
    }
    
    if (strpos($content, 'Content-Type: text/html') !== false) {
        echo "✅ Content-Type header mevcut<br>";
    } else {
        echo "❌ Content-Type header eksik<br>";
    }
    
    // Dosya adı kontrolü
    if (strpos($content, 'teklif_') !== false) {
        echo "✅ Dosya adı formatı doğru<br>";
    } else {
        echo "❌ Dosya adı formatı hatalı<br>";
    }
    
    echo "<h3>download.php İçeriği:</h3>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 1000)) . "...</pre>";
}

echo "<h2>3. list.php İndirme Fonksiyonu Kontrolü</h2>";
if (file_exists('modules/teklifler/list.php')) {
    $content = file_get_contents('modules/teklifler/list.php');
    
    if (strpos($content, 'download.php') !== false) {
        echo "✅ download.php referansı mevcut<br>";
    } else {
        echo "❌ download.php referansı eksik<br>";
    }
    
    if (strpos($content, 'function downloadTeklif') !== false) {
        echo "✅ downloadTeklif fonksiyonu mevcut<br>";
    } else {
        echo "❌ downloadTeklif fonksiyonu eksik<br>";
    }
}

echo "<h2>4. Test Linkleri</h2>";
echo "<p><a href='api/teklifler/download.php?id=13' target='_blank'>Test İndirme Linki (ID=13)</a></p>";
echo "<p><a href='api/teklifler/download.php?id=14' target='_blank'>Test İndirme Linki (ID=14)</a></p>";

echo "<h2>5. Tarayıcı Önerileri</h2>";
echo "<ul>";
echo "<li>Tarayıcı cache'ini temizleyin (Ctrl+F5)</li>";
echo "<li>Farklı tarayıcıda test edin (Chrome, Firefox, Edge)</li>";
echo "<li>İndirme ayarlarını kontrol edin</li>";
echo "<li>Güvenlik yazılımı indirmeyi engelliyor olabilir</li>";
echo "</ul>";

echo "<h2>6. Sunucu Log Kontrolü</h2>";
if (file_exists('logs/php_errors.log')) {
    echo "<p>Son 10 satır PHP log:</p>";
    $logs = file('logs/php_errors.log');
    $recent_logs = array_slice($logs, -10);
    echo "<pre>" . htmlspecialchars(implode('', $recent_logs)) . "</pre>";
} else {
    echo "❌ PHP log dosyası bulunamadı";
}

echo "<h2>7. Manuel Test</h2>";
echo "<p>Bu linkleri tarayıcıda açıp test edin:</p>";
echo "<ul>";
echo "<li><a href='api/teklifler/download.php?id=13' target='_blank'>https://prokonstarim.com.tr/muhasebedemo/api/teklifler/download.php?id=13</a></li>";
echo "<li><a href='api/teklifler/download.php?id=14' target='_blank'>https://prokonstarim.com.tr/muhasebedemo/api/teklifler/download.php?id=14</a></li>";
echo "</ul>";

echo "<h2>8. Alternatif Çözüm</h2>";
echo "<p>Eğer sorun devam ederse, dosyayı sunucuda oluşturup link verebiliriz:</p>";
echo "<textarea rows='10' cols='80'>";
echo "// Geçici dosya oluşturma sistemi\n";
echo "// 1. HTML içeriği dosyaya yaz\n";
echo "// 2. Dosya linkini kullanıcıya gönder\n";
echo "// 3. 24 saat sonra dosyayı sil\n";
echo "</textarea>";
?>
