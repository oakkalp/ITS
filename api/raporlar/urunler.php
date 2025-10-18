<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');

$firma_id = get_firma_id();
$baslangic = $_GET['baslangic'] ?? date('Y-m-01');
$bitis = $_GET['bitis'] ?? date('Y-m-d');

$query = "SELECT 
    u.urun_adi,
    u.stok_miktari,
    SUM(fd.miktar) as toplam_miktar,
    SUM(fd.toplam) as toplam_tutar
FROM fatura_detaylari fd
INNER JOIN urunler u ON fd.urun_id = u.id
INNER JOIN faturalar f ON fd.fatura_id = f.id
WHERE f.firma_id = ? AND f.fatura_tipi = 'satis' AND f.fatura_tarihi BETWEEN ? AND ?
GROUP BY u.id
ORDER BY toplam_tutar DESC
LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
$stmt->execute();
$result = $stmt->get_result();

$urunler = [];
while ($row = $result->fetch_assoc()) {
    $urunler[] = $row;
}

json_success('Ürün raporu', $urunler);
?>

