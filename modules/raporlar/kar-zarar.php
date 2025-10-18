<?php
$page_title = 'Kar-Zarar Raporu';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-graph-up-arrow me-2"></i>Kar-Zarar Raporu</h5>
    <div>
        <button class="btn btn-success me-2" onclick="exportExcel()">
            <i class="bi bi-file-earmark-excel me-2"></i>Excel Export
        </button>
        <button class="btn btn-info me-2" onclick="printRapor()">
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
            <div class="col-md-3">
                <label class="form-label">Rapor Tipi</label>
                <select class="form-select" id="raporTipi">
                    <option value="aylik">Aylık</option>
                    <option value="yillik">Yıllık</option>
                    <option value="ozel">Özel Tarih</option>
                </select>
            </div>
            <div class="col-md-2" id="aySecim">
                <label class="form-label">Ay</label>
                <select class="form-select" id="aySelect">
                    <option value="01">Ocak</option>
                    <option value="02">Şubat</option>
                    <option value="03">Mart</option>
                    <option value="04">Nisan</option>
                    <option value="05">Mayıs</option>
                    <option value="06">Haziran</option>
                    <option value="07">Temmuz</option>
                    <option value="08">Ağustos</option>
                    <option value="09">Eylül</option>
                    <option value="10">Ekim</option>
                    <option value="11">Kasım</option>
                    <option value="12">Aralık</option>
                </select>
            </div>
            <div class="col-md-2" id="yilSecim">
                <label class="form-label">Yıl</label>
                <select class="form-select" id="yilSelect">
                    <?php
                    $current_year = date('Y');
                    for ($i = $current_year; $i >= $current_year - 5; $i--) {
                        echo "<option value='$i'" . ($i == $current_year ? ' selected' : '') . ">$i</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2" id="ozelTarih" style="display: none;">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="baslangicTarihi">
            </div>
            <div class="col-md-2" id="ozelTarih2" style="display: none;">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="bitisTarihi">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary d-block w-100" onclick="loadKarZararRaporu()">
                    <i class="bi bi-search me-2"></i>Yükle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Özet Kartları -->
<div class="row mb-3" id="ozetKartlari" style="display: none;">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h5 class="card-title">Toplam Gelir</h5>
                <h3 id="toplamGelir">₺0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h5 class="card-title">Toplam Gider</h5>
                <h3 id="toplamGider">₺0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h5 class="card-title">Brüt Kar</h5>
                <h3 id="brutKar">₺0</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h5 class="card-title">Net Kar</h5>
                <h3 id="netKar">₺0</h3>
            </div>
        </div>
    </div>
</div>

<!-- Detaylı Rapor -->
<div class="row">
    <!-- Gelirler -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">GELİRLER</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="gelirTable">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Tutar</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Giderler -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">GİDERLER</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm" id="giderTable">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Tutar</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detaylı Hareketler -->
<div class="card mt-3">
    <div class="card-header">
        <h6 class="mb-0">Detaylı Hareketler</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="detayTable">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Tip</th>
                        <th>Açıklama</th>
                        <th>Kategori</th>
                        <th>Tutar</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yazdırma için gizli alan -->
<div id="printRaporArea" style="display: none;">
    <div class="rapor-header">
        <h2>KAR-ZARAR RAPORU</h2>
        <div class="firma-bilgi">
            <h4 id="printRaporFirmaAdi">Firma Adı</h4>
            <p id="printRaporFirmaAdres">Adres</p>
            <p id="printRaporFirmaTel">Telefon</p>
        </div>
    </div>
    
    <div class="rapor-bilgi">
        <div class="row">
            <div class="col-6">
                <p><strong>Rapor Tarihi:</strong> <span id="printRaporTarihi"></span></p>
                <p><strong>Dönem:</strong> <span id="printRaporDonem"></span></p>
            </div>
            <div class="col-6">
                <p><strong>Toplam Gelir:</strong> <span id="printToplamGelir"></span></p>
                <p><strong>Toplam Gider:</strong> <span id="printToplamGider"></span></p>
                <p><strong>Net Kar:</strong> <span id="printNetKar"></span></p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-6">
            <h4>GELİRLER</h4>
            <table class="table table-bordered" id="printGelirTable">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Tutar</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody id="printGelirTableBody"></tbody>
            </table>
        </div>
        <div class="col-6">
            <h4>GİDERLER</h4>
            <table class="table table-bordered" id="printGiderTable">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Tutar</th>
                        <th>%</th>
                    </tr>
                </thead>
            <tbody id="printGiderTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Detaylı Hareketler -->
<div class="mt-4">
    <h4>DETAYLI HAREKETLER</h4>
    <table class="table table-bordered" id="printDetayTable">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Tip</th>
                <th>Açıklama</th>
                <th>Kategori</th>
                <th>Tutar</th>
            </tr>
        </thead>
        <tbody id="printDetayTableBody"></tbody>
    </table>
</div>
</div>
</div>

<style>
/* Normal görünüm için print area gizli */
#printRaporArea {
    display: none;
}

