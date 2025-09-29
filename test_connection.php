<?php
/**
 * Veritabanı Bağlantı Testi
 * Host veritabanı bağlantısını test eder
 */

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'seomewco_prim');
define('DB_USER', 'seomewco_prim');
define('DB_PASS', '6EsXGPBckD9c8Kr4MDFW');
define('DB_CHARSET', 'utf8mb4');

echo "<h2>Veritabanı Bağlantı Testi</h2>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "<p style='color: green;'>✅ Veritabanı bağlantısı başarılı!</p>";
    
    // Tabloları kontrol et
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Mevcut tablolar:</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Kullanıcı sayısını kontrol et
    if (in_array('kullanicilar', $tables)) {
        $user_count = $pdo->query("SELECT COUNT(*) as count FROM kullanicilar")->fetch()['count'];
        echo "<p><strong>Kullanıcı sayısı:</strong> $user_count</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Veritabanı bağlantı hatası: " . $e->getMessage() . "</p>";
    
    echo "<h3>Olası Çözümler:</h3>";
    echo "<ol>";
    echo "<li><strong>Kullanıcı şifresi kontrol edin:</strong> cPanel'de MySQL kullanıcısının şifresini kontrol edin</li>";
    echo "<li><strong>Kullanıcı izinleri:</strong> Kullanıcının veritabanına erişim izni olduğundan emin olun</li>";
    echo "<li><strong>Veritabanı adı:</strong> Veritabanı adının doğru olduğundan emin olun</li>";
    echo "<li><strong>Host adresi:</strong> Bazı hostlarda 'localhost' yerine farklı host adresi gerekebilir</li>";
    echo "</ol>";
    
    echo "<h3>Host Bilgileri:</h3>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
    echo "<li><strong>Veritabanı:</strong> " . DB_NAME . "</li>";
    echo "<li><strong>Kullanıcı:</strong> " . DB_USER . "</li>";
    echo "<li><strong>Şifre:</strong> " . str_repeat('*', strlen(DB_PASS)) . "</li>";
    echo "</ul>";
}
?>
