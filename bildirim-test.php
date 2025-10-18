<?php
$page_title = 'Bildirim Test';
require_once 'includes/auth.php';
require_login();
require_once 'includes/header.php';
?>

<div class="top-bar">
    <h5><i class="bi bi-bell me-2"></i>Bildirim Test Sistemi</h5>
    <span class="text-muted">Firebase olmadan çalışan bildirim sistemi</span>
</div>

<div class="row g-4">
    <!-- Bildirim Durumu -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Bildirim Durumu</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <h6>Browser Desteği:</h6>
                        <div id="browser-support" class="badge bg-secondary">Kontrol ediliyor...</div>
                    </div>
                    <div class="col-md-3">
                        <h6>Bildirim İzni:</h6>
                        <div id="notification-permission" class="badge bg-secondary">Kontrol ediliyor...</div>
                    </div>
                    <div class="col-md-3">
                        <h6>Aktif Bildirimler:</h6>
                        <div id="active-notifications" class="badge bg-info">0</div>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="requestPermission()">
                            <i class="bi bi-bell me-2"></i>İzin İste
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Test Bildirimleri -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h6 class="mb-0">Test Bildirimleri</h6>
            </div>
            <div class="card-body">
                <button class="btn btn-warning mb-2 w-100" onclick="sendTestNotification('bugun')">
                    🚨 Bugün Vadesi Gelen Çek
                </button>
                <button class="btn btn-warning mb-2 w-100" onclick="sendTestNotification('yarin')">
                    ⚠️ Yarın Vadesi Gelen Çek
                </button>
                <button class="btn btn-success mb-2 w-100" onclick="sendTestNotification('tahsilat')">
                    💰 Tahsilat Bildirimi
                </button>
                <button class="btn btn-info mb-2 w-100" onclick="sendTestNotification('genel')">
                    📢 Genel Bildirim
                </button>
            </div>
        </div>
    </div>
    
    <!-- Otomatik Bildirimler -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Otomatik Bildirimler</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Sistemdeki vadesi yaklaşan çek ve tahsilatları kontrol edin:</p>
                <button class="btn btn-success w-100 mb-2" onclick="loadAndShowNotifications()">
                    <i class="bi bi-lightning-charge me-2"></i>Tüm Bildirimleri Göster
                </button>
                <div id="notification-count" class="mt-3 text-center"></div>
            </div>
        </div>
    </div>
    
    <!-- Bildirim Listesi -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Gönderilen Bildirimler</h6>
            </div>
            <div class="card-body">
                <div id="notification-list"></div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let notificationCount = 0;

$(document).ready(function() {
    checkBrowserSupport();
    checkPermission();
});

function checkBrowserSupport() {
    if ('Notification' in window) {
        $('#browser-support').removeClass('bg-secondary').addClass('bg-success').text('Destekleniyor ✓');
    } else {
        $('#browser-support').removeClass('bg-secondary').addClass('bg-danger').text('Desteklenmiyor ✗');
    }
}

function checkPermission() {
    if ('Notification' in window) {
        const permission = Notification.permission;
        if (permission === 'granted') {
            $('#notification-permission').removeClass('bg-secondary').addClass('bg-success').text('İzin Verildi ✓');
        } else if (permission === 'denied') {
            $('#notification-permission').removeClass('bg-secondary').addClass('bg-danger').text('Reddedildi ✗');
        } else {
            $('#notification-permission').removeClass('bg-secondary').addClass('bg-warning').text('Beklemede ⏳');
        }
    }
}

function requestPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function(permission) {
            checkPermission();
            if (permission === 'granted') {
                showSuccess('Bildirim izni verildi!');
            } else {
                showError('Bildirim izni reddedildi!');
            }
        });
    } else {
        showError('Bu tarayıcı bildirimleri desteklemiyor!');
    }
}

