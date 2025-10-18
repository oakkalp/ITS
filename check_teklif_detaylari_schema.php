<?php
require_once 'config.php';

try {
    $query = "DESCRIBE teklif_detaylari";
    $result = $db->query($query);
    
    echo "<h3>teklif_detaylari Tablo Yapısı:</h3>";
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
    
    // urun_id sütununun NULL olup olmadığını kontrol et
    $null_check = "SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'teklif_detaylari' AND COLUMN_NAME = 'urun_id'";
    $null_result = $db->query($null_check);
    $null_row = $null_result->fetch_assoc();
    
    echo "<h3>urun_id Sütunu NULL Kontrolü:</h3>";
    echo "<p>IS_NULLABLE: " . $null_row['IS_NULLABLE'] . "</p>";
    
    if ($null_row['IS_NULLABLE'] === 'NO') {
        echo "<p style='color: red;'><strong>HATA:</strong> urun_id sütunu NULL değer kabul etmiyor!</p>";
        echo "<p>Çözüm: ALTER TABLE teklif_detaylari MODIFY COLUMN urun_id INT NULL;</p>";
    } else {
        echo "<p style='color: green;'><strong>OK:</strong> urun_id sütunu NULL değer kabul ediyor.</p>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
