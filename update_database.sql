-- Primew Panel Veritabanı Güncelleme Scripti
-- Onay sistemi için gerekli sütunları ekler

USE primew;

-- Satışlar tablosuna onay sütunları ekle
ALTER TABLE satislar 
ADD COLUMN IF NOT EXISTS onay_durumu ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede' AFTER odeme_durumu;

ALTER TABLE satislar 
ADD COLUMN IF NOT EXISTS onay_tarihi TIMESTAMP NULL AFTER onay_durumu;

ALTER TABLE satislar 
ADD COLUMN IF NOT EXISTS onaylayan_id INT NULL AFTER onay_tarihi;

-- Foreign key ekle (eğer yoksa)
ALTER TABLE satislar 
ADD CONSTRAINT IF NOT EXISTS fk_satislar_onaylayan 
FOREIGN KEY (onaylayan_id) REFERENCES personel(id) ON DELETE SET NULL;

-- Mevcut satışları 'beklemede' olarak güncelle
UPDATE satislar 
SET onay_durumu = 'beklemede' 
WHERE onay_durumu IS NULL;

-- Personel tablosuna rol ve şifre sütunları ekle (eğer yoksa)
ALTER TABLE personel 
ADD COLUMN IF NOT EXISTS rol ENUM('admin', 'satisci') DEFAULT 'satisci' AFTER ad_soyad;

ALTER TABLE personel 
ADD COLUMN IF NOT EXISTS sifre VARCHAR(255) NOT NULL DEFAULT MD5('123456') AFTER rol;

-- Mevcut personel kayıtlarını güncelle
UPDATE personel 
SET rol = 'satisci' 
WHERE rol IS NULL;

UPDATE personel 
SET sifre = MD5('123456') 
WHERE sifre IS NULL OR sifre = '';

-- Admin kullanıcısı oluştur (eğer yoksa)
INSERT IGNORE INTO personel (ad_soyad, rol, sifre, durum) 
VALUES ('Uğur İlhan', 'admin', MD5('admin123'), 'aktif');

-- Satışçı kullanıcıları oluştur (eğer yoksa)
INSERT IGNORE INTO personel (ad_soyad, rol, sifre, durum) 
VALUES 
('Şeyma', 'satisci', MD5('seyma123'), 'aktif'),
('Mehmet Kaya', 'satisci', MD5('mehmet123'), 'aktif');

-- Kullanıcılar tablosunu güncelle (eğer varsa)
UPDATE kullanicilar 
SET ad_soyad = 'Uğur İlhan' 
WHERE kullanici_adi = 'admin';

UPDATE kullanicilar 
SET ad_soyad = 'Şeyma' 
WHERE kullanici_adi = 'seyma';

UPDATE kullanicilar 
SET ad_soyad = 'Mehmet Kaya' 
WHERE kullanici_adi = 'mehmet';

-- Başarı mesajı
SELECT 'Veritabanı başarıyla güncellendi!' as message;
