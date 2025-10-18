<?php
$page_title = 'Ana Panel';
require_once 'includes/auth.php';
require_login();
require_once 'includes/header.php';

$user = get_user();
$firma_id = get_firma_id();
?>

<div class="top-bar">
    <h5><i class="bi bi-house-door me-2"></i>Ana Panel</h5>
    <span class="text-muted">Hoş geldiniz, <?php echo $user['ad_soyad']; ?>!</span>
</div>

<?php if (is_super_admin()): ?>
    <!-- Super Admin Dashboard -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #e3f2fd; color: #2196f3;">
                    <i class="bi bi-building"></i>
                </div>
                <h3 id="totalFirms">-</h3>
                <p>Toplam Firma</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #f3e5f5; color: #9c27b0;">
                    <i class="bi bi-people"></i>
                </div>
                <h3 id="totalUsers">-</h3>
                <p>Toplam Kullanıcı</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #e8f5e9; color: #4caf50;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 id="activeFirms">-</h3>
                <p>Aktif Firma</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #fff3e0; color: #ff9800;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h3 id="todayTransactions">-</h3>
                <p>Bugünkü İşlemler</p>
            </div>
        </div>
    </div>
    
    <!-- Grafikler -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aylık Firma Aktivitesi</h6>
                </div>
                <div class="card-body">
                    <canvas id="firmaAktiviteChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Kullanıcı Dağılımı</h6>
                </div>
                <div class="card-body">
                    <canvas id="kullaniciDagilimChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Firmalar</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="firmsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Firma Adı</th>
                                    <th>Vergi No</th>
                                    <th>Telefon</th>
                                    <th>Durum</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Firma Yöneticisi / Kullanıcı Dashboard -->
    
    <!-- Hızlı Erişim Butonları -->
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Hızlı Erişim</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php if (has_permission('cariler', 'yazma')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo url('modules/cariler/list.php'); ?>" class="btn btn-outline-primary w-100">
                                <i class="bi bi-person-plus me-2"></i>Yeni Cari
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('urunler', 'yazma')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo url('modules/stok/list.php'); ?>" class="btn btn-outline-success w-100">
                                <i class="bi bi-box-seam me-2"></i>Yeni Ürün
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('faturalar', 'yazma')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo url('modules/faturalar/create.php?tip=alis'); ?>" class="btn btn-outline-warning w-100">
                                <i class="bi bi-receipt me-2"></i>Alış Faturası
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="<?php echo url('modules/faturalar/create.php?tip=satis'); ?>" class="btn btn-outline-info w-100">
                                <i class="bi bi-receipt me-2"></i>Satış Faturası
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('kasa', 'yazma')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo url('modules/kasa/list.php'); ?>" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-cash-stack me-2"></i>Kasa İşlemi
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_permission('cekler', 'yazma')): ?>
                        <div class="col-md-2">
                            <a href="<?php echo url('modules/cekler/list.php'); ?>" class="btn btn-outline-dark w-100">
                                <i class="bi bi-file-earmark-check me-2"></i>Çek İşlemi
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- İstatistik Kartları -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #e3f2fd; color: #2196f3;">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <h3 id="totalCariler">-</h3>
                <p>Toplam Cari</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #f3e5f5; color: #9c27b0;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h3 id="totalUrunler">-</h3>
                <p>Toplam Ürün</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #e8f5e9; color: #4caf50;">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <h3 id="totalAlacaklar">-</h3>
                <p>Toplam Alacak</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon" style="background: #ffebee; color: #f44336;">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <h3 id="totalBorclar">-</h3>
                <p>Toplam Borç</p>
            </div>
        </div>
    </div>
    
    <!-- Grafikler -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aylık Gelir-Gider</h6>
                </div>
                <div class="card-body">
                    <canvas id="gelirGiderChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Stok Durumu</h6>
                </div>
                <div class="card-body">
                    <canvas id="stokDurumChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Son Hareketler ve Uyarılar -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Son Hareketler</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="sonHareketlerTable">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Tip</th>
                                    <th>Açıklama</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h6 class="mb-0">Uyarılar</h6>
                </div>
                <div class="card-body">
                    <div id="uyarilarListesi">
                        <!-- Uyarılar buraya yüklenecek -->
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php require_once 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    <?php if (is_super_admin()): ?>
        loadSuperAdminStats();
        loadFirmaAktiviteChart();
        loadKullaniciDagilimChart();
        loadFirmsTable();
    <?php else: ?>
        loadFirmaStats();
        loadAlacaklar();
        loadBorclar();
        loadGelirGiderChart();
        loadStokDurumChart();
        loadSonHareketler();
        loadUyarilar();
    <?php endif; ?>
});

<?php if (is_super_admin()): ?>
function loadSuperAdminStats() {
    $.get('api/admin/stats.php')
        .done(function(response) {
            if (response.success) {
                $('#totalFirms').text(response.data.total_firms);
                $('#totalUsers').text(response.data.total_users);
                $('#activeFirms').text(response.data.active_firms);
                $('#todayTransactions').text(response.data.today_transactions);
            }
        })
        .fail(function() {
            console.log('Admin stats yüklenemedi');
        });
}

