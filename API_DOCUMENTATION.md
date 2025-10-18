# Muhasebe Demo API Dokümantasyonu

## Genel Bilgiler
- **Base URL**: `https://prokonstarim.com.tr/muhasebedemo`
- **Kimlik Doğrulama**: Hibrit sistem (Session + JWT)
- **Content-Type**: `application/json` (Flutter), `application/x-www-form-urlencoded` (Web)

## Kimlik Doğrulama Sistemi

### Hibrit Kimlik Doğrulama
```php
// Web paneli için session kontrolü
if (isset($_SESSION['user_id'])) {
    $firma_id = get_firma_id();
    $user_id = $_SESSION['user_id'];
} else {
    // Flutter uygulaması için JWT token kontrolü
    require_once '../../includes/jwt.php';
    $headers = getallheaders();
    $token = null;
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    if (!$token) {
        json_error('Authorization header gerekli', 401);
    }
    try {
        $decoded = JWT::decode($token, JWT_SECRET_KEY);
        $firma_id = $decoded->firma_id;
        $user_id = $decoded->user_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['firma_id'] = $firma_id;
        $_SESSION['ad_soyad'] = $decoded->ad_soyad;
        $_SESSION['firma_adi'] = $decoded->firma_adi;
    } catch (Exception $e) {
        json_error('Geçersiz token', 401);
    }
}
```

## API Endpoint'leri

### 1. Kimlik Doğrulama

#### Login
- **URL**: `/api/auth/login.php`
- **Method**: POST
- **Content-Type**: `application/json`
- **Request**:
```json
{
    "kullanici_adi": "melih",
    "sifre": "melih1996"
}
```
- **Response**:
```json
{
    "success": true,
    "message": "Giriş başarılı",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJh...",
        "user": {
            "id": 1,
            "ad_soyad": "Melih Dalar",
            "firma_id": 4,
            "rol": "admin"
        }
    }
}
```

### 2. Cariler (Müşteriler)

#### Cariler Listesi
- **URL**: `/api/cariler/list.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Response**:
```json
{
    "success": true,
    "message": "Cariler listelendi",
    "data": [
        {
            "id": 1,
            "unvan": "Test Müşteri",
            "telefon": "05551234567",
            "email": "test@example.com",
            "adres": "Test Adres",
            "is_musteri": 1,
            "is_tedarikci": 0
        }
    ]
}
```

#### Cari Ekleme
- **URL**: `/api/cariler/create.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "unvan": "Yeni Müşteri",
    "telefon": "05551234567",
    "email": "yeni@example.com",
    "adres": "Yeni Adres",
    "is_musteri": 1,
    "is_tedarikci": 0
}
```

#### Cari Güncelleme
- **URL**: `/api/cariler/update.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "id": 1,
    "unvan": "Güncellenmiş Müşteri",
    "telefon": "05551234567",
    "email": "guncel@example.com",
    "adres": "Güncel Adres",
    "is_musteri": 1,
    "is_tedarikci": 0
}
```

#### Cari Silme
- **URL**: `/api/cariler/delete.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "id": 1
}
```

### 3. Ürünler/Stok

#### Ürünler Listesi
- **URL**: `/api/urunler/list.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Response**:
```json
{
    "success": true,
    "message": "Ürünler listelendi",
    "data": [
        {
            "id": 1,
            "urun_adi": "Test Ürün",
            "urun_kodu": "TU001",
            "birim": "adet",
            "satis_fiyati": 100.00,
            "stok_miktari": 50,
            "kdv_orani": 18,
            "aktif": 1
        }
    ]
}
```

#### Ürün Ekleme
- **URL**: `/api/urunler/create.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "urun_adi": "Yeni Ürün",
    "urun_kodu": "YU001",
    "birim": "adet",
    "satis_fiyati": 150.00,
    "stok_miktari": 25,
    "kdv_orani": 18,
    "aktif": 1
}
```

#### Ürün Güncelleme
- **URL**: `/api/urunler/update.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "id": 1,
    "urun_adi": "Güncellenmiş Ürün",
    "urun_kodu": "GU001",
    "birim": "adet",
    "satis_fiyati": 200.00,
    "stok_miktari": 30,
    "kdv_orani": 18,
    "aktif": 1
}
```

#### Ürün Silme
- **URL**: `/api/urunler/delete.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "id": 1
}
```

### 4. Faturalar

