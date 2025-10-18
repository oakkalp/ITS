<?php
ob_start(); // Output buffering başlat

require_once '../../config.php';
require_once '../../includes/auth.php';
require_once '../../includes/jwt.php';

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
    
    // Yetki kontrolü (geçici olarak devre dışı)
    // if (!has_permission($kullanici_id, 'personel', 'okuma')) {
    //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
    // }

} catch (Exception $e) {
    json_error('Geçersiz token: ' . $e->getMessage(), 401);
}

ob_clean(); // Buffer'ı temizle

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleGetPersonel();
        break;
    case 'stats':
        handleGetPersonelStats();
        break;
    case 'get':
        handleGetPersonelById();
        break;
    case 'create':
        // if (!has_permission($kullanici_id, 'personel', 'yazma')) {
        //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
        // }
        handleCreatePersonel();
        break;
    case 'update':
        // if (!has_permission($kullanici_id, 'personel', 'guncelleme')) {
        //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
        // }
        handleUpdatePersonel();
        break;
    case 'delete':
        // if (!has_permission($kullanici_id, 'personel', 'silme')) {
        //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
        // }
        handleDeletePersonel();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetPersonel() {
    global $db, $firma_id;

    $query = "SELECT * FROM personel WHERE firma_id = ? ORDER BY ad_soyad ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $personel = [];
    while ($row = $result->fetch_assoc()) {
        $personel[] = $row;
    }

    json_success('Personel listelendi', $personel);
}

function handleGetPersonelStats() {
    global $db, $firma_id;

    $toplam = $db->query("SELECT COUNT(*) as c FROM personel WHERE firma_id = $firma_id")->fetch_assoc()['c'];
    $aktif = $db->query("SELECT COUNT(*) as c FROM personel WHERE firma_id = $firma_id AND aktif = 1")->fetch_assoc()['c'];
    $pasif = $db->query("SELECT COUNT(*) as c FROM personel WHERE firma_id = $firma_id AND aktif = 0")->fetch_assoc()['c'];
    $toplam_maas = $db->query("SELECT COALESCE(SUM(maas), 0) as total FROM personel WHERE firma_id = $firma_id AND aktif = 1")->fetch_assoc()['total'];

    json_success('İstatistikler', [
        'toplam' => $toplam,
        'aktif' => $aktif,
        'pasif' => $pasif,
        'toplam_maas' => $toplam_maas
    ]);
}

function handleGetPersonelById() {
    global $db, $firma_id;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_error('Personel ID gerekli', 400);
    }

    $stmt = $db->prepare("SELECT * FROM personel WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $personel = $result->fetch_assoc();

    if (!$personel) {
        json_error('Personel bulunamadı', 404);
    }

    json_success('Personel getirildi', $personel);
}

function handleCreatePersonel() {
    global $db, $firma_id, $kullanici_id;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['ad_soyad'])) {
        json_error('Ad Soyad gerekli', 400);
    }

    $ad_soyad = $data['ad_soyad'];
    $telefon = $data['telefon'] ?? '';
    $tc_no = $data['tc_no'] ?? '';
    $gorev = $data['gorev'] ?? '';
    $maas = floatval($data['maas'] ?? 0);
    $ise_giris_tarihi = $data['ise_giris_tarihi'] ?? null;
    $adres = $data['adres'] ?? '';
    $aktif = intval($data['aktif'] ?? 1);

    $stmt = $db->prepare("INSERT INTO personel (firma_id, kullanici_id, ad_soyad, telefon, tc_no, gorev, maas, ise_giris_tarihi, adres, aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssdssi", $firma_id, $kullanici_id, $ad_soyad, $telefon, $tc_no, $gorev, $maas, $ise_giris_tarihi, $adres, $aktif);

    if (!$stmt->execute()) {
        json_error('Personel eklenirken hata oluştu: ' . $stmt->error, 500);
    }

    json_success('Personel başarıyla eklendi');
}

function handleUpdatePersonel() {
    global $db, $firma_id;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'], $data['ad_soyad'])) {
        json_error('ID ve Ad Soyad gerekli', 400);
    }

    $id = $data['id'];
    $ad_soyad = $data['ad_soyad'];
    $telefon = $data['telefon'] ?? '';
    $tc_no = $data['tc_no'] ?? '';
    $gorev = $data['gorev'] ?? '';
    $maas = floatval($data['maas'] ?? 0);
    $ise_giris_tarihi = $data['ise_giris_tarihi'] ?? null;
    $adres = $data['adres'] ?? '';
    $aktif = intval($data['aktif'] ?? 1);

    $stmt = $db->prepare("UPDATE personel SET ad_soyad = ?, telefon = ?, tc_no = ?, gorev = ?, maas = ?, ise_giris_tarihi = ?, adres = ?, aktif = ? WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ssssdssiii", $ad_soyad, $telefon, $tc_no, $gorev, $maas, $ise_giris_tarihi, $adres, $aktif, $id, $firma_id);

    if (!$stmt->execute()) {
        json_error('Personel güncellenirken hata oluştu: ' . $stmt->error, 500);
    }

    json_success('Personel başarıyla güncellendi');
}

function handleDeletePersonel() {
    global $db, $firma_id;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        json_error('Personel ID gerekli', 400);
    }

    $stmt = $db->prepare("DELETE FROM personel WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $id, $firma_id);

    if (!$stmt->execute()) {
        json_error('Personel silinirken hata oluştu: ' . $stmt->error, 500);
    }

    json_success('Personel başarıyla silindi');
}
?>
