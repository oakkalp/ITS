<?php
$page_title = 'Çek Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('cekler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-file-earmark-check me-2"></i>Çek Yönetimi</h5>
    <?php if (has_permission('cekler', 'yazma')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cekModal" onclick="yeniCek()">
        <i class="bi bi-plus-circle me-2"></i>Yeni Çek Ekle
    </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="cekTable">
            <thead>
                <tr>
                    <th>Çek No</th>
                    <th>Tip</th>
                    <th>Cari/Kişi</th>
                    <th>Tutar</th>
                    <th>Vade Tarihi</th>
                    <th>Banka</th>
                    <th>Şube</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Çek Modal -->
<div class="modal fade" id="cekModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Çek Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cekForm">
                <input type="hidden" id="cek_id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Çek Tipi *</label>
                            <select class="form-select" name="cek_tipi" required>
                                <option value="alinan">Alınan Çek</option>
                                <option value="verilen">Verilen Çek</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Çek No *</label>
                            <input type="text" class="form-control" name="cek_no" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cari</label>
                            <select class="form-select" name="cari_id" id="cari_id">
                                <option value="">Cari Dışı</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="cari_disi_fields">
                            <label class="form-label">Kişi/Şirket Adı *</label>
                            <input type="text" class="form-control" name="cari_disi_kisi" placeholder="Örn: Ahmet Yılmaz">
                        </div>
                        <div class="col-md-6" id="cek_kaynagi_field">
                            <label class="form-label">Çek Kaynağı</label>
                            <select class="form-select" name="cek_kaynagi">
                                <option value="takas">Takas</option>
                                <option value="ciro">Ciro</option>
                                <option value="verilen">Verilen</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tutar (₺) *</label>
                            <input type="number" step="0.01" class="form-control" name="tutar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banka *</label>
                            <input type="text" class="form-control" name="banka_adi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şube</label>
                            <input type="text" class="form-control" name="sube">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vade Tarihi *</label>
                            <input type="date" class="form-control" name="vade_tarihi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="durum">
                                <option value="portfoy">Portföyde</option>
                                <option value="ciro">Ciroda</option>
                                <option value="tahsil">Tahsil Edildi</option>
                                <option value="odendi">Ödendi</option>
                                <option value="iade">İade</option>
                                <option value="iptal">İptal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="aciklama" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
let table;

$(document).ready(function() {
    loadCariler();
    
    table = $('#cekTable').DataTable({
        ajax: {
            url: '../../api/cekler/list.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'cek_no' },
            { 
                data: 'cek_tipi',
                render: function(data) {
                    if (data == 'alinan') {
                        return '<span class="badge bg-success">Alınan</span>';
                    } else if (data == 'verilen') {
                        return '<span class="badge bg-warning">Verilen</span>';
                    }
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    if (row.cari_disi_kisi && row.cari_disi_kisi.trim() !== '') {
                        return '<span class="text-info">' + row.cari_disi_kisi + '</span>';
                    } else if (row.cari_unvan) {
                        return row.cari_unvan;
                    } else {
                        return '<span class="text-muted">-</span>';
                    }
                }
            },
            { 
                data: 'tutar',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'vade_tarihi',
                render: function(data) {
                    const vade = new Date(data);
                    const bugun = new Date();
                    const kalan = Math.ceil((vade - bugun) / (1000 * 60 * 60 * 24));
                    
                    let color = 'text-dark';
                    if (kalan < 0) color = 'text-danger';
                    else if (kalan <= 7) color = 'text-warning';
                    
                    return `<span class="${color}">${formatDate(data)} (${kalan} gün)</span>`;
                }
            },
            { data: 'banka_adi' },
            { data: 'sube' },
            { 
                data: 'durum',
                render: function(data) {
                    const durumlar = {
                        'portfoy': '<span class="badge bg-info">Portföyde</span>',
                        'bankada': '<span class="badge bg-primary">Bankada</span>',
                        'tahsil_edildi': '<span class="badge bg-success">Tahsil Edildi</span>',
                        'ciroda': '<span class="badge bg-warning">Ciroda</span>',
                        'iade': '<span class="badge bg-danger">İade</span>'
                    };
                    return durumlar[data] || data;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let buttons = '';
                    <?php if (has_permission('cekler', 'guncelleme')): ?>
                    buttons += '<button class="btn btn-sm btn-warning" onclick="duzenle(' + data.id + ')"><i class="bi bi-pencil"></i></button> ';
                    <?php endif; ?>
                    <?php if (has_permission('cekler', 'silme')): ?>
                    buttons += '<button class="btn btn-sm btn-danger" onclick="sil(' + data.id + ')"><i class="bi bi-trash"></i></button>';
                    <?php endif; ?>
                    return buttons;
                }
            }
        ],
        order: [[4, 'asc']]
    });
    
    $('#cekForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function loadCariler() {
    $.get('../../api/cariler/list.php', function(response) {
        if (response.success) {
            let html = '<option value="">Cari Dışı</option>';
            response.data.forEach(function(cari) {
                html += `<option value="${cari.id}">${cari.unvan}</option>`;
            });
            $('#cari_id').html(html);
        }
    });
}

// Cari seçimi değiştiğinde
$('#cari_id').on('change', function() {
    const cariId = $(this).val();
    if (cariId) {
        // Cari seçildi - cari dışı alanları gizle
        $('#cari_disi_fields').hide();
        $('#cek_kaynagi_field').hide();
        $('[name="cari_disi_kisi"]').removeAttr('required');
        
        // Cari bilgilerini otomatik doldur
        loadCariBilgileri(cariId);
    } else {
        // Cari dışı seçildi - cari dışı alanları göster
        $('#cari_disi_fields').show();
        $('#cek_kaynagi_field').show();
        $('[name="cari_disi_kisi"]').attr('required', 'required');
    }
});

// Cari bilgilerini yükle ve otomatik doldur
function loadCariBilgileri(cariId) {
    $.get('../../api/cariler/get.php?id=' + cariId, function(response) {
        if (response.success) {
            const cari = response.data;
            // Cari bilgilerini form alanlarına otomatik doldur
            // Örnek: telefon, email, adres gibi alanlar varsa doldur
            console.log('Cari bilgileri yüklendi:', cari);
        }
    }).fail(function() {
        console.log('Cari bilgileri yüklenemedi');
    });
}

function yeniCek() {
    $('#cek_id').val('');
    $('#cekForm')[0].reset();
    $('#modalTitle').text('Yeni Çek Ekle');
    
    // Varsayılan olarak cari dışı alanları göster
    $('#cari_disi_fields').show();
    $('#cek_kaynagi_field').show();
    $('[name="cari_disi_kisi"]').attr('required', 'required');
}

function duzenle(id) {
    $.get('../../api/cekler/get.php?id=' + id, function(response) {
        if (response.success) {
            const cek = response.data;
            $('#cek_id').val(cek.id);
            $('[name="cek_tipi"]').val(cek.cek_tipi);
            $('[name="cek_no"]').val(cek.cek_no);
            $('[name="cari_id"]').val(cek.cari_id);
            $('[name="tutar"]').val(cek.tutar);
            $('[name="banka_adi"]').val(cek.banka_adi);
            $('[name="sube"]').val(cek.sube);
            $('[name="vade_tarihi"]').val(cek.vade_tarihi);
            $('[name="durum"]').val(cek.durum);
            $('[name="aciklama"]').val(cek.aciklama);
            
            // Cari dışı çek alanları
            if (cek.cari_disi_kisi && cek.cari_disi_kisi.trim() !== '') {
                $('[name="cari_disi_kisi"]').val(cek.cari_disi_kisi);
                $('[name="cek_kaynagi"]').val(cek.cek_kaynagi);
                $('#cari_disi_fields').show();
                $('#cek_kaynagi_field').show();
            } else {
                $('#cari_disi_fields').hide();
                $('#cek_kaynagi_field').hide();
            }
            
            $('#modalTitle').text('Çek Düzenle');
            $('#cekModal').modal('show');
        }
    });
}

function kaydet() {
    const formData = $('#cekForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    // banka alanını banka_adi olarak eşleştir
    if (data.banka) {
        data.banka_adi = data.banka;
        delete data.banka;
    }
    
    console.log('Çek kaydetme - Form verisi:', data);
    
    // Cari dışı çek kontrolü
    if (!data.cari_id || data.cari_id === '' || data.cari_id === '0') {
        // Cari dışı çek için kişi adı kontrolü - form'dan al
        const cariDisiKisi = $('[name="cari_disi_kisi"]').val();
        if (!cariDisiKisi || cariDisiKisi.trim() === '') {
            showError('Cari dışı çek için kişi/şirket adı gerekli');
            return;
        }
        data.cari_id = null; // NULL olarak gönder
        data.cari_disi_kisi = cariDisiKisi;
        data.cek_kaynagi = $('[name="cek_kaynagi"]').val() || '';
    } else {
        data.cari_disi_kisi = '';
        data.cek_kaynagi = '';
    }
    
    const url = data.id ? '../../api/cekler/update.php' : '../../api/cekler/create.php';
    
    $.ajax({
        url: url,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('Çek API Response:', response);
            if (response.success) {
                showSuccess(response.message);
                $('#cekModal').modal('hide');
                table.ajax.reload();
            } else {
                console.error('Çek API Success false:', response);
                showError(response.message || 'İşlem başarısız');
            }
        },
        error: function(xhr) {
            console.error('Çek AJAX Error Status:', xhr.status);
            console.error('Çek AJAX Error Response:', xhr.responseText);
            let errorMessage = 'Bir hata oluştu!';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMessage = response.message;
                }
            } catch (e) {
                errorMessage = xhr.responseText || 'Sunucu hatası';
            }
            
            showError(errorMessage);
        }
    });
}

function sil(id) {
    confirmDelete(function() {
        $.ajax({
            url: '../../api/cekler/delete.php',
            method: 'POST',
            data: {
                id: id,
                _method: 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    table.ajax.reload();
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Çek silme hatası:', error);
                showError('Çek silinirken hata oluştu');
            }
        });
    });
}
</script>

