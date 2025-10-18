<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_role(['firma_yoneticisi']);

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$firma_id = get_firma_id();

// POST ile gelen PUT/DELETE isteklerini kontrol et
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['_method'])) {
        $method = strtoupper($data['_method']);
        if (isset($data['id_param'])) {
            $id = $data['id_param'];
        }
    }
}

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ? AND firma_id = ? AND rol = 'kullanici'");
            $stmt->bind_param("ii", $id, $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                unset($row['sifre']);
                json_success('Kullanıcı bulundu', $row);
            } else {
                json_error('Kullanıcı bulunamadı', 404);
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE firma_id = ? AND rol = 'kullanici' ORDER BY id DESC");
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $kullanicilar = [];
            while ($row = $result->fetch_assoc()) {
                unset($row['sifre']);
                $kullanicilar[] = $row;
            }
            json_success('Kullanıcılar listelendi', $kullanicilar);
        }
        break;
        
    case 'POST':
        // Eğer _method parametresi varsa, data zaten yukarıda tanımlandı
        if (!isset($data)) {
            $data = json_decode(file_get_contents('php://input'), true);
        }
        
        $db->begin_transaction();
        
        try {
            $sifre_hash = hash_password($data['sifre']);
            
            // Kullanıcıyı ekle
            $stmt = $db->prepare("INSERT INTO kullanicilar (firma_id, kullanici_adi, sifre, ad_soyad, email, telefon, rol, aktif) VALUES (?, ?, ?, ?, ?, ?, 'kullanici', ?)");
            $stmt->bind_param("isssssi", 
                $firma_id,
                $data['kullanici_adi'],
                $sifre_hash,
                $data['ad_soyad'],
                $data['email'],
                $data['telefon'],
                $data['aktif']
            );
            $stmt->execute();
            $kullanici_id = $db->insert_id;
            
            // Yetkileri ekle
            if (isset($data['yetkiler'])) {
                $stmt_yetki = $db->prepare("INSERT INTO kullanici_yetkileri (kullanici_id, modul_id, okuma, yazma, guncelleme, silme) VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($data['yetkiler'] as $modul_id => $yetkiler) {
                    $okuma = isset($yetkiler['okuma']) ? 1 : 0;
                    $yazma = isset($yetkiler['yazma']) ? 1 : 0;
                    $guncelleme = isset($yetkiler['guncelleme']) ? 1 : 0;
                    $silme = isset($yetkiler['silme']) ? 1 : 0;
                    
                    // En az bir yetki varsa ekle
                    if ($okuma || $yazma || $guncelleme || $silme) {
                        $stmt_yetki->bind_param("iiiiii", $kullanici_id, $modul_id, $okuma, $yazma, $guncelleme, $silme);
                        $stmt_yetki->execute();
                    }
                }
            }
            
            $db->commit();
            json_success('Kullanıcı ve yetkileri başarıyla eklendi', ['id' => $kullanici_id], 201);
            
        } catch (Exception $e) {
            $db->rollback();
            json_error('Kullanıcı eklenirken hata oluştu: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        if (!$id) {
            json_error('Kullanıcı ID gerekli', 400);
        }
        
        // Eğer _method parametresi varsa, data zaten yukarıda tanımlandı
        if (!isset($data)) {
            $data = json_decode(file_get_contents('php://input'), true);
        }
        
        $db->begin_transaction();
        
        try {
            // Kullanıcıyı güncelle
            if (!empty($data['sifre'])) {
                $sifre_hash = hash_password($data['sifre']);
                $stmt = $db->prepare("UPDATE kullanicilar SET kullanici_adi = ?, sifre = ?, ad_soyad = ?, email = ?, telefon = ?, aktif = ? WHERE id = ? AND firma_id = ?");
                $stmt->bind_param("sssssiii", 
                    $data['kullanici_adi'],
                    $sifre_hash,
                    $data['ad_soyad'],
                    $data['email'],
                    $data['telefon'],
                    $data['aktif'],
                    $id,
                    $firma_id
                );
            } else {
                $stmt = $db->prepare("UPDATE kullanicilar SET kullanici_adi = ?, ad_soyad = ?, email = ?, telefon = ?, aktif = ? WHERE id = ? AND firma_id = ?");
                $stmt->bind_param("ssssiii", 
                    $data['kullanici_adi'],
                    $data['ad_soyad'],
                    $data['email'],
                    $data['telefon'],
                    $data['aktif'],
                    $id,
                    $firma_id
                );
            }
            $stmt->execute();
            
            // Mevcut yetkileri sil
            $db->query("DELETE FROM kullanici_yetkileri WHERE kullanici_id = $id");
            
            // Yeni yetkileri ekle
            if (isset($data['yetkiler'])) {
                $stmt_yetki = $db->prepare("INSERT INTO kullanici_yetkileri (kullanici_id, modul_id, okuma, yazma, guncelleme, silme) VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($data['yetkiler'] as $modul_id => $yetkiler) {
                    $okuma = isset($yetkiler['okuma']) ? 1 : 0;
                    $yazma = isset($yetkiler['yazma']) ? 1 : 0;
                    $guncelleme = isset($yetkiler['guncelleme']) ? 1 : 0;
                    $silme = isset($yetkiler['silme']) ? 1 : 0;
                    
                    if ($okuma || $yazma || $guncelleme || $silme) {
                        $stmt_yetki->bind_param("iiiiii", $id, $modul_id, $okuma, $yazma, $guncelleme, $silme);
                        $stmt_yetki->execute();
                    }
                }
            }
            
            $db->commit();
            json_success('Kullanıcı ve yetkileri başarıyla güncellendi');
            
        } catch (Exception $e) {
            $db->rollback();
            json_error('Kullanıcı güncellenirken hata oluştu: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'DELETE':
        if (!$id) {
            json_error('Kullanıcı ID gerekli', 400);
        }
        
        // Eğer _method parametresi varsa, data zaten yukarıda tanımlandı
        if (!isset($data)) {
            $data = json_decode(file_get_contents('php://input'), true);
        }
        
        $db->begin_transaction();
        
        try {
            // Yetkileri sil
            $db->query("DELETE FROM kullanici_yetkileri WHERE kullanici_id = $id");
            
            // Kullanıcıyı sil
            $stmt = $db->prepare("DELETE FROM kullanicilar WHERE id = ? AND firma_id = ? AND rol = 'kullanici'");
            $stmt->bind_param("ii", $id, $firma_id);
            $stmt->execute();
            
            $db->commit();
            json_success('Kullanıcı ve yetkileri başarıyla silindi');
            
        } catch (Exception $e) {
            $db->rollback();
            json_error('Kullanıcı silinirken hata oluştu: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        json_error('Geçersiz istek metodu', 405);
}
?>

