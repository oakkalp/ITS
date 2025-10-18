<?php
require_once 'config.php';

// Ödemeler tablosunun yapısını kontrol et
$query = "DESCRIBE odemeler";
$result = $db->query($query);

echo "Ödemeler tablosu yapısı:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
