    </div> <!-- End Main Content -->
    
    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // DataTables Türkçe
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                "decimal": "",
                "emptyTable": "Tabloda hiç veri yok",
                "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
                "infoEmpty": "Kayıt yok",
                "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
                "infoPostFix": "",
                "thousands": ".",
                "lengthMenu": "_MENU_ kayıt göster",
                "loadingRecords": "Yükleniyor...",
                "processing": "İşleniyor...",
                "search": "Ara:",
                "zeroRecords": "Eşleşen kayıt bulunamadı",
                "paginate": {
                    "first": "İlk",
                    "last": "Son",
                    "next": "Sonraki",
                    "previous": "Önceki"
                },
                "aria": {
                    "sortAscending": ": artan sütun sıralaması",
                    "sortDescending": ": azalan sütun sıralaması"
                }
            }
        });
        
        // Global AJAX error handler
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            if (jqxhr.status === 401) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oturum Sonlandı',
                    text: 'Lütfen tekrar giriş yapın',
                    confirmButtonText: 'Giriş Sayfasına Git'
                }).then(() => {
                    window.location.href = '<?php echo url('login.php'); ?>';
                });
            }
        });
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo url('sw.js'); ?>')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                        
                        // FCM token'ı kaydet
                        if ('Notification' in window) {
                            Notification.requestPermission().then(function(permission) {
                                if (permission === 'granted') {
                                    // Firebase Cloud Messaging token'ı al ve kaydet
                                    if ('serviceWorker' in navigator && 'PushManager' in window && registration && registration.pushManager) {
                                        registration.pushManager.subscribe({
                                            userVisibleOnly: true,
                                            applicationServerKey: 'BIQVvTApg0EdvHFrH7OYs5ndE2lyD_Gvhx6NwPo13tkj2h_Wccf6Z7ttmi_EnESKw5_Ct4UooMBZmOcnyoQ55gk'
                                        }).then(function(subscription) {
                                            // FCM token'ı sunucuya gönder
                                            $.post('<?php echo url('api/mobile/save-fcm-token.php'); ?>', {
                                                fcm_token: JSON.stringify(subscription)
                                            });
                                        }).catch(function(error) {
                                            console.log('Push subscription failed:', error);
                                        });
                                    }
                                }
                            });
                        }
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
        
        // Push notification click handler
        self.addEventListener('notificationclick', function(event) {
            event.notification.close();
            
            if (event.action === 'explore') {
                // Bildirime tıklandığında ilgili sayfaya git
                const data = event.notification.data;
                let url = '<?php echo url('dashboard.php'); ?>';
                
                if (data && data.action) {
                    switch (data.action) {
                        case 'cekler_page':
                            url = '<?php echo url('modules/cekler/list.php'); ?>';
                            break;
                        case 'cariler_page':
                            url = '<?php echo url('modules/cariler/list.php'); ?>';
                            break;
                    }
                }
                
                event.waitUntil(
                    clients.openWindow(url)
                );
            }
        });
        
        // Success alert
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        // Error alert
        function showError(message) {
            console.error('Hata mesajı:', message); // Debug için
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: message || 'Bilinmeyen bir hata oluştu. Lütfen konsol loglarına bakın.',
                footer: '<small>Detaylı bilgi için F12 > Console</small>'
            });
        }
        
        // Confirm dialog
        function confirmDelete(callback) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: "Bu işlem geri alınamaz!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Evet, Sil!',
                cancelButtonText: 'İptal'
            }).then((result) => {
                if (result.isConfirmed) {
                    callback();
                }
            });
        }
        
        // Format para
        function formatMoney(amount) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(amount);
        }
        
        // Format tarih
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('tr-TR');
        }
    </script>
</body>
</html>

