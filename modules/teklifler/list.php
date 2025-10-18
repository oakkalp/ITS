<?php
require_once '../../includes/header.php';
require_once '../../includes/auth.php';
require_login();
require_permission('teklifler', 'okuma');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Teklif Yönetimi</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teklifModal">
                        <i class="bi bi-plus-circle"></i> Yeni Teklif
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filtreler -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="date" class="form-control" id="filterStart" placeholder="Başlangıç Tarihi">
                        </div>
                        <div class="col-md-4">
                            <input type="date" class="form-control" id="filterEnd" placeholder="Bitiş Tarihi">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-secondary" onclick="table.ajax.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Yenile
                            </button>
                        </div>
                    </div>

                    <!-- Tablo -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="teklifTable">
                            <thead>
                                <tr>
                                    <th>Teklif No</th>
                                    <th>Teklif Başlığı</th>
                                    <th>Tarih</th>
                                    <th>Geçerlilik</th>
                                    <th>Cari/Kişi</th>
                                    <th>Tutar</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Teklif Ekleme/Düzenleme Modal -->
<div class="modal fade" id="teklifModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teklifModalTitle">Yeni Teklif</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="teklifForm">
                <div class="modal-body">
                    <input type="hidden" id="teklifId" name="id">
                    
                    <!-- Üst Bilgiler -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Teklif Başlığı *</label>
                            <input type="text" class="form-control" id="teklifBasligi" name="teklif_basligi" placeholder="Örn: ABC Şirketi - Fiyat Teklifi" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Teklif Tarihi *</label>
                            <input type="date" class="form-control" id="teklifTarihi" name="teklif_tarihi" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Geçerlilik Tarihi *</label>
                            <input type="date" class="form-control" id="gecerlilikTarihi" name="gecerlilik_tarihi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cari Seçimi</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cari_secimi" id="cariSec" value="cari" checked>
                                <label class="form-check-label" for="cariSec">Mevcut Cari</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="cari_secimi" id="cariDisiSec" value="cari_disi">
                                <label class="form-check-label" for="cariDisiSec">Cari Dışı Kişi/Kurum</label>
                            </div>
                        </div>
                    </div>

                    <!-- Cari Seçimi -->
                    <div class="row mb-3" id="cariSecimiDiv">
                        <div class="col-md-10">
                            <label class="form-label">Cari *</label>
                            <select class="form-select" id="cariId" name="cari_id">
                                <option value="">Cari Seçiniz</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#hizliCariModal">
                                <i class="bi bi-plus-circle"></i> Hızlı Ekle
                            </button>
                        </div>
                    </div>

                    <!-- Cari Dışı Bilgiler -->
                    <div class="row mb-3" id="cariDisiDiv" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label">Kişi/Kurum Adı *</label>
                            <input type="text" class="form-control" id="cariDisiKisi" name="cari_disi_kisi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="cariDisiTelefon" name="cari_disi_telefon">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="cariDisiEmail" name="cari_disi_email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" id="cariDisiAdres" name="cari_disi_adres" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Ürün Ekleme -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6>Ürünler</h6>
                            <div class="table-responsive">
                                <table class="table table-sm" id="urunTable">
                                    <thead>
                                        <tr>
                                            <th>Ürün</th>
                                            <th>Miktar</th>
                                            <th>Birim Fiyat</th>
                                            <th>KDV %</th>
                                            <th>Toplam</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody id="urunTableBody">
                                        <!-- Dinamik olarak eklenecek -->
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" onclick="addUrunRow()">
                                <i class="bi bi-plus"></i> Ürün Ekle
                            </button>
                        </div>
                    </div>

                    <!-- Toplam Bilgiler -->
                    <div class="row mb-3">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Ara Toplam:</strong></td>
                                    <td class="text-end"><span id="araToplam">0.00</span> ₺</td>
                                </tr>
                                <tr>
                                    <td><strong>KDV Toplam:</strong></td>
                                    <td class="text-end"><span id="kdvToplam">0.00</span> ₺</td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Genel Toplam:</strong></td>
                                    <td class="text-end"><strong><span id="genelToplam">0.00</span> ₺</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Açıklama -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="teklifButton">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hızlı Cari Ekleme Modal -->
