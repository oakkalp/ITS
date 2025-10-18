<?php
require_once '../../config.php';
require_once '../../includes/auth.php';

// Eğer download parametresi varsa, geçici dosya sistemi kullan
if (isset($_GET['download'])) {
    // Geçici dosya oluştur ve indirme linkini döndür
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
              WHERE t.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id);
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
    $html = generateHTMLPDF($teklif, $detaylar);
    
    // Dosya adı
    $filename = 'teklif_' . $teklif['teklif_no'] . '_' . date('Y-m-d_H-i-s') . '.html';
    
    // Geçici dosya oluştur
    $temp_dir = '../../temp/';
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    $file_path = $temp_dir . $filename;
    file_put_contents($file_path, $html);
    
    // İndirme linkini döndür
    echo json_encode([
        'success' => true,
        'download_url' => 'temp/download.php?file=' . $filename,
        'filename' => $filename
    ]);
    exit;
}

// Normal PDF görüntüleme için login gerekli
require_login();
require_permission('teklifler', 'okuma');

$id = $_GET['id'];
$firma_id = get_firma_id();

// Debug için parametreleri kontrol et
if (isset($_GET['debug'])) {
    echo "<h3>Debug: Parametreler</h3>";
    echo "<p>ID: $id</p>";
    echo "<p>Kullanıcı ID: " . get_user_id() . "</p>";
    echo "<p>Kullanıcı Rol: " . get_user_role() . "</p>";
}

