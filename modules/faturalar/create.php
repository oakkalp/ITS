<?php
$tip = $_GET['tip'] ?? 'satis';
$page_title = ($tip == 'alis' ? 'Alış' : 'Satış') . ' Faturası Oluştur';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'yazma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5>
        <i class="bi bi-<?php echo $tip == 'alis' ? 'arrow-down-circle text-danger' : 'arrow-up-circle text-success'; ?> me-2"></i>
        <?php echo $page_title; ?>
    </h5>
    <a href="list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Geri
    </a>
</div>

<form id="faturaForm">
    <input type="hidden" name="fatura_tipi" value="<?php echo $tip; ?>">
    
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Fatura Bilgileri</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fatura No *</label>
                    <input type="text" class="form-control" name="fatura_no" id="fatura_no" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fatura Tarihi *</label>
                    <input type="date" class="form-control" name="fatura_tarihi" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Cari *</label>
                    <select class="form-select" name="cari_id" id="cari_id" required>
                        <option value="">Seçin...</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#hizliCariModal">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ödeme Tipi</label>
                    <select class="form-select" name="odeme_tipi">
                        <option value="nakit">Nakit</option>
                        <option value="havale">Havale/EFT</option>
                        <option value="cek">Çek</option>
                        <option value="kredi_karti">Kredi Kartı</option>
                        <option value="vadeli">Vadeli</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" class="form-control" name="vade_tarihi" id="vade_tarihi">
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" name="aciklama" rows="2"></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Fatura Kalemleri</h6>
            <button type="button" class="btn btn-sm btn-primary" onclick="kalemEkle()">
                <i class="bi bi-plus"></i> Kalem Ekle
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="kalemlerTable">
                    <thead>
                        <tr>
                            <th style="width: 40%">Ürün</th>
                            <th style="width: 12%">Miktar</th>
                            <th style="width: 15%">Birim Fiyat</th>
                            <th style="width: 10%">KDV %</th>
                            <th style="width: 15%">Toplam</th>
                            <th style="width: 8%"></th>
                        </tr>
                    </thead>
                    <tbody id="kalemlerBody"></tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <table class="table table-sm">
                        <tr>
                            <td class="text-end"><strong>Ara Toplam:</strong></td>
                            <td class="text-end" id="araToplam">0,00 ₺</td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>KDV:</strong></td>
                            <td class="text-end" id="kdvToplam">0,00 ₺</td>
                        </tr>
                        <tr class="table-primary">
                            <td class="text-end"><strong>GENEL TOPLAM:</strong></td>
                            <td class="text-end"><strong id="genelToplam">0,00 ₺</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle me-2"></i>Faturayı Kaydet
            </button>
            <a href="list.php" class="btn btn-secondary btn-lg">İptal</a>
        </div>
    </div>
</form>

<!-- Hızlı Ürün Ekleme Modal -->
<div class="modal fade" id="hizliUrunModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hızlı Ürün Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="hizliUrunForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ürün Kodu</label>
                        <input type="text" class="form-control" name="urun_kodu" id="hizliUrunKodu" placeholder="Otomatik oluşturulacak">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ürün Adı *</label>
                        <input type="text" class="form-control" name="urun_adi" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
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
                        <div class="col-6">
                            <label class="form-label">Alış Fiyatı</label>
                            <input type="number" step="0.01" class="form-control" name="alis_fiyati" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Satış Fiyatı</label>
                            <input type="number" step="0.01" class="form-control" name="satis_fiyati" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stok Miktarı</label>
                            <input type="number" step="0.01" class="form-control" name="stok_miktari" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Ekle ve Kullan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
console.log('JavaScript yükleniyor...');

let kalemSayisi = 0;
let urunler = [];
let aktifKalemId = null;

// Debug: kalemEkle fonksiyonunu kontrol et
console.log('kalemEkle fonksiyonu tanımlanıyor...');