<div class="modal fade" id="hizliCariModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hızlı Cari Ekleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="hizliCariForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Cari Adı *</label>
                            <input type="text" class="form-control" id="hizliCariAdi" name="unvan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="hizliCariTelefon" name="telefon">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="hizliCariEmail" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vergi No</label>
                            <input type="text" class="form-control" id="hizliCariVergiNo" name="vergi_no">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" id="hizliCariAdres" name="adres" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Cari Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
let table;
let urunCounter = 0;

$(document).ready(function() {
    loadCariler();
    
    // Modal açıldığında tarihleri otomatik doldur
    $('#teklifModal').on('show.bs.modal', function() {
        // Sadece yeni teklif için tarihleri doldur
        if ($('#teklifModalTitle').text() === 'Yeni Teklif') {
            const today = new Date();
            const gecerlilikTarihi = new Date(today);
            gecerlilikTarihi.setDate(today.getDate() + 15);
            
            $('#teklifTarihi').val(today.toISOString().split('T')[0]);
            $('#gecerlilikTarihi').val(gecerlilikTarihi.toISOString().split('T')[0]);
        }
    });
    
    table = $('#teklifTable').DataTable({
        ajax: {
            url: '../../api/teklifler/list.php',
            data: function(d) {
                d.start = $('#filterStart').val();
                d.end = $('#filterEnd').val();
            },
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'teklif_no' },
            { data: 'teklif_basligi' },
            { 
                data: 'teklif_tarihi',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { 
                data: 'gecerlilik_tarihi',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { 
                data: null,
                render: function(data) {
                    return data.cari_unvan || data.cari_disi_kisi || 'N/A';
                }
            },
            { 
                data: 'genel_toplam',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="viewTeklif(${data.id})" title="Görüntüle">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="previewTeklif(${data.id})" title="Baskı Önizlemesi">
                                <i class="bi bi-printer"></i>
                            </button>
                            <button class="btn btn-outline-success" onclick="downloadTeklif(${data.id})" title="Teklif İndir">
                                <i class="bi bi-file-earmark-code"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="sendWhatsApp(${data.id})" title="WhatsApp Gönder">
                                <i class="bi bi-whatsapp"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="editTeklif(${data.id})" title="Düzenle">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteTeklif(${data.id})" title="Sil">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Cari seçimi değişikliği
    $('input[name="cari_secimi"]').change(function() {
        if ($(this).val() === 'cari') {
            $('#cariSecimiDiv').show();
            $('#cariDisiDiv').hide();
            $('#cariId').prop('required', true);
            $('#cariDisiKisi').prop('required', false);
        } else {
            $('#cariSecimiDiv').hide();
            $('#cariDisiDiv').show();
            $('#cariId').prop('required', false);
            $('#cariDisiKisi').prop('required', true);
        }
    });

    // Form submit
    $('#teklifForm').submit(function(e) {
        e.preventDefault();
        saveTeklif();
    });

    // İlk ürün satırını ekle
    addUrunRow();
});

function loadCariler() {
    $.get('../../api/cariler/list.php', function(response) {
        if (response.success) {
            const cariler = response.data;
            let options = '<option value="">Cari Seçiniz</option>';
            cariler.forEach(cari => {
                options += `<option value="${cari.id}">${cari.unvan}</option>`;
            });
            $('#cariId').html(options);
        }
    });
}

function addUrunRow() {
    urunCounter++;
    const row = `
        <tr id="urunRow_${urunCounter}">
            <td>
                <div class="input-group">
                    <select class="form-select urun-select" name="urun_id[]" onchange="calculateRow(${urunCounter})">
                        <option value="">Ürün Seçiniz</option>
                        <option value="manuel">+ Elle Ürün Ekle</option>
                    </select>
                    <input type="text" class="form-control manuel-urun" name="manuel_urun[]" placeholder="Ürün adı" style="display:none;" onchange="calculateRow(${urunCounter})">
                </div>
            </td>
            <td>
                <input type="number" class="form-control" name="miktar[]" step="0.001" min="0" onchange="calculateRow(${urunCounter})" onkeyup="calculateRow(${urunCounter})">
            </td>
            <td>
                <input type="number" class="form-control" name="birim_fiyat[]" step="0.01" min="0" onchange="calculateRow(${urunCounter})" onkeyup="calculateRow(${urunCounter})">
            </td>
            <td>
                <select class="form-select" name="kdv_orani[]" onchange="calculateRow(${urunCounter})">
                    <option value="0">%0</option>
                    <option value="1">%1</option>
                    <option value="8">%8</option>
                    <option value="18" selected>%18</option>
                    <option value="20">%20</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control toplam-input" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeUrunRow(${urunCounter})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#urunTableBody').append(row);
    loadUrunler(urunCounter);
}

function loadUrunler(counter) {
    $.get('../../api/urunler/list.php', function(response) {
        if (response.success) {
            const urunler = response.data;
            let options = '<option value="">Ürün Seçiniz</option>';
            options += '<option value="manuel">+ Elle Ürün Ekle</option>';
            urunler.forEach(urun => {
                options += `<option value="${urun.id}" data-fiyat="${urun.satis_fiyati}">${urun.urun_adi}</option>`;
            });
            $(`#urunRow_${counter} .urun-select`).html(options);
        }
    });
}

// Elle ürün ekleme kontrolü
$(document).on('change', '.urun-select', function() {
    const selectedValue = $(this).val();
    const manuelInput = $(this).closest('.input-group').find('.manuel-urun');
    
    if (selectedValue === 'manuel') {
        $(this).hide();
        manuelInput.show().focus();
    } else {
        $(this).show();
        manuelInput.hide();
    }
});

function removeUrunRow(counter) {
    $(`#urunRow_${counter}`).remove();
    calculateTotals();
}

function calculateRow(counter) {
    const row = $(`#urunRow_${counter}`);
    const miktar = parseFloat(row.find('input[name="miktar[]"]').val()) || 0;
    const birimFiyat = parseFloat(row.find('input[name="birim_fiyat[]"]').val()) || 0;
    const kdvOrani = parseFloat(row.find('select[name="kdv_orani[]"]').val()) || 0;
    
    const araToplam = miktar * birimFiyat;
    const kdvTutari = araToplam * (kdvOrani / 100);
    const toplam = araToplam + kdvTutari;
    
    row.find('.toplam-input').val(toplam.toFixed(2));
    calculateTotals();
}

function calculateTotals() {
    let araToplam = 0;
    let kdvToplam = 0;
    
    $('#urunTableBody tr').each(function() {
        const toplam = parseFloat($(this).find('.toplam-input').val()) || 0;
        const miktar = parseFloat($(this).find('input[name="miktar[]"]').val()) || 0;
        const birimFiyat = parseFloat($(this).find('input[name="birim_fiyat[]"]').val()) || 0;
        const kdvOrani = parseFloat($(this).find('select[name="kdv_orani[]"]').val()) || 0;
        
        const satirAraToplam = miktar * birimFiyat;
        const satirKdv = satirAraToplam * (kdvOrani / 100);
        
        araToplam += satirAraToplam;
        kdvToplam += satirKdv;
    });
    
    const genelToplam = araToplam + kdvToplam;
    
    $('#araToplam').text(araToplam.toFixed(2));
    $('#kdvToplam').text(kdvToplam.toFixed(2));
    $('#genelToplam').text(genelToplam.toFixed(2));
}

function saveTeklif() {
    const formData = new FormData($('#teklifForm')[0]);
    
    // Ürün verilerini topla
    const urunler = [];
    $('#urunTableBody tr').each(function() {
        const urunId = $(this).find('select[name="urun_id[]"]').val();
        const manuelUrun = $(this).find('input[name="manuel_urun[]"]').val();
        const miktar = $(this).find('input[name="miktar[]"]').val();
        const birimFiyat = $(this).find('input[name="birim_fiyat[]"]').val();
        const kdvOrani = $(this).find('select[name="kdv_orani[]"]').val();
        
        if ((urunId || manuelUrun) && miktar && birimFiyat) {
            urunler.push({
                urun_id: urunId || null,
                manuel_urun: manuelUrun || null,
                miktar: miktar,
                birim_fiyat: birimFiyat,
                kdv_orani: kdvOrani
            });
        }
    });
    
    formData.append('urunler', JSON.stringify(urunler));
    
    $.ajax({
        url: '../../api/teklifler/save.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('API Response:', response); // Debug için
            if (response.success) {
                showSuccess(response.message);
                $('#teklifModal').modal('hide');
                table.ajax.reload();
                resetForm();
            } else {
                console.log('API Error:', response); // Debug için
                showError(response.message || response.error || 'Bilinmeyen hata oluştu');
            }
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error Status:', xhr.status);
            console.log('AJAX Error Response:', xhr.responseText);
            
            let errorMessage = 'Bir hata oluştu!';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMessage = response.message;
                } else if (response.error) {
                    errorMessage = response.error;
                }
            } catch (e) {
                errorMessage = xhr.responseText || 'Sunucu hatası';
            }
            
            showError(errorMessage);
        }
    });
}

