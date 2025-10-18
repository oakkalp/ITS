<?php
ob_start();
require_once "../../config.php";
require_once "../../includes/auth.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION["user_id"])) {
    $firma_id = get_firma_id();
    $user_id = $_SESSION["user_id"];
} else {
    require_once "../../includes/jwt.php";
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers["Authorization"])) {
        $auth_header = $headers["Authorization"];
        if (preg_match("/Bearer\s+(.*)$/i", $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
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
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        json_error("Sadece POST metodu kabul edilir", 405);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Debug log
    error_log("Fatura oluşturma - Input: " . print_r($input, true));
    
    $required_fields = ["fatura_no", "fatura_tarihi", "fatura_tipi", "cari_id", "toplam_tutar"];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            error_log("Eksik alan: $field");
            json_error("$field alanı gerekli", 400);
        }
    }
    
    $db->begin_transaction();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO faturalar (
                firma_id, fatura_no, fatura_tarihi, fatura_tipi, cari_id, 
                toplam_tutar, vade_tarihi, aciklama, odeme_durumu, kullanici_id, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'odenmedi', ?, NOW())
        ");
        
        $vade_tarihi = $input["vade_tarihi"] ?? null;
        $aciklama = $input["aciklama"] ?? "";
        
        $stmt->bind_param("isssisssi", 
            $firma_id,
            $input["fatura_no"],
            $input["fatura_tarihi"],
            $input["fatura_tipi"],
            $input["cari_id"],
            $input["toplam_tutar"],
            $vade_tarihi,
            $aciklama,
            $user_id
        );
        
        $stmt->execute();
        $fatura_id = $db->insert_id;
        
        error_log("Fatura oluşturuldu - ID: $fatura_id");
        
        $detaylar = $input["detaylar"] ?? $input["kalemler"] ?? [];
        if (is_array($detaylar)) {
            $detay_stmt = $db->prepare("
                INSERT INTO fatura_detaylari (
                    fatura_id, urun_id, miktar, birim_fiyat, toplam
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($detaylar as $detay) {
                $toplam = $detay["miktar"] * $detay["birim_fiyat"];
                $detay_stmt->bind_param("iiddd",
                    $fatura_id,
                    $detay["urun_id"],
                    $detay["miktar"],
                    $detay["birim_fiyat"],
                    $toplam
                );
                $detay_stmt->execute();
                
                if ($input["fatura_tipi"] === "alis") {
                    $stok_stmt = $db->prepare("UPDATE urunler SET stok_miktari = stok_miktari + ? WHERE id = ? AND firma_id = ?");
                    $stok_stmt->bind_param("dii", $detay["miktar"], $detay["urun_id"], $firma_id);
                    $stok_stmt->execute();
                } else {
                    $stok_stmt = $db->prepare("UPDATE urunler SET stok_miktari = stok_miktari - ? WHERE id = ? AND firma_id = ?");
                    $stok_stmt->bind_param("dii", $detay["miktar"], $detay["urun_id"], $firma_id);
                    $stok_stmt->execute();
                }
            }
        }
        
        // Cari bakiyesini güncelle
        $bakiye_degisim = 0;
        if ($input["fatura_tipi"] === "alis") {
            // Alış faturası = bizden borç (negatif bakiye)
            $bakiye_degisim = -$input["toplam_tutar"];
        } else {
            // Satış faturası = bizden alacak (pozitif bakiye)
            $bakiye_degisim = $input["toplam_tutar"];
        }
        
        $bakiye_stmt = $db->prepare("UPDATE cariler SET bakiye = bakiye + ? WHERE id = ? AND firma_id = ?");
        $bakiye_stmt->bind_param("dii", $bakiye_degisim, $input["cari_id"], $firma_id);
        $bakiye_stmt->execute();
        
        error_log("Cari bakiyesi güncellendi - Cari ID: {$input['cari_id']}, Değişim: $bakiye_degisim");
        
        $db->commit();
        
        json_success("Fatura başarıyla oluşturuldu", [
            "fatura_id" => $fatura_id,
            "fatura_no" => $input["fatura_no"]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Transaction hatası: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Fatura oluşturma hatası: " . $e->getMessage());
    json_error("Fatura oluşturulurken hata oluştu: " . $e->getMessage(), 500);
}
?>
