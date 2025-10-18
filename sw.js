// Service Worker for Muhasebe Demo
const CACHE_NAME = 'muhasebedemo-v1';
const urlsToCache = [
    '/muhasebedemo/',
    '/muhasebedemo/dashboard.php',
    '/muhasebedemo/modules/cariler/list.php',
    '/muhasebedemo/modules/stok/list.php',
    '/muhasebedemo/modules/faturalar/list.php',
    '/muhasebedemo/modules/kasa/list.php',
    '/muhasebedemo/modules/cekler/list.php',
    '/muhasebedemo/mobiluygulamaiconu.png'
];

// Install event
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Cache açıldı');
                return cache.addAll(urlsToCache).catch(function(error) {
                    console.log('Cache ekleme hatası:', error);
                    // Hata olsa bile cache'i açık bırak
                    return cache;
                });
            })
    );
});

// Fetch event
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Cache'de varsa cache'den döndür
                if (response) {
                    return response;
                }
                
                // Cache'de yoksa network'ten al
                return fetch(event.request);
            }
        )
    );
});

// Push notification event
self.addEventListener('push', function(event) {
    console.log('Push notification alındı');
    
    const options = {
        body: 'Yeni bildirim',
        icon: '/muhasebedemo/mobiluygulamaiconu.png',
        badge: '/muhasebedemo/mobiluygulamaiconu.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Detayları Gör',
                icon: '/muhasebedemo/mobiluygulamaiconu.png'
            },
            {
                action: 'close',
                title: 'Kapat',
                icon: '/muhasebedemo/mobiluygulamaiconu.png'
            }
        ]
    };
    
    if (event.data) {
        const data = event.data.json();
        options.body = data.body || 'Yeni bildirim';
        options.title = data.title || 'Muhasebe Demo';
        
        // Çek vade bildirimi
        if (data.type === 'cek_vade' || data.type === 'cek_vade_bugun' || data.type === 'cek_vade_yaklasan') {
            options.body = data.body;
            options.tag = 'cek-notification';
            options.renotify = true;
        }
        
        // Tahsilat bildirimi
        if (data.type === 'tahsilat' || data.type === 'tahsilat_bugun' || data.type === 'tahsilat_yaklasan') {
            options.body = data.body;
            options.tag = 'tahsilat-notification';
            options.renotify = true;
        }
    }
    
    event.waitUntil(
        self.registration.showNotification('Muhasebe Demo', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', function(event) {
    console.log('Notification tıklandı');
    
    event.notification.close();
    
    if (event.action === 'explore') {
        // Bildirime tıklandığında ilgili sayfaya git
        const data = event.notification.data;
        let url = '/muhasebedemo/dashboard.php';
        
        if (data && data.action) {
            switch (data.action) {
                case 'cekler_page':
                    url = '/muhasebedemo/modules/cekler/list.php';
                    break;
                case 'cariler_page':
                    url = '/muhasebedemo/modules/cariler/list.php';
                    break;
                default:
                    url = '/muhasebedemo/dashboard.php';
            }
        }
        
        event.waitUntil(
            clients.openWindow(url)
        );
    } else if (event.action === 'close') {
        // Bildirimi kapat
        event.notification.close();
    } else {
        // Bildirime tıklandığında ana sayfaya git
        event.waitUntil(
            clients.openWindow('/muhasebedemo/dashboard.php')
        );
    }
});

// Background sync
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // FCM token'ı kaydet
    return fetch('/muhasebedemo/api/mobile/save-fcm-token.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fcm_token: 'background-sync-token'
        })
    });
}
