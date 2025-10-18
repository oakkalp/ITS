<?php
// Cariler update test
require_once 'config.php';
require_once 'includes/auth.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Cariler Update Test</h2>";

// Session kontrolü
echo "<h3>Session Bilgileri:</h3>";
echo "<p>Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "</p>";
echo "<p>Firma ID: " . (get_firma_id() ?? 'Not Set') . "</p>";

// Test update
echo "<h3>Test Update:</h3>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_update' value='1'>";
echo "<input type='submit' value='Test Update Yap'>";
echo "</form>";

if (isset($_POST['test_update'])) {
    $test_data = [
        'id' => 1,
        'unvan' => 'Test Cari',
        'telefon' => '1234567890',
        'email' => 'test@test.com',
        'adres' => 'Test Adres',
        'is_musteri' => 1,
        'is_tedarikci' => 0
    ];
    
    echo "<h4>Test Data:</h4>";
    echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";
    
    // Update query test
    $firma_id = get_firma_id();
    $stmt = $db->prepare("UPDATE cariler SET unvan = ?, telefon = ?, email = ?, adres = ?, is_musteri = ?, is_tedarikci = ? WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ssssiiii", 
        $test_data['unvan'],
        $test_data['telefon'],
        $test_data['email'],
        $test_data['adres'],
        $test_data['is_musteri'],
        $test_data['is_tedarikci'],
        $test_data['id'],
        $firma_id
    );
    
    if ($stmt->execute()) {
        echo "<p style='color:green'>Test update başarılı!</p>";
    } else {
        echo "<p style='color:red'>Test update hatası: " . $stmt->error . "</p>";
    }
}
?>
