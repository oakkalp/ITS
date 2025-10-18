<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();

try {
    $user_id = $_SESSION['user_id'];
    $fcm_token = $_POST['fcm_token'] ?? null;
    
    if (!$fcm_token) {
        json_error('FCM token gerekli', 400);
    }
    
    // Kullanıcının FCM token'ını güncelle
    $update_query = "UPDATE kullanicilar SET fcm_token = ? WHERE id = ?";
    $stmt = $db->prepare($update_query);
    $stmt->bind_param("si", $fcm_token, $user_id);
    
    if ($stmt->execute()) {
        json_success('FCM token başarıyla kaydedildi');
    } else {
        json_error('FCM token kaydedilemedi', 500);
    }
    
} catch (Exception $e) {
    error_log("FCM token kaydetme hatası: " . $e->getMessage());
    json_error('FCM token kaydedilirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
