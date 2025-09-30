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