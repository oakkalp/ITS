<?php

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'yazma');

$firma_id = get_firma_id();
$kullanici_id = get_user_id();

try {
    $db->begin_transaction();
    
    // Form verilerini al
    $id = $_POST['id'] ?? null;
    $teklif_basligi = $_POST['teklif_basligi'];
    $teklif_tarihi = $_POST['teklif_tarihi'];
    $gecerlilik_tarihi = $_POST['gecerlilik_tarihi'];
    $cari_secimi = $_POST['cari_secimi'];
    $cari_id = ($cari_secimi === 'cari') ? $_POST['cari_id'] : null;
    $cari_disi_kisi = ($cari_secimi === 'cari_disi') ? $_POST['cari_disi_kisi'] : null;
    $cari_disi_adres = ($cari_secimi === 'cari_disi') ? $_POST['cari_disi_adres'] : null;
    $cari_disi_telefon = ($cari_secimi === 'cari_disi') ? $_POST['cari_disi_telefon'] : null;
    $cari_disi_email = ($cari_secimi === 'cari_disi') ? $_POST['cari_disi_email'] : null;
    $aciklama = $_POST['aciklama'] ?? '';
    $urunler = json_decode($_POST['urunler'], true);
    
    // Toplam hesaplamaları
    $ara_toplam = 0;
    $kdv_tutari = 0;
    
    foreach ($urunler as $urun) {
        $miktar = floatval($urun['miktar']);
        $birim_fiyat = floatval($urun['birim_fiyat']);
        $kdv_orani = floatval($urun['kdv_orani']);
        
        $satir_ara_toplam = $miktar * $birim_fiyat;
        $satir_kdv = $satir_ara_toplam * ($kdv_orani / 100);
        
        $ara_toplam += $satir_ara_toplam;
        $kdv_tutari += $satir_kdv;
    }
    
    $genel_toplam = $ara_toplam + $kdv_tutari;
    
    if ($id) {
        // Güncelleme
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
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssissssdddsii", 
            $teklif_basligi, $teklif_tarihi, $gecerlilik_tarihi, $cari_id, $cari_disi_kisi, 
            $cari_disi_adres, $cari_disi_telefon, $cari_disi_email,
            $ara_toplam, $kdv_tutari, $genel_toplam, $aciklama, $id, $firma_id);
        $stmt->execute();
        
        // Eski detayları sil
        $delete_query = "DELETE FROM teklif_detaylari WHERE teklif_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);
        $delete_stmt->execute();
        
        $teklif_id = $id;
        $message = 'Teklif güncellendi';
    } else {
        // Teklif numarası oluştur
        $teklif_no_query = "SELECT COALESCE(MAX(CAST(teklif_no AS UNSIGNED)), 0) + 1 as next_no FROM teklifler WHERE firma_id = ?";
        $teklif_no_stmt = $db->prepare($teklif_no_query);
        $teklif_no_stmt->bind_param("i", $firma_id);
        $teklif_no_stmt->execute();
        $teklif_no_result = $teklif_no_stmt->get_result();
        $teklif_no_row = $teklif_no_result->fetch_assoc();
        $teklif_no = $teklif_no_row['next_no'];
        
        // Yeni ekleme
        $query = "INSERT INTO teklifler 
                  (firma_id, teklif_no, teklif_basligi, teklif_tarihi, gecerlilik_tarihi, cari_id, cari_disi_kisi, 
                   cari_disi_adres, cari_disi_telefon, cari_disi_email, ara_toplam, 
                   kdv_tutari, genel_toplam, aciklama, kullanici_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("issssissssdddsi", 
            $firma_id, $teklif_no, $teklif_basligi, $teklif_tarihi, $gecerlilik_tarihi, $cari_id, $cari_disi_kisi,
            $cari_disi_adres, $cari_disi_telefon, $cari_disi_email,
            $ara_toplam, $kdv_tutari, $genel_toplam, $aciklama, $kullanici_id);
        $stmt->execute();
        
        $teklif_id = $db->insert_id;
        $message = 'Teklif oluşturuldu';
    }
    
    // Ürün detaylarını ekle
    foreach ($urunler as $urun) {
        $urun_id = $urun['urun_id'];
        $manuel_urun = $urun['manuel_urun'] ?? null;
        $miktar = floatval($urun['miktar']);
        $birim_fiyat = floatval($urun['birim_fiyat']);
        $kdv_orani = floatval($urun['kdv_orani']);
        
        $satir_ara_toplam = $miktar * $birim_fiyat;
        $satir_kdv = $satir_ara_toplam * ($kdv_orani / 100);
        $satir_toplam = $satir_ara_toplam + $satir_kdv;
        
        // Manuel ürün kontrolü
        if ($manuel_urun) {
            // Manuel ürün için urun_id null olacak
            $detail_query = "INSERT INTO teklif_detaylari 
                             (teklif_id, urun_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam, aciklama) 
                             VALUES (?, NULL, ?, ?, ?, ?, ?, ?)";
            
            $detail_stmt = $db->prepare($detail_query);
            $detail_stmt->bind_param("iddddds", 
                $teklif_id, $miktar, $birim_fiyat, $kdv_orani, $satir_kdv, $satir_toplam, $manuel_urun);
        } else {
            // Normal ürün için urun_id gerekli
            if (empty($urun_id)) {
                error_log("Ürün ID boş, atlanıyor");
                continue;
            }
            
            // Ürün ID'nin geçerli olup olmadığını kontrol et
            $urun_check = $db->query("SELECT id FROM urunler WHERE id = $urun_id AND firma_id = $firma_id");
            if ($urun_check->num_rows == 0) {
                error_log("Geçersiz ürün ID: $urun_id");
                continue;
            }
            
            // Normal ürün için ürün adını veritabanından al
            $urun_adi_query = "SELECT urun_adi FROM urunler WHERE id = ? AND firma_id = ?";
            $urun_adi_stmt = $db->prepare($urun_adi_query);
            $urun_adi_stmt->bind_param("ii", $urun_id, $firma_id);
            $urun_adi_stmt->execute();
            $urun_adi_result = $urun_adi_stmt->get_result();
            $urun_adi = '';
            if ($urun_adi_row = $urun_adi_result->fetch_assoc()) {
                $urun_adi = $urun_adi_row['urun_adi'];
            }
            
            $detail_query = "INSERT INTO teklif_detaylari 
                             (teklif_id, urun_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam, aciklama) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $detail_stmt = $db->prepare($detail_query);
            $detail_stmt->bind_param("iiddddds", 
                $teklif_id, $urun_id, $miktar, $birim_fiyat, $kdv_orani, $satir_kdv, $satir_toplam, $urun_adi);
        }
        
        if (!$detail_stmt->execute()) {
            error_log("Teklif detayı eklenemedi: " . $detail_stmt->error);
            throw new Exception("Teklif detayı eklenemedi: " . $detail_stmt->error);
        }
    }
    
    $db->commit();
    json_success($message, ['id' => $teklif_id]);
    
} catch (Exception $e) {
    $db->rollback();
    error_log("Teklif kaydetme hatası: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    json_error('Teklif kaydedilirken hata oluştu: ' . $e->getMessage());
}
?>
