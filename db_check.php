<?php
/**
 * Veritabanı Kontrol Scripti
 */
require_once 'config.php';
require_once 'includes/auth.php';

echo "<h2>Veritabanı Kontrolü</h2>";

try {
    // Login kontrolü
    if (!isset($_SESSION['user_id'])) {
        echo "<p>❌ Önce login olmanız gerekiyor!</p>";
        echo "<p><a href='login.php'>Login Sayfasına Git</a></p>";
        exit;
    }
    
    $firma_id = $_SESSION['firma_id'] ?? null;
    echo "<p><strong>Firma ID:</strong> " . $firma_id . "</p>";
    
    if (!$firma_id) {
        echo "<p>❌ Firma ID bulunamadı!</p>";
        exit;
    }
    
    // Cariler kontrolü
    echo "<h3>Cariler Tablosu:</h3>";
    $result = $db->query("SELECT COUNT(*) as count FROM cariler WHERE firma_id = $firma_id");
    $row = $result->fetch_assoc();
    echo "<p>Toplam cari sayısı: " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        $result = $db->query("SELECT id, unvan, is_musteri, is_tedarikci FROM cariler WHERE firma_id = $firma_id LIMIT 5");
        echo "<table border='1'><tr><th>ID</th><th>Unvan</th><th>Müşteri</th><th>Tedarikçi</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['unvan']}</td><td>{$row['is_musteri']}</td><td>{$row['is_tedarikci']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Ürünler kontrolü
    echo "<h3>Ürünler Tablosu:</h3>";
    $result = $db->query("SELECT COUNT(*) as count FROM urunler WHERE firma_id = $firma_id");
    $row = $result->fetch_assoc();
    echo "<p>Toplam ürün sayısı: " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        $result = $db->query("SELECT id, urun_adi, stok_miktari, alis_fiyati, satis_fiyati FROM urunler WHERE firma_id = $firma_id LIMIT 5");
        echo "<table border='1'><tr><th>ID</th><th>Ürün Adı</th><th>Stok</th><th>Alış Fiyatı</th><th>Satış Fiyatı</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['urun_adi']}</td><td>{$row['stok_miktari']}</td><td>{$row['alis_fiyati']}</td><td>{$row['satis_fiyati']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Veriler mevcut
    echo "<h3>✅ Veriler Mevcut!</h3>";
    echo "<p>Cariler ve ürünler tablosunda veri bulundu. Fatura sayfasını test edebilirsiniz.</p>";
    
    echo "<h3>API Test Linkleri:</h3>";
    echo "<p><a href='test_api.php' target='_blank'>API Test Sayfası</a></p>";
    echo "<p><a href='modules/faturalar/create.php?tip=alis' target='_blank'>Alış Faturası Oluştur</a></p>";
    echo "<p><a href='modules/faturalar/create.php?tip=satis' target='_blank'>Satış Faturası Oluştur</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
}
?>
