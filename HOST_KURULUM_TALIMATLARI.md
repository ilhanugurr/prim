# 🚀 Host Kurulum Talimatları

## 📋 Yapılması Gerekenler

### 1️⃣ Veritabanı Ayarları
Host'ta `config/database.php` dosyasını şu şekilde güncelleyin:

```php
// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'seomewco_prim');
define('DB_USER', 'seomewco_prim');
define('DB_PASS', '6EsXGPBckD9c8Kr4MDFW');
define('DB_CHARSET', 'utf8mb4');
```

### 2️⃣ cPanel MySQL Kontrolü
1. **cPanel'e giriş yapın**
2. **MySQL Veritabanları** bölümüne gidin
3. **Kullanıcı adı ve şifre** doğru mu kontrol edin
4. **Veritabanı izinleri** kullanıcıya atanmış mı kontrol edin

### 3️⃣ Alternatif Host Adresleri
Bazı hostlarda `localhost` yerine şunları deneyin:
- `127.0.0.1`
- `mysql.yourdomain.com`
- `db.yourdomain.com`

### 4️⃣ Port Kontrolü
Bazı hostlarda farklı port kullanılabilir:
```php
define('DB_HOST', 'localhost:3306'); // Veya farklı port
```

### 5️⃣ Veritabanı Oluşturma
Host'ta veritabanını oluşturduktan sonra:

1. **SQL Import** yapın
2. **database.sql** dosyasını import edin
3. **Tablo yapısını** kontrol edin

### 6️⃣ Test Dosyaları
Host'a yükledikten sonra test edin:
- `test_connection.php` - Bağlantı testi
- `host_debug.php` - Debug bilgileri

## 🔧 Sorun Giderme

### ❌ "Access denied" Hatası
1. **Kullanıcı şifresi** yanlış olabilir
2. **Kullanıcı izinleri** eksik olabilir
3. **Host adresi** yanlış olabilir

### ❌ "Database not found" Hatası
1. **Veritabanı adı** yanlış olabilir
2. **Veritabanı** oluşturulmamış olabilir

### ❌ "Connection refused" Hatası
1. **Host adresi** yanlış olabilir
2. **Port** yanlış olabilir
3. **MySQL servisi** çalışmıyor olabilir

## 📞 Host Desteği
Eğer sorun devam ederse:
1. **Hosting sağlayıcınızla** iletişime geçin
2. **MySQL ayarlarını** kontrol edin
3. **cPanel MySQL** bölümünü kontrol edin

## ✅ Başarılı Kurulum Sonrası
1. **Giriş sayfası** çalışmalı
2. **Veritabanı bağlantısı** başarılı olmalı
3. **Tüm sayfalar** erişilebilir olmalı
