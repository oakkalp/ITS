<?php
$page_title = 'Stok Hareket Raporu';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-graph-up me-2"></i>Stok Hareket Raporu</h5>
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
                <label class="form-label">Ürün Seçin</label>
                <select class="form-select" id="urunSelect">
                    <option value="">Tüm Ürünler</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Hareket Tipi</label>
                <select class="form-select" id="hareketTipi">
                    <option value="">Tümü</option>
                    <option value="giris">Giriş</option>
                    <option value="cikis">Çıkış</option>
                    <option value="alis">Alış Faturası</option>
                    <option value="satis">Satış Faturası</option>
                    <option value="manuel">Manuel Düzeltme</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="baslangicTarihi">
            </div>
            <div class="col-md-2">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="bitisTarihi">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary d-block w-100" onclick="loadHareketRaporu()">
                    <i class="bi bi-search me-2"></i>Yükle
                </button>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-secondary d-block w-100" onclick="resetFilters()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Özet Bilgiler -->
<div class="card mb-3" id="ozetCard" style="display: none;">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0">Hareket Özeti</h6>
    </div>
    <div class="card-body">
        <div class="row" id="ozetBilgileri">
            <!-- Özet bilgileri buraya yüklenecek -->
        </div>
    </div>
</div>

<!-- Hareket Tablosu -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Stok Hareketleri</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped" id="hareketTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 10%;">Tarih</th>
                        <th style="width: 20%;">Ürün</th>
                        <th style="width: 15%;">Hareket Tipi</th>
                        <th style="width: 12%;">Belge No</th>
                        <th style="width: 10%;">Miktar</th>
                        <th style="width: 10%;">Birim Fiyat</th>
                        <th style="width: 10%;">Toplam</th>
                        <th style="width: 13%;">Kalan Stok</th>
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
        <h2>STOK HAREKET RAPORU</h2>
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
                <p><strong>Tarih Aralığı:</strong> <span id="printRaporTarihAraligi"></span></p>
                <p><strong>Ürün:</strong> <span id="printRaporUrun"></span></p>
            </div>
            <div class="col-6">
                <p><strong>Toplam Giriş:</strong> <span id="printRaporToplamGiris"></span></p>
                <p><strong>Toplam Çıkış:</strong> <span id="printRaporToplamCikis"></span></p>
                <p><strong>Net Hareket:</strong> <span id="printRaporNetHareket"></span></p>
            </div>
        </div>
    </div>
    
    <table class="table table-bordered" id="printRaporTable">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Ürün</th>
                <th>Hareket Tipi</th>
                <th>Belge No</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
                <th>Toplam</th>
                <th>Kalan Stok</th>
            </tr>
        </thead>
        <tbody id="printRaporTableBody"></tbody>
    </table>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printRaporArea, #printRaporArea * {
        visibility: visible !important;
    }
    #printRaporArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white !important;
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
        width: 100% !important;
    }
    .table th, .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
        background: white !important;
        color: black !important;
        font-size: 12px !important;
    }
    .table th {
        background: #f8f9fa !important;
        font-weight: bold !important;
    }
    .text-end {
        text-align: right !important;
    }
}

/* Normal görünüm için */
#printRaporArea {
    display: none;
}
</style>

<?php require_once '../../includes/footer.php'; ?>

<script>
let hareketData = null;
let ozetData = null;

$(document).ready(function() {
    loadUrunler();
    setDefaultDates();
});

function setDefaultDates() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#baslangicTarihi').val(firstDay.toISOString().split('T')[0]);
    $('#bitisTarihi').val(today.toISOString().split('T')[0]);
}

function resetFilters() {
    $('#urunSelect').val('');
    $('#hareketTipi').val('');
    setDefaultDates();
}

function loadUrunler() {
    $.get('../../api/stok/list.php', function(response) {
        if (response.success) {
            let html = '<option value="">Tüm Ürünler</option>';
            response.data.forEach(function(urun) {
                html += `<option value="${urun.id}">${urun.urun_adi}</option>`;
            });
            $('#urunSelect').html(html);
        }
    });
}

