<?php
/**
 * Primew Panel - Kimlik Doğrulama Yardımcı Fonksiyonları
 */

// Oturum başlat (eğer başlatılmamışsa)
if (session_status() == PHP_SESSION_NONE) {
    // Session için cookie parametrelerini ayarla (Chrome için)
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_lifetime', 0);
    
    session_start();
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol et
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Kullanıcının admin olup olmadığını kontrol et
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

/**
 * Kullanıcının satışçı olup olmadığını kontrol et
 */
function isSatisci() {
    return isLoggedIn() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'satisci';
}

/**
 * Kullanıcının belirli bir sayfaya erişim yetkisi var mı kontrol et
 */
function hasPagePermission($sayfa_adi, $yetki_tipi = 'goruntuleme') {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin her zaman tam yetkiye sahip
    if ($_SESSION['rol'] === 'admin') {
        return true;
    }
    
    try {
        // Database sınıfını kullan
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance();
        
        // Kullanıcının rol ID'sini al
        $roller = $db->query("SELECT id FROM roller WHERE rol_adi = ?", [$_SESSION['rol']]);
        if (empty($roller)) {
            return false;
        }
        $rol_id = $roller[0]['id'];
        
        // Sayfa ID'sini al
        $sayfalar = $db->query("SELECT id FROM sayfalar WHERE sayfa_adi = ?", [$sayfa_adi]);
        if (empty($sayfalar)) {
            return false;
        }
        $sayfa_id = $sayfalar[0]['id'];
        
        // İzin kontrolü
        $izinler = $db->query("SELECT yetki_tipi FROM rol_sayfa_izinleri WHERE rol_id = ? AND sayfa_id = ?", [$rol_id, $sayfa_id]);
        if (empty($izinler)) {
            return false;
        }
        
        $izin_yetki = $izinler[0]['yetki_tipi'];
        
        // Yetki tipi kontrolü
        $yetki_hierarşisi = [
            'goruntuleme' => 1,
            'ekleme' => 2,
            'duzenleme' => 3,
            'silme' => 4,
            'tam_yetki' => 5
        ];
        
        $kullanici_yetki = $yetki_hierarşisi[$izin_yetki] ?? 0;
        $gerekli_yetki = $yetki_hierarşisi[$yetki_tipi] ?? 1;
        
        return $kullanici_yetki >= $gerekli_yetki;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Sadece admin kullanıcılarına erişim ver
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Sadece satışçı kullanıcılarına erişim ver
 */
function requireSatisci() {
    requireLogin();
    if (!isSatisci()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Kullanıcı bilgilerini al
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'kullanici_adi' => $_SESSION['kullanici_adi'] ?? '',
        'ad_soyad' => $_SESSION['ad_soyad'] ?? '',
        'rol' => $_SESSION['rol'] ?? '',
        'personel_id' => $_SESSION['personel_id'] ?? null,
        'personel_adi' => $_SESSION['personel_adi'] ?? ''
    ];
}

/**
 * Kullanıcının kendi personel ID'sini al
 */
function getCurrentPersonelId() {
    return $_SESSION['personel_id'] ?? null;
}
?>