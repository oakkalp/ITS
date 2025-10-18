<?php
// Error reporting'i aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Config'i yükle
require_once '../../config.php';

// JWT kütüphanesini yükle
require_once '../../includes/jwt.php';

/**
 * Flutter API Base Sınıfı
 * Tüm Flutter API'leri için ortak fonksiyonlar
 */
class FlutterAPI {
    protected $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * JWT token doğrulama
     */
    protected function authenticateUser() {
        $headers = getallheaders();
        
        // Authorization header'ı kontrol et
        $auth_header = null;
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $auth_header = $headers['authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if (!$auth_header) {
            $this->sendError('Authorization header gerekli', 401);
        }
        
        // Bearer token'ı çıkar
        if (strpos($auth_header, 'Bearer ') !== 0) {
            $this->sendError('Geçersiz authorization formatı', 401);
        }
        
        $token = substr($auth_header, 7);
        
        try {
            $payload = JWT::decode($token, JWT_SECRET_KEY, ['HS256']);
            
            // Token'ın geçerliliğini kontrol et
            if (!$payload || !isset($payload['user_id'])) {
                $this->sendError('Geçersiz token', 401);
            }
            
            // Kullanıcı bilgilerini al
            $stmt = $this->db->prepare("SELECT * FROM kullanicilar WHERE id = ? AND aktif = 1");
            $stmt->bind_param("i", $payload['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->sendError('Kullanıcı bulunamadı', 401);
            }
            
            $user = $result->fetch_assoc();
            
            return [
                'user_id' => $user['id'],
                'username' => $user['kullanici_adi'],
                'firma_id' => $user['firma_id'],
                'rol' => $user['rol']
            ];
            
        } catch (Exception $e) {
            $this->sendError('Token doğrulama hatası: ' . $e->getMessage(), 401);
        }
    }
    
    /**
     * Input validation
     */
    protected function validateInput($input, $rules) {
        if (!is_array($input)) {
            $this->sendError('Geçersiz input formatı', 400);
        }
        
        foreach ($rules as $field => $rule) {
            $rules_array = explode('|', $rule);
            
            foreach ($rules_array as $single_rule) {
                if ($single_rule === 'required') {
                    if (!isset($input[$field]) || empty($input[$field])) {
                        $this->sendError("$field alanı gerekli", 400);
                    }
                } elseif (strpos($single_rule, 'min:') === 0) {
                    $min_length = (int)substr($single_rule, 4);
                    if (isset($input[$field]) && strlen($input[$field]) < $min_length) {
                        $this->sendError("$field alanı en az $min_length karakter olmalı", 400);
                    }
                } elseif ($single_rule === 'integer') {
                    if (isset($input[$field]) && !is_numeric($input[$field])) {
                        $this->sendError("$field alanı sayısal olmalı", 400);
                    }
                } elseif ($single_rule === 'email') {
                    if (isset($input[$field]) && !empty($input[$field]) && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                        $this->sendError("$field alanı geçerli bir email adresi olmalı", 400);
                    }
                }
            }
        }
    }
    
    /**
     * POST body'den JSON input al
     */
    protected function getInput() {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }
        
        $decoded = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Geçersiz JSON formatı: ' . json_last_error_msg(), 400);
        }
        
        return $decoded ?: [];
    }
    
    /**
     * SQL query çalıştırma
     */
    protected function executeQuery($query, $params = []) {
        $stmt = $this->db->prepare($query);
        
        if (!$stmt) {
            $this->sendError('SQL hazırlama hatası: ' . $this->db->error, 500);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $this->sendError('SQL çalıştırma hatası: ' . $stmt->error, 500);
        }
        
        return $stmt;
    }
    
    /**
     * Başarılı response gönderme
     */
    protected function sendSuccess($data = null, $message = 'İşlem başarılı') {
        // Output buffer'ı temizle
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        http_response_code(200);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Hata response gönderme
     */
    protected function sendError($message, $code = 400) {
        // Output buffer'ı temizle
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Pagination hesaplama
     */
    protected function calculatePagination($page, $limit, $total) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit));
        $offset = ($page - 1) * $limit;
        $total_pages = ceil($total / $limit);
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ];
    }
    
    /**
     * Date format dönüştürme
     */
    protected function formatDate($date, $format = 'Y-m-d H:i:s') {
        if (empty($date)) {
            return null;
        }
        
        if (is_string($date)) {
            return date($format, strtotime($date));
        }
        
        return date($format, $date);
    }
    
    /**
     * Currency format
     */
    protected function formatCurrency($amount) {
        return number_format($amount, 2, ',', '.');
    }
    
    /**
     * File upload validation
     */
    protected function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->sendError('Dosya yükleme hatası', 400);
        }
        
        $file_size = $file['size'];
        if ($file_size > $max_size) {
            $this->sendError('Dosya boyutu çok büyük (Max: ' . ($max_size / 1024 / 1024) . 'MB)', 400);
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            $this->sendError('Geçersiz dosya türü. İzin verilen türler: ' . implode(', ', $allowed_types), 400);
        }
        
        return true;
    }
    
    /**
     * JWT Token oluştur
     */
    protected function generateJWT($user_id, $firma_id, $role) {
        $payload = [
            'user_id' => $user_id,
            'firma_id' => $firma_id,
            'rol' => $role,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 saat
        ];
        
        return JWT::encode($payload, JWT_SECRET_KEY);
    }
}
?>