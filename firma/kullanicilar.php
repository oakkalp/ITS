<?php
$page_title = 'Firma Kullanıcı Yönetimi';
require_once '../includes/auth.php';
require_login();
require_role(['firma_yoneticisi']);
require_once '../includes/header.php';

$firma_id = get_firma_id();
?>

<div class="top-bar">
    <h5><i class="bi bi-person-gear me-2"></i>Kullanıcı Yönetimi</h5>
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
                    <th>Email</th>
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
                    
                    <hr class="my-4">
                    <h6>Modül Yetkileri</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Modül</th>
                                    <th class="text-center">Okuma</th>
                                    <th class="text-center">Yazma</th>
                                    <th class="text-center">Güncelleme</th>
                                    <th class="text-center">Silme</th>
                                </tr>
                            </thead>
                            <tbody id="yetkilerTable"></tbody>
                        </table>
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
let moduller = [];

$(document).ready(function() {
    loadModuller();
    
    table = $('#kullaniciTable').DataTable({
        ajax: {
            url: '../api/firma/kullanicilar.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'id' },
            { data: 'kullanici_adi' },
            { data: 'ad_soyad' },
            { data: 'email' },
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
                    return `
                        <button class="btn btn-sm btn-info" onclick="yetkiDuzenle(${data.id})"><i class="bi bi-key"></i></button>
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

function loadModuller() {
    $.get('../api/firma/moduller.php', function(response) {
        if (response.success) {
            moduller = response.data;
            renderYetkilerTable();
        }
    });
}

function renderYetkilerTable(yetkiler = {}) {
    let html = '';
    moduller.forEach(function(modul) {
        const yetki = yetkiler[modul.id] || {};
        html += `
            <tr>
                <td>${modul.modul_adi}</td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" name="yetkiler[${modul.id}][okuma]" ${yetki.okuma ? 'checked' : ''}>
                </td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" name="yetkiler[${modul.id}][yazma]" ${yetki.yazma ? 'checked' : ''}>
                </td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" name="yetkiler[${modul.id}][guncelleme]" ${yetki.guncelleme ? 'checked' : ''}>
                </td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" name="yetkiler[${modul.id}][silme]" ${yetki.silme ? 'checked' : ''}>
                </td>
            </tr>
        `;
    });
    $('#yetkilerTable').html(html);
}

function yeniKullanici() {
    $('#kullanici_id').val('');
    $('#kullaniciForm')[0].reset();
    $('#sifre').prop('required', true);
    renderYetkilerTable();
    $('#modalTitle').text('Yeni Kullanıcı Ekle');
}

function duzenle(id) {
    $.get('../api/firma/kullanicilar.php?id=' + id, function(response) {
        if (response.success) {
            const user = response.data;
            $('#kullanici_id').val(user.id);
            $('[name="kullanici_adi"]').val(user.kullanici_adi);
            $('[name="ad_soyad"]').val(user.ad_soyad);
            $('[name="email"]').val(user.email);
            $('[name="telefon"]').val(user.telefon);
            $('[name="aktif"]').val(user.aktif);
            $('#sifre').prop('required', false);
            
            // Yetkileri yükle
            $.get('../api/firma/yetkiler.php?kullanici_id=' + id, function(resp) {
                const yetkiler = {};
                if (resp.success) {
                    resp.data.forEach(function(y) {
                        yetkiler[y.modul_id] = y;
                    });
                }
                renderYetkilerTable(yetkiler);
            });
            
            $('#modalTitle').text('Kullanıcı Düzenle');
            $('#kullaniciModal').modal('show');
        }
    });
}

function yetkiDuzenle(id) {
    duzenle(id);
}

function kaydet() {
    const formData = $('#kullaniciForm').serializeArray();
    const data = { yetkiler: {} };
    
    formData.forEach(item => {
        if (item.name.startsWith('yetkiler[')) {
            const match = item.name.match(/yetkiler\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const modulId = match[1];
                const yetkiTip = match[2];
                if (!data.yetkiler[modulId]) data.yetkiler[modulId] = {};
                data.yetkiler[modulId][yetkiTip] = true;
            }
        } else {
            data[item.name] = item.value;
        }
    });
    
    const url = '../api/firma/kullanicilar.php';
    const method = 'POST';
    
    // PUT işlemi için _method parametresi ekle
    if (data.id) {
        data._method = 'PUT';
        data.id_param = data.id;
    }
    
    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                $('#kullaniciModal').modal('hide');
                table.ajax.reload();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            showError(xhr.responseJSON?.message || 'Bir hata oluştu!');
        }
    });
}

function sil(id) {
    confirmDelete(function() {
        $.ajax({
            url: '../api/firma/kullanicilar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                _method: 'DELETE',
                id_param: id
            }),
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    table.ajax.reload();
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.message || 'Bir hata oluştu!');
            }
        });
    });
}
</script>

