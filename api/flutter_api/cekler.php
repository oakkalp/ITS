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
    // if (!has_permission($kullanici_id, 'cekler', 'okuma')) {
    //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
    // }

} catch (Exception $e) {
    json_error('Geçersiz token: ' . $e->getMessage(), 401);
}

ob_clean(); // Buffer'ı temizle

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleGetCekler();
        break;
    case 'stats':
        handleGetCeklerStats();
        break;
    case 'get':
        handleGetCek();
        break;
    case 'create':
        // if (!has_permission($kullanici_id, 'cekler', 'yazma')) {
        //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
        // }
        handleCreateCek();
        break;
    case 'update':
        // if (!has_permission($kullanici_id, 'cekler', 'guncelleme')) {
        //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
        // }
        handleUpdateCek();
        break;
    case 'delete':
        // if (!has_permission($kullanici_id, 'cekler', 'silme')) {
        //     json_error('Bu işlemi yapmaya yetkiniz yok.', 403);
        // }
        handleDeleteCek();
        break;
    case 'vadesi_yaklasan':
        handleGetVadesiYaklasanCekler();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetCekler() {
    global $db, $firma_id;

    // Debug log
    error_log("=== CEKLER LIST DEBUG ===");
    error_log("Firma ID: $firma_id");

    // Check if cekler table exists
    $table_check = $db->query("SHOW TABLES LIKE 'cekler'");
    if ($table_check->num_rows == 0) {
        error_log("cekler table does not exist!");
        json_error('Çekler tablosu bulunamadı. Lütfen sistem yöneticisi ile iletişime geçin.', 500);
    }

    $query = "SELECT c.*, car.unvan as cari_unvan FROM cekler c 
              LEFT JOIN cariler car ON c.cari_id = car.id 
              WHERE c.firma_id = ? ORDER BY c.vade_tarihi ASC";

    $stmt = $db->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $db->error);
        json_error('Veritabanı sorgusu hazırlanamadı: ' . $db->error, 500);
    }

    $stmt->bind_param("i", $firma_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        json_error('Veritabanı sorgusu çalıştırılamadı: ' . $stmt->error, 500);
    }

    $result = $stmt->get_result();
    $cekler = [];
    
    while ($row = $result->fetch_assoc()) {
        // Vade tarihi hesaplama
        $vade_tarihi = new DateTime($row['vade_tarihi']);
        $bugun = new DateTime();
        $kalan_gun = $bugun->diff($vade_tarihi)->days;
        
        if ($vade_tarihi < $bugun) {
            $kalan_gun = -$kalan_gun; // Geçmiş tarihler için negatif
        }
        
        $row['kalan_gun'] = $kalan_gun;
        $cekler[] = $row;
    }

    error_log("Found " . count($cekler) . " çek records");
    if (count($cekler) > 0) {
        error_log("First çek: " . json_encode($cekler[0]));
    }

    json_success('Çekler listelendi', $cekler);
}

