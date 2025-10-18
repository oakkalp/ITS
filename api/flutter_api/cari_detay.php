<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS isteği için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Config dosyasını yükle
    require_once '../../config.php';
    
    // Cari ID kontrolü
    $cari_id = $_GET['id'] ?? null;
    if (!$cari_id) {
        throw new Exception('Cari ID gerekli');
    }
    
    // Veritabanı bağlantısını test et
    if (!isset($db) || !$db) {
        throw new Exception('Veritabanı bağlantısı başarısız - db değişkeni yok');
    }
    
    // Cari bilgilerini getir
    $stmt = $db->prepare("SELECT * FROM cariler WHERE id = ? AND firma_id = 5");
    $stmt->bind_param("i", $cari_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$cari = $result->fetch_assoc()) {
        throw new Exception('Cari bulunamadı');
    }
    
    // Fatura hareketleri
    $faturalar = $db->query("
        SELECT 
            CONCAT('fatura_', fatura_tipi) as tip,
            CASE 
                WHEN fatura_tipi = 'alis' THEN 'Alış Faturası'
                WHEN fatura_tipi = 'satis' THEN 'Satış Faturası'
                ELSE 'Fatura'
            END as tip_display,
            fatura_tarihi as tarih,
            CONCAT('Fatura No: ', fatura_no) as aciklama,
            toplam_tutar as tutar,
            0 as bakiye
        FROM faturalar 
        WHERE cari_id = $cari_id AND firma_id = 5
        ORDER BY fatura_tarihi DESC
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Ödemeler tablosundan hareketleri getir
    $odemeler = $db->query("
        SELECT 
            odeme_tipi as tip,
            CASE 
                WHEN odeme_tipi = 'tahsilat' THEN 'Tahsilat'
                WHEN odeme_tipi = 'odeme' THEN 'Ödeme'
                ELSE 'Ödeme Hareketi'
            END as tip_display,
            odeme_tarihi as tarih,
            aciklama,
            tutar,
            0 as bakiye
        FROM odemeler 
        WHERE cari_id = $cari_id AND firma_id = 5
        ORDER BY odeme_tarihi DESC
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Kasa hareketleri (ödeme/tahsilat) - sadece bu cari ile ilgili olanları bul
    $cari_unvan = $db->real_escape_string($cari['unvan']);
    
    // Bu carinin faturalarını al
    $cari_faturalar = $db->query("SELECT fatura_no FROM faturalar WHERE cari_id = $cari_id AND firma_id = 5")->fetch_all(MYSQLI_ASSOC);
    
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
            WHERE firma_id = 5 
            AND (aciklama LIKE '%Cari: $cari_unvan%' 
                 OR $fatura_conditions_str)
            ORDER BY tarih DESC
        ")->fetch_all(MYSQLI_ASSOC);
    }
    
    // Tüm hareketleri birleştir ve tarihe göre sırala
    $hareketler = array_merge($faturalar, $odemeler, $kasa_hareketleri);
    usort($hareketler, function($a, $b) {
        return strtotime($b['tarih']) - strtotime($a['tarih']);
    });
    
    // Web'deki mantığı kullan - Cari bakiyesini veritabanından al
    $bakiye = floatval($cari['bakiye']);
    
    // Bakiye pozitif ise alacak, negatif ise borç
    if ($bakiye > 0) {
        $total_alacak = $bakiye;
        $total_borc = 0;
    } else if ($bakiye < 0) {
        $total_alacak = 0;
        $total_borc = abs($bakiye);
    } else {
        $total_alacak = 0;
        $total_borc = 0;
    }
    
    // Debug için log ekle
    error_log("Cari ID: $cari_id, Bakiye: $bakiye, Alacak: $total_alacak, Borç: $total_borc");
    
    // Özet bilgiler
    $ozet = [
        'toplam_alacak' => $total_alacak,
        'toplam_borc' => $total_borc,
        'bakiye' => $bakiye,
        'hareket_sayisi' => count($hareketler),
        'son_hareket' => !empty($hareketler) ? $hareketler[0]['tarih'] : null
    ];
    
    // Response
    $response = [
        'success' => true,
        'message' => 'Cari detay getirildi',
        'data' => [
            'cari' => $cari,
            'hareketler' => $hareketler,
            'ozet' => $ozet
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Cari Detay API Hatası: " . $e->getMessage());
    
    $response = [
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage(),
        'data' => null
    ];
    
    http_response_code(500);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>