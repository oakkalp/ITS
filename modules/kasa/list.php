<?php
$page_title = 'Kasa Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('kasa', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-cash-stack me-2"></i>Kasa Yönetimi</h5>
    <?php if (has_permission('kasa', 'yazma')): ?>
    <div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hareketModal" onclick="yeniHareket('gelir')">
            <i class="bi bi-plus-circle me-2"></i>Gelir Ekle
        </button>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#hareketModal" onclick="yeniHareket('gider')">
            <i class="bi bi-dash-circle me-2"></i>Gider Ekle
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Tarih Filtreleme -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="baslangicTarihi">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="bitisTarihi">
            </div>
            <div class="col-md-3">
                <label class="form-label">İşlem Tipi</label>
                <select class="form-select" id="islemTipiFiltre">
                    <option value="">Tümü</option>
                    <option value="gelir">Gelir</option>
                    <option value="gider">Gider</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="filtrele()">
                        <i class="bi bi-search"></i> Filtrele
                    </button>
                    <button class="btn btn-secondary" onclick="filtreleriTemizle()">
                        <i class="bi bi-x-circle"></i> Temizle
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #d4edda; color: #28a745;">
                <i class="bi bi-arrow-down"></i>
            </div>
            <h3 id="toplamGelir">0,00 ₺</h3>
            <p>Toplam Gelir</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #f8d7da; color: #dc3545;">
                <i class="bi bi-arrow-up"></i>
            </div>
            <h3 id="toplamGider">0,00 ₺</h3>
            <p>Toplam Gider</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #d1ecf1; color: #0c5460;">
                <i class="bi bi-wallet2"></i>
            </div>
            <h3 id="kasaBakiye">0,00 ₺</h3>
            <p>Kasa Bakiye</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #fff3cd; color: #856404;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <h3 id="bugunHareket">0</h3>
            <p id="hareketLabel">Bugün Hareket</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="kasaTable">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>İşlem Tipi</th>
                    <th>Kategori</th>
                    <th>Açıklama</th>
                    <th>Gelir</th>
                    <th>Gider</th>
                    <th>Bakiye</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Hareket Modal -->
<div class="modal fade" id="hareketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Hareket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="hareketForm">
                <input type="hidden" id="hareket_id" name="id">
                <input type="hidden" id="islem_tipi" name="islem_tipi">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tarih *</label>
                            <input type="date" class="form-control" name="tarih" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Kategori *</label>
                            <select class="form-select" name="kategori" id="kategori" required>
                                <option value="">Seçin...</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tutar (₺) *</label>
                            <input type="number" step="0.01" class="form-control" name="tutar" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" name="odeme_yontemi">
                                <option value="nakit">Nakit</option>
                                <option value="havale">Havale/EFT</option>
                                <option value="kredi_karti">Kredi Kartı</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="aciklama" rows="3"></textarea>
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

const gelirKategoriler = [
    'Satış Tahsilatı', 'Fatura Ödemesi', 'Hizmet Geliri', 'Faiz Geliri', 'Diğer Gelir'
];

const giderKategoriler = [
    'Personel Maaşı', 'Kira', 'Elektrik', 'Su', 'İnternet', 'Telefon', 
    'Ofis Malzemeleri', 'Yakıt', 'Yemek', 'Vergi', 'SGK', 'Bakım Onarım', 'Diğer Gider'
];

$(document).ready(function() {
    loadStats();
    
    // Bugünün tarihini varsayılan olarak ayarla
    const today = new Date().toISOString().split('T')[0];
    $('#bitisTarihi').val(today);
    
    table = $('#kasaTable').DataTable({
        ajax: {
            url: '../../api/kasa/list.php',
            data: function(d) {
                // Filtre parametrelerini ekle
                d.baslangic = $('#baslangicTarihi').val();
                d.bitis = $('#bitisTarihi').val();
                d.islem_tipi = $('#islemTipiFiltre').val();
            },
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { 
                data: 'tarih',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { 
                data: 'islem_tipi',
                render: function(data) {
                    return data == 'gelir' 
                        ? '<span class="badge bg-success">Gelir</span>' 
                        : '<span class="badge bg-danger">Gider</span>';
                }
            },
            { data: 'kategori' },
            { data: 'aciklama' },
            { 
                data: null,
                render: function(data) {
                    return data.islem_tipi == 'gelir' ? formatMoney(data.tutar) : '-';
                }
            },
            { 
                data: null,
                render: function(data) {
                    return data.islem_tipi == 'gider' ? formatMoney(data.tutar) : '-';
                }
            },
            { 
                data: 'bakiye',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let buttons = '';
                    <?php if (has_permission('kasa', 'silme')): ?>
                    buttons = '<button class="btn btn-sm btn-danger" onclick="sil(' + data.id + ')"><i class="bi bi-trash"></i></button>';
                    <?php endif; ?>
                    return buttons;
                }
            }
        ],
        order: [[0, 'desc']]
    });
    
    $('#hareketForm').on('submit', function(e) {
        e.preventDefault();
        kaydet();
    });
});

