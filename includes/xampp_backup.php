<?php
require_once 'config.php';

/**
 * XAMPP Uyumlu Backup Sistemi
 */
class XAMPPBackupSystem {
    private $backup_dir;
    private $db;
    
    public function __construct($backup_dir = 'backups') {
        $this->backup_dir = __DIR__ . '/' . $backup_dir;
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Backup dizinini oluştur
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    /**
     * Veritabanı backup'ı oluştur (PHP ile)
     */
    public function createDatabaseBackup($filename = null) {
        if ($filename === null) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $filepath = $this->backup_dir . '/' . $filename;
        $sql_content = '';
        
        // SQL başlığı
        $sql_content .= "-- Fidan Takip Sistemi Backup\n";
        $sql_content .= "-- Tarih: " . date('Y-m-d H:i:s') . "\n";
        $sql_content .= "-- Veritabanı: " . DB_NAME . "\n\n";
        $sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_content .= "SET AUTOCOMMIT = 0;\n";
        $sql_content .= "START TRANSACTION;\n";
        $sql_content .= "SET time_zone = \"+00:00\";\n\n";
        
        // Tabloları al
        $tables_query = "SHOW TABLES";
        $tables_result = $this->db->query($tables_query);
        
        while ($table_row = $tables_result->fetch_array()) {
            $table_name = $table_row[0];
            
            // Tablo yapısını al
            $sql_content .= "-- Tablo yapısı: $table_name\n";
            $sql_content .= "DROP TABLE IF EXISTS `$table_name`;\n";
            
            $create_query = "SHOW CREATE TABLE `$table_name`";
            $create_result = $this->db->query($create_query);
            $create_row = $create_result->fetch_array();
            
            $sql_content .= $create_row[1] . ";\n\n";
            
            // Tablo verilerini al
            $sql_content .= "-- Tablo verileri: $table_name\n";
            $data_query = "SELECT * FROM `$table_name`";
            $data_result = $this->db->query($data_query);
            
            if ($data_result->num_rows > 0) {
                $sql_content .= "INSERT INTO `$table_name` VALUES\n";
                
                $values = [];
                while ($data_row = $data_result->fetch_assoc()) {
                    $escaped_values = array_map(function($value) {
                        return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }, array_values($data_row));
                    
                    $values[] = "(" . implode(',', $escaped_values) . ")";
                }
                
                $sql_content .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $sql_content .= "COMMIT;\n";
        
        // Dosyaya yaz
        if (file_put_contents($filepath, $sql_content)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Backup dosyası yazılamadı'
            ];
        }
    }
    
    /**
     * Basit dosya backup'ı (ZIP olmadan)
     */
    public function createFileBackup($source_dir = '.', $exclude_dirs = ['backups', 'cache', 'logs']) {
        $backup_name = 'files_backup_' . date('Y-m-d_H-i-s') . '.txt';
        $backup_path = $this->backup_dir . '/' . $backup_name;
        
        $backup_content = "-- Dosya Backup Listesi\n";
        $backup_content .= "-- Tarih: " . date('Y-m-d H:i:s') . "\n\n";
        
        $files = $this->getFileList($source_dir, $exclude_dirs);
        
        foreach ($files as $file) {
            $backup_content .= "FILE: " . $file . "\n";
            if (file_exists($file)) {
                $backup_content .= "SIZE: " . filesize($file) . " bytes\n";
                $backup_content .= "DATE: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
            }
            $backup_content .= "\n";
        }
        
        if (file_put_contents($backup_path, $backup_content)) {
            return [
                'success' => true,
                'filename' => $backup_name,
                'filepath' => $backup_path,
                'size' => filesize($backup_path),
                'file_count' => count($files)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Dosya backup\'ı oluşturulamadı'
            ];
        }
    }
    
    /**
     * Dosya listesi al
     */
    private function getFileList($dir, $exclude_dirs, $base_path = '') {
        $files = [];
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $item_path = $dir . '/' . $item;
            $relative_path = $base_path . $item;
            
            // Exclude dizinleri kontrol et
            $should_exclude = false;
            foreach ($exclude_dirs as $exclude) {
                if (strpos($item_path, $exclude) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) {
                continue;
            }
            
            if (is_dir($item_path)) {
                $files = array_merge($files, $this->getFileList($item_path, $exclude_dirs, $relative_path . '/'));
            } else {
                $files[] = $relative_path;
            }
        }
        
        return $files;
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
        
        $sql_content = file_get_contents($filepath);
        
        if ($sql_content === false) {
            return [
                'success' => false,
                'error' => 'Backup dosyası okunamadı'
            ];
        }
        
        // SQL komutlarını çalıştır
        $queries = explode(';', $sql_content);
        $success_count = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && !preg_match('/^--/', $query)) {
                if ($this->db->query($query)) {
                    $success_count++;
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "Backup başarıyla geri yüklendi ($success_count komut çalıştırıldı)"
        ];
    }
}

// Backup test
echo "=== XAMPP Backup Sistemi Test ===\n";

$backup = new XAMPPBackupSystem();

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
    echo "📁 Dosya sayısı: " . $file_result['file_count'] . "\n";
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

echo "\n🎉 XAMPP Backup sistemi hazır!\n";
echo "\n📋 Otomatik backup için cron job kurun:\n";
echo "0 2 * * * php /path/to/fidan/backup_auto.php\n";
?>
