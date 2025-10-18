<?php
/**
 * Kurulum İşlem Scripti - Basitleştirilmiş
 */

// Hata gösterimini aç
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON için kapalı
ini_set('log_errors', 1);

// JSON header
header('Content-Type: application/json; charset=utf-8');

// Output buffering
ob_start();

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']));
}

// Input al
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

if ($action === 'install_db') {
    installDatabase();
} elseif ($action === 'delete_installer') {
    deleteInstaller();
} else {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Geçersiz action: ' . $action]));
}

function installDatabase() {
    $logs = [];
    
    try {
        // Manuel DB bağlantısı (config.php kullanmadan)
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $dbname = 'fidan_takip';
        
        $logs[] = '<span class="log-info">🔌 Veritabanına bağlanılıyor...</span>';
        
        $db = new mysqli($host, $user, $pass);
        
        if ($db->connect_error) {
            throw new Exception('MySQL bağlantı hatası: ' . $db->connect_error);
        }
        
        $logs[] = '<span class="log-success">✓ MySQL bağlantısı başarılı</span>';
        
        // Veritabanını oluştur
        $db->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci");
        $db->select_db($dbname);
        $db->set_charset('utf8mb4');
        
        $logs[] = '<span class="log-success">✓ Veritabanı seçildi: ' . $dbname . '</span>';
        
        // SQL dosyasını oku
        $sql_file = __DIR__ . '/install.sql';
        if (!file_exists($sql_file)) {
            throw new Exception('install.sql dosyası bulunamadı!');
        }
        
        $sql_content = file_get_contents($sql_file);
        $logs[] = '<span class="log-success">✓ SQL dosyası okundu (' . number_format(strlen($sql_content)) . ' karakter)</span>';
        
        // SQL'i çalıştır
        $logs[] = '<span class="log-info">⏳ SQL komutları çalıştırılıyor...</span>';
        
        if ($db->multi_query($sql_content)) {
            do {
                if ($result = $db->store_result()) {
                    $result->free();
                }
            } while ($db->more_results() && $db->next_result());
        }
        
        if ($db->error) {
            $logs[] = '<span class="log-error">⚠ SQL Hatası: ' . $db->error . '</span>';
        } else {
            $logs[] = '<span class="log-success">✓ SQL komutları tamamlandı</span>';
        }
        
        // Tabloları kontrol et
        $logs[] = '<br><span class="log-info"><strong>📋 Tablo Kontrolü:</strong></span>';
        $tables = ['kullanicilar', 'firmalar', 'moduller', 'kullanici_yetkileri', 'cariler', 'urunler', 'faturalar', 'fatura_detaylari', 'odemeler', 'kasa_hareketleri', 'cekler', 'personel'];
        
        $created_count = 0;
        $missing = [];
        
        foreach ($tables as $table) {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $logs[] = '<span class="log-success">✓ ' . $table . '</span>';
                $created_count++;
            } else {
                $logs[] = '<span class="log-error">✗ ' . $table . ' eksik!</span>';
                $missing[] = $table;
            }
        }
        
        $logs[] = '<br><span class="log-info"><strong>Oluşturulan tablo sayısı: ' . $created_count . '/' . count($tables) . '</strong></span>';
        
        if (count($missing) > 0) {
            $logs[] = '<span class="log-error">Eksik tablolar: ' . implode(', ', $missing) . '</span>';
        }
        
        // Admin kullanıcısını kontrol et
        $result = $db->query("SELECT * FROM kullanicilar WHERE kullanici_adi = 'admin' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $logs[] = '<br><span class="log-success">✓ Super Admin kullanıcısı mevcut</span>';
            $logs[] = '<span class="log-info">👤 <strong>Kullanıcı Adı:</strong> admin</span>';
            $logs[] = '<span class="log-info">🔑 <strong>Şifre:</strong> admin123</span>';
        } else {
            $logs[] = '<br><span class="log-error">✗ Admin kullanıcısı bulunamadı!</span>';
        }
        
        // Modül sayısını kontrol et
        $result = $db->query("SELECT COUNT(*) as c FROM moduller");
        if ($result) {
            $row = $result->fetch_assoc();
            $logs[] = '<span class="log-success">✓ ' . $row['c'] . ' modül yüklendi</span>';
        }
        
        // Gerekli klasörleri oluştur
        $folders = ['logs', 'uploads', 'api'];
        foreach ($folders as $folder) {
            $path = __DIR__ . '/' . $folder;
            if (!file_exists($path)) {
                if (mkdir($path, 0755, true)) {
                    $logs[] = '<span class="log-success">✓ ' . $folder . ' klasörü oluşturuldu</span>';
                } else {
                    $logs[] = '<span class="log-error">✗ ' . $folder . ' klasörü oluşturulamadı</span>';
                }
            }
        }
        
        $db->close();
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Kurulum başarıyla tamamlandı!',
            'logs' => $logs
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $logs[] = '<br><span class="log-error">💥 <strong>HATA:</strong> ' . $e->getMessage() . '</span>';
        $logs[] = '<span class="log-error">📍 ' . $e->getFile() . ' : ' . $e->getLine() . '</span>';
        
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'logs' => $logs
        ], JSON_UNESCAPED_UNICODE);
    }
}

function deleteInstaller() {
    try {
        $files = ['install.php', 'install_process.php', 'install.sql', 'test_mysql.php', 'README.md'];
        $deleted = [];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                if (@unlink($file)) {
                    $deleted[] = $file;
                }
            }
        }
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => count($deleted) . ' dosya silindi: ' . implode(', ', $deleted)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