function loadHareketRaporu() {
    const urunId = $('#urunSelect').val();
    const hareketTipi = $('#hareketTipi').val();
    const baslangicTarihi = $('#baslangicTarihi').val();
    const bitisTarihi = $('#bitisTarihi').val();
    
    console.log('=== HAREKET RAPORU YÜKLENİYOR ===');
    console.log('Ürün ID:', urunId);
    console.log('Hareket Tipi:', hareketTipi);
    console.log('Başlangıç Tarihi:', baslangicTarihi);
    console.log('Bitiş Tarihi:', bitisTarihi);
    
    if (!baslangicTarihi || !bitisTarihi) {
        showError('Lütfen tarih aralığını seçin');
        return;
    }
    
    if (baslangicTarihi > bitisTarihi) {
        showError('Başlangıç tarihi bitiş tarihinden büyük olamaz');
        return;
    }
    
    // Loading göster
    $('#hareketTable tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Yükleniyor...</span></div></td></tr>');
    
    let url = `../../api/stok/hareket-raporu.php?baslangic=${baslangicTarihi}&bitis=${bitisTarihi}`;
    if (urunId) url += `&urun_id=${urunId}`;
    if (hareketTipi) url += `&hareket_tipi=${hareketTipi}`;
    
    console.log('API URL:', url);
    
    $.get(url, function(response) {
        console.log('API Yanıtı:', response);
        
        if (response.success) {
            hareketData = response.data;
            ozetData = response.ozet;
            console.log('Hareket Verisi:', hareketData);
            console.log('Özet Verisi:', ozetData);
            
            displayHareketRaporu(response.data);
            displayOzet(response.ozet);
        } else {
            console.error('API Hatası:', response.message);
            showError(response.message || 'Rapor yüklenirken hata oluştu');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Hatası:', status, error);
        console.error('XHR Response:', xhr.responseText);
        showError('Rapor yüklenirken hata oluştu');
    });
}

function displayHareketRaporu(data) {
    console.log('=== HAREKET RAPORU GÖSTERİLİYOR ===');
    console.log('Veri uzunluğu:', data.length);
    console.log('Veri:', data);
    
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="8" class="text-center">Bu kriterlere uygun hareket bulunamadı</td></tr>';
    } else {
        data.forEach(function(item) {
            const hareketClass = item.hareket_tipi.includes('giris') || item.hareket_tipi.includes('alis') || item.hareket_tipi.includes('manuel_giris') ? 'text-success' : 'text-danger';
            const miktarClass = item.miktar > 0 ? 'text-success' : 'text-danger';
            
            // Miktar gösterimini düzelt
            let miktarDisplay = '';
            if (item.miktar > 0) {
                miktarDisplay = `+${item.miktar}`;
            } else {
                miktarDisplay = `${item.miktar}`;
            }
            
            // Kalan stok formatını düzelt (ondalık kısmı kaldır)
            const kalanStok = Math.round(parseFloat(item.kalan_stok));
            
            html += `
                <tr>
                    <td>${formatDate(item.tarih)}</td>
                    <td>${item.urun_adi}</td>
                    <td><span class="badge ${hareketClass}">${item.hareket_tipi_display}</span></td>
                    <td>${item.belge_no || '-'}</td>
                    <td class="text-end ${miktarClass} fw-bold">${miktarDisplay}</td>
                    <td class="text-end">${formatMoney(item.birim_fiyat)}</td>
                    <td class="text-end">${formatMoney(item.toplam)}</td>
                    <td class="text-end fw-bold">${kalanStok}</td>
                </tr>
            `;
        });
    }
    
    console.log('HTML oluşturuldu, satır sayısı:', data.length);
    $('#hareketTable tbody').html(html);
}

function displayOzet(ozet) {
    if (!ozet) return;
    
    $('#ozetCard').show();
    
    const html = `
        <div class="col-md-3">
            <p><strong>Toplam Giriş:</strong> <span class="text-success fw-bold">${ozet.toplam_giris}</span></p>
            <p><strong>Toplam Çıkış:</strong> <span class="text-danger fw-bold">${ozet.toplam_cikis}</span></p>
        </div>
        <div class="col-md-3">
            <p><strong>Net Hareket:</strong> <span class="fw-bold ${ozet.net_hareket >= 0 ? 'text-success' : 'text-danger'}">${ozet.net_hareket >= 0 ? '+' : ''}${ozet.net_hareket}</span></p>
            <p><strong>Toplam İşlem:</strong> <span class="fw-bold">${ozet.toplam_islem}</span></p>
        </div>
        <div class="col-md-3">
            <p><strong>Toplam Değer:</strong> <span class="fw-bold">${formatMoney(ozet.toplam_deger)}</span></p>
            <p><strong>Ortalama Fiyat:</strong> <span class="fw-bold">${formatMoney(ozet.ortalama_fiyat)}</span></p>
        </div>
        <div class="col-md-3">
            <p><strong>Başlangıç Stok:</strong> <span class="fw-bold">${ozet.baslangic_stok}</span></p>
            <p><strong>Son Stok:</strong> <span class="fw-bold">${ozet.son_stok}</span></p>
        </div>
    `;
    
    $('#ozetBilgileri').html(html);
}

