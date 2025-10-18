<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['super_admin']);

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            // Tek firma getir
            $stmt = $db->prepare("SELECT * FROM firmalar WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                json_success('Firma bulundu', $row);
            } else {
                json_error('Firma bulunamadı', 404);
            }
        } else {
            // Tüm firmaları getir
            $result = $db->query("SELECT * FROM firmalar ORDER BY id DESC");
            $firmalar = [];
            while ($row = $result->fetch_assoc()) {
                $firmalar[] = $row;
            }
            json_success('Firmalar listelendi', $firmalar);
        }
        break;
        
    case 'POST':
        // Yeni firma ekle
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("INSERT INTO firmalar (firma_adi, vergi_dairesi, vergi_no, telefon, adres, email, aktif) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", 
            $data['firma_adi'],
            $data['vergi_dairesi'],
            $data['vergi_no'],
            $data['telefon'],
            $data['adres'],
            $data['email'],
            $data['aktif']
        );
        
        if ($stmt->execute()) {
            json_success('Firma başarıyla eklendi', ['id' => $db->insert_id], 201);
        } else {
            json_error('Firma eklenirken hata oluştu: ' . $stmt->error, 500);
        }
        break;
        
    case 'PUT':
        // Firma güncelle
        if (!$id) {
            json_error('Firma ID gerekli', 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $db->prepare("UPDATE firmalar SET firma_adi = ?, vergi_dairesi = ?, vergi_no = ?, telefon = ?, adres = ?, email = ?, aktif = ? WHERE id = ?");
        $stmt->bind_param("ssssssii", 
            $data['firma_adi'],
            $data['vergi_dairesi'],
            $data['vergi_no'],
            $data['telefon'],
            $data['adres'],
            $data['email'],
            $data['aktif'],
            $id
        );
        
        if ($stmt->execute()) {
            json_success('Firma başarıyla güncellendi');
        } else {
            json_error('Firma güncellenirken hata oluştu: ' . $stmt->error, 500);
        }
        break;
        
    case 'DELETE':
        // Firma sil
        if (!$id) {
            json_error('Firma ID gerekli', 400);
        }
        
        // Önce firma ile ilişkili kullanıcıları kontrol et
        $check = $db->query("SELECT COUNT(*) as c FROM kullanicilar WHERE firma_id = $id");
        $count = $check->fetch_assoc()['c'];
        
        if ($count > 0) {
            json_error('Bu firmaya ait ' . $count . ' kullanıcı bulunmaktadır. Önce kullanıcıları silin.', 400);
        }
        
        $stmt = $db->prepare("DELETE FROM firmalar WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            json_success('Firma başarıyla silindi');
        } else {
            json_error('Firma silinirken hata oluştu: ' . $stmt->error, 500);
        }
        break;
        
    default:
        json_error('Geçersiz istek metodu', 405);
}
?>

