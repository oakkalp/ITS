<?php
$page_title = 'Cari Hesap Ekstreleri';
require_once '../../includes/auth.php';
require_login();
require_permission('cariler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-file-text me-2"></i>Cari Hesap Ekstreleri</h5>
    <div>
        <button class="btn btn-success me-2" onclick="exportExcel()">
            <i class="bi bi-file-earmark-excel me-2"></i>Excel Export
        </button>
        <button class="btn btn-info me-2" onclick="printEkstre()">
            <i class="bi bi-printer me-2"></i>Yazdır
        </button>
    </div>
</div>

<!-- Filtreler -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Filtreler</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Cari Seçin</label>
                <select class="form-select" id="cariSelect">
                    <option value="">Tüm Cariler</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="baslangicTarihi">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="bitisTarihi">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary d-block w-100" onclick="loadEkstre()">
                    <i class="bi bi-search me-2"></i>Yükle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cari Özet Bilgileri -->
<div class="card mb-3" id="cariOzetCard" style="display: none;">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">Cari Özet Bilgileri</h6>
    </div>
    <div class="card-body">
        <div class="row" id="cariOzetBilgileri">
            <!-- Cari bilgileri buraya yüklenecek -->
        </div>
    </div>
</div>

<!-- Ekstre Tablosu -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Hesap Ekstresi</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ekstreTable">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Belge No</th>
                        <th>Açıklama</th>
                        <th>Borç</th>
                        <th>Alacak</th>
                        <th>Bakiye</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yazdırma için gizli alan -->
<div id="printEkstreArea" style="display: none;">
    <div class="ekstre-header">
        <h2>CARİ HESAP EKSTRESİ</h2>
        <div class="firma-bilgi">
            <h4 id="printEkstreFirmaAdi">Firma Adı</h4>
            <p id="printEkstreFirmaAdres">Adres</p>
            <p id="printEkstreFirmaTel">Telefon</p>
        </div>
    </div>
    
    <div class="ekstre-bilgi">
        <div class="row">
            <div class="col-6">
                <p><strong>Cari:</strong> <span id="printEkstreCari"></span></p>
                <p><strong>Vergi No:</strong> <span id="printEkstreVergiNo"></span></p>
                <p><strong>Adres:</strong> <span id="printEkstreCariAdres"></span></p>
            </div>
            <div class="col-6">
                <p><strong>Tarih Aralığı:</strong> <span id="printEkstreTarihAraligi"></span></p>
                <p><strong>Başlangıç Bakiyesi:</strong> <span id="printEkstreBaslangicBakiye"></span></p>
                <p><strong>Son Bakiye:</strong> <span id="printEkstreSonBakiye"></span></p>
            </div>
        </div>
    </div>
    
    <table class="table table-bordered" id="printEkstreTable">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Belge No</th>
                <th>Açıklama</th>
                <th>Borç</th>
                <th>Alacak</th>
                <th>Bakiye</th>
            </tr>
        </thead>
        <tbody id="printEkstreTableBody"></tbody>
    </table>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printEkstreArea, #printEkstreArea * {
        visibility: visible;
    }
    #printEkstreArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .ekstre-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 20px;
    }
    .firma-bilgi {
        text-align: left;
        margin-top: 20px;
    }
    .ekstre-bilgi {
        margin: 20px 0;
        padding: 15px;
        border: 1px solid #ddd;
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
let ekstreData = null;
let cariData = null;

$(document).ready(function() {
    loadCariler();
    setDefaultDates();
});

function setDefaultDates() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#baslangicTarihi').val(firstDay.toISOString().split('T')[0]);
    $('#bitisTarihi').val(today.toISOString().split('T')[0]);
}

function loadCariler() {
    $.get('../../api/cariler/list.php', function(response) {
        if (response.success) {
            let html = '<option value="">Tüm Cariler</option>';
            response.data.forEach(function(cari) {
                html += `<option value="${cari.id}">${cari.unvan}</option>`;
            });
            $('#cariSelect').html(html);
        }
    });
}

function loadEkstre() {
    const cariId = $('#cariSelect').val();
    const baslangicTarihi = $('#baslangicTarihi').val();
    const bitisTarihi = $('#bitisTarihi').val();
    
    if (!baslangicTarihi || !bitisTarihi) {
        showError('Lütfen tarih aralığını seçin');
        return;
    }
    
    if (baslangicTarihi > bitisTarihi) {
        showError('Başlangıç tarihi bitiş tarihinden büyük olamaz');
        return;
    }
    
    // Loading göster
    $('#ekstreTable tbody').html('<tr><td colspan="6" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></td></tr>');
    
    let url = `../../api/cariler/ekstre.php?baslangic=${baslangicTarihi}&bitis=${bitisTarihi}`;
    if (cariId) {
        url += `&cari_id=${cariId}`;
    }
    
    $.get(url, function(response) {
        if (response.success) {
            ekstreData = response.data;
            displayEkstre(response.data);
            
            if (cariId) {
                displayCariOzet(response.cari_info);
            }
        } else {
            showError(response.message || 'Ekstre yüklenirken hata oluştu');
        }
    }).fail(function() {
        showError('Ekstre yüklenirken hata oluştu');
    });
}