$(document).ready(function() {
    // Satış faturası için otomatik fatura no ve vade tarihi ayarla
    <?php if ($tip == 'satis'): ?>
    generateFaturaNo();
    <?php endif; ?>
    
    // Her iki fatura tipi için otomatik vade tarihi ayarla
    setVadeTarihi();
    
    loadCariler();
    loadUrunler(function() {
        kalemEkle(); // İlk satırı ekle - ürünler yüklendikten sonra
    });
    
    $('#faturaForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
    
    $('#hizliUrunForm').on('submit', function(e) {
        e.preventDefault();
        hizliUrunKaydet();
    });
});

function loadCariler() {
    console.log('loadCariler çağrıldı - Tip: <?php echo $tip; ?>');
    
    // Loading göster
    $('#cari_id').html('<option value="">Yükleniyor...</option>');
    
    $.get('../../api/cariler/list.php', function(response) {
        console.log('Cariler API response:', response);
        console.log('Response type:', typeof response);
        console.log('Response success:', response.success);
        console.log('Response data:', response.data);
        console.log('Data length:', response.data ? response.data.length : 'undefined');
        
        if (response && response.success) {
            let html = '<option value="">Seçin...</option>';
            let addedCount = 0;
            
            if (response.data && Array.isArray(response.data)) {
                response.data.forEach(function(cari) {
                    console.log('Cari kontrol:', cari);
                    console.log('Cari ID:', cari.id);
                    console.log('Cari Unvan:', cari.unvan);
                    
                    // Geçici olarak tüm carileri göster
                    html += `<option value="${cari.id}">${cari.unvan}</option>`;
                    addedCount++;
                    console.log('Cari eklendi:', cari.unvan);
                });
            } else {
                console.warn('Response.data is not an array:', response.data);
            }
            
            console.log('Toplam eklenen cari sayısı:', addedCount);
            console.log('HTML to be set:', html);
            $('#cari_id').html(html);
            
            if (addedCount === 0) {
                console.warn('Hiç cari eklenmedi!');
                $('#cari_id').html('<option value="">Cari bulunamadı</option>');
                showError('Bu fatura tipi için uygun cari bulunamadı!');
            }
        } else {
            console.error('Cariler yüklenemedi:', response);
            $('#cari_id').html('<option value="">Hata</option>');
            showError('Cariler yüklenemedi: ' + (response ? response.message : 'Bilinmeyen hata'));
        }
    }).fail(function(xhr, status, error) {
        console.error('Cariler API hatası:', xhr);
        console.error('Status:', status);
        console.error('Error:', error);
        console.error('Response Text:', xhr.responseText);
        $('#cari_id').html('<option value="">Hata</option>');
        showError('Cariler yüklenemedi! Status: ' + status + ', Error: ' + error);
    });
}

function loadUrunler(callback) {
    $.get('../../api/stok/list.php', function(response) {
        if (response.success) {
            urunler = response.data;
            
            // Tüm açılır menüleri güncelle
            $('select[name*="[urun_id]"]').each(function() {
                const mevcutDeger = $(this).val();
                let html = '<option value="">Ürün Seçin...</option>';
                urunler.forEach(function(urun) {
                    html += `<option value="${urun.id}" data-fiyat="<?php echo $tip == 'alis' ? '${urun.alis_fiyati}' : '${urun.satis_fiyati}'; ?>">${urun.urun_adi}</option>`;
                });
                $(this).html(html).val(mevcutDeger);
            });
            
            if (callback) callback();
        } else {
            console.error('Ürünler yüklenemedi:', response.message);
            showError('Ürünler yüklenemedi: ' + response.message);
        }
    }).fail(function(xhr) {
        console.error('Ürünler API hatası:', xhr);
        showError('Ürünler yüklenemedi!');
    });
}

function kalemEkle() {
    console.log('kalemEkle fonksiyonu çağrıldı');
    kalemSayisi++;
    let html = `
        <tr id="kalem_${kalemSayisi}">
            <td>
                <div class="input-group input-group-sm">
                    <select class="form-select form-select-sm" name="kalemler[${kalemSayisi}][urun_id]" onchange="urunSec(${kalemSayisi}, this.value)" required>
                        <option value="">Ürün Seçin...</option>
                    </select>
                    <button type="button" class="btn btn-success btn-sm" onclick="hizliUrunEkle(${kalemSayisi})" title="Hızlı Ürün Ekle">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </td>
            <td><input type="number" step="0.01" class="form-control form-control-sm" name="kalemler[${kalemSayisi}][miktar]" value="1" onchange="hesapla(${kalemSayisi})" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm" name="kalemler[${kalemSayisi}][birim_fiyat]" value="0" onchange="hesapla(${kalemSayisi})" required></td>
            <td>
                <select class="form-select form-select-sm" name="kalemler[${kalemSayisi}][kdv_orani]" onchange="hesapla(${kalemSayisi})">
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="8">8</option>
                    <option value="18" selected>18</option>
                    <option value="20">20</option>
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm" id="toplam_${kalemSayisi}" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="kalemSil(${kalemSayisi})"><i class="bi bi-trash"></i></button></td>
        </tr>
    `;
    
    $('#kalemlerBody').append(html);
    
    // Ürünleri doldur
    let urunHtml = '<option value="">Ürün Seçin...</option>';
    urunler.forEach(function(urun) {
        urunHtml += `<option value="${urun.id}" data-fiyat="<?php echo $tip == 'alis' ? '${urun.alis_fiyati}' : '${urun.satis_fiyati}'; ?>">${urun.urun_adi}</option>`;
    });
    $(`#kalem_${kalemSayisi} select[name*="[urun_id]"]`).html(urunHtml);
}

function urunSec(kalemId, urunId) {
    if (urunId) {
        const urun = urunler.find(u => u.id == urunId);
        if (urun) {
            const fiyat = <?php echo $tip == 'alis' ? 'urun.alis_fiyati' : 'urun.satis_fiyati'; ?>;
            $(`#kalem_${kalemId} input[name*="[birim_fiyat]"]`).val(fiyat);
            hesapla(kalemId);
        }
    }
}

function kalemSil(kalemId) {
    $(`#kalem_${kalemId}`).remove();
    toplamHesapla();
}

function hesapla(kalemId) {
    const miktar = parseFloat($(`#kalem_${kalemId} input[name*="[miktar]"]`).val()) || 0;
    const fiyat = parseFloat($(`#kalem_${kalemId} input[name*="[birim_fiyat]"]`).val()) || 0;
    const kdv = parseFloat($(`#kalem_${kalemId} select[name*="[kdv_orani]"]`).val()) || 0;
    
    const araToplam = miktar * fiyat;
    const kdvTutar = araToplam * (kdv / 100);
    const toplam = araToplam + kdvTutar;
    
    $(`#toplam_${kalemId}`).val(toplam.toFixed(2) + ' ₺');
    
    toplamHesapla();
}

function toplamHesapla() {
    let araToplam = 0;
    let kdvToplam = 0;
    
    $('#kalemlerBody tr').each(function() {
        const miktar = parseFloat($(this).find('input[name*="[miktar]"]').val()) || 0;
        const fiyat = parseFloat($(this).find('input[name*="[birim_fiyat]"]').val()) || 0;
        const kdv = parseFloat($(this).find('select[name*="[kdv_orani]"]').val()) || 0;
        
        const kalemAra = miktar * fiyat;
        const kalemKdv = kalemAra * (kdv / 100);
        
        araToplam += kalemAra;
        kdvToplam += kalemKdv;
    });
    
    const genelToplam = araToplam + kdvToplam;
    
    $('#araToplam').text(formatMoney(araToplam));
    $('#kdvToplam').text(formatMoney(kdvToplam));
    $('#genelToplam').text(formatMoney(genelToplam));
}

function hizliUrunEkle(kalemId) {
    aktifKalemId = kalemId;
    $('#hizliUrunForm')[0].reset();
    
    // Otomatik ürün kodu oluştur
    $.get('../../api/stok/generate_code.php', function(response) {
        console.log('Ürün kodu API yanıtı:', response);
        
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
            $('#hizliUrunKodu').val(response.data?.urun_kodu || '');
        } else {
            const errorMsg = response?.message || 'Ürün kodu oluşturulamadı';
            console.error('Ürün kodu oluşturulamadı:', errorMsg);
            $('#hizliUrunKodu').attr('placeholder', 'Ürün kodu giriniz');
        }
    }).fail(function(xhr, status, error) {
        console.error('Ürün kodu API\'si çağrılamadı:', status, error);
        $('#hizliUrunKodu').attr('placeholder', 'Ürün kodu giriniz');
    });
    
    $('#hizliUrunModal').modal('show');
}

