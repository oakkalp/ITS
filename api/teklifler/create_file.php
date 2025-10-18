<?php
require_once '../../config.php';
require_once '../../includes/auth.php';

// Geçici dosya oluşturma sistemi
$id = $_GET['id'] ?? 0;
$download = $_GET['download'] ?? 0;

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

// Detayları al - önce teklif_detaylari tablosundan
$detay_query = "SELECT td.*, u.urun_adi 
                FROM teklif_detaylari td 
                LEFT JOIN urunler u ON td.urun_id = u.id 
                WHERE td.teklif_id = ?
                ORDER BY td.id";
$detay_stmt = $db->prepare($detay_query);
$detay_stmt->bind_param("i", $id);
$detay_stmt->execute();
$detay_result = $detay_stmt->get_result();
$detaylar = [];
while ($row = $detay_result->fetch_assoc()) {
    $detaylar[] = $row;
}

// Eğer teklif_detaylari tablosunda veri yoksa, detaylar JSON kolonundan al
if (empty($detaylar) && !empty($teklif['detaylar'])) {
    $detaylar_json = json_decode($teklif['detaylar'], true);
    if ($detaylar_json && is_array($detaylar_json)) {
        foreach ($detaylar_json as $detay) {
            $detaylar[] = [
                'urun_adi' => $detay['urun_adi'] ?? $detay['aciklama'] ?? '',
                'miktar' => $detay['miktar'] ?? 0,
                'birim' => $detay['birim'] ?? '',
                'birim_fiyat' => $detay['birim_fiyat'] ?? 0,
                'toplam' => $detay['toplam'] ?? 0,
                'kdv_orani' => $detay['kdv_orani'] ?? 18,
                'kdv_tutari' => $detay['kdv_tutari'] ?? 0
            ];
        }
    }
}

// HTML içeriği oluştur - preview.php'deki print-container tasarımı ile aynı
$html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teklif - ' . htmlspecialchars($teklif['teklif_no'] ?? '') . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 32px;
            color: #0d6efd;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 10px 0 5px 0;
            font-size: 20px;
            color: #333;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .company-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .company-info, .customer-info {
            width: 48%;
        }
        
        .company-info h3, .customer-info h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .company-info p, .customer-info p {
            margin: 5px 0;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .info-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-section p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 13px;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        
        .products-table .text-center {
            text-align: center;
        }
        
        .products-table .text-right {
            text-align: right;
        }
        
        .products-table .text-right-bold {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-section {
            margin-top: 30px;
        }
        
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 15px;
            border: none;
        }
        
        .totals-table .label {
            text-align: left;
            font-weight: bold;
        }
        
        .totals-table .amount {
            text-align: right;
        }
        
        .totals-table .final-row {
            background-color: #e3f2fd;
            font-size: 16px;
            font-weight: bold;
        }
        
        .description-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .description-section h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        
        .description-section p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        @media print {
            * {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: white !important;
            }
            
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 12px !important;
            }
            
            .print-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 20px !important;
                margin: 0 !important;
                max-width: none !important;
                width: 100% !important;
            }
            
            .header {
                border-bottom: 2px solid #000 !important;
                margin-bottom: 20px !important;
            }
            
            .header h1 {
                color: #000 !important;
                font-size: 24px !important;
            }
            
            .header h2 {
                color: #000 !important;
                font-size: 16px !important;
            }
            
            .company-section {
                margin-bottom: 20px !important;
            }
            
            .info-section {
                margin-bottom: 20px !important;
                background: #f8f8f8 !important;
            }
            
            .products-table {
                margin-bottom: 20px !important;
                font-size: 11px !important;
            }
            
            .products-table th,
            .products-table td {
                border: 1px solid #000 !important;
                padding: 4px !important;
            }
            
            .products-table th {
                background: #f0f0f0 !important;
                font-weight: bold !important;
            }
            
            .totals-section {
                margin-top: 20px !important;
            }
            
            .totals-table .final-row {
                background: #e0e0e0 !important;
            }
            
            .description-section {
                margin-top: 20px !important;
                background: #f8f8f8 !important;
            }
            
            /* Tüm menü ve navigasyon elementlerini gizle */
            nav, .navbar, .sidebar, .menu, .navigation, 
            .btn, .button, .action-buttons, .header-nav,
            .main-nav, .top-nav, .left-nav, .right-nav {
                display: none !important;
            }
            
            /* Sayfa kırılma kontrolü */
            .company-section, .info-section, .products-table, 
            .totals-section, .description-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Başlık -->
    <div class="header">
        <h1>TEKLİF</h1>
            <h2>' . htmlspecialchars($teklif['teklif_basligi'] ?? '') . '</h2>
            <p class="subtitle">Geçerlilik Tarihi: ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'] ?? date('Y-m-d'))) . '</p>
    </div>
    
        <!-- Firma ve Müşteri Bilgileri -->
        <div class="company-section">
    <div class="company-info">
                <h3>' . htmlspecialchars($teklif['firma_adi'] ?? '') . '</h3>
                <p><strong>Tel:</strong> ' . htmlspecialchars($teklif['firma_telefon'] ?? '') . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($teklif['firma_email'] ?? '') . '</p>
                <p><strong>Adres:</strong> ' . htmlspecialchars($teklif['firma_adres'] ?? '') . '</p>';
                
