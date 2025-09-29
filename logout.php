<?php
/**
 * Primew Panel - Çıkış Sayfası
 */

session_start();

// Oturumu sonlandır
session_destroy();

// Giriş sayfasına yönlendir
header('Location: login.php');
exit;
?>
