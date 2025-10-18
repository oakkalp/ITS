<?php
// Debug test - cariler update
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Cariler Update Debug Test</h2>";

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>Session Bilgileri:</h3>";
echo "<p>Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "</p>";
echo "<p>Firma ID: " . ($_SESSION['firma_id'] ?? 'Not Set') . "</p>";

// Test update.php'yi çağır
echo "<h3>Test Update.php:</h3>";
echo "<form method='POST' action='api/cariler/update.php'>";
echo "<input type='hidden' name='id' value='1'>";
echo "<input type='hidden' name='unvan' value='Test Cari'>";
echo "<input type='hidden' name='telefon' value='1234567890'>";
echo "<input type='hidden' name='email' value='test@test.com'>";
echo "<input type='hidden' name='adres' value='Test Adres'>";
echo "<input type='hidden' name='is_musteri' value='1'>";
echo "<input type='hidden' name='is_tedarikci' value='0'>";
echo "<input type='submit' value='Test Update'>";
echo "</form>";

if ($_POST) {
    echo "<h4>POST Data:</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Update.php'yi test et - local API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/muhasebedemo/api/cariler/update.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_POST));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h4>Response:</h4>";
    echo "<p>HTTP Code: " . $httpCode . "</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>