function resetForm() {
    $('#teklifForm')[0].reset();
    $('#urunTableBody').empty();
    urunCounter = 0;
    addUrunRow();
    calculateTotals();
    
    // Modal başlığını ve buton metnini sıfırla
    $('#teklifModalTitle').text('Yeni Teklif');
    $('#teklifButton').text('Kaydet');
    
    // Cari seçimini sıfırla
    $('input[name="cari_secimi"][value="cari"]').prop('checked', true);
    $('#cariSecimiDiv').show();
    $('#cariDisiDiv').hide();
    
    // Teklif başlığını temizle
    $('#teklifBasligi').val('');
    
    // Tarihleri otomatik doldur
    const today = new Date();
    const gecerlilikTarihi = new Date(today);
    gecerlilikTarihi.setDate(today.getDate() + 15);
    
    $('#teklifTarihi').val(today.toISOString().split('T')[0]);
    $('#gecerlilikTarihi').val(gecerlilikTarihi.toISOString().split('T')[0]);
}

function viewTeklif(id) {
    window.open(`view.php?id=${id}`, '_blank');
}

function previewTeklif(id) {
    window.open(`preview.php?id=${id}`, '_blank');
}

function downloadTeklif(id) {
    // HTML dosyasını oluştur ve indir
    $.get(`../../api/teklifler/create_file.php?id=${id}`, function(response) {
        if (response.success) {
            // Dosya oluşturuldu, indirme linkini aç
            window.open(`../../temp/download.php?file=${response.filename}`, '_blank');
        } else {
            showError('Dosya oluşturulamadı');
        }
    }, 'json').fail(function() {
        showError('İndirme işlemi başarısız');
    });
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
            showError('Dosya oluşturulamadı');
        }
    }, 'json').fail(function() {
        showError('WhatsApp paylaşımı başarısız');
    });
}