if (!empty($teklif['firma_vergi_dairesi'])) {
    $html .= '<p><strong>Vergi Dairesi:</strong> ' . htmlspecialchars($teklif['firma_vergi_dairesi']) . '</p>';
}
if (!empty($teklif['firma_vergi_no'])) {
    $html .= '<p><strong>Vergi No:</strong> ' . htmlspecialchars($teklif['firma_vergi_no']) . '</p>';
}

$html .= '</div>
    
    <div class="customer-info">
        <h3>Teklif Verilen</h3>
                <p><strong>' . htmlspecialchars($teklif['cari_unvan'] ?? $teklif['cari_disi_kisi'] ?? '') . '</strong></p>
                <p><strong>Tel:</strong> ' . htmlspecialchars($teklif['cari_telefon'] ?? $teklif['cari_disi_telefon'] ?? '') . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($teklif['cari_email'] ?? $teklif['cari_disi_email'] ?? '') . '</p>
                <p><strong>Adres:</strong> ' . htmlspecialchars($teklif['cari_adres'] ?? $teklif['cari_disi_adres'] ?? '') . '</p>';
                
if (!empty($teklif['cari_vergi_dairesi'])) {
    $html .= '<p><strong>Vergi Dairesi:</strong> ' . htmlspecialchars($teklif['cari_vergi_dairesi']) . '</p>';
}
if (!empty($teklif['cari_vergi_no'])) {
    $html .= '<p><strong>Vergi No:</strong> ' . htmlspecialchars($teklif['cari_vergi_no']) . '</p>';
}

$html .= '</div>
    </div>
    
        <!-- Teklif Bilgileri -->
        <div class="info-section">
            <p><strong>Teklif No:</strong> ' . htmlspecialchars($teklif['teklif_no'] ?? '') . '</p>
            <p><strong>Teklif Tarihi:</strong> ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'] ?? date('Y-m-d'))) . '</p>
            <p><strong>Geçerlilik Tarihi:</strong> ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'] ?? date('Y-m-d'))) . '</p>
    </div>
    
        <!-- Ürünler Tablosu -->
        <table class="products-table">
        <thead>
            <tr>
                    <th style="width: 5%;">Sıra</th>
                    <th style="width: 35%;">Ürün/Hizmet</th>
                    <th style="width: 10%;">Miktar</th>
                    <th style="width: 12%;">Birim Fiyat</th>
                    <th style="width: 8%;">KDV %</th>
                    <th style="width: 12%;">KDV Tutarı</th>
                    <th style="width: 18%;">Toplam</th>
            </tr>
        </thead>
        <tbody>';

$sira = 1;
foreach ($detaylar as $detay) {
    $urun_adi = $detay['urun_adi'] ?? $detay['aciklama'] ?? '';
    $html .= '<tr>
                    <td class="text-center">' . $sira . '</td>
                    <td>' . htmlspecialchars($urun_adi) . '</td>
                    <td class="text-center">' . number_format($detay['miktar'] ?? 0, 2, ',', '.') . ' ' . htmlspecialchars($detay['birim'] ?? 'adet') . '</td>
                    <td class="text-right">' . number_format($detay['birim_fiyat'] ?? 0, 2, ',', '.') . ' ₺</td>
                    <td class="text-center">%' . number_format($detay['kdv_orani'] ?? 0, 0) . '</td>
                    <td class="text-right">' . number_format($detay['kdv_tutari'] ?? 0, 2, ',', '.') . ' ₺</td>
                    <td class="text-right-bold">' . number_format($detay['toplam'] ?? 0, 2, ',', '.') . ' ₺</td>
              </tr>';
    $sira++;
}

$html .= '</tbody>
    </table>
    
        <!-- Toplam Bilgileri -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Ara Toplam:</td>
                    <td class="amount">' . number_format($teklif['ara_toplam'] ?? 0, 2, ',', '.') . ' ₺</td>
                </tr>
                <tr>
                    <td class="label">KDV Toplam:</td>
                    <td class="amount">' . number_format($teklif['kdv_tutari'] ?? 0, 2, ',', '.') . ' ₺</td>
                </tr>
                <tr class="final-row">
                    <td class="label">GENEL TOPLAM:</td>
                    <td class="amount">' . number_format($teklif['genel_toplam'] ?? 0, 2, ',', '.') . ' ₺</td>
                </tr>
            </table>
        </div>';
        
// Açıklama bölümü
if (!empty($teklif['aciklama'])) {
    $html .= '<div class="description-section">
            <h4>Açıklama:</h4>
            <p>' . nl2br(htmlspecialchars($teklif['aciklama'])) . '</p>
        </div>';
}

$html .= '</div>
</body>
</html>';

// Dosya adı
$filename = 'teklif_' . $teklif['teklif_no'] . '_' . date('Y-m-d_H-i-s') . '.html';

// Geçici dosya oluştur
$temp_dir = '../../temp/';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

$file_path = $temp_dir . $filename;
file_put_contents($file_path, $html);

// Eğer download=1 ise doğrudan dosyayı indir
if ($download == 1) {
    // İndirme header'ları
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Dosyayı gönder
    readfile($file_path);
    
    // Dosyayı sil
    unlink($file_path);
    exit;
}

// Dosya linkini döndür
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'download_url' => 'temp/' . $filename,
    'filename' => $filename,
    'message' => 'Dosya başarıyla oluşturuldu'
]);
?>
