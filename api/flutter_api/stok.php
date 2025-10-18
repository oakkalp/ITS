<?php
/**
 * =====================================================
 * FLUTTER MOBİL UYGULAMA - STOK MODÜLÜ API
 * =====================================================
 * Stok yönetimi için API endpoint'leri
 */

require_once '../../config.php';
require_once '../../includes/auth.php';

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Token doğrulama
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    json_error('Token gerekli!', 401);
}

$payload = verify_jwt_token($token);

if (!$payload) {
    json_error('Geçersiz token!', 401);
}

$firma_id = $payload['firma_id'];
$user_id = $payload['user_id'];
$user_role = $payload['rol'];

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            handleGetStokListesi($firma_id);
            break;
        case 'get':
            handleGetStok($firma_id);
            break;
        case 'create':
            handleCreateStok($firma_id, $user_id);
            break;
        case 'update':
            handleUpdateStok($firma_id, $user_id);
            break;
        case 'delete':
            handleDeleteStok($firma_id);
            break;
        case 'generate_code':
            handleGenerateCode($firma_id);
            break;
        case 'manuel_hareket':
            handleManuelHareket($firma_id, $user_id);
            break;
        case 'hareket_raporu':
            handleHareketRaporu($firma_id);
            break;
        default:
            json_error('Geçersiz işlem', 400);
    }
    
} catch (Exception $e) {
    error_log("Flutter Stok API Hatası: " . $e->getMessage());
    json_error('Sunucu hatası: ' . $e->getMessage(), 500);
}

/**
 * Stok listesini getir
 */
function handleGetStokListesi($firma_id) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT 
            id,
            urun_kodu,
            urun_adi,
            kategori,
            birim,
            stok_miktari,
            alis_fiyati,
            satis_fiyati,
            barkod,
            aciklama,
            aktif,
            olusturma_tarihi
        FROM urunler 
        WHERE firma_id = ? 
        ORDER BY urun_adi ASC
    ");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $urunler = [];
    while ($row = $result->fetch_assoc()) {
        $urunler[] = [
            'id' => $row['id'],
            'urun_kodu' => $row['urun_kodu'],
            'urun_adi' => $row['urun_adi'],
            'kategori' => $row['kategori'],
            'birim' => $row['birim'],
            'stok_miktari' => floatval($row['stok_miktari']),
            'alis_fiyati' => floatval($row['alis_fiyati']),
            'satis_fiyati' => floatval($row['satis_fiyati']),
            'barkod' => $row['barkod'],
            'aciklama' => $row['aciklama'],
            'aktif' => $row['aktif'],
            'olusturma_tarihi' => $row['olusturma_tarihi']
        ];
    }
    
    json_success('Stok listesi getirildi', $urunler);
}

/**
 * Tek stok getir
 */
function handleGetStok($firma_id) {
    global $db;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        json_error('Ürün ID gerekli', 400);
    }
    
    $stmt = $db->prepare("
        SELECT 
            id,
            urun_kodu,
            urun_adi,
            kategori,
            birim,
            stok_miktari,
            alis_fiyati,
            satis_fiyati,
            barkod,
            aciklama,
            aktif,
            olusturma_tarihi
        FROM urunler 
        WHERE id = ? AND firma_id = ?
    ");
    $stmt->bind_param("ii", $id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$urun = $result->fetch_assoc()) {
        json_error('Ürün bulunamadı', 404);
    }
    
    json_success('Ürün getirildi', [
        'id' => $urun['id'],
        'urun_kodu' => $urun['urun_kodu'],
        'urun_adi' => $urun['urun_adi'],
        'kategori' => $urun['kategori'],
        'birim' => $urun['birim'],
        'stok_miktari' => floatval($urun['stok_miktari']),
        'alis_fiyati' => floatval($urun['alis_fiyati']),
        'satis_fiyati' => floatval($urun['satis_fiyati']),
        'barkod' => $urun['barkod'],
        'aciklama' => $urun['aciklama'],
        'aktif' => $urun['aktif'],
        'olusturma_tarihi' => $urun['olusturma_tarihi']
    ]);
}