// Teklif bilgilerini al - BASIT SORGU
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
$stmt->bind_param("ii", $id, $firma_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug için SQL sorgusunu kontrol et
if (isset($_GET['debug'])) {
    echo "<h3>Debug: SQL Sorgusu</h3>";
    echo "<p>Sorgu: " . htmlspecialchars($query) . "</p>";
    echo "<p>Parametreler: ID=$id, Firma ID=$firma_id</p>";
    echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
    echo "<p>Sonuç sayısı: " . $result->num_rows . "</p>";
    
    if ($result->num_rows === 0) {
        // Tüm teklifleri listele
        $all_query = "SELECT id, firma_id, teklif_no, teklif_basligi FROM teklifler ORDER BY id DESC LIMIT 10";
        $all_result = $db->query($all_query);
        echo "<h4>Tüm teklifler:</h4>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Firma ID</th><th>Teklif No</th><th>Teklif Başlığı</th></tr>";
        while ($row = $all_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['firma_id'] . "</td>";
            echo "<td>" . $row['teklif_no'] . "</td>";
            echo "<td>" . $row['teklif_basligi'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

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

// Basit metin tabanlı PDF oluştur
$text_content = generateTextContent($teklif, $detaylar);

// Debug için içeriği kontrol et
if (empty($text_content)) {
    die('Metin içeriği boş!');
}

// Debug için içerik uzunluğunu kontrol et
if (strlen($text_content) < 50) {
    die('Metin çok kısa! Uzunluk: ' . strlen($text_content) . '<br>İçerik: ' . htmlspecialchars($text_content));
}

// Debug için teklif verilerini kontrol et
if (empty($teklif['teklif_basligi'])) {
    die('Teklif başlığı boş! Teklif ID: ' . $id);
}

// Debug için detayları kontrol et
if (empty($detaylar)) {
    die('Teklif detayları boş! Teklif ID: ' . $id);
}

// YENİ İNDİRME SİSTEMİ
if (isset($_GET['download'])) {
    // HTML içeriği oluştur
    $html_content = generateHTMLPDF($teklif, $detaylar);
    
    // Dosya adını oluştur
    $filename = 'teklif_' . date('Y-m-d_H-i-s') . '.html';
    
    // İndirme için gerekli header'ları gönder
    ob_clean(); // Önceki çıktıları temizle
    
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . strlen($html_content));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // HTML içeriğini gönder
    echo $html_content;
    exit;
}

// Debug için içerik önizlemesi
if (isset($_GET['debug'])) {
    echo "<h3>Debug: Metin İçeriği</h3>";
    echo "<pre>" . htmlspecialchars($text_content) . "</pre>";
    echo "<h3>Debug: Teklif Verileri</h3>";
    echo "<pre>" . print_r($teklif, true) . "</pre>";
    echo "<h3>Debug: Detaylar</h3>";
    echo "<pre>" . print_r($detaylar, true) . "</pre>";
    
    // HTML PDF test
    echo "<h3>Debug: HTML PDF Test</h3>";
    $html_content = generateHTMLPDF($teklif, $detaylar);
    echo "<h4>HTML İçeriği:</h4>";
    echo "<pre style='max-height: 200px; overflow: auto;'>" . htmlspecialchars(substr($html_content, 0, 1000)) . "...</pre>";
    
    // PDF test
    echo "<h3>Debug: PDF Test</h3>";
    
    // HTML PDF önizleme
    echo "<h4>HTML PDF Önizleme:</h4>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 400px; overflow: auto;'>";
    echo $html_content;
    echo "</div>";
    
    // HTML indirme linki
    echo "<h4>HTML İndirme:</h4>";
    echo "<p><a href='?id=" . $id . "&download=1' class='btn btn-primary'>Teklif İndir (HTML)</a></p>";
    echo "<p><small>Bu link HTML dosyasını indirecek. Baskı önizlemesindeki tasarımla birebir aynıdır.</small></p>";
    
    // HTML tasarımı hakkında bilgi
    echo "<h4>HTML Tasarımı:</h4>";
    echo "<p>Bu HTML, baskı önizlemesindeki tasarımla birebir aynıdır:</p>";
    echo "<ul>";
    echo "<li>A4 sayfa boyutu</li>";
    echo "<li>Tablo düzeni</li>";
    echo "<li>Font boyutları</li>";
    echo "<li>Renkler</li>";
    echo "<li>Margin ve padding değerleri</li>";
    echo "</ul>";
    echo "<p><strong>Not:</strong> HTML dosyasını indirdikten sonra tarayıcıda açıp 'Yazdır' → 'PDF olarak kaydet' ile PDF'e çevirebilirsiniz.</p>";
    
    $pdf_content = createSimpleTextPDF($text_content);
    echo "<p>PDF uzunluğu: " . strlen($pdf_content) . " karakter</p>";
    echo "<p>PDF başlangıcı: " . htmlspecialchars(substr($pdf_content, 0, 100)) . "</p>";
    echo "<p>PDF sonu: " . htmlspecialchars(substr($pdf_content, -100)) . "</p>";
    
    // Content stream'i kontrol et
    $stream_start = strpos($pdf_content, "stream\n");
    $stream_end = strpos($pdf_content, "endstream");
    if ($stream_start !== false && $stream_end !== false) {
        $stream_content = substr($pdf_content, $stream_start + 7, $stream_end - $stream_start - 7);
        echo "<h4>Content Stream:</h4>";
        echo "<pre>" . htmlspecialchars($stream_content) . "</pre>";
    }
    
    // HTML PDF'i debug modunda göster
    echo "<h4>HTML PDF İndirme Test:</h4>";
    echo "<a href='?id=$id&download=1' target='_blank'>PDF İndir</a>";
    
    // HTML PDF önizleme
    echo "<h4>HTML PDF Önizleme:</h4>";
    $html_content = generateHTMLPDF($teklif, $detaylar);
    echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: white;'>";
    echo $html_content;
    echo "</div>";
    
    exit;
}

// PDF header'ları
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Teklif_' . $teklif['teklif_no'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// En basit PDF oluştur
echo createSimpleTextPDF($text_content);

function generateTextContent($teklif, $detaylar) {
    $content = "TEKLİF\n";
    $content .= "======\n\n";
    
    $content .= "Teklif Başlığı: " . ($teklif['teklif_basligi'] ?? 'Belirtilmemiş') . "\n";
    $content .= "Teklif No: " . ($teklif['teklif_no'] ?? 'Belirtilmemiş') . "\n";
    $content .= "Teklif Tarihi: " . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . "\n";
    $content .= "Geçerlilik Tarihi: " . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . "\n\n";
    
    $content .= "Firma: " . ($teklif['firma_adi'] ?? 'Belirtilmemiş') . "\n";
    $content .= "Adres: " . ($teklif['firma_adres'] ?? 'Belirtilmemiş') . "\n";
    $content .= "Tel: " . ($teklif['firma_telefon'] ?? 'Belirtilmemiş') . "\n";
    $content .= "Email: " . ($teklif['firma_email'] ?? 'Belirtilmemiş') . "\n\n";
    
    $content .= "Teklif Verilen:\n";
    if ($teklif['cari_id']) {
        $content .= "Cari: " . ($teklif['cari_unvan'] ?? 'Belirtilmemiş') . "\n";
        $content .= "Adres: " . ($teklif['cari_adres'] ?? 'Belirtilmemiş') . "\n";
        $content .= "Tel: " . ($teklif['cari_telefon'] ?? 'Belirtilmemiş') . "\n";
        $content .= "Email: " . ($teklif['cari_email'] ?? 'Belirtilmemiş') . "\n\n";
    } else {
        $content .= "Kişi: " . ($teklif['cari_disi_kisi'] ?? 'Belirtilmemiş') . "\n";
        $content .= "Adres: " . ($teklif['cari_disi_adres'] ?? 'Belirtilmemiş') . "\n";
        $content .= "Tel: " . ($teklif['cari_disi_telefon'] ?? 'Belirtilmemiş') . "\n";
        $content .= "Email: " . ($teklif['cari_disi_email'] ?? 'Belirtilmemiş') . "\n\n";
    }
    
    $content .= "ÜRÜNLER:\n";
    $content .= "========\n";
    
    if (empty($detaylar)) {
        $content .= "Ürün bulunamadı.\n\n";
    } else {
        $sira = 1;
        foreach ($detaylar as $detay) {
            $content .= $sira . ". " . ($detay['urun_adi'] ?? $detay['aciklama'] ?? 'Ürün') . "\n";
            $content .= "   Miktar: " . number_format($detay['miktar'], 2, ',', '.') . " " . ($detay['birim'] ?? 'adet') . "\n";
            $content .= "   Birim Fiyat: " . number_format($detay['birim_fiyat'], 2, ',', '.') . " ₺\n";
            $content .= "   KDV: %" . $detay['kdv_orani'] . "\n";
            $content .= "   Toplam: " . number_format($detay['toplam'], 2, ',', '.') . " ₺\n\n";
            $sira++;
        }
    }
    
    $content .= "TOPLAM:\n";
    $content .= "Ara Toplam: " . number_format($teklif['ara_toplam'], 2, ',', '.') . " ₺\n";
    $content .= "KDV Toplam: " . number_format($teklif['kdv_tutari'], 2, ',', '.') . " ₺\n";
    $content .= "GENEL TOPLAM: " . number_format($teklif['genel_toplam'], 2, ',', '.') . " ₺\n\n";
    
    if (!empty($teklif['aciklama'])) {
        $content .= "AÇIKLAMA:\n";
        $content .= $teklif['aciklama'] . "\n";
    }
    
    return $content;
}

function generateHTMLContent($teklif, $detaylar) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teklif ' . $teklif['teklif_no'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; color: #0d6efd; }
        .subtitle { font-size: 11px; color: #666; margin-top: 5px; }
        .company-info { margin-bottom: 20px; }
        .customer-info { margin-bottom: 20px; }
        .dates { text-align: right; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-table { width: 60%; margin-left: auto; }
        .total-row { font-weight: bold; background-color: #e3f2fd; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">' . htmlspecialchars($teklif['teklif_basligi']) . '</div>
        <div class="subtitle">Geçerlilik Tarihi: ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</div>
    </div>
    
    <div class="company-info">
        <strong>' . htmlspecialchars($teklif['firma_adi']) . '</strong><br>
        ' . htmlspecialchars($teklif['firma_adres']) . '<br>
        Tel: ' . htmlspecialchars($teklif['firma_telefon']) . ' | Email: ' . htmlspecialchars($teklif['firma_email']) . '<br>
        Vergi Dairesi: ' . htmlspecialchars($teklif['firma_vergi_dairesi']) . ' | Vergi No: ' . htmlspecialchars($teklif['firma_vergi_no']) . '
    </div>
    
    <div style="text-align: right; margin-bottom: 20px;">
        <strong>TEKLİF</strong><br>
        Teklif No: ' . htmlspecialchars($teklif['teklif_no']) . '<br>
        Tarih: ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '
    </div>
    
    <div class="customer-info">
        <strong>Teklif Verilen:</strong><br>';
    
    if ($teklif['cari_id']) {
        $html .= '<strong>' . htmlspecialchars($teklif['cari_unvan']) . '</strong><br>
        ' . htmlspecialchars($teklif['cari_adres']) . '<br>
        Tel: ' . htmlspecialchars($teklif['cari_telefon']) . ' | Email: ' . htmlspecialchars($teklif['cari_email']) . '<br>';
    } else {
        $html .= '<strong>' . htmlspecialchars($teklif['cari_disi_kisi']) . '</strong><br>
        ' . htmlspecialchars($teklif['cari_disi_adres']) . '<br>
        Tel: ' . htmlspecialchars($teklif['cari_disi_telefon']) . ' | Email: ' . htmlspecialchars($teklif['cari_disi_email']) . '<br>';
    }
    
    $html .= '</div>
    
    <div class="dates">
        <strong>Tarihler:</strong><br>
        Teklif Tarihi: ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '<br>
        Geçerlilik Tarihi: ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">Sıra</th>
                <th style="width: 30%;">Ürün/Hizmet</th>
                <th class="text-center" style="width: 10%;">Miktar</th>
                <th class="text-right" style="width: 12%;">Birim Fiyat</th>
                <th class="text-center" style="width: 6%;">KDV %</th>
                <th class="text-right" style="width: 10%;">KDV Tutarı</th>
                <th class="text-right" style="width: 10%;">Toplam</th>
                <th class="text-center" style="width: 8%;">Teklif Tarihi</th>
                <th class="text-center" style="width: 9%;">Geçerlilik Tarihi</th>
            </tr>
        </thead>
        <tbody>';
    
    $sira = 1;
    foreach ($detaylar as $detay) {
        $html .= '<tr>
            <td class="text-center">' . $sira . '</td>
            <td>' . htmlspecialchars($detay['urun_adi'] ?: $detay['aciklama']) . '</td>
            <td class="text-center">' . number_format($detay['miktar'], 2, ',', '.') . ' ' . htmlspecialchars($detay['birim'] ?: 'adet') . '</td>
            <td class="text-right">' . number_format($detay['birim_fiyat'], 2, ',', '.') . ' ₺</td>
            <td class="text-center">%' . $detay['kdv_orani'] . '</td>
            <td class="text-right">' . number_format($detay['kdv_tutari'], 2, ',', '.') . ' ₺</td>
            <td class="text-right"><strong>' . number_format($detay['toplam'], 2, ',', '.') . ' ₺</strong></td>
            <td class="text-center">' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '</td>
            <td class="text-center">' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</td>
        </tr>';
        $sira++;
    }
    
    $html .= '</tbody>
    </table>
    
    <table class="total-table">
        <tr>
            <td><strong>Ara Toplam:</strong></td>
            <td class="text-right">' . number_format($teklif['ara_toplam'], 2, ',', '.') . ' ₺</td>
        </tr>
        <tr>
            <td><strong>KDV Toplam:</strong></td>
            <td class="text-right">' . number_format($teklif['kdv_tutari'], 2, ',', '.') . ' ₺</td>
        </tr>
        <tr class="total-row">
            <td><strong>GENEL TOPLAM:</strong></td>
            <td class="text-right"><strong>' . number_format($teklif['genel_toplam'], 2, ',', '.') . ' ₺</strong></td>
        </tr>
    </table>';
    
    if (!empty($teklif['aciklama'])) {
        $html .= '<div style="margin-top: 20px;">
            <strong>Açıklama:</strong><br>
            ' . nl2br(htmlspecialchars($teklif['aciklama'])) . '
        </div>';
    }
    
    $html .= '</body>
</html>';
    
    return $html;
}

function convertHTMLToPDF($html) {
    // HTML'yi temizle ve PDF formatına çevir
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    
    // Türkçe karakterleri ASCII'ye çevir
    $html = str_replace(['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü'], 
                       ['c', 'C', 'g', 'G', 'i', 'I', 'o', 'O', 's', 'S', 'u', 'U'], $html);
    
    // Satır sonlarını normalize et
    $html = preg_replace('/\r\n|\r|\n/', "\n", $html);
    
    // Fazla boşlukları temizle
    $html = preg_replace('/\s+/', ' ', $html);
    
    // HTML'yi düzenli metne çevir - tablo yapısını koru
    $text = '';
    
    // Başlıkları çıkar
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $matches)) {
        $text .= strtoupper(trim(strip_tags($matches[1]))) . "\n\n";
    }
    if (preg_match('/<h2[^>]*>(.*?)<\/h2>/i', $html, $matches)) {
        $text .= strtoupper(trim(strip_tags($matches[1]))) . "\n\n";
    }
    
    // Firma bilgilerini çıkar
    if (preg_match('/<h3[^>]*>Firma Bilgileri<\/h3>.*?<div[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $firma_info = strip_tags($matches[1]);
        $firma_info = preg_replace('/\s+/', ' ', $firma_info);
        $firma_info = trim($firma_info);
        if (!empty($firma_info)) {
            $text .= "FIRMA BILGILERI:\n";
            $text .= $firma_info . "\n\n";
        }
    }
    
    // Müşteri bilgilerini çıkar
    if (preg_match('/<h3[^>]*>Tarihler<\/h3>.*?<div[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $musteri_info = strip_tags($matches[1]);
        $musteri_info = preg_replace('/\s+/', ' ', $musteri_info);
        $musteri_info = trim($musteri_info);
        if (!empty($musteri_info)) {
            $text .= "MUSTERI BILGILERI:\n";
            $text .= $musteri_info . "\n\n";
        }
    }
    
    // Tarihleri çıkar
    if (preg_match('/<div[^>]*class="text-end"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $tarihler = strip_tags($matches[1]);
        $tarihler = preg_replace('/\s+/', ' ', $tarihler);
        $tarihler = trim($tarihler);
        if (!empty($tarihler)) {
            $text .= "TARIHLER:\n";
            $text .= $tarihler . "\n\n";
        }
    }
    
    // Tablo verilerini çıkar
    if (preg_match('/<table[^>]*class="table"[^>]*>(.*?)<\/table>/is', $html, $table_match)) {
        $text .= "URUNLER:\n";
        
        // Tablo başlıklarını çıkar
        if (preg_match('/<thead[^>]*>(.*?)<\/thead>/is', $table_match[1], $thead_match)) {
            if (preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $thead_match[1], $headers)) {
                $header_text = '';
                foreach ($headers[1] as $header) {
                    $header_text .= trim(strip_tags($header)) . " | ";
                }
                $text .= rtrim($header_text, " | ") . "\n";
                $text .= str_repeat("-", strlen(rtrim($header_text, " | "))) . "\n";
            }
        }
        
        // Tablo satırlarını çıkar
        if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/is', $table_match[1], $tbody_match)) {
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tbody_match[1], $rows)) {
                foreach ($rows[1] as $row) {
                    if (preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $cells)) {
                        $row_text = '';
                        foreach ($cells[1] as $cell) {
                            $cell_text = strip_tags($cell);
                            $cell_text = preg_replace('/\s+/', ' ', $cell_text);
                            $cell_text = trim($cell_text);
                            $row_text .= $cell_text . " | ";
                        }
                        $text .= rtrim($row_text, " | ") . "\n";
                    }
                }
            }
        }
        $text .= "\n";
    }
    
    // Toplamları çıkar
    if (preg_match('/<div[^>]*class="totals"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $toplamlar = strip_tags($matches[1]);
        $toplamlar = preg_replace('/\s+/', ' ', $toplamlar);
        $toplamlar = trim($toplamlar);
        if (!empty($toplamlar)) {
            $text .= "TOPLAMLAR:\n";
            $text .= $toplamlar . "\n\n";
        }
    }
    
    // Açıklamayı çıkar
    if (preg_match('/<h3[^>]*>Aciklama<\/h3>.*?<p[^>]*>(.*?)<\/p>/is', $html, $matches)) {
        $aciklama = strip_tags($matches[1]);
        $aciklama = preg_replace('/\s+/', ' ', $aciklama);
        $aciklama = trim($aciklama);
        if (!empty($aciklama)) {
            $text .= "ACIKLAMA:\n";
            $text .= $aciklama . "\n\n";
        }
    }
    
    // Debug için metin uzunluğunu kontrol et
    if (strlen($text) < 10) {
        $text = "TEKLIF ICERIGI\n\nBu teklif icerigi goruntulenemiyor. Lutfen tekrar deneyin.";
    }
    
    // PDF içeriği oluştur
    $pdf_content = createSimpleTextPDF($text);
    
    return $pdf_content;
}

function convertHTMLToStyledPDF($html) {
    // HTML'yi temizle ve PDF formatına çevir - tasarımı koru
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    
    // Türkçe karakterleri ASCII'ye çevir
    $html = str_replace(['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü'], 
                       ['c', 'C', 'g', 'G', 'i', 'I', 'o', 'O', 's', 'S', 'u', 'U'], $html);
    
    // HTML'yi düzenli metne çevir - tasarımı koru
    $text = '';
    
    // Başlıkları çıkar
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $matches)) {
        $text .= strtoupper(trim(strip_tags($matches[1]))) . "\n\n";
    }
    if (preg_match('/<h2[^>]*>(.*?)<\/h2>/i', $html, $matches)) {
        $text .= strtoupper(trim(strip_tags($matches[1]))) . "\n\n";
    }
    
    // Firma bilgilerini çıkar
    if (preg_match('/<h3[^>]*>Firma Bilgileri<\/h3>.*?<div[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $firma_info = strip_tags($matches[1]);
        $firma_info = preg_replace('/\s+/', ' ', $firma_info);
        $firma_info = trim($firma_info);
        if (!empty($firma_info)) {
            $text .= "FIRMA BILGILERI:\n";
            $text .= $firma_info . "\n\n";
        }
    }
    
    // Müşteri bilgilerini çıkar
    if (preg_match('/<h3[^>]*>Tarihler<\/h3>.*?<div[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $musteri_info = strip_tags($matches[1]);
        $musteri_info = preg_replace('/\s+/', ' ', $musteri_info);
        $musteri_info = trim($musteri_info);
        if (!empty($musteri_info)) {
            $text .= "MUSTERI BILGILERI:\n";
            $text .= $musteri_info . "\n\n";
        }
    }
    
    // Tarihleri çıkar
    if (preg_match('/<div[^>]*class="text-end"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $tarihler = strip_tags($matches[1]);
        $tarihler = preg_replace('/\s+/', ' ', $tarihler);
        $tarihler = trim($tarihler);
        if (!empty($tarihler)) {
            $text .= "TARIHLER:\n";
            $text .= $tarihler . "\n\n";
        }
    }
    
    // Tablo verilerini çıkar - daha düzenli format
    if (preg_match('/<table[^>]*class="table"[^>]*>(.*?)<\/table>/is', $html, $table_match)) {
        $text .= "URUNLER:\n";
        
        // Tablo başlıklarını çıkar
        if (preg_match('/<thead[^>]*>(.*?)<\/thead>/is', $table_match[1], $thead_match)) {
            if (preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $thead_match[1], $headers)) {
                $header_text = '';
                foreach ($headers[1] as $header) {
                    $header_text .= trim(strip_tags($header)) . " | ";
                }
                $text .= rtrim($header_text, " | ") . "\n";
                $text .= str_repeat("-", strlen(rtrim($header_text, " | "))) . "\n";
            }
        }
        
        // Tablo satırlarını çıkar
        if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/is', $table_match[1], $tbody_match)) {
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tbody_match[1], $rows)) {
                foreach ($rows[1] as $row) {
                    if (preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $cells)) {
                        $row_text = '';
                        foreach ($cells[1] as $cell) {
                            $cell_text = strip_tags($cell);
                            $cell_text = preg_replace('/\s+/', ' ', $cell_text);
                            $cell_text = trim($cell_text);
                            $row_text .= $cell_text . " | ";
                        }
                        $text .= rtrim($row_text, " | ") . "\n";
                    }
                }
            }
        }
        $text .= "\n";
    }
    
    // Toplamları çıkar
    if (preg_match('/<div[^>]*class="totals"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $toplamlar = strip_tags($matches[1]);
        $toplamlar = preg_replace('/\s+/', ' ', $toplamlar);
        $toplamlar = trim($toplamlar);
        if (!empty($toplamlar)) {
            $text .= "TOPLAMLAR:\n";
            $text .= $toplamlar . "\n\n";
        }
    }
    
    // Açıklamayı çıkar
    if (preg_match('/<h3[^>]*>Aciklama<\/h3>.*?<p[^>]*>(.*?)<\/p>/is', $html, $matches)) {
        $aciklama = strip_tags($matches[1]);
        $aciklama = preg_replace('/\s+/', ' ', $aciklama);
        $aciklama = trim($aciklama);
        if (!empty($aciklama)) {
            $text .= "ACIKLAMA:\n";
            $text .= $aciklama . "\n\n";
        }
    }
    
    // Debug için metin uzunluğunu kontrol et
    if (strlen($text) < 10) {
        $text = "TEKLIF ICERIGI\n\nBu teklif icerigi goruntulenemiyor. Lutfen tekrar deneyin.";
    }
    
    // PDF içeriği oluştur - tasarımı koru
    $pdf_content = createStyledTextPDF($text);
    
    return $pdf_content;
}

