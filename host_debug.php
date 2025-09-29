<?php
/**
 * Host Veritabanı Debug
 * Farklı bağlantı seçeneklerini test eder
 */

echo "<h2>Host Veritabanı Debug</h2>";

// Test 1: Mevcut ayarlar
echo "<h3>Test 1: Mevcut Ayarlar</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=seomewco_prim;charset=utf8mb4", "seomewco_prim", "6EsXGPBckD9c8Kr4MDFW");
    echo "<p style='color: green;'>✅ Bağlantı başarılı!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

// Test 2: Farklı host adresi
echo "<h3>Test 2: Farklı Host Adresi</h3>";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=seomewco_prim;charset=utf8mb4", "seomewco_prim", "6EsXGPBckD9c8Kr4MDFW");
    echo "<p style='color: green;'>✅ Bağlantı başarılı!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

// Test 3: Root kullanıcı ile
echo "<h3>Test 3: Root Kullanıcı</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=seomewco_prim;charset=utf8mb4", "root", "");
    echo "<p style='color: green;'>✅ Root bağlantı başarılı!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Root hata: " . $e->getMessage() . "</p>";
}

// Test 4: Veritabanı olmadan bağlantı
echo "<h3>Test 4: MySQL Sunucu Bağlantısı</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "seomewco_prim", "6EsXGPBckD9c8Kr4MDFW");
    echo "<p style='color: green;'>✅ MySQL sunucu bağlantısı başarılı!</p>";
    
    // Mevcut veritabanlarını listele
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Mevcut veritabanları:</strong></p><ul>";
    foreach ($databases as $db) {
        echo "<li>$db</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ MySQL sunucu hatası: " . $e->getMessage() . "</p>";
}

echo "<h3>Öneriler:</h3>";
echo "<ol>";
echo "<li><strong>cPanel MySQL:</strong> cPanel'de MySQL kullanıcısını ve şifresini kontrol edin</li>";
echo "<li><strong>Veritabanı izinleri:</strong> Kullanıcının veritabanına erişim izni olduğundan emin olun</li>";
echo "<li><strong>Host adresi:</strong> Bazı hostlarda 'localhost' yerine farklı adres gerekebilir</li>";
echo "<li><strong>Port:</strong> Bazı hostlarda farklı port kullanılabilir</li>";
echo "</ol>";
?>
