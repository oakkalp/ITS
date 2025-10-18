<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/firebase_notification.php';
require_login();

try {
    $firma_id = get_firma_id();
    $notification_days = CEK_NOTIFICATION_DAYS_BEFORE;
    
    // Vadesi yaklaşan çekleri bul
    $cek_query = "
        SELECT 
            c.*,
            car.unvan as cari_unvan,
            DATEDIFF(c.vade_tarihi, CURDATE()) as kalan_gun
        FROM cekler c
        LEFT JOIN cariler car ON c.cari_id = car.id
        WHERE c.firma_id = ? 
        AND c.durum = 'beklemede'
        AND c.vade_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY c.vade_tarihi ASC
    ";
    
    $stmt = $db->prepare($cek_query);
    $stmt->bind_param("ii", $firma_id, $notification_days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cekler = [];
    $toplam_tutar = 0;
    $bugun_vadesi_gelen = 0;
    $bu_hafta_vadesi_gelen = 0;
    
    while ($row = $result->fetch_assoc()) {
        $cekler[] = $row;
        $toplam_tutar += $row['tutar'];
        
        if ($row['kalan_gun'] == 0) {
            $bugun_vadesi_gelen++;
        } elseif ($row['kalan_gun'] <= 7) {
            $bu_hafta_vadesi_gelen++;
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
    if (!empty($cekler) && !empty($user_tokens) && NOTIFICATION_ENABLED) {
        $firebase = new FirebaseNotification();
        
        foreach ($user_tokens as $token_row) {
            $fcm_token = $token_row['fcm_token'];
            
            // Bugün vadesi gelen çekler için özel bildirim
            if ($bugun_vadesi_gelen > 0) {
                $title = "🚨 Çek Ödemesi Bugün!";
                $body = "$bugun_vadesi_gelen çekin vadesi bugün! Toplam: ₺" . number_format($toplam_tutar, 2);
                
                $firebase->sendToUser($fcm_token, $title, $body, [
                    'type' => 'cek_vade_bugun',
                    'cek_sayisi' => $bugun_vadesi_gelen,
                    'toplam_tutar' => $toplam_tutar,
                    'action' => 'cekler_page'
                ]);
            }
            // Bu hafta vadesi gelen çekler için uyarı
            elseif ($bu_hafta_vadesi_gelen > 0) {
                $title = "⚠️ Çek Ödemeleri Yaklaşıyor";
                $body = "$bu_hafta_vadesi_gelen çekin vadesi bu hafta! Toplam: ₺" . number_format($toplam_tutar, 2);
                
                $firebase->sendToUser($fcm_token, $title, $body, [
                    'type' => 'cek_vade_yaklasan',
                    'cek_sayisi' => $bu_hafta_vadesi_gelen,
                    'toplam_tutar' => $toplam_tutar,
                    'action' => 'cekler_page'
                ]);
            }
            
            // Her çek için ayrı bildirim
            foreach ($cekler as $cek) {
                if ($cek['kalan_gun'] <= $notification_days) {
                    $cek_data = [
                        'cek_id' => $cek['id'],
                        'cek_no' => $cek['cek_no'],
                        'tutar' => number_format($cek['tutar'], 2),
                        'banka' => $cek['banka_adi'],
                        'kalan_gun' => $cek['kalan_gun'],
                        'cari_unvan' => $cek['cari_unvan'] ?: $cek['cari_disi_kisi']
                    ];
                    
                    $firebase->sendCekVadeNotification($fcm_token, $cek_data);
                }
            }
        }
    }
    
    // Sonuçları döndür
    json_success('Çek vade takibi tamamlandı', [
        'cekler' => $cekler,
        'ozet' => [
            'toplam_cek' => count($cekler),
            'toplam_tutar' => $toplam_tutar,
            'bugun_vadesi_gelen' => $bugun_vadesi_gelen,
            'bu_hafta_vadesi_gelen' => $bu_hafta_vadesi_gelen,
            'bildirim_gonderilen_kullanici' => count($user_tokens)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Çek vade takip hatası: " . $e->getMessage());
    json_error('Çek vade takibi sırasında hata oluştu: ' . $e->getMessage(), 500);
}
?>
