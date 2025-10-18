<?php
require_once 'includes/auth.php';
logout_user();
redirect(url('login.php'));
?>

