<?php
/**
 * Primew Panel - Ödeme Sil
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$odeme_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($odeme_id > 0) {
    // Durumu iptal olarak güncelle (fiziksel silme yerine)
    $db->update('tahsilatlar', ['durum' => 'iptal'], ['id' => $odeme_id]);
}

header('Location: kasa.php?deleted=1');
exit;
?>