function hizliUrunKaydet() {
    const formData = $('#hizliUrunForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    console.log('Hızlı ürün kaydetme - Form verisi:', data);
    
    $.ajax({
        url: '../../api/stok/create.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('Hızlı ürün kaydetme - API yanıtı:', response);
            
            if (response.success) {
                showSuccess('Ürün eklendi!');
                $('#hizliUrunModal').modal('hide');
                
                // Ürünleri yeniden yükle
                loadUrunler(function() {
                    // Yeni eklenen ürünü seç
                    if (aktifKalemId) {
                        $(`#kalem_${aktifKalemId} select[name*="[urun_id]"]`).val(response.data.id).trigger('change');
                    }
                });
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr);
            console.error('Response Text:', xhr.responseText);
            
            let errorMessage = 'Bir hata oluştu!';
            
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {
                errorMessage = xhr.responseText || errorMessage;
            }
            
            showError(errorMessage);
        }
    });
}

function kaydet() {
    const formData = new FormData($('#faturaForm')[0]);
    // Toplam tutarı hesapla
    let toplamTutar = 0;
    $('#kalemlerBody tr').each(function() {
        const miktar = parseFloat($(this).find('input[name*="[miktar]"]').val()) || 0;
        const fiyat = parseFloat($(this).find('input[name*="[birim_fiyat]"]').val()) || 0;
        const kdv = parseFloat($(this).find('select[name*="[kdv_orani]"]').val()) || 0;
        
        const kalemAra = miktar * fiyat;
        const kalemKdv = kalemAra * (kdv / 100);
        toplamTutar += kalemAra + kalemKdv;
    });
    
    const data = {
        fatura_tipi: formData.get('fatura_tipi'),
        fatura_no: formData.get('fatura_no'),
        fatura_tarihi: formData.get('fatura_tarihi'),
        cari_id: formData.get('cari_id'),
        odeme_tipi: formData.get('odeme_tipi'),
        vade_tarihi: formData.get('vade_tarihi'),
        aciklama: formData.get('aciklama'),
        toplam_tutar: toplamTutar,
        kalemler: []
    };
    
    // Kalemleri topla
    $('#kalemlerBody tr').each(function() {
        const urun_id = $(this).find('select[name*="[urun_id]"]').val();
        if (urun_id) {
            data.kalemler.push({
                urun_id: urun_id,
                miktar: $(this).find('input[name*="[miktar]"]').val(),
                birim_fiyat: $(this).find('input[name*="[birim_fiyat]"]').val(),
                kdv_orani: $(this).find('select[name*="[kdv_orani]"]').val()
            });
        }
    });
    
    if (data.kalemler.length == 0) {
        showError('En az bir kalem eklemelisiniz!');
        return;
    }
    
    $.ajax({
        url: '../../api/faturalar/create.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('Fatura oluşturma response:', response);
            if (response.success) {
                showSuccess(response.message || 'Fatura başarıyla oluşturuldu!');
                setTimeout(() => window.location.href = 'list.php', 1500);
            } else {
                showError(response.message || 'Bilinmeyen hata oluştu');
            }
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr);
            console.error('Response Text:', xhr.responseText);
            
            let errorMessage = 'Bir hata oluştu!';
            
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {
                errorMessage = xhr.responseText || errorMessage;
            }
            
            showError(errorMessage);
        }
    });
}

