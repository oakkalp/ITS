<?php
require_once 'config.php';

echo "<h2>Teklif Verilerini Kontrol Et</h2>";

try {
    // Teklifler tablosunda kayıt var mı?
    $query = "SELECT * FROM teklifler ORDER BY id DESC LIMIT 5";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>Teklifler:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Firma ID</th><th>Teklif No</th><th>Teklif Başlığı</th><th>Teklif Tarihi</th><th>Geçerlilik Tarihi</th><th>Cari ID</th><th>Kullanıcı ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['firma_id'] . "</td>";
            echo "<td>" . $row['teklif_no'] . "</td>";
            echo "<td>" . $row['teklif_basligi'] . "</td>";
            echo "<td>" . $row['teklif_tarihi'] . "</td>";
            echo "<td>" . $row['gecerlilik_tarihi'] . "</td>";
            echo "<td>" . $row['cari_id'] . "</td>";
            echo "<td>" . $row['kullanici_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Teklifler tablosunda kayıt yok!</p>";
    }
    
    // Teklif detayları tablosunda kayıt var mı?
    $query = "SELECT * FROM teklif_detaylari ORDER BY id DESC LIMIT 5";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>Teklif Detayları:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Teklif ID</th><th>Ürün ID</th><th>Miktar</th><th>Birim</th><th>Birim Fiyat</th><th>KDV Oranı</th><th>Toplam</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['teklif_id'] . "</td>";
            echo "<td>" . $row['urun_id'] . "</td>";
            echo "<td>" . $row['miktar'] . "</td>";
            echo "<td>" . ($row['birim'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['birim_fiyat'] . "</td>";
            echo "<td>" . $row['kdv_orani'] . "</td>";
            echo "<td>" . $row['toplam'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Teklif detayları tablosunda kayıt yok!</p>";
    }
    
    // Firmalar tablosunda kayıt var mı?
    $query = "SELECT id, firma_adi FROM firmalar LIMIT 3";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>Firmalar:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Firma Adı</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['firma_adi'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Firmalar tablosunda kayıt yok!</p>";
    }
    
    // Cariler tablosunda kayıt var mı?
    $query = "SELECT id, unvan FROM cariler LIMIT 3";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>Cariler:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Unvan</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['unvan'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Cariler tablosunda kayıt yok!</p>";
    }
    
    // Urunler tablosunda kayıt var mı?
    $query = "SELECT id, urun_adi FROM urunler LIMIT 3";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>Ürünler:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Ürün Adı</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['urun_adi'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Ürünler tablosunda kayıt yok!</p>";
    }
    
    // Test için yeni teklif ekle
    echo "<h3>Yeni Test Teklifi Ekle:</h3>";
    
    $sql = "INSERT INTO teklifler (
        firma_id, teklif_no, teklif_basligi, teklif_tarihi, gecerlilik_tarihi,
        cari_id, ara_toplam, kdv_tutari, genel_toplam, kullanici_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $firma_id = 1;
    $teklif_no = 'TKL-' . date('YmdHis');
    $teklif_basligi = 'Test Teklifi ' . date('Y-m-d H:i:s');
    $teklif_tarihi = date('Y-m-d');
    $gecerlilik_tarihi = date('Y-m-d', strtotime('+30 days'));
    $cari_id = 1;
    $ara_toplam = 1000.00;
    $kdv_tutari = 180.00;
    $genel_toplam = 1180.00;
    $kullanici_id = 7;
    
    $stmt->bind_param("issssidddi", $firma_id, $teklif_no, $teklif_basligi, $teklif_tarihi, $gecerlilik_tarihi, $cari_id, $ara_toplam, $kdv_tutari, $genel_toplam, $kullanici_id);
    
    if ($stmt->execute()) {
        $teklif_id = $db->insert_id;
        echo "<p style='color: green;'>✓ Yeni test teklifi eklendi (ID: $teklif_id)</p>";
        
        // Teklif detayı ekle
        $sql = "INSERT INTO teklif_detaylari (
            teklif_id, urun_id, miktar, birim_fiyat, kdv_orani, kdv_tutari, toplam
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $urun_id = 1;
        $miktar = 2.00;
        $birim_fiyat = 500.00;
        $kdv_orani = 18.00;
        $kdv_tutari = 180.00;
        $toplam = 1180.00;
        
        $stmt->bind_param("iiddddd", $teklif_id, $urun_id, $miktar, $birim_fiyat, $kdv_orani, $kdv_tutari, $toplam);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Test teklif detayı eklendi</p>";
            echo "<p><a href='api/teklifler/pdf_real.php?id=$teklif_id&debug=1'>PDF debug test (ID: $teklif_id)</a></p>";
            echo "<p><a href='api/teklifler/pdf_real.php?id=$teklif_id'>PDF indirme test (ID: $teklif_id)</a></p>";
        } else {
            echo "<p style='color: red;'>✗ Test teklif detayı eklenemedi: " . $stmt->error . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Test teklifi eklenemedi: " . $stmt->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
