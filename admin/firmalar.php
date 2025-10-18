<?php
$page_title = 'Firma Yönetimi';
require_once '../includes/auth.php';
require_login();
require_role(['super_admin']);
require_once '../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-building me-2"></i>Firma Yönetimi</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#firmaModal" onclick="yeniFirma()">
        <i class="bi bi-plus-circle me-2"></i>Yeni Firma Ekle
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="firmaTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Firma Adı</th>
                    <th>Vergi No</th>
                    <th>Telefon</th>
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

<!-- Firma Modal -->
<div class="modal fade" id="firmaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="firmaForm">
                <input type="hidden" id="firma_id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Firma Adı *</label>
                            <input type="text" class="form-control" name="firma_adi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vergi No</label>
                            <input type="text" class="form-control" name="vergi_no">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vergi Dairesi</label>
                            <input type="text" class="form-control" name="vergi_dairesi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" name="adres" rows="3"></textarea>
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
    table = $('#firmaTable').DataTable({
        ajax: {
            url: '../api/admin/firmalar.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'id' },
            { data: 'firma_adi' },
            { data: 'vergi_no' },
            { data: 'telefon' },
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
                        <button class="btn btn-sm btn-warning" onclick="duzenle(${data.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="sil(${data.id})"><i class="bi bi-trash"></i></button>
                        <a href="firma_detay.php?id=${data.id}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                    `;
                }
            }
        ]
    });
    
    $('#firmaForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function yeniFirma() {
    $('#firma_id').val('');
    $('#firmaForm')[0].reset();
    $('#modalTitle').text('Yeni Firma Ekle');
}

function duzenle(id) {
    $.get('../api/admin/firmalar.php?id=' + id, function(response) {
        if (response.success) {
            const firma = response.data;
            $('#firma_id').val(firma.id);
            $('[name="firma_adi"]').val(firma.firma_adi);
            $('[name="vergi_no"]').val(firma.vergi_no);
            $('[name="vergi_dairesi"]').val(firma.vergi_dairesi);
            $('[name="telefon"]').val(firma.telefon);
            $('[name="email"]').val(firma.email);
            $('[name="aktif"]').val(firma.aktif);
            $('[name="adres"]').val(firma.adres);
            $('#modalTitle').text('Firma Düzenle');
            $('#firmaModal').modal('show');
        }
    });
}

function kaydet() {
    const formData = $('#firmaForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    const url = data.id ? '../api/admin/firmalar.php?id=' + data.id : '../api/admin/firmalar.php';
    const method = data.id ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                $('#firmaModal').modal('hide');
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
            url: '../api/admin/firmalar.php?id=' + id,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    table.ajax.reload();
                } else {
                    showError(response.message);
                }
            }
        });
    });
}
</script>