function generatePDFContent($teklif, $detaylar) {
    $content = $teklif['teklif_basligi'] . "\n";
    $content .= "Geçerlilik Tarihi: " . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . "\n\n";
    
    $content .= "Firma: " . $teklif['firma_adi'] . "\n";
    $content .= "Adres: " . $teklif['firma_adres'] . "\n";
    $content .= "Tel: " . $teklif['firma_telefon'] . " | Email: " . $teklif['firma_email'] . "\n";
    $content .= "Vergi Dairesi: " . $teklif['firma_vergi_dairesi'] . " | Vergi No: " . $teklif['firma_vergi_no'] . "\n\n";
    
    $content .= "TEKLİF\n";
    $content .= "Teklif No: " . $teklif['teklif_no'] . "\n\n";
    
    $content .= "Teklif Verilen:\n";
    if ($teklif['cari_id']) {
        $content .= $teklif['cari_unvan'] . "\n";
        $content .= $teklif['cari_adres'] . "\n";
        $content .= "Tel: " . $teklif['cari_telefon'] . " | Email: " . $teklif['cari_email'] . "\n\n";
    } else {
        $content .= $teklif['cari_disi_kisi'] . "\n";
        $content .= $teklif['cari_disi_adres'] . "\n";
        $content .= "Tel: " . $teklif['cari_disi_telefon'] . " | Email: " . $teklif['cari_disi_email'] . "\n\n";
    }
    
    $content .= "Tarihler:\n";
    $content .= "Teklif Tarihi: " . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . "\n";
    $content .= "Geçerlilik Tarihi: " . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . "\n\n";
    
    $content .= "ÜRÜNLER:\n";
    $content .= "========\n";
    $content .= sprintf("%-3s %-25s %-8s %-10s %-4s %-10s %-10s %-8s %-10s\n", 
        "Sıra", "Ürün/Hizmet", "Miktar", "Birim Fiyat", "KDV%", "KDV Tutarı", "Toplam", "Teklif Tarihi", "Geçerlilik Tarihi");
    $content .= str_repeat("-", 120) . "\n";
    
    $sira = 1;
    foreach ($detaylar as $detay) {
        $urun_adi = $detay['urun_adi'] ?: $detay['aciklama'];
        $miktar = number_format($detay['miktar'], 2, ',', '.') . ' ' . ($detay['birim'] ?: 'adet');
        $birim_fiyat = number_format($detay['birim_fiyat'], 2, ',', '.') . ' ₺';
        $kdv_orani = '%' . $detay['kdv_orani'];
        $kdv_tutari = number_format($detay['kdv_tutari'], 2, ',', '.') . ' ₺';
        $toplam = number_format($detay['toplam'], 2, ',', '.') . ' ₺';
        $teklif_tarihi = date('d.m.Y', strtotime($teklif['teklif_tarihi']));
        $gecerlilik_tarihi = date('d.m.Y', strtotime($teklif['gecerlilik_tarihi']));
        
        $content .= sprintf("%-3d %-25s %-8s %-10s %-4s %-10s %-10s %-8s %-10s\n", 
            $sira, substr($urun_adi, 0, 25), $miktar, $birim_fiyat, $kdv_orani, $kdv_tutari, $toplam, $teklif_tarihi, $gecerlilik_tarihi);
        $sira++;
    }
    
    $content .= str_repeat("-", 120) . "\n";
    $content .= sprintf("%-70s %-12s\n", "Ara Toplam:", number_format($teklif['ara_toplam'], 2, ',', '.') . ' ₺');
    $content .= sprintf("%-70s %-12s\n", "KDV Toplam:", number_format($teklif['kdv_tutari'], 2, ',', '.') . ' ₺');
    $content .= sprintf("%-70s %-12s\n", "GENEL TOPLAM:", number_format($teklif['genel_toplam'], 2, ',', '.') . ' ₺');
    
    if (!empty($teklif['aciklama'])) {
        $content .= "\nAÇIKLAMA:\n";
        $content .= "=========\n";
        $content .= $teklif['aciklama'] . "\n";
    }
    
    return $content;
}

