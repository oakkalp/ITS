<?php
/**
 * =====================================================
 * FLUTTER MOBİL UYGULAMA - CARİLER API
 * =====================================================
 * Web panel ile aynı veritabanını kullanır
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

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    handleGetCariler($firma_id);
                    break;
                case 'detail':
                    handleGetCariDetail($firma_id);
                    break;
                case 'ekstre':
                    handleGetCariEkstre($firma_id);
                    break;
                default:
                    json_error('Geçersiz işlem', 400);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'create':
                    handleCreateCari($firma_id, $user_id);
                    break;
                case 'update':
                    handleUpdateCari($firma_id, $user_id);
                    break;
                case 'delete':
                    handleDeleteCari($firma_id);
                    break;
                default:
                    json_error('Geçersiz işlem', 400);
            }
            break;
            
        case 'PUT':
            switch ($action) {
                case 'update':
                    handleUpdateCari($firma_id, $user_id);
                    break;
                default:
                    json_error('Geçersiz işlem', 400);
            }
            break;
            
        default:
            json_error('Desteklenmeyen HTTP metodu', 405);
    }
    
} catch (Exception $e) {
    error_log("Flutter Cariler API Hatası: " . $e->getMessage());
    json_error('Sunucu hatası: ' . $e->getMessage(), 500);
}

/**
 * Cariler listesini getir
 */