#### Faturalar Listesi
- **URL**: `/api/faturalar/list.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Response**:
```json
{
    "success": true,
    "message": "Faturalar listelendi",
    "data": [
        {
            "id": 1,
            "fatura_no": "F2025001",
            "fatura_tarihi": "2025-01-11",
            "fatura_tipi": "satis",
            "cari_id": 1,
            "toplam_tutar": 1180.00,
            "vade_tarihi": "2025-01-26",
            "odeme_durumu": "odenmedi",
            "odenen_tutar": 0
        }
    ]
}
```

#### Fatura Oluşturma
- **URL**: `/api/faturalar/create.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "fatura_tipi": "satis",
    "fatura_no": "F2025002",
    "fatura_tarihi": "2025-01-11",
    "cari_id": 1,
    "odeme_tipi": "nakit",
    "vade_tarihi": "2025-01-26",
    "aciklama": "Test Fatura",
    "toplam_tutar": 1180.00,
    "kalemler": [
        {
            "urun_id": 1,
            "miktar": 10,
            "birim_fiyat": 100.00,
            "kdv_orani": 18
        }
    ]
}
```

### 5. Çekler

#### Çekler Listesi
- **URL**: `/api/cekler/list.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Response**:
```json
{
    "success": true,
    "message": "Çekler listelendi",
    "data": [
        {
            "id": 1,
            "cek_tipi": "alinan",
            "cari_id": 1,
            "cari_disi_kisi": null,
            "cek_no": "123456",
            "banka_adi": "Test Bankası",
            "tutar": 1000.00,
            "vade_tarihi": "2025-02-01",
            "cek_kaynagi": "takas",
            "durum": "portfoy"
        }
    ]
}
```

#### Çek Ekleme
- **URL**: `/api/cekler/create.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "cek_tipi": "alinan",
    "cari_id": 1,
    "cari_disi_kisi": null,
    "cek_no": "789012",
    "banka_adi": "Test Bankası",
    "tutar": 2000.00,
    "vade_tarihi": "2025-03-01",
    "cek_kaynagi": "takas",
    "durum": "portfoy"
}
```

#### Çek Silme
- **URL**: `/api/cekler/delete.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "id": 1
}
```

### 6. Kasa

#### Kasa Hareketleri Listesi
- **URL**: `/api/kasa/list.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Response**:
```json
{
    "success": true,
    "message": "Kasa hareketleri listelendi",
    "data": [
        {
            "id": 1,
            "islem_tipi": "gelir",
            "tarih": "2025-01-11",
            "kategori": "Satış",
            "tutar": 1000.00,
            "odeme_yontemi": "nakit",
            "aciklama": "Test Gelir",
            "bakiye": 1000.00
        }
    ]
}
```

#### Kasa Hareketi Ekleme
- **URL**: `/api/kasa/create.php`
- **Method**: POST
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Request**:
```json
{
    "islem_tipi": "gelir",
    "tarih": "2025-01-11",
    "kategori": "Satış",
    "tutar": 500.00,
    "odeme_yontemi": "nakit",
    "aciklama": "Test Gelir"
}
```

### 7. Teklifler

#### Teklifler Listesi
- **URL**: `/api/teklifler/list.php`
- **Method**: GET
- **Headers**: `Authorization: Bearer {token}` (Flutter)
- **Response**:
```json
{
    "success": true,
    "message": "Teklifler listelendi",
    "data": [
        {
            "id": 1,
            "teklif_no": "T2025001",
            "teklif_tarihi": "2025-01-11",
            "gecerlilik_tarihi": "2025-01-26",
            "cari_id": 1,
            "toplam_tutar": 1180.00,
            "durum": "bekliyor"
        }
    ]
}
```

## Hata Kodları

### HTTP Status Kodları
- **200**: Başarılı
- **201**: Oluşturuldu
- **400**: Bad Request (Geçersiz veri)
- **401**: Unauthorized (Kimlik doğrulama hatası)
- **404**: Not Found (Kaynak bulunamadı)
- **500**: Internal Server Error (Sunucu hatası)

### Hata Response Formatı
```json
{
    "success": false,
    "message": "Hata mesajı",
    "errors": []
}
```

## Önemli Notlar

1. **Hibrit Kimlik Doğrulama**: Web paneli session kullanır, Flutter JWT token kullanır
2. **Content-Type**: Flutter `application/json`, Web paneli `application/x-www-form-urlencoded`
3. **HTTP Method Override**: IIS kısıtlamaları nedeniyle DELETE/PUT işlemleri POST ile gönderilir
4. **Firma ID**: Tüm işlemler firma bazlı çalışır
5. **Veri Güvenliği**: Tüm input'lar validate edilir ve SQL injection koruması vardır

## Test Kullanıcı Bilgileri
- **Kullanıcı Adı**: melih
- **Şifre**: melih1996
- **Firma ID**: 4
- **Rol**: admin
