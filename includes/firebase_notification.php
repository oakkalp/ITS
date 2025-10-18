<?php
require_once 'config.php';

class FirebaseNotification {
    private $firebase_url = 'https://fcm.googleapis.com/fcm/send';
    private $server_key;
    
    public function __construct() {
        // Firebase server key'i config'den al
        $this->server_key = FIREBASE_SERVER_KEY ?? null;
        
        if (!$this->server_key) {
            throw new Exception('Firebase server key bulunamadı');
        }
    }
    
    /**
     * Tek kullanıcıya bildirim gönder
     */
    public function sendToUser($user_token, $title, $body, $data = []) {
        $fields = [
            'to' => $user_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1
            ],
            'data' => $data,
            'priority' => 'high'
        ];
        
        return $this->sendNotification($fields);
    }
    
    /**
     * Tüm kullanıcılara bildirim gönder
     */
    public function sendToAll($title, $body, $data = []) {
        $fields = [
            'to' => '/topics/all',
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1
            ],
            'data' => $data,
            'priority' => 'high'
        ];
        
        return $this->sendNotification($fields);
    }
    
    /**
     * Belirli bir konuya (topic) bildirim gönder
     */
    public function sendToTopic($topic, $title, $body, $data = []) {
        $fields = [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1
            ],
            'data' => $data,
            'priority' => 'high'
        ];
        
        return $this->sendNotification($fields);
    }
    
    /**
     * Çek vade bildirimi gönder
     */
    public function sendCekVadeNotification($user_token, $cek_data) {
        $kalan_gun = $cek_data['kalan_gun'];
        $cek_no = $cek_data['cek_no'];
        $tutar = $cek_data['tutar'];
        $banka = $cek_data['banka'];
        
        if ($kalan_gun == 0) {
            $title = "🚨 Çek Vadesi Bugün!";
            $body = "Çek No: $cek_no - Tutar: ₺$tutar - Banka: $banka";
        } else {
            $title = "⚠️ Çek Vadesi Yaklaşıyor";
            $body = "$kalan_gun gün sonra çek ödemesi! Çek No: $cek_no - ₺$tutar";
        }
        
        $data = [
            'type' => 'cek_vade',
            'cek_id' => $cek_data['cek_id'],
            'action' => 'cekler_page'
        ];
        
        return $this->sendToUser($user_token, $title, $body, $data);
    }
    
    /**
     * Tahsilat bildirimi gönder
     */
    public function sendTahsilatNotification($user_token, $tahsilat_data) {
        $kalan_gun = $tahsilat_data['kalan_gun'];
        $cari_unvan = $tahsilat_data['cari_unvan'];
        $tutar = $tahsilat_data['tutar'];
        
        if ($kalan_gun == 0) {
            $title = "💰 Tahsilat Günü Bugün!";
            $body = "$cari_unvan'dan ₺$tutar tahsilat bekleniyor";
        } else {
            $title = "📅 Tahsilat Yaklaşıyor";
            $body = "$kalan_gun gün sonra $cari_unvan'dan ₺$tutar tahsilat";
        }
        
        $data = [
            'type' => 'tahsilat',
            'cari_id' => $tahsilat_data['cari_id'],
            'action' => 'cariler_page'
        ];
        
        return $this->sendToUser($user_token, $title, $body, $data);
    }
    
    /**
     * Firebase'e bildirim gönder
     */
    private function sendNotification($fields) {
        $headers = [
            'Authorization: key=' . $this->server_key,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->firebase_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $response = json_decode($result, true);
            return [
                'success' => true,
                'message_id' => $response['message_id'] ?? null,
                'response' => $response
            ];
        } else {
            return [
                'success' => false,
                'error' => 'HTTP ' . $http_code,
                'response' => $result
            ];
        }
    }
}

// Kullanım örneği:
/*
$firebase = new FirebaseNotification();

// Tek kullanıcıya bildirim
$firebase->sendToUser(
    'user_fcm_token_here',
    'Başlık',
    'Bildirim içeriği',
    ['custom_data' => 'value']
);

// Çek vade bildirimi
$firebase->sendCekVadeNotification('user_token', [
    'cek_id' => 1,
    'cek_no' => '123456',
    'tutar' => '5000',
    'banka' => 'Ziraat Bankası',
    'kalan_gun' => 2
]);
*/
?>