function createMinimalPDF($content) {
    // Türkçe karakterleri ASCII'ye çevir
    $content = str_replace(
        ['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü'],
        ['c', 'C', 'g', 'G', 'i', 'I', 'o', 'O', 's', 'S', 'u', 'U'],
        $content
    );
    
    // Çok basit PDF oluştur
    $lines = explode("\n", $content);
    
    // PDF başlığı
    $pdf = "%PDF-1.4\n";
    
    // Catalog objesi
    $pdf .= "1 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Catalog\n";
    $pdf .= "/Pages 2 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Pages objesi
    $pdf .= "2 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Pages\n";
    $pdf .= "/Kids [3 0 R]\n";
    $pdf .= "/Count 1\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Page objesi
    $pdf .= "3 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Page\n";
    $pdf .= "/Parent 2 0 R\n";
    $pdf .= "/MediaBox [0 0 612 792]\n";
    $pdf .= "/Contents 4 0 R\n";
    $pdf .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Content stream oluştur
    $stream = "BT\n";
    $stream .= "/F1 12 Tf\n";
    
    $y = 750;
    foreach ($lines as $line) {
        if ($y < 50) break;
        
        // Karakterleri escape et
        $line = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line);
        
        $stream .= "50 " . $y . " Td\n";
        $stream .= "(" . $line . ") Tj\n";
        $stream .= "0 -14 Td\n";
        $y -= 14;
    }
    
    $stream .= "ET\n";
    
    // Contents objesi
    $pdf .= "4 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Length " . strlen($stream) . "\n";
    $pdf .= ">>\n";
    $pdf .= "stream\n";
    $pdf .= $stream;
    $pdf .= "endstream\n";
    $pdf .= "endobj\n";
    
    // Font objesi
    $pdf .= "5 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // XRef tablosu - dinamik offset hesaplama
    $xref_pos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 6\n";
    
    // Offset'leri dinamik hesapla
    $obj1_pos = strpos($pdf, "1 0 obj");
    $obj2_pos = strpos($pdf, "2 0 obj");
    $obj3_pos = strpos($pdf, "3 0 obj");
    $obj4_pos = strpos($pdf, "4 0 obj");
    $obj5_pos = strpos($pdf, "5 0 obj");
    
    $pdf .= sprintf("%010d 65535 f \n", 0);
    $pdf .= sprintf("%010d 00000 n \n", $obj1_pos);
    $pdf .= sprintf("%010d 00000 n \n", $obj2_pos);
    $pdf .= sprintf("%010d 00000 n \n", $obj3_pos);
    $pdf .= sprintf("%010d 00000 n \n", $obj4_pos);
    $pdf .= sprintf("%010d 00000 n \n", $obj5_pos);
    
    // Trailer
    $pdf .= "trailer\n";
    $pdf .= "<<\n";
    $pdf .= "/Size 6\n";
    $pdf .= "/Root 1 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "startxref\n";
    $pdf .= $xref_pos . "\n";
    $pdf .= "%%EOF\n";
    
    return $pdf;
}

