<?php
require_once '../../config.php';
require_once '../../includes/auth.php';

// Basit indirme sistemi
$id = $_GET['id'] ?? 0;

if (!$id) {
    die('Geçersiz teklif ID');
}

// Teklif bilgilerini al
$query = "SELECT 
            t.*,
            c.unvan as cari_unvan,
            c.telefon as cari_telefon,
            c.email as cari_email,
            c.adres as cari_adres,
            f.firma_adi,
            f.telefon as firma_telefon,
            f.email as firma_email,
            f.adres as firma_adres
          FROM teklifler t
          LEFT JOIN cariler c ON t.cari_id = c.id
          LEFT JOIN firmalar f ON t.firma_id = f.id
          WHERE t.id = ? AND t.firma_id = ?";

$stmt = $db->prepare($query);
$firma_id = get_firma_id();
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Teklif bulunamadı');
}

$teklif = $result->fetch_assoc();

// Detayları al
$detay_query = "SELECT td.*, u.urun_adi 
                FROM teklif_detaylari td 
                LEFT JOIN urunler u ON td.urun_id = u.id 
                WHERE td.teklif_id = ?";
$detay_stmt = $db->prepare($detay_query);
$detay_stmt->bind_param("i", $id);
$detay_stmt->execute();
$detay_result = $detay_stmt->get_result();
$detaylar = [];
while ($row = $detay_result->fetch_assoc()) {
    $detaylar[] = $row;
}

// HTML içeriği oluştur
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teklif - ' . htmlspecialchars($teklif['teklif_no']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-info { margin-bottom: 20px; }
        .customer-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TEKLİF</h1>
        <h2>' . htmlspecialchars($teklif['teklif_basligi']) . '</h2>
    </div>
    
    <div class="company-info">
        <h3>Firma Bilgileri</h3>
        <p><strong>' . htmlspecialchars($teklif['firma_adi']) . '</strong></p>
        <p>Tel: ' . htmlspecialchars($teklif['firma_telefon']) . '</p>
        <p>Email: ' . htmlspecialchars($teklif['firma_email']) . '</p>
        <p>Adres: ' . htmlspecialchars($teklif['firma_adres']) . '</p>
    </div>
    
    <div class="customer-info">
        <h3>Teklif Verilen</h3>
        <p><strong>' . htmlspecialchars($teklif['cari_unvan']) . '</strong></p>
        <p>Tel: ' . htmlspecialchars($teklif['cari_telefon']) . '</p>
        <p>Email: ' . htmlspecialchars($teklif['cari_email']) . '</p>
        <p>Adres: ' . htmlspecialchars($teklif['cari_adres']) . '</p>
    </div>
    
    <div>
        <p><strong>Teklif Tarihi:</strong> ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '</p>
        <p><strong>Geçerlilik Tarihi:</strong> ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</p>
    </div>
    
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
                <td>' . htmlspecialchars($detay['urun_adi']) . '</td>
                <td>' . number_format($detay['miktar'], 2) . ' ' . htmlspecialchars($detay['birim']) . '</td>
                <td>' . number_format($detay['birim_fiyat'], 2) . ' ₺</td>
                <td>%' . number_format($detay['kdv_orani'], 2) . '</td>
                <td>' . number_format($detay['kdv_tutari'], 2) . ' ₺</td>
                <td>' . number_format($detay['toplam'], 2) . ' ₺</td>
              </tr>';
    $sira++;
}

$html .= '</tbody>
    </table>
    
    <div class="total">
        <p>Ara Toplam: ' . number_format($teklif['ara_toplam'], 2) . ' ₺</p>
        <p>KDV Toplam: ' . number_format($teklif['kdv_tutari'], 2) . ' ₺</p>
        <p><strong>GENEL TOPLAM: ' . number_format($teklif['genel_toplam'], 2) . ' ₺</strong></p>
    </div>
</body>
</html>';

// Dosya adı
$filename = 'teklif_' . $teklif['teklif_no'] . '_' . date('Y-m-d') . '.html';

// İndirme header'ları
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($html));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

echo $html;
exit;
?>
