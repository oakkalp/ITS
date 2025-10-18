<?php
// Test scripti - Cari delete API'sini test et
echo "<h2>Cari Delete API Test</h2>";

// Test verisi
$test_data = json_encode(['id' => 2]);
echo "<p>Test verisi: " . $test_data . "</p>";

// cURL ile test
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/muhasebedemo/api/cariler/delete.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $test_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($test_data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test'); // Session için

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "<p>HTTP Code: " . $http_code . "</p>";
echo "<p>Response: " . htmlspecialchars($response) . "</p>";
if ($error) {
    echo "<p>cURL Error: " . $error . "</p>";
}

// Basit POST testi
echo "<h3>Basit POST Testi</h3>";
$post_data = http_build_query(['id' => 2]);
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $post_data
    ]
]);

$response2 = file_get_contents('http://localhost/muhasebedemo/api/cariler/delete.php', false, $context);
echo "<p>Simple POST Response: " . htmlspecialchars($response2) . "</p>";
?>
