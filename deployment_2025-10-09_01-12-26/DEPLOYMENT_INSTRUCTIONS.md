# Production Deployment Talimatları

## Hedef Sunucu
- Domain: prokonstarim.com.tr
- Klasör: /onmuhasebedemo
- Veritabanı: muhasebedemo
- Kullanıcı: admin
- Şifre: zd3up16Hzmpy!

## Deployment Adımları

1. **Dosyaları Yükle**
   ```bash
   # Tüm dosyaları /onmuhasebedemo klasörüne yükle
   scp -r deployment_2025-10-09_01-12-26/* user@prokonstarim.com.tr:/path/to/onmuhasebedemo/
   ```

2. **Veritabanı Kurulumu**
   ```bash
   # Migration script'i çalıştır
   mysql -u admin -p'zd3up16Hzmpy!' muhasebedemo < database_migration.sql
   ```

3. **Dosya İzinleri**
   ```bash
   chmod 755 /onmuhasebedemo
   chmod 644 /onmuhasebedemo/*.php
   chmod 755 /onmuhasebedemo/uploads
   chmod 755 /onmuhasebedemo/backups
   chmod 755 /onmuhasebedemo/cache
   chmod 755 /onmuhasebedemo/logs
   ```

4. **Cron Job Kurulumu**
   ```bash
   # Backup için
   0 2 * * * php /path/to/onmuhasebedemo/backup_auto.php
   
   # Bildirimler için
   0 9 * * * php /path/to/onmuhasebedemo/cron_notifications.php
   ```

5. **SSL Sertifikası**
   - Let's Encrypt ile SSL kurulumu
   - HTTPS yönlendirmesi aktifleştir

6. **Test**
   - https://prokonstarim.com.tr/onmuhasebedemo/login.php
   - Admin girişi: admin / admin123
   - API test: https://prokonstarim.com.tr/onmuhasebedemo/api/flutter/auth.php

## Flutter Uygulama

### API Endpoints
- Base URL: https://prokonstarim.com.tr/onmuhasebedemo/api/flutter/
- Auth: /auth/login, /auth/logout, /auth/profile
- Dashboard: /dashboard/stats, /dashboard/charts, /dashboard/notifications

### Android Ayarları
- Package: com.prokonstarim.onmuhasebe
- Min SDK: 21
- Target SDK: 34
- Icon: mobiluygulamaiconu.png

### Firebase Ayarları
- Project ID: onmuhasebeceksenet
- Server Key: BIQVvTApg0EdvHFrH7OYs5ndE2lyD_Gvhx6NwPo13tkj2h_Wccf6Z7ttmi_EnESKw5_Ct4UooMBZmOcnyoQ55gk
- Service Account: onmuhasebeceksenet-10bff7999d8d.json

