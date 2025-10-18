<?php
// Error reporting'i tamamen kapat
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// Output buffering başlat ve temizle
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Config'i yükle
require_once '../../config.php';
require_once '../../includes/jwt.php';

// Basit kasa ekleme API
try {
    // JWT token doğrulama
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authorization header gerekli']);
        exit;
    }
    
    $payload = JWT::decode($token, JWT_SECRET_KEY);
    $firma_id = $payload['firma_id'];
    $user_id = $payload['user_id'];
    
    // POST body'den veri al
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz JSON formatı']);
        exit;
    }
    
    // Input validation
    if (empty($input['aciklama']) || empty($input['tutar'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'aciklama ve tutar alanları gerekli']);
        exit;
    }
    
    // Kasa hareketi kaydet
    $query = "INSERT INTO kasa_hareketleri (
                firma_id, tarih, aciklama, tutar, islem_tipi, 
                kategori, odeme_yontemi, kullanici_id
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("issdsssi", 
        $firma_id,
        $input['tarih'] ?? date('Y-m-d'),
        $input['aciklama'],
        floatval($input['tutar']),
        $input['islem_tipi'] ?? 'gelir',
        $input['kategori'] ?? 'genel',
        $input['odeme_yontemi'] ?? 'nakit',
        $user_id
    );
    
    if ($stmt->execute()) {
        $hareket_id = $db->insert_id;
        
        // Buffer'ı temizle
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Kasa hareketi başarıyla eklendi',
            'data' => [
                'id' => $hareket_id,
                'aciklama' => $input['aciklama'],
                'tutar' => $input['tutar']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    // Buffer'ı temizle
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>