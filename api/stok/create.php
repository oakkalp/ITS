<?php
// Output buffering başlat
ob_start();

require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'yazma');

// Buffer'ı temizle
ob_clean();

// Debug için
error_log("Stok create çağrıldı");

$data = json_decode(file_get_contents('php://input'), true);

// JSON decode hatası kontrolü
if (json_last_error() !== JSON_ERROR_NONE) {
    json_error('JSON parse hatası: ' . json_last_error_msg(), 400);
}

if (!$data) {
    json_error('Veri gönderilmedi', 400);
}

error_log("Gelen data: " . print_r($data, true));

$firma_id = get_firma_id();

// Zorunlu alan kontrolü
if (empty($data['urun_adi'])) {
    json_error('Ürün adı zorunludur', 400);
}

if (empty($data['birim'])) {
    json_error('Birim zorunludur', 400);
}

// Null değerleri kontrol et
$urun_kodu = $data['urun_kodu'] ?? null;
$kategori = $data['kategori'] ?? null;
$stok = isset($data['stok_miktari']) ? floatval($data['stok_miktari']) : 0;
$alis_fiyati = isset($data['alis_fiyati']) ? floatval($data['alis_fiyati']) : 0;
$satis_fiyati = isset($data['satis_fiyati']) ? floatval($data['satis_fiyati']) : 0;
$barkod = $data['barkod'] ?? null;
$aciklama = $data['aciklama'] ?? null;
$aktif = isset($data['aktif']) ? intval($data['aktif']) : 1;

// Debug log
error_log("Create parametreleri - Firma ID: $firma_id, Ürün Adı: " . ($data['urun_adi'] ?? 'YOK'));
error_log("Stok miktarı: " . ($data['stok_miktari'] ?? 'YOK'));
error_log("Alış fiyatı: " . ($data['alis_fiyati'] ?? 'YOK'));
error_log("Satış fiyatı: " . ($data['satis_fiyati'] ?? 'YOK'));
error_log("Ürün kodu: " . ($data['urun_kodu'] ?? 'YOK'));

// Önce tabloda barkod ve aciklama sütunları var mı kontrol et
$result = $db->query("SHOW COLUMNS FROM urunler LIKE 'barkod'");
$hasBarkod = $result->num_rows > 0;

$result = $db->query("SHOW COLUMNS FROM urunler LIKE 'aciklama'");
$hasAciklama = $result->num_rows > 0;

// Değerleri değişkenlere ata (bind_param'dan önce)
$urun_adi = $data['urun_adi'];
$birim = $data['birim'];

error_log("Değişkenler tanımlandı - Ürün Adı: '$urun_adi', Birim: '$birim'");

// SQL sorgusunu dinamik olarak oluştur
$columns = "firma_id, urun_kodu, urun_adi, kategori, birim, stok_miktari, alis_fiyati, satis_fiyati, aktif";
$values = "?, ?, ?, ?, ?, ?, ?, ?, ?";
$bindTypes = "issssdddi";
$bindParams = [$firma_id, $urun_kodu, $urun_adi, $kategori, $birim, $stok, $alis_fiyati, $satis_fiyati, $aktif];

if ($hasBarkod) {
    $columns .= ", barkod";
    $values .= ", ?";
    $bindTypes .= "s";
    $bindParams[] = $barkod;
}

if ($hasAciklama) {
    $columns .= ", aciklama";
    $values .= ", ?";
    $bindTypes .= "s";
    $bindParams[] = $aciklama;
}

$sql = "INSERT INTO urunler ($columns) VALUES ($values)";
error_log("Create SQL: $sql");
error_log("Bind types: $bindTypes");

$stmt = $db->prepare($sql);

if (!$stmt) {
    error_log("Create prepare hatası: " . $db->error);
    json_error('SQL prepare hatası: ' . $db->error, 500);
}

// bind_param'ı dinamik olarak çağır
error_log("Bind parametreleri: " . print_r($bindParams, true));
error_log("Bind types: $bindTypes");
$stmt->bind_param($bindTypes, ...$bindParams);

if ($stmt->execute()) {
    json_success('Ürün başarıyla eklendi', ['id' => $db->insert_id], 201);
} else {
    error_log("Create execute hatası: " . $stmt->error);
    json_error('Ürün eklenirken hata oluştu: ' . $stmt->error, 500);
}
?>

