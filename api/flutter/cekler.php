<?php
/**
 * Çekler API
 */

require_once 'flutter_api.php';

class CeklerAPI extends FlutterAPI {
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        error_log("CeklerAPI: Method=$method, Action=$action");
        
        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'list':
                        return $this->getCekler();
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
    
    private function getCekler() {
        // Önce query parameter'dan token'ı dene
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("CeklerAPI: JWT validation failed for query token");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            // Header'dan token'ı dene
            $user = $this->validateAuth();
            if (!$user) {
                error_log("CeklerAPI: Auth failed");
                return false;
            }
        }
        
        try {
            $user_role = $user['rol'];
            $firma_id = $user['firma_id'];
            
            if ($user_role === 'super_admin') {
                // Super admin tüm çekleri görebilir
                $query = "SELECT c.id, c.cek_no, c.tutar, c.vade_tarihi, c.durum, 
                                 c.banka_adi, c.aciklama, c.olusturma_tarihi, c.cari_disi_kisi,
                                 car.unvan as cari_unvan
                         FROM cekler c
                         LEFT JOIN cariler car ON c.cari_id = car.id
                         ORDER BY c.olusturma_tarihi DESC";
                $stmt = $this->db->prepare($query);
            } else {
                // Diğer kullanıcılar sadece kendi firmalarının çeklerini görebilir
                $query = "SELECT c.id, c.cek_no, c.tutar, c.vade_tarihi, c.durum, 
                                 c.banka_adi, c.aciklama, c.olusturma_tarihi, c.cari_disi_kisi,
                                 car.unvan as cari_unvan
                         FROM cekler c
                         LEFT JOIN cariler car ON c.cari_id = car.id
                         WHERE c.firma_id = ? 
                         ORDER BY c.olusturma_tarihi DESC";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cekler = [];
            while ($row = $result->fetch_assoc()) {
                $cekler[] = [
                    'id' => $row['id'],
                    'cek_no' => $row['cek_no'],
                    'tutar' => $row['tutar'],
                    'vade_tarihi' => $row['vade_tarihi'],
                    'durum' => $row['durum'],
                    'banka_adi' => $row['banka_adi'],
                    'aciklama' => $row['aciklama'],
                    'olusturma_tarihi' => $row['olusturma_tarihi'],
                    'cari_unvan' => $row['cari_unvan'],
                    'cari_disi_kisi' => $row['cari_disi_kisi']
                ];
            }
            
            $this->sendSuccess(['cekler' => $cekler], 'Çekler listelendi');
            return true;
            
        } catch (Exception $e) {
            error_log("CeklerAPI Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
}

// API'yi çalıştır
$api = new CeklerAPI();
$api->handleRequest();
?>
