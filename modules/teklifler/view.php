<?php
require_once '../../includes/header.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'okuma');

$firma_id = get_firma_id();
$id = $_GET['id'] ?? 0;

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

// Teklif detaylarını al - önce teklif_detaylari tablosundan
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

// Eğer teklif_detaylari tablosunda veri yoksa, detaylar kolonundan al
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
                'kdv_orani' => $detay['kdv_orani'] ?? 18
            ];
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Teklif Görüntüle</h4>
                    <div>
                        <button class="btn btn-primary" onclick="window.open('preview.php?id=<?php echo $id; ?>', '_blank')">
                            <i class="bi bi-eye"></i> Baskı Önizlemesi
                        </button>
                        <button class="btn btn-success" onclick="window.open('preview.php?id=<?php echo $id; ?>', '_blank')">
                            <i class="bi bi-printer"></i> Yazdır
                        </button>
                        <button class="btn btn-danger" onclick="downloadTeklif(<?php echo $id; ?>)">
                            <i class="bi bi-file-earmark-code"></i> Teklif İndir
                        </button>
                        <button class="btn btn-info" onclick="sendWhatsApp(<?php echo $id; ?>)">
                            <i class="bi bi-whatsapp"></i> WhatsApp Gönder
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Teklif Başlığı -->
                    <div class="row mb-4">
                        <div class="col-12 text-center">
                            <h2 class="text-primary mb-0" style="font-size: 24px; font-weight: bold;"><?php echo htmlspecialchars($teklif['teklif_basligi'] ?? ''); ?></h2>
                            <p class="mb-0" style="font-size: 12px; color: #666;">Geçerlilik Tarihi: <?php echo date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])); ?></p>
                        </div>
                    </div>
                    
                    <!-- Başlık ve Firma Bilgileri -->
                    <div class="row mb-4">
                        <div class="col-md-7">
                            <h3 class="mb-2"><strong><?php echo htmlspecialchars($teklif['firma_adi'] ?? ''); ?></strong></h3>
                            <p class="mb-1"><?php echo htmlspecialchars($teklif['firma_adres'] ?? ''); ?></p>
                            <p class="mb-1">Tel: <?php echo htmlspecialchars($teklif['firma_telefon'] ?? ''); ?> | Email: <?php echo htmlspecialchars($teklif['firma_email'] ?? ''); ?></p>
                            <p class="mb-0">Vergi Dairesi: <?php echo htmlspecialchars($teklif['firma_vergi_dairesi'] ?? ''); ?> | Vergi No: <?php echo htmlspecialchars($teklif['firma_vergi_no'] ?? ''); ?></p>
                        </div>
                        <div class="col-md-5 text-end">
                            <h1 class="text-primary mb-0" style="font-size: 28px; font-weight: bold;">TEKLİF</h1>
                            <p class="mb-1">Teklif No: <?php echo htmlspecialchars($teklif['teklif_no'] ?? ''); ?></p>
                            <p class="mb-0">Tarih: <?php echo date('d.m.Y', strtotime($teklif['teklif_tarihi'])); ?></p>
                        </div>
                    </div>

                    <!-- Teklif Verilen ve Tarihler -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-2"><strong>Teklif Verilen:</strong></h5>
                            <?php if ($teklif['cari_id']): ?>
                                <h4 class="mb-1"><strong><?php echo htmlspecialchars($teklif['cari_unvan'] ?? ''); ?></strong></h4>
                                <p class="mb-1"><?php echo htmlspecialchars($teklif['cari_adres'] ?? ''); ?></p>
                                <p class="mb-0">Tel: <?php echo htmlspecialchars($teklif['cari_telefon'] ?? ''); ?> | Email: <?php echo htmlspecialchars($teklif['cari_email'] ?? ''); ?></p>
                            <?php else: ?>
                                <h4 class="mb-1"><strong><?php echo htmlspecialchars($teklif['cari_disi_kisi'] ?? ''); ?></strong></h4>
                                <p class="mb-1"><?php echo htmlspecialchars($teklif['cari_disi_adres'] ?? ''); ?></p>
                                <p class="mb-0">Tel: <?php echo htmlspecialchars($teklif['cari_disi_telefon'] ?? ''); ?> | Email: <?php echo htmlspecialchars($teklif['cari_disi_email'] ?? ''); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5 class="mb-2"><strong>Tarihler:</strong></h5>
                            <p class="mb-1">Teklif Tarihi: <?php echo date('d.m.Y', strtotime($teklif['teklif_tarihi'])); ?></p>
                            <p class="mb-0">Geçerlilik Tarihi: <?php echo date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])); ?></p>
                        </div>
                    </div>

                    <!-- Ürünler Tablosu -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 5%;">Sıra</th>
                                    <th style="width: 30%;">Ürün/Hizmet</th>
                                    <th class="text-center" style="width: 10%;">Miktar</th>
                                    <th class="text-end" style="width: 12%;">Birim Fiyat</th>
                                    <th class="text-center" style="width: 6%;">KDV %</th>
                                    <th class="text-end" style="width: 10%;">KDV Tutarı</th>
                                    <th class="text-end" style="width: 10%;">Toplam</th>
                                    <th class="text-center" style="width: 8%;">Teklif Tarihi</th>
                                    <th class="text-center" style="width: 9%;">Geçerlilik Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $sira = 1; foreach ($detaylar as $detay): ?>
                                <tr>
                                    <td class="text-center"><?php echo $sira; ?></td>
                                    <td><?php echo htmlspecialchars($detay['urun_adi'] ?: $detay['aciklama']); ?></td>
                                    <td class="text-center"><?php echo number_format($detay['miktar'], 2, ',', '.'); ?> <?php echo htmlspecialchars($detay['birim'] ?: 'adet'); ?></td>
                                    <td class="text-end"><?php echo number_format($detay['birim_fiyat'], 2, ',', '.'); ?> ₺</td>
                                    <td class="text-center">%<?php echo $detay['kdv_orani']; ?></td>
                                    <td class="text-end"><?php echo number_format($detay['kdv_tutari'], 2, ',', '.'); ?> ₺</td>
                                    <td class="text-end fw-bold"><?php echo number_format($detay['toplam'], 2, ',', '.'); ?> ₺</td>
                                    <td class="text-center"><?php echo date('d.m.Y', strtotime($teklif['teklif_tarihi'])); ?></td>
                                    <td class="text-center"><?php echo date('d.m.Y', strtotime($teklif['gecerlilik_tarihi'])); ?></td>
                                </tr>
                                <?php $sira++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Toplam Bilgileri -->
                    <div class="row">
                        <div class="col-md-6">
                            <?php if (!empty($teklif['aciklama'])): ?>
                            <div class="mt-3">
                                <h6 class="mb-2"><strong>Açıklama:</strong></h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($teklif['aciklama'] ?? '')); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <td class="fw-bold">Ara Toplam:</td>
                                    <td class="text-end"><?php echo number_format($teklif['ara_toplam'], 2, ',', '.'); ?> ₺</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">KDV Toplam:</td>
                                    <td class="text-end"><?php echo number_format($teklif['kdv_tutari'], 2, ',', '.'); ?> ₺</td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="fw-bold">GENEL TOPLAM:</td>
                                    <td class="text-end fw-bold"><?php echo number_format($teklif['genel_toplam'], 2, ',', '.'); ?> ₺</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Tüm sidebar ve menüleri gizle */
    .sidebar,
    .navbar,
    .breadcrumb,
    .btn,
    .card-header .btn,
    .card-header a,
    .main-sidebar,
    .content-wrapper,
    .main-header,
    .main-footer {
        display: none !important;
    }
    
    /* Body ayarları - A4 genişlik */
    body {
        margin: 0 !important;
        padding: 0 !important;
        font-size: 12px !important;
        background: white !important;
        color: black !important;
        width: 210mm !important;
        max-width: 210mm !important;
        min-height: auto !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }
    
    /* Container ayarları - A4 genişlik */
    .container-fluid {
        margin: 0 !important;
        padding: 15mm !important;
        width: 210mm !important;
        max-width: 210mm !important;
        box-sizing: border-box !important;
        position: relative !important;
    }
    
    /* Card ayarları - A4 genişlik */
    .card {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        width: 100% !important;
        max-width: 210mm !important;
        box-sizing: border-box !important;
        position: relative !important;
    }
    
    .card-body {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 210mm !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }
    
    /* Card header - sadece başlık */
    .card-header {
        background: white !important;
        border: none !important;
        padding: 0 0 8px 0 !important;
        margin-bottom: 8px !important;
        height: auto !important;
    }
    
    .card-header h4 {
        display: block !important;
        margin: 0 !important;
        font-size: 14px !important;
        font-weight: bold !important;
        color: black !important;
    }
    
    /* Tablo ayarları - A4 genişlik */
    .table {
        border-collapse: collapse !important;
        width: 100% !important;
        max-width: 180mm !important;
        font-size: 12px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        table-layout: fixed !important;
    }
    
    .table th,
    .table td {
        border: 1px solid #000 !important;
        padding: 1px 6px !important;
        text-align: left !important;
        vertical-align: middle !important;
        word-wrap: break-word !important;
        line-height: 1.0 !important;
        height: 18px !important;
    }
    
    .table th {
        background: #f8f9fa !important;
        font-weight: bold !important;
        font-size: 11px !important;
        height: 18px !important;
        line-height: 1.0 !important;
    }
    
    /* Tablo sütun genişlikleri - tarih sütunları ile */
    .table th:nth-child(1),
    .table td:nth-child(1) { width: 5% !important; } /* Sıra */
    .table th:nth-child(2),
    .table td:nth-child(2) { width: 30% !important; } /* Ürün */
    .table th:nth-child(3),
    .table td:nth-child(3) { width: 10% !important; } /* Miktar */
    .table th:nth-child(4),
    .table td:nth-child(4) { width: 12% !important; } /* Birim Fiyat */
    .table th:nth-child(5),
    .table td:nth-child(5) { width: 6% !important; } /* KDV % */
    .table th:nth-child(6),
    .table td:nth-child(6) { width: 10% !important; } /* KDV Tutarı */
    .table th:nth-child(7),
    .table td:nth-child(7) { width: 10% !important; } /* Toplam */
    .table th:nth-child(8),
    .table td:nth-child(8) { width: 8% !important; } /* Teklif Tarihi */
    .table th:nth-child(9),
    .table td:nth-child(9) { width: 9% !important; } /* Geçerlilik Tarihi */
    
    /* Tablo responsive ayarları - A4 genişlik */
    .table-responsive {
        width: 100% !important;
        max-width: 180mm !important;
        overflow: visible !important;
        box-sizing: border-box !important;
        margin-bottom: 8px !important;
    }
    
    /* Text ayarları - resimdeki gibi */
    h1, h2, h3, h4, h5, h6 {
        color: black !important;
        font-weight: bold !important;
        margin: 4px 0 !important;
        font-size: 12px !important;
    }
    
    h1 {
        font-size: 22px !important;
        margin-bottom: 8px !important;
        color: #0d6efd !important;
    }
    
    h2 {
        font-size: 20px !important;
        margin-bottom: 4px !important;
        color: #0d6efd !important;
        text-align: center !important;
    }
    
    /* Geçerlilik tarihi yazdırmada */
    .card-body p {
        font-size: 11px !important;
        color: #666 !important;
        text-align: center !important;
        margin: 2px 0 !important;
    }
    
    /* Tarihler sağa yaslanmış */
    .text-end {
        text-align: right !important;
    }
    
    .text-end p {
        text-align: right !important;
        margin: 1px 0 !important;
    }
    
    h3 {
        font-size: 14px !important;
        margin-bottom: 2px !important;
        line-height: 1.1 !important;
    }
    
    h4 {
        font-size: 13px !important;
        margin-bottom: 1px !important;
        line-height: 1.1 !important;
    }
    
    h5 {
        font-size: 12px !important;
        margin-bottom: 1px !important;
        line-height: 1.1 !important;
    }
    
    h6 {
        font-size: 12px !important;
        margin-bottom: 4px !important;
    }
    
    p, div, span {
        color: black !important;
        margin: 0px 0 !important;
        font-size: 11px !important;
        line-height: 1.1 !important;
    }
    
    .small {
        font-size: 11px !important;
    }
    
    .fw-bold {
        font-weight: bold !important;
    }
    
    /* Badge ayarları */
    .badge {
        background: transparent !important;
        color: black !important;
        border: 1px solid #000 !important;
        padding: 1px 4px !important;
        font-size: 9px !important;
    }
    
    /* Row ve column ayarları - A4 genişlik */
    .row {
        margin: 0 !important;
        width: 100% !important;
        max-width: 180mm !important;
        display: block !important;
        margin-bottom: 2px !important;
    }
    
    .col-md-6,
    .col-md-8,
    .col-md-7,
    .col-md-5,
    .col-md-4,
    .col-12 {
        display: block !important;
        width: 100% !important;
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* İki sütunlu düzen için */
    .col-md-6 {
        width: 50% !important;
        float: left !important;
        padding-right: 10px !important;
        margin-bottom: 5px !important;
    }
    
    .col-md-6:last-child {
        padding-right: 0 !important;
    }
    
    /* 7-5 sütunlu düzen için */
    .col-md-7 {
        width: 58% !important;
        float: left !important;
        padding-right: 10px !important;
        margin-bottom: 5px !important;
    }
    
    .col-md-5 {
        width: 42% !important;
        float: left !important;
        padding-right: 0 !important;
        margin-bottom: 5px !important;
    }
    
    /* 8-4 sütunlu düzen için */
    .col-md-8 {
        width: 67% !important;
        float: left !important;
        padding-right: 10px !important;
        margin-bottom: 5px !important;
    }
    
    .col-md-4 {
        width: 33% !important;
        float: left !important;
        padding-right: 0 !important;
        margin-bottom: 5px !important;
    }
    
    /* Tek sütunlu düzen için */
    .col-12 {
        width: 100% !important;
        float: none !important;
    }
    
    /* Toplam tablosu - tek satır sığacak */
    .table.table-bordered {
        font-size: 10px !important;
        margin-top: 10px !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .table.table-bordered td {
        padding: 1px 6px !important;
        line-height: 1.0 !important;
        height: 16px !important;
        white-space: nowrap !important;
    }
    
    .table.table-bordered td:first-child {
        text-align: left !important;
        width: 60% !important;
    }
    
    .table.table-bordered td:last-child {
        text-align: right !important;
        width: 40% !important;
    }
    
    .table-primary td {
        font-weight: bold !important;
        font-size: 11px !important;
        background: #e3f2fd !important;
        padding: 1px 6px !important;
        height: 16px !important;
        line-height: 1.0 !important;
        white-space: nowrap !important;
    }
    
    /* Sayfa boyutu ayarları - A4 */
    @page {
        size: A4;
        margin: 15mm;
        width: 210mm;
        height: 297mm;
    }
    
    /* Görünürlük ayarları */
    * {
        visibility: visible !important;
        box-sizing: border-box !important;
    }
}
</style>

<script>
function downloadTeklif(id) {
    // Doğrudan indirme linkini aç
    const downloadUrl = `../../api/teklifler/create_file.php?id=${id}&download=1`;
    window.open(downloadUrl, '_blank');
}

function sendWhatsApp(id) {
    // HTML dosyasını oluştur ve WhatsApp'ta paylaş
    $.get(`../../api/teklifler/create_file.php?id=${id}`, function(response) {
        if (response.success) {
            // HTML dosyasının tam URL'sini oluştur
            const fileUrl = window.location.origin + '/muhasebedemo/temp/download.php?file=' + response.filename;
            const message = `Teklifinizi inceleyebilir misiniz?\n\n📄 Teklif Dosyası: ${fileUrl}\n\nBu dosyayı tarayıcınızda açarak görüntüleyebilir veya yazdırabilirsiniz.`;
            
            // WhatsApp'ta aç
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        } else {
            alert('Dosya oluşturulamadı');
        }
    }, 'json').fail(function() {
        alert('WhatsApp paylaşımı başarısız');
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
