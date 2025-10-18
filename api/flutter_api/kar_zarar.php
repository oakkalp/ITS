<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// JWT token kontrolü
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($headers['authorization'])) {
    $token = str_replace('Bearer ', '', $headers['authorization']);
}

if (!$token) {
    json_error('Token gerekli', 401);
}

try {
    $decoded = JWT::decode($token, JWT_SECRET_KEY, ['HS256']);
    if (is_array($decoded)) {
        $decoded = (object) $decoded;
    }
    
    $firma_id = $decoded->firma_id;
    $kullanici_id = $decoded->user_id;
    
    // Yetki kontrolü (geçici olarak devre dışı)
    // if (!has_permission($kullanici_id, 'raporlar', 'okuma')) {
    //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
    // }

} catch (Exception $e) {
    json_error('Geçersiz token: ' . $e->getMessage(), 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'rapor':
        handleGetKarZararRaporu();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetKarZararRaporu() {
    global $db, $firma_id;
    
    $tip = $_GET['tip'] ?? 'aylik';
    $ay = $_GET['ay'] ?? null;
    $yil = $_GET['yil'] ?? null;
    $baslangic = $_GET['baslangic'] ?? null;
    $bitis = $_GET['bitis'] ?? null;
    
    // Tarih aralığını belirle
    if ($tip === 'ozel' && $baslangic && $bitis) {
        $tarih_baslangic = $baslangic;
        $tarih_bitis = $bitis;
    } elseif ($tip === 'yillik' && $yil) {
        $tarih_baslangic = $yil . '-01-01';
        $tarih_bitis = $yil . '-12-31';
    } elseif ($tip === 'aylik' && $ay && $yil) {
        $tarih_baslangic = $yil . '-' . $ay . '-01';
        $tarih_bitis = $yil . '-' . $ay . '-' . date('t', strtotime($yil . '-' . $ay . '-01'));
    } else {
        json_error('Geçersiz tarih parametreleri', 400);
    }
    
    // Tarih formatını kontrol et
    if (!DateTime::createFromFormat('Y-m-d', $tarih_baslangic) || !DateTime::createFromFormat('Y-m-d', $tarih_bitis)) {
        json_error('Geçersiz tarih formatı', 400);
    }
    
    try {
        $rapor_data = getKarZararRaporu($firma_id, $tarih_baslangic, $tarih_bitis);
        json_success('Kar-zarar raporu yüklendi', $rapor_data);
    } catch (Exception $e) {
        error_log("Kar-zarar raporu hatası: " . $e->getMessage());
        json_error('Rapor yüklenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function getKarZararRaporu($firma_id, $baslangic, $bitis) {
    global $db;
    
    $gelirler = [];
    $giderler = [];
    $detaylar = [];
    
    // Satış faturalarından gelirler
    $satis_query = "
        SELECT 
            'Satış Faturaları' as kategori,
            SUM(toplam_tutar) as tutar,
            COUNT(*) as adet
        FROM faturalar 
        WHERE firma_id = ? AND fatura_tipi = 'satis' 
        AND fatura_tarihi BETWEEN ? AND ?
    ";
    
    $stmt = $db->prepare($satis_query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $satis_result = $stmt->get_result()->fetch_assoc();
    
    if ($satis_result['tutar'] > 0) {
        $gelirler[] = [
            'kategori' => 'Satış Faturaları',
            'tutar' => $satis_result['tutar'],
            'adet' => $satis_result['adet']
        ];
    }
    
    // Kasa gelirleri
    $kasa_gelir_query = "
        SELECT 
            COALESCE(kategori, 'Diğer Gelirler') as kategori,
            SUM(tutar) as tutar,
            COUNT(*) as adet
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND islem_tipi = 'gelir' 
        AND tarih BETWEEN ? AND ?
        GROUP BY kategori
    ";
    
    $stmt = $db->prepare($kasa_gelir_query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $kasa_gelir_result = $stmt->get_result();
    
    while ($row = $kasa_gelir_result->fetch_assoc()) {
        if ($row['tutar'] > 0) {
            $gelirler[] = [
                'kategori' => $row['kategori'],
                'tutar' => $row['tutar'],
                'adet' => $row['adet']
            ];
        }
    }
    
    // Alış faturalarından giderler
    $alis_query = "
        SELECT 
            'Alış Faturaları' as kategori,
            SUM(toplam_tutar) as tutar,
            COUNT(*) as adet
        FROM faturalar 
        WHERE firma_id = ? AND fatura_tipi = 'alis' 
        AND fatura_tarihi BETWEEN ? AND ?
    ";
    
    $stmt = $db->prepare($alis_query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $alis_result = $stmt->get_result()->fetch_assoc();
    
    if ($alis_result['tutar'] > 0) {
        $giderler[] = [
            'kategori' => 'Alış Faturaları',
            'tutar' => $alis_result['tutar'],
            'adet' => $alis_result['adet']
        ];
    }
    
    // Kasa giderleri
    $kasa_gider_query = "
        SELECT 
            COALESCE(kategori, 'Diğer Giderler') as kategori,
            SUM(tutar) as tutar,
            COUNT(*) as adet
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND islem_tipi = 'gider' 
        AND tarih BETWEEN ? AND ?
        GROUP BY kategori
    ";
    
    $stmt = $db->prepare($kasa_gider_query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $kasa_gider_result = $stmt->get_result();
    
    while ($row = $kasa_gider_result->fetch_assoc()) {
        if ($row['tutar'] > 0) {
            $giderler[] = [
                'kategori' => $row['kategori'],
                'tutar' => $row['tutar'],
                'adet' => $row['adet']
            ];
        }
    }
    
    // Detaylı hareketler
    $detay_query = "
        SELECT 
            'fatura' as tip,
            fatura_tarihi as tarih,
            CONCAT('Fatura No: ', fatura_no) as aciklama,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'Satış Faturası'
                WHEN fatura_tipi = 'alis' THEN 'Alış Faturası'
            END as tip_display,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'gelir'
                WHEN fatura_tipi = 'alis' THEN 'gider'
            END as kategori_tip,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'Satış Faturaları'
                WHEN fatura_tipi = 'alis' THEN 'Alış Faturaları'
            END as kategori,
            toplam_tutar as tutar
        FROM faturalar 
        WHERE firma_id = ? AND fatura_tarihi BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'kasa' as tip,
            tarih,
            COALESCE(aciklama, 'Kasa Hareketi') as aciklama,
            CASE 
                WHEN islem_tipi = 'gelir' THEN 'Kasa Geliri'
                WHEN islem_tipi = 'gider' THEN 'Kasa Gideri'
            END as tip_display,
            CASE 
                WHEN islem_tipi = 'gelir' THEN 'gelir'
                WHEN islem_tipi = 'gider' THEN 'gider'
            END as kategori_tip,
            COALESCE(kategori, 'Diğer') as kategori,
            tutar
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND tarih BETWEEN ? AND ?
        
        ORDER BY tarih ASC
    ";
    
    $stmt = $db->prepare($detay_query);
    $stmt->bind_param("isssis", $firma_id, $baslangic, $bitis, $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $detay_result = $stmt->get_result();
    
    while ($row = $detay_result->fetch_assoc()) {
        $detaylar[] = $row;
    }
    
    // Toplamları hesapla
    $toplam_gelir = array_sum(array_column($gelirler, 'tutar'));
    $toplam_gider = array_sum(array_column($giderler, 'tutar'));
    $brut_kar = $toplam_gelir - $toplam_gider;
    $net_kar = $brut_kar; // Şu an için net kar = brut kar (vergi, amortisman vb. eklenebilir)
    
    return [
        'toplam_gelir' => $toplam_gelir,
        'toplam_gider' => $toplam_gider,
        'brut_kar' => $brut_kar,
        'net_kar' => $net_kar,
        'gelirler' => $gelirler,
        'giderler' => $giderler,
        'detaylar' => $detaylar
    ];
}
?>
