<?php
$page_title = 'Fatura Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('faturalar', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-receipt me-2"></i>Fatura Yönetimi</h5>
    <div>
        <?php if (has_permission('faturalar', 'yazma')): ?>
        <a href="create.php?tip=alis" class="btn btn-danger">
            <i class="bi bi-arrow-down-circle me-2"></i>Alış Faturası
        </a>
        <a href="create.php?tip=satis" class="btn btn-success">
            <i class="bi bi-arrow-up-circle me-2"></i>Satış Faturası
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fatura Tipi</label>
                <select class="form-select" id="filterTip">
                    <option value="">Tümü</option>
                    <option value="alis">Alış Faturası</option>
                    <option value="satis">Satış Faturası</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ödeme Durumu</label>
                <select class="form-select" id="filterOdeme">
                    <option value="">Tümü</option>
                    <option value="odendi">Ödendi</option>
                    <option value="kismi">Kısmi Ödeme</option>
                    <option value="bekliyor">Bekliyor</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="filterStart">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="filterEnd">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover" id="faturaTable">
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tip</th>
                    <th>Cari</th>
                    <th>Tarih</th>
                    <th>Toplam</th>
                    <th>Ödenen</th>
                    <th>Kalan</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Ödeme/Tahsilat Modal -->
<div class="modal fade" id="odemeModal" tabindex="-1" aria-labelledby="odemeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="odemeModalLabel">Ödeme/Tahsilat İşlemi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Fatura Bilgileri</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Fatura No:</strong></td>
                                <td id="modalFaturaNo">-</td>
                            </tr>
                            <tr>
                                <td><strong>Tip:</strong></td>
                                <td id="modalFaturaTipi">-</td>
                            </tr>
                            <tr>
                                <td><strong>Tarih:</strong></td>
                                <td id="modalFaturaTarihi">-</td>
                            </tr>
                            <tr>
                                <td><strong>Tutar:</strong></td>
                                <td id="modalFaturaTutari">-</td>
                            </tr>
                            <tr>
                                <td><strong>Durum:</strong></td>
                                <td id="modalFaturaDurumu">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 id="modalIslemBasligi">Ödeme İşlemi</h6>
                        <form id="odemeForm">
                            <input type="hidden" id="modalFaturaId" name="fatura_id">
                            <div class="mb-3">
                                <label for="modalOdemeTarihi" class="form-label">Tarih</label>
                                <input type="date" class="form-control" id="modalOdemeTarihi" name="odeme_tarihi" required>
                            </div>
                            <div class="mb-3">
                                <label for="modalOdemeTutari" class="form-label">Tutar</label>
                                <input type="number" class="form-control" id="modalOdemeTutari" name="odeme_tutari" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="modalOdemeYontemi" class="form-label">Yöntem</label>
                                <select class="form-select" id="modalOdemeYontemi" name="odeme_yontemi" required>
                                    <option value="nakit">Nakit</option>
                                    <option value="kredi_karti">Kredi Kartı</option>
                                    <option value="banka_havalesi">Banka Havalesi</option>
                                    <option value="cek">Çek</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="modalAciklama" class="form-label">Açıklama</label>
                                <textarea class="form-control" id="modalAciklama" name="aciklama" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" id="modalKaydetBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
let table;

