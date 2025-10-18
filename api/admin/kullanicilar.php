<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['super_admin']);

// Buffer'ı temizle
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT k.*, f.firma_adi FROM kullanicilar k LEFT JOIN firmalar f ON k.firma_id = f.id WHERE k.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                unset($row['sifre']); // Şifreyi gönderme
                json_success('Kullanıcı bulundu', $row);
            } else {
                json_error('Kullanıcı bulunamadı', 404);
            }
        } else {
            $result = $db->query("SELECT k.*, f.firma_adi FROM kullanicilar k LEFT JOIN firmalar f ON k.firma_id = f.id ORDER BY k.id DESC");
            $kullanicilar = [];
            while ($row = $result->fetch_assoc()) {
                unset($row['sifre']);
                $kullanicilar[] = $row;
            }
            json_success('Kullanıcılar listelendi', $kullanicilar);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        // Eğer data null ise hata döndür
        if ($data === null) {
            json_error('Geçersiz JSON verisi', 400);
        }
        
        if ($action === 'delete') {
            $id = $data['id'] ?? null;
            if (!$id) {
                json_error('Kullanıcı ID gerekli', 400);
            }
            
            // Super admin silinemez
            $check = $db->query("SELECT rol FROM kullanicilar WHERE id = $id");
            $user = $check->fetch_assoc();
            if ($user['rol'] == 'super_admin') {
                json_error('Super Admin kullanıcısı silinemez!', 400);
            }
            
            $stmt = $db->prepare("DELETE FROM kullanicilar WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                json_success('Kullanıcı başarıyla silindi');
            } else {
                json_error('Kullanıcı silinirken hata oluştu: ' . $stmt->error, 500);
            }
            break;
        } elseif ($action === 'update') {
            error_log("Kullanıcı güncelleme API çağrıldı");
            error_log("Gelen data: " . print_r($data, true));
            
            $id = $data['id'] ?? null;
            if (!$id) {
                json_error('Kullanıcı ID gerekli', 400);
            }
            
            // Mevcut kullanıcıyı kontrol et
            $check = $db->query("SELECT * FROM kullanicilar WHERE id = $id");
            if ($check->num_rows == 0) {
                json_error('Kullanıcı bulunamadı', 404);
            }
            
            // Kullanıcı adı benzersizlik kontrolü (mevcut kullanıcı hariç)
            $username_check = $db->query("SELECT id FROM kullanicilar WHERE kullanici_adi = '" . $data['kullanici_adi'] . "' AND id != $id");
            if ($username_check->num_rows > 0) {
                json_error('Bu kullanıcı adı zaten kullanılıyor', 400);
            }
            
            // Şifre güncellemesi varsa
            if (!empty($data['sifre'])) {
                error_log("Şifre güncelleme ile");
                $hashed_password = password_hash($data['sifre'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE kullanicilar SET firma_id = ?, rol = ?, kullanici_adi = ?, sifre = ?, ad_soyad = ?, email = ?, telefon = ?, aktif = ? WHERE id = ?");
                $stmt->bind_param("isssssssi",
                    $data['firma_id'],
                    $data['rol'],
                    $data['kullanici_adi'],
                    $hashed_password,
                    $data['ad_soyad'],
                    $data['email'],
                    $data['telefon'],
                    $data['aktif'],
                    $id
                );
            } else {
                error_log("Şifre güncelleme olmadan");
                $stmt = $db->prepare("UPDATE kullanicilar SET firma_id = ?, rol = ?, kullanici_adi = ?, ad_soyad = ?, email = ?, telefon = ?, aktif = ? WHERE id = ?");
                $stmt->bind_param("issssssi",
                    $data['firma_id'],
                    $data['rol'],
                    $data['kullanici_adi'],
                    $data['ad_soyad'],
                    $data['email'],
                    $data['telefon'],
                    $data['aktif'],
                    $id
                );
            }
            
            if ($stmt->execute()) {
                json_success('Kullanıcı başarıyla güncellendi');
            } else {
                json_error('Kullanıcı güncellenirken hata oluştu: ' . $stmt->error, 500);
            }
            break;
        } else {
            // Yeni kullanıcı ekleme
            // Kullanıcı adı benzersizlik kontrolü
            $username_check = $db->query("SELECT id FROM kullanicilar WHERE kullanici_adi = '" . $data['kullanici_adi'] . "'");
            if ($username_check->num_rows > 0) {
                json_error('Bu kullanıcı adı zaten kullanılıyor', 400);
            }
            
            $sifre_hash = hash_password($data['sifre']);
            
            $stmt = $db->prepare("INSERT INTO kullanicilar (firma_id, kullanici_adi, sifre, ad_soyad, email, telefon, rol, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", 
                $data['firma_id'],
                $data['kullanici_adi'],
                $sifre_hash,
                $data['ad_soyad'],
                $data['email'],
                $data['telefon'],
                $data['rol'],
                $data['aktif']
            );
            
            if ($stmt->execute()) {
                json_success('Kullanıcı başarıyla eklendi', ['id' => $db->insert_id], 201);
            } else {
                json_error('Kullanıcı eklenirken hata oluştu: ' . $stmt->error, 500);
            }
        }
        break;
        
    case 'PUT':
        // PUT method artık kullanılmıyor, POST ile action: update kullanılıyor
        json_error('PUT method desteklenmiyor. POST method ile action: update kullanın.', 405);
        break;
        
    case 'DELETE':
        // DELETE method artık kullanılmıyor, POST ile action: delete kullanılıyor
        json_error('DELETE method desteklenmiyor. POST method ile action: delete kullanın.', 405);
        break;
        
    default:
        json_error('Geçersiz istek metodu', 405);
}
?>