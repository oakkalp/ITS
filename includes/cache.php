<?php
require_once 'config.php';

/**
 * Basit Cache Sistemi
 * Dosya tabanlı cache sistemi
 */
class CacheSystem {
    private $cache_dir;
    private $default_ttl = 3600; // 1 saat
    
    public function __construct($cache_dir = 'cache') {
        $this->cache_dir = __DIR__ . '/' . $cache_dir;
        
        // Cache dizinini oluştur
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Cache'e veri kaydet
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;
        $expires = time() + $ttl;
        
        $cache_data = [
            'data' => $data,
            'expires' => $expires,
            'created' => time()
        ];
        
        $file = $this->getCacheFile($key);
        return file_put_contents($file, serialize($cache_data)) !== false;
    }
    
    /**
     * Cache'den veri al
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }
        
        $cache_data = unserialize($content);
        if ($cache_data === false) {
            return null;
        }
        
        // Süresi dolmuş mu kontrol et
        if (time() > $cache_data['expires']) {
            $this->delete($key);
            return null;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Cache'den veri sil
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    /**
     * Tüm cache'i temizle
     */
    public function clear() {
        $files = glob($this->cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    /**
     * Cache dosya yolu
     */
    private function getCacheFile($key) {
        return $this->cache_dir . '/' . md5($key) . '.cache';
    }
    
    /**
     * Cache istatistikleri
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*');
        $total_files = count($files);
        $total_size = 0;
        $expired_files = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            
            $content = file_get_contents($file);
            if ($content !== false) {
                $cache_data = unserialize($content);
                if ($cache_data && time() > $cache_data['expires']) {
                    $expired_files++;
                }
            }
        }
        
        return [
            'total_files' => $total_files,
            'total_size' => $total_size,
            'expired_files' => $expired_files,
            'active_files' => $total_files - $expired_files
        ];
    }
}

// Cache helper fonksiyonları
function cache_set($key, $data, $ttl = 3600) {
    global $cache;
    if (!$cache) {
        $cache = new CacheSystem();
    }
    return $cache->set($key, $data, $ttl);
}

function cache_get($key) {
    global $cache;
    if (!$cache) {
        $cache = new CacheSystem();
    }
    return $cache->get($key);
}

function cache_delete($key) {
    global $cache;
    if (!$cache) {
        $cache = new CacheSystem();
    }
    return $cache->delete($key);
}

function cache_clear() {
    global $cache;
    if (!$cache) {
        $cache = new CacheSystem();
    }
    return $cache->clear();
}

// Cache'i başlat
$cache = new CacheSystem();

// Cache test
echo "=== Cache Sistemi Test ===\n";

// Test verisi kaydet
$test_data = [
    'message' => 'Cache test başarılı!',
    'timestamp' => time(),
    'random' => rand(1000, 9999)
];

if (cache_set('test_key', $test_data, 60)) {
    echo "✅ Test verisi cache'e kaydedildi\n";
} else {
    echo "❌ Test verisi kaydedilemedi\n";
}

// Test verisi oku
$cached_data = cache_get('test_key');
if ($cached_data) {
    echo "✅ Cache'den veri okundu: " . json_encode($cached_data) . "\n";
} else {
    echo "❌ Cache'den veri okunamadı\n";
}

// Cache istatistikleri
$stats = $cache->getStats();
echo "\n📊 Cache İstatistikleri:\n";
echo "Toplam dosya: " . $stats['total_files'] . "\n";
echo "Toplam boyut: " . number_format($stats['total_size'] / 1024, 2) . " KB\n";
echo "Aktif dosya: " . $stats['active_files'] . "\n";
echo "Süresi dolmuş: " . $stats['expired_files'] . "\n";

echo "\n🎉 Cache sistemi hazır!\n";
?>
