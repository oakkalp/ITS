<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: list.php');
    exit;
}

$page_title = 'Fatura Detay';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-receipt me-2"></i>Fatura Detay</h5>
    <div>
        <button class="btn btn-success me-2" onclick="printFatura()">
            <i class="bi bi-printer me-2"></i>Yazdır
        </button>
        <button class="btn btn-info me-2" onclick="exportPDF()">
            <i class="bi bi-file-earmark-pdf me-2"></i>PDF
        </button>
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Geri
        </a>
    </div>
</div>

<!-- Fatura Bilgileri -->
<div class="card mb-3" id="faturaCard">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">Fatura Bilgileri</h6>
    </div>
    <div class="card-body">
        <div class="row" id="faturaBilgileri">
            <div class="col-12 text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fatura Detayları -->
<div class="card" id="faturaDetayCard">
    <div class="card-header">
        <h6 class="mb-0">Fatura Kalemleri</h6>
    </div>
    <div class="card-body">
        <table class="table table-hover" id="faturaDetayTable">
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th>Miktar</th>
                    <th>Birim Fiyat</th>
                    <th>KDV %</th>
                    <th>Ara Toplam</th>
                    <th>KDV Tutarı</th>
                    <th>Toplam</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Yazdırma için gizli alan -->
<div id="printArea">
    <div class="fatura-header">
        <h2>FATURA</h2>
        <div class="firma-bilgi">
            <h4 id="printFirmaAdi">Firma Adı</h4>
            <p id="printFirmaAdres">Adres</p>
            <p id="printFirmaTel">Telefon</p>
        </div>
    </div>
    
    <div class="fatura-bilgi">
        <div class="row">
            <div class="col-6">
                <p><strong>Fatura No:</strong> <span id="printFaturaNo"></span></p>
                <p><strong>Tarih:</strong> <span id="printTarih"></span></p>
                <p><strong>Tip:</strong> <span id="printTip"></span></p>
            </div>
            <div class="col-6">
                <p><strong>Cari:</strong> <span id="printCari"></span></p>
                <p><strong>Vergi No:</strong> <span id="printVergiNo"></span></p>
                <p><strong>Adres:</strong> <span id="printCariAdres"></span></p>
            </div>
        </div>
        
        <!-- Açıklama alanı -->
        <div id="printAciklamaArea">
            <div class="alert alert-info mt-3">
                <strong>Açıklama:</strong> <span id="printAciklama"></span>
            </div>
        </div>
    </div>
    
    <table class="table table-bordered" id="printTable">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
                <th>KDV %</th>
                <th>Ara Toplam</th>
                <th>KDV Tutarı</th>
                <th>Toplam</th>
            </tr>
        </thead>
        <tbody id="printTableBody"></tbody>
    </table>
    
    <div class="fatura-footer">
        <div class="row">
            <div class="col-6">
                <p><strong>Ara Toplam:</strong> <span id="printAraToplam"></span></p>
                <p><strong>KDV Toplam:</strong> <span id="printKdvToplam"></span></p>
            </div>
            <div class="col-6">
                <p><strong>Toplam Tutar:</strong> <span id="printToplamTutar"></span></p>
                <p><strong>Ödenen:</strong> <span id="printOdenen"></span></p>
                <p><strong>Kalan:</strong> <span id="printKalan"></span></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Normal görünüm için print area gizli */
#printArea {
    display: none;
}

#printAciklamaArea {
    display: none;
}

