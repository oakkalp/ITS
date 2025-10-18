<?php
require_once 'config.php';

/**
 * CSRF Koruması Sınıfı
 */
class CSRFProtection {
    private static $token_name = 'csrf_token';
    private static $session_key = 'csrf_tokens';
    
    /**
     * CSRF token oluştur
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        
        if (!isset($_SESSION[self::$session_key])) {
            $_SESSION[self::$session_key] = [];
        }
        
        // Eski token'ları temizle (1 saatten eski)
        self::cleanOldTokens();
        
        // Yeni token'ı kaydet
        $_SESSION[self::$session_key][$token] = time();
        
        return $token;
    }
    
    /**
     * CSRF token'ı HTML form'a ekle
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$token_name . '" value="' . $token . '">';
    }
    
    /**
     * CSRF token'ı kontrol et
     */
    public static function validateToken($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($token === null) {
            $token = $_POST[self::$token_name] ?? $_GET[self::$token_name] ?? null;
        }
        
        if (!$token) {
            return false;
        }
        
        if (!isset($_SESSION[self::$session_key][$token])) {
            return false;
        }
        
        // Token'ın süresi dolmuş mu kontrol et (1 saat)
        if (time() - $_SESSION[self::$session_key][$token] > 3600) {
            unset($_SESSION[self::$session_key][$token]);
            return false;
        }
        
        // Token kullanıldıktan sonra sil
        unset($_SESSION[self::$session_key][$token]);
        
        return true;
    }
    
    /**
     * Eski token'ları temizle
     */
    private static function cleanOldTokens() {
        if (!isset($_SESSION[self::$session_key])) {
            return;
        }
        
        $current_time = time();
        foreach ($_SESSION[self::$session_key] as $token => $timestamp) {
            if ($current_time - $timestamp > 3600) { // 1 saat
                unset($_SESSION[self::$session_key][$token]);
            }
        }
    }
    
    /**
     * AJAX için token header'ı döndür
     */
    public static function getTokenHeader() {
        $token = self::generateToken();
        return ['X-CSRF-Token' => $token];
    }
    
    /**
     * AJAX token'ı kontrol et
     */
    public static function validateAjaxToken() {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? null;
        
        return self::validateToken($token);
    }
}

/**
 * Input Validation Sınıfı
 */
class InputValidator {
    
    /**
     * Email validasyonu
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Telefon validasyonu
     */
    public static function validatePhone($phone) {
        // Türkiye telefon numarası formatı
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^(05[0-9]{9}|[0-9]{10,11})$/', $phone);
    }
    
    /**
     * TC Kimlik No validasyonu
     */
    public static function validateTC($tc) {
        $tc = preg_replace('/[^0-9]/', '', $tc);
        
        if (strlen($tc) !== 11) {
            return false;
        }
        
        // TC Kimlik algoritması
        $digits = str_split($tc);
        $sum1 = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $sum2 = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        
        $check1 = ($sum1 * 7 - $sum2) % 10;
        $check2 = ($sum1 + $sum2 + $digits[9]) % 10;
        
        return $check1 == $digits[9] && $check2 == $digits[10];
    }
    
    /**
     * Vergi No validasyonu
     */
    public static function validateTaxNumber($tax_no) {
        $tax_no = preg_replace('/[^0-9]/', '', $tax_no);
        
        if (strlen($tax_no) !== 10) {
            return false;
        }
        
        // Vergi No algoritması
        $digits = str_split($tax_no);
        $sum = 0;
        
        for ($i = 0; $i < 9; $i++) {
            $sum += $digits[$i] * (10 - $i);
        }
        
        $remainder = $sum % 11;
        $check_digit = $remainder < 2 ? $remainder : 11 - $remainder;
        
        return $check_digit == $digits[9];
    }
    
    /**
     * SQL Injection koruması
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * XSS koruması
     */
    public static function preventXSS($input) {
        if (is_array($input)) {
            return array_map([self::class, 'preventXSS'], $input);
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Dosya yükleme güvenliği
     */
    public static function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        
        if (!in_array($extension, $allowed_types)) {
            return false;
        }
        
        // Dosya boyutu kontrolü (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // MIME type kontrolü
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        
        if (!isset($allowed_mimes[$extension]) || $mime_type !== $allowed_mimes[$extension]) {
            return false;
        }
        
        return true;
    }
}

// Güvenlik test
echo "=== Güvenlik Sistemi Test ===\n";

// CSRF Test
$token = CSRFProtection::generateToken();
echo "✅ CSRF Token oluşturuldu: " . substr($token, 0, 20) . "...\n";

if (CSRFProtection::validateToken($token)) {
    echo "✅ CSRF Token doğrulandı\n";
} else {
    echo "❌ CSRF Token doğrulanamadı\n";
}

// Input Validation Test
echo "\n📧 Email Test:\n";
echo "test@example.com: " . (InputValidator::validateEmail('test@example.com') ? '✅' : '❌') . "\n";
echo "invalid-email: " . (InputValidator::validateEmail('invalid-email') ? '✅' : '❌') . "\n";

echo "\n📱 Telefon Test:\n";
echo "05551234567: " . (InputValidator::validatePhone('05551234567') ? '✅' : '❌') . "\n";
echo "1234567890: " . (InputValidator::validatePhone('1234567890') ? '✅' : '❌') . "\n";

echo "\n🆔 TC Kimlik Test:\n";
echo "12345678901: " . (InputValidator::validateTC('12345678901') ? '✅' : '❌') . "\n";

echo "\n🏢 Vergi No Test:\n";
echo "1234567890: " . (InputValidator::validateTaxNumber('1234567890') ? '✅' : '❌') . "\n";

echo "\n🎉 Güvenlik sistemi hazır!\n";
?>