function editTeklif(id) {
    // Teklif bilgilerini getir ve modal'ı doldur
    $.get('../../api/teklifler/get.php?id=' + id, function(response) {
        if (response.success) {
            const teklif = response.data;
            
            // Modal'ı doldur
            $('#teklifId').val(teklif.id);
            $('#teklifBasligi').val(teklif.teklif_basligi);
            $('#teklifTarihi').val(teklif.teklif_tarihi);
            $('#gecerlilikTarihi').val(teklif.gecerlilik_tarihi);
            $('#aciklama').val(teklif.aciklama);
            
            // Cari seçimi
            if (teklif.cari_id) {
                $('input[name="cari_secimi"][value="cari"]').prop('checked', true);
                $('#cariId').val(teklif.cari_id);
                $('#cariSecimiDiv').show();
                $('#cariDisiDiv').hide();
            } else {
                $('input[name="cari_secimi"][value="cari_disi"]').prop('checked', true);
                $('#cariDisiKisi').val(teklif.cari_disi_kisi);
                $('#cariDisiTelefon').val(teklif.cari_disi_telefon);
                $('#cariDisiEmail').val(teklif.cari_disi_email);
                $('#cariDisiAdres').val(teklif.cari_disi_adres);
                $('#cariSecimiDiv').hide();
                $('#cariDisiDiv').show();
            }
            
            // Ürünleri yükle
            loadTeklifDetaylari(id);
            
            // Modal'ı aç
            $('#teklifModalTitle').text('Teklif Düzenle');
            $('#teklifButton').text('Güncelle');
            $('#teklifModal').modal('show');
        } else {
            showError('Teklif bilgileri yüklenemedi');
        }
    });
}

