-- Primew Panel Veritabanı Yapısı
-- MySQL 8.0+ uyumlu

CREATE DATABASE IF NOT EXISTS primew CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE primew;

-- Firmalar tablosu
CREATE TABLE IF NOT EXISTS firmalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_adi VARCHAR(255) NOT NULL,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Personel tablosu
CREATE TABLE IF NOT EXISTS personel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'satisci') DEFAULT 'satisci',
    sifre VARCHAR(255) NOT NULL,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ürün/Hizmet tablosu
CREATE TABLE IF NOT EXISTS urun_hizmet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_adi VARCHAR(255) NOT NULL,
    aciklama TEXT,
    fiyat DECIMAL(10,2),
    firma_id INT,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (firma_id) REFERENCES firmalar(id) ON DELETE SET NULL
);

-- Yapılar tablosu
CREATE TABLE IF NOT EXISTS yapilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    yapi_adi VARCHAR(255) NOT NULL,
    aciklama TEXT,
    tip ENUM('sistem', 'modul', 'entegrasyon') DEFAULT 'sistem',
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Mail ayarları tablosu
CREATE TABLE IF NOT EXISTS mail_ayarlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255),
    smtp_port INT DEFAULT 587,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),
    from_email VARCHAR(255),
    from_name VARCHAR(255),
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Satışlar tablosu
CREATE TABLE IF NOT EXISTS satislar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    musteri_id INT,
    firma_id INT NOT NULL,
    urun_hizmet_id INT NOT NULL,
    satis_tarihi DATE NOT NULL,
    toplam_tutar DECIMAL(10,2) NOT NULL,
    indirim DECIMAL(5,2) DEFAULT 0.00,
    odeme_durumu ENUM('odendi', 'odenmedi', 'odeme_bekleniyor', 'iptal') DEFAULT 'odendi',
    onay_durumu ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede',
    onay_tarihi TIMESTAMP NULL,
    onaylayan_id INT NULL,
    aciklama TEXT,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE,
    FOREIGN KEY (musteri_id) REFERENCES musteriler(id) ON DELETE SET NULL,
    FOREIGN KEY (firma_id) REFERENCES firmalar(id) ON DELETE CASCADE,
    FOREIGN KEY (urun_hizmet_id) REFERENCES urun_hizmet(id) ON DELETE CASCADE,
    FOREIGN KEY (onaylayan_id) REFERENCES personel(id) ON DELETE SET NULL
);

-- Checklist tablosu
CREATE TABLE IF NOT EXISTS checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    aciklama TEXT,
    tamamlandi BOOLEAN DEFAULT FALSE,
    oncelik ENUM('dusuk', 'orta', 'yuksek') DEFAULT 'orta',
    son_tarih DATE,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Örnek veriler ekle
INSERT INTO firmalar (firma_adi) VALUES
('Başbuğ Otomotiv'),
('SistemYazılım'),
('Otoismail'),
('Eryaz Tekstil');

INSERT INTO personel (ad_soyad, rol, sifre) VALUES
('Ahmet Yılmaz', 'satisci', MD5('ahmet123')),
('Ayşe Demir', 'satisci', MD5('ayse123')),
('Mehmet Kaya', 'satisci', MD5('mehmet123')),
('Fatma Özkan', 'admin', MD5('fatma123'));

INSERT INTO urun_hizmet (urun_adi, aciklama, fiyat, firma_id) VALUES
('Otomotiv Yedek Parça', 'Araç yedek parçaları', 150.00, 1),
('Yazılım Geliştirme', 'Özel yazılım çözümleri', 5000.00, 2),
('Gıda Ürünleri', 'Taze gıda ürünleri', 25.50, 3),
('Tekstil Ürünleri', 'Kumaş ve giyim', 75.00, 4);

INSERT INTO yapilar (yapi_adi, aciklama, tip) VALUES
('API Entegrasyonu', 'Dış API bağlantıları', 'entegrasyon'),
('Veritabanı Yönetimi', 'MySQL veritabanı işlemleri', 'sistem'),
('Mail Sistemi', 'E-posta gönderim sistemi', 'modul');

