<?php
$page_title = 'Mobil Uygulama Test';
require_once 'includes/auth.php';
require_login();
require_once 'includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-phone me-2"></i>Mobil Uygulama Test</h5>
    <span class="text-muted">Push notification ve PWA testleri</span>
</div>

<div class="row g-4">
    <!-- PWA Test -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">PWA (Progressive Web App) Test</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Service Worker Durumu:</h6>
                    <div id="sw-status" class="badge bg-secondary">Kontrol ediliyor...</div>
                </div>
                
                <div class="mb-3">
                    <h6>Bildirim İzni:</h6>
                    <div id="notification-permission" class="badge bg-secondary">Kontrol ediliyor...</div>
                </div>
                
                <div class="mb-3">
                    <h6>FCM Token:</h6>
                    <div id="fcm-token" class="text-muted small">Yükleniyor...</div>
                </div>
                
                <button class="btn btn-primary" onclick="requestNotificationPermission()">
                    <i class="bi bi-bell me-2"></i>Bildirim İzni İste
                </button>
                
                <button class="btn btn-success" onclick="testNotification()">
                    <i class="bi bi-bell-fill me-2"></i>Test Bildirimi Gönder
                </button>
            </div>
        </div>
    </div>
    
    <!-- Çek Vade Test -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0">Çek Vade Bildirim Testi</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Test Çek Bilgileri:</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" class="form-control" id="test-cek-no" placeholder="Çek No" value="123456">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control" id="test-cek-tutar" placeholder="Tutar" value="5000">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <input type="text" class="form-control" id="test-cek-banka" placeholder="Banka" value="Ziraat Bankası">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control" id="test-cek-kalan-gun" placeholder="Kalan Gün" value="2">
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-warning" onclick="testCekNotification()">
                    <i class="bi bi-calendar-check me-2"></i>Çek Vade Bildirimi Test Et
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tahsilat Test -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Tahsilat Bildirim Testi</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Test Tahsilat Bilgileri:</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" class="form-control" id="test-cari-unvan" placeholder="Cari Unvan" value="Test Müşteri">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control" id="test-tahsilat-tutar" placeholder="Tutar" value="10000">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <input type="text" class="form-control" id="test-fatura-no" placeholder="Fatura No" value="FAT-2024-001">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control" id="test-tahsilat-kalan-gun" placeholder="Kalan Gün" value="1">
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-success" onclick="testTahsilatNotification()">
                    <i class="bi bi-cash-stack me-2"></i>Tahsilat Bildirimi Test Et
                </button>
            </div>
        </div>
    </div>
    
    <!-- Cron Job Test -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Cron Job Test</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Otomatik bildirim sistemini test edin:</p>
                
                <button class="btn btn-info" onclick="runCekVadeTakip()">
                    <i class="bi bi-clock me-2"></i>Çek Vade Takibi Çalıştır
                </button>
                
                <button class="btn btn-info" onclick="runTahsilatTakip()">
                    <i class="bi bi-clock-history me-2"></i>Tahsilat Takibi Çalıştır
                </button>
                
                <div id="cron-result" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Sonuçlar -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">Test Sonuçları</h6>
    </div>
    <div class="card-body">
        <div id="test-results" class="text-muted">
            Test sonuçları burada görünecek...
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let fcmToken = null;

$(document).ready(function() {
    checkServiceWorkerStatus();
    checkNotificationPermission();
    getFCMToken();
});

function checkServiceWorkerStatus() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration().then(function(registration) {
            if (registration) {
                $('#sw-status').removeClass('bg-secondary').addClass('bg-success').text('Aktif');
            } else {
                $('#sw-status').removeClass('bg-secondary').addClass('bg-danger').text('Pasif');
            }
        });
    } else {
        $('#sw-status').removeClass('bg-secondary').addClass('bg-danger').text('Desteklenmiyor');
    }
}

function checkNotificationPermission() {
    if ('Notification' in window) {
        const permission = Notification.permission;
        if (permission === 'granted') {
            $('#notification-permission').removeClass('bg-secondary').addClass('bg-success').text('İzin Verildi');
        } else if (permission === 'denied') {
            $('#notification-permission').removeClass('bg-secondary').addClass('bg-danger').text('Reddedildi');
        } else {
            $('#notification-permission').removeClass('bg-secondary').addClass('bg-warning').text('İstenmedi');
        }
    } else {
        $('#notification-permission').removeClass('bg-secondary').addClass('bg-danger').text('Desteklenmiyor');
    }
}

function getFCMToken() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration().then(function(registration) {
            if (registration && 'PushManager' in window) {
                registration.pushManager.getSubscription().then(function(subscription) {
                    if (subscription) {
                        fcmToken = JSON.stringify(subscription);
                        $('#fcm-token').text(fcmToken.substring(0, 50) + '...');
                    } else {
                        $('#fcm-token').text('Token bulunamadı');
                    }
                });
            }
        });
    }
}