@media print {
    body * {
        visibility: hidden;
    }
    #printRaporArea, #printRaporArea * {
        visibility: visible;
    }
    #printRaporArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
    }
    .rapor-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #000;
        padding-bottom: 20px;
    }
    .firma-bilgi {
        text-align: left;
        margin-top: 20px;
    }
    .rapor-bilgi {
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
    .mt-4 {
        margin-top: 1.5rem !important;
    }
    h4 {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 10px;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>

<script>
let raporData = null;

$(document).ready(function() {
    setCurrentMonth();
    $('#raporTipi').on('change', toggleDateInputs);
});

function setCurrentMonth() {
    const now = new Date();
    const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
    $('#aySelect').val(currentMonth);
}

function toggleDateInputs() {
    const raporTipi = $('#raporTipi').val();
    
    if (raporTipi === 'ozel') {
        $('#aySecim').hide();
        $('#yilSecim').hide();
        $('#ozelTarih').show();
        $('#ozelTarih2').show();
    } else {
        $('#aySecim').show();
        $('#yilSecim').show();
        $('#ozelTarih').hide();
        $('#ozelTarih2').hide();
    }
}

function loadKarZararRaporu() {
    const raporTipi = $('#raporTipi').val();
    let url = '../../api/raporlar/kar-zarar.php?';
    
    if (raporTipi === 'ozel') {
        const baslangic = $('#baslangicTarihi').val();
        const bitis = $('#bitisTarihi').val();
        
        if (!baslangic || !bitis) {
            showError('Lütfen tarih aralığını seçin');
            return;
        }
        
        if (baslangic > bitis) {
            showError('Başlangıç tarihi bitiş tarihinden büyük olamaz');
            return;
        }
        
        url += `baslangic=${baslangic}&bitis=${bitis}`;
    } else {
        const ay = $('#aySelect').val();
        const yil = $('#yilSelect').val();
        url += `ay=${ay}&yil=${yil}&tip=${raporTipi}`;
    }
    
    // Loading göster
    $('#ozetKartlari').hide();
    $('#gelirTable tbody').html('<tr><td colspan="3" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></td></tr>');
    $('#giderTable tbody').html('<tr><td colspan="3" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></td></tr>');
    $('#detayTable tbody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></td></tr>');
    
    $.get(url, function(response) {
        console.log('Kar-zarar raporu API yanıtı:', response);
        if (response.success) {
            raporData = response.data;
            console.log('Rapor verisi:', raporData);
            displayKarZararRaporu(response.data);
        } else {
            console.error('Rapor hatası:', response.message);
            showError(response.message || 'Rapor yüklenirken hata oluştu');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX hatası:', status, error);
        console.error('XHR Response:', xhr.responseText);
        showError('Rapor yüklenirken hata oluştu');
    });
}

function displayKarZararRaporu(data) {
    // Özet kartları
    $('#toplamGelir').text(formatMoney(data.toplam_gelir));
    $('#toplamGider').text(formatMoney(data.toplam_gider));
    $('#brutKar').text(formatMoney(data.brut_kar));
    $('#netKar').text(formatMoney(data.net_kar));
    $('#ozetKartlari').show();
    
    // Gelirler tablosu
    let gelirHtml = '';
    data.gelirler.forEach(function(item) {
        const yuzde = data.toplam_gelir > 0 ? ((item.tutar / data.toplam_gelir) * 100).toFixed(1) : 0;
        gelirHtml += `
            <tr>
                <td>${item.kategori}</td>
                <td class="text-end">${formatMoney(item.tutar)}</td>
                <td class="text-end">%${yuzde}</td>
            </tr>
        `;
    });
    $('#gelirTable tbody').html(gelirHtml);
    
    // Giderler tablosu
    let giderHtml = '';
    data.giderler.forEach(function(item) {
        const yuzde = data.toplam_gider > 0 ? ((item.tutar / data.toplam_gider) * 100).toFixed(1) : 0;
        giderHtml += `
            <tr>
                <td>${item.kategori}</td>
                <td class="text-end">${formatMoney(item.tutar)}</td>
                <td class="text-end">%${yuzde}</td>
            </tr>
        `;
    });
    $('#giderTable tbody').html(giderHtml);
    
    // Detaylı hareketler
    let detayHtml = '';
    console.log('Detaylı hareketler:', data.detaylar);
    
    if (data.detaylar && data.detaylar.length > 0) {
        data.detaylar.forEach(function(item) {
            console.log('Hareket item:', item);
            const tipClass = item.kategori_tip === 'gelir' ? 'text-success' : 'text-danger';
            detayHtml += `
                <tr>
                    <td>${formatDate(item.tarih)}</td>
                    <td><span class="badge ${tipClass}">${item.tip_display}</span></td>
                    <td>${item.aciklama}</td>
                    <td>${item.kategori}</td>
                    <td class="text-end">${formatMoney(item.tutar)}</td>
                </tr>
            `;
        });
    } else {
        detayHtml = '<tr><td colspan="5" class="text-center text-muted">Bu dönemde hareket bulunmamaktadır</td></tr>';
    }
    
    $('#detayTable tbody').html(detayHtml);
}

function printRapor() {
    if (!raporData) {
        showError('Önce rapor yükleyin');
        return;
    }
    
    console.log('Yazdırma başlatılıyor...');
    
    // Print area'yı görünür yap
    $('#printRaporArea').show();
    
    // Print area'yı doldur
    fillPrintRaporArea();
    
    // Firma bilgileri yüklendikten sonra yazdır
    setTimeout(function() {
        console.log('Yazdırma işlemi başlatılıyor...');
        window.print();
        
        // Yazdırma sonrası print area'yı gizle
        setTimeout(function() {
            $('#printRaporArea').hide();
        }, 1000);
    }, 500);
}

function exportExcel() {
    if (!raporData) {
        showError('Önce rapor yükleyin');
        return;
    }
    
    // Excel export için CSV formatında veri hazırla
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "KAR-ZARAR RAPORU\n";
    csvContent += `Toplam Gelir,${raporData.toplam_gelir}\n`;
    csvContent += `Toplam Gider,${raporData.toplam_gider}\n`;
    csvContent += `Net Kar,${raporData.net_kar}\n\n`;
    
    csvContent += "GELİRLER\n";
    csvContent += "Kategori,Tutar,Yüzde\n";
    raporData.gelirler.forEach(function(item) {
        const yuzde = raporData.toplam_gelir > 0 ? ((item.tutar / raporData.toplam_gelir) * 100).toFixed(1) : 0;
        csvContent += `${item.kategori},${item.tutar},%${yuzde}\n`;
    });
    
    csvContent += "\nGİDERLER\n";
    csvContent += "Kategori,Tutar,Yüzde\n";
    raporData.giderler.forEach(function(item) {
        const yuzde = raporData.toplam_gider > 0 ? ((item.tutar / raporData.toplam_gider) * 100).toFixed(1) : 0;
        csvContent += `${item.kategori},${item.tutar},%${yuzde}\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `kar_zarar_raporu_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function fillPrintRaporArea() {
    if (!raporData) return;
    
    console.log('Print area dolduruluyor...');
    
    // Rapor bilgilerini doldur
    $('#printRaporTarihi').text(formatDate(new Date()));
    
    const raporTipi = $('#raporTipi').val();
    let donem = '';
    if (raporTipi === 'ozel') {
        const baslangic = $('#baslangicTarihi').val();
        const bitis = $('#bitisTarihi').val();
        donem = `${formatDate(baslangic)} - ${formatDate(bitis)}`;
    } else {
        const ay = $('#aySelect option:selected').text();
        const yil = $('#yilSelect').val();
        donem = `${ay} ${yil}`;
    }
    $('#printRaporDonem').text(donem);
    
    // Özet bilgileri
    $('#printToplamGelir').text(formatMoney(raporData.toplam_gelir));
    $('#printToplamGider').text(formatMoney(raporData.toplam_gider));
    $('#printNetKar').text(formatMoney(raporData.net_kar));
    
    // Gelirler tablosu
    let gelirTableHtml = '';
    raporData.gelirler.forEach(function(item) {
        const yuzde = raporData.toplam_gelir > 0 ? ((item.tutar / raporData.toplam_gelir) * 100).toFixed(1) : 0;
        gelirTableHtml += `
            <tr>
                <td>${item.kategori}</td>
                <td class="text-end">${formatMoney(item.tutar)}</td>
                <td class="text-end">%${yuzde}</td>
            </tr>
        `;
    });
    $('#printGelirTableBody').html(gelirTableHtml);
    
    // Giderler tablosu
    let giderTableHtml = '';
    raporData.giderler.forEach(function(item) {
        const yuzde = raporData.toplam_gider > 0 ? ((item.tutar / raporData.toplam_gider) * 100).toFixed(1) : 0;
        giderTableHtml += `
            <tr>
                <td>${item.kategori}</td>
                <td class="text-end">${formatMoney(item.tutar)}</td>
                <td class="text-end">%${yuzde}</td>
            </tr>
        `;
    });
    $('#printGiderTableBody').html(giderTableHtml);
    
    // Detaylı hareketler tablosu
    let detayTableHtml = '';
    if (raporData.detaylar && raporData.detaylar.length > 0) {
        raporData.detaylar.forEach(function(item) {
            detayTableHtml += `
                <tr>
                    <td>${formatDate(item.tarih)}</td>
                    <td>${item.tip_display}</td>
                    <td>${item.aciklama}</td>
                    <td>${item.kategori}</td>
                    <td class="text-end">${formatMoney(item.tutar)}</td>
                </tr>
            `;
        });
    } else {
        detayTableHtml = '<tr><td colspan="5" class="text-center">Bu dönemde hareket bulunmamaktadır</td></tr>';
    }
    $('#printDetayTableBody').html(detayTableHtml);
    
    // Firma bilgilerini al (asenkron)
    $.get('../../api/firma/bilgiler.php', function(response) {
        if (response.success) {
            const firma = response.data;
            $('#printRaporFirmaAdi').text(firma.firma_adi);
            $('#printRaporFirmaAdres').text(firma.adres || '');
            $('#printRaporFirmaTel').text(firma.telefon || '');
            console.log('Firma bilgileri yüklendi:', firma);
        } else {
            console.error('Firma bilgileri yüklenemedi:', response.message);
            // Varsayılan değerler
            $('#printRaporFirmaAdi').text('Firma Adı');
            $('#printRaporFirmaAdres').text('Adres');
            $('#printRaporFirmaTel').text('Telefon');
        }
    }).fail(function(xhr, status, error) {
        console.error('Firma bilgileri API hatası:', status, error);
        // Varsayılan değerler
        $('#printRaporFirmaAdi').text('Firma Adı');
        $('#printRaporFirmaAdres').text('Adres');
        $('#printRaporFirmaTel').text('Telefon');
    });
}

// Helper fonksiyonlar
function formatMoney(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0,00 ₺';
    }
    
    const num = parseFloat(amount);
    return num.toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' ₺';
}

function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}

function showError(message) {
    // Bootstrap toast kullan
    const toastHtml = `
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle me-2"></i>${message || 'Bir hata oluştu!'}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Toast container'ı oluştur veya bul
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Toast'ı ekle
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Son eklenen toast'ı göster
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Toast gösterildikten sonra DOM'dan kaldır
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
</script>
