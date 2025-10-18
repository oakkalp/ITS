<?php
$page_title = 'Firma Ayarları';
require_once '../includes/auth.php';
require_login();
require_role(['firma_yoneticisi']);
require_once '../includes/header.php';

$firma_id = get_firma_id();
?>

<div class="top-bar">
    <h5><i class="bi bi-gear me-2"></i>Firma Ayarları</h5>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Firma Bilgileri</h6>
    </div>
    <div class="card-body">
        <form id="firmaForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Firma Adı *</label>
                    <input type="text" class="form-control" name="firma_adi" id="firma_adi" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vergi No</label>
                    <input type="text" class="form-control" name="vergi_no" id="vergi_no">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vergi Dairesi</label>
                    <input type="text" class="form-control" name="vergi_dairesi" id="vergi_dairesi">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" class="form-control" name="telefon" id="telefon">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Adres</label>
                    <textarea class="form-control" name="adres" id="adres" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
            
            <!-- Logo Yükleme -->
            <div class="row mt-4">
                <div class="col-12">
                    <h6>Logo Yükleme</h6>
                    <div class="mb-3">
                        <label class="form-label">Mevcut Logo</label>
                        <div id="currentLogo" class="mb-2">
                            <!-- Mevcut logo burada gösterilecek -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Logo Seç</label>
                        <input type="file" class="form-control" id="logoFile" accept="image/*">
                        <div class="form-text">PNG, JPG, JPEG formatları desteklenir. Maksimum 2MB.</div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="logoYukle()">Logo Yükle</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    loadFirma();
    
    $('#firmaForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function loadFirma() {
    $.get('../api/firma/bilgiler.php', function(response) {
        if (response.success) {
            const firma = response.data;
            $('#firma_adi').val(firma.firma_adi);
            $('#vergi_no').val(firma.vergi_no);
            $('#vergi_dairesi').val(firma.vergi_dairesi);
            $('#telefon').val(firma.telefon);
            $('#email').val(firma.email);
            $('#adres').val(firma.adres);
            
            // Logo göster
            if (firma.logo) {
                $('#currentLogo').html(`<img src="../uploads/logos/${firma.logo}" alt="Logo" style="max-height: 100px; max-width: 200px;" class="img-thumbnail">`);
            } else {
                $('#currentLogo').html('<p class="text-muted">Henüz logo yüklenmemiş</p>');
            }
        }
    });
}

function logoYukle() {
    const fileInput = document.getElementById('logoFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showError('Lütfen bir dosya seçin');
        return;
    }
    
    // Dosya boyutu kontrolü (2MB)
    if (file.size > 2 * 1024 * 1024) {
        showError('Dosya boyutu 2MB\'dan büyük olamaz');
        return;
    }
    
    // Dosya tipi kontrolü
    if (!file.type.startsWith('image/')) {
        showError('Sadece resim dosyaları yüklenebilir');
        return;
    }
    
    const formData = new FormData();
    formData.append('logo', file);
    
    $.ajax({
        url: '../api/firma/logo_upload.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showSuccess('Logo başarıyla yüklendi');
                loadFirma(); // Sayfayı yenile
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            showError('Logo yüklenirken hata oluştu');
        }
    });
}

function kaydet() {
    const formData = $('#firmaForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    $.ajax({
        url: '../api/firma/guncelle.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showSuccess('Firma bilgileri güncellendi!');
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            showError(xhr.responseJSON?.message || 'Bir hata oluştu!');
        }
    });
}
</script>

