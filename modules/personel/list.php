<?php
$page_title = 'Personel Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('personel', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-person-badge me-2"></i>Personel Yönetimi</h5>
    <?php if (has_permission('personel', 'yazma')): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#personelModal" onclick="yeniPersonel()">
        <i class="bi bi-plus-circle me-2"></i>Yeni Personel Ekle
    </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="personelTable">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Telefon</th>
                    <th>Adres</th>
                    <th>Pozisyon</th>
                    <th>Maaş</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Personel Modal -->
<div class="modal fade" id="personelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Personel Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="personelForm">
                <input type="hidden" id="personel_id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" class="form-control" name="ad_soyad" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon">
                        </div>
                        <div class="col-6">
                            <label class="form-label">TC Kimlik No</label>
                            <input type="text" class="form-control" name="tc_no" maxlength="11">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Pozisyon</label>
                            <input type="text" class="form-control" name="pozisyon">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Maaş (₺)</label>
                            <input type="number" step="0.01" class="form-control" name="maas" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">İşe Giriş Tarihi</label>
                            <input type="date" class="form-control" name="ise_giris_tarihi">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
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
    table = $('#personelTable').DataTable({
        ajax: {
            url: '../../api/personel/list.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'ad_soyad' },
            { data: 'telefon' },
            { data: 'adres' },
            { data: 'gorev' },
            { 
                data: 'maas',
                render: function(data) {
                    return formatMoney(data);
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
                    let buttons = '';
                    <?php if (has_permission('personel', 'guncelleme')): ?>
                    buttons += '<button class="btn btn-sm btn-warning" onclick="duzenle(' + data.id + ')"><i class="bi bi-pencil"></i></button> ';
                    <?php endif; ?>
                    <?php if (has_permission('personel', 'silme')): ?>
                    buttons += '<button class="btn btn-sm btn-danger" onclick="sil(' + data.id + ')"><i class="bi bi-trash"></i></button>';
                    <?php endif; ?>
                    return buttons;
                }
            }
        ]
    });
    
    $('#personelForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function yeniPersonel() {
    $('#personel_id').val('');
    $('#personelForm')[0].reset();
    $('#modalTitle').text('Yeni Personel Ekle');
}

function duzenle(id) {
    console.log('Personel düzenleme - ID:', id);
    $.get('../../api/personel/get.php?id=' + id, function(response) {
        console.log('Personel get API yanıtı:', response);
        if (response.success) {
            const p = response.data;
            console.log('Personel verisi:', p);
            console.log('Görev/Pozisyon:', p.gorev);
            
            $('#personel_id').val(p.id);
            $('[name="ad_soyad"]').val(p.ad_soyad);
            $('[name="telefon"]').val(p.telefon);
            $('[name="tc_no"]').val(p.tc_no);
            $('[name="pozisyon"]').val(p.gorev);
            $('[name="maas"]').val(p.maas);
            $('[name="ise_giris_tarihi"]').val(p.ise_giris_tarihi);
            $('[name="adres"]').val(p.adres);
            $('[name="aktif"]').val(p.aktif);
            $('#modalTitle').text('Personel Düzenle');
            $('#personelModal').modal('show');
        }
    });
}

function kaydet() {
    const formData = $('#personelForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    console.log('Personel kaydetme - Form verisi:', data);
    
    // Pozisyon alanını gorev olarak eşleştir
    if (data.pozisyon) {
        data.gorev = data.pozisyon;
        delete data.pozisyon;
    }
    
    console.log('Personel kaydetme - İşlenmiş veri:', data);
    
    const url = data.id ? '../../api/personel/update.php' : '../../api/personel/create.php';
    console.log('Personel kaydetme - API URL:', url);
    
    $.ajax({
        url: url,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('Personel kaydetme - API yanıtı:', response);
            if (response.success) {
                showSuccess(response.message);
                $('#personelModal').modal('hide');
                table.ajax.reload();
            } else {
                showError(response.message || 'İşlem başarısız');
            }
        },
        error: function(xhr, status, error) {
            console.error('Personel kaydetme hatası:', status, error);
            console.error('XHR Response:', xhr.responseText);
            
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
            url: '../../api/personel/delete.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    table.ajax.reload();
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Silme hatası:', status, error);
                showError('Personel silinirken hata oluştu');
            }
        });
    });
}
</script>

<script>
// Helper fonksiyonlar
function showSuccess(message) {
    // Bootstrap toast kullan
    const toastHtml = `
        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>${message}
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

function confirmDelete(callback) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu işlem geri alınamaz!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

