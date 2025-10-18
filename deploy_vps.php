<?php
/**
 * VPS Deployment Script
 * prokonstarim.com.tr/muhasebedemo için
 */

echo "=== VPS DEPLOYMENT SCRIPT ===\n";
echo "Hedef: prokonstarim.com.tr/muhasebedemo\n";
echo "Veritabanı: muhasebedemo\n";
echo "Kullanıcı: admin\n\n";

// Güncellenmiş ayarlar
$config = [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'admin', 
    'DB_PASS' => 'zd3up16Hzmpy!',
    'DB_NAME' => 'muhasebedemo',
    'BASE_URL' => 'https://prokonstarim.com.tr/muhasebedemo',
    'API_URL' => 'https://prokonstarim.com.tr/muhasebedemo/api',
    'SITE_URL' => 'https://prokonstarim.com.tr/muhasebedemo'
];

echo "Güncellenmiş Ayarlar:\n";
foreach ($config as $key => $value) {
    echo "- $key: $value\n";
}

echo "\n=== FLUTTER UYGULAMA AYARLARI ===\n";
echo "API Base URL: https://prokonstarim.com.tr/muhasebedemo\n";
echo "HTML İndirme URL: https://prokonstarim.com.tr/muhasebedemo/api/flutter/teklifler.php\n";

echo "\n=== VPS YÜKLEME ADIMLARI ===\n";
echo "1. Tüm dosyaları /public_html/muhasebedemo/ klasörüne yükleyin\n";
echo "2. Veritabanını muhasebedemo olarak oluşturun\n";
echo "3. Kullanıcı admin, şifre zd3up16Hzmpy! olarak ayarlayın\n";
echo "4. config.php dosyası otomatik güncellenmiş durumda\n";
echo "5. Flutter uygulaması yeni URL'leri kullanacak\n";

echo "\n=== SSL SERTİFİKASI ===\n";
echo "HTTPS kullanıldığı için SSL sertifikası gerekli\n";
echo "Let's Encrypt veya cPanel SSL kullanabilirsiniz\n";

echo "\n=== TEST EDİLECEK ÖZELLİKLER ===\n";
echo "- Web panel girişi\n";
echo "- Mobil uygulama API bağlantısı\n";
echo "- HTML indirme özelliği\n";
echo "- Tüm modüller (Teklifler, Cariler, Stok, vb.)\n";

echo "\nDeployment hazır! 🚀\n";
?>
