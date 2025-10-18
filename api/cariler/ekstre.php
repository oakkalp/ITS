<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('cariler', 'okuma');

try {
    $firma_id = get_firma_id();
    $baslangic = $_GET['baslangic'] ?? null;
    $bitis = $_GET['bitis'] ?? null;
    $cari_id = $_GET['cari_id'] ?? null;
    
    if (!$baslangic || !$bitis) {
        json_error('Başlangıç ve bitiş tarihi gerekli', 400);
    }
    
    // Tarih formatını kontrol et
    if (!DateTime::createFromFormat('Y-m-d', $baslangic) || !DateTime::createFromFormat('Y-m-d', $bitis)) {
        json_error('Geçersiz tarih formatı', 400);
    }
    
    $ekstre_data = [];
    $cari_info = null;
    
    if ($cari_id) {
        // Tek cari için ekstre
        $ekstre_data = getCariEkstre($firma_id, $cari_id, $baslangic, $bitis);
        $cari_info = getCariInfo($firma_id, $cari_id, $baslangic, $bitis);
    } else {
        // Tüm cariler için ekstre
        $ekstre_data = getAllCarilerEkstre($firma_id, $baslangic, $bitis);
    }
    
    json_success('Ekstre yüklendi', $ekstre_data, 200, $cari_info);
    
} catch (Exception $e) {
    error_log("Cari ekstre hatası: " . $e->getMessage());
    json_error('Ekstre yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}

function getCariEkstre($firma_id, $cari_id, $baslangic, $bitis) {
    global $db;
    
    $ekstre = [];
    
    // Başlangıç bakiyesini hesapla
    $baslangic_bakiye = 0;
    
    // Faturalar
    $fatura_query = "
        SELECT 
            'fatura_' || fatura_tipi as tip,
            fatura_tarihi as tarih,
            fatura_no as belge_no,
            CONCAT('Fatura No: ', fatura_no) as aciklama,
            CASE 
                WHEN fatura_tipi = 'alis' THEN toplam_tutar
                ELSE 0
            END as borç,
            CASE 
                WHEN fatura_tipi = 'satis' THEN toplam_tutar
                ELSE 0
            END as alacak,
            0 as bakiye
        FROM faturalar 
        WHERE cari_id = ? AND firma_id = ? 
        AND fatura_tarihi BETWEEN ? AND ?
        ORDER BY fatura_tarihi ASC
    ";
    
    $stmt = $db->prepare($fatura_query);
    $stmt->bind_param("iiss", $cari_id, $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $ekstre[] = $row;
    }
    
    // Kasa hareketleri (ödeme/tahsilat)
    $kasa_query = "
        SELECT 
            'kasa_' || islem_tipi as tip,
            tarih,
            CONCAT('Kasa ', islem_tipi) as belge_no,
            CONCAT(islem_tipi, ' - ', COALESCE(aciklama, '')) as aciklama,
            CASE 
                WHEN islem_tipi = 'gider' THEN tutar
                ELSE 0
            END as borç,
            CASE 
                WHEN islem_tipi = 'gelir' THEN tutar
                ELSE 0
            END as alacak,
            0 as bakiye
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND tarih BETWEEN ? AND ?
        AND (aciklama LIKE CONCAT('%', (SELECT unvan FROM cariler WHERE id = ?), '%') 
             OR aciklama LIKE '%ödeme%' OR aciklama LIKE '%tahsilat%')
        ORDER BY tarih ASC
    ";
    
    $stmt = $db->prepare($kasa_query);
    $stmt->bind_param("issi", $firma_id, $baslangic, $bitis, $cari_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $ekstre[] = $row;
    }
    
    // Tarihe göre sırala
    usort($ekstre, function($a, $b) {
        return strtotime($a['tarih']) - strtotime($b['tarih']);
    });
    
    // Running balance hesapla
    $running_balance = 0;
    foreach ($ekstre as &$item) {
        $running_balance += $item['alacak'] - $item['borç'];
        $item['bakiye'] = $running_balance;
    }
    
    return $ekstre;
}

function getAllCarilerEkstre($firma_id, $baslangic, $bitis) {
    global $db;
    
    $ekstre = [];
    
    // Faturalar
    $fatura_query = "
        SELECT 
            'fatura_' || f.fatura_tipi as tip,
            f.fatura_tarihi as tarih,
            f.fatura_no as belge_no,
            CONCAT('Fatura No: ', f.fatura_no, ' - ', c.unvan) as aciklama,
            CASE 
                WHEN f.fatura_tipi = 'alis' THEN f.toplam_tutar
                ELSE 0
            END as borç,
            CASE 
                WHEN f.fatura_tipi = 'satis' THEN f.toplam_tutar
                ELSE 0
            END as alacak,
            0 as bakiye,
            c.unvan as cari_unvan
        FROM faturalar f
        JOIN cariler c ON f.cari_id = c.id
        WHERE f.firma_id = ? 
        AND f.fatura_tarihi BETWEEN ? AND ?
        ORDER BY f.fatura_tarihi ASC
    ";
    
    $stmt = $db->prepare($fatura_query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $ekstre[] = $row;
    }
    
    // Kasa hareketleri
    $kasa_query = "
        SELECT 
            'kasa_' || islem_tipi as tip,
            tarih,
            CONCAT('Kasa ', islem_tipi) as belge_no,
            CONCAT(islem_tipi, ' - ', COALESCE(aciklama, '')) as aciklama,
            CASE 
                WHEN islem_tipi = 'gider' THEN tutar
                ELSE 0
            END as borç,
            CASE 
                WHEN islem_tipi = 'gelir' THEN tutar
                ELSE 0
            END as alacak,
            0 as bakiye,
            '' as cari_unvan
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND tarih BETWEEN ? AND ?
        ORDER BY tarih ASC
    ";
    
    $stmt = $db->prepare($kasa_query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $ekstre[] = $row;
    }
    
    // Tarihe göre sırala
    usort($ekstre, function($a, $b) {
        return strtotime($a['tarih']) - strtotime($b['tarih']);
    });
    
    return $ekstre;
}

function getCariInfo($firma_id, $cari_id, $baslangic, $bitis) {
    global $db;
    
    // Cari bilgilerini al
    $cari_query = "SELECT * FROM cariler WHERE id = ? AND firma_id = ?";
    $stmt = $db->prepare($cari_query);
    $stmt->bind_param("ii", $cari_id, $firma_id);
    $stmt->execute();
    $cari = $stmt->get_result()->fetch_assoc();
    
    if (!$cari) {
        return null;
    }
    
    // Başlangıç bakiyesini hesapla (tarihten önceki işlemler)
    $baslangic_bakiye_query = "
        SELECT 
            COALESCE(SUM(CASE WHEN fatura_tipi = 'satis' THEN toplam_tutar ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN fatura_tipi = 'alis' THEN toplam_tutar ELSE 0 END), 0) as bakiye
        FROM faturalar 
        WHERE cari_id = ? AND firma_id = ? AND fatura_tarihi < ?
    ";
    
    $stmt = $db->prepare($baslangic_bakiye_query);
    $stmt->bind_param("iis", $cari_id, $firma_id, $baslangic);
    $stmt->execute();
    $baslangic_bakiye = $stmt->get_result()->fetch_assoc()['bakiye'];
    
    // Dönem içi toplamlar
    $toplam_query = "
        SELECT 
            COALESCE(SUM(CASE WHEN fatura_tipi = 'alis' THEN toplam_tutar ELSE 0 END), 0) as toplam_borc,
            COALESCE(SUM(CASE WHEN fatura_tipi = 'satis' THEN toplam_tutar ELSE 0 END), 0) as toplam_alacak
        FROM faturalar 
        WHERE cari_id = ? AND firma_id = ? AND fatura_tarihi BETWEEN ? AND ?
    ";
    
    $stmt = $db->prepare($toplam_query);
    $stmt->bind_param("iiss", $cari_id, $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $toplamlar = $stmt->get_result()->fetch_assoc();
    
    // Son bakiye
    $son_bakiye = $baslangic_bakiye + $toplamlar['toplam_alacak'] - $toplamlar['toplam_borc'];
    
    return [
        'unvan' => $cari['unvan'],
        'vergi_no' => $cari['vergi_no'],
        'telefon' => $cari['telefon'],
        'email' => $cari['email'],
        'adres' => $cari['adres'],
        'baslangic_bakiye' => $baslangic_bakiye,
        'son_bakiye' => $son_bakiye,
        'toplam_borc' => $toplamlar['toplam_borc'],
        'toplam_alacak' => $toplamlar['toplam_alacak']
    ];
}
?>
