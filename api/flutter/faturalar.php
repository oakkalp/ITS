<?php
require_once 'flutter_api.php';

class FaturalarAPI extends FlutterAPI {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $this->getFaturalar();
                break;
            case 'create':
                $this->createFatura();
                break;
            case 'delete':
                $this->deleteFatura();
                break;
            default:
                $this->sendError('Geçersiz işlem', 400);
        }
    }
    
    private function getFaturalar() {
        try {
            // Önce query parameter'dan token'ı dene
            $token = $_GET['token'] ?? '';
            if (!empty($token)) {
                $user = $this->validateJWT($token);
                if (!$user) {
                    error_log("FaturalarAPI: JWT validation failed for query token");
                    $this->sendError('Yetkilendirme hatası', 401);
                    return;
                }
            } else {
                // Header'dan token'ı dene
                $user = $this->validateAuth();
                if (!$user) return;
            }
            
            $firma_id = $user['firma_id'];
            
            $query = "SELECT f.*, c.unvan as cari_unvan 
                     FROM faturalar f
                     LEFT JOIN cariler c ON f.cari_id = c.id
                     WHERE f.firma_id = ? 
                     ORDER BY f.fatura_tarihi DESC, f.id DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $firma_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $faturalar = [];
            while ($row = $result->fetch_assoc()) {
                $faturalar[] = [
                    'id' => $row['id'],
                    'fatura_no' => $row['fatura_no'],
                    'tarih' => $row['fatura_tarihi'],
                    'cari_unvan' => $row['cari_unvan'],
                    'tutar' => $row['ara_toplam'],
                    'kdv_tutari' => $row['kdv_tutari'],
                    'toplam' => $row['genel_toplam'],
                    'odeme_durumu' => $row['odeme_durumu'],
                    'tip' => $row['fatura_tipi'],
                    'aciklama' => $row['aciklama']
                ];
            }
            
            $this->sendSuccess($faturalar, 'Faturalar başarıyla alındı');
            
        } catch (Exception $e) {
            error_log("Faturalar API Hatası: " . $e->getMessage());
            $this->sendError('Veriler yüklenirken hata oluştu', 500);
        }
    }
    
    private function createFatura() {
        try {
            $user = $this->validateAuth();
            if (!$user) return;
            
            $firma_id = $user['firma_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Gerekli alanları kontrol et
            $required_fields = ['fatura_no', 'tarih', 'tutar', 'kdv_tutari', 'toplam'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->sendError("$field alanı gerekli", 400);
                    return;
                }
            }
            
            $fatura_no = $data['fatura_no'];
            $tarih = $data['tarih'];
            $cari_id = $data['cari_id'] ?? null;
            $tutar = floatval($data['tutar']);
            $kdv_tutari = floatval($data['kdv_tutari']);
            $toplam = floatval($data['toplam']);
            $odeme_durumu = $data['odeme_durumu'] ?? 'Bekliyor';
            $tip = $data['tip'] ?? 'Alış';
            $aciklama = $data['aciklama'] ?? '';
            
            // Fatura numarası benzersizlik kontrolü
            $check_query = "SELECT id FROM faturalar WHERE fatura_no = ? AND firma_id = ?";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bind_param("si", $fatura_no, $firma_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $this->sendError('Bu fatura numarası zaten kullanılıyor', 400);
                return;
            }
            
            // Fatura oluştur
            $insert_query = "INSERT INTO faturalar 
                            (firma_id, fatura_no, tarih, cari_id, tutar, kdv_tutari, toplam, odeme_durumu, tip, aciklama) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $this->db->prepare($insert_query);
            $insert_stmt->bind_param("issddddsss", 
                $firma_id, $fatura_no, $tarih, $cari_id, $tutar, $kdv_tutari, $toplam, $odeme_durumu, $tip, $aciklama);
            
            if ($insert_stmt->execute()) {
                $fatura_id = $this->db->insert_id;
                $this->sendSuccess(['id' => $fatura_id], 'Fatura başarıyla oluşturuldu');
            } else {
                $this->sendError('Fatura oluşturulurken hata oluştu', 500);
            }
            
        } catch (Exception $e) {
            error_log("Fatura Oluşturma Hatası: " . $e->getMessage());
            $this->sendError('Fatura oluşturulurken hata oluştu', 500);
        }
    }
    
    private function deleteFatura() {
        try {
            $user = $this->validateAuth();
            if (!$user) return;
            
            $firma_id = $user['firma_id'];
            $fatura_id = $_GET['id'] ?? null;
            
            if (!$fatura_id) {
                $this->sendError('Fatura ID gerekli', 400);
                return;
            }
            
            // Fatura sahiplik kontrolü
            $check_query = "SELECT id FROM faturalar WHERE id = ? AND firma_id = ?";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bind_param("ii", $fatura_id, $firma_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $this->sendError('Fatura bulunamadı', 404);
                return;
            }
            
            // Fatura sil
            $delete_query = "DELETE FROM faturalar WHERE id = ? AND firma_id = ?";
            $delete_stmt = $this->db->prepare($delete_query);
            $delete_stmt->bind_param("ii", $fatura_id, $firma_id);
            
            if ($delete_stmt->execute()) {
                $this->sendSuccess(null, 'Fatura başarıyla silindi');
            } else {
                $this->sendError('Fatura silinirken hata oluştu', 500);
            }
            
        } catch (Exception $e) {
            error_log("Fatura Silme Hatası: " . $e->getMessage());
            $this->sendError('Fatura silinirken hata oluştu', 500);
        }
    }
}

$api = new FaturalarAPI();
$api->handleRequest();
?>




