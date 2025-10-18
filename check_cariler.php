<?php
require_once 'config.php';

echo "<h2>Cari Kontrol</h2>";

try {
    // Cariler tablosunu kontrol et
    $result = $db->query("DESCRIBE cariler");
    if ($result->num_rows > 0) {
        echo "<h3>Cariler Tablosu Yapısı:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Alan</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
    }
    
    // Cariler kayıtlarını kontrol et
    $firma_id = 3; // Test için sabit firma ID
    $result = $db->query("SELECT * FROM cariler WHERE firma_id = $firma_id");
    
    if ($result->num_rows > 0) {
        echo "<h3>Cari Kayıtları:</h3>";
        echo "<table border='1'>";
        echo "<tr>";
        while ($field = $result->fetch_field()) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Tedarikci ve müşteri durumlarını kontrol et
        echo "<h3>Tedarikci/Müşteri Durumu:</h3>";
        $result = $db->query("SELECT id, unvan, is_tedarikci, is_musteri FROM cariler WHERE firma_id = $firma_id");
        while ($row = $result->fetch_assoc()) {
            echo "<p><strong>" . $row['unvan'] . "</strong> - Tedarikci: " . ($row['is_tedarikci'] ? 'Evet' : 'Hayır') . ", Müşteri: " . ($row['is_musteri'] ? 'Evet' : 'Hayır') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Cari kaydı bulunamadı!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