function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                showSuccess('Bildirim izni verildi!');
                checkNotificationPermission();
                getFCMToken();
            } else {
                showError('Bildirim izni reddedildi!');
                checkNotificationPermission();
            }
        });
    } else {
        showError('Bu tarayıcı bildirimleri desteklemiyor!');
    }
}

function testNotification() {
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification('Fidan Takip Test', {
            body: 'Bu bir test bildirimidir!',
            icon: '<?php echo url('mobiluygulamaiconu.png'); ?>',
            badge: '<?php echo url('mobiluygulamaiconu.png'); ?>'
        });
        
        notification.onclick = function() {
            window.focus();
            notification.close();
        };
        
        addTestResult('✅ Test bildirimi gönderildi');
    } else {
        showError('Bildirim izni verilmemiş!');
    }
}

function testCekNotification() {
    const cekData = {
        cek_no: $('#test-cek-no').val(),
        tutar: $('#test-cek-tutar').val(),
        banka: $('#test-cek-banka').val(),
        kalan_gun: $('#test-cek-kalan-gun').val()
    };
    
    $.post('<?php echo url('api/mobile/test-notification.php'); ?>', {
        type: 'cek_vade',
        data: cekData
    }, function(response) {
        if (response.success) {
            addTestResult('✅ Çek vade bildirimi test edildi: ' + response.message);
        } else {
            addTestResult('❌ Çek vade bildirimi hatası: ' + response.message);
        }
    });
}

function testTahsilatNotification() {
    const tahsilatData = {
        cari_unvan: $('#test-cari-unvan').val(),
        tutar: $('#test-tahsilat-tutar').val(),
        fatura_no: $('#test-fatura-no').val(),
        kalan_gun: $('#test-tahsilat-kalan-gun').val()
    };
    
    $.post('<?php echo url('api/mobile/test-notification.php'); ?>', {
        type: 'tahsilat',
        data: tahsilatData
    }, function(response) {
        if (response.success) {
            addTestResult('✅ Tahsilat bildirimi test edildi: ' + response.message);
        } else {
            addTestResult('❌ Tahsilat bildirimi hatası: ' + response.message);
        }
    });
}

function runCekVadeTakip() {
    $('#cron-result').html('<div class="spinner-border spinner-border-sm" role="status"></div> Çalıştırılıyor...');
    
    $.get('<?php echo url('api/mobile/cek-vade-takip.php'); ?>', function(response) {
        if (response.success) {
            const ozet = response.data.ozet;
            $('#cron-result').html(`
                <div class="alert alert-success">
                    <strong>✅ Çek Vade Takibi Tamamlandı!</strong><br>
                    Toplam Çek: ${ozet.toplam_cek}<br>
                    Toplam Tutar: ₺${ozet.toplam_tutar.toLocaleString()}<br>
                    Bugün Vadesi Gelen: ${ozet.bugun_vadesi_gelen}<br>
                    Bu Hafta Vadesi Gelen: ${ozet.bu_hafta_vadesi_gelen}<br>
                    Bildirim Gönderilen Kullanıcı: ${ozet.bildirim_gonderilen_kullanici}
                </div>
            `);
        } else {
            $('#cron-result').html(`<div class="alert alert-danger">❌ Hata: ${response.message}</div>`);
        }
    });
}

function runTahsilatTakip() {
    $('#cron-result').html('<div class="spinner-border spinner-border-sm" role="status"></div> Çalıştırılıyor...');
    
    $.get('<?php echo url('api/mobile/tahsilat-takip.php'); ?>', function(response) {
        if (response.success) {
            const ozet = response.data.ozet;
            $('#cron-result').html(`
                <div class="alert alert-success">
                    <strong>✅ Tahsilat Takibi Tamamlandı!</strong><br>
                    Toplam Tahsilat: ${ozet.toplam_tahsilat}<br>
                    Toplam Tutar: ₺${ozet.toplam_tutar.toLocaleString()}<br>
                    Bugün Tahsilat: ${ozet.bugun_tahsilat}<br>
                    Bu Hafta Tahsilat: ${ozet.bu_hafta_tahsilat}<br>
                    Bildirim Gönderilen Kullanıcı: ${ozet.bildirim_gonderilen_kullanici}
                </div>
            `);
        } else {
            $('#cron-result').html(`<div class="alert alert-danger">❌ Hata: ${response.message}</div>`);
        }
    });
}

function addTestResult(message) {
    const timestamp = new Date().toLocaleTimeString();
    const result = `<div class="mb-2"><small class="text-muted">[${timestamp}]</small> ${message}</div>`;
    $('#test-results').prepend(result);
}
</script>
