<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['super_admin']);

$total_firms = $db->query("SELECT COUNT(*) as c FROM firmalar")->fetch_assoc()['c'];
$active_firms = $db->query("SELECT COUNT(*) as c FROM firmalar WHERE aktif = 1")->fetch_assoc()['c'];
$total_users = $db->query("SELECT COUNT(*) as c FROM kullanicilar")->fetch_assoc()['c'];

json_success('İstatistikler yüklendi', [
    'total_firms' => $total_firms,
    'active_firms' => $active_firms,
    'total_users' => $total_users
]);
?>

