<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/firebase_notification.php';
require_login();

try {
    $firma_id = get_firma_id();
    $notification_days = TAHSILAT_NOTIFICATION_DAYS_BEFORE;
    
    // Vadesi yaklaşan tahsilatları bul (satış faturaları)
    $tahsilat_query = "
        SELECT 
            f.*,
            c.unvan as cari_unvan,
            DATEDIFF(f.vade_tarihi, CURDATE()) as kalan_gun,
            (f.toplam_tutar - COALESCE(f.odenen_tutar, 0)) as kalan_tutar
        FROM faturalar f
        JOIN cariler c ON f.cari_id = c.id
        WHERE f.firma_id = ? 
        AND f.fatura_tipi = 'satis'
        AND f.odeme_durumu != 'tahsil_edildi'
        AND f.vade_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        AND (f.toplam_tutar - COALESCE(f.odenen_tutar, 0)) > 0
        ORDER BY f.vade_tarihi ASC
    ";
    
    $stmt = $db->prepare($tahsilat_query);
    $stmt->bind_param("ii", $firma_id, $notification_days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tahsilatlar = [];
    $toplam_tutar = 0;
    $bugun_tahsilat = 0;
    $bu_hafta_tahsilat = 0;
    
    while ($row = $result->fetch_assoc()) {
        $tahsilatlar[] = $row;
        $toplam_tutar += $row['kalan_tutar'];
        
        if ($row['kalan_gun'] == 0) {
            $bugun_tahsilat++;
        } elseif ($row['kalan_gun'] <= 7) {
            $bu_hafta_tahsilat++;
        }
    }
    
    // Kullanıcıların FCM token'larını al
    $user_tokens_query = "
        SELECT DISTINCT fcm_token 
        FROM kullanicilar 
        WHERE firma_id = ? AND fcm_token IS NOT NULL AND fcm_token != ''
    ";
    
    $stmt = $db->prepare($user_tokens_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $user_tokens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Bildirim gönder
    if (!empty($tahsilatlar) && !empty($user_tokens) && NOTIFICATION_ENABLED) {
        $firebase = new FirebaseNotification();
        
        foreach ($user_tokens as $token_row) {
            $fcm_token = $token_row['fcm_token'];
            
            // Bugün tahsilat günü olanlar için özel bildirim
            if ($bugun_tahsilat > 0) {
                $title = "💰 Tahsilat Günü Bugün!";
                $body = "$bugun_tahsilat müşteriden tahsilat bekleniyor! Toplam: ₺" . number_format($toplam_tutar, 2);
                
                $firebase->sendToUser($fcm_token, $title, $body, [
                    'type' => 'tahsilat_bugun',
                    'tahsilat_sayisi' => $bugun_tahsilat,
                    'toplam_tutar' => $toplam_tutar,
                    'action' => 'cariler_page'
                ]);
            }
            // Bu hafta tahsilat beklenenler için uyarı
            elseif ($bu_hafta_tahsilat > 0) {
                $title = "📅 Tahsilatlar Yaklaşıyor";
                $body = "$bu_hafta_tahsilat müşteriden tahsilat bu hafta! Toplam: ₺" . number_format($toplam_tutar, 2);
                
                $firebase->sendToUser($fcm_token, $title, $body, [
                    'type' => 'tahsilat_yaklasan',
                    'tahsilat_sayisi' => $bu_hafta_tahsilat,
                    'toplam_tutar' => $toplam_tutar,
                    'action' => 'cariler_page'
                ]);
            }
            
            // Her tahsilat için ayrı bildirim
            foreach ($tahsilatlar as $tahsilat) {
                if ($tahsilat['kalan_gun'] <= $notification_days) {
                    $tahsilat_data = [
                        'cari_id' => $tahsilat['cari_id'],
                        'cari_unvan' => $tahsilat['cari_unvan'],
                        'tutar' => number_format($tahsilat['kalan_tutar'], 2),
                        'fatura_no' => $tahsilat['fatura_no'],
                        'kalan_gun' => $tahsilat['kalan_gun']
                    ];
                    
                    $firebase->sendTahsilatNotification($fcm_token, $tahsilat_data);
                }
            }
        }
    }
    
    // Sonuçları döndür
    json_success('Tahsilat takibi tamamlandı', [
        'tahsilatlar' => $tahsilatlar,
        'ozet' => [
            'toplam_tahsilat' => count($tahsilatlar),
            'toplam_tutar' => $toplam_tutar,
            'bugun_tahsilat' => $bugun_tahsilat,
            'bu_hafta_tahsilat' => $bu_hafta_tahsilat,
            'bildirim_gonderilen_kullanici' => count($user_tokens)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Tahsilat takip hatası: " . $e->getMessage());
    json_error('Tahsilat takibi sırasında hata oluştu: ' . $e->getMessage(), 500);
}
?>
