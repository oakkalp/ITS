<?php
// Cache busting - header'dan önce
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$page_title = 'Stok Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('urunler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-box-seam me-2"></i>Stok Yönetimi</h5>
    <?php if (has_permission('urunler', 'yazma')): ?>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#urunModal" onclick="yeniUrun()">
            <i class="bi bi-plus-circle me-2"></i>Yeni Ürün Ekle
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#manuelStokModal">
            <i class="bi bi-box-arrow-in-up me-2"></i>Elle Stok Girişi
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="urunTable">
            <thead>
                <tr>
                    <th>Ürün Kodu</th>
                    <th>Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Birim</th>
                    <th>Stok</th>
                    <th>Alış Fiyatı</th>
                    <th>Satış Fiyatı</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Ürün Modal -->
<div class="modal fade" id="urunModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Ürün Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="urunForm">
                <input type="hidden" id="urun_id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Ürün Kodu</label>
                            <input type="text" class="form-control" name="urun_kodu" placeholder="Otomatik oluşturulur">
                            <small class="form-text text-muted">Yeni ürün eklerken otomatik oluşturulur</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ürün Adı *</label>
                            <input type="text" class="form-control" name="urun_adi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="kategori">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Birim *</label>
                            <select class="form-select" name="birim" required>
                                <option value="Adet">Adet</option>
                                <option value="Kg">Kg</option>
                                <option value="Lt">Lt</option>
                                <option value="Mt">Mt</option>
                                <option value="Paket">Paket</option>
                                <option value="Kutu">Kutu</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stok Miktarı</label>
                            <input type="number" step="0.01" class="form-control" name="stok_miktari" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Alış Fiyatı (₺)</label>
                            <input type="number" step="0.01" class="form-control" name="alis_fiyati" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satış Fiyatı (₺)</label>
                            <input type="number" step="0.01" class="form-control" name="satis_fiyati" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barkod</label>
                            <input type="text" class="form-control" name="barkod">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="aktif">
                                <option value="1">Aktif</option>
                                <option value="0">Pasif</option>
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

<!-- Elle Stok Girişi Modal -->
<div class="modal fade" id="manuelStokModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Elle Stok Girişi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="manuelStokForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Ürün Seçin *</label>
                            <select class="form-select" id="manuelUrunSelect" required>
                                <option value="">Ürün seçin...</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hareket Tipi *</label>
                            <select class="form-select" id="manuelHareketTipi" required>
                                <option value="">Seçin...</option>
                                <option value="manuel_giris">Elle Giriş (+)</option>
                                <option value="manuel_cikis">Elle Çıkış (-)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Miktar *</label>
                            <input type="number" step="0.01" class="form-control" id="manuelMiktar" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Birim Fiyat</label>
                            <input type="number" step="0.01" class="form-control" id="manuelBirimFiyat" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Belge No</label>
                            <input type="text" class="form-control" id="manuelBelgeNo" placeholder="Otomatik oluşturulur">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" id="manuelAciklama" rows="2" placeholder="Örn: Leylandi 4m düzenleme"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<!-- Cache busting -->
<script>
// Tarayıcı önbelleğini temizle
if ('caches' in window) {
    caches.keys().then(function(names) {
        for (let name of names) {
            caches.delete(name);
        }
    });
}

// Sayfa yüklendiğinde debug bilgisi
console.log('=== STOK LİSTESİ YÜKLENDİ ===');
console.log('Timestamp:', new Date().toISOString());
console.log('URL:', window.location.href);
console.log('User Agent:', navigator.userAgent);
</script>

<script>
let table;

