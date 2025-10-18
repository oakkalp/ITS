<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

// Sadece POST veya GET isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Geçersiz istek metodu', 405);
}

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
    
    // Yetki kontrolü - sadece firma yöneticisi
    $stmt_role = $db->prepare("SELECT rol FROM kullanicilar WHERE id = ? AND firma_id = ?");
    $stmt_role->bind_param("ii", $kullanici_id, $firma_id);
    $stmt_role->execute();
    $role_result = $stmt_role->get_result();
    $user_role = $role_result->fetch_assoc()['rol'] ?? '';
    
    if ($user_role !== 'firma_yoneticisi') {
        json_error('Bu işlemi yapmaya yetkiniz yok. Sadece firma yöneticisi kullanıcıları bu modüle erişebilir.', 403);
    }

} catch (Exception $e) {
    json_error('Geçersiz token: ' . $e->getMessage(), 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleGetKullanicilar();
        break;
    case 'get':
        handleGetKullanici();
        break;
    case 'create':
        handleCreateKullanici();
        break;
    case 'update':
        handleUpdateKullanici();
        break;
    case 'delete':
        handleDeleteKullanici();
        break;
    case 'moduller':
        handleGetModuller();
        break;
    case 'yetkiler':
        handleGetYetkiler();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetKullanicilar() {
    global $db, $firma_id;
    
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE firma_id = ? AND rol = 'kullanici' ORDER BY id DESC");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $kullanicilar = [];
    while ($row = $result->fetch_assoc()) {
        unset($row['sifre']); // Şifreyi güvenlik için çıkar
        $kullanicilar[] = $row;
    }
    
    json_success('Kullanıcılar listelendi', $kullanicilar);
}

function handleGetKullanici() {
    global $db, $firma_id;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_error('Kullanıcı ID gerekli', 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ? AND firma_id = ? AND rol = 'kullanici'");
    $stmt->bind_param("ii", $id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        unset($row['sifre']); // Şifreyi güvenlik için çıkar
        json_success('Kullanıcı bulundu', $row);
    } else {
        json_error('Kullanıcı bulunamadı', 404);
    }
}

function handleCreateKullanici() {
    global $db, $firma_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['kullanici_adi'], $data['sifre'], $data['ad_soyad'])) {
        json_error('Eksik veri', 400);
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
            $data['email'] ?? '',
            $data['telefon'] ?? '',
            $data['aktif'] ?? 1
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
        error_log("Kullanıcı eklenirken hata: " . $e->getMessage());
        json_error('Kullanıcı eklenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleUpdateKullanici() {
    global $db, $firma_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_error('Kullanıcı ID gerekli', 400);
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
                $data['email'] ?? '',
                $data['telefon'] ?? '',
                $data['aktif'] ?? 1,
                $id,
                $firma_id
            );
        } else {
            $stmt = $db->prepare("UPDATE kullanicilar SET kullanici_adi = ?, ad_soyad = ?, email = ?, telefon = ?, aktif = ? WHERE id = ? AND firma_id = ?");
            $kullanici_adi = $data['kullanici_adi'];
            $ad_soyad = $data['ad_soyad'];
            $email = $data['email'] ?? '';
            $telefon = $data['telefon'] ?? '';
            $aktif = $data['aktif'] ?? 1;
            $stmt->bind_param("ssssiii", 
                $kullanici_adi,
                $ad_soyad,
                $email,
                $telefon,
                $aktif,
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
        error_log("Kullanıcı güncellenirken hata: " . $e->getMessage());
        json_error('Kullanıcı güncellenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleDeleteKullanici() {
    global $db, $firma_id;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        json_error('Kullanıcı ID gerekli', 400);
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
        error_log("Kullanıcı silinirken hata: " . $e->getMessage());
        json_error('Kullanıcı silinirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleGetModuller() {
    global $db;
    
    $result = $db->query("SELECT * FROM moduller WHERE modul_kodu != 'ayarlar' ORDER BY sira");
    $moduller = [];
    
    while ($row = $result->fetch_assoc()) {
        $moduller[] = $row;
    }
    
    json_success('Modüller listelendi', $moduller);
}

function handleGetYetkiler() {
    global $db;
    
    $kullanici_id = $_GET['kullanici_id'] ?? null;
    if (!$kullanici_id) {
        json_error('Kullanıcı ID gerekli', 400);
    }
    
    $stmt = $db->prepare("SELECT * FROM kullanici_yetkileri WHERE kullanici_id = ?");
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $yetkiler = [];
    while ($row = $result->fetch_assoc()) {
        $yetkiler[] = $row;
    }
    
    json_success('Yetkiler listelendi', $yetkiler);
}
?>
