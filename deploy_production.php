<?php
/**
 * Production Deployment Script
 * IP adresi tabanlı deployment
 */

echo "=== Production Deployment Script ===\n";
echo "Hedef: 192.168.1.137/fidan\n";
echo "Tarih: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Dosya listesi oluştur
echo "1. 📁 Dosya listesi oluşturuluyor...\n";

$files_to_deploy = [
    // Core files
    'config_production.php' => 'config.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/auth.php',
    'includes/cache.php',
    'includes/security.php',
    'includes/xampp_backup.php',
    'includes/firebase_notification.php',
    
    // Main pages
    'dashboard.php',
    'login.php',
    'logout.php',
    
    // Modules
    'modules/cariler/list.php',
    'modules/cariler/create.php',
    'modules/cariler/edit.php',
    'modules/cariler/view.php',
    'modules/faturalar/list.php',
    'modules/faturalar/create.php',
    'modules/faturalar/view.php',
    'modules/stok/list.php',
    'modules/stok/create.php',
    'modules/stok/edit.php',
    'modules/cekler/list.php',
    'modules/cekler/create.php',
    'modules/cekler/edit.php',
    'modules/kasa/list.php',
    'modules/kasa/create.php',
    'modules/personel/list.php',
    'modules/personel/create.php',
    'modules/personel/edit.php',
    'modules/raporlar/index.php',
    'modules/raporlar/kar-zarar.php',
    
    // API files
    'api/cariler/create.php',
    'api/cariler/update.php',
    'api/cariler/delete.php',
    'api/faturalar/create.php',
    'api/faturalar/update.php',
    'api/faturalar/delete.php',
    'api/stok/create.php',
    'api/stok/update.php',
    'api/stok/delete.php',
    'api/cekler/create.php',
    'api/cekler/update.php',
    'api/cekler/delete.php',
    'api/kasa/create.php',
    'api/personel/create.php',
    'api/personel/update.php',
    'api/personel/delete.php',
    
    // Flutter API
    'api/flutter/flutter_api.php',
    'api/flutter/auth.php',
    'api/flutter/dashboard.php',
    
    // Mobile API
    'api/mobile/save-fcm-token.php',
    'api/mobile/cek-vade-takip.php',
    'api/mobile/tahsilat-takip.php',
    'api/mobile/test-notification.php',
    'api/mobile/browser-notification.php',
    
    // Assets
    'mobiluygulamaiconu.png',
    'manifest.json',
    'sw.js',
    
    // Scripts
    'backup_auto.php',
    'cron_notifications.php',
    'system_status.php',
    'bildirim-test.php',
    'mobile-test.php'
];

echo "✅ " . count($files_to_deploy) . " dosya listelendi\n\n";

// 2. Deployment paketi oluştur
echo "2. 📦 Deployment paketi oluşturuluyor...\n";

$deployment_dir = 'deployment_' . date('Y-m-d_H-i-s');
if (!is_dir($deployment_dir)) {
    mkdir($deployment_dir, 0755, true);
}

$deployed_files = 0;
$errors = [];

foreach ($files_to_deploy as $source => $target) {
    if (is_string($target)) {
        // Key-value pair (source => target)
        $source_file = $source;
        $target_file = $target;
    } else {
        // Single file
        $source_file = $target;
        $target_file = $target;
    }
    
    if (file_exists($source_file)) {
        $target_path = $deployment_dir . '/' . $target_file;
        $target_dir = dirname($target_path);
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        if (copy($source_file, $target_path)) {
            $deployed_files++;
            echo "✅ $source_file -> $target_file\n";
        } else {
            $errors[] = "❌ $source_file kopyalanamadı";
        }
    } else {
        $errors[] = "⚠️ $source_file bulunamadı";
    }
}

echo "\n📊 Deployment Özeti:\n";
echo "✅ Başarılı: $deployed_files dosya\n";
echo "❌ Hata: " . count($errors) . " dosya\n";

