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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı kontrolü hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Method'a göre işlem yap
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Kullanıcı bilgilerini getir
        try {
            $query = "SELECT kullanici_adi, ad_soyad, email, telefon, rol FROM kullanicilar WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            $userData = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => 'Kullanıcı bilgileri getirildi',
                'data' => $userData
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    case 'PUT':
        // Kullanıcı bilgilerini güncelle veya şifre değiştir
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz JSON: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Şifre değiştirme kontrolü
            if (isset($input['eski_sifre']) && isset($input['yeni_sifre'])) {
                // Şifre değiştirme
                $eski_sifre = $input['eski_sifre'];
                $yeni_sifre = $input['yeni_sifre'];
                
                // Eski şifreyi kontrol et
                $stmt_check = $db->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
                $stmt_check->bind_param("i", $user['id']);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows === 0) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                
                $user_check = $result_check->fetch_assoc();
                
                // Şifre kontrolü (basit karşılaştırma - production'da hash kullanılmalı)
                if ($user_check['sifre'] !== $eski_sifre) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Eski şifre yanlış'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                
                // Yeni şifreyi kaydet
                $stmt_update = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
                $stmt_update->bind_param("si", $yeni_sifre, $user['id']);
                
                if ($stmt_update->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Şifre başarıyla değiştirildi'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Şifre güncellenirken hata oluştu: ' . $stmt_update->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                // Profil bilgilerini güncelle
                $ad_soyad = $input['ad_soyad'] ?? '';
                $email = $input['email'] ?? '';
                $telefon = $input['telefon'] ?? '';
                
                $stmt_update = $db->prepare("UPDATE kullanicilar SET ad_soyad = ?, email = ?, telefon = ? WHERE id = ?");
                $stmt_update->bind_param("sssi", $ad_soyad, $email, $telefon, $user['id']);
                
                if ($stmt_update->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Profil başarıyla güncellendi'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Profil güncellenirken hata oluştu: ' . $stmt_update->error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
}
?>





