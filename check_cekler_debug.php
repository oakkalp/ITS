<?php
// Çekler tablosunu kontrol et
require_once 'config.php';

echo "<h2>Çekler Tablosu Kontrolü</h2>";

// Tablo yapısını kontrol et
$query = "DESCRIBE cekler";
$result = $db->query($query);

if ($result) {
    echo "<h3>Tablo Yapısı:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Çekler tablosu bulunamadı!</p>";
}

echo "<br><br>";

// Mevcut çekleri listele
$query = "SELECT COUNT(*) as toplam FROM cekler";
$result = $db->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<h3>Toplam Çek Sayısı: " . $row['toplam'] . "</h3>";
}

echo "<br>";

// Son 5 çeki göster
$query = "SELECT * FROM cekler ORDER BY id DESC LIMIT 5";
$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    echo "<h3>Son 5 Çek:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Çek No</th><th>Banka</th><th>Tutar</th><th>Durum</th><th>Firma ID</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['cek_no'] ?? 'Yok') . "</td>";
        echo "<td>" . ($row['banka_adi'] ?? 'Yok') . "</td>";
        echo "<td>" . ($row['tutar'] ?? '0') . "</td>";
        echo "<td>" . ($row['durum'] ?? 'Yok') . "</td>";
        echo "<td>" . ($row['firma_id'] ?? 'Yok') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Hiç çek bulunamadı!</p>";
}

// Test çek ekle
echo "<br><br>";
echo "<h3>Test Çek Ekle:</h3>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_cek' value='1'>";
echo "<input type='submit' value='Test Çek Ekle'>";
echo "</form>";

if (isset($_POST['test_cek'])) {
    $query = "INSERT INTO cekler (firma_id, cek_no, banka_adi, tutar, durum, cek_tipi, vade_tarihi) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $firma_id = 1; // Test firma ID
    $cek_no = 'TEST' . time();
    $banka = 'Test Bankası';
    $tutar = 1000;
    $durum = 'portfoy';
    $tip = 'alinan';
    $vade = date('Y-m-d', strtotime('+30 days'));
    
    $stmt->bind_param("issdsss", $firma_id, $cek_no, $banka, $tutar, $durum, $tip, $vade);
    
    if ($stmt->execute()) {
        echo "<p style='color:green'>Test çek eklendi! ID: " . $db->insert_id . "</p>";
    } else {
        echo "<p style='color:red'>Test çek eklenemedi: " . $stmt->error . "</p>";
    }
}
?>
