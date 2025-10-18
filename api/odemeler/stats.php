<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('odemeler', 'okuma');

$firma_id = get_firma_id();

$odenmemiş = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND odeme_durumu = 'bekliyor'")->fetch_assoc()['c'];

$kismi = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND odeme_durumu = 'kismi'")->fetch_assoc()['c'];

$odenen = $db->query("SELECT COUNT(*) as c FROM faturalar WHERE firma_id = $firma_id AND odeme_durumu = 'odendi'")->fetch_assoc()['c'];

json_success('İstatistikler', [
    'odenmemiş' => $odenmemiş,
    'kismi' => $kismi,
    'odenen' => $odenen
]);
?>

