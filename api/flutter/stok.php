<?php
/**
 * Stok API
 */

require_once 'flutter_api.php';

class StokAPI extends FlutterAPI {
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        error_log("StokAPI: Method=$method, Action=$action");
        
        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'list':
                        return $this->getStok();
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
    
    private function getStok() {
        // Önce query parameter'dan token'ı dene
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("StokAPI: JWT validation failed for query token");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            // Header'dan token'ı dene
            $user = $this->validateAuth();
            if (!$user) {
                error_log("StokAPI: Auth failed");
                return false;
            }
        }
        
        try {
            $user_role = $user['rol'];
            $firma_id = $user['firma_id'];
            
            if ($user_role === 'super_admin') {
                // Super admin tüm stokları görebilir
                $query = "SELECT id, urun_adi, stok_miktari, satis_fiyati as birim_fiyat, kategori, urun_kodu as aciklama 
                         FROM urunler 
                         WHERE aktif = 1 
                         ORDER BY urun_adi ASC";
                $stmt = $this->db->prepare($query);
            } else {
                // Diğer kullanıcılar sadece kendi firmalarının stoklarını görebilir
                $query = "SELECT id, urun_adi, stok_miktari, satis_fiyati as birim_fiyat, kategori, urun_kodu as aciklama 
                         FROM urunler 
                         WHERE firma_id = ? AND aktif = 1 
                         ORDER BY urun_adi ASC";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stoklar = [];
            while ($row = $result->fetch_assoc()) {
                $stoklar[] = [
                    'id' => $row['id'],
                    'urun_adi' => $row['urun_adi'],
                    'stok_miktari' => $row['stok_miktari'],
                    'birim_fiyat' => $row['birim_fiyat'],
                    'kategori' => $row['kategori'],
                    'aciklama' => $row['aciklama']
                ];
            }
            
            $this->sendSuccess(['stoklar' => $stoklar], 'Stok listelendi');
            return true;
            
        } catch (Exception $e) {
            error_log("StokAPI Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
}

// API'yi çalıştır
$api = new StokAPI();
$api->handleRequest();
?>
