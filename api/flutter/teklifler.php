<?php
require_once 'flutter_api.php';

class TekliflerAPI extends FlutterAPI {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $this->getAction();
        
        error_log("TekliflerAPI: Method=$method, Action=$action");
        error_log("TekliflerAPI: Headers=" . json_encode(getallheaders()));
        
        switch ($method) {
            case 'GET':
                if ($action === 'list') {
                    $this->getTeklifler();
                } elseif ($action === 'get') {
                    $this->getTeklif();
                } elseif ($action === 'pdf') {
                    $this->generatePDF();
                } else {
                    $this->sendError('Geçersiz işlem', 400);
                }
                break;
            case 'POST':
                $this->createTeklif();
                break;
            case 'PUT':
                $this->updateTeklif();
                break;
            case 'DELETE':
                $this->deleteTeklif();
                break;
            default:
                $this->sendError('Desteklenmeyen HTTP metodu', 405);
        }
    }
    
    private function getTeklifler() {
        try {
            $user = $this->validateAuth();
            if (!$user) {
                error_log("getTeklifler: validateAuth failed");
                return;
            }
            
            $firma_id = $user['firma_id'];
            $user_role = $user['rol'];
            
            // Rol bazlı sorgu
            if ($user_role === 'super_admin') {
                $query = "SELECT 
                            t.id,
                            t.teklif_no,
                            t.teklif_basligi,
                            t.teklif_tarihi,
                            t.gecerlilik_tarihi,
                            t.ara_toplam,
                            t.kdv_tutari,
                            t.genel_toplam,
                            t.aciklama,
                            t.olusturma_tarihi,
                            c.unvan as cari_unvan,
                            t.cari_disi_kisi,
                            u.ad_soyad as kullanici_adi,
                            f.firma_adi,
                            t.durum
                          FROM teklifler t
                          LEFT JOIN cariler c ON t.cari_id = c.id
                          LEFT JOIN kullanicilar u ON t.kullanici_id = u.id
                          LEFT JOIN firmalar f ON t.firma_id = f.id
                          ORDER BY t.olusturma_tarihi DESC";
                $stmt = $this->db->prepare($query);
            } else {
                $query = "SELECT 
                            t.id,
                            t.teklif_no,
                            t.teklif_basligi,
                            t.teklif_tarihi,
                            t.gecerlilik_tarihi,
                            t.ara_toplam,
                            t.kdv_tutari,
                            t.genel_toplam,
                            t.aciklama,
                            t.olusturma_tarihi,
                            c.unvan as cari_unvan,
                            t.cari_disi_kisi,
                            u.ad_soyad as kullanici_adi,
                            t.durum
                          FROM teklifler t
                          LEFT JOIN cariler c ON t.cari_id = c.id
                          LEFT JOIN kullanicilar u ON t.kullanici_id = u.id
                          WHERE t.firma_id = ?
                          ORDER BY t.olusturma_tarihi DESC";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $teklifler = [];
            while ($row = $result->fetch_assoc()) {
                $teklifler[] = [
                    'id' => $row['id'],
                    'teklif_no' => $row['teklif_no'],
                    'teklif_basligi' => $row['teklif_basligi'],
                    'teklif_tarihi' => $row['teklif_tarihi'],
                    'gecerlilik_tarihi' => $row['gecerlilik_tarihi'],
                    'ara_toplam' => $row['ara_toplam'],
                    'kdv_tutari' => $row['kdv_tutari'],
                    'genel_toplam' => $row['genel_toplam'],
                    'aciklama' => $row['aciklama'],
                    'olusturma_tarihi' => $row['olusturma_tarihi'],
                    'cari_unvan' => $row['cari_unvan'],
                    'cari_disi_kisi' => $row['cari_disi_kisi'],
                    'kullanici_adi' => $row['kullanici_adi'],
                    'firma_adi' => $row['firma_adi'] ?? null,
                    'durum' => $row['durum'] ?? 'hazir'
                ];
            }
            
            $this->sendSuccess($teklifler, 'Teklifler listelendi');
            
        } catch (Exception $e) {
            error_log("Teklifler API Hatası: " . $e->getMessage());
            error_log("Teklifler API Stack Trace: " . $e->getTraceAsString());
            $this->sendError('Veriler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
        }
    }
    
    private function getTeklif() {
        try {
            $user = $this->validateAuth();
            if (!$user) return;
            
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Teklif ID gerekli', 400);
                return;
            }
            
            $firma_id = $user['firma_id'];
            $user_role = $user['rol'];
            
            // Rol bazlı sorgu
            if ($user_role === 'super_admin') {
                $query = "SELECT 
                            t.id,
                            t.teklif_no,
                            t.teklif_basligi,
                            t.teklif_tarihi,
                            t.gecerlilik_tarihi,
                            t.cari_id,
                            t.cari_disi_kisi,
                            t.cari_disi_adres,
                            t.cari_disi_telefon,
                            t.cari_disi_email,
                            t.ara_toplam,
                            t.kdv_tutari,
                            t.genel_toplam,
                            t.aciklama,
                            t.olusturma_tarihi,
                            c.unvan as cari_unvan,
                            u.ad_soyad as kullanici_adi
                          FROM teklifler t
                          LEFT JOIN cariler c ON t.cari_id = c.id
                          LEFT JOIN kullanicilar u ON t.kullanici_id = u.id
                          WHERE t.id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $id);
            } else {
                $query = "SELECT 
                            t.id,
                            t.teklif_no,
                            t.teklif_basligi,
                            t.teklif_tarihi,
                            t.gecerlilik_tarihi,
                            t.cari_id,
                            t.cari_disi_kisi,
                            t.cari_disi_adres,
                            t.cari_disi_telefon,
                            t.cari_disi_email,
                            t.ara_toplam,
                            t.kdv_tutari,
                            t.genel_toplam,
                            t.aciklama,
                            t.olusturma_tarihi,
                            c.unvan as cari_unvan,
                            u.ad_soyad as kullanici_adi
                          FROM teklifler t
                          LEFT JOIN cariler c ON t.cari_id = c.id
                          LEFT JOIN kullanicilar u ON t.kullanici_id = u.id
                          WHERE t.id = ? AND t.firma_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ii", $id, $firma_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Teklif detaylarını al
                $detay_query = "SELECT 
                                  td.id,
                                  td.urun_id,
                                  td.miktar,
                                  td.birim_fiyat,
                                  td.kdv_orani,
                                  td.kdv_tutari,
                                  td.toplam,
                                  td.aciklama,
                                  u.urun_adi,
                                  u.birim
                                FROM teklif_detaylari td
                                LEFT JOIN urunler u ON td.urun_id = u.id
                                WHERE td.teklif_id = ?
                                ORDER BY td.id";
                
                $detay_stmt = $this->db->prepare($detay_query);
                $detay_stmt->bind_param("i", $id);
                $detay_stmt->execute();
                $detay_result = $detay_stmt->get_result();
                
                $detaylar = [];
                while ($detay_row = $detay_result->fetch_assoc()) {
                    $detaylar[] = [
                        'id' => $detay_row['id'],
                        'urun_id' => $detay_row['urun_id'],
                        'urun_adi' => $detay_row['urun_adi'],
                        'birim' => $detay_row['birim'],
                        'miktar' => $detay_row['miktar'],
                        'birim_fiyat' => $detay_row['birim_fiyat'],
                        'kdv_orani' => $detay_row['kdv_orani'],
                        'kdv_tutari' => $detay_row['kdv_tutari'],
                        'toplam' => $detay_row['toplam'],
                        'aciklama' => $detay_row['aciklama']
                    ];
                }
                
                $teklif = [
                    'id' => $row['id'],
                    'teklif_no' => $row['teklif_no'],
                    'teklif_basligi' => $row['teklif_basligi'],
                    'teklif_tarihi' => $row['teklif_tarihi'],
                    'gecerlilik_tarihi' => $row['gecerlilik_tarihi'],
                    'cari_id' => $row['cari_id'],
                    'cari_unvan' => $row['cari_unvan'],
                    'cari_disi_kisi' => $row['cari_disi_kisi'],
                    'cari_disi_adres' => $row['cari_disi_adres'],
                    'cari_disi_telefon' => $row['cari_disi_telefon'],
                    'cari_disi_email' => $row['cari_disi_email'],
                    'ara_toplam' => $row['ara_toplam'],
                    'kdv_tutari' => $row['kdv_tutari'],
                    'genel_toplam' => $row['genel_toplam'],
                    'aciklama' => $row['aciklama'],
                    'olusturma_tarihi' => $row['olusturma_tarihi'],
                    'kullanici_adi' => $row['kullanici_adi'],
                    'detaylar' => $detaylar
                ];
                
                $this->sendSuccess($teklif, 'Teklif detayları alındı');
            } else {
                $this->sendError('Teklif bulunamadı', 404);
            }
            
        } catch (Exception $e) {
            error_log("Teklif Detay API Hatası: " . $e->getMessage());
            $this->sendError('Veriler yüklenirken hata oluştu', 500);
        }
    }
    
    private function createTeklif() {
        try {
            // Önce query parameter'dan token'ı dene
            $token = $_GET['token'] ?? '';
            if (!empty($token)) {
                // Token'ı header'a ekle ve validateAuth kullan
                $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
                $user = $this->validateAuth();
                if (!$user) {
                    error_log("TekliflerAPI: Auth validation failed for query token");
                    $this->sendError('Yetkilendirme hatası', 401);
                    return;
                }
            } else {
                // Header'dan token'ı dene
                $user = $this->validateAuth();
                if (!$user) return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendError('Geçersiz JSON', 400);
                return;
            }
            
            $firma_id = $user['firma_id'];
            $kullanici_id = $user['id'];
            
            // Teklif numarası oluştur
            $teklif_no_query = "SELECT COALESCE(MAX(CAST(teklif_no AS UNSIGNED)), 0) + 1 as next_no FROM teklifler WHERE firma_id = ?";
            $teklif_no_stmt = $this->db->prepare($teklif_no_query);
            $teklif_no_stmt->bind_param("i", $firma_id);
            $teklif_no_stmt->execute();
            $teklif_no_result = $teklif_no_stmt->get_result();
            $teklif_no_row = $teklif_no_result->fetch_assoc();
            $teklif_no = $teklif_no_row['next_no'];
            
            $this->db->begin_transaction();
            
            // Teklif ekle
            $query = "INSERT INTO teklifler 
                      (firma_id, teklif_no, teklif_basligi, teklif_tarihi, gecerlilik_tarihi, cari_id, cari_disi_kisi, 
                       cari_disi_adres, cari_disi_telefon, cari_disi_email, ara_toplam, 
                       kdv_tutari, genel_toplam, aciklama, kullanici_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("issssissssdddsi", 
                $firma_id, $teklif_no, $input['teklif_basligi'], $input['teklif_tarihi'], $input['gecerlilik_tarihi'], 
                $input['cari_id'], $input['cari_disi_kisi'], $input['cari_disi_adres'], $input['cari_disi_telefon'], 
                $input['cari_disi_email'], $input['ara_toplam'], $input['kdv_tutari'], $input['genel_toplam'], 
                $input['aciklama'], $kullanici_id);
            $stmt->execute();
            
            $teklif_id = $this->db->insert_id;
            
            // Ürün detaylarını ekle
            if (!empty($input['urunler'])) {
                foreach ($input['urunler'] as $urun) {
                    $urun_id = $urun['urun_id'] ?? null;
                    $manuel_urun = $urun['manuel_urun'] ?? null;
                    $miktar = floatval($urun['miktar']);
                    $birim_fiyat = floatval($urun['birim_fiyat']);
                    $kdv_orani = floatval($urun['kdv_orani']);
                    
                    $satir_ara_toplam = $miktar * $birim_fiyat;
                    $satir_kdv = $satir_ara_toplam * ($kdv_orani / 100);
                    $satir_toplam = $satir_ara_toplam + $satir_kdv;
                    
                    // Manuel ürün için urun_id olmadan INSERT
                    if ($manuel_urun && empty($urun_id)) {
                        $detail_query = "INSERT INTO teklif_detaylari 
                                         (teklif_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam, aciklama) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                        
                        $detail_stmt = $this->db->prepare($detail_query);
                        $detail_stmt->bind_param("iddddds", 
                            $teklif_id, $miktar, $birim_fiyat, $kdv_orani, $satir_kdv, $satir_toplam, $manuel_urun);
                    } else {
                        // Normal ürün için urun_id ile INSERT
                        $detail_query = "INSERT INTO teklif_detaylari 
                                         (teklif_id, urun_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam, aciklama) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $detail_stmt = $this->db->prepare($detail_query);
                        $detail_stmt->bind_param("iiddddds", 
                            $teklif_id, $urun_id, $miktar, $birim_fiyat, $kdv_orani, $satir_kdv, $satir_toplam, $manuel_urun);
                    }
                    
                    $detail_stmt->execute();
                }
            }
            
            $this->db->commit();
            $this->sendSuccess(['id' => $teklif_id], 'Teklif başarıyla oluşturuldu');
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Teklif Oluşturma Hatası: " . $e->getMessage());
            $this->sendError('Teklif oluşturulurken hata oluştu', 500);
        }
    }
    
    private function updateTeklif() {
        try {
            $user = $this->validateAuth();
            if (!$user) return;
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['id'])) {
                $this->sendError('Teklif ID gerekli', 400);
                return;
            }
            
            $firma_id = $user['firma_id'];
            $user_role = $user['rol'];
            $id = $input['id'];
            
            $this->db->begin_transaction();
            
            // Rol bazlı güncelleme
            if ($user_role === 'super_admin') {
                $query = "UPDATE teklifler SET 
                          teklif_basligi = ?, 
                          teklif_tarihi = ?, 
                          gecerlilik_tarihi = ?, 
                          cari_id = ?, 
                          cari_disi_kisi = ?, 
                          cari_disi_adres = ?, 
                          cari_disi_telefon = ?, 
                          cari_disi_email = ?, 
                          ara_toplam = ?, 
                          kdv_tutari = ?, 
                          genel_toplam = ?, 
                          aciklama = ?,
                          guncelleme_tarihi = CURRENT_TIMESTAMP
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("sssisssssdddsi", 
                    $input['teklif_basligi'], $input['teklif_tarihi'], $input['gecerlilik_tarihi'], $input['cari_id'], 
                    $input['cari_disi_kisi'], $input['cari_disi_adres'], $input['cari_disi_telefon'], 
                    $input['cari_disi_email'], $input['ara_toplam'], $input['kdv_tutari'], $input['genel_toplam'], 
                    $input['aciklama'], $id);
            } else {
                $query = "UPDATE teklifler SET 
                          teklif_basligi = ?, 
                          teklif_tarihi = ?, 
                          gecerlilik_tarihi = ?, 
                          cari_id = ?, 
                          cari_disi_kisi = ?, 
                          cari_disi_adres = ?, 
                          cari_disi_telefon = ?, 
                          cari_disi_email = ?, 
                          ara_toplam = ?, 
                          kdv_tutari = ?, 
                          genel_toplam = ?, 
                          aciklama = ?,
                          guncelleme_tarihi = CURRENT_TIMESTAMP
                        WHERE id = ? AND firma_id = ?";
                
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("sssisssssdddsii", 
                    $input['teklif_basligi'], $input['teklif_tarihi'], $input['gecerlilik_tarihi'], $input['cari_id'], 
                    $input['cari_disi_kisi'], $input['cari_disi_adres'], $input['cari_disi_telefon'], 
                    $input['cari_disi_email'], $input['ara_toplam'], $input['kdv_tutari'], $input['genel_toplam'], 
                    $input['aciklama'], $id, $firma_id);
            }
            
            $stmt->execute();
            
            // Eski detayları sil
            $delete_query = "DELETE FROM teklif_detaylari WHERE teklif_id = ?";
            $delete_stmt = $this->db->prepare($delete_query);
            $delete_stmt->bind_param("i", $id);
            $delete_stmt->execute();
            
            // Yeni detayları ekle
            if (!empty($input['urunler'])) {
                foreach ($input['urunler'] as $urun) {
                    $urun_id = $urun['urun_id'] ?? null;
                    $manuel_urun = $urun['manuel_urun'] ?? null;
                    $miktar = floatval($urun['miktar']);
                    $birim_fiyat = floatval($urun['birim_fiyat']);
                    $kdv_orani = floatval($urun['kdv_orani']);
                    
                    $satir_ara_toplam = $miktar * $birim_fiyat;
                    $satir_kdv = $satir_ara_toplam * ($kdv_orani / 100);
                    $satir_toplam = $satir_ara_toplam + $satir_kdv;
                    
                    // Manuel ürün için urun_id olmadan INSERT
                    if ($manuel_urun && empty($urun_id)) {
                        $detail_query = "INSERT INTO teklif_detaylari 
                                         (teklif_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam, aciklama) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                        
                        $detail_stmt = $this->db->prepare($detail_query);
                        $detail_stmt->bind_param("iddddds", 
                            $id, $miktar, $birim_fiyat, $kdv_orani, $satir_kdv, $satir_toplam, $manuel_urun);
                    } else {
                        // Normal ürün için urun_id ile INSERT
                        $detail_query = "INSERT INTO teklif_detaylari 
                                         (teklif_id, urun_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam, aciklama) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $detail_stmt = $this->db->prepare($detail_query);
                        $detail_stmt->bind_param("iiddddds", 
                            $id, $urun_id, $miktar, $birim_fiyat, $kdv_orani, $satir_kdv, $satir_toplam, $manuel_urun);
                    }
                    
                    $detail_stmt->execute();
                }
            }
            
            $this->db->commit();
            $this->sendSuccess(['id' => $id], 'Teklif başarıyla güncellendi');
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Teklif Güncelleme Hatası: " . $e->getMessage());
            $this->sendError('Teklif güncellenirken hata oluştu', 500);
        }
    }
    
    private function deleteTeklif() {
        try {
            $user = $this->validateAuth();
            if (!$user) return;
            
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->sendError('Teklif ID gerekli', 400);
                return;
            }
            
            $firma_id = $user['firma_id'];
            $user_role = $user['rol'];
            
            $this->db->begin_transaction();
            
            // Rol bazlı silme
            if ($user_role === 'super_admin') {
                $query = "DELETE FROM teklifler WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $id);
            } else {
                $query = "DELETE FROM teklifler WHERE id = ? AND firma_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ii", $id, $firma_id);
            }
            
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                // Detayları da sil
                $delete_detail_query = "DELETE FROM teklif_detaylari WHERE teklif_id = ?";
                $delete_detail_stmt = $this->db->prepare($delete_detail_query);
                $delete_detail_stmt->bind_param("i", $id);
                $delete_detail_stmt->execute();
                
                $this->db->commit();
                $this->sendSuccess(['id' => $id], 'Teklif başarıyla silindi');
            } else {
                $this->db->rollback();
                $this->sendError('Teklif bulunamadı veya silme yetkiniz yok', 404);
            }
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Teklif Silme Hatası: " . $e->getMessage());
            $this->sendError('Teklif silinirken hata oluştu', 500);
        }
    }
    
    private function generatePDF() {
        try {
            // Önce query parameter'dan token'ı dene
            $token = $_GET['token'] ?? '';
            if (!empty($token)) {
                // Token'ı header'a ekle ve validateAuth kullan
                $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
                $user = $this->validateAuth();
                if (!$user) {
                    error_log("TekliflerAPI: Auth validation failed for query token");
                    $this->sendError('Yetkilendirme hatası', 401);
                    return false;
                }
            } else {
                // Header'dan token'ı dene
                $user = $this->validateAuth();
                if (!$user) {
                    return false;
                }
            }
            
            $teklif_id = $_GET['id'] ?? '';
            if (empty($teklif_id)) {
                $this->sendError('Teklif ID gerekli', 400);
                return false;
            }
            
            // Teklif detaylarını al
            $query = "SELECT t.*, c.unvan as cari_unvan, c.yetkili_kisi as cari_ad_soyad, 
                             c.telefon as cari_telefon, c.email as cari_email, c.adres as cari_adres,
                             u.ad_soyad as kullanici_adi, f.firma_adi
                      FROM teklifler t
                      LEFT JOIN cariler c ON t.cari_id = c.id
                      LEFT JOIN kullanicilar u ON t.kullanici_id = u.id
                      LEFT JOIN firmalar f ON t.firma_id = f.id
                      WHERE t.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('i', $teklif_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->sendError('Teklif bulunamadı', 404);
                return false;
            }
            
            $teklif = $result->fetch_assoc();
            
            // Teklif detaylarını al
            $detayQuery = "SELECT td.*, u.urun_adi 
                           FROM teklif_detaylari td
                           LEFT JOIN urunler u ON td.urun_id = u.id
                           WHERE td.teklif_id = ?
                           ORDER BY td.id";
            
            $detayStmt = $this->db->prepare($detayQuery);
            $detayStmt->bind_param('i', $teklif_id);
            $detayStmt->execute();
            $detayResult = $detayStmt->get_result();
            
            $detaylar = [];
            while ($row = $detayResult->fetch_assoc()) {
                $detaylar[] = $row;
            }
            
            // HTML oluştur
            $html = $this->generateTeklifHTML($teklif, $detaylar);
            
            // HTML header'ları
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="teklif_' . $teklif['teklif_no'] . '.html"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // HTML çıktısı
            echo $html;
            
        } catch (Exception $e) {
            error_log("PDF Oluşturma Hatası: " . $e->getMessage());
            $this->sendError('PDF oluşturulurken hata oluştu', 500);
        }
    }
    
    private function generateTeklifHTML($teklif, $detaylar) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teklif ' . htmlspecialchars($teklif['teklif_no']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-info { margin-bottom: 20px; }
        .customer-info { margin-bottom: 20px; }
        .offer-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .grand-total { font-size: 18px; color: #2e7d32; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TEKLİF</h1>
        <h2>' . htmlspecialchars($teklif['firma_adi'] ?? 'Firma Adı') . '</h2>
    </div>
    
    <div class="company-info">
        <h3>Firma Bilgileri</h3>
        <p><strong>Firma:</strong> ' . htmlspecialchars($teklif['firma_adi'] ?? '') . '</p>
        <p><strong>Teklif No:</strong> ' . htmlspecialchars($teklif['teklif_no']) . '</p>
        <p><strong>Tarih:</strong> ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '</p>
        <p><strong>Geçerlilik:</strong> ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</p>
    </div>
    
    <div class="customer-info">
        <h3>Müşteri Bilgileri</h3>';
        
        if ($teklif['cari_id']) {
            $html .= '<p><strong>Müşteri:</strong> ' . htmlspecialchars($teklif['cari_unvan'] ?? $teklif['cari_ad_soyad']) . '</p>';
            if ($teklif['cari_telefon']) {
                $html .= '<p><strong>Telefon:</strong> ' . htmlspecialchars($teklif['cari_telefon']) . '</p>';
            }
            if ($teklif['cari_email']) {
                $html .= '<p><strong>E-posta:</strong> ' . htmlspecialchars($teklif['cari_email']) . '</p>';
            }
            if ($teklif['cari_adres']) {
                $html .= '<p><strong>Adres:</strong> ' . htmlspecialchars($teklif['cari_adres']) . '</p>';
            }
        } else {
            $html .= '<p><strong>Müşteri:</strong> ' . htmlspecialchars($teklif['cari_disi_kisi']) . '</p>';
        }
        
        $html .= '</div>
    
    <div class="offer-info">
        <h3>Teklif Detayları</h3>
        <p><strong>Başlık:</strong> ' . htmlspecialchars($teklif['teklif_basligi']) . '</p>';
        
        if ($teklif['aciklama']) {
            $html .= '<p><strong>Açıklama:</strong> ' . htmlspecialchars($teklif['aciklama']) . '</p>';
        }
        
        $html .= '</div>
    
    <table>
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Ürün/Hizmet</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
                <th>KDV %</th>
                <th>KDV Tutarı</th>
                <th>Toplam</th>
            </tr>
        </thead>
        <tbody>';
        
        $sira = 1;
        foreach ($detaylar as $detay) {
            $html .= '<tr>
                <td>' . $sira . '</td>
                <td>' . htmlspecialchars($detay['urun_adi'] ?? 'Manuel Ürün') . '</td>
                <td>' . number_format($detay['miktar'], 2, ',', '.') . '</td>
                <td>' . number_format($detay['birim_fiyat'], 2, ',', '.') . ' ₺</td>
                <td>%' . number_format($detay['kdv_orani'], 0) . '</td>
                <td>' . number_format($detay['kdv_tutari'], 2, ',', '.') . ' ₺</td>
                <td>' . number_format($detay['toplam'], 2, ',', '.') . ' ₺</td>
            </tr>';
            $sira++;
        }
        
        $html .= '</tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6"><strong>Ara Toplam</strong></td>
                <td><strong>' . number_format($teklif['ara_toplam'], 2, ',', '.') . ' ₺</strong></td>
            </tr>
            <tr class="total-row">
                <td colspan="6"><strong>KDV Toplam</strong></td>
                <td><strong>' . number_format($teklif['kdv_tutari'], 2, ',', '.') . ' ₺</strong></td>
            </tr>
            <tr class="total-row grand-total">
                <td colspan="6"><strong>GENEL TOPLAM</strong></td>
                <td><strong>' . number_format($teklif['genel_toplam'], 2, ',', '.') . ' ₺</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 30px; text-align: center;">
        <p><strong>Bu teklif ' . date('d.m.Y') . ' tarihinde oluşturulmuştur.</strong></p>
        <p>Teşekkür ederiz.</p>
    </div>
</body>
</html>';
        
        return $html;
    }
}

// API'yi çalıştır
$api = new TekliflerAPI();
$api->handleRequest();
?>