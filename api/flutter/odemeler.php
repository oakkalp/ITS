<?php
require_once 'flutter_api.php';

class OdemelerAPI extends FlutterAPI {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $this->getAction();
        
        switch ($method) {
            case 'GET':
                if ($action === 'list') {
                    $this->getOdemeler();
                } else {
                    $this->sendError('Geçersiz işlem', 400);
                }
                break;
            default:
                $this->sendError('Desteklenmeyen HTTP metodu', 405);
        }
    }
    
    private function getOdemeler() {
        try {
            $user = $this->validateAuth();
            if (!$user) return;
            
            $firma_id = $user['firma_id'];
            
            // Ödenmemiş faturaları ödemeler olarak göster (web ile aynı mantık)
            $query = "SELECT 
                        f.id,
                        f.genel_toplam as tutar,
                        f.fatura_tarihi as odeme_tarihi,
                        'fatura' as odeme_tipi,
                        CONCAT('Fatura: ', f.fatura_no) as aciklama,
                        c.unvan as cari_unvan,
                        f.fatura_no,
                        f.fatura_tipi,
                        u.ad_soyad as kullanici_adi,
                        f.odeme_durumu,
                        (f.genel_toplam - f.odenen_tutar) as kalan_tutar
                      FROM faturalar f
                      LEFT JOIN cariler c ON f.cari_id = c.id
                      LEFT JOIN kullanicilar u ON f.kullanici_id = u.id
                      WHERE f.firma_id = ? AND f.odeme_durumu != 'odendi'
                      ORDER BY f.fatura_tarihi DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $odemeler = [];
            while ($row = $result->fetch_assoc()) {
                // Fatura tipini Türkçe'ye çevir
                $fatura_tipi = $row['fatura_tipi'] == 'alis' ? 'Alış' : 'Satış';
                $odeme_durumu = $this->translateOdemeDurumu($row['odeme_durumu']);
                
                $odemeler[] = [
                    'id' => $row['id'],
                    'tutar' => $row['tutar'],
                    'kalan_tutar' => $row['kalan_tutar'],
                    'odeme_tarihi' => $row['odeme_tarihi'],
                    'odeme_yontemi' => 'Fatura',
                    'aciklama' => $row['aciklama'] ?: 'Fatura',
                    'cari_unvan' => $row['cari_unvan'] ?: 'N/A',
                    'fatura_no' => $row['fatura_no'] ?: null,
                    'fatura_tipi' => $fatura_tipi,
                    'odeme_durumu' => $odeme_durumu,
                    'kullanici_adi' => $row['kullanici_adi']
                ];
            }
            
            $this->sendSuccess($odemeler, 'Ödemeler listelendi');
            
        } catch (Exception $e) {
            error_log("Ödemeler API Hatası: " . $e->getMessage());
            $this->sendError('Veriler yüklenirken hata oluştu', 500);
        }
    }
    
    private function translateOdemeYontemi($tip) {
        $translations = [
            'nakit' => 'Nakit',
            'banka' => 'Banka',
            'cek' => 'Çek',
            'kredi_karti' => 'Kredi Kartı'
        ];
        
        return $translations[$tip] ?? $tip;
    }
    
    private function translateOdemeDurumu($durum) {
        $translations = [
            'odenmedi' => 'Ödenmedi',
            'kismi' => 'Kısmi Ödendi',
            'odendi' => 'Ödendi'
        ];
        
        return $translations[$durum] ?? $durum;
    }
}

// API'yi çalıştır
$api = new OdemelerAPI();
$api->handleRequest();
?>