function loadFirmaAktiviteChart() {
    $.get('api/admin/firma-aktivite.php')
        .done(function(response) {
            if (response.success) {
                const ctx = document.getElementById('firmaAktiviteChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            label: 'Firma Aktivitesi',
                            data: response.data.values,
                            borderColor: '#2196f3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
}

function loadKullaniciDagilimChart() {
    $.get('api/admin/kullanici-dagilim.php')
        .done(function(response) {
            if (response.success) {
                const ctx = document.getElementById('kullaniciDagilimChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            data: response.data.values,
                            backgroundColor: [
                                '#2196f3',
                                '#9c27b0',
                                '#4caf50',
                                '#ff9800'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
}

function loadFirmsTable() {
    $('#firmsTable').DataTable({
        ajax: {
            url: 'api/admin/firmalar.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            { data: 'firma_adi' },
            { data: 'vergi_no' },
            { data: 'telefon' },
            { 
                data: 'aktif',
                render: function(data) {
                    return data ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Pasif</span>';
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
                render: function(data) {
                    return `<a href="admin/firmalar.php?id=${data.id}" class="btn btn-sm btn-primary">Detay</a>`;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json'
        }
    });
}

<?php else: ?>
function loadFirmaStats() {
    $.get('api/dashboard/firma_stats.php')
        .done(function(response) {
            console.log('Firma Stats Response:', response);
            if (response.success) {
                $('#totalCariler').text(response.data.total_cariler);
                $('#totalUrunler').text(response.data.total_urunler);
            }
        })
        .fail(function() {
            console.log('Firma stats yüklenemedi');
            $('#totalCariler').text('0');
            $('#totalUrunler').text('0');
        });
}

function loadAlacaklar() {
    $.get('api/dashboard/alacaklar.php')
        .done(function(response) {
            console.log('Alacaklar Response:', response);
            if (response.success) {
                const toplam = response.data.reduce((sum, item) => sum + parseFloat(item.bakiye), 0);
                $('#totalAlacaklar').text(formatMoney(toplam));
            }
        })
        .fail(function() {
            console.log('Alacaklar yüklenemedi');
            $('#totalAlacaklar').text('₺0');
        });
}

function loadBorclar() {
    $.get('api/dashboard/borclar.php')
        .done(function(response) {
            console.log('Borclar Response:', response);
            if (response.success) {
                const toplam = response.data.reduce((sum, item) => sum + Math.abs(parseFloat(item.bakiye)), 0);
                $('#totalBorclar').text(formatMoney(toplam));
            }
        })
        .fail(function() {
            console.log('Borclar yüklenemedi');
            $('#totalBorclar').text('₺0');
        });
}

function loadGelirGiderChart() {
    $.get('api/dashboard/gelir-gider-chart.php')
        .done(function(response) {
            if (response.success) {
                const ctx = document.getElementById('gelirGiderChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: response.data.labels,
                        datasets: [
                            {
                                label: 'Gelir',
                                data: response.data.gelirler,
                                backgroundColor: '#4caf50'
                            },
                            {
                                label: 'Gider',
                                data: response.data.giderler,
                                backgroundColor: '#f44336'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
}

function loadStokDurumChart() {
    $.get('api/dashboard/stok-durum-chart.php')
        .done(function(response) {
            if (response.success) {
                const ctx = document.getElementById('stokDurumChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            data: response.data.values,
                            backgroundColor: [
                                '#4caf50',
                                '#ff9800',
                                '#f44336'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
}

function loadSonHareketler() {
    $.get('api/dashboard/son-hareketler.php')
        .done(function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(item) {
                    const tipClass = item.tip === 'gelir' ? 'text-success' : 'text-danger';
                    html += `
                        <tr>
                            <td>${formatDate(item.tarih)}</td>
                            <td><span class="badge ${tipClass}">${item.tip_display}</span></td>
                            <td>${item.aciklama}</td>
                            <td class="text-end">${formatMoney(item.tutar)}</td>
                        </tr>
                    `;
                });
                $('#sonHareketlerTable tbody').html(html);
            }
        });
}

function loadUyarilar() {
    $.get('api/dashboard/uyarilar.php')
        .done(function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(uyari) {
                    const iconClass = uyari.tip === 'kritik' ? 'bi-exclamation-triangle text-danger' : 
                                     uyari.tip === 'uyari' ? 'bi-exclamation-circle text-warning' : 
                                     'bi-info-circle text-info';
                    html += `
                        <div class="alert alert-${uyari.tip === 'kritik' ? 'danger' : uyari.tip === 'uyari' ? 'warning' : 'info'} alert-sm">
                            <i class="bi ${iconClass} me-2"></i>
                            ${uyari.mesaj}
                        </div>
                    `;
                });
                $('#uyarilarListesi').html(html);
            }
        });
}
<?php endif; ?>
</script>