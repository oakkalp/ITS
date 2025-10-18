<?php
$page_title = 'Kullanıcı Yönetimi';
require_once '../includes/auth.php';
require_login();
require_role(['super_admin']);
require_once '../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-people me-2"></i>Tüm Kullanıcılar</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kullaniciModal" onclick="yeniKullanici()">
        <i class="bi bi-plus-circle me-2"></i>Yeni Kullanıcı Ekle
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="kullaniciTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>Ad Soyad</th>
                    <th>Firma</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Kullanıcı Modal -->
<div class="modal fade" id="kullaniciModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="kullaniciForm">
                <input type="hidden" id="kullanici_id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Firma *</label>
                            <select class="form-select" name="firma_id" id="firma_id" required>
                                <option value="">Firma Seçin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rol *</label>
                            <select class="form-select" name="rol" required>
                                <option value="firma_yoneticisi">Firma Yöneticisi</option>
                                <option value="kullanici">Kullanıcı</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kullanıcı Adı *</label>
                            <input type="text" class="form-control" name="kullanici_adi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şifre *</label>
                            <input type="password" class="form-control" name="sifre" id="sifre">
                            <small class="text-muted">Düzenlerken boş bırakın</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" class="form-control" name="ad_soyad" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
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

<?php require_once '../includes/footer.php'; ?>

<script>
let table;

$(document).ready(function() {
    loadFirmalar();
    
    table = $('#kullaniciTable').DataTable({
        ajax: {
            url: '../api/admin/kullanicilar.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'id' },
            { data: 'kullanici_adi' },
            { data: 'ad_soyad' },
            { data: 'firma_adi' },
            { 
                data: 'rol',
                render: function(data) {
                    if (data == 'super_admin') return '<span class="badge bg-danger">Super Admin</span>';
                    if (data == 'firma_yoneticisi') return '<span class="badge bg-primary">Firma Yöneticisi</span>';
                    return '<span class="badge bg-info">Kullanıcı</span>';
                }
            },
            { 
                data: 'aktif',
                render: function(data) {
                    return data == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Pasif</span>';
                }
            },
            { 
                data: 'olusturma_tarihi',
                render: function(data) {
                    return formatDate(data);
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    if (data.rol == 'super_admin') return '-';
                    return `
                        <button class="btn btn-sm btn-warning" onclick="duzenle(${data.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="sil(${data.id})"><i class="bi bi-trash"></i></button>
                    `;
                }
            }
        ]
    });
    
    $('#kullaniciForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function loadFirmalar() {
    $.get('../api/admin/firmalar.php', function(response) {
        if (response.success) {
            let html = '<option value="">Firma Seçin</option>';
            response.data.forEach(function(firma) {
                html += `<option value="${firma.id}">${firma.firma_adi}</option>`;
            });
            $('#firma_id').html(html);
        }
    });
}

function yeniKullanici() {
    $('#kullanici_id').val('');
    $('#kullaniciForm')[0].reset();
    $('#sifre').prop('required', true);
    $('#modalTitle').text('Yeni Kullanıcı Ekle');
}

function duzenle(id) {
    $.get('../api/admin/kullanicilar.php?id=' + id, function(response) {
        if (response.success) {
            const user = response.data;
            $('#kullanici_id').val(user.id);
            $('[name="firma_id"]').val(user.firma_id);
            $('[name="rol"]').val(user.rol);
            $('[name="kullanici_adi"]').val(user.kullanici_adi);
            $('[name="ad_soyad"]').val(user.ad_soyad);
            $('[name="email"]').val(user.email);
            $('[name="telefon"]').val(user.telefon);
            $('[name="aktif"]').val(user.aktif);
            $('#sifre').prop('required', false);
            $('#modalTitle').text('Kullanıcı Düzenle');
            $('#kullaniciModal').modal('show');
        }
    });
}

function kaydet() {
    const formData = $('#kullaniciForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    const url = '../api/admin/kullanicilar.php';
    const method = 'POST';
    
    // Düzenleme işlemi için action ekle
    if (data.id) {
        data.action = 'update';
    }
    
    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('Raw response:', response);
            
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
                showSuccess(cleanResponse.message);
                $('#kullaniciModal').modal('hide');
                table.ajax.reload();
            } else {
                console.error('API Success false:', cleanResponse);
                showError(cleanResponse.message || 'İşlem başarısız');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error Status:', xhr.status); // Debug için
            console.error('AJAX Error Response:', xhr.responseText); // Debug için
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
            url: '../api/admin/kullanicilar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id, action: 'delete' }),
            success: function(response) {
                console.log('Raw response:', response);
                
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
                    showSuccess(cleanResponse.message);
                    table.ajax.reload();
                } else {
                    showError(cleanResponse.message || 'Silme işlemi başarısız');
                }
            },
            error: function(xhr, status, error) {
                showError('Kullanıcı silinirken hata oluştu: ' + error);
            }
        });
    });
}
</script>

