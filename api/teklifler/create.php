<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'yazma');

$data = json_decode(file_get_contents('php://input'), true);
$firma_id = get_firma_id();

if (!$data) {
    json_error('Geçersiz veri', 400);
}

$db->begin_transaction();

try {
    // Teklif kaydını ekle
    $stmt = $db->prepare("INSERT INTO teklifler (firma_id, teklif_no, teklif_basligi, cari_id, teklif_tarihi, gecerlilik_tarihi, toplam_tutar, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssds", 
        $firma_id,
        $data['teklif_no'],
        $data['teklif_basligi'],
        $data['cari_id'],
        $data['teklif_tarihi'],
        $data['gecerlilik_tarihi'],
        $data['toplam_tutar'],
        $data['durum'] ?? 'aktif'
    );
    $stmt->execute();
    $teklif_id = $db->insert_id;

    // Teklif detaylarını ekle
    if (isset($data['detaylar']) && is_array($data['detaylar'])) {
        $stmt_detay = $db->prepare("INSERT INTO teklif_detaylari (teklif_id, urun_id, miktar, birim_fiyat, toplam) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($data['detaylar'] as $detay) {
            $stmt_detay->bind_param("iiddd", 
                $teklif_id,
                $detay['urun_id'],
                $detay['miktar'],
                $detay['birim_fiyat'],
                $detay['toplam']
            );
            $stmt_detay->execute();
        }
    }

    $db->commit();
    json_success('Teklif başarıyla oluşturuldu', ['id' => $teklif_id]);

} catch (Exception $e) {
    $db->rollback();
    json_error('Teklif oluşturulurken hata: ' . $e->getMessage(), 500);
}
?>
