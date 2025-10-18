<?php
/**
 * Cariler API
 */

require_once '../flutter/flutter_api.php';

class CarilerAPI extends FlutterAPI {
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        file_put_contents('debug_cariler.txt', "CarilerAPI: Method=$method, Action=$action\n", FILE_APPEND);
        
        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'list':
                        return $this->getCariler();
                    default:
                        $this->sendError('Geçersiz işlem', 400);
                        return false;
                }
                break;
            case 'POST':
                switch ($action) {
                    case 'add':
                        return $this->addCari();
                    default:
                        $this->sendError('Geçersiz işlem', 400);
                        return false;
                }
                break;
            case 'PUT':
                switch ($action) {
                    case 'update':
                        return $this->updateCari();
                    default:
                        $this->sendError('Geçersiz işlem', 400);
                        return false;
                }
                break;
            case 'DELETE':
                switch ($action) {
                    case 'delete':
                        return $this->deleteCari();
                    default:
                        $this->sendError('Geçersiz işlem', 400);
                        return false;
                }
                break;
            default:
                $this->sendError('Desteklenmeyen HTTP metodu', 405);
                return false;
        }
    }
    
    private function getCariler() {
        // Önce query parameter'dan token'ı dene
        $token = $_GET['token'] ?? '';
        file_put_contents('debug_cariler.txt', "CarilerAPI getCariler - Token from query: " . substr($token, 0, 50) . "...\n", FILE_APPEND);
        
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                file_put_contents('debug_cariler.txt', "CarilerAPI: JWT validation failed for query token\n", FILE_APPEND);
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
            file_put_contents('debug_cariler.txt', "CarilerAPI: JWT validation success for query token\n", FILE_APPEND);
        } else {
            // Header'dan token'ı dene
            file_put_contents('debug_cariler.txt', "CarilerAPI: Trying header authentication\n", FILE_APPEND);
            $user = $this->validateAuth();
            if (!$user) {
                file_put_contents('debug_cariler.txt', "CarilerAPI: Auth failed\n", FILE_APPEND);
                return false;
            }
            file_put_contents('debug_cariler.txt', "CarilerAPI: Header authentication success\n", FILE_APPEND);
        }
        
        try {
            $user_role = $user['rol'];
            $firma_id = $user['firma_id'];
            
            if ($user_role === 'super_admin') {
                // Super admin tüm carileri görebilir
                $query = "SELECT id, unvan, yetkili_kisi, telefon, email, adres, vergi_no, vergi_dairesi 
                         FROM cariler 
                         WHERE aktif = 1 
                         ORDER BY unvan ASC";
                $stmt = $this->db->prepare($query);
            } else {
                // Diğer kullanıcılar sadece kendi firmalarının carilerini görebilir
                $query = "SELECT id, unvan, yetkili_kisi, telefon, email, adres, vergi_no, vergi_dairesi 
                         FROM cariler 
                         WHERE firma_id = ? AND aktif = 1 
                         ORDER BY unvan ASC";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cariler = [];
            while ($row = $result->fetch_assoc()) {
                $cariler[] = [
                    'id' => $row['id'],
                    'unvan' => $row['unvan'],
                    'yetkili_kisi' => $row['yetkili_kisi'],
                    'telefon' => $row['telefon'],
                    'email' => $row['email'],
                    'adres' => $row['adres'],
                    'vergi_no' => $row['vergi_no'],
                    'vergi_dairesi' => $row['vergi_dairesi']
                ];
            }
            
            $this->sendSuccess(['cariler' => $cariler], 'Cariler listelendi');
            return true;
            
        } catch (Exception $e) {
            error_log("CarilerAPI Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function addCari() {
        // Token doğrulama
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("CarilerAPI: JWT validation failed for add");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            $user = $this->validateAuth();
            if (!$user) {
                error_log("CarilerAPI: Auth failed for add");
                return false;
            }
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendError('Geçersiz JSON verisi', 400);
                return false;
            }
            
            $firma_id = $user['firma_id'];
            $unvan = $input['unvan'] ?? '';
            $yetkili_kisi = $input['yetkili_kisi'] ?? '';
            $telefon = $input['telefon'] ?? '';
            $email = $input['email'] ?? '';
            $adres = $input['adres'] ?? '';
            $vergi_no = $input['vergi_no'] ?? '';
            $vergi_dairesi = $input['vergi_dairesi'] ?? '';
            
            if (empty($unvan)) {
                $this->sendError('Unvan alanı zorunludur', 400);
                return false;
            }
            
            $query = "INSERT INTO cariler (firma_id, unvan, yetkili_kisi, telefon, email, adres, vergi_no, vergi_dairesi, aktif, olusturma_tarihi) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('isssssss', $firma_id, $unvan, $yetkili_kisi, $telefon, $email, $adres, $vergi_no, $vergi_dairesi);
            
            if ($stmt->execute()) {
                $cari_id = $this->db->insert_id;
                $this->sendSuccess(['cari_id' => $cari_id], 'Cari başarıyla eklendi');
                return true;
            } else {
                $this->sendError('Cari eklenirken hata oluştu', 500);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CarilerAPI Add Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function updateCari() {
        // Token doğrulama
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("CarilerAPI: JWT validation failed for update");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            $user = $this->validateAuth();
            if (!$user) {
                error_log("CarilerAPI: Auth failed for update");
                return false;
            }
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendError('Geçersiz JSON verisi', 400);
                return false;
            }
            
            $cari_id = $input['id'] ?? 0;
            if (!$cari_id) {
                $this->sendError('Cari ID gerekli', 400);
                return false;
            }
            
            // Cari'nin kullanıcının firmasına ait olduğunu kontrol et
            $check_query = "SELECT firma_id FROM cariler WHERE id = ? AND aktif = 1";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bind_param('i', $cari_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $this->sendError('Cari bulunamadı', 404);
                return false;
            }
            
            $cari = $check_result->fetch_assoc();
            if ($user['rol'] !== 'super_admin' && $cari['firma_id'] != $user['firma_id']) {
                $this->sendError('Bu cariye erişim yetkiniz yok', 403);
                return false;
            }
            
            $unvan = $input['unvan'] ?? '';
            $yetkili_kisi = $input['yetkili_kisi'] ?? '';
            $telefon = $input['telefon'] ?? '';
            $email = $input['email'] ?? '';
            $adres = $input['adres'] ?? '';
            $vergi_no = $input['vergi_no'] ?? '';
            $vergi_dairesi = $input['vergi_dairesi'] ?? '';
            
            if (empty($unvan)) {
                $this->sendError('Unvan alanı zorunludur', 400);
                return false;
            }
            
            $query = "UPDATE cariler SET unvan = ?, yetkili_kisi = ?, telefon = ?, email = ?, adres = ?, vergi_no = ?, vergi_dairesi = ?, guncelleme_tarihi = NOW() 
                     WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('sssssssi', $unvan, $yetkili_kisi, $telefon, $email, $adres, $vergi_no, $vergi_dairesi, $cari_id);
            
            if ($stmt->execute()) {
                $this->sendSuccess(['cari_id' => $cari_id], 'Cari başarıyla güncellendi');
                return true;
            } else {
                $this->sendError('Cari güncellenirken hata oluştu', 500);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CarilerAPI Update Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function deleteCari() {
        // Token doğrulama
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("CarilerAPI: JWT validation failed for delete");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            $user = $this->validateAuth();
            if (!$user) {
                error_log("CarilerAPI: Auth failed for delete");
                return false;
            }
        }
        
        try {
            $cari_id = $_GET['id'] ?? 0;
            if (!$cari_id) {
                $this->sendError('Cari ID gerekli', 400);
                return false;
            }
            
            // Cari'nin kullanıcının firmasına ait olduğunu kontrol et
            $check_query = "SELECT firma_id FROM cariler WHERE id = ? AND aktif = 1";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bind_param('i', $cari_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $this->sendError('Cari bulunamadı', 404);
                return false;
            }
            
            $cari = $check_result->fetch_assoc();
            if ($user['rol'] !== 'super_admin' && $cari['firma_id'] != $user['firma_id']) {
                $this->sendError('Bu cariye erişim yetkiniz yok', 403);
                return false;
            }
            
            // Soft delete - aktif = 0 yap
            $query = "UPDATE cariler SET aktif = 0, guncelleme_tarihi = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $cari_id);
            
            if ($stmt->execute()) {
                $this->sendSuccess(['cari_id' => $cari_id], 'Cari başarıyla silindi');
                return true;
            } else {
                $this->sendError('Cari silinirken hata oluştu', 500);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("CarilerAPI Delete Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
}

// API'yi çalıştır
$api = new CarilerAPI();
$api->handleRequest();
?>
