<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/firebase_notification.php';
require_login();

try {
    $type = $_POST['type'] ?? null;
    $data = $_POST['data'] ?? [];
    
    if (!$type) {
        json_error('Bildirim tipi gerekli', 400);
    }
    
    // Kullanıcının FCM token'ını al
    $user_id = $_SESSION['user_id'];
    $token_query = "SELECT fcm_token FROM kullanicilar WHERE id = ?";
    $stmt = $db->prepare($token_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $fcm_token = $stmt->get_result()->fetch_assoc()['fcm_token'];
    
    if (!$fcm_token) {
        json_error('FCM token bulunamadı. Lütfen mobil uygulamadan giriş yapın.', 400);
    }
    
    $firebase = new FirebaseNotification();
    
    if ($type === 'cek_vade') {
        $cek_data = [
            'cek_id' => 999, // Test ID
            'cek_no' => $data['cek_no'],
            'tutar' => number_format($data['tutar'], 2),
            'banka' => $data['banka'],
            'kalan_gun' => $data['kalan_gun']
        ];
        
        $result = $firebase->sendCekVadeNotification($fcm_token, $cek_data);
        
        if ($result['success']) {
            json_success('Çek vade test bildirimi gönderildi');
        } else {
            json_error('Çek vade test bildirimi gönderilemedi: ' . $result['error']);
        }
        
    } elseif ($type === 'tahsilat') {
        $tahsilat_data = [
            'cari_id' => 999, // Test ID
            'cari_unvan' => $data['cari_unvan'],
            'tutar' => number_format($data['tutar'], 2),
            'fatura_no' => $data['fatura_no'],
            'kalan_gun' => $data['kalan_gun']
        ];
        
        $result = $firebase->sendTahsilatNotification($fcm_token, $tahsilat_data);
        
        if ($result['success']) {
            json_success('Tahsilat test bildirimi gönderildi');
        } else {
            json_error('Tahsilat test bildirimi gönderilemedi: ' . $result['error']);
        }
        
    } else {
        json_error('Geçersiz bildirim tipi', 400);
    }
    
} catch (Exception $e) {
    error_log("Test notification hatası: " . $e->getMessage());
    json_error('Test bildirimi gönderilirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
