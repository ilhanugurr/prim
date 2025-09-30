<?php
require_once 'config/database.php';
$db = Database::getInstance();

echo "<h2>Personel Tablosuna kullanici_adi Sütunu Ekleme</h2>";

try {
    // kullanici_adi sütununu ekle
    $check = $db->query("SHOW COLUMNS FROM personel LIKE 'kullanici_adi'");
    
    if (empty($check)) {
        $db->query("ALTER TABLE personel ADD COLUMN kullanici_adi VARCHAR(100) NULL AFTER ad_soyad");
        echo "<p style='color: green;'>✅ kullanici_adi sütunu eklendi!</p>";
        
        // Mevcut personeller için kullanici_adi oluştur
        $personeller = $db->select('personel');
        foreach ($personeller as $p) {
            $kullanici_adi = strtolower(str_replace(' ', '', $p['ad_soyad']));
            $db->update('personel', ['kullanici_adi' => $kullanici_adi], ['id' => $p['id']]);
        }
        echo "<p style='color: green;'>✅ Mevcut personeller için kullanıcı adları oluşturuldu!</p>";
        
        // Unique index ekle
        $db->query("ALTER TABLE personel ADD UNIQUE KEY unique_kullanici_adi (kullanici_adi)");
        echo "<p style='color: green;'>✅ Unique index eklendi!</p>";
    } else {
        echo "<p style='color: blue;'>✓ kullanici_adi sütunu zaten var.</p>";
    }
    
    // Admin personeli ekle (eğer yoksa)
    $admin_check = $db->select('personel', ['kullanici_adi' => 'admin']);
    if (empty($admin_check)) {
        $db->insert('personel', [
            'ad_soyad' => 'Uğur İlhan',
            'kullanici_adi' => 'admin',
            'rol' => 'admin',
            'sifre' => md5('admin123123'),
            'durum' => 'aktif'
        ]);
        echo "<p style='color: green;'>✅ Admin personeli eklendi!</p>";
    } else {
        echo "<p style='color: blue;'>✓ Admin personeli zaten var.</p>";
    }
    
    // Tüm personelleri göster
    $all_personel = $db->select('personel', [], 'id ASC');
    echo "<h3>Tüm Personeller:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Ad Soyad</th><th>Kullanıcı Adı</th><th>Rol</th><th>Durum</th></tr>";
    foreach ($all_personel as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['ad_soyad']}</td>";
        echo "<td>{$p['kullanici_adi']}</td>";
        echo "<td>{$p['rol']}</td>";
        echo "<td>{$p['durum']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Giriş Bilgileri:</h3>";
    echo "<ul>";
    foreach ($all_personel as $p) {
        $sifre_ornek = strtolower(str_replace(' ', '', $p['ad_soyad'])) . '123';
        echo "<li><strong>{$p['ad_soyad']}</strong>: {$p['kullanici_adi']} / {$sifre_ornek}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<p><a href='login.php'>Giriş Sayfasına Git</a></p>";
?>
