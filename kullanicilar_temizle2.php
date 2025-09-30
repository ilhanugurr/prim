<?php
require_once 'config/database.php';
$db = Database::getInstance();

echo "<h2>Personel ID'si NULL Olan Kullanıcıları Temizleme</h2>";

// personel_id NULL olan kullanıcıları sil (admin hariç)
$deleted = $db->query("
    DELETE FROM kullanicilar 
    WHERE personel_id IS NULL 
    AND kullanici_adi != 'admin'
");

echo "<p style='color: green;'>✅ Temizlendi!</p>";

// Kalan kullanıcıları göster
$all_users = $db->query("SELECT k.*, p.ad_soyad FROM kullanicilar k LEFT JOIN personel p ON k.personel_id = p.id ORDER BY k.id");
echo "<h3>Kalan Kullanıcılar:</h3><table border='1' style='border-collapse: collapse;'>";
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

echo "<p><a href='login.php'>Giriş Sayfasına Git</a></p>";
?>
