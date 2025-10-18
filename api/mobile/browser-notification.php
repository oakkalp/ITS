<?php
/**
 * Firebase olmadan Browser Notification sistemi
 * Bu sistem Firebase Server Key gerektirmez
 */
require_once '../../config.php';
require_once '../../includes/auth.php';
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
    
    $bildirimler = [];
    
    while ($row = $result->fetch_assoc()) {
        $kalan_gun = $row['kalan_gun'];
        $cek_no = $row['cek_no'];
        $tutar = number_format($row['tutar'], 2);
        $banka = $row['banka_adi'];
        $cari = $row['cari_unvan'] ?: $row['cari_disi_kisi'];
        
        if ($kalan_gun == 0) {
            $title = "🚨 Çek Vadesi Bugün!";
            $body = "Çek No: $cek_no - Tutar: ₺$tutar - Banka: $banka - Cari: $cari";
            $urgency = 'high';
        } elseif ($kalan_gun == 1) {
            $title = "⚠️ Çek Vadesi Yarın!";
            $body = "Çek No: $cek_no - Tutar: ₺$tutar - Banka: $banka - Cari: $cari";
            $urgency = 'high';
        } else {
            $title = "📅 Çek Vadesi Yaklaşıyor";
            $body = "$kalan_gun gün sonra! Çek No: $cek_no - ₺$tutar - Banka: $banka";
            $urgency = 'normal';
        }
        
        $bildirimler[] = [
            'id' => $row['id'],
            'title' => $title,
            'body' => $body,
            'urgency' => $urgency,
            'icon' => '/fidan/mobiluygulamaiconu.png',
            'badge' => '/fidan/mobiluygulamaiconu.png',
            'tag' => 'cek-' . $row['id'],
            'data' => [
                'type' => 'cek_vade',
                'cek_id' => $row['id'],
                'action' => 'cekler_page',
                'url' => '/fidan/modules/cekler/list.php'
            ],
            'kalan_gun' => $kalan_gun,
            'cek_no' => $cek_no,
            'tutar' => $tutar,
            'banka' => $banka,
            'cari' => $cari
        ];
    }
    
    // Vadesi yaklaşan tahsilatlar
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
    $tahsilat_days = TAHSILAT_NOTIFICATION_DAYS_BEFORE;
    $stmt->bind_param("ii", $firma_id, $tahsilat_days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $kalan_gun = $row['kalan_gun'];
        $fatura_no = $row['fatura_no'];
        $tutar = number_format($row['kalan_tutar'], 2);
        $cari = $row['cari_unvan'];
        
        if ($kalan_gun == 0) {
            $title = "💰 Tahsilat Günü Bugün!";
            $body = "$cari'dan ₺$tutar tahsilat bekleniyor! Fatura: $fatura_no";
            $urgency = 'high';
        } elseif ($kalan_gun == 1) {
            $title = "💵 Tahsilat Yarın!";
            $body = "$cari'dan ₺$tutar tahsilat yarın! Fatura: $fatura_no";
            $urgency = 'high';
        } else {
            $title = "📅 Tahsilat Yaklaşıyor";
            $body = "$kalan_gun gün sonra $cari'dan ₺$tutar tahsilat";
            $urgency = 'normal';
        }
        
        $bildirimler[] = [
            'id' => 'fatura-' . $row['id'],
            'title' => $title,
            'body' => $body,
            'urgency' => $urgency,
            'icon' => '/fidan/mobiluygulamaiconu.png',
            'badge' => '/fidan/mobiluygulamaiconu.png',
            'tag' => 'tahsilat-' . $row['id'],
            'data' => [
                'type' => 'tahsilat',
                'fatura_id' => $row['id'],
                'cari_id' => $row['cari_id'],
                'action' => 'cariler_page',
                'url' => '/fidan/modules/cariler/detay.php?id=' . $row['cari_id']
            ],
            'kalan_gun' => $kalan_gun,
            'fatura_no' => $fatura_no,
            'tutar' => $tutar,
            'cari' => $cari
        ];
    }
    
    json_success('Bildirimler hazırlandı', [
        'bildirimler' => $bildirimler,
        'toplam' => count($bildirimler),
        'cek_sayisi' => count(array_filter($bildirimler, function($b) { return isset($b['data']['type']) && $b['data']['type'] === 'cek_vade'; })),
        'tahsilat_sayisi' => count(array_filter($bildirimler, function($b) { return isset($b['data']['type']) && $b['data']['type'] === 'tahsilat'; }))
    ]);
    
} catch (Exception $e) {
    error_log("Browser notification hatası: " . $e->getMessage());
    json_error('Bildirimler hazırlanırken hata oluştu: ' . $e->getMessage(), 500);
}
?>
