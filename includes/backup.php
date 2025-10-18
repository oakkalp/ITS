<?php
require_once 'config.php';

/**
 * Otomatik Backup Sistemi
 */
class BackupSystem {
    private $backup_dir;
    private $db_config;
    
    public function __construct($backup_dir = 'backups') {
        $this->backup_dir = __DIR__ . '/' . $backup_dir;
        $this->db_config = [
            'host' => DB_HOST,
            'username' => DB_USER,
            'password' => DB_PASS,
            'database' => DB_NAME
        ];
        
        // Backup dizinini oluştur
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Veritabanı backup'ı oluştur
     */
    public function createDatabaseBackup($filename = null) {
        if ($filename === null) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $filepath = $this->backup_dir . '/' . $filename;
        
        // mysqldump komutu
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($this->db_config['host']),
            escapeshellarg($this->db_config['username']),
            escapeshellarg($this->db_config['password']),
            escapeshellarg($this->db_config['database']),
            escapeshellarg($filepath)
        );
        
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Backup oluşturulamadı',
                'output' => $output
            ];
        }
    }
    
    /**
     * Dosya backup'ı oluştur
     */
    public function createFileBackup($source_dir = '.', $exclude_dirs = ['backups', 'cache', 'logs']) {
        $backup_name = 'files_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $backup_path = $this->backup_dir . '/' . $backup_name;
        
        $zip = new ZipArchive();
        
        if ($zip->open($backup_path, ZipArchive::CREATE) !== TRUE) {
            return [
                'success' => false,
                'error' => 'ZIP dosyası oluşturulamadı'
            ];
        }
        
        $this->addDirectoryToZip($zip, $source_dir, $exclude_dirs);
        $zip->close();
        
        return [
            'success' => true,
            'filename' => $backup_name,
            'filepath' => $backup_path,
            'size' => filesize($backup_path)
        ];
    }
    
    /**
     * ZIP'e dizin ekle
     */
    private function addDirectoryToZip($zip, $dir, $exclude_dirs, $base_path = '') {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $dir . '/' . $file;
            $zip_path = $base_path . $file;
            
            // Exclude dizinleri kontrol et
            $should_exclude = false;
            foreach ($exclude_dirs as $exclude) {
                if (strpos($file_path, $exclude) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) {
                continue;
            }
            
            if (is_dir($file_path)) {
                $zip->addEmptyDir($zip_path . '/');
                $this->addDirectoryToZip($zip, $file_path, $exclude_dirs, $zip_path . '/');
            } else {
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
    
    /**
     * Eski backup'ları temizle
     */
    public function cleanOldBackups($days_to_keep = 30) {
        $files = glob($this->backup_dir . '/*');
        $deleted_count = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_time = filemtime($file);
                $days_old = (time() - $file_time) / (24 * 60 * 60);
                
                if ($days_old > $days_to_keep) {
                    if (unlink($file)) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Backup listesi
     */
    public function getBackupList() {
        $files = glob($this->backup_dir . '/*');
        $backups = [];
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'created' => filemtime($file),
                    'type' => pathinfo($file, PATHINFO_EXTENSION)
                ];
            }
        }
        
        // Tarihe göre sırala (en yeni önce)
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Backup'ı geri yükle
     */
    public function restoreDatabaseBackup($filename) {
        $filepath = $this->backup_dir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'error' => 'Backup dosyası bulunamadı'
            ];
        }
        
        // MySQL komutu
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($this->db_config['host']),
            escapeshellarg($this->db_config['username']),
            escapeshellarg($this->db_config['password']),
            escapeshellarg($this->db_config['database']),
            escapeshellarg($filepath)
        );
        
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        if ($return_code === 0) {
            return [
                'success' => true,
                'message' => 'Backup başarıyla geri yüklendi'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Backup geri yüklenemedi',
                'output' => $output
            ];
        }
    }
}

// Backup test
echo "=== Backup Sistemi Test ===\n";

$backup = new BackupSystem();

// Veritabanı backup'ı oluştur
echo "1. Veritabanı backup'ı oluşturuluyor...\n";
$db_result = $backup->createDatabaseBackup();

if ($db_result['success']) {
    echo "✅ Veritabanı backup'ı oluşturuldu: " . $db_result['filename'] . "\n";
    echo "📊 Boyut: " . number_format($db_result['size'] / 1024, 2) . " KB\n";
} else {
    echo "❌ Veritabanı backup'ı oluşturulamadı: " . $db_result['error'] . "\n";
}

// Dosya backup'ı oluştur
echo "\n2. Dosya backup'ı oluşturuluyor...\n";
$file_result = $backup->createFileBackup();

if ($file_result['success']) {
    echo "✅ Dosya backup'ı oluşturuldu: " . $file_result['filename'] . "\n";
    echo "📊 Boyut: " . number_format($file_result['size'] / 1024, 2) . " KB\n";
} else {
    echo "❌ Dosya backup'ı oluşturulamadı: " . $file_result['error'] . "\n";
}

// Backup listesi
echo "\n3. Backup listesi:\n";
$backups = $backup->getBackupList();
foreach ($backups as $backup_item) {
    $date = date('Y-m-d H:i:s', $backup_item['created']);
    $size = number_format($backup_item['size'] / 1024, 2);
    echo "📁 " . $backup_item['filename'] . " - " . $size . " KB - " . $date . "\n";
}

// Eski backup'ları temizle
echo "\n4. Eski backup'lar temizleniyor...\n";
$deleted = $backup->cleanOldBackups(0); // Test için 0 gün
echo "🗑️ Silinen dosya sayısı: " . $deleted . "\n";

echo "\n🎉 Backup sistemi hazır!\n";
echo "\n📋 Otomatik backup için cron job kurun:\n";
echo "0 2 * * * php /path/to/fidan/backup_auto.php\n";
?>