function handleGetCariler($firma_id) {
    global $db;
    
    $search = $_GET['search'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 25);
    $offset = ($page - 1) * $limit;
    
    $whereClause = "WHERE firma_id = ? AND aktif = 1";
    $params = [$firma_id];
    $paramTypes = "i";
    
    if (!empty($search)) {
        $whereClause .= " AND (unvan LIKE ? OR yetkili_kisi LIKE ? OR telefon LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $paramTypes .= "sss";
    }
    
    // Toplam kayıt sayısı
    $countQuery = "SELECT COUNT(*) as total FROM cariler $whereClause";
    $stmt = $db->prepare($countQuery);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $totalRecords = $stmt->get_result()->fetch_assoc()['total'];
    
    // Önce tablo yapısını kontrol et
    $checkColumns = $db->query("SHOW COLUMNS FROM cariler");
    $columns = [];
    while ($row = $checkColumns->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $hasCariTipi = in_array('cari_tipi', $columns);
    $hasIsMusteri = in_array('is_musteri', $columns);
    $hasIsTedarikci = in_array('is_tedarikci', $columns);
    $hasCariKodu = in_array('cari_kodu', $columns);
    
    // Cariler listesi - dinamik kolon seçimi
    $selectFields = "id, unvan, yetkili_kisi, telefon, email, adres, vergi_no, vergi_dairesi, bakiye";
    if ($hasCariTipi) $selectFields .= ", cari_tipi";
    if ($hasIsMusteri) $selectFields .= ", is_musteri";
    if ($hasIsTedarikci) $selectFields .= ", is_tedarikci";
    if ($hasCariKodu) $selectFields .= ", cari_kodu";
    $selectFields .= ", olusturma_tarihi";
    
    $query = "
        SELECT $selectFields
        FROM cariler 
        $whereClause 
        ORDER BY unvan ASC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $paramTypes .= "ii";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cariler = [];
    while ($row = $result->fetch_assoc()) {
        $cari = [
            'id' => $row['id'],
            'unvan' => $row['unvan'],
            'yetkili_kisi' => $row['yetkili_kisi'],
            'telefon' => $row['telefon'],
            'email' => $row['email'],
            'adres' => $row['adres'],
            'vergi_no' => $row['vergi_no'],
            'vergi_dairesi' => $row['vergi_dairesi'],
            'bakiye' => (float)$row['bakiye'],
            'olusturma_tarihi' => $row['olusturma_tarihi']
        ];
        
        // Dinamik kolonları ekle
        if ($hasCariTipi && isset($row['cari_tipi'])) {
            $cari['cari_tipi'] = $row['cari_tipi'];
        } elseif ($hasIsMusteri && $hasIsTedarikci) {
            // is_musteri ve is_tedarikci kolonlarından cari_tipi belirle
            if ($row['is_musteri'] == 1 && $row['is_tedarikci'] == 1) {
                $cari['cari_tipi'] = 'her_ikisi';
            } elseif ($row['is_musteri'] == 1) {
                $cari['cari_tipi'] = 'musteri';
            } elseif ($row['is_tedarikci'] == 1) {
                $cari['cari_tipi'] = 'tedarikci';
            } else {
                $cari['cari_tipi'] = 'musteri'; // Varsayılan
            }
            
            // Flutter için is_musteri ve is_tedarikci değerlerini de gönder
            $cari['is_musteri'] = (int)$row['is_musteri'];
            $cari['is_tedarikci'] = (int)$row['is_tedarikci'];
        } else {
            $cari['cari_tipi'] = 'musteri'; // Varsayılan değer
        }
        
        if ($hasCariKodu && isset($row['cari_kodu'])) {
            $cari['cari_kodu'] = $row['cari_kodu'];
        } else {
            $cari['cari_kodu'] = 'CAR' . str_pad($row['id'], 4, '0', STR_PAD_LEFT); // Otomatik kod
        }
        
        $cariler[] = $cari;
    }
    
    json_success('Cariler getirildi', [
        'cariler' => $cariler,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ]);
}

/**
 * Cari detayını getir
 */
function handleGetCariDetail($firma_id) {
    global $db;
    
    $cari_id = $_GET['id'] ?? null;
    
    if (!$cari_id) {
        json_error('Cari ID gerekli', 400);
    }
    
    // Önce tablo yapısını kontrol et
    $checkColumns = $db->query("SHOW COLUMNS FROM cariler");
    $columns = [];
    while ($row = $checkColumns->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $hasCariTipi = in_array('cari_tipi', $columns);
    $hasIsMusteri = in_array('is_musteri', $columns);
    $hasIsTedarikci = in_array('is_tedarikci', $columns);
    $hasCariKodu = in_array('cari_kodu', $columns);
    
    // Dinamik kolon seçimi
    $selectFields = "id, unvan, yetkili_kisi, telefon, email, adres, vergi_no, vergi_dairesi, bakiye";
    if ($hasCariTipi) $selectFields .= ", cari_tipi";
    if ($hasIsMusteri) $selectFields .= ", is_musteri";
    if ($hasIsTedarikci) $selectFields .= ", is_tedarikci";
    if ($hasCariKodu) $selectFields .= ", cari_kodu";
    $selectFields .= ", olusturma_tarihi";
    
    $stmt = $db->prepare("
        SELECT $selectFields
        FROM cariler 
        WHERE id = ? AND firma_id = ? AND aktif = 1
    ");
    $stmt->bind_param("ii", $cari_id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_error('Cari bulunamadı', 404);
    }
    
    $cari = $result->fetch_assoc();
    
    $response = [
        'id' => $cari['id'],
        'unvan' => $cari['unvan'],
        'yetkili_kisi' => $cari['yetkili_kisi'],
        'telefon' => $cari['telefon'],
        'email' => $cari['email'],
        'adres' => $cari['adres'],
        'vergi_no' => $cari['vergi_no'],
        'vergi_dairesi' => $cari['vergi_dairesi'],
        'bakiye' => (float)$cari['bakiye'],
        'olusturma_tarihi' => $cari['olusturma_tarihi']
    ];
    
    // Dinamik kolonları ekle
    if ($hasCariTipi && isset($cari['cari_tipi'])) {
        $response['cari_tipi'] = $cari['cari_tipi'];
    } elseif ($hasIsMusteri && $hasIsTedarikci) {
        // is_musteri ve is_tedarikci kolonlarından cari_tipi belirle
        if ($cari['is_musteri'] == 1) {
            $response['cari_tipi'] = 'musteri';
        } elseif ($cari['is_tedarikci'] == 1) {
            $response['cari_tipi'] = 'tedarikci';
        } else {
            $response['cari_tipi'] = 'musteri'; // Varsayılan
        }
    } else {
        $response['cari_tipi'] = 'musteri';
    }
    
    if ($hasCariKodu && isset($cari['cari_kodu'])) {
        $response['cari_kodu'] = $cari['cari_kodu'];
    } else {
        $response['cari_kodu'] = 'CAR' . str_pad($cari['id'], 4, '0', STR_PAD_LEFT);
    }
    
    json_success('Cari detayı getirildi', $response);
}

/**
 * Cari ekstresini getir
 */
function handleGetCariEkstre($firma_id) {
    global $db;
    
    $cari_id = $_GET['id'] ?? null;
    $baslangic = $_GET['baslangic'] ?? date('Y-m-01');
    $bitis = $_GET['bitis'] ?? date('Y-m-d');
    
    if (!$cari_id) {
        json_error('Cari ID gerekli', 400);
    }
    
    // Cari bilgilerini al
    $stmt = $db->prepare("
        SELECT unvan, bakiye 
        FROM cariler 
        WHERE id = ? AND firma_id = ? AND aktif = 1
    ");
    $stmt->bind_param("ii", $cari_id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_error('Cari bulunamadı', 404);
    }
    
    $cari = $result->fetch_assoc();
    
    // Ekstre hareketlerini al
    $stmt = $db->prepare("
        SELECT 
            'fatura' as tip,
            fatura_tarihi as tarih,
            CONCAT('Fatura No: ', fatura_no) as aciklama,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'Satış Faturası'
                WHEN fatura_tipi = 'alis' THEN 'Alış Faturası'
            END as tip_display,
            CASE 
                WHEN fatura_tipi = 'satis' THEN toplam_tutar
                WHEN fatura_tipi = 'alis' THEN -toplam_tutar
            END as tutar,
            'fatura' as kategori
        FROM faturalar 
        WHERE cari_id = ? AND fatura_tarihi BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'odeme' as tip,
            tarih,
            COALESCE(aciklama, 'Ödeme') as aciklama,
            'Ödeme' as tip_display,
            tutar,
            'odeme' as kategori
        FROM odemeler 
        WHERE cari_id = ? AND tarih BETWEEN ? AND ?
        
        ORDER BY tarih DESC
    ");
    $stmt->bind_param("isssis", $cari_id, $baslangic, $bitis, $cari_id, $baslangic, $bitis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hareketler = [];
    while ($row = $result->fetch_assoc()) {
        $hareketler[] = [
            'tip' => $row['tip'],
            'tarih' => $row['tarih'],
            'aciklama' => $row['aciklama'],
            'tip_display' => $row['tip_display'],
            'tutar' => (float)$row['tutar'],
            'kategori' => $row['kategori']
        ];
    }
    
    json_success('Cari ekstresi getirildi', [
        'cari' => [
            'unvan' => $cari['unvan'],
            'bakiye' => (float)$cari['bakiye']
        ],
        'hareketler' => $hareketler,
        'tarih_araligi' => [
            'baslangic' => $baslangic,
            'bitis' => $bitis
        ]
    ]);
}

/**
 * Yeni cari oluştur
 */
function handleCreateCari($firma_id, $user_id) {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['unvan'])) {
        json_error('Ünvan gerekli', 400);
    }
    
    $unvan = trim($input['unvan']);
    $yetkili_kisi = $input['yetkili_kisi'] ?? null;
    $telefon = $input['telefon'] ?? null;
    $email = $input['email'] ?? null;
    $adres = $input['adres'] ?? null;
    $vergi_no = $input['vergi_no'] ?? null;
    $vergi_dairesi = $input['vergi_dairesi'] ?? null;
    $cari_tipi = $input['cari_tipi'] ?? 'musteri';
    
    // Flutter'dan gelen is_musteri ve is_tedarikci değerlerini al
    $is_musteri = isset($input['is_musteri']) ? (int)$input['is_musteri'] : 0;
    $is_tedarikci = isset($input['is_tedarikci']) ? (int)$input['is_tedarikci'] : 0;
    
    // Eğer is_musteri/is_tedarikci gönderilmişse, cari_tipi'ni bunlara göre belirle
    if (isset($input['is_musteri']) || isset($input['is_tedarikci'])) {
        if ($is_musteri && $is_tedarikci) {
            $cari_tipi = 'musteri'; // Varsayılan olarak müşteri, ama is_musteri=1 ve is_tedarikci=1 olacak
        } elseif ($is_musteri) {
            $cari_tipi = 'musteri';
        } elseif ($is_tedarikci) {
            $cari_tipi = 'tedarikci';
        }
    }
    
    // Tablo yapısını kontrol et
    $checkColumns = $db->query("SHOW COLUMNS FROM cariler");
    $columns = [];
    while ($row = $checkColumns->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $hasCariTipi = in_array('cari_tipi', $columns);
    $hasIsMusteri = in_array('is_musteri', $columns);
    $hasIsTedarikci = in_array('is_tedarikci', $columns);
    $hasCariKodu = in_array('cari_kodu', $columns);
    
    // Otomatik cari kodu oluştur
    if ($hasCariKodu) {
        $stmt = $db->prepare("
            SELECT MAX(CAST(SUBSTRING(cari_kodu, 5) AS UNSIGNED)) as max_code 
            FROM cariler 
            WHERE firma_id = ? AND cari_kodu LIKE 'CAR%'
        ");
        $stmt->bind_param("i", $firma_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $nextCode = ($result['max_code'] ?? 0) + 1;
        $cari_kodu = 'CAR' . str_pad($nextCode, 4, '0', STR_PAD_LEFT);
    } else {
        $cari_kodu = null;
    }
    
    // Dinamik INSERT sorgusu - temel alanlar
    $insertFields = "firma_id, unvan, yetkili_kisi, telefon, email, adres, vergi_no, vergi_dairesi, bakiye, aktif";
    $insertValues = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
    $bindTypes = "isssssssii";
    $bindValues = [$firma_id, $unvan, $yetkili_kisi, $telefon, $email, $adres, $vergi_no, $vergi_dairesi, 0, 1];
    
    // Cari kodu ekle
    if ($hasCariKodu) {
        $insertFields = "cari_kodu, " . $insertFields;
        $insertValues = "?, " . $insertValues;
        $bindTypes = "s" . $bindTypes;
        array_unshift($bindValues, $cari_kodu);
    }
    
    // Cari tipi ekle
    if ($hasCariTipi) {
        $insertFields .= ", cari_tipi";
        $insertValues .= ", ?";
        $bindTypes .= "s";
        $bindValues[] = $cari_tipi;
    } elseif ($hasIsMusteri && $hasIsTedarikci) {
        // is_musteri ve is_tedarikci kolonları kullan
        $insertFields .= ", is_musteri, is_tedarikci";
        $insertValues .= ", ?, ?";
        $bindTypes .= "ii";
        $bindValues[] = $is_musteri; // Flutter'dan gelen değer
        $bindValues[] = $is_tedarikci; // Flutter'dan gelen değer
    }
    
    $stmt = $db->prepare("INSERT INTO cariler ($insertFields) VALUES ($insertValues)");
    $stmt->bind_param($bindTypes, ...$bindValues);
    
    if ($stmt->execute()) {
        $cari_id = $db->insert_id;
        
        write_log("Flutter - Yeni cari oluşturuldu: $unvan (ID: $cari_id)", 'cari');
        
        json_success('Cari başarıyla oluşturuldu', [
            'id' => $cari_id,
            'cari_kodu' => $cari_kodu
        ]);
    } else {
        json_error('Cari oluşturulurken hata oluştu: ' . $stmt->error, 500);
    }
}

/**
 * Cari güncelle
 */
function handleUpdateCari($firma_id, $user_id) {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        json_error('Cari ID gerekli', 400);
    }
    
    $cari_id = $input['id'];
    $unvan = trim($input['unvan'] ?? '');
    $yetkili_kisi = $input['yetkili_kisi'] ?? null;
    $telefon = $input['telefon'] ?? null;
    $email = $input['email'] ?? null;
    $adres = $input['adres'] ?? null;
    $vergi_no = $input['vergi_no'] ?? null;
    $vergi_dairesi = $input['vergi_dairesi'] ?? null;
    $cari_tipi = $input['cari_tipi'] ?? 'musteri';
    
    // Tablo yapısını kontrol et
    $checkColumns = $db->query("SHOW COLUMNS FROM cariler");
    $columns = [];
    while ($row = $checkColumns->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $hasCariTipi = in_array('cari_tipi', $columns);
    $hasIsMusteri = in_array('is_musteri', $columns);
    $hasIsTedarikci = in_array('is_tedarikci', $columns);
    
    // Dinamik UPDATE sorgusu
    $updateFields = "unvan = ?, yetkili_kisi = ?, telefon = ?, email = ?, adres = ?, vergi_no = ?, vergi_dairesi = ?";
    $bindTypes = "sssssss";
    $bindValues = [$unvan, $yetkili_kisi, $telefon, $email, $adres, $vergi_no, $vergi_dairesi];
    
    if ($hasCariTipi) {
        $updateFields .= ", cari_tipi = ?";
        $bindTypes .= "s";
        $bindValues[] = $cari_tipi;
    } elseif ($hasIsMusteri && $hasIsTedarikci) {
        // is_musteri ve is_tedarikci kolonları kullan
        $updateFields .= ", is_musteri = ?, is_tedarikci = ?";
        $bindTypes .= "ii";
        
        // Flutter'dan gelen is_musteri ve is_tedarikci değerlerini kullan
        $is_musteri = $input['is_musteri'] ?? 0;
        $is_tedarikci = $input['is_tedarikci'] ?? 0;
        
        // Debug log
        error_log("Update Cari Debug - ID: $cari_id, is_musteri: $is_musteri, is_tedarikci: $is_tedarikci");
        
        $bindValues[] = $is_musteri;
        $bindValues[] = $is_tedarikci;
    }
    
    $bindTypes .= "ii";
    $bindValues[] = $cari_id;
    $bindValues[] = $firma_id;
    
    $stmt = $db->prepare("UPDATE cariler SET $updateFields WHERE id = ? AND firma_id = ? AND aktif = 1");
    $stmt->bind_param($bindTypes, ...$bindValues);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            write_log("Flutter - Cari güncellendi: $unvan (ID: $cari_id)", 'cari');
            json_success('Cari başarıyla güncellendi');
        } else {
            json_error('Cari bulunamadı', 404);
        }
    } else {
        json_error('Cari güncellenirken hata oluştu: ' . $stmt->error, 500);
    }
}

/**
 * Cari sil
 */
function handleDeleteCari($firma_id) {
    global $db;
    
    // POST body'den ID'yi al
    $input = json_decode(file_get_contents('php://input'), true);
    $cari_id = $input['id'] ?? null;
    
    if (!$cari_id) {
        json_error('Cari ID gerekli', 400);
    }
    
    // Cari'nin faturaları var mı kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM faturalar WHERE cari_id = ?");
    $stmt->bind_param("i", $cari_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        json_error('Bu cariye ait faturalar bulunduğu için silinemez!', 400);
    }
    
    // Soft delete - aktif = 0 yap
    $stmt = $db->prepare("UPDATE cariler SET aktif = 0 WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $cari_id, $firma_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            write_log("Flutter - Cari silindi (ID: $cari_id)", 'cari');
            json_success('Cari başarıyla silindi');
        } else {
            json_error('Cari bulunamadı', 404);
        }
    } else {
        json_error('Cari silinirken hata oluştu: ' . $stmt->error, 500);
    }
}
?>