function sendTestNotification(type) {
    if (Notification.permission !== 'granted') {
        showError('Önce bildirim izni vermelisiniz!');
        return;
    }
    
    let title, body, icon, data;
    
    switch(type) {
        case 'bugun':
            title = '🚨 Çek Vadesi Bugün!';
            body = 'Çek No: 123456 - Tutar: ₺5,000.00 - Banka: Ziraat Bankası';
            data = { url: '<?php echo url('modules/cekler/list.php'); ?>' };
            break;
        case 'yarin':
            title = '⚠️ Çek Vadesi Yarın!';
            body = 'Çek No: 789012 - Tutar: ₺3,500.00 - Banka: İş Bankası';
            data = { url: '<?php echo url('modules/cekler/list.php'); ?>' };
            break;
        case 'tahsilat':
            title = '💰 Tahsilat Günü Bugün!';
            body = 'Test Müşteri\'den ₺10,000.00 tahsilat bekleniyor!';
            data = { url: '<?php echo url('modules/cariler/list.php'); ?>' };
            break;
        case 'genel':
            title = '📢 Fidan Takip Bildirimi';
            body = 'Test bildirimi başarıyla gönderildi!';
            data = { url: '<?php echo url('dashboard.php'); ?>' };
            break;
    }
    
    icon = '<?php echo url('mobiluygulamaiconu.png'); ?>';
    
    const notification = new Notification(title, {
        body: body,
        icon: icon,
        badge: icon,
        tag: 'test-' + Date.now(),
        requireInteraction: type === 'bugun' || type === 'yarin',
        data: data
    });
    
    notification.onclick = function() {
        window.focus();
        if (data && data.url) {
            window.location.href = data.url;
        }
        notification.close();
    };
    
    notificationCount++;
    $('#active-notifications').text(notificationCount);
    
    addToNotificationList(title, body);
}

function loadAndShowNotifications() {
    $.get('<?php echo url('api/mobile/browser-notification.php'); ?>', function(response) {
        if (response.success) {
            const data = response.data;
            const bildirimler = data.bildirimler;
            
            $('#notification-count').html(`
                <div class="alert alert-info">
                    <strong>Toplam ${data.toplam} bildirim bulundu!</strong><br>
                    🔔 Çek Bildirimleri: ${data.cek_sayisi}<br>
                    💰 Tahsilat Bildirimleri: ${data.tahsilat_sayisi}
                </div>
            `);
            
            if (Notification.permission === 'granted') {
                bildirimler.forEach(function(bildirim, index) {
                    setTimeout(function() {
                        const notification = new Notification(bildirim.title, {
                            body: bildirim.body,
                            icon: '<?php echo url('mobiluygulamaiconu.png'); ?>',
                            badge: '<?php echo url('mobiluygulamaiconu.png'); ?>',
                            tag: bildirim.tag,
                            requireInteraction: bildirim.urgency === 'high',
                            data: bildirim.data
                        });
                        
                        notification.onclick = function() {
                            window.focus();
                            if (bildirim.data && bildirim.data.url) {
                                window.location.href = bildirim.data.url;
                            }
                            notification.close();
                        };
                        
                        notificationCount++;
                        $('#active-notifications').text(notificationCount);
                        addToNotificationList(bildirim.title, bildirim.body);
                    }, index * 1000); // Her bildirimi 1 saniye arayla gönder
                });
            } else {
                showError('Bildirim göndermek için izin vermelisiniz!');
            }
        } else {
            showError('Bildirimler yüklenirken hata oluştu: ' + response.message);
        }
    });
}

function addToNotificationList(title, body) {
    const time = new Date().toLocaleTimeString('tr-TR');
    const html = `
        <div class="alert alert-light border mb-2">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>${title}</strong><br>
                    <small class="text-muted">${body}</small>
                </div>
                <small class="text-muted">${time}</small>
            </div>
        </div>
    `;
    $('#notification-list').prepend(html);
}
</script>

<style>
.card-header {
    font-weight: 600;
}
</style>
