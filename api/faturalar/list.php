<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hibrit kimlik doğrulama: Session veya JWT
if (isset($_SESSION['user_id'])) {
    // Web panel - session kullan
    $firma_id = get_firma_id();
    $user_id = $_SESSION['user_id'];
} else {
    // Flutter app - JWT kullan
    require_once '../../includes/jwt.php';
    
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        json_error('Authorization header gerekli', 401);
    }
    
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        $firma_id = $decoded->firma_id;
        $user_id = $decoded->user_id;
        
        // Session'ı JWT'den doldur
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad;
        $_SESSION['firma_adi'] = $decoded->firma_adi;
        
    } catch (Exception $e) {
        json_error('Geçersiz token', 401);
    }
}

// Buffer'ı temizle
ob_clean();

try {
    $tip = $_GET['tip'] ?? '';
    $odeme_durumu = $_GET['odeme_durumu'] ?? '';
    $start = $_GET['start'] ?? '';
    $end = $_GET['end'] ?? '';
    
    $where_conditions = ["f.firma_id = ?"];
    $params = [$firma_id];
    $types = "i";
    
    if ($tip) {
        $where_conditions[] = "f.fatura_tipi = ?";
        $params[] = $tip;
        $types .= "s";
    }
    
    if ($odeme_durumu) {
        $where_conditions[] = "f.odeme_durumu = ?";
        $params[] = $odeme_durumu;
        $types .= "s";
    }
    
    if ($start) {
        $where_conditions[] = "f.fatura_tarihi >= ?";
        $params[] = $start;
        $types .= "s";
    }
    
    if ($end) {
        $where_conditions[] = "f.fatura_tarihi <= ?";
        $params[] = $end;
        $types .= "s";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query = "
        SELECT 
            f.id,
            f.fatura_no,
            f.fatura_tarihi,
            f.fatura_tipi,
            f.odeme_durumu,
            f.toplam_tutar,
            f.vade_tarihi,
            c.unvan as cari_unvan,
            f.aciklama,
            f.olusturma_tarihi,
            COALESCE((
                SELECT SUM(tutar) 
                FROM odemeler o 
                WHERE o.fatura_id = f.id
            ), 0) as odenen_tutar
        FROM faturalar f
        LEFT JOIN cariler c ON f.cari_id = c.id
        WHERE $where_clause
        ORDER BY f.fatura_tarihi DESC, f.id DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $faturalar = [];
    while ($row = $result->fetch_assoc()) {
        // Durum gösterimi
        $durum_class = '';
        $durum_text = '';
        
        switch ($row['odeme_durumu']) {
            case 'odendi':
                $durum_class = 'success';
                $durum_text = $row['fatura_tipi'] === 'satis' ? 'Tahsil Edildi' : 'Ödendi';
                break;
            case 'kismi':
                $durum_class = 'warning';
                $durum_text = $row['fatura_tipi'] === 'satis' ? 'Kısmi Tahsilat' : 'Kısmi Ödeme';
                break;
            case 'odenmedi':
                $durum_class = 'danger';
                $durum_text = $row['fatura_tipi'] === 'satis' ? 'Tahsilat Bekliyor' : 'Ödeme Bekliyor';
                break;
            default:
                $durum_class = 'secondary';
                $durum_text = 'Bilinmiyor';
        }
        
        $row['durum_class'] = $durum_class;
        $row['durum_text'] = $durum_text;
        $row['fatura_tipi_text'] = $row['fatura_tipi'] === 'satis' ? 'Satış' : 'Alış';
        
        $faturalar[] = $row;
    }
    
    json_success('Faturalar listelendi', $faturalar);
    
} catch (Exception $e) {
    error_log("Faturalar listesi hatası: " . $e->getMessage());
    json_error('Faturalar yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>