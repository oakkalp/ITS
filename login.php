<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - İşletme Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        .login-header p {
            margin: 0;
            opacity: 0.9;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control {
            height: 50px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 10px 20px 10px 45px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
            z-index: 10;
        }
        .btn-login {
            height: 50px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .default-creds {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }
        .default-creds strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-building" style="font-size: 48px;"></i>
            <h1>İşletme Takibi</h1>
            <p>İşletme Yönetim Sistemi</p>
        </div>
        <div class="login-body">
            <div id="alertBox"></div>
            
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <div class="input-group">
                        <i class="bi bi-person-fill input-icon"></i>
                        <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required autocomplete="username">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Şifre</label>
                    <div class="input-group">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" class="form-control" id="sifre" name="sifre" required autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Giriş Yap
                </button>
            </form>
            
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const btn = $('#loginBtn');
                const originalText = btn.html();
                
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Giriş yapılıyor...');
                $('#alertBox').empty();
                
                $.ajax({
                    url: 'api/auth/login.php',
                    method: 'POST',
                    data: JSON.stringify({
                        kullanici_adi: $('#kullanici_adi').val(),
                        sifre: $('#sifre').val()
                    }),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#alertBox').html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + response.message + '</div>');
                            setTimeout(function() {
                                window.location.href = 'dashboard.php';
                            }, 500);
                        } else {
                            $('#alertBox').html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>' + response.message + '</div>');
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        let message = 'Bir hata oluştu!';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        $('#alertBox').html('<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>' + message + '</div>');
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
</body>
</html>