function createStyledTextPDF($content) {
    // Türkçe karakterleri ASCII'ye çevir
    $content = str_replace(
        ['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü'],
        ['c', 'C', 'g', 'G', 'i', 'I', 'o', 'O', 's', 'S', 'u', 'U'],
        $content
    );
    
    $lines = explode("\n", $content);
    $offsets = [];  // Her objenin pozisyonunu saklayacak dizi
    $pdf = '';
    
    // Header
    $pdf .= "%PDF-1.4\n";
    
    // Catalog (1 0 obj)
    $offsets[1] = strlen($pdf);
    $pdf .= "1 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Catalog\n";
    $pdf .= "/Pages 2 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Pages (2 0 obj)
    $offsets[2] = strlen($pdf);
    $pdf .= "2 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Pages\n";
    $pdf .= "/Kids [3 0 R]\n";
    $pdf .= "/Count 1\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Page (3 0 obj)
    $offsets[3] = strlen($pdf);
    $pdf .= "3 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Page\n";
    $pdf .= "/Parent 2 0 R\n";
    $pdf .= "/MediaBox [0 0 612 792]\n";
    $pdf .= "/Contents 4 0 R\n";
    $pdf .= "/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >>\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Content Stream oluştur - farklı font boyutları
    $stream = "BT\n";
    $stream .= "/F1 12 Tf\n";  // Normal font
    $stream .= "50 750 Td\n";
    $stream .= "16 TL\n";  // Satır arası
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            $stream .= "T*\n";  // Boş satır
            continue;
        }
        
        // Başlık kontrolü
        if (strpos($line, 'TEKLIF') === 0 || strpos($line, 'FIRMA') === 0 || 
            strpos($line, 'MUSTERI') === 0 || strpos($line, 'TARIHLER') === 0 ||
            strpos($line, 'URUNLER') === 0 || strpos($line, 'TOPLAMLAR') === 0 ||
            strpos($line, 'ACIKLAMA') === 0) {
            $stream .= "/F2 14 Tf\n";  // Büyük font
            $stream .= "(" . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line) . ") Tj\n";
            $stream .= "T*\n";
            $stream .= "/F1 12 Tf\n";  // Normal fonta dön
        } else if (strpos($line, '---') === 0) {
            // Çizgi
            $stream .= "(" . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line) . ") Tj\n";
            $stream .= "T*\n";
        } else {
            // Normal metin
            $stream .= "(" . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line) . ") Tj\n";
            $stream .= "T*\n";
        }
    }
    $stream .= "ET\n";
    
    // Contents (4 0 obj)
    $offsets[4] = strlen($pdf);
    $pdf .= "4 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Length " . strlen($stream) . "\n";
    $pdf .= ">>\n";
    $pdf .= "stream\n";
    $pdf .= $stream;
    $pdf .= "\nendstream\n";
    $pdf .= "endobj\n";
    
    // Font 1 - Normal (5 0 obj)
    $offsets[5] = strlen($pdf);
    $pdf .= "5 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Font 2 - Bold (6 0 obj)
    $offsets[6] = strlen($pdf);
    $pdf .= "6 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica-Bold\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // XRef - Dinamik offset değerleri
    $xref_pos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 7\n";
    $pdf .= "0000000000 65535 f \n";  // 0. obje her zaman böyle
    
    // Gerçek offset değerlerini kullan
    for ($i = 1; $i <= 6; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    
    // Trailer
    $pdf .= "trailer\n";
    $pdf .= "<<\n";
    $pdf .= "/Size 7\n";        // Toplam obje sayısı
    $pdf .= "/Root 1 0 R\n";    // Catalog objesi
    $pdf .= ">>\n";
    $pdf .= "startxref\n";
    $pdf .= $xref_pos . "\n";   // XRef tablosunun pozisyonu
    $pdf .= "%%EOF\n";          // Dosya sonu
    
    return $pdf;
}

