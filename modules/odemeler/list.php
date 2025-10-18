<?php
$page_title = 'Ödeme Yönetimi';
require_once '../../includes/auth.php';
require_login();
require_permission('odemeler', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-credit-card me-2"></i>Ödeme Yönetimi</h5>
</div>

<!-- Özet Kartlar -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon" style="background: #f8d7da; color: #dc3545;">
                <i class="bi bi-exclamation-circle"></i>
            </div>
            <h3 id="odenmeyenFatura">0</h3>
            <p>Ödenmemiş Fatura</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon" style="background: #fff3cd; color: #856404;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <h3 id="kismiOdeme">0</h3>
            <p>Kısmi Ödeme</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="icon" style="background: #d4edda; color: #28a745;">
                <i class="bi bi-check-circle"></i>
            </div>
            <h3 id="odenenFatura">0</h3>
            <p>Ödenen Fatura</p>
        </div>
    </div>
</div>

<!-- Ödenecek Faturalar -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Ödenecek Faturalar</h6>
    </div>
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

<!-- Ödeme Modal -->
<div class="modal fade" id="odemeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Ödeme Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="odemeForm">
                <input type="hidden" id="fatura_id" name="fatura_id">
                <div class="modal-body">
                    <div class="alert alert-info" id="faturaInfo"></div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Ödeme Tarihi *</label>
                            <input type="date" class="form-control" name="odeme_tarihi" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ödeme Tutarı (₺) *</label>
                            <input type="number" step="0.01" class="form-control" name="tutar" id="odenecekTutar" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" name="odeme_yontemi">
                                <option value="nakit">Nakit</option>
                                <option value="havale">Havale/EFT</option>
                                <option value="kredi_karti">Kredi Kartı</option>
                                <option value="cek">Çek</option>
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
                    <button type="submit" class="btn btn-primary" id="odemeButton">Ödeme Yap</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
let table;

$(document).ready(function() {
    loadStats();
    
    table = $('#faturaTable').DataTable({
        ajax: {
            url: '../../api/odemeler/faturalar.php',
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
                    return '<strong class="text-danger">' + formatMoney(kalan) + '</strong>';
                }
            },
            { 
                data: 'odeme_durumu',
                render: function(data) {
                    if (data == 'odendi') return '<span class="badge bg-success">Ödendi</span>';
                    if (data == 'kismi') return '<span class="badge bg-warning">Kısmi</span>';
                    return '<span class="badge bg-danger">Bekliyor</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    if (data.odeme_durumu == 'odendi') {
                        return '<button class="btn btn-sm btn-secondary" disabled>Ödendi</button>';
                    }
                    return '<button class="btn btn-sm btn-success" onclick="odemeYap(' + data.id + ', \'' + data.fatura_no + '\', ' + data.toplam_tutar + ', ' + data.odenen_tutar + ', \'' + data.fatura_tipi + '\')"><i class="bi bi-cash"></i> ' + (data.fatura_tipi == 'satis' ? 'Tahsilat Yap' : 'Ödeme Yap') + '</button>';
                }
            }
        ],
        order: [[3, 'desc']]
    });
    
    $('#odemeForm').on('submit', function(e) {
        e.preventDefault();
        odemeKaydet();
    });
});

function loadStats() {
    $.get('../../api/odemeler/stats.php', function(response) {
        if (response.success) {
            $('#odenmeyenFatura').text(response.data.odenmemiş);
            $('#kismiOdeme').text(response.data.kismi);
            $('#odenenFatura').text(response.data.odenen);
        }
    });
}

function odemeYap(faturaId, faturaNo, toplamTutar, odenenTutar, faturaTipi) {
    const kalanTutar = parseFloat(toplamTutar) - parseFloat(odenenTutar);
    
    // Modal başlığını ve buton metnini güncelle
    const isTahsilat = faturaTipi === 'satis';
    $('#modalTitle').text(isTahsilat ? 'Tahsilat Ekle' : 'Ödeme Ekle');
    $('#odemeButton').text(isTahsilat ? 'Tahsilat Yap' : 'Ödeme Yap');
    
    $('#fatura_id').val(faturaId);
    $('#odenecekTutar').val(kalanTutar.toFixed(2)).attr('max', kalanTutar.toFixed(2));
    
    $('#faturaInfo').html(`
        <strong>Fatura No:</strong> ${faturaNo}<br>
        <strong>Toplam Tutar:</strong> ${formatMoney(toplamTutar)}<br>
        <strong>Ödenen:</strong> ${formatMoney(odenenTutar)}<br>
        <strong>Kalan:</strong> <span class="text-danger">${formatMoney(kalanTutar)}</span>
    `);
    
    $('#odemeModal').modal('show');
}

function odemeKaydet() {
    console.log('Ödeme kaydetme başlatılıyor...');
    const formData = $('#odemeForm').serializeArray();
    const data = {};
    formData.forEach(item => data[item.name] = item.value);
    
    console.log('Gönderilen data:', data);
    
    $.ajax({
        url: '../../api/odemeler/create.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            console.log('API Response:', response);
            if (response.success) {
                showSuccess('Ödeme başarıyla kaydedildi!');
                $('#odemeModal').modal('hide');
                table.ajax.reload();
                loadStats();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr);
            console.error('Response Text:', xhr.responseText);
            showError(xhr.responseJSON?.message || 'Bir hata oluştu!');
        }
    });
}
</script>

