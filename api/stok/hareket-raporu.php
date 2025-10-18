<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'okuma');

// Buffer'ı temizle
ob_clean();

try {
    $firma_id = get_firma_id();
    $baslangic = $_GET['baslangic'] ?? null;
    $bitis = $_GET['bitis'] ?? null;
    $urun_id = $_GET['urun_id'] ?? null;
    $hareket_tipi = $_GET['hareket_tipi'] ?? null;
    
    error_log("Stok hareket raporu API çağrıldı - Firma ID: $firma_id, Başlangıç: $baslangic, Bitiş: $bitis, Ürün ID: $urun_id, Hareket Tipi: $hareket_tipi");
    
    if (!$baslangic || !$bitis) {
        json_error('Başlangıç ve bitiş tarihi gerekli', 400);
    }
    
    // Tarih formatını kontrol et
    if (!DateTime::createFromFormat('Y-m-d', $baslangic) || !DateTime::createFromFormat('Y-m-d', $bitis)) {
        json_error('Geçersiz tarih formatı', 400);
    }
    
    $hareket_data = getStokHareketleri($firma_id, $baslangic, $bitis, $urun_id, $hareket_tipi);
    $ozet_data = calculateOzet($hareket_data, $urun_id, $baslangic);
    
    error_log("Stok hareket raporu - Bulunan hareket sayısı: " . count($hareket_data));
    
    json_success('Stok hareket raporu yüklendi', $hareket_data, 200, $ozet_data);
    
} catch (Exception $e) {
    error_log("Stok hareket raporu hatası: " . $e->getMessage());
    json_error('Rapor yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}

function getStokHareketleri($firma_id, $baslangic, $bitis, $urun_id = null, $hareket_tipi = null) {
    global $db;
    
    $hareketler = [];
    
    error_log("getStokHareketleri çağrıldı - Firma ID: $firma_id, Başlangıç: $baslangic, Bitiş: $bitis");
    
    // Önce tabloların varlığını kontrol et
    $table_check = $db->query("SHOW TABLES LIKE 'faturalar'");
    if ($table_check->num_rows == 0) {
        error_log("faturalar tablosu bulunamadı");
        return $hareketler;
    }
    
    $table_check = $db->query("SHOW TABLES LIKE 'fatura_detaylari'");
    if ($table_check->num_rows == 0) {
        error_log("fatura_detaylari tablosu bulunamadı");
        return $hareketler;
    }
    
    // Fatura hareketleri
    $fatura_query = "
        SELECT 
            f.fatura_tarihi as tarih,
            u.urun_adi,
            CASE 
                WHEN f.fatura_tipi = 'alis' THEN 'Alış Faturası'
                WHEN f.fatura_tipi = 'satis' THEN 'Satış Faturası'
            END as hareket_tipi_display,
            f.fatura_tipi as hareket_tipi,
            f.fatura_no as belge_no,
            fd.miktar,
            fd.birim_fiyat,
            fd.toplam,
            u.stok_miktari as kalan_stok
        FROM faturalar f
        JOIN fatura_detaylari fd ON f.id = fd.fatura_id
        JOIN urunler u ON fd.urun_id = u.id
        WHERE f.firma_id = ? 
        AND f.fatura_tarihi BETWEEN ? AND ?
    ";
    
    $params = [$firma_id, $baslangic, $bitis];
    $types = "iss";
    
    if ($urun_id) {
        $fatura_query .= " AND fd.urun_id = ?";
        $params[] = $urun_id;
        $types .= "i";
    }
    
    if ($hareket_tipi) {
        if ($hareket_tipi === 'alis') {
            $fatura_query .= " AND f.fatura_tipi = 'alis'";
        } elseif ($hareket_tipi === 'satis') {
            $fatura_query .= " AND f.fatura_tipi = 'satis'";
        }
    }
    
    $fatura_query .= " ORDER BY f.fatura_tarihi ASC";
    
    error_log("SQL Query: " . $fatura_query);
    error_log("Params: " . print_r($params, true));
    
    $stmt = $db->prepare($fatura_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row_count = 0;
    while ($row = $result->fetch_assoc()) {
        // Miktarı hareket tipine göre ayarla
        if ($row['hareket_tipi'] === 'alis') {
            $row['miktar'] = abs($row['miktar']); // Pozitif (giriş)
        } else {
            $row['miktar'] = -abs($row['miktar']); // Negatif (çıkış)
        }
        
        // Kalan stok formatını düzelt
        $row['kalan_stok'] = round(floatval($row['kalan_stok']));
        
        error_log("Hareket bulundu: " . print_r($row, true));
        $hareketler[] = $row;
        $row_count++;
    }
    
    error_log("SQL sonucu: $row_count satır bulundu");
    
    // Manuel stok hareketleri (tablo varsa)
    $table_check = $db->query("SHOW TABLES LIKE 'stok_hareketleri'");
    if ($table_check->num_rows > 0) {
        $manuel_query = "
            SELECT 
                sh.tarih,
                u.urun_adi,
                CASE 
                    WHEN sh.hareket_tipi = 'manuel_giris' THEN 'Elle Giriş'
                    WHEN sh.hareket_tipi = 'manuel_cikis' THEN 'Elle Çıkış'
                END as hareket_tipi_display,
                sh.hareket_tipi as hareket_tipi,
                sh.belge_no as belge_no,
                sh.miktar,
                sh.birim_fiyat,
                sh.toplam,
                u.stok_miktari as kalan_stok
            FROM stok_hareketleri sh
            JOIN urunler u ON sh.urun_id = u.id
            WHERE sh.firma_id = ? 
            AND DATE(sh.tarih) BETWEEN ? AND ?
            AND sh.hareket_tipi IN ('manuel_giris', 'manuel_cikis')
        ";
        
        $manuel_params = [$firma_id, $baslangic, $bitis];
        $manuel_types = "iss";
        
        if ($urun_id) {
            $manuel_query .= " AND sh.urun_id = ?";
            $manuel_params[] = $urun_id;
            $manuel_types .= "i";
        }
        
        if ($hareket_tipi) {
            if ($hareket_tipi === 'manuel') {
                // Manuel hareketler için özel filtreleme
                $manuel_query .= " AND sh.hareket_tipi IN ('manuel_giris', 'manuel_cikis')";
            }
        }
        
        $manuel_query .= " ORDER BY sh.tarih ASC";
        
        error_log("Manuel hareketler SQL Query: " . $manuel_query);
        error_log("Manuel hareketler Params: " . print_r($manuel_params, true));
        
        $manuel_stmt = $db->prepare($manuel_query);
        $manuel_stmt->bind_param($manuel_types, ...$manuel_params);
        $manuel_stmt->execute();
        $manuel_result = $manuel_stmt->get_result();
        
        $manuel_count = 0;
        while ($row = $manuel_result->fetch_assoc()) {
            // Miktarı hareket tipine göre ayarla
            if ($row['hareket_tipi'] === 'manuel_giris') {
                $row['miktar'] = abs($row['miktar']); // Pozitif (giriş)
            } else {
                $row['miktar'] = -abs($row['miktar']); // Negatif (çıkış)
            }
            
            // Kalan stok formatını düzelt
            $row['kalan_stok'] = round(floatval($row['kalan_stok']));
            
            error_log("Manuel hareket bulundu: " . print_r($row, true));
            $hareketler[] = $row;
            $manuel_count++;
        }
        
        error_log("Manuel hareketler bulundu: $manuel_count");
    } else {
        error_log("stok_hareketleri tablosu bulunamadı - manuel hareketler atlanıyor");
    }
    
    // Tarihe göre sırala
    usort($hareketler, function($a, $b) {
        return strtotime($a['tarih']) - strtotime($b['tarih']);
    });
    
    return $hareketler;
}

function calculateOzet($hareketler, $urun_id = null, $baslangic = null) {
    $toplam_giris = 0;
    $toplam_cikis = 0;
    $toplam_deger = 0;
    $toplam_islem = count($hareketler);
    $baslangic_stok = 0;
    $son_stok = 0;
    
    foreach ($hareketler as $hareket) {
        if ($hareket['miktar'] > 0) {
            $toplam_giris += $hareket['miktar'];
        } else {
            $toplam_cikis += abs($hareket['miktar']);
        }
        
        $toplam_deger += $hareket['toplam'];
    }
    
    $net_hareket = $toplam_giris - $toplam_cikis;
    
    // Başlangıç ve son stok hesaplama
    if ($urun_id && $baslangic) {
        global $db;
        
        // Başlangıç stok (tarihten önceki hareketler)
        $baslangic_query = "
            SELECT COALESCE(SUM(
                CASE 
                    WHEN f.fatura_tipi = 'alis' THEN fd.miktar
                    WHEN f.fatura_tipi = 'satis' THEN -fd.miktar
                    ELSE 0
                END
            ), 0) + COALESCE((
                SELECT SUM(
                    CASE 
                        WHEN sh.hareket_tipi = 'manuel_giris' THEN sh.miktar
                        WHEN sh.hareket_tipi = 'manuel_cikis' THEN -sh.miktar
                        ELSE 0
                    END
                )
                FROM stok_hareketleri sh
                WHERE sh.urun_id = ? AND DATE(sh.tarih) < ?
            ), 0) as baslangic_stok
            FROM faturalar f
            JOIN fatura_detaylari fd ON f.id = fd.fatura_id
            WHERE fd.urun_id = ? AND f.fatura_tarihi < ?
        ";
        
        $stmt = $db->prepare($baslangic_query);
        $stmt->bind_param("isis", $urun_id, $baslangic, $urun_id, $baslangic);
        $stmt->execute();
        $baslangic_stok = $stmt->get_result()->fetch_assoc()['baslangic_stok'];
        
        $son_stok = $baslangic_stok + $net_hareket;
    } else {
        // Tüm ürünler için genel bilgi
        $baslangic_stok = 0;
        $son_stok = 0;
    }
    
    // Ortalama fiyat hesaplama
    $ortalama_fiyat = $toplam_islem > 0 ? $toplam_deger / ($toplam_giris + $toplam_cikis) : 0;
    
    return [
        'toplam_giris' => $toplam_giris,
        'toplam_cikis' => $toplam_cikis,
        'net_hareket' => $net_hareket,
        'toplam_deger' => $toplam_deger,
        'toplam_islem' => $toplam_islem,
        'ortalama_fiyat' => $ortalama_fiyat,
        'baslangic_stok' => $baslangic_stok,
        'son_stok' => $son_stok
    ];
}
?>
