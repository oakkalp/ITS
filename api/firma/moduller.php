<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();

$result = $db->query("SELECT * FROM moduller WHERE modul_kodu != 'ayarlar' ORDER BY sira");
$moduller = [];

while ($row = $result->fetch_assoc()) {
    $moduller[] = $row;
}

json_success('Modüller listelendi', $moduller);
?>

