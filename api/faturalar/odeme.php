<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'guncelleme');

$method = $_SERVER['REQUEST_METHOD'];
$firma_id = get_firma_id();

if ($method !== 'POST') {
    json_error('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Veri doğrulama
$required_fields = ['fatura_id', 'odeme_tarihi', 'odeme_tutari', 'odeme_yontemi'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        json_error("$field alanı gerekli", 400);
    }
}

$fatura_id = (int)$data['fatura_id'];
$odeme_tarihi = $data['odeme_tarihi'];
$odeme_tutari = (float)$data['odeme_tutari'];
$odeme_yontemi = $data['odeme_yontemi'];
$aciklama = $data['aciklama'] ?? '';

try {
    $db->begin_transaction();
    
    // Fatura bilgilerini kontrol et
    $stmt = $db->prepare("SELECT * FROM faturalar WHERE id = ? AND firma_id = ?");
    $stmt->bind_param("ii", $fatura_id, $firma_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$fatura = $result->fetch_assoc()) {
        throw new Exception('Fatura bulunamadı');
    }
    
    if ($fatura['odeme_durumu'] == 'odendi') {
        throw new Exception('Bu fatura zaten ödenmiştir');
    }
    
    // Ödeme kaydını ekle
    $stmt_odeme = $db->prepare("INSERT INTO fatura_odemeleri (fatura_id, odeme_tarihi, odeme_tutari, odeme_yontemi, aciklama, firma_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_odeme->bind_param("isdssi", $fatura_id, $odeme_tarihi, $odeme_tutari, $odeme_yontemi, $aciklama, $firma_id);
    $stmt_odeme->execute();
    
    // Faturayı ödendi olarak işaretle
    $stmt_update = $db->prepare("UPDATE faturalar SET odeme_durumu = 'odendi', odeme_tarihi = ? WHERE id = ? AND firma_id = ?");
    $stmt_update->bind_param("sii", $odeme_tarihi, $fatura_id, $firma_id);
    $stmt_update->execute();
    
    $db->commit();
    json_success('Ödeme başarıyla kaydedildi', ['odeme_id' => $db->insert_id]);
    
} catch (Exception $e) {
    $db->rollback();
    json_error('Ödeme kaydedilirken hata oluştu: ' . $e->getMessage(), 500);
}
?>
