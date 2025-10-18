<?php
/**
 * =====================================================
 * FLUTTER MOBİL UYGULAMA - DASHBOARD API
 * =====================================================
 * Ana panel verilerini sağlar
 */

require_once '../../config.php';
require_once '../../includes/auth.php';

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Token doğrulama
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    json_error('Token gerekli!', 401);
}

$payload = verify_jwt_token($token);

if (!$payload) {
    json_error('Geçersiz token!', 401);
}

$firma_id = $payload['firma_id'];
$user_id = $payload['user_id'];
$user_role = $payload['rol'];

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'stats':
            handleGetStats($firma_id, $user_role);
            break;
        case 'recent_activities':
            handleGetRecentActivities($firma_id);
            break;
        case 'alerts':
            handleGetAlerts($firma_id);
            break;
        case 'charts':
            handleGetCharts($firma_id);
            break;
        default:
            json_error('Geçersiz işlem', 400);
    }
    
} catch (Exception $e) {
    error_log("Flutter Dashboard API Hatası: " . $e->getMessage());
    json_error('Sunucu hatası: ' . $e->getMessage(), 500);
}

/**
 * İstatistikleri getir
 */
function handleGetStats($firma_id, $user_role) {
    global $db;
    
    $stats = [];
    
    // Toplam Cari sayısı
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM cariler WHERE firma_id = ? AND aktif = 1");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_cariler'] = $result['total'];
    
    // Toplam Ürün sayısı
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM urunler WHERE firma_id = ? AND aktif = 1");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_urunler'] = $result['total'];
    
    // Toplam Alacak
    $stmt = $db->prepare("
        SELECT SUM(bakiye) as total 
        FROM cariler 
        WHERE firma_id = ? AND aktif = 1 AND bakiye > 0
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_alacaklar'] = $result['total'] ?? 0;
    
    // Toplam Borç
    $stmt = $db->prepare("
        SELECT SUM(ABS(bakiye)) as total 
        FROM cariler 
        WHERE firma_id = ? AND aktif = 1 AND bakiye < 0
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_borclar'] = $result['total'] ?? 0;
    
    // Bu ayki gelir
    $stmt = $db->prepare("
        SELECT SUM(tutar) as total 
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND islem_tipi = 'gelir' 
        AND MONTH(tarih) = MONTH(CURRENT_DATE()) 
        AND YEAR(tarih) = YEAR(CURRENT_DATE())
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['aylik_gelir'] = $result['total'] ?? 0;
    
    // Bu ayki gider
    $stmt = $db->prepare("
        SELECT SUM(tutar) as total 
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND islem_tipi = 'gider' 
        AND MONTH(tarih) = MONTH(CURRENT_DATE()) 
        AND YEAR(tarih) = YEAR(CURRENT_DATE())
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['aylik_gider'] = $result['total'] ?? 0;
    
    // Bugünkü hareket sayısı
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM kasa_hareketleri 
        WHERE firma_id = ? AND DATE(tarih) = CURRENT_DATE()
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['bugun_hareket'] = $result['total'];
    
    json_success('İstatistikler getirildi', $stats);
}

/**
 * Son hareketleri getir
 */
function handleGetRecentActivities($firma_id) {
    global $db;
    
    // Web'deki son-hareketler.php ile aynı sorgu
    $hareket_query = "
        SELECT 
            'fatura' as kaynak,
            fatura_tarihi as tarih,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'Satış Faturası'
                WHEN fatura_tipi = 'alis' THEN 'Alış Faturası'
            END as tip_display,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'gelir'
                WHEN fatura_tipi = 'alis' THEN 'gider'
            END as tip,
            CONCAT('Fatura No: ', fatura_no) as aciklama,
            toplam_tutar as tutar
        FROM faturalar 
        WHERE firma_id = ? 
        
        UNION ALL
        
        SELECT 
            'kasa' as kaynak,
            tarih,
            CASE 
                WHEN islem_tipi = 'gelir' THEN 'Kasa Geliri'
                WHEN islem_tipi = 'gider' THEN 'Kasa Gideri'
            END as tip_display,
            islem_tipi as tip,
            COALESCE(aciklama, 'Kasa Hareketi') as aciklama,
            tutar
        FROM kasa_hareketleri 
        WHERE firma_id = ?
        
        ORDER BY tarih DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($hareket_query);
    $stmt->bind_param("ii", $firma_id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'kaynak' => $row['kaynak'],
            'tip' => $row['tip'],
            'tarih' => $row['tarih'],
            'tip_display' => $row['tip_display'],
            'aciklama' => $row['aciklama'],
            'tutar' => $row['tutar']
        ];
    }
    
    json_success('Son hareketler getirildi', $activities);
}

/**
 * Uyarıları getir
 */
function handleGetAlerts($firma_id) {
    global $db;
    
    $alerts = [];
    
    // Düşük stok uyarıları
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM urunler 
        WHERE firma_id = ? AND aktif = 1 AND stok <= 5
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $alerts[] = [
            'tip' => 'uyari',
            'mesaj' => $result['count'] . ' ürünün stoku kritik seviyede!',
            'icon' => 'bi-exclamation-triangle'
        ];
    }
    
    // Vadesi yaklaşan çekler
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM cekler 
        WHERE firma_id = ? AND durum = 'portfoy' 
        AND vade_tarihi BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $alerts[] = [
            'tip' => 'kritik',
            'mesaj' => $result['count'] . ' çekin vadesi yaklaşıyor!',
            'icon' => 'bi-exclamation-circle'
        ];
    }
    
    // Ödenmemiş faturalar
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM faturalar 
        WHERE firma_id = ? AND odeme_durumu = 'odenmedi'
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $alerts[] = [
            'tip' => 'uyari',
            'mesaj' => $result['count'] . ' fatura ödenmemiş!',
            'icon' => 'bi-receipt'
        ];
    }
    
    json_success('Uyarılar getirildi', $alerts);
}

/**
 * Grafik verilerini getir
 */
function handleGetCharts($firma_id) {
    global $db;
    
    // Son 6 ayın gelir-gider verileri
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(tarih, '%Y-%m') as ay,
            SUM(CASE WHEN islem_tipi = 'gelir' THEN tutar ELSE 0 END) as gelir,
            SUM(CASE WHEN islem_tipi = 'gider' THEN tutar ELSE 0 END) as gider
        FROM kasa_hareketleri 
        WHERE firma_id = ? 
        AND tarih >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(tarih, '%Y-%m')
        ORDER BY ay ASC
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $chartData = [
        'labels' => [],
        'gelirler' => [],
        'giderler' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $chartData['labels'][] = $row['ay'];
        $chartData['gelirler'][] = (float)$row['gelir'];
        $chartData['giderler'][] = (float)$row['gider'];
    }
    
    json_success('Grafik verileri getirildi', $chartData);
}
?>
