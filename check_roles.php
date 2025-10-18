<?php
require_once 'config.php';

echo "Kullanıcı Rolleri:\n";
$result = mysqli_query($db, 'SELECT DISTINCT rol FROM kullanicilar');
while($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['rol'] . "\n";
}

echo "\nKullanıcı Örnekleri:\n";
$result = mysqli_query($db, 'SELECT kullanici_adi, rol, firma_id FROM kullanicilar LIMIT 10');
while($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['kullanici_adi'] . " (Rol: " . $row['rol'] . ", Firma: " . $row['firma_id'] . ")\n";
}
?>

