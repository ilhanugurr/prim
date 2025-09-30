<?php
require_once 'config/database.php';
$db = Database::getInstance();

echo "<h2>Personel Şifrelerini Güncelleme</h2>";

// Şeyma
$db->update('personel', ['sifre' => md5('seyma123')], ['id' => 13]);
echo "<p>✅ Şeyma şifresi güncellendi (seyma123)</p>";

// mert
$db->update('personel', ['sifre' => md5('mert123')], ['id' => 19]);
echo "<p>✅ mert şifresi güncellendi (mert123)</p>";

echo "<h3>Giriş Bilgileri:</h3>";
echo "<ul>";
echo "<li>Admin: <strong>admin</strong> / <strong>admin123123</strong></li>";
echo "<li>Şeyma: <strong>seyma</strong> / <strong>seyma123</strong></li>";
echo "<li>mert: <strong>mert</strong> / <strong>mert123</strong></li>";
echo "</ul>";

echo "<p><a href='login.php'>Giriş Sayfasına Git</a></p>";
?>
