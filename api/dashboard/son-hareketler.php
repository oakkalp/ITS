<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

try {
    $firma_id = get_firma_id();
    
    // Son hareketleri al (faturalar + kasa hareketleri)
    $hareket_query = "
        SELECT 
            'fatura' as kaynak,
            fatura_tarihi as tarih,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'Satış Faturası'
                WHEN fatura_tipi = 'alis' THEN 'Alış Faturası'
            END as tip_display,
            CASE 
                WHEN fatura_tipi = 'satis' THEN 'gelir'
                WHEN fatura_tipi = 'alis' THEN 'gider'
            END as tip,
            CONCAT('Fatura No: ', fatura_no) as aciklama,
            toplam_tutar as tutar
        FROM faturalar 
        WHERE firma_id = ? 
        
        UNION ALL
        
        SELECT 
            'kasa' as kaynak,
            tarih,
            CASE 
                WHEN islem_tipi = 'gelir' THEN 'Kasa Geliri'
                WHEN islem_tipi = 'gider' THEN 'Kasa Gideri'
            END as tip_display,
            islem_tipi as tip,
            COALESCE(aciklama, 'Kasa Hareketi') as aciklama,
            tutar
        FROM kasa_hareketleri 
        WHERE firma_id = ?
        
        ORDER BY tarih DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($hareket_query);
    $stmt->bind_param("ii", $firma_id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hareketler = [];
    while ($row = $result->fetch_assoc()) {
        $hareketler[] = $row;
    }
    
    json_success('Son hareketler yüklendi', $hareketler);
    
} catch (Exception $e) {
    error_log("Son hareketler hatası: " . $e->getMessage());
    json_error('Hareketler yüklenirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
