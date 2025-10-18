<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// Web paneli için session kontrolü
if (isset($_SESSION['user_id'])) {
    // Web panelinden gelen istek - session kullan
    $firma_id = get_firma_id();
    error_log("Web paneli session kullanılıyor, Firma ID: " . $firma_id);
} else {
    // Flutter uygulamasından gelen istek - JWT token kontrolü
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth_header) || !str_starts_with($auth_header, 'Bearer ')) {
        error_log("Authorization header bulunamadı: " . print_r($headers, true));
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header gerekli']);
        exit;
    }

    $token = substr($auth_header, 7); // "Bearer " kısmını çıkar
    error_log("Token alındı: " . substr($token, 0, 20) . "...");

    // Token'ı decode et ve kullanıcı bilgilerini al
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        error_log("Token decode edildi: " . print_r($decoded, true));
        
        // Session'ı manuel olarak ayarla
        $_SESSION['user_id'] = $decoded->user_id;
        $_SESSION['firma_id'] = $decoded->firma_id;
        $_SESSION['rol'] = $decoded->rol;
        
        $firma_id = $decoded->firma_id;
        error_log("Firma ID token'dan alındı: " . $firma_id);
        
    } catch (Exception $e) {
        error_log("Token decode hatası: " . $e->getMessage());
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
        exit;
    }
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    json_error('Veri gönderilmedi', 400);
}

// Zorunlu alan kontrolü
if (empty($data['urun_id']) || empty($data['hareket_tipi']) || empty($data['miktar'])) {
    json_error('Ürün ID, hareket tipi ve miktar zorunludur', 400);
}

$urun_id = intval($data['urun_id']);
$hareket_tipi = $data['hareket_tipi'];
$miktar = floatval($data['miktar']);
$birim_fiyat = floatval($data['birim_fiyat'] ?? 0);
$belge_no = $data['belge_no'] ?? '';
$aciklama = $data['aciklama'] ?? '';

// Ürünün mevcut stok miktarını al
$stmt = $db->prepare("SELECT stok_miktari FROM urunler WHERE id = ? AND firma_id = ?");
$stmt->bind_param("ii", $urun_id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    json_error('Ürün bulunamadı', 404);
}

$urun = $result->fetch_assoc();
$eski_stok = floatval($urun['stok_miktari']);

// Yeni stok miktarını hesapla
if ($hareket_tipi === 'manuel_giris') {
    $yeni_stok = $eski_stok + $miktar;
} elseif ($hareket_tipi === 'manuel_cikis') {
    $yeni_stok = $eski_stok - $miktar;
    if ($yeni_stok < 0) {
        json_error('Stok miktarı negatif olamaz', 400);
    }
} else {
    json_error('Geçersiz hareket tipi', 400);
}

$db->begin_transaction();

try {
    // Ürün stok miktarını güncelle
    $stmt = $db->prepare("UPDATE urunler SET stok_miktari = ? WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("dii", $yeni_stok, $urun_id, $firma_id);
    $stmt->execute();

    // Stok hareketi kaydı ekle (tam sütunlarla)
    $stmt = $db->prepare("INSERT INTO stok_hareketleri (firma_id, urun_id, hareket_tipi, miktar, birim_fiyat, belge_no, aciklama, eski_stok, yeni_stok, tarih) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisddssdd", $firma_id, $urun_id, $hareket_tipi, $miktar, $birim_fiyat, $belge_no, $aciklama, $eski_stok, $yeni_stok);
    $stmt->execute();

    $db->commit();

    json_success('Manuel stok hareketi başarıyla kaydedildi', [
        'eski_stok' => $eski_stok,
        'yeni_stok' => $yeni_stok,
        'hareket_tipi' => $hareket_tipi,
        'miktar' => $miktar
    ]);

} catch (Exception $e) {
    $db->rollback();
    error_log("Manuel stok hareketi hatası: " . $e->getMessage());
    json_error('Stok hareketi kaydedilemedi: ' . $e->getMessage(), 500);
}
?>