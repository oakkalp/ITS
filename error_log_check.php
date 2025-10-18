<?php
/**
 * PHP Error Log Kontrol Scripti
 */
echo "<h2>PHP Error Log Kontrolü</h2>";

echo "<h3>PHP Ayarları:</h3>";
echo "<p><strong>Error Log:</strong> " . ini_get('error_log') . "</p>";
echo "<p><strong>Log Errors:</strong> " . (ini_get('log_errors') ? 'Açık' : 'Kapalı') . "</p>";
echo "<p><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'Açık' : 'Kapalı') . "</p>";
echo "<p><strong>Error Reporting:</strong> " . error_reporting() . "</p>";

echo "<h3>Test Error Log:</h3>";
error_log("Test error log mesajı - " . date('Y-m-d H:i:s'));
echo "<p>✅ Test error log mesajı gönderildi</p>";

echo "<h3>PHP Error Log Dosyaları:</h3>";
$log_files = [
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/logs/php_error_log',
    ini_get('error_log')
];

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        echo "<p>✅ Bulundu: $log_file</p>";
        echo "<p>Son 10 satır:</p>";
        echo "<pre style='background:#f0f0f0; padding:10px; max-height:200px; overflow-y:auto;'>";
        $lines = file($log_file);
        $last_lines = array_slice($lines, -10);
        echo htmlspecialchars(implode('', $last_lines));
        echo "</pre>";
    } else {
        echo "<p>❌ Bulunamadı: $log_file</p>";
    }
}

echo "<h3>Test API Çağrısı:</h3>";
echo "<button onclick='testAPI()'>API Test</button>";
echo "<pre id='apiResult' style='background:#f0f0f0; padding:10px;'></pre>";

?>
<script>
function testAPI() {
    document.getElementById('apiResult').textContent = 'Test ediliyor...';
    
    fetch('api/faturalar/create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fatura_tipi: 'alis',
            fatura_no: 'TEST-001',
            fatura_tarihi: '2025-10-08',
            cari_id: 8,
            odeme_tipi: 'nakit',
            kalemler: [{
                urun_id: 3,
                miktar: 1,
                birim_fiyat: 100,
                kdv_orani: 18
            }]
        })
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('apiResult').textContent = data;
    })
    .catch(error => {
        document.getElementById('apiResult').textContent = 'Hata: ' + error;
    });
}
</script>
