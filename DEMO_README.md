# İşletme Takip Sistemi - DEMO VERSİYON

## ⚠️ ÖNEMLİ UYARI

Bu sistem **eksik çalışacak şekilde** ayarlanmıştır! Production'a geçmeden önce aşağıdaki eksiklikleri tamamlamanız gerekmektedir.

## 🚨 EKSİK ÇALIŞAN ÖZELLİKLER

### 1. Firebase Bildirimleri
- **Durum**: ❌ Çalışmıyor
- **Sebep**: Demo Firebase key kullanılıyor
- **Çözüm**: Gerçek Firebase projesi oluşturun ve key'i güncelleyin

### 2. JWT Güvenliği
- **Durum**: ⚠️ Güvensiz
- **Sebep**: Demo secret key kullanılıyor
- **Çözüm**: Güçlü bir secret key oluşturun

### 3. Veritabanı Güvenliği
- **Durum**: ⚠️ Güvensiz
- **Sebep**: Şifre yok, root kullanıcısı
- **Çözüm**: Güçlü şifre ve özel kullanıcı oluşturun

### 4. SSL Sertifikası
- **Durum**: ❌ Yok
- **Sebep**: HTTP kullanılıyor
- **Çözüm**: HTTPS sertifikası kurun

### 5. Backup Sistemi
- **Durum**: ❌ Çalışmıyor
- **Sebep**: Backup fonksiyonları eksik
- **Çözüm**: Otomatik backup sistemi kurun

### 6. Log Yönetimi
- **Durum**: ⚠️ Eksik
- **Sebep**: Log temizleme yok
- **Çözüm**: Log rotation sistemi kurun

### 7. Cache Sistemi
- **Durum**: ❌ Yok
- **Sebep**: Cache fonksiyonları eksik
- **Çözüm**: Redis/Memcached kurun

### 8. Rate Limiting
- **Durum**: ❌ Yok
- **Sebep**: DDoS koruması yok
- **Çözüm**: Rate limiting sistemi kurun

### 9. Input Validation
- **Durum**: ⚠️ Eksik
- **Sebep**: Güvenlik kontrolleri yok
- **Çözüm**: Input validation ekleyin

### 10. Error Handling
- **Durum**: ⚠️ Eksik
- **Sebep**: Hata yönetimi eksik
- **Çözüm**: Kapsamlı error handling ekleyin

## 🛠️ PRODUCTION'A GEÇİŞ REHBERİ

### 1. Veritabanı Ayarları
```php
// config.php dosyasında
define('DB_USER', 'production_user'); // root yerine
define('DB_PASS', 'güçlü_şifre_123!'); // boş yerine
```

### 2. Firebase Ayarları
```php
// config.php dosyasında
define('FIREBASE_SERVER_KEY', 'GERÇEK_FIREBASE_KEY');
define('FIREBASE_PROJECT_ID', 'gerçek_proje_id');
```

### 3. JWT Güvenliği
```php
// config.php dosyasında
define('JWT_SECRET_KEY', 'çok_güçlü_gizli_anahtar_2025!');
```

### 4. URL Ayarları
```php
// config.php dosyasında
define('BASE_URL', 'https://yourdomain.com/muhasebedemo');
define('API_URL', 'https://yourdomain.com/muhasebedemo/api');
```

### 5. SSL Sertifikası
- Let's Encrypt veya ücretli SSL sertifikası kurun
- HTTP'den HTTPS'e yönlendirme yapın

### 6. Güvenlik Ayarları
```php
// config.php dosyasında
error_reporting(0); // Production'da hataları gizle
ini_set('display_errors', 0);
```

## 📱 MOBİL UYGULAMA (FLUTTER)

### API URL Güncelleme
```dart
// lib/utils/app_constants.dart dosyasında
static const String baseUrl = 'https://yourdomain.com/muhasebedemo';
```

### Android Ayarları
```xml
<!-- android/app/src/main/AndroidManifest.xml -->
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
```

### iOS Ayarları
```xml
<!-- ios/Runner/Info.plist -->
<key>NSAppTransportSecurity</key>
<dict>
    <key>NSAllowsArbitraryLoads</key>
    <true/>
</dict>
```

## 🔧 KURULUM ADIMLARI

### 1. XAMPP Kurulumu
```bash
# XAMPP'i indirin ve kurun
# Apache ve MySQL'i başlatın
# phpMyAdmin'e gidin: http://localhost/phpmyadmin
```

### 2. Veritabanı Oluşturma
```sql
CREATE DATABASE muhasebedemo CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
```

### 3. Proje Dosyalarını Kopyalama
```bash
# Proje dosyalarını şu klasöre kopyalayın:
C:\xampp\htdocs\muhasebedemo\
```

### 4. Veritabanı Tablolarını Oluşturma
```bash
# Tarayıcıda şu adresi açın:
http://localhost/muhasebedemo/install.php
```

### 5. Demo Verilerini Yükleme
```bash
# SQL dosyasını phpMyAdmin'de çalıştırın:
muhasebedemo.sql
```

## 🌐 ERİŞİM BİLGİLERİ

### Web Panel
- **URL**: http://192.168.1.137/muhasebedemo
- **Admin Kullanıcı**: admin
- **Şifre**: admin123

### Mobil API
- **Base URL**: http://192.168.1.137/muhasebedemo/api
- **Auth Endpoint**: /flutter_api/auth.php

### Flutter Uygulaması
- **API URL**: http://192.168.1.137/muhasebedemo
- **Platform**: Android & iOS

## 📊 SİSTEM ÖZELLİKLERİ

### ✅ Çalışan Özellikler
- Kullanıcı girişi/çıkışı
- Dashboard görünümü
- Cari yönetimi
- Stok takibi
- Fatura oluşturma
- Çek yönetimi
- Raporlar
- Mobil API

### ❌ Eksik Özellikler
- Firebase bildirimleri
- Otomatik backup
- Log yönetimi
- Cache sistemi
- Rate limiting
- SSL sertifikası
- Güvenlik kontrolleri

## 🚀 PRODUCTION CHECKLIST

- [ ] Veritabanı şifresi değiştirildi
- [ ] Firebase key güncellendi
- [ ] JWT secret key değiştirildi
- [ ] SSL sertifikası kuruldu
- [ ] Backup sistemi kuruldu
- [ ] Log rotation ayarlandı
- [ ] Cache sistemi kuruldu
- [ ] Rate limiting eklendi
- [ ] Input validation eklendi
- [ ] Error handling eklendi
- [ ] Güvenlik testleri yapıldı
- [ ] Performance testleri yapıldı

## 📞 DESTEK

Bu demo sistem sadece test amaçlıdır. Production kullanımı için tüm eksiklikleri tamamlamanız gerekmektedir.

**Uyarı**: Bu sistemi production'da kullanmadan önce mutlaka bir güvenlik uzmanına danışın!

---

**© 2025 İşletme Takip Sistemi - Demo Versiyon**
