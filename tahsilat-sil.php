<?php
/**
 * Primew Panel - Tahsilat Sil
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$tahsilat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tahsilat_id > 0) {
    // Durumu iptal olarak güncelle (fiziksel silme yerine)
    $db->update('tahsilatlar', ['durum' => 'iptal'], ['id' => $tahsilat_id]);
}

header('Location: tahsilatlar.php?deleted=1');
exit;
?>

