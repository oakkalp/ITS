<?php
// Test için basit ürün kodu oluşturma
function generateUrunKodu($firmaId, $mevcutKodlar) {
    // Firma ID'sine göre prefix oluştur
    $prefix = 'U' . str_pad($firmaId, 3, '0', STR_PAD_LEFT);
    
    // Bugünün tarihini al (YYMMDD formatında)
    $tarih = date('ymd');
    
    // Bugün oluşturulan ürün sayısını bul
    $bugunKodlari = array_filter($mevcutKodlar, function($kod) use ($prefix, $tarih) {
        return strpos($kod, $prefix . $tarih) === 0;
    });
    
    $siraNo = count($bugunKodlari) + 1;
    
    // Format: U00120250115001 (U + FirmaID + Tarih + SıraNo)
    return $prefix . $tarih . str_pad($siraNo, 3, '0', STR_PAD_LEFT);
}

// Test
$firmaId = 5;
$mevcutKodlar = ['U00520250115001', 'U00520250115002'];

echo "Test Sonuçları:\n";
echo "Firma ID: $firmaId\n";
echo "Mevcut Kodlar: " . implode(', ', $mevcutKodlar) . "\n";
echo "Yeni Kod: " . generateUrunKodu($firmaId, $mevcutKodlar) . "\n";

// Bugünün tarihi
echo "Bugünün Tarihi: " . date('ymd') . "\n";
echo "Tam Tarih: " . date('Y-m-d H:i:s') . "\n";
?>