function createSimpleTextPDF($content) {
    // Türkçe karakterleri ASCII'ye çevir
    $content = str_replace(
        ['ç', 'Ç', 'ğ', 'Ğ', 'ı', 'İ', 'ö', 'Ö', 'ş', 'Ş', 'ü', 'Ü'],
        ['c', 'C', 'g', 'G', 'i', 'I', 'o', 'O', 's', 'S', 'u', 'U'],
        $content
    );
    
    $lines = explode("\n", $content);
    $offsets = [];  // Her objenin pozisyonunu saklayacak dizi
    $pdf = '';
    
    // Header
    $pdf .= "%PDF-1.4\n";
    
    // Catalog (1 0 obj)
    $offsets[1] = strlen($pdf);
    $pdf .= "1 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Catalog\n";
    $pdf .= "/Pages 2 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Pages (2 0 obj)
    $offsets[2] = strlen($pdf);
    $pdf .= "2 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Pages\n";
    $pdf .= "/Kids [3 0 R]\n";
    $pdf .= "/Count 1\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Page (3 0 obj)
    $offsets[3] = strlen($pdf);
    $pdf .= "3 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Page\n";
    $pdf .= "/Parent 2 0 R\n";
    $pdf .= "/MediaBox [0 0 612 792]\n";
    $pdf .= "/Contents 4 0 R\n";
    $pdf .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Content Stream oluştur - Text Leading kullan
    $stream = "BT\n";
    $stream .= "/F1 10 Tf\n";
    $stream .= "50 750 Td\n";
    $stream .= "14 TL\n";  // Satır arası (Text Leading)
    
    foreach ($lines as $line) {
        // Karakter escape sırası önemli - önce backslash
        $line = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
        $stream .= "(" . $line . ") Tj\n";
        $stream .= "T*\n";  // Otomatik bir sonraki satıra geç
    }
    $stream .= "ET\n";
    
    // Contents (4 0 obj)
    $offsets[4] = strlen($pdf);
    $pdf .= "4 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Length " . strlen($stream) . "\n";
    $pdf .= ">>\n";
    $pdf .= "stream\n";
    $pdf .= $stream;
    $pdf .= "\nendstream\n";  // Başta newline önemli
    $pdf .= "endobj\n";
    
    // Font (5 0 obj)
    $offsets[5] = strlen($pdf);
    $pdf .= "5 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // XRef - Dinamik offset değerleri
    $xref_pos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 6\n";
    $pdf .= "0000000000 65535 f \n";  // 0. obje her zaman böyle
    
    // Gerçek offset değerlerini kullan
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    
    // Trailer
    $pdf .= "trailer\n";
    $pdf .= "<<\n";
    $pdf .= "/Size 6\n";        // Toplam obje sayısı
    $pdf .= "/Root 1 0 R\n";    // Catalog objesi
    $pdf .= ">>\n";
    $pdf .= "startxref\n";
    $pdf .= $xref_pos . "\n";   // XRef tablosunun pozisyonu
    $pdf .= "%%EOF\n";          // Dosya sonu
    
    return $pdf;
}


