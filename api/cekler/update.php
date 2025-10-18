<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('cekler', 'yazma');

$data = json_decode(file_get_contents('php://input'), true);
$firma_id = get_firma_id();

if (!$data || !isset($data['id'])) {
    json_error('Geçersiz veri', 400);
}

$cek_id = $data['id'];

// Çekin sahibi kontrolü
$stmt_check = $db->prepare("SELECT id FROM cekler WHERE id = ? AND firma_id = ?");
$stmt_check->bind_param("ii", $cek_id, $firma_id);
$stmt_check->execute();
if (!$stmt_check->get_result()->fetch_assoc()) {
    json_error('Çek bulunamadı veya yetkiniz yok', 404);
}

// Debug log
error_log("Çek Update - Gelen veri: " . json_encode($data));
error_log("Çek Update - Firma ID: $firma_id, Çek ID: $cek_id");
error_log("Çek Update - Parametre sayısı: 11, Tip tanımı: issssdssssi");
error_log("Çek Update - Cari ID: " . ($cari_id ?? 'NULL') . " (Orijinal: " . ($data['cari_id'] ?? 'YOK') . ")");

// Değişkenleri önce tanımla
$cari_id = !empty($data['cari_id']) ? intval($data['cari_id']) : null;
$cari_disi_kisi = $data['cari_disi_kisi'] ?? null;
$cek_no = $data['cek_no'];
$banka_adi = $data['banka_adi'];
$sube = $data['sube'] ?? null;
$tutar = $data['tutar'];
$vade_tarihi = $data['vade_tarihi'];
$cek_kaynagi = $data['cek_kaynagi'] ?? null;
$durum = $data['durum'] ?? 'beklemede';
$aciklama = $data['aciklama'] ?? null;

$stmt = $db->prepare("UPDATE cekler SET cari_id = ?, cari_disi_kisi = ?, cek_no = ?, banka_adi = ?, sube = ?, tutar = ?, vade_tarihi = ?, cek_kaynagi = ?, durum = ?, aciklama = ? WHERE id = ?");
$stmt->bind_param("issssdssssi", 
    $cari_id,
    $cari_disi_kisi,
    $cek_no,
    $banka_adi,
    $sube,
    $tutar,
    $vade_tarihi,
    $cek_kaynagi,
    $durum,
    $aciklama,
    $cek_id
);

if ($stmt->execute()) {
    json_success('Çek başarıyla güncellendi');
} else {
    json_error('Çek güncellenirken hata oluştu', 500);
}
?>