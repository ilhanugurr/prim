<?php
require_once 'config/database.php';
$db = Database::getInstance();

echo "<h2>Kullanıcılar Tablosunu Silme</h2>";

try {
    $db->query("DROP TABLE IF EXISTS kullanicilar");
    echo "<p style='color: green;'>✅ kullanicilar tablosu başarıyla silindi!</p>";
    echo "<p>Artık sistem sadece <strong>personel</strong> tablosunu kullanıyor.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<p><a href='login.php'>Giriş Sayfasına Git</a></p>";
?>
