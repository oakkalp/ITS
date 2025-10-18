<?php
// Config dosyasını include et
if (!defined('CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

// Auth dosyasını include et
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'İşletme Takip Sistemi'; ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="İşletme Takip">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="İşletme Takip">
    <meta name="description" content="Muhasebe ve stok takip sistemi">
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-config" content="/muhasebedemo/browserconfig.xml">
    <meta name="msapplication-TileColor" content="#2196f3">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="theme-color" content="#2196f3">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    <link rel="apple-touch-icon" sizes="167x167" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    
    <!-- Manifest -->
    <link rel="manifest" href="<?php echo url('manifest.json'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    <link rel="shortcut icon" href="<?php echo url('mobiluygulamaiconu.png'); ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            margin: 10px 0 5px 0;
            font-size: 20px;
            font-weight: 700;
        }
        
        .sidebar-header small {
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 25px;
        }
        
        .menu-item.active {
            background: rgba(255,255,255,0.2);
            border-left: 4px solid white;
        }
        
        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 25px;
        }
        
        .user-info {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        
        .user-info .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-info .user-role {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h5 {
            margin: 0;
            color: var(--primary-color);
        }
        
        /* Cards */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }
        
        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        /* Tables */
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        /* Modal */
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <?php
            $firma_logo = null;
            if (isset($_SESSION['firma_id'])) {
                $logo_result = $db->query("SELECT logo FROM firmalar WHERE id = " . $_SESSION['firma_id']);
                if ($logo_result && $logo_result->num_rows > 0) {
                    $firma_logo = $logo_result->fetch_assoc()['logo'];
                }
            }
            ?>
            
            <?php if ($firma_logo): ?>
                <img src="<?php echo url('uploads/logos/' . $firma_logo); ?>" alt="Logo" style="max-height: 48px; max-width: 120px; object-fit: contain;">
            <?php else: ?>
                <i class="bi bi-flower1" style="font-size: 48px;"></i>
            <?php endif; ?>
            
            <h4><?php echo get_user()['ad_soyad'] ?? 'İşletme Takip'; ?></h4>
            <small><?php echo is_super_admin() ? 'Sistem Yönetimi' : 'Firma Yönetimi'; ?></small>
        </div>
        
        <div class="sidebar-menu">
            <?php if (is_super_admin()): ?>
                <a href="<?php echo url('dashboard.php'); ?>" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Ana Panel
                </a>
                <a href="<?php echo url('admin/firmalar.php'); ?>" class="menu-item">
                    <i class="bi bi-building"></i> Firmalar
                </a>
                <a href="<?php echo url('admin/kullanicilar.php'); ?>" class="menu-item">
                    <i class="bi bi-people"></i> Tüm Kullanıcılar
                </a>
                
                <!-- Kullanıcı Bilgileri ve Çıkış -->
                <div class="user-info">
                    <div class="user-name">
                        <i class="bi bi-person-circle me-2"></i>
                        <?php echo get_user()['ad_soyad']; ?>
                    </div>
                    <div class="user-role">
                        <?php 
                        $rol = get_user_role();
                        echo $rol == 'super_admin' ? 'Super Admin' : ($rol == 'firma_yoneticisi' ? 'Firma Yöneticisi' : 'Kullanıcı');
                        ?>
                    </div>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-sm btn-light mt-2 w-100">
                        <i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap
                    </a>
                </div>
            <?php else: ?>
                <a href="<?php echo url('dashboard.php'); ?>" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Ana Panel
                </a>
                
                <?php if (has_permission('cariler', 'okuma')): ?>
                <a href="<?php echo url('modules/cariler/list.php'); ?>" class="menu-item">
                    <i class="bi bi-person-lines-fill"></i> Cariler
                </a>
                <a href="<?php echo url('modules/cariler/ekstre.php'); ?>" class="menu-item">
                    <i class="bi bi-file-text"></i> Cari Ekstreleri
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('urunler', 'okuma')): ?>
                <a href="<?php echo url('modules/stok/list.php'); ?>" class="menu-item">
                    <i class="bi bi-box-seam"></i> Stok Yönetimi
                </a>
                <a href="<?php echo url('modules/stok/hareket-raporu.php'); ?>" class="menu-item">
                    <i class="bi bi-graph-up"></i> Stok Hareket Raporu
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('faturalar', 'okuma')): ?>
                <a href="<?php echo url('modules/faturalar/list.php'); ?>" class="menu-item">
                    <i class="bi bi-receipt"></i> Faturalar
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('teklifler', 'okuma')): ?>
                <a href="<?php echo url('modules/teklifler/list.php'); ?>" class="menu-item">
                    <i class="bi bi-file-earmark-text"></i> Teklifler
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('odemeler', 'okuma')): ?>
                <a href="<?php echo url('modules/odemeler/list.php'); ?>" class="menu-item">
                    <i class="bi bi-credit-card"></i> Ödemeler
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('kasa', 'okuma')): ?>
                <a href="<?php echo url('modules/kasa/list.php'); ?>" class="menu-item">
                    <i class="bi bi-cash-stack"></i> Kasa
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('cekler', 'okuma')): ?>
                <a href="<?php echo url('modules/cekler/list.php'); ?>" class="menu-item">
                    <i class="bi bi-file-earmark-check"></i> Çek Yönetimi
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('personel', 'okuma')): ?>
                <a href="<?php echo url('modules/personel/list.php'); ?>" class="menu-item">
                    <i class="bi bi-person-badge"></i> Personel
                </a>
                <?php endif; ?>
                
                <?php if (has_permission('raporlar', 'okuma')): ?>
                <a href="<?php echo url('modules/raporlar/index.php'); ?>" class="menu-item">
                    <i class="bi bi-graph-up"></i> Raporlar
                </a>
                <a href="<?php echo url('modules/raporlar/kar-zarar.php'); ?>" class="menu-item">
                    <i class="bi bi-graph-up-arrow"></i> Kar-Zarar Raporu
                </a>
                <a href="<?php echo url('bildirim-test.php'); ?>" class="menu-item">
                    <i class="bi bi-bell"></i> Bildirim Test
                </a>
                <?php endif; ?>
                
                <?php if (is_firma_yoneticisi()): ?>
                <a href="<?php echo url('firma/kullanicilar.php'); ?>" class="menu-item">
                    <i class="bi bi-person-gear"></i> Kullanıcı Yönetimi
                </a>
                <a href="<?php echo url('firma/ayarlar.php'); ?>" class="menu-item">
                    <i class="bi bi-gear"></i> Firma Ayarları
                </a>
                <?php endif; ?>
                
                <!-- Kullanıcı Bilgileri ve Çıkış - Tüm kullanıcılar için -->
                <div class="user-info">
                    <div class="user-name">
                        <i class="bi bi-person-circle me-2"></i>
                        <?php echo get_user()['ad_soyad']; ?>
                    </div>
                    <div class="user-role">
                        <?php 
                        $rol = get_user_role();
                        echo $rol == 'super_admin' ? 'Super Admin' : ($rol == 'firma_yoneticisi' ? 'Firma Yöneticisi' : 'Firma Kullanıcısı');
                        ?>
                    </div>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-sm btn-light mt-2 w-100">
                        <i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">

