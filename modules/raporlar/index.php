<?php
$page_title = 'Raporlar';
require_once '../../includes/auth.php';
require_login();
require_permission('raporlar', 'okuma');
require_once '../../includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-graph-up me-2"></i>Raporlar</h5>
</div>

<!-- Hızlı Filtre -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="baslangic" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" id="bitis" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100" onclick="raporYukle()">
                    <i class="bi bi-search me-2"></i>Rapor Oluştur
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Özet Kartlar -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #d4edda; color: #28a745;">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
            <h3 id="satislar">0,00 ₺</h3>
            <p>Satışlar</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #f8d7da; color: #dc3545;">
                <i class="bi bi-arrow-down-circle"></i>
            </div>
            <h3 id="alislar">0,00 ₺</h3>
            <p>Alışlar</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #d1ecf1; color: #0c5460;">
                <i class="bi bi-wallet2"></i>
            </div>
            <h3 id="kar">0,00 ₺</h3>
            <p>Kar/Zarar</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: #fff3cd; color: #856404;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <h3 id="kasaBakiye">0,00 ₺</h3>
            <p>Kasa Bakiye</p>
        </div>
    </div>
</div>

<!-- Cari Bakiyeler -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-down me-2"></i>En Çok Alacaklılar</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Cari</th>
                            <th class="text-end">Tutar</th>
                        </tr>
                    </thead>
                    <tbody id="alacaklilar"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-up me-2"></i>En Çok Borçlular</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Cari</th>
                            <th class="text-end">Tutar</th>
                        </tr>
                    </thead>
                    <tbody id="borclilar"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ürün Raporları -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>En Çok Satan Ürünler</h6>
    </div>
    <div class="card-body">
        <table class="table table-hover" id="urunRapor">
            <thead>
                <tr>
                    <th>Ürün Adı</th>
                    <th class="text-end">Satış Miktarı</th>
                    <th class="text-end">Satış Tutarı</th>
                    <th class="text-end">Mevcut Stok</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Aylık Grafik -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Aylık Ciro Grafiği</h6>
    </div>
    <div class="card-body">
        <canvas id="ciroGrafik" height="80"></canvas>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let ciroChart;

$(document).ready(function() {
    raporYukle();
});

function raporYukle() {
    const baslangic = $('#baslangic').val();
    const bitis = $('#bitis').val();
    
    console.log('Rapor yükleme - Başlangıç:', baslangic, 'Bitiş:', bitis);
    
    // Genel istatistikler
    $.get('../../api/raporlar/genel.php', { baslangic, bitis })
        .done(function(response) {
            console.log('Genel rapor yanıtı:', response);
            if (response.success) {
                const data = response.data;
                $('#satislar').text(formatMoney(data.satislar));
                $('#alislar').text(formatMoney(data.alislar));
                $('#kar').text(formatMoney(data.kar));
                $('#kasaBakiye').text(formatMoney(data.kasa_bakiye));
            } else {
                console.error('Genel rapor hatası:', response.message);
                showError('Genel rapor yüklenemedi: ' + response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Genel rapor AJAX hatası:', status, error);
            console.error('XHR Response:', xhr.responseText);
            showError('Genel rapor yüklenirken hata oluştu');
        });
    
    // Alacaklılar
    $.get('../../api/raporlar/alacaklar.php', { limit: 5 })
        .done(function(response) {
            console.log('Alacaklılar yanıtı:', response);
            if (response.success) {
                let html = '';
                response.data.forEach(function(cari) {
                    html += `<tr>
                        <td>${cari.unvan}</td>
                        <td class="text-end text-success fw-bold">${formatMoney(cari.bakiye)}</td>
                    </tr>`;
                });
                $('#alacaklilar').html(html || '<tr><td colspan="2" class="text-center">Kayıt yok</td></tr>');
            } else {
                console.error('Alacaklılar hatası:', response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Alacaklılar AJAX hatası:', status, error);
        });
    
    // Borçlular
    $.get('../../api/raporlar/borclar.php', { limit: 5 })
        .done(function(response) {
            console.log('Borçlular yanıtı:', response);
            if (response.success) {
                let html = '';
                response.data.forEach(function(cari) {
                    html += `<tr>
                        <td>${cari.unvan}</td>
                        <td class="text-end text-danger fw-bold">${formatMoney(Math.abs(cari.bakiye))}</td>
                    </tr>`;
                });
                $('#borclilar').html(html || '<tr><td colspan="2" class="text-center">Kayıt yok</td></tr>');
            } else {
                console.error('Borçlular hatası:', response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Borçlular AJAX hatası:', status, error);
        });
    
    // Ürün raporu
    $.get('../../api/raporlar/urunler.php', { baslangic, bitis })
        .done(function(response) {
            console.log('Ürün raporu yanıtı:', response);
            if (response.success) {
                let html = '';
                response.data.forEach(function(urun) {
                    html += `<tr>
                        <td>${urun.urun_adi}</td>
                        <td class="text-end">${urun.toplam_miktar}</td>
                        <td class="text-end">${formatMoney(urun.toplam_tutar)}</td>
                        <td class="text-end">${urun.stok}</td>
                    </tr>`;
                });
                $('#urunRapor tbody').html(html || '<tr><td colspan="4" class="text-center">Kayıt yok</td></tr>');
            } else {
                console.error('Ürün raporu hatası:', response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Ürün raporu AJAX hatası:', status, error);
        });
    
    // Aylık grafik
    $.get('../../api/raporlar/aylik.php')
        .done(function(response) {
            console.log('Aylık grafik yanıtı:', response);
            if (response.success) {
                const labels = response.data.map(d => d.ay);
                const satislar = response.data.map(d => parseFloat(d.satislar));
                const alislar = response.data.map(d => parseFloat(d.alislar));
                
                if (ciroChart) ciroChart.destroy();
                
                const ctx = document.getElementById('ciroGrafik').getContext('2d');
                ciroChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Satışlar',
                                data: satislar,
                                borderColor: 'rgb(40, 167, 69)',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Alışlar',
                                data: alislar,
                                borderColor: 'rgb(220, 53, 69)',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('tr-TR') + ' ₺';
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                console.error('Aylık grafik hatası:', response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Aylık grafik AJAX hatası:', status, error);
        });
}

// Helper fonksiyonlar
function formatMoney(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0,00 ₺';
    }
    
    const num = parseFloat(amount);
    return num.toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' ₺';
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
</script>