/**
 * Yeni stok oluştur
 */
function handleCreateStok($firma_id, $user_id) {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    // Zorunlu alanları kontrol et
    $required_fields = ['urun_adi', 'birim'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_error("$field alanı zorunlu", 400);
        }
    }
    
    // Ürün kodu oluştur (eğer verilmemişse)
    $urun_kodu = $input['urun_kodu'] ?? generateUrunKodu($firma_id);
    
    $stmt = $db->prepare("
        INSERT INTO urunler (
            firma_id, urun_kodu, urun_adi, kategori, birim, 
            stok_miktari, alis_fiyati, satis_fiyati, barkod, 
            aciklama, aktif, olusturma_tarihi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $kategori = $input['kategori'] ?? '';
    $birim = $input['birim'];
    $stok_miktari = $input['stok_miktari'] ?? 0;
    $alis_fiyati = $input['alis_fiyati'] ?? 0;
    $satis_fiyati = $input['satis_fiyati'] ?? 0;
    $barkod = $input['barkod'] ?? '';
    $aciklama = $input['aciklama'] ?? '';
    $aktif = $input['aktif'] ?? 1;
    
    $stmt->bind_param(
        "issssdddsii",
        $firma_id,
        $urun_kodu,
        $input['urun_adi'],
        $kategori,
        $birim,
        $stok_miktari,
        $alis_fiyati,
        $satis_fiyati,
        $barkod,
        $aciklama,
        $aktif
    );
    
    if ($stmt->execute()) {
        $urun_id = $db->insert_id;
        json_success('Ürün başarıyla oluşturuldu', ['id' => $urun_id]);
    } else {
        json_error('Ürün oluşturulamadı: ' . $db->error, 500);
    }
}

/**
 * Stok güncelle
 */
function handleUpdateStok($firma_id, $user_id) {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    $id = $input['id'];
    
    // Ürünün var olduğunu kontrol et
    $check_stmt = $db->prepare("SELECT id FROM urunler WHERE id = ? AND firma_id = ?");
    $check_stmt->bind_param("ii", $id, $firma_id);
    $check_stmt->execute();
    if (!$check_stmt->get_result()->fetch_assoc()) {
        json_error('Ürün bulunamadı', 404);
    }
    
    $stmt = $db->prepare("
        UPDATE urunler SET 
            urun_kodu = ?,
            urun_adi = ?,
            kategori = ?,
            birim = ?,
            stok_miktari = ?,
            alis_fiyati = ?,
            satis_fiyati = ?,
            barkod = ?,
            aciklama = ?,
            aktif = ?
        WHERE id = ? AND firma_id = ?
    ");
    
    $urun_kodu = $input['urun_kodu'] ?? '';
    $urun_adi = $input['urun_adi'] ?? '';
    $kategori = $input['kategori'] ?? '';
    $birim = $input['birim'] ?? '';
    $stok_miktari = $input['stok_miktari'] ?? 0;
    $alis_fiyati = $input['alis_fiyati'] ?? 0;
    $satis_fiyati = $input['satis_fiyati'] ?? 0;
    $barkod = $input['barkod'] ?? '';
    $aciklama = $input['aciklama'] ?? '';
    $aktif = $input['aktif'] ?? 1;
    
    $stmt->bind_param(
        "sssddddssiii",
        $urun_kodu,
        $urun_adi,
        $kategori,
        $birim,
        $stok_miktari,
        $alis_fiyati,
        $satis_fiyati,
        $barkod,
        $aciklama,
        $aktif,
        $id,
        $firma_id
    );
    
    if ($stmt->execute()) {
        json_success('Ürün başarıyla güncellendi');
    } else {
        json_error('Ürün güncellenemedi: ' . $db->error, 500);
    }
}

/**
 * Stok sil
 */
function handleDeleteStok($firma_id) {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        json_error('Ürün ID gerekli', 400);
    }
    
    // Ürünün var olduğunu kontrol et
    $check_stmt = $db->prepare("SELECT id FROM urunler WHERE id = ? AND firma_id = ?");
    $check_stmt->bind_param("ii", $id, $firma_id);
    $check_stmt->execute();
    if (!$check_stmt->get_result()->fetch_assoc()) {
        json_error('Ürün bulunamadı', 404);
    }
    
    $stmt = $db->prepare("DELETE FROM urunler WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $id, $firma_id);
    
    if ($stmt->execute()) {
        json_success('Ürün başarıyla silindi');
    } else {
        json_error('Ürün silinemedi: ' . $db->error, 500);
    }
}

/**
 * Ürün kodu oluştur
 */
function handleGenerateCode($firma_id) {
    $urun_kodu = generateUrunKodu($firma_id);
    json_success('Ürün kodu oluşturuldu', ['urun_kodu' => $urun_kodu]);
}

/**
 * Manuel stok hareketi
 */
function handleManuelHareket($firma_id, $user_id) {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json_error('Geçersiz veri formatı', 400);
    }
    
    $required_fields = ['urun_id', 'hareket_tipi', 'miktar'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            json_error("$field alanı zorunlu", 400);
        }
    }
    
    $urun_id = $input['urun_id'];
    $hareket_tipi = $input['hareket_tipi'];
    $miktar = floatval($input['miktar']);
    $birim_fiyat = floatval($input['birim_fiyat'] ?? 0);
    $belge_no = $input['belge_no'] ?? '';
    $aciklama = $input['aciklama'] ?? '';
    
    // Ürünün var olduğunu kontrol et
    $check_stmt = $db->prepare("SELECT stok_miktari FROM urunler WHERE id = ? AND firma_id = ?");
    $check_stmt->bind_param("ii", $urun_id, $firma_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if (!$urun = $result->fetch_assoc()) {
        json_error('Ürün bulunamadı', 404);
    }
    
    $eski_stok = floatval($urun['stok_miktari']);
    
    // Stok hareketini hesapla
    if ($hareket_tipi === 'manuel_giris') {
        $yeni_stok = $eski_stok + $miktar;
    } else {
        $yeni_stok = $eski_stok - $miktar;
    }
    
    // Stok hareketi tablosuna kaydet (varsa)
    $table_check = $db->query("SHOW TABLES LIKE 'stok_hareketleri'");
    if ($table_check->num_rows > 0) {
        $stmt = $db->prepare("
            INSERT INTO stok_hareketleri (
                firma_id, urun_id, hareket_tipi, miktar, birim_fiyat, 
                toplam, belge_no, aciklama, tarih
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $toplam = $miktar * $birim_fiyat;
        $stmt->bind_param(
            "iisddsss",
            $firma_id,
            $urun_id,
            $hareket_tipi,
            $miktar,
            $birim_fiyat,
            $toplam,
            $belge_no,
            $aciklama
        );
        
        if (!$stmt->execute()) {
            json_error('Stok hareketi kaydedilemedi: ' . $db->error, 500);
        }
    }
    
    // Ürün stokunu güncelle
    $update_stmt = $db->prepare("UPDATE urunler SET stok_miktari = ? WHERE id = ? AND firma_id = ?");
    $update_stmt->bind_param("dii", $yeni_stok, $urun_id, $firma_id);
    
    if ($update_stmt->execute()) {
        json_success('Manuel stok hareketi başarıyla kaydedildi', [
            'eski_stok' => $eski_stok,
            'yeni_stok' => $yeni_stok
        ]);
    } else {
        json_error('Stok güncellenemedi: ' . $db->error, 500);
    }
}

/**
 * Stok hareket raporu
 */
function handleHareketRaporu($firma_id) {
    global $db;
    
    $baslangic = $_GET['baslangic'] ?? null;
    $bitis = $_GET['bitis'] ?? null;
    $urun_id = $_GET['urun_id'] ?? null;
    $hareket_tipi = $_GET['hareket_tipi'] ?? null;
    
    if (!$baslangic || !$bitis) {
        json_error('Başlangıç ve bitiş tarihi gerekli', 400);
    }
    
    // Tarih formatını kontrol et
    if (!DateTime::createFromFormat('Y-m-d', $baslangic) || !DateTime::createFromFormat('Y-m-d', $bitis)) {
        json_error('Geçersiz tarih formatı', 400);
    }
    
    $hareket_data = getStokHareketleri($firma_id, $baslangic, $bitis, $urun_id, $hareket_tipi);
    $ozet_data = calculateOzet($hareket_data, $urun_id, $baslangic);
    
    json_success('Stok hareket raporu yüklendi', $hareket_data, 200, $ozet_data);
}

/**
 * Ürün kodu oluştur
 */
function generateUrunKodu($firma_id) {
    global $db;
    
    // Mevcut kodları al
    $stmt = $db->prepare("SELECT urun_kodu FROM urunler WHERE firma_id = ? AND urun_kodu IS NOT NULL AND urun_kodu != ''");
    $stmt->bind_param("i", $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mevcutKodlari = [];
    while ($row = $result->fetch_assoc()) {
        $mevcutKodlari[] = $row['urun_kodu'];
    }
    
    // Yeni kod oluştur
    $year = date('y');
    $month = date('m');
    $day = date('d');
    
    $counter = 1;
    do {
        $urun_kodu = "U{$year}{$month}{$day}" . str_pad($counter, 3, '0', STR_PAD_LEFT);
        $counter++;
    } while (in_array($urun_kodu, $mevcutKodlari));
    
    return $urun_kodu;
}

/**
 * Stok hareketlerini getir
 */
function getStokHareketleri($firma_id, $baslangic, $bitis, $urun_id = null, $hareket_tipi = null) {
    global $db;
    
    $hareketler = [];
    
    // Fatura hareketleri
    $fatura_query = "
        SELECT 
            f.fatura_tarihi as tarih,
            u.urun_adi,
            CASE 
                WHEN f.fatura_tipi = 'alis' THEN 'Alış Faturası'
                WHEN f.fatura_tipi = 'satis' THEN 'Satış Faturası'
            END as hareket_tipi_display,
            f.fatura_tipi as hareket_tipi,
            f.fatura_no as belge_no,
            fd.miktar,
            fd.birim_fiyat,
            fd.toplam,
            u.stok_miktari as kalan_stok
        FROM faturalar f
        JOIN fatura_detaylari fd ON f.id = fd.fatura_id
        JOIN urunler u ON fd.urun_id = u.id
        WHERE f.firma_id = ? 
        AND f.fatura_tarihi BETWEEN ? AND ?
    ";
    
    $params = [$firma_id, $baslangic, $bitis];
    $types = "iss";
    
    if ($urun_id) {
        $fatura_query .= " AND fd.urun_id = ?";
        $params[] = $urun_id;
        $types .= "i";
    }
    
    if ($hareket_tipi) {
        if ($hareket_tipi === 'alis') {
            $fatura_query .= " AND f.fatura_tipi = 'alis'";
        } elseif ($hareket_tipi === 'satis') {
            $fatura_query .= " AND f.fatura_tipi = 'satis'";
        }
    }
    
    $fatura_query .= " ORDER BY f.fatura_tarihi ASC";
    
    $stmt = $db->prepare($fatura_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Miktarı hareket tipine göre ayarla
        if ($row['hareket_tipi'] === 'alis') {
            $row['miktar'] = abs($row['miktar']); // Pozitif (giriş)
        } else {
            $row['miktar'] = -abs($row['miktar']); // Negatif (çıkış)
        }
        
        // Kalan stok formatını düzelt
        $row['kalan_stok'] = round(floatval($row['kalan_stok']));
        
        $hareketler[] = $row;
    }
    
    // Manuel stok hareketleri (tablo varsa)
    $table_check = $db->query("SHOW TABLES LIKE 'stok_hareketleri'");
    if ($table_check->num_rows > 0) {
        $manuel_query = "
            SELECT 
                sh.tarih,
                u.urun_adi,
                CASE 
                    WHEN sh.hareket_tipi = 'manuel_giris' THEN 'Elle Giriş'
                    WHEN sh.hareket_tipi = 'manuel_cikis' THEN 'Elle Çıkış'
                END as hareket_tipi_display,
                sh.hareket_tipi as hareket_tipi,
                sh.belge_no as belge_no,
                sh.miktar,
                sh.birim_fiyat,
                sh.toplam,
                u.stok_miktari as kalan_stok
            FROM stok_hareketleri sh
            JOIN urunler u ON sh.urun_id = u.id
            WHERE sh.firma_id = ? 
            AND DATE(sh.tarih) BETWEEN ? AND ?
            AND sh.hareket_tipi IN ('manuel_giris', 'manuel_cikis')
        ";
        
        $manuel_params = [$firma_id, $baslangic, $bitis];
        $manuel_types = "iss";
        
        if ($urun_id) {
            $manuel_query .= " AND sh.urun_id = ?";
            $manuel_params[] = $urun_id;
            $manuel_types .= "i";
        }
        
        if ($hareket_tipi) {
            if ($hareket_tipi === 'manuel') {
                $manuel_query .= " AND sh.hareket_tipi IN ('manuel_giris', 'manuel_cikis')";
            }
        }
        
        $manuel_query .= " ORDER BY sh.tarih ASC";
        
        $manuel_stmt = $db->prepare($manuel_query);
        $manuel_stmt->bind_param($manuel_types, ...$manuel_params);
        $manuel_stmt->execute();
        $manuel_result = $manuel_stmt->get_result();
        
        while ($row = $manuel_result->fetch_assoc()) {
            // Miktarı hareket tipine göre ayarla
            if ($row['hareket_tipi'] === 'manuel_giris') {
                $row['miktar'] = abs($row['miktar']); // Pozitif (giriş)
            } else {
                $row['miktar'] = -abs($row['miktar']); // Negatif (çıkış)
            }
            
            // Kalan stok formatını düzelt
            $row['kalan_stok'] = round(floatval($row['kalan_stok']));
            
            $hareketler[] = $row;
        }
    }
    
    // Tarihe göre sırala
    usort($hareketler, function($a, $b) {
        return strtotime($a['tarih']) - strtotime($b['tarih']);
    });
    
    return $hareketler;
}

/**
 * Özet hesapla
 */
function calculateOzet($hareketler, $urun_id = null, $baslangic = null) {
    $toplam_giris = 0;
    $toplam_cikis = 0;
    $toplam_deger = 0;
    $toplam_islem = count($hareketler);
    $baslangic_stok = 0;
    $son_stok = 0;
    
    foreach ($hareketler as $hareket) {
        if ($hareket['miktar'] > 0) {
            $toplam_giris += $hareket['miktar'];
        } else {
            $toplam_cikis += abs($hareket['miktar']);
        }
        
        $toplam_deger += $hareket['toplam'];
    }
    
    $net_hareket = $toplam_giris - $toplam_cikis;
    
    // Ortalama fiyat hesaplama
    $ortalama_fiyat = $toplam_islem > 0 ? $toplam_deger / ($toplam_giris + $toplam_cikis) : 0;
    
    return [
        'toplam_giris' => $toplam_giris,
        'toplam_cikis' => $toplam_cikis,
        'net_hareket' => $net_hareket,
        'toplam_deger' => $toplam_deger,
        'toplam_islem' => $toplam_islem,
        'ortalama_fiyat' => $ortalama_fiyat,
        'baslangic_stok' => $baslangic_stok,
        'son_stok' => $son_stok
    ];
}
?>
