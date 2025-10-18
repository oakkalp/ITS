<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 0); // HTML çıktısını engelle
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../config.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Config hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Basit authentication (kullanıcı adı kontrolü)
$username = $_GET['username'] ?? $_POST['username'] ?? '';
if (empty($username)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı gerekli'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // Kullanıcıyı bul
    $stmt = $db->prepare("SELECT id, firma_id, rol FROM kullanicilar WHERE kullanici_adi = ? AND aktif = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $user = $result->fetch_assoc();
    $firma_id = $user['firma_id'];
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı kontrolü hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Rapor tipini al
$rapor_tipi = $_GET['tip'] ?? 'genel';
$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

try {
    switch ($rapor_tipi) {
        case 'genel':
            // Genel rapor - satış, alış, kar, kasa bakiye
            $stmt_satis = $db->prepare("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = ? AND fatura_tipi = 'satis' AND fatura_tarihi BETWEEN ? AND ?");
            $stmt_satis->bind_param("iss", $firma_id, $baslangic, $bitis);
            $stmt_satis->execute();
            $satislar_result = $stmt_satis->get_result()->fetch_assoc();
            $satislar = $satislar_result['total'] ?? 0;
            
            $stmt_alis = $db->prepare("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = ? AND fatura_tipi = 'alis' AND fatura_tarihi BETWEEN ? AND ?");
            $stmt_alis->bind_param("iss", $firma_id, $baslangic, $bitis);
            $stmt_alis->execute();
            $alislar_result = $stmt_alis->get_result()->fetch_assoc();
            $alislar = $alislar_result['total'] ?? 0;
            
            $stmt_gelir = $db->prepare("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = ? AND islem_tipi = 'gelir'");
            $stmt_gelir->bind_param("i", $firma_id);
            $stmt_gelir->execute();
            $gelir_result = $stmt_gelir->get_result()->fetch_assoc();
            $gelir = $gelir_result['total'] ?? 0;
            
            $stmt_gider = $db->prepare("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = ? AND islem_tipi = 'gider'");
            $stmt_gider->bind_param("i", $firma_id);
            $stmt_gider->execute();
            $gider_result = $stmt_gider->get_result()->fetch_assoc();
            $gider = $gider_result['total'] ?? 0;
            
            $data = [[
                'satislar' => floatval($satislar),
                'alislar' => floatval($alislar),
                'kar' => floatval($satislar) - floatval($alislar),
                'kasa_bakiye' => floatval($gelir) - floatval($gider)
            ]];
            break;
            
        case 'satislar':
            // Satış raporu
            $query = "SELECT 
                f.fatura_no,
                f.fatura_tarihi,
                f.toplam_tutar,
                c.unvan as cari_unvan
            FROM faturalar f
            LEFT JOIN cariler c ON f.cari_id = c.id
            WHERE f.firma_id = ? AND f.fatura_tipi = 'satis' AND f.fatura_tarihi BETWEEN ? AND ?
            ORDER BY f.fatura_tarihi DESC
            LIMIT 50";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'alacaklar':
            // Alacaklılar raporu
            $query = "SELECT unvan, bakiye FROM cariler WHERE firma_id = ? AND bakiye > 0 ORDER BY bakiye DESC LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'borclar':
            // Borçlular raporu
            $query = "SELECT unvan, bakiye FROM cariler WHERE firma_id = ? AND bakiye < 0 ORDER BY bakiye ASC LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'urunler':
            // Ürün satış raporu
            $query = "SELECT 
                u.urun_adi,
                u.stok_miktari,
                SUM(fd.adet) as toplam_miktar,
                SUM(fd.toplam_tutar) as toplam_tutar
            FROM fatura_detaylari fd
            INNER JOIN urunler u ON fd.urun_id = u.id
            INNER JOIN faturalar f ON fd.fatura_id = f.id
            WHERE f.firma_id = ? AND f.fatura_tipi = 'satis' AND f.fatura_tarihi BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY toplam_tutar DESC
            LIMIT 10";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'stok':
            // Stok durumu raporu
            $query = "SELECT 
                urun_adi,
                stok_miktari,
                kritik_stok,
                satis_fiyati,
                CASE 
                    WHEN stok_miktari <= kritik_stok THEN 'Kritik'
                    WHEN stok_miktari <= kritik_stok * 2 THEN 'Düşük'
                    ELSE 'Normal'
                END as durum
            FROM urunler 
            WHERE firma_id = ? 
            ORDER BY stok_miktari ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'kasa':
            // Kasa hareketleri raporu
            $query = "SELECT 
                tarih as islem_tarihi,
                islem_tipi,
                aciklama,
                tutar,
                CASE 
                    WHEN islem_tipi = 'gelir' THEN 'Gelir'
                    ELSE 'Gider'
                END as tip_adi
            FROM kasa_hareketleri 
            WHERE firma_id = ? AND tarih BETWEEN ? AND ?
            ORDER BY tarih DESC
            LIMIT 20";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        case 'cekler':
            // Çekler raporu
            $query = "SELECT 
                cek_no,
                cek_tipi,
                tutar,
                vade_tarihi,
                durum,
                CASE 
                    WHEN cari_disi_cek = 1 THEN cari_disi_kisi
                    ELSE car.unvan
                END as kisi_unvan
            FROM cekler c
            LEFT JOIN cariler car ON c.cari_id = car.id
            WHERE c.firma_id = ? AND vade_tarihi BETWEEN ? AND ?
            ORDER BY vade_tarihi ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz rapor tipi'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Rapor başarıyla oluşturuldu',
        'data' => $data,
        'baslangic' => $baslangic,
        'bitis' => $bitis
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