function generateHTMLPDF($teklif, $detaylar) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teklif - ' . htmlspecialchars($teklif['teklif_no']) . '</title>
    <style>
        /* A4 sayfa ayarları */
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15mm;
            background: white;
            color: black;
            font-size: 12px;
            width: 210mm;
            max-width: 210mm;
            box-sizing: border-box;
        }
        
        /* Başlık */
        h1 {
            font-size: 22px;
            margin-bottom: 8px;
            color: #0d6efd;
            text-align: center;
        }
        
        h2 {
            font-size: 20px;
            margin-bottom: 4px;
            color: #0d6efd;
            text-align: center;
        }
        
        h3 {
            font-size: 14px;
            margin-bottom: 2px;
            line-height: 1.1;
        }
        
        h4 {
            font-size: 13px;
            margin-bottom: 1px;
            line-height: 1.1;
        }
        
        p, div, span {
            color: black;
            margin: 0px 0;
            font-size: 11px;
            line-height: 1.1;
        }
        
        /* Firma ve müşteri bilgileri */
        .info-section {
            margin-bottom: 8px;
        }
        
        .info-row {
            margin-bottom: 2px;
        }
        
        /* Tarihler sağa yaslanmış */
        .text-end {
            text-align: right;
        }
        
        .text-end p {
            text-align: right;
            margin: 1px 0;
            font-size: 11px;
            color: #666;
        }
        
        /* Tablo ayarları */
        .table {
            border-collapse: collapse;
            width: 100%;
            max-width: 180mm;
            font-size: 12px;
            margin: 0;
            box-sizing: border-box;
            table-layout: fixed;
        }
        
        .table th,
        .table td {
            border: 1px solid #000;
            padding: 1px 6px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            line-height: 1.0;
            height: 18px;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 11px;
            height: 18px;
            line-height: 1.0;
        }
        
        /* Tablo sütun genişlikleri */
        .table th:nth-child(1),
        .table td:nth-child(1) { width: 5%; } /* Sıra */
        .table th:nth-child(2),
        .table td:nth-child(2) { width: 30%; } /* Ürün */
        .table th:nth-child(3),
        .table td:nth-child(3) { width: 10%; } /* Miktar */
        .table th:nth-child(4),
        .table td:nth-child(4) { width: 12%; } /* Birim Fiyat */
        .table th:nth-child(5),
        .table td:nth-child(5) { width: 6%; } /* KDV % */
        .table th:nth-child(6),
        .table td:nth-child(6) { width: 10%; } /* KDV Tutarı */
        .table th:nth-child(7),
        .table td:nth-child(7) { width: 10%; } /* Toplam */
        .table th:nth-child(8),
        .table td:nth-child(8) { width: 8%; } /* Teklif Tarihi */
        .table th:nth-child(9),
        .table td:nth-child(9) { width: 9%; } /* Geçerlilik Tarihi */
        
        /* Toplamlar */
        .totals {
            margin-top: 8px;
        }
        
        .total-row {
            margin: 2px 0;
            font-weight: bold;
            font-size: 11px;
        }
        
        .grand-total {
            font-size: 12px;
            color: #333;
            border-top: 1px solid #333;
            padding-top: 4px;
            margin-top: 8px;
            font-weight: bold;
        }
        
        /* Yazdırma ayarları */
        @media print {
            body { margin: 0; padding: 15mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>TEKLİF</h1>
    <h2>' . htmlspecialchars($teklif['teklif_basligi'] ?? '') . '</h2>
    
    <div class="info-section">
        <div style="display: flex; justify-content: space-between;">
            <div style="width: 50%;">
                <h3>Firma Bilgileri</h3>
                <div class="info-row"><strong>' . htmlspecialchars($teklif['firma_adi'] ?? '') . '</strong></div>
                <div class="info-row">' . htmlspecialchars($teklif['firma_adres'] ?? '') . '</div>
                <div class="info-row">Tel: ' . htmlspecialchars($teklif['firma_telefon'] ?? '') . '</div>
                <div class="info-row">Email: ' . htmlspecialchars($teklif['firma_email'] ?? '') . '</div>
            </div>
            <div style="width: 50%; text-align: right;">
                <h3>Tarihler</h3>';
    
    if ($teklif['cari_id']) {
        $html .= '<div class="info-row"><strong>' . htmlspecialchars($teklif['cari_unvan'] ?? '') . '</strong></div>';
        $html .= '<div class="info-row">' . htmlspecialchars($teklif['cari_adres'] ?? '') . '</div>';
        $html .= '<div class="info-row">Tel: ' . htmlspecialchars($teklif['cari_telefon'] ?? '') . '</div>';
        $html .= '<div class="info-row">Email: ' . htmlspecialchars($teklif['cari_email'] ?? '') . '</div>';
    } else {
        $html .= '<div class="info-row"><strong>' . htmlspecialchars($teklif['cari_disi_kisi'] ?? '') . '</strong></div>';
        $html .= '<div class="info-row">' . htmlspecialchars($teklif['cari_disi_adres'] ?? '') . '</div>';
        $html .= '<div class="info-row">Tel: ' . htmlspecialchars($teklif['cari_disi_telefon'] ?? '') . '</div>';
        $html .= '<div class="info-row">Email: ' . htmlspecialchars($teklif['cari_disi_email'] ?? '') . '</div>';
    }
    
    $html .= '</div>
        </div>
    </div>
    
    <div class="text-end">
        <p>Teklif Tarihi: ' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '</p>
        <p>Geçerlilik Tarihi: ' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</p>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Ürün/Hizmet</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
                <th>KDV %</th>
                <th>KDV Tutarı</th>
                <th>Toplam</th>
                <th>Teklif Tarihi</th>
                <th>Geçerlilik Tarihi</th>
            </tr>
        </thead>
        <tbody>';
    
    $sira = 1;
    foreach ($detaylar as $detay) {
        $urun_adi = $detay['urun_adi'] ?: $detay['aciklama'] ?: 'Ürün';
        $html .= '<tr>';
        $html .= '<td>' . $sira . '</td>';
        $html .= '<td>' . htmlspecialchars($urun_adi ?? '') . '</td>';
        $html .= '<td>' . number_format($detay['miktar'], 2, ',', '.') . ' ' . ($detay['birim'] ?: 'adet') . '</td>';
        $html .= '<td style="text-align: right;">' . number_format($detay['birim_fiyat'], 2, ',', '.') . ' ₺</td>';
        $html .= '<td>%' . $detay['kdv_orani'] . '</td>';
        $html .= '<td style="text-align: right;">' . number_format($detay['kdv_tutari'], 2, ',', '.') . ' ₺</td>';
        $html .= '<td style="text-align: right;">' . number_format($detay['toplam'], 2, ',', '.') . ' ₺</td>';
        $html .= '<td>' . date('d.m.Y', strtotime($teklif['teklif_tarihi'])) . '</td>';
        $html .= '<td>' . date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])) . '</td>';
        $html .= '</tr>';
        $sira++;
    }
    
    $html .= '</tbody>
    </table>
    
    <div class="totals">
        <div class="total-row" style="text-align: right;">Ara Toplam: ' . number_format($teklif['ara_toplam'], 2, ',', '.') . ' ₺</div>
        <div class="total-row" style="text-align: right;">KDV Toplam: ' . number_format($teklif['kdv_tutari'], 2, ',', '.') . ' ₺</div>
        <div class="grand-total" style="text-align: right;">GENEL TOPLAM: ' . number_format($teklif['genel_toplam'], 2, ',', '.') . ' ₺</div>
    </div>';
    
    if (!empty($teklif['aciklama'])) {
        $html .= '<div style="margin-top: 8px;">
            <h3>Açıklama</h3>
            <p>' . nl2br(htmlspecialchars($teklif['aciklama'] ?? '')) . '</p>
        </div>';
    }
    
    $html .= '</body>
