<?php
require_once 'config.php';

echo "<h2>Stok Hareket Raporu Kontrol</h2>";

try {
    // Tabloları kontrol et
    $tables = ['faturalar', 'fatura_detaylari', 'urunler'];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>✓ $table tablosu mevcut</p>";
            
            // Kayıt sayısını kontrol et
            $count_result = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            echo "<p>→ $count kayıt bulundu</p>";
            
            // Örnek kayıtları göster
            if ($count > 0) {
                $sample_result = $db->query("SELECT * FROM $table LIMIT 3");
                echo "<table border='1' style='margin-left: 20px;'>";
                echo "<tr>";
                while ($field = $sample_result->fetch_field()) {
                    echo "<th>" . $field->name . "</th>";
                }
                echo "</tr>";
                while ($row = $sample_result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table><br>";
            }
        } else {
            echo "<p style='color: red;'>✗ $table tablosu bulunamadı</p>";
        }
    }
    
    // Stok hareket raporu testi
    echo "<h3>Stok Hareket Raporu Testi</h3>";
    
    $firma_id = 3; // Test için sabit firma ID
    $baslangic = '2024-01-01';
    $bitis = '2025-12-31';
    
    // Fatura hareketleri sorgusu
    $query = "
        SELECT 
            f.fatura_tarihi as tarih,
            u.urun_adi,
            CASE 
                WHEN f.fatura_tipi = 'alis' THEN 'Alış Faturası'
                WHEN f.fatura_tipi = 'satis' THEN 'Satış Faturası'
            END as hareket_tipi_display,
            f.fatura_tipi as hareket_tipi,
            f.fatura_no as belge_no,
            fd.miktar,
            fd.birim_fiyat,
            fd.toplam,
            u.stok_miktari as kalan_stok
        FROM faturalar f
        JOIN fatura_detaylari fd ON f.id = fd.fatura_id
        JOIN urunler u ON fd.urun_id = u.id
        WHERE f.firma_id = ? 
        AND f.fatura_tarihi BETWEEN ? AND ?
        ORDER BY f.fatura_tarihi ASC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("iss", $firma_id, $baslangic, $bitis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Stok hareket raporu verisi bulundu</p>";
        echo "<table border='1'>";
        echo "<tr><th>Tarih</th><th>Ürün</th><th>Hareket Tipi</th><th>Belge No</th><th>Miktar</th><th>Birim Fiyat</th><th>Toplam</th><th>Kalan Stok</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['tarih'] . "</td>";
            echo "<td>" . $row['urun_adi'] . "</td>";
            echo "<td>" . $row['hareket_tipi_display'] . "</td>";
            echo "<td>" . $row['belge_no'] . "</td>";
            echo "<td>" . $row['miktar'] . "</td>";
            echo "<td>" . $row['birim_fiyat'] . "</td>";
            echo "<td>" . $row['toplam'] . "</td>";
            echo "<td>" . $row['kalan_stok'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ Bu tarih aralığında stok hareketi bulunamadı</p>";
        
        // Tüm faturaları kontrol et
        $all_faturas = $db->query("SELECT COUNT(*) as count FROM faturalar WHERE firma_id = $firma_id");
        $fatura_count = $all_faturas->fetch_assoc()['count'];
        echo "<p>Toplam fatura sayısı: $fatura_count</p>";
        
        if ($fatura_count > 0) {
            $sample_fatura = $db->query("SELECT * FROM faturalar WHERE firma_id = $firma_id LIMIT 1");
            $fatura = $sample_fatura->fetch_assoc();
            echo "<p>Örnek fatura tarihi: " . $fatura['fatura_tarihi'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
