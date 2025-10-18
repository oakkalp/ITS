<?php
require_once '../../config.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'okuma');

$firma_id = get_firma_id();
$id = $_GET['id'];

// Teklif bilgilerini al
$query = "SELECT 
            t.*,
            c.unvan as cari_unvan,
            c.vergi_dairesi as cari_vergi_dairesi,
            c.vergi_no as cari_vergi_no,
            c.telefon as cari_telefon,
            c.email as cari_email,
            c.adres as cari_adres,
            f.firma_adi,
            f.vergi_dairesi as firma_vergi_dairesi,
            f.vergi_no as firma_vergi_no,
            f.telefon as firma_telefon,
            f.email as firma_email,
            f.adres as firma_adres
          FROM teklifler t
          LEFT JOIN cariler c ON t.cari_id = c.id
          LEFT JOIN firmalar f ON t.firma_id = f.id
          WHERE t.id = ? AND t.firma_id = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Teklif bulunamadı');
}

$teklif = $result->fetch_assoc();

// Teklif detaylarını al
$detail_query = "SELECT 
                   td.*,
                   u.urun_adi,
                   u.birim
                 FROM teklif_detaylari td
                 LEFT JOIN urunler u ON td.urun_id = u.id
                 WHERE td.teklif_id = ?
                 ORDER BY td.id";

$detail_stmt = $db->prepare($detail_query);
$detail_stmt->bind_param("i", $id);
$detail_stmt->execute();
$detail_result = $detail_stmt->get_result();

$detaylar = [];
while ($row = $detail_result->fetch_assoc()) {
    $detaylar[] = $row;
}

// HTML içeriği oluştur
$html = generateTeklifHTML($teklif, $detaylar);

// Basit PDF oluştur (HTML formatında)
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="Teklif_' . $teklif['teklif_no'] . '.html"');

echo $html;

function generateTeklifHTML($teklif, $detaylar) {
    $html = '
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-info { margin-bottom: 20px; }
        .client-info { margin-bottom: 20px; }
        .teklif-info { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; }
    </style>
    
    <div class="header">
        <h1>TEKLİF</h1>
        <h2>' . htmlspecialchars($teklif['firma_adi']) . '</h2>
    </div>
    
    <div class="company-info">
        <strong>Firma Bilgileri:</strong><br>
        ' . htmlspecialchars($teklif['firma_adres']) . '<br>
        Tel: ' . htmlspecialchars($teklif['firma_telefon']) . ' | Email: ' . htmlspecialchars($teklif['firma_email']) . '<br>
        Vergi Dairesi: ' . htmlspecialchars($teklif['firma_vergi_dairesi']) . ' | Vergi No: ' . htmlspecialchars($teklif['firma_vergi_no']) . '
    </div>
    
    <div class="client-info">
        <strong>Teklif Verilen:</strong><br>';
    
    if ($teklif['cari_id']) {
        $html .= '
        ' . htmlspecialchars($teklif['cari_unvan']) . '<br>
        ' . htmlspecialchars($teklif['cari_adres']) . '<br>
        Tel: ' . htmlspecialchars($teklif['cari_telefon']) . ' | Email: ' . htmlspecialchars($teklif['cari_email']) . '<br>
        Vergi Dairesi: ' . htmlspecialchars($teklif['cari_vergi_dairesi']) . ' | Vergi No: ' . htmlspecialchars($teklif['cari_vergi_no']);
    } else {
        $html .= '
        ' . htmlspecialchars($teklif['cari_disi_kisi']) . '<br>
        ' . htmlspecialchars($teklif['cari_disi_adres']) . '<br>
        Tel: ' . htmlspecialchars($teklif['cari_disi_telefon']) . ' | Email: ' . htmlspecialchars($teklif['cari_disi_email']);
    }
    
    $html .= '
    </div>
    
    <div class="teklif-info">
        <table class="table">
            <tr>
                <td><strong>Teklif No:</strong></td>
                <td>' . htmlspecialchars($teklif['teklif_no']) . '</td>
                <td><strong>Teklif Tarihi:</strong></td>
                <td>' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '</td>
            </tr>
            <tr>
                <td><strong>Geçerlilik Tarihi:</strong></td>
                <td>' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</td>
                <td><strong>Durum:</strong></td>
                <td>' . ucfirst($teklif['durum']) . '</td>
            </tr>
        </table>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Ürün</th>
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
        $html .= '
            <tr>
                <td class="text-center">' . $sira . '</td>
                <td>' . htmlspecialchars($detay['urun_adi']) . '</td>
                <td class="text-right">' . number_format($detay['miktar'], 3, ',', '.') . ' ' . htmlspecialchars($detay['birim']) . '</td>
                <td class="text-right">' . number_format($detay['birim_fiyat'], 2, ',', '.') . ' ₺</td>
                <td class="text-center">%' . $detay['kdv_orani'] . '</td>
                <td class="text-right">' . number_format($detay['kdv_tutari'], 2, ',', '.') . ' ₺</td>
                <td class="text-right">' . number_format($detay['toplam'], 2, ',', '.') . ' ₺</td>
            </tr>';
        $sira++;
    }
    
    $html .= '
        </tbody>
    </table>
    
    <table class="table">
        <tr class="total-row">
            <td colspan="5" class="text-right"><strong>Ara Toplam:</strong></td>
            <td class="text-right">' . number_format($teklif['ara_toplam'], 2, ',', '.') . ' ₺</td>
        </tr>
        <tr class="total-row">
            <td colspan="5" class="text-right"><strong>KDV Toplam:</strong></td>
            <td class="text-right">' . number_format($teklif['kdv_tutari'], 2, ',', '.') . ' ₺</td>
        </tr>
        <tr class="total-row">
            <td colspan="5" class="text-right"><strong>Genel Toplam:</strong></td>
            <td class="text-right">' . number_format($teklif['genel_toplam'], 2, ',', '.') . ' ₺</td>
        </tr>
    </table>';
    
    if (!empty($teklif['aciklama'])) {
        $html .= '
        <div style="margin-top: 20px;">
            <strong>Açıklama:</strong><br>
            ' . nl2br(htmlspecialchars($teklif['aciklama'])) . '
        </div>';
    }
    
    return $html;
}
?>
