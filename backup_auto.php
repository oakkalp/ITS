<?php
require_once 'config.php';
require_once 'includes/xampp_backup.php';

/**
 * Otomatik Backup Script
 * Cron job ile çalıştırılacak
 */

try {
    echo "=== Otomatik Backup Başlatılıyor ===\n";
    echo "Tarih: " . date('Y-m-d H:i:s') . "\n\n";
    
    $backup = new XAMPPBackupSystem();
    
    // Veritabanı backup'ı
    echo "1. Veritabanı backup'ı oluşturuluyor...\n";
    $db_result = $backup->createDatabaseBackup();
    
    if ($db_result['success']) {
        echo "✅ Veritabanı backup'ı: " . $db_result['filename'] . " (" . number_format($db_result['size'] / 1024, 2) . " KB)\n";
    } else {
        echo "❌ Veritabanı backup'ı başarısız: " . $db_result['error'] . "\n";
    }
    
    // Dosya backup'ı (haftalık)
    if (date('N') == 1) { // Pazartesi
        echo "\n2. Haftalık dosya backup'ı oluşturuluyor...\n";
        $file_result = $backup->createFileBackup();
        
        if ($file_result['success']) {
            echo "✅ Dosya backup'ı: " . $file_result['filename'] . " (" . number_format($file_result['size'] / 1024, 2) . " KB)\n";
        } else {
            echo "❌ Dosya backup'ı başarısız: " . $file_result['error'] . "\n";
        }
    } else {
        echo "\n2. Dosya backup'ı atlandı (sadece Pazartesi)\n";
    }
    
    // Eski backup'ları temizle (30 günden eski)
    echo "\n3. Eski backup'lar temizleniyor...\n";
    $deleted = $backup->cleanOldBackups(30);
    echo "🗑️ Silinen eski backup sayısı: " . $deleted . "\n";
    
    // Backup istatistikleri
    echo "\n4. Backup istatistikleri:\n";
    $backups = $backup->getBackupList();
    $total_size = 0;
    
    foreach ($backups as $backup_item) {
        $total_size += $backup_item['size'];
    }
    
    echo "📊 Toplam backup sayısı: " . count($backups) . "\n";
    echo "📊 Toplam boyut: " . number_format($total_size / 1024, 2) . " KB\n";
    
    // Log dosyasına kaydet
    $log_message = date('Y-m-d H:i:s') . " - Backup tamamlandı. DB: " . ($db_result['success'] ? 'OK' : 'FAIL') . ", Files: " . (isset($file_result) && $file_result['success'] ? 'OK' : 'SKIP') . ", Deleted: $deleted\n";
    file_put_contents('backups/backup.log', $log_message, FILE_APPEND | LOCK_EX);
    
    echo "\n🎉 Otomatik backup tamamlandı!\n";
    
} catch (Exception $e) {
    echo "❌ Backup hatası: " . $e->getMessage() . "\n";
    
    // Hata logu
    $error_log = date('Y-m-d H:i:s') . " - Backup hatası: " . $e->getMessage() . "\n";
    file_put_contents('backups/backup.log', $error_log, FILE_APPEND | LOCK_EX);
}
?>