$(document).ready(function() {
    loadManuelUrunler();
    
    // Elle stok girişi form submit
    $('#manuelStokForm').on('submit', function(e) {
        e.preventDefault();
        saveManuelStok();
    });
    
    table = $('#urunTable').DataTable({
        ajax: {
            url: '../../api/stok/list.php',
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'urun_kodu' },
            { data: 'urun_adi' },
            { data: 'kategori' },
            { data: 'birim' },
            { 
                data: 'stok_miktari',
                render: function(data) {
                    const stok = parseFloat(data);
                    if (isNaN(stok)) {
                        return '<span class="badge bg-secondary">0</span>';
                    }
                    if (stok <= 0) {
                        return '<span class="badge bg-danger">' + stok + '</span>';
                    } else if (stok <= 10) {
                        return '<span class="badge bg-warning">' + stok + '</span>';
                    } else {
                        return '<span class="badge bg-success">' + stok + '</span>';
                    }
                }
            },
            { 
                data: 'alis_fiyati',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'satis_fiyati',
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
                    <?php if (has_permission('urunler', 'guncelleme')): ?>
                    buttons += '<button class="btn btn-sm btn-warning" onclick="duzenle(' + data.id + ')"><i class="bi bi-pencil"></i></button> ';
                    <?php endif; ?>
                    <?php if (has_permission('urunler', 'silme')): ?>
                    buttons += '<button class="btn btn-sm btn-danger" onclick="sil(' + data.id + ')"><i class="bi bi-trash"></i></button>';
                    <?php endif; ?>
                    return buttons;
                }
            }
        ]
    });
    
    $('#urunForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function yeniUrun() {
    console.log('=== YENİ ÜRÜN MODALI AÇILIYOR ===');
    
    $('#urun_id').val('');
    $('#urunForm')[0].reset();
    $('#modalTitle').text('Yeni Ürün Ekle');
    
    // Ürün kodu otomatik oluştur
    console.log('Ürün kodu API\'si çağrılıyor...');
    
    $.get('../../api/stok/generate_code.php', function(response) {
        console.log('Ürün kodu API yanıtı:', response);
        console.log('Response type:', typeof response);
        console.log('Response success:', response?.success);
        console.log('Response message:', response?.message);
        
        // Response'u kontrol et
        if (typeof response === 'string') {
            try {
                response = JSON.parse(response);
            } catch (e) {
                console.error('JSON parse hatası:', e);
                response = { success: false, message: 'Geçersiz yanıt formatı' };
            }
        }
        
        if (response && response.success === true) {
            console.log('Ürün kodu başarıyla oluşturuldu:', response.data?.urun_kodu);
            $('[name="urun_kodu"]').val(response.data?.urun_kodu || '');
            
            // Başarı mesajı göster
            showSuccess('Ürün kodu otomatik oluşturuldu: ' + (response.data?.urun_kodu || 'Bilinmeyen'));
        } else {
            const errorMsg = response?.message || 'Ürün kodu oluşturulamadı';
            console.error('Ürün kodu oluşturulamadı:', errorMsg);
            // Hata durumunda manuel kod girişi için placeholder
            $('[name="urun_kodu"]').attr('placeholder', 'Ürün kodu giriniz');
            showError('Ürün kodu otomatik oluşturulamadı: ' + errorMsg);
        }
    }).fail(function(xhr, status, error) {
        console.error('Ürün kodu API\'si çağrılamadı:', status, error);
        console.error('XHR Status:', xhr.status);
        console.error('XHR Response:', xhr.responseText);
        
        let errorMsg = 'API çağrılamadı: ' + error;
        if (xhr.status === 401) {
            errorMsg = 'Oturum süresi dolmuş. Lütfen tekrar giriş yapın.';
        } else if (xhr.status === 500) {
            errorMsg = 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.';
        }
        
        $('[name="urun_kodu"]').attr('placeholder', 'Ürün kodu giriniz');
        showError(errorMsg);
        
        // Fallback: Basit ürün kodu oluştur
        generateFallbackUrunKodu();
    });
}

function generateFallbackUrunKodu() {
    // Fallback ürün kodu oluştur
    const now = new Date();
    const year = now.getFullYear().toString().slice(-2);
    const month = (now.getMonth() + 1).toString().padStart(2, '0');
    const day = now.getDate().toString().padStart(2, '0');
    const time = now.getTime().toString().slice(-6);
    
    const fallbackKod = `U${year}${month}${day}${time}`;
    
    console.log('Fallback ürün kodu oluşturuldu:', fallbackKod);
    $('[name="urun_kodu"]').val(fallbackKod);
    showSuccess('Ürün kodu otomatik oluşturuldu (Fallback): ' + fallbackKod);
}

function duzenle(id) {
    console.log('=== ÜRÜN DÜZENLEME BAŞLATILIYOR ===');
    console.log('Ürün ID:', id);
    
    $.get('../../api/stok/get.php?id=' + id, function(response) {
        console.log('API yanıtı:', response);
        
        if (response.success) {
            const urun = response.data;
            console.log('Ürün verisi:', urun);
            console.log('Açıklama:', urun.aciklama);
            
            $('#urun_id').val(urun.id);
            $('[name="urun_kodu"]').val(urun.urun_kodu);
            $('[name="urun_adi"]').val(urun.urun_adi);
            $('[name="kategori"]').val(urun.kategori);
            $('[name="birim"]').val(urun.birim);
            $('[name="stok_miktari"]').val(urun.stok_miktari);
            $('[name="alis_fiyati"]').val(urun.alis_fiyati);
            $('[name="satis_fiyati"]').val(urun.satis_fiyati);
            $('[name="barkod"]').val(urun.barkod);
            $('[name="aciklama"]').val(urun.aciklama);
            $('[name="aktif"]').val(urun.aktif);
            
            console.log('Form alanları dolduruldu');
            console.log('Açıklama alanı değeri:', $('[name="aciklama"]').val());
            
            $('#modalTitle').text('Ürün Düzenle');
            $('#urunModal').modal('show');
        } else {
            console.error('Ürün getirilemedi:', response.message);
            showError('Ürün getirilemedi: ' + response.message);
        }
    }).fail(function(xhr, status, error) {
        console.error('API çağrılamadı:', status, error);
        showError('Ürün getirilemedi: ' + error);
    });
}

