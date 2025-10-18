<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';
require_once '../../includes/jwt.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// JWT Token kontrolü
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
    
    // JWT decode sonucu array ise object'e çevir
    if (is_array($decoded)) {
        $decoded = (object) $decoded;
    }
    
    $firma_id = $decoded->firma_id;
    $user_id = $decoded->user_id;
} catch (Exception $e) {
    json_error('Geçersiz token', 401);
}

// Action parametresi
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        handleGetFaturalar();
        break;
    case 'get':
        handleGetFatura();
        break;
    case 'detay':
        handleGetFaturaDetay();
        break;
    case 'create':
        handleCreateFatura();
        break;
    case 'update':
        handleUpdateFatura();
        break;
    case 'delete':
        handleDeleteFatura();
        break;
    case 'odeme':
        handleFaturaOdeme();
        break;
    default:
        json_error('Geçersiz action', 400);
}

function handleGetFaturalar() {
    global $firma_id;
    
    try {
        error_log("Faturalar API: handleGetFaturalar başladı, firma_id: $firma_id");
        
        // Database bağlantısını yeniden oluştur
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            error_log("Faturalar API: Database bağlantı hatası: " . $db->connect_error);
            throw new Exception('Database bağlantı hatası: ' . $db->connect_error);
        }
        
        error_log("Faturalar API: Database bağlantısı başarılı");
        
        $tip = $_GET['tip'] ?? '';
        $odeme_durumu = $_GET['odeme_durumu'] ?? '';
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';
        
        $where_conditions = ["f.firma_id = ?"];
        $params = [$firma_id];
        $types = "i";
        
        if ($tip) {
            $where_conditions[] = "f.fatura_tipi = ?";
            $params[] = $tip;
            $types .= "s";
        }
        
        if ($odeme_durumu) {
            $where_conditions[] = "f.odeme_durumu = ?";
            $params[] = $odeme_durumu;
            $types .= "s";
        }
        
        if ($start) {
            $where_conditions[] = "f.fatura_tarihi >= ?";
            $params[] = $start;
            $types .= "s";
        }
        
        if ($end) {
            $where_conditions[] = "f.fatura_tarihi <= ?";
            $params[] = $end;
            $types .= "s";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT 
                f.id,
                f.fatura_no,
                f.fatura_tarihi,
                f.fatura_tipi,
                f.odeme_durumu,
                f.toplam_tutar,
                f.vade_tarihi,
                f.aciklama,
                f.olusturma_tarihi,
                c.unvan as cari_unvan,
                COALESCE((
                    SELECT SUM(tutar) 
                    FROM odemeler o 
                    WHERE o.fatura_id = f.id
                ), 0) as odenen_tutar
            FROM faturalar f
            LEFT JOIN cariler c ON f.cari_id = c.id
            WHERE $where_clause
            ORDER BY f.fatura_tarihi DESC, f.id DESC
        ";
        
        error_log("Faturalar API: Query: $query");
        error_log("Faturalar API: Params: " . json_encode($params));
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Query prepare hatası: ' . $db->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $faturalar = [];
        while ($row = $result->fetch_assoc()) {
            // Durum gösterimi
            $durum_class = '';
            $durum_text = '';
            
            switch ($row['odeme_durumu']) {
                case 'odendi':
                    $durum_class = 'success';
                    $durum_text = $row['fatura_tipi'] === 'satis' ? 'Tahsil Edildi' : 'Ödendi';
                    break;
                case 'kismi':
                    $durum_class = 'warning';
                    $durum_text = $row['fatura_tipi'] === 'satis' ? 'Kısmi Tahsilat' : 'Kısmi Ödeme';
                    break;
                case 'odenmedi':
                    $durum_class = 'danger';
                    $durum_text = $row['fatura_tipi'] === 'satis' ? 'Tahsilat Bekliyor' : 'Ödeme Bekliyor';
                    break;
                default:
                    $durum_class = 'secondary';
                    $durum_text = 'Bilinmiyor';
            }
            
            $row['durum_class'] = $durum_class;
            $row['durum_text'] = $durum_text;
            $row['fatura_tipi_text'] = $row['fatura_tipi'] === 'satis' ? 'Satış' : 'Alış';
            
            $faturalar[] = $row;
        }
        
        $db->close();
        
        error_log("Faturalar API: " . count($faturalar) . " fatura bulundu");
        
        json_success('Faturalar listelendi', $faturalar);
        
    } catch (Exception $e) {
        error_log("Faturalar listesi hatası: " . $e->getMessage());
        json_error('Faturalar yüklenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleGetFatura() {
    global $db, $firma_id;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        json_error('Fatura ID gerekli', 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                f.*,
                c.unvan as cari_unvan,
                c.vergi_no as cari_vergi_no,
                c.adres as cari_adres
            FROM faturalar f
            LEFT JOIN cariler c ON f.cari_id = c.id
            WHERE f.id = ? AND f.firma_id = ?
        ");
        $stmt->bind_param("ii", $id, $firma_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $fatura = $result->fetch_assoc();
        
        if (!$fatura) {
            json_error('Fatura bulunamadı', 404);
        }
        
        json_success('Fatura başarıyla getirildi', $fatura);
        
    } catch (Exception $e) {
        error_log("Fatura get hatası: " . $e->getMessage());
        json_error('Fatura getirilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleGetFaturaDetay() {
    global $db;
    
    $fatura_id = $_GET['fatura_id'] ?? null;
    
    if (!$fatura_id) {
        json_error('Fatura ID gerekli', 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                fd.*,
                u.urun_adi
            FROM fatura_detaylari fd
            LEFT JOIN urunler u ON fd.urun_id = u.id
            WHERE fd.fatura_id = ?
            ORDER BY fd.id
        ");
        $stmt->bind_param("i", $fatura_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $detaylar = [];
        while ($row = $result->fetch_assoc()) {
            $detaylar[] = $row;
        }
        
        json_success('Fatura detayları başarıyla getirildi', $detaylar);
        
    } catch (Exception $e) {
        error_log("Fatura detay hatası: " . $e->getMessage());
        json_error('Fatura detayları getirilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleCreateFatura() {
    global $db, $firma_id, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Fatura oluştur
        $stmt = $db->prepare("
            INSERT INTO faturalar (
                firma_id, cari_id, fatura_tipi, fatura_no, fatura_tarihi, 
                vade_tarihi, odeme_tipi, ara_toplam, kdv_tutari, genel_toplam, 
                toplam_tutar, odenen_tutar, kalan_tutar, odeme_durumu, 
                aciklama, kullanici_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $fatura_no = $input['fatura_no'] ?? '';
        $cari_id = $input['cari_id'] ?? null;
        $fatura_tipi = $input['fatura_tipi'] ?? '';
        $fatura_tarihi = $input['fatura_tarihi'] ?? '';
        $vade_tarihi = $input['vade_tarihi'] ?? '';
        $odeme_tipi = $input['odeme_tipi'] ?? '';
        $ara_toplam = $input['ara_toplam'] ?? 0;
        $kdv_tutari = $input['kdv_tutari'] ?? 0;
        $genel_toplam = $input['genel_toplam'] ?? 0;
        $toplam_tutar = $input['toplam_tutar'] ?? 0;
        $odenen_tutar = 0;
        $kalan_tutar = $toplam_tutar;
        $odeme_durumu = $input['odeme_durumu'] ?? 'odenmedi';
        $aciklama = $input['aciklama'] ?? '';
        
        $stmt->bind_param(
            "iisssssddddddssi",
            $firma_id, $cari_id, $fatura_tipi, $fatura_no, $fatura_tarihi,
            $vade_tarihi, $odeme_tipi, $ara_toplam, $kdv_tutari, $genel_toplam,
            $toplam_tutar, $odenen_tutar, $kalan_tutar, $odeme_durumu,
            $aciklama, $user_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Fatura oluşturulamadı: ' . $stmt->error);
        }
        
        $fatura_id = $db->insert_id;
        
        // Fatura detaylarını ekle
        if (isset($input['urunler']) && is_array($input['urunler'])) {
            $detay_stmt = $db->prepare("
                INSERT INTO fatura_detaylari (
                    fatura_id, urun_id, miktar, birim_fiyat, kdv_orani, 
                    ara_toplam, kdv_tutar, toplam
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($input['urunler'] as $urun) {
                $urun_id = $urun['urun_id'] ?? null;
                $miktar = $urun['miktar'] ?? 0;
                $birim_fiyat = $urun['birim_fiyat'] ?? 0;
                $kdv_orani = $urun['kdv_orani'] ?? 0;
                $ara_toplam = $urun['ara_toplam'] ?? 0;
                $kdv_tutar = $urun['kdv_tutar'] ?? 0;
                $toplam = $urun['toplam'] ?? 0;
                
                $detay_stmt->bind_param(
                    "iidddddd",
                    $fatura_id, $urun_id, $miktar, $birim_fiyat, $kdv_orani,
                    $ara_toplam, $kdv_tutar, $toplam
                );
                
                if (!$detay_stmt->execute()) {
                    throw new Exception('Fatura detayı eklenemedi: ' . $detay_stmt->error);
                }
            }
        }
        
        // Cari bakiyesini güncelle
        $bakiye_degisim = 0;
        if ($fatura_tipi === 'alis') {
            // Alış faturası = bizden borç (negatif bakiye)
            $bakiye_degisim = -$toplam_tutar;
        } else {
            // Satış faturası = bizden alacak (pozitif bakiye)
            $bakiye_degisim = $toplam_tutar;
        }
        
        $bakiye_stmt = $db->prepare("UPDATE cariler SET bakiye = bakiye + ? WHERE id = ? AND firma_id = ?");
        $bakiye_stmt->bind_param("dii", $bakiye_degisim, $cari_id, $firma_id);
        $bakiye_stmt->execute();
        
        error_log("Cari bakiyesi güncellendi - Cari ID: $cari_id, Değişim: $bakiye_degisim");
        
        $db->commit();
        json_success('Fatura başarıyla oluşturuldu', ['id' => $fatura_id]);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Fatura oluşturma hatası: " . $e->getMessage());
        json_error('Fatura oluşturulurken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleUpdateFatura() {
    global $db, $firma_id, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        json_error('Fatura ID gerekli', 400);
    }
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Fatura güncelle
        $stmt = $db->prepare("
            UPDATE faturalar SET 
                cari_id = ?, fatura_tarihi = ?, vade_tarihi = ?, 
                odeme_tipi = ?, ara_toplam = ?, kdv_tutari = ?, 
                genel_toplam = ?, toplam_tutar = ?, kalan_tutar = ?, 
                odeme_durumu = ?, aciklama = ?
            WHERE id = ? AND firma_id = ?
        ");
        
        $cari_id = $input['cari_id'] ?? null;
        $fatura_tarihi = $input['fatura_tarihi'] ?? '';
        $vade_tarihi = $input['vade_tarihi'] ?? '';
        $odeme_tipi = $input['odeme_tipi'] ?? '';
        $ara_toplam = $input['ara_toplam'] ?? 0;
        $kdv_tutari = $input['kdv_tutari'] ?? 0;
        $genel_toplam = $input['genel_toplam'] ?? 0;
        $toplam_tutar = $input['toplam_tutar'] ?? 0;
        $kalan_tutar = $toplam_tutar;
        $odeme_durumu = $input['odeme_durumu'] ?? 'odenmedi';
        $aciklama = $input['aciklama'] ?? '';
        
        $stmt->bind_param(
            "isssddddddssii",
            $cari_id, $fatura_tarihi, $vade_tarihi, $odeme_tipi,
            $ara_toplam, $kdv_tutari, $genel_toplam, $toplam_tutar,
            $kalan_tutar, $odeme_durumu, $aciklama, $id, $firma_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Fatura güncellenemedi: ' . $stmt->error);
        }
        
        // Eski detayları sil
        $delete_stmt = $db->prepare("DELETE FROM fatura_detaylari WHERE fatura_id = ?");
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        
        // Yeni detayları ekle
        if (isset($input['urunler']) && is_array($input['urunler'])) {
            $detay_stmt = $db->prepare("
                INSERT INTO fatura_detaylari (
                    fatura_id, urun_id, miktar, birim_fiyat, kdv_orani, 
                    ara_toplam, kdv_tutar, toplam
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($input['urunler'] as $urun) {
                $urun_id = $urun['urun_id'] ?? null;
                $miktar = $urun['miktar'] ?? 0;
                $birim_fiyat = $urun['birim_fiyat'] ?? 0;
                $kdv_orani = $urun['kdv_orani'] ?? 0;
                $ara_toplam = $urun['ara_toplam'] ?? 0;
                $kdv_tutar = $urun['kdv_tutar'] ?? 0;
                $toplam = $urun['toplam'] ?? 0;
                
                $detay_stmt->bind_param(
                    "iidddddd",
                    $id, $urun_id, $miktar, $birim_fiyat, $kdv_orani,
                    $ara_toplam, $kdv_tutar, $toplam
                );
                
                if (!$detay_stmt->execute()) {
                    throw new Exception('Fatura detayı eklenemedi: ' . $detay_stmt->error);
                }
            }
        }
        
        $db->commit();
        json_success('Fatura başarıyla güncellendi');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Fatura güncelleme hatası: " . $e->getMessage());
        json_error('Fatura güncellenirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleDeleteFatura() {
    global $db, $firma_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        json_error('Fatura ID gerekli', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Önce detayları sil
        $delete_detay_stmt = $db->prepare("DELETE FROM fatura_detaylari WHERE fatura_id = ?");
        $delete_detay_stmt->bind_param("i", $id);
        $delete_detay_stmt->execute();
        
        // Sonra faturayı sil
        $delete_stmt = $db->prepare("DELETE FROM faturalar WHERE id = ? AND firma_id = ?");
        $delete_stmt->bind_param("ii", $id, $firma_id);
        
        if (!$delete_stmt->execute()) {
            throw new Exception('Fatura silinemedi: ' . $delete_stmt->error);
        }
        
        if ($delete_stmt->affected_rows === 0) {
            throw new Exception('Fatura bulunamadı');
        }
        
        $db->commit();
        json_success('Fatura başarıyla silindi');
    
} catch (Exception $e) {
        $db->rollback();
        error_log("Fatura silme hatası: " . $e->getMessage());
        json_error('Fatura silinirken hata oluştu: ' . $e->getMessage(), 500);
    }
}

function handleFaturaOdeme() {
    global $db, $firma_id, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    try {
        $db->begin_transaction();
        
        // Ödeme kaydı oluştur
        $stmt = $db->prepare("
            INSERT INTO odemeler (
                firma_id, fatura_id, odeme_tipi, tutar, odeme_tarihi, 
                odeme_yontemi, aciklama, kullanici_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $fatura_id = $input['fatura_id'] ?? null;
        $odeme_tipi = $input['odeme_tipi'] ?? '';
        $tutar = $input['odeme_tutari'] ?? 0;
        $odeme_tarihi = $input['odeme_tarihi'] ?? '';
        $odeme_yontemi = $input['odeme_yontemi'] ?? '';
        $aciklama = $input['aciklama'] ?? '';
        
        $stmt->bind_param(
            "iisdsssi",
            $firma_id, $fatura_id, $odeme_tipi, $tutar, $odeme_tarihi,
            $odeme_yontemi, $aciklama, $user_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Ödeme kaydedilemedi: ' . $stmt->error);
        }
        
        // Fatura ödeme durumunu güncelle
        $update_stmt = $db->prepare("
            UPDATE faturalar SET 
                odenen_tutar = odenen_tutar + ?,
                kalan_tutar = toplam_tutar - (odenen_tutar + ?),
                odeme_durumu = CASE 
                    WHEN (odenen_tutar + ?) >= toplam_tutar THEN 'odendi'
                    WHEN (odenen_tutar + ?) > 0 THEN 'kismi'
                    ELSE 'odenmedi'
                END
            WHERE id = ? AND firma_id = ?
        ");
        
        $update_stmt->bind_param("ddddii", $tutar, $tutar, $tutar, $tutar, $fatura_id, $firma_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Fatura güncellenemedi: ' . $update_stmt->error);
        }
        
        $db->commit();
        json_success('Ödeme başarıyla kaydedildi');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Fatura ödeme hatası: " . $e->getMessage());
        json_error('Ödeme kaydedilirken hata oluştu: ' . $e->getMessage(), 500);
    }
}
?>