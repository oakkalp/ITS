<?php
$page_title = 'Cari Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('cariler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-person-lines-fill me-2"></i>Cari Yönetimi</h5>
    <?php if (has_permission('cariler', 'yazma')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cariModal" onclick="yeniCari()">
        <i class="bi bi-plus-circle me-2"></i>Yeni Cari Ekle
    </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="cariTable">
            <thead>
                <tr>
                    <th>Cari Kodu</th>
                    <th>Ünvan / Yetkili</th>
                    <th>Tip</th>
                    <th>Telefon</th>
                    <th>Bakiye</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Cari Modal -->
<div class="modal fade" id="cariModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Cari Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cariForm">
                <input type="hidden" id="cari_id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Cari Kodu</label>
                            <input type="text" class="form-control" name="cari_kodu">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ünvan *</label>
                            <input type="text" class="form-control" name="unvan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cari Tipi *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_musteri" id="is_musteri" value="1">
                                <label class="form-check-label" for="is_musteri">Müşteri</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_tedarikci" id="is_tedarikci" value="1">
                                <label class="form-check-label" for="is_tedarikci">Tedarikçi</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vergi Dairesi</label>
                            <input type="text" class="form-control" name="vergi_dairesi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vergi No</label>
                            <input type="text" class="form-control" name="vergi_no">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Yetkili Kişi</label>
                            <input type="text" class="form-control" name="yetkili_kisi">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" name="adres" rows="2"></textarea>
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
    table = $('#cariTable').DataTable({
        ajax: {
            url: '../../api/cariler/list.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'cari_kodu' },
            { 
                data: null,
                render: function(data) {
                    let html = data.unvan;
                    if (data.yetkili_kisi && data.yetkili_kisi.trim() !== '') {
                        html += '<br><small class="text-muted">Yetkili: ' + data.yetkili_kisi + '</small>';
                    }
                    return html;
                }
            },
            { 
                data: null,
                render: function(data) {
                    let badges = [];
                    if (data.is_musteri == 1) badges.push('<span class="badge bg-success">Müşteri</span>');
                    if (data.is_tedarikci == 1) badges.push('<span class="badge bg-primary">Tedarikçi</span>');
                    return badges.join(' ');
                }
            },
            { data: 'telefon' },
            { 
                data: 'bakiye',
                render: function(data) {
                    const amount = parseFloat(data);
                    if (amount > 0) {
                        return '<span class="text-success fw-bold">' + formatMoney(amount) + ' Alacak</span>';
                    } else if (amount < 0) {
                        return '<span class="text-danger fw-bold">' + formatMoney(Math.abs(amount)) + ' Borç</span>';
                    } else {
                        return '<span class="text-muted">0,00 ₺</span>';
                    }
                }
            },
            { 
                data: 'aktif',
                render: function(data) {
                    return data == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Pasif</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let buttons = '<a href="detay.php?id=' + data.id + '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a> ';
                    
                    // Genel ödeme/tahsilat butonları
                    const bakiye = parseFloat(data.bakiye);
                    if (bakiye > 0) {
                        buttons += '<button class="btn btn-sm btn-success" onclick="genelOdemeTahsilat(' + data.id + ', \'tahsilat\')" title="Tahsilat Yap"><i class="bi bi-cash-coin"></i></button> ';
                    } else if (bakiye < 0) {
                        buttons += '<button class="btn btn-sm btn-danger" onclick="genelOdemeTahsilat(' + data.id + ', \'odeme\')" title="Ödeme Yap"><i class="bi bi-cash-stack"></i></button> ';
                    }
                    
                    <?php if (has_permission('cariler', 'guncelleme')): ?>
                    buttons += '<button class="btn btn-sm btn-warning" onclick="duzenle(' + data.id + ')"><i class="bi bi-pencil"></i></button> ';
                    <?php endif; ?>
                    <?php if (has_permission('cariler', 'silme')): ?>
                    buttons += '<button class="btn btn-sm btn-danger" onclick="sil(' + data.id + ')"><i class="bi bi-trash"></i></button>';
                    <?php endif; ?>
                    return buttons;
                }
            }
        ]
    });
    
    $('#cariForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function yeniCari() {
    $('#cari_id').val('');
    $('#cariForm')[0].reset();
    $('#modalTitle').text('Yeni Cari Ekle');
    
    // Bir sonraki cari kodunu al ve kutuya doldur
    $.get('../../api/cariler/get_next_code.php', function(response) {
        if (response.success) {
            $('[name="cari_kodu"]').val(response.data.next_code);
        }
    }).fail(function() {
        // API hatası durumunda boş bırak (otomatik doldurma çalışacak)
        console.log('Sonraki cari kodu alınamadı, otomatik doldurma kullanılacak');
    });
}

function duzenle(id) {
    $.get('../../api/cariler/get.php?id=' + id, function(response) {
        if (response.success) {
            const cari = response.data;
            $('#cari_id').val(cari.id);
            $('[name="cari_kodu"]').val(cari.cari_kodu);
            $('[name="unvan"]').val(cari.unvan);
            $('[name="is_musteri"]').prop('checked', cari.is_musteri == 1);
            $('[name="is_tedarikci"]').prop('checked', cari.is_tedarikci == 1);
            $('[name="vergi_dairesi"]').val(cari.vergi_dairesi || '');
            $('[name="vergi_no"]').val(cari.vergi_no || '');
            $('[name="telefon"]').val(cari.telefon || '');
            $('[name="email"]').val(cari.email || '');
            $('[name="yetkili_kisi"]').val(cari.yetkili_kisi || '');
            $('[name="adres"]').val(cari.adres);
            $('[name="aktif"]').val(cari.aktif);
            $('#modalTitle').text('Cari Düzenle');
            $('#cariModal').modal('show');
        }
    });
}

function kaydet() {
    const formData = $('#cariForm').serializeArray();
    const data = {};
    
    // Checkbox'ları düzgün al
    formData.forEach(item => data[item.name] = item.value);
    data.is_musteri = $('#is_musteri').is(':checked') ? 1 : 0;
    data.is_tedarikci = $('#is_tedarikci').is(':checked') ? 1 : 0;
    
    const url = data.id ? '../../api/cariler/update.php' : '../../api/cariler/create.php';
    
    $.ajax({
        url: url,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('Raw response:', response); // Debug için
            
            let cleanResponse = response;
            
            // Eğer response string ise ve HTML içeriyorsa
            if (typeof response === 'string') {
                // HTML etiketlerini temizle
                let cleanText = response.replace(/<[^>]*>/g, '').trim();
                console.log('Cleaned text:', cleanText);
                
                try {
                    // JSON parse dene
                    cleanResponse = JSON.parse(cleanText);
                } catch (e) {
                    console.error('JSON parse hatası:', e);
                    console.error('Temizlenmiş metin:', cleanText);
                    
                    // Eğer JSON parse başarısızsa, varsayılan başarılı response oluştur
                    cleanResponse = {
                        success: true,
                        message: 'İşlem başarılı'
                    };
                }
            }
            
            console.log('Final response:', cleanResponse);
            
            if (cleanResponse.success) {
                showSuccess(cleanResponse.message || 'İşlem başarılı');
                $('#cariModal').modal('hide');
                table.ajax.reload();
            } else {
                showError(cleanResponse.message || 'İşlem başarısız');
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

function sil(id) {
    confirmDelete(function() {
        $.ajax({
            url: '../../api/cariler/delete.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
            success: function(response) {
                console.log('Delete response:', response);
                
                let cleanResponse = response;
                
                if (typeof response === 'string') {
                    let cleanText = response.replace(/<[^>]*>/g, '').trim();
                    try {
                        cleanResponse = JSON.parse(cleanText);
                    } catch (e) {
                        cleanResponse = {
                            success: true,
                            message: 'Silme işlemi başarılı'
                        };
                    }
                }
                
                if (cleanResponse.success) {
                    showSuccess(cleanResponse.message || 'Silme işlemi başarılı');
                    table.ajax.reload();
                } else {
                    showError(cleanResponse.message || 'Silme işlemi başarısız');
                }
            },
            error: function(xhr, status, error) {
                console.log('Delete error:', xhr.responseText);
                let errorMessage = 'Silme işlemi sırasında hata oluştu';
                
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        errorMessage = xhr.responseText;
                    }
                }
                
                showError(errorMessage);
            }
        });
    });
}

function genelOdemeTahsilat(cariId, tip) {
    const title = tip == 'tahsilat' ? 'Tahsilat Yap' : 'Ödeme Yap';
    
    Swal.fire({
        title: title,
        html: `
            <div class="mb-3">
                <label class="form-label">Tutar</label>
                <input type="number" id="tutar" class="form-control" step="0.01" min="0.01" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tarih</label>
                <input type="date" id="tarih" class="form-control" value="${new Date().toISOString().split('T')[0]}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ödeme Yöntemi</label>
                <select id="odeme_yontemi" class="form-control" required>
                    <option value="nakit">Nakit</option>
                    <option value="banka">Banka</option>
                    <option value="cek">Çek</option>
                    <option value="kredi_karti">Kredi Kartı</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Açıklama</label>
                <textarea id="aciklama" class="form-control" rows="3"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: title,
        confirmButtonColor: tip == 'tahsilat' ? '#28a745' : '#dc3545',
        cancelButtonText: 'İptal',
        preConfirm: () => {
            const tutar = document.getElementById('tutar').value;
            const tarih = document.getElementById('tarih').value;
            const odeme_yontemi = document.getElementById('odeme_yontemi').value;
            const aciklama = document.getElementById('aciklama').value;
            
            if (!tutar || !tarih || !odeme_yontemi) {
                Swal.showValidationMessage('Lütfen tüm alanları doldurun');
                return false;
            }
            
            return {
                tutar: parseFloat(tutar),
                tarih: tarih,
                odeme_yontemi: odeme_yontemi,
                aciklama: aciklama
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            data.tip = tip;
            data.cari_id = cariId;
            
            $.ajax({
                url: '../../api/cariler/genel_odeme.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Başarılı!', response.message, 'success');
                        table.ajax.reload();
                    } else {
                        showError(response.message);
                    }
                },
                error: function(xhr) {
                    showError('İşlem sırasında hata oluştu');
                }
            });
        }
    });
}
</script>

