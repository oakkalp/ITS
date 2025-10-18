<?php
try {
    require_once '../../config.php';
    require_once '../../includes/auth.php';

    // Session kontrolü
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['firma_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Oturum süresi dolmuş']);
        exit;
    }

    $cari_id = $_GET['cari_id'] ?? null;
    $firma_id = $_SESSION['firma_id'];

    if (!$cari_id) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cari ID gerekli']);
        exit;
    }

// Fatura hareketleri
$faturalar = $db->query("
    SELECT 
        CONCAT('fatura_', fatura_tipi) as tip,
        fatura_tarihi as tarih,
        CONCAT('Fatura No: ', fatura_no) as aciklama,
        toplam_tutar as tutar,
        0 as bakiye
    FROM faturalar 
    WHERE cari_id = $cari_id AND firma_id = $firma_id
    ORDER BY fatura_tarihi DESC
")->fetch_all(MYSQLI_ASSOC);

// Odemeler tablosundaki hareketler (ödeme/tahsilat)
$odemeler = $db->query("
    SELECT 
        odeme_tipi as tip,
        odeme_tarihi as tarih,
        aciklama,
        tutar,
        0 as bakiye
    FROM odemeler 
    WHERE cari_id = $cari_id AND firma_id = $firma_id
    ORDER BY odeme_tarihi DESC
")->fetch_all(MYSQLI_ASSOC);

// Kasa hareketleri (ödeme/tahsilat) - sadece bu cari ile ilgili olanları bul
$cari_unvan = $db->query("SELECT unvan FROM cariler WHERE id = $cari_id")->fetch_assoc()['unvan'];
$cari_unvan_escaped = $db->real_escape_string($cari_unvan);

// Bu carinin faturalarını al
$cari_faturalar = $db->query("SELECT fatura_no FROM faturalar WHERE cari_id = $cari_id AND firma_id = $firma_id")->fetch_all(MYSQLI_ASSOC);

$kasa_hareketleri = [];
if (!empty($cari_faturalar)) {
    // Fatura numaralarını LIKE sorgusu için hazırla
    $fatura_conditions = [];
    foreach ($cari_faturalar as $fatura) {
        $fatura_no_escaped = $db->real_escape_string($fatura['fatura_no']);
        $fatura_conditions[] = "aciklama LIKE '%Fatura No: $fatura_no_escaped%'";
    }
    
    $fatura_conditions_str = implode(' OR ', $fatura_conditions);
    
    $kasa_hareketleri = $db->query("
        SELECT 
            CASE 
                WHEN islem_tipi = 'gelir' THEN 'tahsilat'
                WHEN islem_tipi = 'gider' THEN 'odeme'
                ELSE islem_tipi
            END as tip,
            tarih,
            aciklama,
            tutar,
            bakiye
        FROM kasa_hareketleri 
        WHERE firma_id = $firma_id 
        AND (aciklama LIKE '%Cari: $cari_unvan_escaped%' 
             OR $fatura_conditions_str)
        ORDER BY tarih DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

// Tüm hareketleri birleştir ve tarihe göre sırala
$hareketler = array_merge($faturalar, $odemeler, $kasa_hareketleri);

// Tarihe göre sırala
usort($hareketler, function($a, $b) {
    return strtotime($b['tarih']) - strtotime($a['tarih']);
});

// Cari bakiyesini hesapla ve her hareket için bakiye ekle
$cari_bakiye = $db->query("SELECT bakiye FROM cariler WHERE id = $cari_id")->fetch_assoc()['bakiye'];
$running_balance = $cari_bakiye;

// Hareketleri ters sırada işle (en eski tarihten başla)
$hareketler_reversed = array_reverse($hareketler);
foreach ($hareketler_reversed as &$hareket) {
    $hareket['bakiye'] = $running_balance;
    
    // Bakiye hesaplaması (hareket tipine göre)
    if ($hareket['tip'] == 'fatura_alis') {
        // Alış faturası - borç artar (bakiye azalır)
        $running_balance += floatval($hareket['tutar']);
    } elseif ($hareket['tip'] == 'fatura_satis') {
        // Satış faturası - alacak artar (bakiye artar)
        $running_balance -= floatval($hareket['tutar']);
    } elseif ($hareket['tip'] == 'odeme') {
        // Ödeme - borç azalır (bakiye artar)
        $running_balance += floatval($hareket['tutar']);
    } elseif ($hareket['tip'] == 'tahsilat') {
        // Tahsilat - alacak azalır (bakiye azalır)
        $running_balance -= floatval($hareket['tutar']);
    }
}

// Tekrar ters çevir (en yeni tarih önce)
$hareketler = array_reverse($hareketler_reversed);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Hareketler başarıyla getirildi', 'data' => $hareketler]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
