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
        $firma_id = $decoded->firma_id ?? $decoded->firmaId ?? null;
        $user_id = $decoded->user_id ?? $decoded->userId ?? null;
        
        // Debug log
        error_log("JWT Decoded - firma_id: $firma_id, user_id: $user_id");
        error_log("JWT Decoded - Full token: " . json_encode($decoded));
        
        if (!$firma_id || !$user_id) {
            json_error('Token eksik bilgi içeriyor', 401);
        }
        
        // Session'ı JWT'den doldur (ad_soyad ve firma_adi eksik olsa bile)
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad ?? 'Kullanıcı';
        $_SESSION['firma_adi'] = $decoded->firma_adi ?? 'Firma';
        
    } catch (Exception $e) {
        error_log("JWT Decode Error: " . $e->getMessage());
        json_error('Geçersiz token: ' . $e->getMessage(), 401);
    }
}

// Buffer'ı temizle
ob_clean();

$data = json_decode(file_get_contents('php://input'), true);

// Debug log
error_log("Çek Create - Gelen veri: " . json_encode($data));
error_log("Çek Create - Firma ID: $firma_id, User ID: $user_id");

if (!$data) {
    json_error('Geçersiz veri', 400);
}

// Zorunlu alanları kontrol et
$required_fields = ['cek_tipi', 'cek_no', 'banka_adi', 'tutar', 'vade_tarihi'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        json_error("$field alanı gerekli", 400);
    }
}

$cari_id = !empty($data['cari_id']) ? intval($data['cari_id']) : null;
$cari_disi_kisi = $data['cari_disi_kisi'] ?? null;
$cek_kaynagi = $data['cek_kaynagi'] ?? null;
$durum = $data['durum'] ?? 'portfoy';
$aciklama = $data['aciklama'] ?? null;
$sube = $data['sube'] ?? null;

// Debug log
error_log("Çek Create - Gelen veri: " . json_encode($data));
error_log("Çek Create - Firma ID: $firma_id, User ID: $user_id");
error_log("Çek Create - Şube: " . ($data['sube'] ?? 'YOK'));

// Değişkenleri önce tanımla
$cek_tipi = $data['cek_tipi'];
$cek_no = $data['cek_no'];
$banka_adi = $data['banka_adi'];
$tutar = $data['tutar'];
$vade_tarihi = $data['vade_tarihi'];

$stmt = $db->prepare("INSERT INTO cekler (firma_id, cek_tipi, cari_id, cari_disi_kisi, cek_no, banka_adi, sube, tutar, vade_tarihi, cek_kaynagi, durum, aciklama, kullanici_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isissssdssssi", 
    $firma_id,
    $cek_tipi,
    $cari_id,
    $cari_disi_kisi,
    $cek_no,
    $banka_adi,
    $sube,
    $tutar,
    $vade_tarihi,
    $cek_kaynagi,
    $durum,
    $aciklama,
    $user_id
);

if ($stmt->execute()) {
    json_success('Çek başarıyla eklendi', ['id' => $db->insert_id]);
} else {
    json_error('Çek eklenirken hata oluştu', 500);
}
?>