INSERT INTO mail_ayarlari (smtp_host, smtp_port, smtp_username, from_email, from_name) VALUES
('smtp.gmail.com', 587, 'noreply@primew.com', 'noreply@primew.com', 'Primew Panel');

INSERT INTO checklist (baslik, aciklama, oncelik, son_tarih) VALUES
('Veritabanı yedekleme', 'Günlük veritabanı yedekleme işlemi', 'yuksek', '2024-12-31'),
('API testleri', 'Tüm API bağlantılarının test edilmesi', 'orta', '2024-12-25'),
('Güvenlik güncellemesi', 'Sistem güvenlik güncellemeleri', 'yuksek', '2024-12-20');

-- Müşteriler tablosu
CREATE TABLE IF NOT EXISTS musteriler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_adi VARCHAR(255) NOT NULL,
    yetkili_ad_soyad VARCHAR(255) NOT NULL,
    telefon VARCHAR(20),
    email VARCHAR(255),
    adres TEXT,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hedefler tablosu
CREATE TABLE IF NOT EXISTS hedefler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    firma_id INT NOT NULL,
    aylik_hedef DECIMAL(15,2) NOT NULL,
    yillik_hedef DECIMAL(15,2) NOT NULL,
    yil INT NOT NULL,
    ay INT NOT NULL,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personel_id) REFERENCES personel(id) ON DELETE CASCADE,
    FOREIGN KEY (firma_id) REFERENCES firmalar(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hedef (personel_id, firma_id, yil, ay)
);

-- Firma komisyon tablosu
CREATE TABLE IF NOT EXISTS firma_komisyon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_id INT NOT NULL,
    min_fiyat DECIMAL(10,2) NOT NULL,
    max_fiyat DECIMAL(10,2) NOT NULL,
    komisyon_orani DECIMAL(5,2) NOT NULL,
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    son_guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (firma_id) REFERENCES firmalar(id) ON DELETE CASCADE
);

-- Örnek müşteri verileri
INSERT INTO musteriler (firma_adi, yetkili_ad_soyad, telefon, email, adres) VALUES
('ABC Teknoloji', 'Ahmet Yılmaz', '0532 123 45 67', 'ahmet@abcteknoloji.com', 'İstanbul, Beşiktaş, Levent Mahallesi, Teknoloji Caddesi No:123'),
('XYZ Otomotiv', 'Ayşe Demir', '0533 987 65 43', 'ayse@xyzotomotiv.com', 'Ankara, Çankaya, Kızılay Mahallesi, Otomotiv Sokak No:456'),
('DEF Gıda', 'Mehmet Kaya', '0534 555 44 33', 'mehmet@defgida.com', 'İzmir, Konak, Alsancak Mahallesi, Gıda Caddesi No:789'),
('GHI Tekstil', 'Fatma Özkan', '0535 777 88 99', 'fatma@ghitekstil.com', 'Bursa, Osmangazi, Merkez Mahallesi, Tekstil Sokak No:321');

-- Hedefler örnek verileri
INSERT INTO hedefler (personel_id, firma_id, aylik_hedef, yillik_hedef, yil, ay) VALUES
(1, 1, 50000.00, 600000.00, 2024, 12),
(1, 2, 30000.00, 360000.00, 2024, 12),
(2, 1, 75000.00, 900000.00, 2024, 12),
(2, 3, 40000.00, 480000.00, 2024, 12),
(3, 2, 25000.00, 300000.00, 2024, 12);

-- Örnek komisyon verileri
INSERT INTO firma_komisyon (firma_id, min_fiyat, max_fiyat, komisyon_orani) VALUES
(1, 0.00, 1000.00, 5.00),
(1, 1000.01, 5000.00, 7.50),
(1, 5000.01, 999999.99, 10.00),
(2, 0.00, 2000.00, 6.00),
(2, 2000.01, 10000.00, 8.50),
(3, 0.00, 500.00, 3.00),
(3, 500.01, 2000.00, 5.50);