@media print {
    body * {
        visibility: hidden;
    }
    #printArea, #printArea * {
        visibility: visible;
    }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
    }
    #printAciklamaArea {
        display: block !important;
    }
    .fatura-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 20px;
    }
    .firma-bilgi {
        text-align: left;
        margin-top: 20px;
    }
    .fatura-bilgi {
        margin: 20px 0;
        padding: 15px;
        border: 1px solid #ddd;
    }
    .fatura-footer {
        margin-top: 30px;
        padding: 15px;
        border: 1px solid #ddd;
        background-color: #f8f9fa;
    }
    .table {
        margin: 20px 0;
    }
    .table th, .table td {
        border: 1px solid #000;
        padding: 8px;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>

<script>
const faturaId = <?php echo $id; ?>;
let faturaData = null;
let faturaDetayData = null;

$(document).ready(function() {
    loadFaturaDetay();
    loadFaturaKalemleri();
});

function loadFaturaDetay() {
    $.get('../../api/faturalar/get.php?id=' + faturaId, function(response) {
        if (response.success) {
            faturaData = response.data;
            const fatura = response.data;
            
            let html = `
                <div class="col-md-6">
                    <p><strong>Fatura No:</strong> ${fatura.fatura_no}</p>
                    <p><strong>Tip:</strong> `;
            
            if (fatura.fatura_tipi == 'alis') {
                html += '<span class="badge bg-danger">Alış Faturası</span>';
            } else {
                html += '<span class="badge bg-success">Satış Faturası</span>';
            }
            
            html += `</p>
                    <p><strong>Tarih:</strong> ${formatDate(fatura.fatura_tarihi)}</p>
                    <p><strong>Cari:</strong> ${fatura.cari_unvan}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Ara Toplam:</strong> ${formatMoney(fatura.ara_toplam)}</p>
                    <p><strong>KDV Toplam:</strong> ${formatMoney(fatura.kdv_toplam)}</p>
                    <p><strong>Toplam Tutar:</strong> <span class="fw-bold">${formatMoney(fatura.toplam_tutar)}</span></p>
                    <p><strong>Ödenen:</strong> ${formatMoney(fatura.odenen_tutar)}</p>
                    <p><strong>Durum:</strong> `;
            
            if (fatura.odeme_durumu == 'odendi') {
                html += '<span class="badge bg-success">Ödendi</span>';
            } else if (fatura.odeme_durumu == 'kismi') {
                html += '<span class="badge bg-warning">Kısmi</span>';
            } else {
                html += '<span class="badge bg-danger">Bekliyor</span>';
            }
            
            html += `</p>
                </div>
            `;
            
            // Açıklama varsa ekle
            if (fatura.aciklama && fatura.aciklama.trim() !== '') {
                html += `
                <div class="col-12 mt-3">
                    <div class="alert alert-info">
                        <strong>Açıklama:</strong> ${fatura.aciklama}
                    </div>
                </div>
                `;
            }
            
            $('#faturaBilgileri').html(html);
        } else {
            showError('Fatura bulunamadı!');
            setTimeout(() => window.location.href = 'list.php', 1500);
        }
    });
}

function loadFaturaKalemleri() {
    $('#faturaDetayTable').DataTable({
        ajax: {
            url: '../../api/faturalar/detay.php?fatura_id=' + faturaId,
            dataSrc: function(json) {
                faturaDetayData = json.data || [];
                return faturaDetayData;
            }
        },
        columns: [
            { data: 'urun_adi' },
            { 
                data: 'miktar',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'birim_fiyat',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'kdv_orani',
                render: function(data) {
                    return data + '%';
                }
            },
            { 
                data: 'ara_toplam',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'kdv_tutar',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'toplam',
                render: function(data) {
                    return formatMoney(data);
                }
            }
        ],
        order: [[0, 'asc']],
        searching: false,
        paging: false,
        info: false
    });
}

function printFatura() {
    if (!faturaData || !faturaDetayData) {
        showError('Fatura verileri henüz yüklenmedi!');
        return;
    }
    
    // Print area'yı görünür yap
    $('#printArea').show();
    
    // Yazdırma alanını doldur
    fillPrintArea();
    
    // Firma bilgileri yüklendikten sonra yazdır
    setTimeout(function() {
        window.print();
        
        // Yazdırma sonrası print area'yı gizle
        setTimeout(function() {
            $('#printArea').hide();
        }, 1000);
    }, 500);
}

function exportPDF() {
    if (!faturaData || !faturaDetayData) {
        showError('Fatura verileri henüz yüklenmedi!');
        return;
    }
    
    // Print area'yı görünür yap
    $('#printArea').show();
    
    // Yazdırma alanını doldur
    fillPrintArea();
    
    // PDF için yeni pencere aç
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Fatura ${faturaData.fatura_no}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .fatura-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                .firma-bilgi { text-align: left; margin-top: 20px; }
                .fatura-bilgi { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
                .fatura-footer { margin-top: 30px; padding: 15px; border: 1px solid #ddd; background-color: #f8f9fa; }
                .table { margin: 20px 0; width: 100%; border-collapse: collapse; }
                .table th, .table td { border: 1px solid #000; padding: 8px; text-align: left; }
                .table th { background-color: #f8f9fa; font-weight: bold; }
                .text-end { text-align: right; }
                .alert { padding: 10px; margin: 10px 0; border: 1px solid #ddd; background-color: #f8f9fa; }
            </style>
        </head>
        <body>
            ${document.getElementById('printArea').innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
    
    // Print area'yı gizle
    setTimeout(function() {
        $('#printArea').hide();
    }, 1000);
}

function fillPrintArea() {
    if (!faturaData) return;
    
    // Firma bilgilerini al
    $.get('../../api/firma/bilgiler.php', function(response) {
        if (response.success) {
            const firma = response.data;
            $('#printFirmaAdi').text(firma.firma_adi);
            $('#printFirmaAdres').text(firma.adres || '');
            $('#printFirmaTel').text(firma.telefon || '');
        }
    });
    
    // Fatura bilgilerini doldur
    $('#printFaturaNo').text(faturaData.fatura_no);
    $('#printTarih').text(formatDate(faturaData.fatura_tarihi));
    $('#printTip').text(faturaData.fatura_tipi == 'alis' ? 'Alış Faturası' : 'Satış Faturası');
    $('#printCari').text(faturaData.cari_unvan);
    $('#printVergiNo').text(faturaData.cari_vergi_no || '');
    $('#printCariAdres').text(faturaData.cari_adres || '');
    
    // Toplam bilgilerini doldur
    $('#printAraToplam').text(formatMoney(faturaData.ara_toplam));
    $('#printKdvToplam').text(formatMoney(faturaData.kdv_toplam));
    $('#printToplamTutar').text(formatMoney(faturaData.toplam_tutar));
    $('#printOdenen').text(formatMoney(faturaData.odenen_tutar));
    
    const kalan = parseFloat(faturaData.toplam_tutar) - parseFloat(faturaData.odenen_tutar);
    $('#printKalan').text(formatMoney(kalan));
    
    // Açıklama bilgisini doldur
    if (faturaData.aciklama && faturaData.aciklama.trim() !== '') {
        $('#printAciklama').text(faturaData.aciklama);
        $('#printAciklamaArea').show();
    } else {
        $('#printAciklamaArea').hide();
    }
    
    // Tablo içeriğini doldur
    let tableHtml = '';
    faturaDetayData.forEach(function(kalem) {
        tableHtml += `
            <tr>
                <td>${kalem.urun_adi}</td>
                <td>${parseFloat(kalem.miktar).toFixed(2)}</td>
                <td>${formatMoney(kalem.birim_fiyat)}</td>
                <td>${kalem.kdv_orani}%</td>
                <td>${formatMoney(kalem.ara_toplam)}</td>
                <td>${formatMoney(kalem.kdv_tutar)}</td>
                <td>${formatMoney(kalem.toplam)}</td>
            </tr>
        `;
    });
    $('#printTableBody').html(tableHtml);
}
</script>