</html>';
    
    return $html;
}

function createBasicPDF($content) {
    // En basit PDF oluştur
    $lines = explode("\n", $content);
    
    // PDF başlığı
    $pdf = "%PDF-1.4\n";
    
    // Catalog
    $pdf .= "1 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Catalog\n";
    $pdf .= "/Pages 2 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Pages
    $pdf .= "2 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Pages\n";
    $pdf .= "/Kids [3 0 R]\n";
    $pdf .= "/Count 1\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Page
    $pdf .= "3 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Page\n";
    $pdf .= "/Parent 2 0 R\n";
    $pdf .= "/MediaBox [0 0 612 792]\n";
    $pdf .= "/Contents 4 0 R\n";
    $pdf .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Content
    $content_stream = "BT\n";
    $content_stream .= "/F1 12 Tf\n";
    
    $y = 750;
    foreach ($lines as $line) {
        if ($y < 50) break;
        
        $content_stream .= "50 " . $y . " Td\n";
        $line = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line);
        $content_stream .= "(" . $line . ") Tj\n";
        $content_stream .= "0 -14 Td\n";
        $y -= 14;
    }
    
    $content_stream .= "ET\n";
    
    $pdf .= "4 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Length " . strlen($content_stream) . "\n";
    $pdf .= ">>\n";
    $pdf .= "stream\n";
    $pdf .= $content_stream;
    $pdf .= "endstream\n";
    $pdf .= "endobj\n";
    
    // Font
    $pdf .= "5 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // XRef
    $xref_pos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 6\n";
    $pdf .= "0000000000 65535 f \n";
    $pdf .= "0000000009 00000 n \n";
    $pdf .= "0000000058 00000 n \n";
    $pdf .= "0000000115 00000 n \n";
    $pdf .= "0000000206 00000 n \n";
    $pdf .= "0000000380 00000 n \n";
    
    // Trailer
    $pdf .= "trailer\n";
    $pdf .= "<<\n";
    $pdf .= "/Size 6\n";
    $pdf .= "/Root 1 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "startxref\n";
    $pdf .= $xref_pos . "\n";
    $pdf .= "%%EOF\n";
    
    return $pdf;
}

function createSimplePDF($content) {
    // İçeriği satırlara böl
    $lines = explode("\n", $content);
    
    // Basit PDF oluştur
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Catalog\n";
    $pdf .= "/Pages 2 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    $pdf .= "2 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Pages\n";
    $pdf .= "/Kids [3 0 R]\n";
    $pdf .= "/Count 1\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    $pdf .= "3 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Page\n";
    $pdf .= "/Parent 2 0 R\n";
    $pdf .= "/MediaBox [0 0 612 792]\n";
    $pdf .= "/Contents 4 0 R\n";
    $pdf .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // Content stream
    $content_stream = "BT\n";
    $content_stream .= "/F1 10 Tf\n";
    
    $y_pos = 750;
    foreach ($lines as $line) {
        if ($y_pos < 50) break;
        
        $content_stream .= "50 " . $y_pos . " Td\n";
        $clean_line = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line);
        $content_stream .= "(" . $clean_line . ") Tj\n";
        $content_stream .= "0 -12 Td\n";
        $y_pos -= 12;
    }
    
    $content_stream .= "ET\n";
    
    $pdf .= "4 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Length " . strlen($content_stream) . "\n";
    $pdf .= ">>\n";
    $pdf .= "stream\n";
    $pdf .= $content_stream;
    $pdf .= "endstream\n";
    $pdf .= "endobj\n";
    
    $pdf .= "5 0 obj\n";
    $pdf .= "<<\n";
    $pdf .= "/Type /Font\n";
    $pdf .= "/Subtype /Type1\n";
    $pdf .= "/BaseFont /Helvetica\n";
    $pdf .= ">>\n";
    $pdf .= "endobj\n";
    
    // XRef table
    $xref_pos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 6\n";
    $pdf .= "0000000000 65535 f \n";
    $pdf .= "0000000009 00000 n \n";
    $pdf .= "0000000058 00000 n \n";
    $pdf .= "0000000115 00000 n \n";
    $pdf .= "0000000206 00000 n \n";
    $pdf .= "0000000380 00000 n \n";
    
    $pdf .= "trailer\n";
    $pdf .= "<<\n";
    $pdf .= "/Size 6\n";
    $pdf .= "/Root 1 0 R\n";
    $pdf .= ">>\n";
    $pdf .= "startxref\n";
    $pdf .= $xref_pos . "\n";
    $pdf .= "%%EOF\n";
    
    return $pdf;
}
?>
