<?php
ob_start(); // Output buffering başlat

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

ob_clean(); // Buffer'ı temizle

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'genel':
        handleGetGenelRapor();
        break;
    case 'alacaklar':
        handleGetAlacaklar();
        break;
    case 'borclar':
        handleGetBorclar();
        break;
    case 'urunler':
        handleGetUrunRaporu();
        break;
    case 'aylik':
        handleGetAylikRapor();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetGenelRapor() {
    global $db, $firma_id;

    $baslangic = $_GET['baslangic'] ?? date('Y-m-01');
    $bitis = $_GET['bitis'] ?? date('Y-m-d');

    // Satışlar
    $stmt = $db->prepare("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = ? AND fatura_tipi = 'satis' AND fatura_tarihi BETWEEN ? AND ?");
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $satislar = $stmt->get_result()->fetch_assoc()['total'];

    // Alışlar
    $stmt = $db->prepare("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = ? AND fatura_tipi = 'alis' AND fatura_tarihi BETWEEN ? AND ?");
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $alislar = $stmt->get_result()->fetch_assoc()['total'];

    // Kasa bakiye
    $stmt = $db->prepare("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = ? AND islem_tipi = 'gelir'");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $gelir = $stmt->get_result()->fetch_assoc()['total'];

    $stmt = $db->prepare("SELECT COALESCE(SUM(tutar), 0) as total FROM kasa_hareketleri WHERE firma_id = ? AND islem_tipi = 'gider'");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $gider = $stmt->get_result()->fetch_assoc()['total'];

    $data = [
        'satislar' => $satislar,
        'alislar' => $alislar,
        'kar' => $satislar - $alislar,
        'kasa_bakiye' => $gelir - $gider
    ];

    json_success('Genel rapor', $data);
}

function handleGetAlacaklar() {
    global $db, $firma_id;

    $limit = $_GET['limit'] ?? 10;

    $query = "SELECT * FROM cariler WHERE firma_id = ? AND bakiye > 0 ORDER BY bakiye DESC LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $firma_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $cariler = [];
    while ($row = $result->fetch_assoc()) {
        $cariler[] = $row;
    }

    json_success('Alacaklılar', $cariler);
}

function handleGetBorclar() {
    global $db, $firma_id;

    $limit = $_GET['limit'] ?? 10;

    $query = "SELECT * FROM cariler WHERE firma_id = ? AND bakiye < 0 ORDER BY bakiye ASC LIMIT ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $firma_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $cariler = [];
    while ($row = $result->fetch_assoc()) {
        $cariler[] = $row;
    }

    json_success('Borçlular', $cariler);
}

function handleGetUrunRaporu() {
    global $db, $firma_id;

    $baslangic = $_GET['baslangic'] ?? date('Y-m-01');
    $bitis = $_GET['bitis'] ?? date('Y-m-d');

    $query = "SELECT 
        u.urun_adi,
        u.stok_miktari,
        SUM(fd.miktar) as toplam_miktar,
        SUM(fd.toplam) as toplam_tutar
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

    $urunler = [];
    while ($row = $result->fetch_assoc()) {
        $urunler[] = $row;
    }

    json_success('Ürün raporu', $urunler);
}

function handleGetAylikRapor() {
    global $db, $firma_id;

    // Son 12 ay
    $aylar = [];
    for ($i = 11; $i >= 0; $i--) {
        $tarih = date('Y-m', strtotime("-$i months"));
        
        $stmt = $db->prepare("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = ? AND fatura_tipi = 'satis' AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = ?");
        $stmt->bind_param("is", $firma_id, $tarih);
        $stmt->execute();
        $satislar = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $db->prepare("SELECT COALESCE(SUM(toplam_tutar), 0) as total FROM faturalar WHERE firma_id = ? AND fatura_tipi = 'alis' AND DATE_FORMAT(fatura_tarihi, '%Y-%m') = ?");
        $stmt->bind_param("is", $firma_id, $tarih);
        $stmt->execute();
        $alislar = $stmt->get_result()->fetch_assoc()['total'];
        
        $aylar[] = [
            'ay' => $tarih,
            'satislar' => $satislar,
            'alislar' => $alislar
        ];
    }

    json_success('Aylık rapor', $aylar);
}
?>
