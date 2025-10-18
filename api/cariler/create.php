<?php
ob_start();
require_once "../../config.php";
require_once "../../includes/auth.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Debug için basit test
if (isset($_GET['test'])) {
    error_log("Cari create - Test endpoint called");
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint çalışıyor',
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'headers' => getallheaders(),
        'auth_header' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'not set'
    ]);
    exit;
}

if (isset($_SESSION["user_id"])) {
    $firma_id = get_firma_id();
    $user_id = $_SESSION["user_id"];
    error_log("Cari create - Web paneli session kullanılıyor, Firma ID: $firma_id");
} else {
    require_once "../../includes/jwt.php";
    
    // IIS için Authorization header kontrolü
    $auth_header = null;
    
    // IIS'te Authorization header'ı farklı şekilde gelebilir
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['Authorization'])) {
        $auth_header = $_SERVER['Authorization'];
    } else {
        // getallheaders() fonksiyonu IIS'te çalışmayabilir
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if ($headers && isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        }
    }
    
    error_log("Cari create - Auth header: " . ($auth_header ?: 'not found'));
    error_log("Cari create - SERVER vars: " . print_r(array_filter($_SERVER, function($key) {
        return strpos($key, 'AUTH') !== false || strpos($key, 'HTTP') !== false;
    }, ARRAY_FILTER_USE_KEY), true));
    
    $token = null;
    if ($auth_header && preg_match("/Bearer\s+(.*)$/i", $auth_header, $matches)) {
        $token = $matches[1];
        error_log("Cari create - Token extracted: " . substr($token, 0, 20) . "...");
    }
    
    if (!$token) {
        error_log("Cari create - Token bulunamadı");
        json_error("Authorization header gerekli", 401);
    }
    
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        $firma_id = $decoded->firma_id;
        $user_id = $decoded->user_id;
        
        $_SESSION["user_id"] = $user_id;
        $_SESSION["firma_id"] = $firma_id;
        $_SESSION["ad_soyad"] = $decoded->ad_soyad;
        $_SESSION["firma_adi"] = $decoded->firma_adi;
        
    } catch (Exception $e) {
        json_error("Geçersiz token", 401);
    }
}

ob_clean();

try {
    error_log("Cari create - Request method: " . $_SERVER["REQUEST_METHOD"]);
    error_log("Cari create - Content type: " . ($_SERVER["CONTENT_TYPE"] ?? "not set"));
    
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        json_error("Sadece POST metodu kabul edilir", 405);
    }
    
    // FormData ve JSON input desteği
    $input = null;
    $content_type = $_SERVER["CONTENT_TYPE"] ?? '';
    error_log("Cari create - Content-Type: " . $content_type);
    
    if (strpos($content_type, "application/json") !== false) {
        $raw_input = file_get_contents("php://input");
        error_log("Cari create - Raw input: " . $raw_input);
        error_log("Cari create - Raw input length: " . strlen($raw_input));
        
        if (empty($raw_input)) {
            error_log("Cari create - Raw input is empty");
            json_error("Boş JSON verisi", 400);
        }
        
        $input = json_decode($raw_input, true);
        $json_error = json_last_error();
        
        if ($json_error !== JSON_ERROR_NONE) {
            error_log("Cari create - JSON decode error: " . json_last_error_msg());
            error_log("Cari create - JSON error code: " . $json_error);
            json_error("Geçersiz JSON formatı: " . json_last_error_msg(), 400);
        }
        
        error_log("Cari create - Decoded input: " . print_r($input, true));
        error_log("Cari create - Input type: " . gettype($input));
        error_log("Cari create - Input is array: " . (is_array($input) ? 'yes' : 'no'));
    } else {
        error_log("Cari create - Using POST data");
        $input = $_POST;
        error_log("Cari create - POST data: " . print_r($input, true));
    }
    
    if (!$input || (is_array($input) && empty($input))) {
        error_log("Cari create - Input is null or empty");
        error_log("Cari create - Input value: " . print_r($input, true));
        json_error("Geçersiz input", 400);
    }
    
    // Debug log
    error_log("Cari oluşturma - Input: " . print_r($input, true));
    
    // Gerekli alanları kontrol et
    $required_fields = ["unvan"];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            error_log("Eksik alan: $field");
            json_error("$field alanı gerekli", 400);
        }
    }
    
    // Otomatik cari kodu oluştur
    $cari_kodu = $input["cari_kodu"] ?? "";
    if (empty($cari_kodu)) {
        // En yüksek cari kodunu bul ve bir artır
        $max_result = $db->query("SELECT MAX(CAST(SUBSTRING(cari_kodu, 1, 10) AS UNSIGNED)) as max_code FROM cariler WHERE cari_kodu REGEXP '^[0-9]+$' AND firma_id = $firma_id");
        $max_row = $max_result->fetch_assoc();
        $next_code = ($max_row['max_code'] ?? 0) + 1;
        $cari_kodu = str_pad($next_code, 6, '0', STR_PAD_LEFT); // 6 haneli kod
    }
    
    $stmt = $db->prepare("
        INSERT INTO cariler (
            firma_id, cari_kodu, unvan, telefon, email, adres, 
            vergi_dairesi, vergi_no, yetkili_kisi,
            is_musteri, is_tedarikci, aktif, olusturma_tarihi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $telefon = $input["telefon"] ?? "";
    $email = $input["email"] ?? "";
    $adres = $input["adres"] ?? "";
    $vergi_dairesi = $input["vergi_dairesi"] ?? "";
    $vergi_no = $input["vergi_no"] ?? "";
    $yetkili_kisi = $input["yetkili_kisi"] ?? "";
    
    // Cari tipini işle
    $cari_tipi = $input["cari_tipi"] ?? "";
    $is_musteri = 0;
    $is_tedarikci = 0;
    
    if ($cari_tipi === "musteri") {
        $is_musteri = 1;
    } elseif ($cari_tipi === "tedarikci") {
        $is_tedarikci = 1;
    } else {
        // Eski sistem uyumluluğu
        $is_musteri = $input["is_musteri"] ?? 0;
        $is_tedarikci = $input["is_tedarikci"] ?? 0;
    }
    
    error_log("Cari tipi: '$cari_tipi', Müşteri: $is_musteri, Tedarikçi: $is_tedarikci");
    
    $stmt->bind_param("issssssssii", 
        $firma_id,
        $cari_kodu,
        $input["unvan"],
        $telefon,
        $email,
        $adres,
        $vergi_dairesi,
        $vergi_no,
        $yetkili_kisi,
        $is_musteri,
        $is_tedarikci
    );
    
    $stmt->execute();
    $cari_id = $db->insert_id;
    
    error_log("Cari oluşturuldu - ID: $cari_id");
    
    json_success("Cari başarıyla oluşturuldu", [
        "id" => $cari_id,
        "unvan" => $input["unvan"]
    ]);
    
} catch (Exception $e) {
    error_log("Cari oluşturma hatası: " . $e->getMessage());
    json_error("Cari oluşturulurken hata oluştu: " . $e->getMessage(), 500);
}
?>