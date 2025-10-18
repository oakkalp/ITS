<?php
// PHP error log'unu kontrol et
echo "<h2>PHP Error Log Kontrolü</h2>";

// Son 20 satırı göster
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    echo "<h3>Error Log Dosyası: $logFile</h3>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    echo "<pre>";
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>Error log dosyası bulunamadı.</p>";
}

// Test login yap
echo "<h3>Test Login</h3>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_login' value='1'>";
echo "<input type='submit' value='Test Login Yap'>";
echo "</form>";

if (isset($_POST['test_login'])) {
    require_once 'config.php';
    require_once 'includes/auth.php';
    
    echo "<h4>Login Test Sonucu:</h4>";
    $result = login_user('melih', 'melih1996');
    
    echo "<pre>";
    echo "Success: " . ($result['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $result['message'] . "\n";
    echo "User Data: " . json_encode($result['user'], JSON_PRETTY_PRINT) . "\n";
    echo "</pre>";
    
    if ($result['success']) {
        $user_for_jwt = [
            'id' => $result['user']['id'],
            'firma_id' => $result['user']['firma_id'],
            'ad_soyad' => $result['user']['ad_soyad'],
            'firma_adi' => $result['user']['firma_adi'],
            'rol' => $result['user']['rol']
        ];
        
        echo "<h4>JWT için Hazırlanan User:</h4>";
        echo "<pre>" . json_encode($user_for_jwt, JSON_PRETTY_PRINT) . "</pre>";
        
        $token = generate_jwt_token($user_for_jwt);
        echo "<h4>Oluşturulan Token:</h4>";
        echo "<pre>" . $token . "</pre>";
        
        // Token'ı decode et
        $parts = explode('.', $token);
        if (count($parts) == 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
            echo "<h4>Token Payload:</h4>";
            echo "<pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
}
?>
