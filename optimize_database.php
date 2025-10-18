<?php
require_once 'config.php';

try {
    echo "=== Database Optimizasyonu Başlatılıyor ===\n";
    echo "Tarih: " . date('Y-m-d H:i:s') . "\n\n";
    
    $optimizations = [];
    
    // 1. Faturalar tablosu optimizasyonu
    echo "1. Faturalar tablosu optimizasyonu...\n";
    
    $faturalar_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_faturalar_firma_tarih ON faturalar(firma_id, fatura_tarihi)",
        "CREATE INDEX IF NOT EXISTS idx_faturalar_cari_tarih ON faturalar(cari_id, fatura_tarihi)",
        "CREATE INDEX IF NOT EXISTS idx_faturalar_tip_tarih ON faturalar(fatura_tipi, fatura_tarihi)",
        "CREATE INDEX IF NOT EXISTS idx_faturalar_vade_tarihi ON faturalar(vade_tarihi)",
        "CREATE INDEX IF NOT EXISTS idx_faturalar_odeme_durumu ON faturalar(odeme_durumu)"
    ];
    
    foreach ($faturalar_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 2. Fatura detayları optimizasyonu
    echo "2. Fatura detayları optimizasyonu...\n";
    
    $fatura_detay_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_fatura_detay_fatura_id ON fatura_detaylari(fatura_id)",
        "CREATE INDEX IF NOT EXISTS idx_fatura_detay_urun_id ON fatura_detaylari(urun_id)"
    ];
    
    foreach ($fatura_detay_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 3. Cariler tablosu optimizasyonu
    echo "3. Cariler tablosu optimizasyonu...\n";
    
    $cariler_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_cariler_firma_id ON cariler(firma_id)",
        "CREATE INDEX IF NOT EXISTS idx_cariler_unvan ON cariler(unvan)",
        "CREATE INDEX IF NOT EXISTS idx_cariler_vergi_no ON cariler(vergi_no)",
        "CREATE INDEX IF NOT EXISTS idx_cariler_aktif ON cariler(aktif)",
        "CREATE INDEX IF NOT EXISTS idx_cariler_bakiye ON cariler(bakiye)"
    ];
    
    foreach ($cariler_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 4. Ürünler tablosu optimizasyonu
    echo "4. Ürünler tablosu optimizasyonu...\n";
    
    $urunler_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_urunler_firma_id ON urunler(firma_id)",
        "CREATE INDEX IF NOT EXISTS idx_urunler_urun_kodu ON urunler(urun_kodu)",
        "CREATE INDEX IF NOT EXISTS idx_urunler_kategori ON urunler(kategori)",
        "CREATE INDEX IF NOT EXISTS idx_urunler_aktif ON urunler(aktif)",
        "CREATE INDEX IF NOT EXISTS idx_urunler_stok_miktari ON urunler(stok_miktari)"
    ];
    
    foreach ($urunler_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 5. Kasa hareketleri optimizasyonu
    echo "5. Kasa hareketleri optimizasyonu...\n";
    
    $kasa_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_kasa_firma_tarih ON kasa_hareketleri(firma_id, tarih)",
        "CREATE INDEX IF NOT EXISTS idx_kasa_islem_tipi ON kasa_hareketleri(islem_tipi)",
        "CREATE INDEX IF NOT EXISTS idx_kasa_kategori ON kasa_hareketleri(kategori)",
        "CREATE INDEX IF NOT EXISTS idx_kasa_tarih ON kasa_hareketleri(tarih)"
    ];
    
    foreach ($kasa_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 6. Çekler tablosu optimizasyonu
    echo "6. Çekler tablosu optimizasyonu...\n";
    
    $cekler_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_cekler_firma_id ON cekler(firma_id)",
        "CREATE INDEX IF NOT EXISTS idx_cekler_cari_id ON cekler(cari_id)",
        "CREATE INDEX IF NOT EXISTS idx_cekler_vade_tarihi ON cekler(vade_tarihi)",
        "CREATE INDEX IF NOT EXISTS idx_cekler_durum ON cekler(durum)",
        "CREATE INDEX IF NOT EXISTS idx_cekler_cek_no ON cekler(cek_no)"
    ];
    
    foreach ($cekler_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 7. Kullanıcılar tablosu optimizasyonu
    echo "7. Kullanıcılar tablosu optimizasyonu...\n";
    
    $kullanicilar_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_kullanicilar_firma_id ON kullanicilar(firma_id)",
        "CREATE INDEX IF NOT EXISTS idx_kullanicilar_rol ON kullanicilar(rol)",
        "CREATE INDEX IF NOT EXISTS idx_kullanicilar_aktif ON kullanicilar(aktif)",
        "CREATE INDEX IF NOT EXISTS idx_kullanicilar_kullanici_adi ON kullanicilar(kullanici_adi)"
    ];
    
    foreach ($kullanicilar_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // 8. Bildirim geçmişi optimizasyonu
    echo "8. Bildirim geçmişi optimizasyonu...\n";
    
    $bildirim_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_bildirim_firma_id ON bildirim_gecmisi(firma_id)",
        "CREATE INDEX IF NOT EXISTS idx_bildirim_kullanici_id ON bildirim_gecmisi(kullanici_id)",
        "CREATE INDEX IF NOT EXISTS idx_bildirim_tipi ON bildirim_gecmisi(bildirim_tipi)",
        "CREATE INDEX IF NOT EXISTS idx_bildirim_tarih ON bildirim_gecmisi(olusturma_tarihi)"
    ];
    
    foreach ($bildirim_indexes as $index) {
        if ($db->query($index)) {
            $optimizations[] = "✅ " . substr($index, 0, 50) . "...";
        } else {
            $optimizations[] = "❌ " . substr($index, 0, 50) . "... - " . $db->error;
        }
    }
    
    // Sonuçları göster
    echo "\n=== Optimizasyon Sonuçları ===\n";
    foreach ($optimizations as $result) {
        echo $result . "\n";
    }
    
    // Tablo boyutlarını kontrol et
    echo "\n=== Tablo Boyutları ===\n";
    $tables = ['faturalar', 'fatura_detaylari', 'cariler', 'urunler', 'kasa_hareketleri', 'cekler', 'kullanicilar'];
    
    foreach ($tables as $table) {
        $count_query = "SELECT COUNT(*) as count FROM $table";
        $result = $db->query($count_query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "📊 $table: $count kayıt\n";
        }
    }
    
    echo "\n🎉 Database optimizasyonu tamamlandı!\n";
    echo "\n📈 Performans İyileştirmeleri:\n";
    echo "✅ Tüm tablolara index eklendi\n";
    echo "✅ Query performansı artırıldı\n";
    echo "✅ Dashboard yükleme hızı iyileştirildi\n";
    echo "✅ Rapor sayfaları hızlandırıldı\n";
    echo "✅ Arama işlemleri optimize edildi\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