$(document).ready(function() {
    table = $('#faturaTable').DataTable({
        ajax: {
            url: '../../api/faturalar/list.php',
            data: function(d) {
                d.tip = $('#filterTip').val();
                d.odeme_durumu = $('#filterOdeme').val();
                d.start = $('#filterStart').val();
                d.end = $('#filterEnd').val();
            },
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'fatura_no' },
            { 
                data: 'fatura_tipi',
                render: function(data) {
                    return data == 'alis' 
                        ? '<span class="badge bg-danger">Alış</span>' 
                        : '<span class="badge bg-success">Satış</span>';
                }
            },
            { data: 'cari_unvan' },
            { 
                data: 'fatura_tarihi',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { 
                data: 'toplam_tutar',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: 'odenen_tutar',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { 
                data: null,
                render: function(data) {
                    const kalan = parseFloat(data.toplam_tutar) - parseFloat(data.odenen_tutar);
                    return formatMoney(kalan);
                }
            },
            { 
                data: null,
                render: function(data) {
                    if (data.odeme_durumu == 'odendi') {
                        return data.fatura_tipi == 'alis' 
                            ? '<span class="badge bg-success">Ödendi</span>'
                            : '<span class="badge bg-success">Tahsil Edildi</span>';
                    }
                    if (data.odeme_durumu == 'kismi') {
                        return data.fatura_tipi == 'alis' 
                            ? '<span class="badge bg-warning">Kısmi Ödeme</span>'
                            : '<span class="badge bg-warning">Kısmi Tahsilat</span>';
                    }
                    return data.fatura_tipi == 'alis' 
                        ? '<span class="badge bg-danger">Ödeme Bekliyor</span>'
                        : '<span class="badge bg-danger">Tahsilat Bekliyor</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    let buttons = '<a href="view.php?id=' + data.id + '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a> ';
                    
                    if (data.odeme_durumu !== 'odendi') {
                        buttons += '<button class="btn btn-sm btn-success" onclick="odemeYap(' + data.id + ')"><i class="bi bi-cash"></i></button> ';
                    }
                    
                    <?php if (has_permission('faturalar', 'silme')): ?>
                    buttons += '<button class="btn btn-sm btn-danger" onclick="sil(' + data.id + ')"><i class="bi bi-trash"></i></button>';
                    <?php endif; ?>
                    
                    return buttons;
                }
            }
        ],
        order: [[3, 'desc']]
    });
    
    // Filtre değişikliklerinde tabloyu yenile
    $('#filterTip, #filterOdeme, #filterStart, #filterEnd').on('change', function() {
        table.ajax.reload();
    });
    
    // Modal kaydet butonu
    $('#modalKaydetBtn').on('click', function() {
        const formData = {
            fatura_id: $('#modalFaturaId').val(),
            odeme_tarihi: $('#modalOdemeTarihi').val(),
            odeme_tutari: $('#modalOdemeTutari').val(),
            odeme_yontemi: $('#modalOdemeYontemi').val(),
            aciklama: $('#modalAciklama').val()
        };
        
        // Form doğrulama
        if (!formData.odeme_tarihi || !formData.odeme_tutari || !formData.odeme_yontemi) {
            showError('Lütfen tüm alanları doldurun');
            return;
        }
        
        if (parseFloat(formData.odeme_tutari) <= 0) {
            showError('Tutar 0\'dan büyük olmalıdır');
            return;
        }
        
        // AJAX ile ödeme kaydet
        $.ajax({
            url: '../../api/faturalar/odeme.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    $('#odemeModal').modal('hide');
                    table.ajax.reload();
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Ödeme kaydedilirken hata oluştu';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showError(errorMessage);
            }
        });
    });
});

function odemeYap(id) {
    // Fatura bilgilerini al
    $.ajax({
        url: '../../api/faturalar/get.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const fatura = response.data;
                
                // Modal başlığını ve içeriği doldur
                if (fatura.fatura_tipi === 'satis') {
                    $('#odemeModalLabel').text('Tahsilat İşlemi');
                    $('#modalIslemBasligi').text('Tahsilat İşlemi');
                    $('#modalKaydetBtn').text('Tahsilat Yap').removeClass('btn-success').addClass('btn-primary');
                } else {
                    $('#odemeModalLabel').text('Ödeme İşlemi');
                    $('#modalIslemBasligi').text('Ödeme İşlemi');
                    $('#modalKaydetBtn').text('Ödeme Yap').removeClass('btn-primary').addClass('btn-success');
                }
                
                // Fatura bilgilerini doldur
                $('#modalFaturaId').val(fatura.id);
                $('#modalFaturaNo').text(fatura.fatura_no);
                $('#modalFaturaTipi').html(fatura.fatura_tipi === 'alis' 
                    ? '<span class="badge bg-danger">Alış</span>' 
                    : '<span class="badge bg-success">Satış</span>');
                $('#modalFaturaTarihi').text(formatDate(fatura.fatura_tarihi));
                $('#modalFaturaTutari').text(formatMoney(fatura.toplam_tutar));
                if (fatura.odeme_durumu === 'odendi') {
                    $('#modalFaturaDurumu').html(fatura.fatura_tipi === 'alis' 
                        ? '<span class="badge bg-success">Ödendi</span>' 
                        : '<span class="badge bg-success">Tahsil Edildi</span>');
                } else if (fatura.odeme_durumu === 'kismi') {
                    $('#modalFaturaDurumu').html(fatura.fatura_tipi === 'alis' 
                        ? '<span class="badge bg-warning">Kısmi Ödeme</span>' 
                        : '<span class="badge bg-warning">Kısmi Tahsilat</span>');
                } else {
                    $('#modalFaturaDurumu').html(fatura.fatura_tipi === 'alis' 
                        ? '<span class="badge bg-danger">Ödeme Bekliyor</span>' 
                        : '<span class="badge bg-danger">Tahsilat Bekliyor</span>');
                }
                
                // Form alanlarını doldur
                $('#modalOdemeTarihi').val(new Date().toISOString().split('T')[0]);
                $('#modalOdemeTutari').val(fatura.toplam_tutar);
                $('#modalAciklama').val('');
                
                // Modalı aç
                $('#odemeModal').modal('show');
            } else {
                showError(response.message);
            }
        },
        error: function() {
            showError('Fatura bilgileri alınırken hata oluştu');
        }
    });
}

function sil(id) {
    confirmDelete(function() {
        $.ajax({
            url: '../../api/faturalar/delete.php',
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
                showError('Fatura silinirken hata oluştu: ' + error);
            }
        });
    });
}
</script>