// Otomatik fatura no oluşturma
function generateFaturaNo() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    
    // Format: SF-YYYY-MM-DD-XXX (SF = Satış Faturası)
    const baseNo = `SF-${year}${month}${day}`;
    
    // Rastgele 3 haneli sayı ekle
    const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    const faturaNo = `${baseNo}-${randomNum}`;
    
    $('#fatura_no').val(faturaNo);
    console.log('Otomatik fatura no oluşturuldu:', faturaNo);
}

// Otomatik vade tarihi ayarlama (15 gün sonra)
function setVadeTarihi() {
    const today = new Date();
    const vadeTarihi = new Date(today);
    vadeTarihi.setDate(today.getDate() + 15);
    
    const vadeTarihiStr = vadeTarihi.toISOString().split('T')[0];
    $('#vade_tarihi').val(vadeTarihiStr);
    console.log('Otomatik vade tarihi ayarlandı:', vadeTarihiStr);
}

// Debug: Fonksiyonların tanımlandığını kontrol et
console.log('Tüm fonksiyonlar tanımlandı');
console.log('kalemEkle:', typeof kalemEkle);
console.log('loadCariler:', typeof loadCariler);
console.log('loadUrunler:', typeof loadUrunler);

// Hızlı Cari Ekleme
$(document).ready(function() {
    console.log('DOM ready - Hızlı cari form listener ekleniyor');
    
    // Modal açıldığında fatura tipine göre varsayılan seçimi yap
    $('#hizliCariModal').on('show.bs.modal', function() {
        const faturaTipi = $('input[name="fatura_tipi"]').val();
        console.log('Modal açılıyor, fatura tipi:', faturaTipi);
        
        if (faturaTipi === 'alis') {
            $('#hizliCariTipi').val('tedarikci');
        } else if (faturaTipi === 'satis') {
            $('#hizliCariTipi').val('musteri');
        }
    });
    
    $('#hizliCariForm').on('submit', function(e) {
        console.log('Hızlı cari form submit edildi');
        e.preventDefault();
        
        const formData = new FormData(this);
        console.log('FormData oluşturuldu');
        
        $.ajax({
            url: '../../api/cariler/create.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('AJAX başarılı:', response);
                if (response.success) {
                    showSuccess('Cari başarıyla eklendi');
                    $('#hizliCariModal').modal('hide');
                    $('#hizliCariForm')[0].reset();
                    
                    // Cari listesini yenile
                    loadCariler();
                    
                    // Yeni eklenen cariyi seç
                    setTimeout(function() {
                        $('#cari_id').val(response.data.id);
                    }, 500);
                } else {
                    showError(response.message || 'Cari eklenirken hata oluştu');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX hatası:', xhr, status, error);
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
});
</script>

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
                            <label class="form-label">Cari Tipi *</label>
                            <select class="form-select" id="hizliCariTipi" name="cari_tipi" required>
                                <option value="">Seçin...</option>
                                <option value="tedarikci">Tedarikçi</option>
                                <option value="musteri">Müşteri</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cari Adı *</label>
                            <input type="text" class="form-control" id="hizliCariAdi" name="unvan" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="hizliCariTelefon" name="telefon">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="hizliCariEmail" name="email">
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