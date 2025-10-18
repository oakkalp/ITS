<?php
require_once 'config.php';

echo "=== CARILER TABLOSU YAPISI ===\n";
$result = $db->query("DESCRIBE cariler");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}

echo "\n=== ÖRNEK CARI KAYDI ===\n";
$result = $db->query("SELECT * FROM cariler LIMIT 1");
if ($row = $result->fetch_assoc()) {
    foreach ($row as $key => $value) {
        echo "$key: $value\n";
    }
} else {
    echo "Cari kaydı bulunamadı\n";
}
?>