function loadStats() {
    $.get('../../api/kasa/stats.php', function(response) {
        if (response.success) {
            $('#toplamGelir').text(formatMoney(response.data.toplam_gelir));
            $('#toplamGider').text(formatMoney(response.data.toplam_gider));
            $('#kasaBakiye').text(formatMoney(response.data.bakiye));
            $('#bugunHareket').text(response.data.bugun_hareket);
        }
    });
}

function yeniHareket(tip) {
    $('#hareket_id').val('');
    $('#hareketForm')[0].reset();
    $('#islem_tipi').val(tip);
    
    // Kategorileri doldur
    const kategoriler = tip == 'gelir' ? gelirKategoriler : giderKategoriler;
    let html = '<option value="">Seçin...</option>';
    kategoriler.forEach(k => html += `<option value="${k}">${k}</option>`);
    $('#kategori').html(html);
    
    $('#modalTitle').text(tip == 'gelir' ? 'Gelir Ekle' : 'Gider Ekle');
}

function kaydet() {
    const formData = $('#hareketForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    $.ajax({
        url: '../../api/kasa/create.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('API Response:', response);
            if (response.success) {
                showSuccess(response.message);
                $('#hareketModal').modal('hide');
                table.ajax.reload();
                loadStats();
            } else {
                console.error('API Success false:', response);
                showError(response.message || 'İşlem başarısız');
            }
        },
        error: function(xhr) {
            console.error('AJAX Error Status:', xhr.status);
            console.error('AJAX Error Response:', xhr.responseText);
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
            url: '../../api/kasa/delete.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    table.ajax.reload();
                    loadStats();
                } else {
                    showError(response.message || 'Silme işlemi başarısız');
                }
            },
            error: function(xhr, status, error) {
                showError('Kasa hareketi silinirken hata oluştu: ' + error);
            }
        });
    });
}

function filtrele() {
    // Tarih kontrolü
    const baslangic = $('#baslangicTarihi').val();
    const bitis = $('#bitisTarihi').val();
    
    if (baslangic && bitis && baslangic > bitis) {
        showError('Başlangıç tarihi bitiş tarihinden sonra olamaz!');
        return;
    }
    
    // Tabloyu yeniden yükle
    table.ajax.reload();
    
    // İstatistikleri güncelle (filtrelenmiş veriler için)
    loadFilteredStats();
}

function filtreleriTemizle() {
    $('#baslangicTarihi').val('');
    $('#bitisTarihi').val('');
    $('#islemTipiFiltre').val('');
    
    // Tabloyu yeniden yükle
    table.ajax.reload();
    
    // Normal istatistikleri yükle
    loadStats();
    
    // Label'ı geri değiştir
    $('#hareketLabel').text('Bugün Hareket');
}

function loadFilteredStats() {
    const baslangic = $('#baslangicTarihi').val();
    const bitis = $('#bitisTarihi').val();
    const islem_tipi = $('#islemTipiFiltre').val();
    
    $.ajax({
        url: '../../api/kasa/stats.php',
        method: 'GET',
        data: {
            baslangic: baslangic,
            bitis: bitis,
            islem_tipi: islem_tipi
        },
        success: function(response) {
            if (response.success) {
                $('#toplamGelir').text(formatMoney(response.data.toplam_gelir));
                $('#toplamGider').text(formatMoney(response.data.toplam_gider));
                $('#kasaBakiye').text(formatMoney(response.data.bakiye));
                
                // Filtre varsa filtrelenmiş hareket sayısını göster
                if ($('#baslangicTarihi').val() || $('#bitisTarihi').val() || $('#islemTipiFiltre').val()) {
                    $('#bugunHareket').text(response.data.filtrelenmis_hareket);
                    $('#hareketLabel').text('Filtrelenmiş Hareket');
                } else {
                    $('#bugunHareket').text(response.data.bugun_hareket);
                    $('#hareketLabel').text('Bugün Hareket');
                }
            }
        }
    });
}
</script>