function printRapor() {
    if (!hareketData) {
        showError('Önce rapor yükleyin');
        return;
    }
    
    // Print area'yı görünür yap
    $('#printRaporArea').show();
    
    fillPrintRaporArea();
    
    // Firma bilgileri yüklendikten sonra yazdır
    setTimeout(function() {
        window.print();
        
        // Yazdırma sonrası print area'yı gizle
        setTimeout(function() {
            $('#printRaporArea').hide();
        }, 1000);
    }, 500);
}

function exportExcel() {
    if (!hareketData) {
        showError('Önce rapor yükleyin');
        return;
    }
    
    // Excel export için CSV formatında veri hazırla
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Tarih,Ürün,Hareket Tipi,Belge No,Miktar,Birim Fiyat,Toplam,Kalan Stok\n";
    
    hareketData.forEach(function(item) {
        csvContent += `${formatDate(item.tarih)},${item.urun_adi},${item.hareket_tipi_display},${item.belge_no || ''},${item.miktar},${item.birim_fiyat},${item.toplam},${item.kalan_stok}\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `stok_hareket_raporu_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function fillPrintRaporArea() {
    if (!hareketData || !ozetData) {
        console.log('Hareket verisi veya özet verisi yok');
        return;
    }
    
    console.log('Print area dolduruluyor...');
    console.log('Hareket verisi:', hareketData);
    console.log('Özet verisi:', ozetData);
    
    // Önce tablo içeriğini doldur (bu hemen yapılabilir)
    let tableHtml = '';
    hareketData.forEach(function(item) {
        // Kalan stok formatını düzelt (ondalık kısmı kaldır)
        const kalanStok = Math.round(parseFloat(item.kalan_stok));
        
        tableHtml += `
            <tr>
                <td>${formatDate(item.tarih)}</td>
                <td>${item.urun_adi}</td>
                <td>${item.hareket_tipi_display}</td>
                <td>${item.belge_no || '-'}</td>
                <td class="text-end">${item.miktar > 0 ? '+' : ''}${item.miktar}</td>
                <td class="text-end">${formatMoney(item.birim_fiyat)}</td>
                <td class="text-end">${formatMoney(item.toplam)}</td>
                <td class="text-end">${kalanStok}</td>
            </tr>
        `;
    });
    $('#printRaporTableBody').html(tableHtml);
    console.log('Tablo içeriği dolduruldu, satır sayısı:', hareketData.length);
    
    // Rapor bilgilerini doldur
    $('#printRaporTarihi').text(formatDate(new Date()));
    
    const baslangic = $('#baslangicTarihi').val();
    const bitis = $('#bitisTarihi').val();
    $('#printRaporTarihAraligi').text(`${formatDate(baslangic)} - ${formatDate(bitis)}`);
    
    const urunId = $('#urunSelect').val();
    if (urunId) {
        const urunAdi = $('#urunSelect option:selected').text();
        $('#printRaporUrun').text(urunAdi);
    } else {
        $('#printRaporUrun').text('Tüm Ürünler');
    }
    
    // Özet bilgileri
    $('#printRaporToplamGiris').text(ozetData.toplam_giris);
    $('#printRaporToplamCikis').text(ozetData.toplam_cikis);
    $('#printRaporNetHareket').text((ozetData.net_hareket >= 0 ? '+' : '') + ozetData.net_hareket);
    
    console.log('Rapor bilgileri dolduruldu');
    
    // Firma bilgilerini al (asenkron)
    $.get('../../api/firma/bilgiler.php', function(response) {
        if (response.success) {
            const firma = response.data;
            $('#printRaporFirmaAdi').text(firma.firma_adi);
            $('#printRaporFirmaAdres').text(firma.adres || '');
            $('#printRaporFirmaTel').text(firma.telefon || '');
            console.log('Firma bilgileri yüklendi');
        } else {
            // Firma bilgileri alınamazsa varsayılan değerler
            $('#printRaporFirmaAdi').text('Firma Adı');
            $('#printRaporFirmaAdres').text('Adres');
            $('#printRaporFirmaTel').text('Telefon');
            console.log('Firma bilgileri alınamadı, varsayılan değerler kullanılıyor');
        }
    }).fail(function() {
        // Hata durumunda varsayılan değerler
        $('#printRaporFirmaAdi').text('Firma Adı');
        $('#printRaporFirmaAdres').text('Adres');
        $('#printRaporFirmaTel').text('Telefon');
        console.log('Firma bilgileri API hatası, varsayılan değerler kullanılıyor');
    });
}
</script>