function handleGetCeklerStats() {
    global $db, $firma_id;

    // Toplam çek sayısı
    $toplam_query = "SELECT COUNT(*) as c FROM cekler WHERE firma_id = ?";
    $stmt = $db->prepare($toplam_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $toplam = $stmt->get_result()->fetch_assoc()['c'];

    // Portföydeki çekler
    $portfoy_query = "SELECT COUNT(*) as c FROM cekler WHERE firma_id = ? AND durum = 'portfoy'";
    $stmt = $db->prepare($portfoy_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $portfoy = $stmt->get_result()->fetch_assoc()['c'];

    // Vadesi yaklaşan çekler (10 gün içinde)
    $yaklasan_query = "SELECT COUNT(*) as c FROM cekler WHERE firma_id = ? AND vade_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 10 DAY) AND durum IN ('portfoy', 'beklemede')";
    $stmt = $db->prepare($yaklasan_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $yaklasan = $stmt->get_result()->fetch_assoc()['c'];

    // Vadesi geçen çekler
    $gecen_query = "SELECT COUNT(*) as c FROM cekler WHERE firma_id = ? AND vade_tarihi < CURDATE() AND durum IN ('portfoy', 'beklemede')";
    $stmt = $db->prepare($gecen_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $gecen = $stmt->get_result()->fetch_assoc()['c'];

    // Toplam tutar
    $tutar_query = "SELECT COALESCE(SUM(tutar), 0) as t FROM cekler WHERE firma_id = ? AND durum IN ('portfoy', 'beklemede')";
    $stmt = $db->prepare($tutar_query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $toplam_tutar = $stmt->get_result()->fetch_assoc()['t'];

    $stats = [
        'toplam' => $toplam,
        'portfoy' => $portfoy,
        'vadesi_yaklasan' => $yaklasan,
        'vadesi_gecen' => $gecen,
        'toplam_tutar' => $toplam_tutar
    ];

    json_success('İstatistikler', $stats);
}

function handleGetCek() {
    global $db, $firma_id;

    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_error('Çek ID gerekli', 400);
    }

    $query = "SELECT c.*, car.unvan as cari_unvan FROM cekler c 
              LEFT JOIN cariler car ON c.cari_id = car.id 
              WHERE c.id = ? AND c.firma_id = ?";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cek = $result->fetch_assoc();

    if (!$cek) {
        json_error('Çek bulunamadı', 404);
    }

    // Vade tarihi hesaplama
    $vade_tarihi = new DateTime($cek['vade_tarihi']);
    $bugun = new DateTime();
    $kalan_gun = $bugun->diff($vade_tarihi)->days;
    
    if ($vade_tarihi < $bugun) {
        $kalan_gun = -$kalan_gun;
    }
    
    $cek['kalan_gun'] = $kalan_gun;

    json_success('Çek detayı', $cek);
}

function handleCreateCek() {
    global $db, $firma_id, $kullanici_id;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['cek_tipi'], $data['cek_no'], $data['tutar'], $data['banka_adi'], $data['vade_tarihi'])) {
        json_error('Eksik veri', 400);
    }

    $cek_tipi = $data['cek_tipi'];
    $cek_no = $data['cek_no'];
    $cari_id = $data['cari_id'] ?? null;
    $cari_disi_kisi = $data['cari_disi_kisi'] ?? '';
    $cek_kaynagi = $data['cek_kaynagi'] ?? '';
    $tutar = floatval($data['tutar']);
    $banka_adi = $data['banka_adi'];
    $sube = $data['sube'] ?? '';
    $vade_tarihi = $data['vade_tarihi'];
    $durum = $data['durum'] ?? 'portfoy';
    $aciklama = $data['aciklama'] ?? '';

    // Cari dışı çek kontrolü
    if (!$cari_id || $cari_id === '' || $cari_id === '0') {
        if (empty($cari_disi_kisi)) {
            json_error('Cari dışı çek için kişi/şirket adı gerekli', 400);
        }
        $cari_id = null;
    } else {
        $cari_disi_kisi = '';
        $cek_kaynagi = '';
    }

    $stmt = $db->prepare("INSERT INTO cekler (firma_id, kullanici_id, cek_tipi, cek_no, cari_id, cari_disi_kisi, cek_kaynagi, tutar, banka_adi, sube, vade_tarihi, durum, aciklama) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssssdssss",
        $firma_id,
        $kullanici_id,
        $cek_tipi,
        $cek_no,
        $cari_id,
        $cari_disi_kisi,
        $cek_kaynagi,
        $tutar,
        $banka_adi,
        $sube,
        $vade_tarihi,
        $durum,
        $aciklama
    );

    if (!$stmt->execute()) {
        json_error('Çek eklenirken hata oluştu: ' . $stmt->error, 500);
    }

    json_success('Çek başarıyla eklendi');
}

function handleUpdateCek() {
    global $db, $firma_id;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        json_error('Çek ID gerekli', 400);
    }

    if (!isset($data['cek_tipi'], $data['cek_no'], $data['tutar'], $data['banka_adi'], $data['vade_tarihi'])) {
        json_error('Eksik veri', 400);
    }

    $cek_tipi = $data['cek_tipi'];
    $cek_no = $data['cek_no'];
    $cari_id = $data['cari_id'] ?? null;
    $cari_disi_kisi = $data['cari_disi_kisi'] ?? '';
    $cek_kaynagi = $data['cek_kaynagi'] ?? '';
    $tutar = floatval($data['tutar']);
    $banka_adi = $data['banka_adi'];
    $sube = $data['sube'] ?? '';
    $vade_tarihi = $data['vade_tarihi'];
    $durum = $data['durum'] ?? 'portfoy';
    $aciklama = $data['aciklama'] ?? '';

    // Cari dışı çek kontrolü
    if (!$cari_id || $cari_id === '' || $cari_id === '0') {
        if (empty($cari_disi_kisi)) {
            json_error('Cari dışı çek için kişi/şirket adı gerekli', 400);
        }
        $cari_id = null;
    } else {
        $cari_disi_kisi = '';
        $cek_kaynagi = '';
    }

    $stmt = $db->prepare("UPDATE cekler SET cek_tipi = ?, cek_no = ?, cari_id = ?, cari_disi_kisi = ?, cek_kaynagi = ?, tutar = ?, banka_adi = ?, sube = ?, vade_tarihi = ?, durum = ?, aciklama = ? WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("sssssdsssssii",
        $cek_tipi,
        $cek_no,
        $cari_id,
        $cari_disi_kisi,
        $cek_kaynagi,
        $tutar,
        $banka_adi,
        $sube,
        $vade_tarihi,
        $durum,
        $aciklama,
        $id,
        $firma_id
    );

    if (!$stmt->execute()) {
        json_error('Çek güncellenirken hata oluştu: ' . $stmt->error, 500);
    }

    json_success('Çek başarıyla güncellendi');
}

function handleDeleteCek() {
    global $db, $firma_id;

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        json_error('Çek ID gerekli', 400);
    }

    $stmt = $db->prepare("DELETE FROM cekler WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $id, $firma_id);

    if (!$stmt->execute()) {
        json_error('Çek silinirken hata oluştu: ' . $stmt->error, 500);
    }

    json_success('Çek başarıyla silindi');
}

function handleGetVadesiYaklasanCekler() {
    global $db, $firma_id;

    // Vadesi yaklaşan çekler (10 gün içinde)
    $query = "SELECT c.*, car.unvan as cari_unvan FROM cekler c 
              LEFT JOIN cariler car ON c.cari_id = car.id 
              WHERE c.firma_id = ? AND c.vade_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 10 DAY) 
              AND c.durum IN ('portfoy', 'beklemede')
              ORDER BY c.vade_tarihi ASC";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cekler = [];
    while ($row = $result->fetch_assoc()) {
        // Vade tarihi hesaplama
        $vade_tarihi = new DateTime($row['vade_tarihi']);
        $bugun = new DateTime();
        $kalan_gun = $bugun->diff($vade_tarihi)->days;
        
        if ($vade_tarihi < $bugun) {
            $kalan_gun = -$kalan_gun;
        }
        
        $row['kalan_gun'] = $kalan_gun;
        $cekler[] = $row;
    }

    json_success('Vadesi yaklaşan çekler', $cekler);
}
?>
