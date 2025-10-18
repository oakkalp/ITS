<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum - Fidan Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 50px 0; }
        .install-card { background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 800px; margin: 0 auto; }
        .step { display: none; padding: 40px; }
        .step.active { display: block; }
        .step-header { text-align: center; margin-bottom: 30px; }
        .step-number { width: 50px; height: 50px; border-radius: 50%; background: #667eea; color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; }
        .success-icon { font-size: 80px; color: #4caf50; text-align: center; margin: 20px 0; }
        .error-icon { font-size: 80px; color: #f44336; text-align: center; margin: 20px 0; }
        .log-output { background: #f5f5f5; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .log-success { color: #4caf50; }
        .log-error { color: #f44336; }
        .log-info { color: #2196f3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <!-- ADIM 1: Hoşgeldiniz -->
            <div class="step active" id="step1">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h2 class="mt-3">Fidan Takip Sistemi Kurulumuna Hoşgeldiniz</h2>
                    <p class="text-muted">Web + Mobil (Flutter) için hazır sistem</p>
                </div>
                <div class="alert alert-info">
                    <h5>✨ Sistem Özellikleri:</h5>
                    <ul>
                        <li>3 Seviyeli Kullanıcı Sistemi (Super Admin, Firma Yöneticisi, Kullanıcı)</li>
                        <li>Firma Yönetimi</li>
                        <li>Cari Yönetimi (Müşteri + Tedarikçi)</li>
                        <li>Stok Yönetimi</li>
                        <li>Alış/Satış Faturaları (Peşin/Vadeli, Kısmi Ödeme)</li>
                        <li>Kasa Yönetimi (Gelir/Gider)</li>
                        <li>Çek Takibi</li>
                        <li>Personel Maaş Takibi</li>
                        <li>Raporlar ve Analizler</li>
                        <li>RESTful API (Flutter için hazır)</li>
                    </ul>
                </div>
                <div class="alert alert-warning">
                    <strong>⚠️ Önemli:</strong> Kurulumdan önce <code>config.php</code> dosyasındaki veritabanı ayarlarını kontrol edin!
                </div>
                <button class="btn btn-primary btn-lg w-100" onclick="nextStep(2)">Kuruluma Başla →</button>
            </div>

            <!-- ADIM 2: Veritabanı Kurulumu -->
            <div class="step" id="step2">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h2 class="mt-3">Veritabanı Kurulumu</h2>
                </div>
                <div id="dbResult"></div>
                <button class="btn btn-primary btn-lg w-100" onclick="installDatabase()">Veritabanını Kur</button>
            </div>

            <!-- ADIM 3: Tamamlandı -->
            <div class="step" id="step3">
                <div class="step-header">
                    <div class="success-icon">✓</div>
                    <h2>Kurulum Başarıyla Tamamlandı!</h2>
                </div>
                <div class="alert alert-success">
                    <h5>🎉 Sisteminiz hazır!</h5>
                    <p>Artık sistemi kullanmaya başlayabilirsiniz.</p>
                </div>
                <div class="card mt-4">
                    <div class="card-body">
                        <h5>🔑 Super Admin Giriş Bilgileri:</h5>
                        <p><strong>Kullanıcı Adı:</strong> <code>admin</code></p>
                        <p><strong>Şifre:</strong> <code>admin123</code></p>
                        <p class="text-danger mb-0"><small>⚠️ İlk girişte şifrenizi mutlaka değiştirin!</small></p>
                    </div>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <a href="login.php" class="btn btn-success btn-lg">Giriş Sayfasına Git →</a>
                    <button class="btn btn-danger" onclick="if(confirm('Kurulum dosyasını silmek istediğinizden emin misiniz?')) deleteInstaller()">Kurulum Dosyasını Sil (Önerilen)</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function nextStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
        }

        function installDatabase() {
            const resultDiv = document.getElementById('dbResult');
            resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Veritabanı kuruluyor...</p></div>';

            fetch('install_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'install_db' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5 class="log-success">✓ Başarılı!</h5>
                            <div class="log-output">${data.logs.join('<br>')}</div>
                        </div>
                        <button class="btn btn-primary btn-lg w-100 mt-3" onclick="nextStep(3)">Devam Et →</button>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5 class="log-error">✗ Hata!</h5>
                            <p>${data.message}</p>
                            <div class="log-output">${data.logs ? data.logs.join('<br>') : ''}</div>
                        </div>
                        <button class="btn btn-warning w-100 mt-3" onclick="installDatabase()">Tekrar Dene</button>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>Bağlantı Hatası!</h5>
                        <p>${error.message}</p>
                        <p class="mb-0"><small>MySQL servisinin çalıştığından emin olun!</small></p>
                    </div>
                    <button class="btn btn-warning w-100 mt-3" onclick="installDatabase()">Tekrar Dene</button>
                `;
            });
        }

        function deleteInstaller() {
            fetch('install_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_installer' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Kurulum dosyaları silindi!');
                    window.location.href = 'login.php';
                }
            });
        }
    </script>
</body>
</html>

