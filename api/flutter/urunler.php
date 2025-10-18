<?php
require_once 'flutter_api.php';

class UrunlerAPI extends FlutterAPI {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $this->getUrunler();
                break;
            default:
                $this->sendError('Geçersiz işlem', 400);
        }
    }
    
    private function getUrunler() {
        try {
            // Önce query parameter'dan token'ı dene
            $token = $_GET['token'] ?? '';
            if (!empty($token)) {
                $user = $this->validateJWT($token);
                if (!$user) {
                    error_log("UrunlerAPI: JWT validation failed for query token");
                    $this->sendError('Yetkilendirme hatası', 401);
                    return;
                }
            } else {
                // Header'dan token'ı dene
                $user = $this->validateAuth();
                if (!$user) return;
            }
            
            $firma_id = $user['firma_id'];
            
            $query = "SELECT id, urun_adi, stok_miktari, satis_fiyati as birim_fiyat, kategori 
                     FROM urunler 
                     WHERE firma_id = ? AND aktif = 1
                     ORDER BY urun_adi ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $urunler = [];
            while ($row = $result->fetch_assoc()) {
                $urunler[] = [
                    'id' => $row['id'],
                    'urun_adi' => $row['urun_adi'],
                    'stok_miktari' => $row['stok_miktari'],
                    'birim_fiyat' => $row['birim_fiyat'],
                    'kategori' => $row['kategori']
                ];
            }
            
            $this->sendSuccess($urunler, 'Ürünler başarıyla alındı');
            
        } catch (Exception $e) {
            error_log("Ürünler API Hatası: " . $e->getMessage());
            $this->sendError('Veriler yüklenirken hata oluştu', 500);
        }
    }
}

$api = new UrunlerAPI();
$api->handleRequest();
?>