function kaydet() {
    console.log('=== ÜRÜN KAYDETME BAŞLATILIYOR ===');
    
    const formData = $('#urunForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    console.log('Form verisi:', data);
    console.log('Açıklama:', data.aciklama);
    
    const url = data.id ? '../../api/stok/update.php' : '../../api/stok/create.php';
    console.log('API URL:', url);
    
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
                $('#urunModal').modal('hide');
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
    console.log('=== STOK SİLME İŞLEMİ BAŞLATILIYOR ===');
    console.log('ID:', id);
    console.log('Timestamp:', new Date().toISOString());
    
    confirmDelete(function() {
        console.log('ConfirmDelete callback çalıştı');
        
        // FormData ile POST gönder
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('../../api/stok/delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Fetch response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Fetch response text:', text);
            
            let response;
            try {
                response = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse hatası:', e);
                response = { success: false, message: 'Sunucu yanıtı parse edilemedi' };
            }
            
            if (response && response.success) {
                showSuccess(response.message || 'Silme işlemi başarılı');
                table.ajax.reload();
            } else {
                showError(response.message || 'Silme işlemi başarısız');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showError('Silme işlemi sırasında hata oluştu: ' + error.message);
        });
    });
}

// Elle stok girişi fonksiyonları
function loadManuelUrunler() {
    $.get('../../api/stok/list.php', function(response) {
        if (response.success) {
            let html = '<option value="">Ürün seçin...</option>';
            response.data.forEach(function(urun) {
                html += `<option value="${urun.id}">${urun.urun_adi} (Stok: ${urun.stok_miktari || 0})</option>`;
            });
            $('#manuelUrunSelect').html(html);
        }
    });
}

function saveManuelStok() {
    const urunId = $('#manuelUrunSelect').val();
    const hareketTipi = $('#manuelHareketTipi').val();
    const miktar = $('#manuelMiktar').val();
    const birimFiyat = $('#manuelBirimFiyat').val() || 0;
    const belgeNo = $('#manuelBelgeNo').val() || '';
    const aciklama = $('#manuelAciklama').val() || '';
    
    if (!urunId || !hareketTipi || !miktar) {
        showError('Lütfen tüm zorunlu alanları doldurun');
        return;
    }
    
    const data = {
        urun_id: urunId,
        hareket_tipi: hareketTipi,
        miktar: parseFloat(miktar),
        birim_fiyat: parseFloat(birimFiyat),
        belge_no: belgeNo,
        aciklama: aciklama
    };
    
    console.log('Manuel stok kaydediliyor:', data);
    
    $.ajax({
        url: '../../api/stok/manuel-hareket.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showSuccess('Manuel stok hareketi başarıyla kaydedildi');
                $('#manuelStokModal').modal('hide');
                $('#manuelStokForm')[0].reset();
                table.ajax.reload(); // Tabloyu yenile
                
                // Başarı mesajında detayları göster
                const urunAdi = $('#manuelUrunSelect option:selected').text();
                const hareketText = hareketTipi === 'manuel_giris' ? 'Elle Giriş' : 'Elle Çıkış';
                const isaret = hareketTipi === 'manuel_giris' ? '+' : '-';
                
                showSuccess(`${urunAdi} için ${hareketText} yapıldı: ${isaret}${miktar}<br>
                           Eski Stok: ${response.data.eski_stok}<br>
                           Yeni Stok: ${response.data.yeni_stok}`);
            } else {
                showError(response.message || 'Stok hareketi kaydedilemedi');
            }
        },
        error: function(xhr) {
            console.error('Manuel stok hatası:', xhr);
            let errorMsg = 'Stok hareketi kaydedilemedi';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showError(errorMsg);
        }
    });
}

// Yardımcı fonksiyonlar
function showSuccess(message) {
    // Bootstrap toast kullanarak başarı mesajı göster
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
    
    // Toast container oluştur veya bul
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Toast'u ekle ve göster
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Toast gösterildikten sonra DOM'dan kaldır
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

function showError(message) {
    // Bootstrap toast kullanarak hata mesajı göster
    const toastHtml = `
        <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Toast container oluştur veya bul
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Toast'u ekle ve göster
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Toast gösterildikten sonra DOM'dan kaldır
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

function confirmDelete(callback) {
    // Bootstrap modal kullanarak onay dialogu göster
    const confirmHtml = `
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Silme Onayı</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bu işlemi geri alamazsınız. Silmek istediğinizden emin misiniz?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Sil</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Modal'ı ekle
    document.body.insertAdjacentHTML('beforeend', confirmHtml);
    const modalElement = document.getElementById('confirmDeleteModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Sil butonuna tıklama olayı ekle
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        modal.hide();
        callback();
    });
    
    // Modal kapandığında DOM'dan kaldır
    modalElement.addEventListener('hidden.bs.modal', function() {
        modalElement.remove();
    });
    
    // Modal'ı göster
    modal.show();
}

function formatMoney(amount) {
    // Para formatını düzenle
    const num = parseFloat(amount) || 0;
    return '₺' + num.toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

