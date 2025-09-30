<?php
require_once 'config/database.php';
$db = Database::getInstance();

echo "<h2>Kullanıcı Tablosu Temizleme</h2>";

// Personel tablosunda olmayan kullanıcıları bul
$orphan_users = $db->query("
    SELECT k.* 
    FROM kullanicilar k
    LEFT JOIN personel p ON k.personel_id = p.id
    WHERE k.personel_id IS NOT NULL AND p.id IS NULL
");

if (!empty($orphan_users)) {
    echo "<h3>Silinecek Kullanıcılar:</h3><ul>";
    foreach ($orphan_users as $user) {
        echo "<li>ID: {$user['id']}, Kullanıcı Adı: {$user['kullanici_adi']}, Personel ID: {$user['personel_id']}</li>";
        $db->delete('kullanicilar', ['id' => $user['id']]);
    }
    echo "</ul>";
    echo "<p style='color: green;'>✅ " . count($orphan_users) . " kullanıcı silindi!</p>";
} else {
    echo "<p style='color: blue;'>✓ Silinecek kullanıcı yok.</p>";
}

// Admin kullanıcısını kontrol et
$admin_user = $db->query("SELECT * FROM kullanicilar WHERE kullanici_adi = 'admin'");
if (empty($admin_user)) {
    echo "<p style='color: orange;'>⚠️ Admin kullanıcısı bulunamadı!</p>";
} else {
    echo "<h3>Admin Kullanıcısı:</h3>";
    echo "<p>ID: {$admin_user[0]['id']}, Kullanıcı Adı: {$admin_user[0]['kullanici_adi']}, Personel ID: " . ($admin_user[0]['personel_id'] ?? 'NULL') . "</p>";
}

// Mevcut kullanıcıları listele
$all_users = $db->query("SELECT k.*, p.ad_soyad FROM kullanicilar k LEFT JOIN personel p ON k.personel_id = p.id ORDER BY k.id");
echo "<h3>Tüm Kullanıcılar:</h3><table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Kullanıcı Adı</th><th>Personel ID</th><th>Personel Adı</th><th>Rol</th></tr>";
foreach ($all_users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['kullanici_adi']}</td>";
    echo "<td>" . ($user['personel_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($user['ad_soyad'] ?? '-') . "</td>";
    echo "<td>{$user['rol']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='personel.php'>Personel Sayfasına Git</a></p>";
?>