function displayEkstre(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="6" class="text-center">Bu tarih aralığında işlem bulunamadı</td></tr>';
    } else {
        data.forEach(function(item) {
            html += `
                <tr>
                    <td>${formatDate(item.tarih)}</td>
                    <td>${item.belge_no || '-'}</td>
                    <td>${item.aciklama}</td>
                    <td class="text-end">${item.borç > 0 ? formatMoney(item.borç) : '-'}</td>
                    <td class="text-end">${item.alacak > 0 ? formatMoney(item.alacak) : '-'}</td>
                    <td class="text-end fw-bold ${item.bakiye >= 0 ? 'text-success' : 'text-danger'}">
                        ${formatMoney(Math.abs(item.bakiye))} ${item.bakiye >= 0 ? 'Alacak' : 'Borç'}
                    </td>
                </tr>
            `;
        });
    }
    
    $('#ekstreTable tbody').html(html);
}

function displayCariOzet(cariInfo) {
    if (!cariInfo) return;
    
    cariData = cariInfo;
    $('#cariOzetCard').show();
    
    const html = `
        <div class="col-md-3">
            <p><strong>Cari:</strong> ${cariInfo.unvan}</p>
            <p><strong>Vergi No:</strong> ${cariInfo.vergi_no || '-'}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Telefon:</strong> ${cariInfo.telefon || '-'}</p>
            <p><strong>Email:</strong> ${cariInfo.email || '-'}</p>
        </div>
        <div class="col-md-3">
            <p><strong>Başlangıç Bakiyesi:</strong> <span class="fw-bold">${formatMoney(cariInfo.baslangic_bakiye)}</span></p>
            <p><strong>Son Bakiye:</strong> <span class="fw-bold ${cariInfo.son_bakiye >= 0 ? 'text-success' : 'text-danger'}">${formatMoney(Math.abs(cariInfo.son_bakiye))} ${cariInfo.son_bakiye >= 0 ? 'Alacak' : 'Borç'}</span></p>
        </div>
        <div class="col-md-3">
            <p><strong>Toplam Borç:</strong> <span class="text-danger fw-bold">${formatMoney(cariInfo.toplam_borc)}</span></p>
            <p><strong>Toplam Alacak:</strong> <span class="text-success fw-bold">${formatMoney(cariInfo.toplam_alacak)}</span></p>
        </div>
    `;
    
    $('#cariOzetBilgileri').html(html);
}

function printEkstre() {
    if (!ekstreData) {
        showError('Önce ekstre yükleyin');
        return;
    }
    
    fillPrintEkstreArea();
    window.print();
}

function exportExcel() {
    if (!ekstreData) {
        showError('Önce ekstre yükleyin');
        return;
    }
    
    // Excel export için CSV formatında veri hazırla
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Tarih,Belge No,Açıklama,Borç,Alacak,Bakiye\n";
    
    ekstreData.forEach(function(item) {
        const borc = item.borç > 0 ? item.borç : '';
        const alacak = item.alacak > 0 ? item.alacak : '';
        const bakiye = `${item.bakiye} ${item.bakiye >= 0 ? 'Alacak' : 'Borç'}`;
        
        csvContent += `${formatDate(item.tarih)},${item.belge_no || ''},${item.aciklama},${borc},${alacak},${bakiye}\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `cari_ekstre_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function fillPrintEkstreArea() {
    if (!ekstreData) return;
    
    // Firma bilgilerini al
    $.get('../../api/firma/bilgiler.php', function(response) {
        if (response.success) {
            const firma = response.data;
            $('#printEkstreFirmaAdi').text(firma.firma_adi);
            $('#printEkstreFirmaAdres').text(firma.adres || '');
            $('#printEkstreFirmaTel').text(firma.telefon || '');
        }
    });
    
    // Cari bilgilerini doldur
    if (cariData) {
        $('#printEkstreCari').text(cariData.unvan);
        $('#printEkstreVergiNo').text(cariData.vergi_no || '');
        $('#printEkstreCariAdres').text(cariData.adres || '');
        $('#printEkstreBaslangicBakiye').text(formatMoney(cariData.baslangic_bakiye));
        $('#printEkstreSonBakiye').text(formatMoney(Math.abs(cariData.son_bakiye)) + ' ' + (cariData.son_bakiye >= 0 ? 'Alacak' : 'Borç'));
    }
    
    // Tarih aralığını doldur
    const baslangic = $('#baslangicTarihi').val();
    const bitis = $('#bitisTarihi').val();
    $('#printEkstreTarihAraligi').text(`${formatDate(baslangic)} - ${formatDate(bitis)}`);
    
    // Tablo içeriğini doldur
    let tableHtml = '';
    ekstreData.forEach(function(item) {
        tableHtml += `
            <tr>
                <td>${formatDate(item.tarih)}</td>
                <td>${item.belge_no || '-'}</td>
                <td>${item.aciklama}</td>
                <td class="text-end">${item.borç > 0 ? formatMoney(item.borç) : '-'}</td>
                <td class="text-end">${item.alacak > 0 ? formatMoney(item.alacak) : '-'}</td>
                <td class="text-end">${formatMoney(Math.abs(item.bakiye))} ${item.bakiye >= 0 ? 'Alacak' : 'Borç'}</td>
            </tr>
        `;
    });
    $('#printEkstreTableBody').html(tableHtml);
}
</script>
