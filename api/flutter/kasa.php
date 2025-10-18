<?php
/**
 * Kasa API
 */

require_once 'flutter_api.php';

class KasaAPI extends FlutterAPI {
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        error_log("KasaAPI: Method=$method, Action=$action");
        
        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'list':
                        return $this->getKasaHareketleri();
                    case 'bakiye':
                        return $this->getKasaBakiye();
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
    
    private function getKasaHareketleri() {
        // Önce query parameter'dan token'ı dene
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("KasaAPI: JWT validation failed for query token");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            // Header'dan token'ı dene
            $user = $this->validateAuth();
            if (!$user) {
                error_log("KasaAPI: Auth failed");
                return false;
            }
        }
        
        try {
            $user_role = $user['rol'];
            $firma_id = $user['firma_id'];
            
            if ($user_role === 'super_admin') {
                // Super admin tüm kasa hareketlerini görebilir
                $query = "SELECT kh.id, kh.tarih, kh.aciklama, kh.tutar, kh.islem_tipi, 
                                 kh.olusturma_tarihi,
                                 u.ad_soyad as kullanici_adi
                         FROM kasa_hareketleri kh
                         LEFT JOIN kullanicilar u ON kh.kullanici_id = u.id
                         ORDER BY kh.olusturma_tarihi DESC
                         LIMIT 100";
                $stmt = $this->db->prepare($query);
            } else {
                // Diğer kullanıcılar sadece kendi firmalarının kasa hareketlerini görebilir
                $query = "SELECT kh.id, kh.tarih, kh.aciklama, kh.tutar, kh.islem_tipi, 
                                 kh.olusturma_tarihi,
                                 u.ad_soyad as kullanici_adi
                         FROM kasa_hareketleri kh
                         LEFT JOIN kullanicilar u ON kh.kullanici_id = u.id
                         WHERE kh.firma_id = ? 
                         ORDER BY kh.olusturma_tarihi DESC
                         LIMIT 100";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $hareketler = [];
            while ($row = $result->fetch_assoc()) {
                $hareketler[] = [
                    'id' => $row['id'],
                    'tarih' => $row['tarih'],
                    'aciklama' => $row['aciklama'],
                    'tutar' => $row['tutar'],
                    'islem_tipi' => $row['islem_tipi'],
                    'olusturma_tarihi' => $row['olusturma_tarihi'],
                    'kullanici_adi' => $row['kullanici_adi']
                ];
            }
            
            $this->sendSuccess(['hareketler' => $hareketler], 'Kasa hareketleri listelendi');
            return true;
            
        } catch (Exception $e) {
            error_log("KasaAPI Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
    
    private function getKasaBakiye() {
        // Önce query parameter'dan token'ı dene
        $token = $_GET['token'] ?? '';
        if (!empty($token)) {
            $user = $this->validateJWT($token);
            if (!$user) {
                error_log("KasaAPI: JWT validation failed for query token");
                $this->sendError('Yetkilendirme hatası', 401);
                return false;
            }
        } else {
            // Header'dan token'ı dene
            $user = $this->validateAuth();
            if (!$user) {
                error_log("KasaAPI: Auth failed");
                return false;
            }
        }
        
        try {
            $user_role = $user['rol'];
            $firma_id = $user['firma_id'];
            
            if ($user_role === 'super_admin') {
                // Super admin tüm firmaların bakiye toplamını görebilir
                $query = "SELECT 
                            SUM(CASE WHEN islem_tipi = 'GELIR' THEN tutar ELSE 0 END) as toplam_gelir,
                            SUM(CASE WHEN islem_tipi = 'GIDER' THEN tutar ELSE 0 END) as toplam_gider,
                            (SUM(CASE WHEN islem_tipi = 'GELIR' THEN tutar ELSE 0 END) - 
                             SUM(CASE WHEN islem_tipi = 'GIDER' THEN tutar ELSE 0 END)) as bakiye
                         FROM kasa_hareketleri 
                         WHERE aktif = 1";
                $stmt = $this->db->prepare($query);
            } else {
                // Diğer kullanıcılar sadece kendi firmalarının bakiyesini görebilir
                $query = "SELECT 
                            SUM(CASE WHEN islem_tipi = 'GELIR' THEN tutar ELSE 0 END) as toplam_gelir,
                            SUM(CASE WHEN islem_tipi = 'GIDER' THEN tutar ELSE 0 END) as toplam_gider,
                            (SUM(CASE WHEN islem_tipi = 'GELIR' THEN tutar ELSE 0 END) - 
                             SUM(CASE WHEN islem_tipi = 'GIDER' THEN tutar ELSE 0 END)) as bakiye
                         FROM kasa_hareketleri 
                         WHERE firma_id = ? AND aktif = 1";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('i', $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $bakiye = $result->fetch_assoc();
            
            if (!$bakiye) {
                $bakiye = [
                    'toplam_gelir' => 0,
                    'toplam_gider' => 0,
                    'bakiye' => 0
                ];
            }
            
            $this->sendSuccess($bakiye, 'Kasa bakiyesi alındı');
            return true;
            
        } catch (Exception $e) {
            error_log("KasaAPI Error: " . $e->getMessage());
            $this->sendError('Veritabanı hatası: ' . $e->getMessage(), 500);
            return false;
        }
    }
}

// API'yi çalıştır
$api = new KasaAPI();
$api->handleRequest();
?>