if (!empty($errors)) {
    echo "\nHatalar:\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

// 3. Database migration script oluştur
echo "\n3. 🗄️ Database migration script oluşturuluyor...\n";

$migration_sql = "-- Production Database Migration Script\n";
$migration_sql .= "-- Tarih: " . date('Y-m-d H:i:s') . "\n";
$migration_sql .= "-- Hedef: muhasebedemo veritabanı\n\n";

// Tablo yapıları
$tables = [
    'firmalar' => "CREATE TABLE IF NOT EXISTS firmalar (
        id int(11) NOT NULL AUTO_INCREMENT,
        firma_adi varchar(255) NOT NULL,
        logo varchar(255) DEFAULT NULL,
        aktif tinyint(1) DEFAULT 1,
        olusturma_tarihi timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'kullanicilar' => "CREATE TABLE IF NOT EXISTS kullanicilar (
        id int(11) NOT NULL AUTO_INCREMENT,
        firma_id int(11) NOT NULL,
        ad_soyad varchar(150) NOT NULL,
        kullanici_adi varchar(50) NOT NULL,
        sifre varchar(255) NOT NULL,
        rol enum('super_admin','firma_yoneticisi','firma_kullanici') NOT NULL,
        aktif tinyint(1) DEFAULT 1,
        fcm_token text DEFAULT NULL,
        son_giris timestamp NULL DEFAULT NULL,
        olusturma_tarihi timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY kullanici_adi (kullanici_adi),
        KEY firma_id (firma_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'cariler' => "CREATE TABLE IF NOT EXISTS cariler (
        id int(11) NOT NULL AUTO_INCREMENT,
        firma_id int(11) NOT NULL,
        unvan varchar(255) NOT NULL,
        vergi_no varchar(20) DEFAULT NULL,
        telefon varchar(20) DEFAULT NULL,
        email varchar(100) DEFAULT NULL,
        adres text DEFAULT NULL,
        bakiye decimal(15,2) DEFAULT 0.00,
        aktif tinyint(1) DEFAULT 1,
        olusturma_tarihi timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY firma_id (firma_id),
        KEY unvan (unvan),
        KEY vergi_no (vergi_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $table_name => $create_sql) {
    $migration_sql .= $create_sql . ";\n\n";
}

// Index'ler
$migration_sql .= "-- Index'ler\n";
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_faturalar_firma_tarih ON faturalar(firma_id, fatura_tarihi)",
    "CREATE INDEX IF NOT EXISTS idx_cariler_firma_id ON cariler(firma_id)",
    "CREATE INDEX IF NOT EXISTS idx_urunler_firma_id ON urunler(firma_id)",
    "CREATE INDEX IF NOT EXISTS idx_cekler_firma_id ON cekler(firma_id)"
];

foreach ($indexes as $index) {
    $migration_sql .= $index . ";\n";
}

// Admin kullanıcısı
$migration_sql .= "\n-- Admin kullanıcısı\n";
$migration_sql .= "INSERT IGNORE INTO kullanicilar (firma_id, ad_soyad, kullanici_adi, sifre, rol) VALUES (1, 'Sistem Yöneticisi', 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'super_admin');\n";

$migration_file = $deployment_dir . '/database_migration.sql';
file_put_contents($migration_file, $migration_sql);

echo "✅ Database migration script oluşturuldu: $migration_file\n";

// 4. Deployment talimatları
echo "\n4. 📋 Deployment talimatları oluşturuluyor...\n";

$instructions = "# Production Deployment Talimatları\n\n";
$instructions .= "## Hedef Sunucu\n";
$instructions .= "- IP Adresi: 192.168.1.137\n";
$instructions .= "- Klasör: /fidan\n";
$instructions .= "- Veritabanı: fidan_takip\n";
$instructions .= "- Kullanıcı: root\n";
$instructions .= "- Şifre: (boş)\n\n";

$instructions .= "## Deployment Adımları\n\n";
$instructions .= "1. **Dosyaları Yükle**\n";
$instructions .= "   ```bash\n";
$instructions .= "   # Tüm dosyaları /fidan klasörüne yükle\n";
$instructions .= "   scp -r $deployment_dir/* user@192.168.1.137:/path/to/fidan/\n";
$instructions .= "   ```\n\n";

$instructions .= "2. **Veritabanı Kurulumu**\n";
$instructions .= "   ```bash\n";
$instructions .= "   # Migration script'i çalıştır\n";
$instructions .= "   mysql -u admin -p'zd3up16Hzmpy!' muhasebedemo < database_migration.sql\n";
$instructions .= "   ```\n\n";

$instructions .= "3. **Dosya İzinleri**\n";
$instructions .= "   ```bash\n";
$instructions .= "   chmod 755 /onmuhasebedemo\n";
$instructions .= "   chmod 644 /onmuhasebedemo/*.php\n";
$instructions .= "   chmod 755 /onmuhasebedemo/uploads\n";
$instructions .= "   chmod 755 /onmuhasebedemo/backups\n";
$instructions .= "   chmod 755 /onmuhasebedemo/cache\n";
$instructions .= "   chmod 755 /onmuhasebedemo/logs\n";
$instructions .= "   ```\n\n";

$instructions .= "4. **Cron Job Kurulumu**\n";
$instructions .= "   ```bash\n";
$instructions .= "   # Backup için\n";
$instructions .= "   0 2 * * * php /path/to/onmuhasebedemo/backup_auto.php\n";
$instructions .= "   \n";
$instructions .= "   # Bildirimler için\n";
$instructions .= "   0 9 * * * php /path/to/onmuhasebedemo/cron_notifications.php\n";
$instructions .= "   ```\n\n";

$instructions .= "5. **SSL Sertifikası**\n";
$instructions .= "   - Let's Encrypt ile SSL kurulumu\n";
$instructions .= "   - HTTPS yönlendirmesi aktifleştir\n\n";

$instructions .= "6. **Test**\n";
$instructions .= "   - http://192.168.1.137/fidan/login.php\n";
$instructions .= "   - Admin girişi: admin / admin123\n";
$instructions .= "   - API test: http://192.168.1.137/fidan/api/flutter/auth.php\n\n";

$instructions .= "## Flutter Uygulama\n\n";
$instructions .= "### API Endpoints\n";
$instructions .= "- Base URL: http://192.168.1.137/fidan/api/flutter/\n";
$instructions .= "- Auth: /auth/login, /auth/logout, /auth/profile\n";
$instructions .= "- Dashboard: /dashboard/stats, /dashboard/charts, /dashboard/notifications\n\n";

$instructions .= "### Android Ayarları\n";
$instructions .= "- Package: com.fidan.takip\n";
$instructions .= "- Min SDK: 21\n";
$instructions .= "- Target SDK: 34\n";
$instructions .= "- Icon: mobiluygulamaiconu.png\n\n";

$instructions .= "### Firebase Ayarları\n";
$instructions .= "- Project ID: onmuhasebeceksenet\n";
$instructions .= "- Server Key: BIQVvTApg0EdvHFrH7OYs5ndE2lyD_Gvhx6NwPo13tkj2h_Wccf6Z7ttmi_EnESKw5_Ct4UooMBZmOcnyoQ55gk\n";
$instructions .= "- Service Account: onmuhasebeceksenet-10bff7999d8d.json\n\n";

$instructions_file = $deployment_dir . '/DEPLOYMENT_INSTRUCTIONS.md';
file_put_contents($instructions_file, $instructions);

echo "✅ Deployment talimatları oluşturuldu: $instructions_file\n";

// 5. Flutter proje yapısı
echo "\n5. 📱 Flutter proje yapısı oluşturuluyor...\n";

$flutter_structure = [
    'lib/main.dart',
    'lib/screens/login_screen.dart',
    'lib/screens/dashboard_screen.dart',
    'lib/screens/cariler_screen.dart',
    'lib/screens/faturalar_screen.dart',
    'lib/screens/stok_screen.dart',
    'lib/screens/cekler_screen.dart',
    'lib/services/api_service.dart',
    'lib/services/auth_service.dart',
    'lib/services/notification_service.dart',
    'lib/models/user_model.dart',
    'lib/models/cari_model.dart',
    'lib/models/fatura_model.dart',
    'lib/widgets/custom_app_bar.dart',
    'lib/widgets/loading_widget.dart',
    'android/app/src/main/AndroidManifest.xml',
    'android/app/build.gradle',
    'pubspec.yaml'
];

$flutter_dir = $deployment_dir . '/flutter_project';
if (!is_dir($flutter_dir)) {
    mkdir($flutter_dir, 0755, true);
}

foreach ($flutter_structure as $file) {
    $file_path = $flutter_dir . '/' . $file;
    $file_dir = dirname($file_path);
    
    if (!is_dir($file_dir)) {
        mkdir($file_dir, 0755, true);
    }
    
    // Boş dosya oluştur
    file_put_contents($file_path, "// $file - Flutter dosyası\n");
}

echo "✅ Flutter proje yapısı oluşturuldu: $flutter_dir\n";

// 6. Özet
echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 DEPLOYMENT HAZIR!\n";
echo str_repeat("=", 60) . "\n";
echo "📁 Deployment klasörü: $deployment_dir\n";
echo "📊 Toplam dosya: $deployed_files\n";
echo "🗄️ Database script: database_migration.sql\n";
echo "📋 Talimatlar: DEPLOYMENT_INSTRUCTIONS.md\n";
echo "📱 Flutter proje: flutter_project/\n";
echo "\n🚀 Sonraki adımlar:\n";
echo "1. Dosyaları sunucuya yükle\n";
echo "2. Veritabanını kur\n";
echo "3. SSL sertifikası kur\n";
echo "4. Flutter uygulamasını geliştir\n";
echo "5. Test et ve yayınla\n";
echo str_repeat("=", 60) . "\n";
?>
