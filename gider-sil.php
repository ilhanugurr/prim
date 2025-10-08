<?php
/**
 * Primew Panel - Gider Sil
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$gider_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($gider_id > 0) {
    // Gideri fiziksel olarak sil (sadece tahsilat_id NULL olanları)
    $db->query("DELETE FROM tahsilat_maliyetler WHERE id = ? AND tahsilat_id IS NULL", [$gider_id]);
}

header('Location: kasa.php?gider_deleted=1');
exit;
?>