function loadTeklifDetaylari(teklifId) {
    $.get('../../api/teklifler/detay.php?id=' + teklifId, function(response) {
        if (response.success) {
            // Mevcut ürün satırlarını temizle
            $('#urunTableBody').empty();
            urunCounter = 0;
            
            // Ürünleri ekle
            response.data.forEach(function(detay) {
                addUrunRow();
                const row = $(`#urunRow_${urunCounter}`);
                
                if (detay.urun_id) {
                    row.find('select[name="urun_id[]"]').val(detay.urun_id);
                } else {
                    row.find('select[name="urun_id[]"]').val('manuel');
                    row.find('input[name="manuel_urun[]"]').val(detay.aciklama).show();
                    row.find('select[name="urun_id[]"]').hide();
                }
                
                row.find('input[name="miktar[]"]').val(detay.miktar);
                row.find('input[name="birim_fiyat[]"]').val(detay.birim_fiyat);
                row.find('select[name="kdv_orani[]"]').val(detay.kdv_orani);
                
                calculateRow(urunCounter);
            });
            
            calculateTotals();
        }
    });
}

function deleteTeklif(id) {
    if (confirm('Bu teklifi silmek istediğinizden emin misiniz?')) {
        $.ajax({
            url: '../../api/teklifler/delete.php',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    table.ajax.reload();
                } else {
                    showError(response.message || response.error || 'Bilinmeyen hata oluştu');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error Status:', xhr.status);
                console.log('AJAX Error Response:', xhr.responseText);
                
                let errorMessage = 'Bir hata oluştu!';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    } else if (response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    errorMessage = xhr.responseText || 'Sunucu hatası';
                }
                
                showError(errorMessage);
            }
        });
    }
}

// Hızlı Cari Ekleme
$('#hizliCariForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '../../api/cariler/create.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showSuccess('Cari başarıyla eklendi');
                $('#hizliCariModal').modal('hide');
                $('#hizliCariForm')[0].reset();
                
                // Cari listesini yenile
                loadCariler();
                
                // Yeni eklenen cariyi seç
                setTimeout(function() {
                    $('#cariId').val(response.data.id);
                }, 500);
            } else {
                showError(response.message || 'Cari eklenirken hata oluştu');
            }
        },
        error: function(xhr, status, error) {
            let errorMessage = 'Bir hata oluştu!';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || response.error || errorMessage;
            } catch (e) {
                errorMessage = xhr.responseText || errorMessage;
            }
            showError(errorMessage);
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
