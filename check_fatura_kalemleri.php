<?php
require_once 'config.php';

echo "<h2>Fatura Kalemleri Kontrolü</h2>";

try {
    // Ürün ID'sini al
    $urun_id = $_GET['id'] ?? 13; // Varsayılan olarak 13
    
    echo "<h3>Ürün ID: $urun_id için Fatura Kalemleri</h3>";
    
    // Fatura detaylarını kontrol et
    $query = "SELECT fd.*, f.fatura_no, f.fatura_tarihi 
              FROM fatura_detaylari fd 
              LEFT JOIN faturalar f ON fd.fatura_id = f.id 
              WHERE fd.urun_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $urun_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Fatura Detay ID</th><th>Fatura ID</th><th>Fatura No</th><th>Fatura Tarihi</th><th>Miktar</th><th>Birim Fiyat</th><th>Toplam</th><th>İşlem</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['fatura_id'] . "</td>";
            echo "<td>" . $row['fatura_no'] . "</td>";
            echo "<td>" . $row['fatura_tarihi'] . "</td>";
            echo "<td>" . $row['miktar'] . "</td>";
            echo "<td>" . $row['birim_fiyat'] . " ₺</td>";
            echo "<td>" . $row['toplam'] . " ₺</td>";
            echo "<td><a href='delete_fatura_detay.php?id=" . $row['id'] . "' onclick='return confirm(\"Bu fatura kalemini silmek istediğinizden emin misiniz?\")'>Sil</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Toplam: " . $result->num_rows . " fatura kalemi bulundu</h3>";
        
        // Tüm fatura kalemlerini silme seçeneği
        echo "<p><a href='delete_all_fatura_detay.php?urun_id=$urun_id' onclick='return confirm(\"Bu ürüne ait TÜM fatura kalemlerini silmek istediğinizden emin misiniz?\")' style='color: red; font-weight: bold;'>TÜM FATURA KALEMLERİNİ SİL</a></p>";
        
    } else {
        echo "<p style='color: green;'>✅ Bu ürüne ait fatura kalemi bulunamadı. Güvenle silinebilir.</p>";
        
        // Ürünü silme seçeneği
        echo "<p><a href='api/stok/delete.php?id=$urun_id' onclick='return confirm(\"Ürünü silmek istediğinizden emin misiniz?\")' style='color: green; font-weight: bold;'>ÜRÜNÜ SİL</a></p>";
    }
    
    // Ürün bilgilerini göster
    echo "<h3>Ürün Bilgileri</h3>";
    $urun_query = "SELECT * FROM urunler WHERE id = ?";
    $urun_stmt = $db->prepare($urun_query);
    $urun_stmt->bind_param("i", $urun_id);
    $urun_stmt->execute();
    $urun_result = $urun_stmt->get_result();
    
    if ($urun_result->num_rows > 0) {
        $urun = $urun_result->fetch_assoc();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Ürün Adı</th><th>Kategori</th><th>Birim</th><th>Stok</th><th>Durum</th></tr>";
        echo "<tr>";
        echo "<td>" . $urun['id'] . "</td>";
        echo "<td>" . $urun['urun_adi'] . "</td>";
        echo "<td>" . ($urun['kategori'] ?? 'N/A') . "</td>";
        echo "<td>" . ($urun['birim'] ?? 'N/A') . "</td>";
        echo "<td>" . ($urun['stok'] ?? '0') . "</td>";
        echo "<td>" . ($urun['aktif'] == 1 ? 'Aktif' : 'Pasif') . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Ürün bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<h3>Kontrol Tamamlandı</h3>";
echo "<p><a href='modules/stok/list.php'>Stok Listesi Sayfasına Dön</a></p>";
?>
