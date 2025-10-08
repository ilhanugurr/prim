<?php
/**
 * Primew Panel - Yeni Gider Ekle
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$errors = [];
$success_message = '';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gider_tarihi = $_POST['gider_tarihi'] ?? null;
    $gider_adi = !empty($_POST['gider_adi']) ? trim($_POST['gider_adi']) : null;
    $personel_id = !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null;
    $tutar = !empty($_POST['tutar']) ? (float)$_POST['tutar'] : 0;
    $aciklama = !empty($_POST['aciklama']) ? trim($_POST['aciklama']) : null;
    
    // Validasyon
    if (!$gider_tarihi) $errors[] = "Gider tarihi zorunludur!";
    if (!$gider_adi) $errors[] = "Gider adı zorunludur!";
    if (!$personel_id) $errors[] = "Personel seçimi zorunludur!";
    if ($tutar <= 0) $errors[] = "Geçerli bir tutar giriniz!";
    
    if (empty($errors)) {
        // Gider tarihi ve personel bilgisini açıklamaya ekleyelim
        $aciklama_with_meta = "[Tarih:" . date('d.m.Y', strtotime($gider_tarihi)) . "][Personel:" . $personel_id . "] " . ($aciklama ? $aciklama : '');
        
        $data = [
            'tahsilat_id' => null, // NULL = bağımsız gider
            'maliyet_adi' => $gider_adi,
            'maliyet_aciklama' => $aciklama_with_meta,
            'maliyet_tutari' => $tutar
        ];
        
        if ($db->insert('tahsilat_maliyetler', $data)) {
            header('Location: kasa.php?gider_success=1');
            exit;
        } else {
            $errors[] = "Gider eklenirken hata oluştu!";
        }
    }
}

// Gider kategorilerini al
$kategoriler = $db->query("SELECT * FROM gider_kategorileri ORDER BY kategori_adi ASC");

// Personel listesi - Admin ise tüm personeller, değilse sadece kendisi
if (isAdmin()) {
    $personeller = $db->select('personel', ['durum' => 'aktif'], 'ad_soyad ASC');
} else {
    $personeller = $db->select('personel', ['id' => $_SESSION['user_id'], 'durum' => 'aktif'], 'ad_soyad ASC');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Gider Ekle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Yeni Gider Ekle';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="kasa.php" style="color: #3b82f6; text-decoration: none;">Kasa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Yeni Gider</span>
                    </nav>
                </div>

                <?php if (!empty($errors)): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin: 8px 0 0 20px; padding: 0;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Form -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); max-width: 800px;">
                    <form method="POST" action="gider-ekle.php">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <!-- Gider Tarihi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Gider Tarihi *</label>
                                <input type="date" name="gider_tarihi" value="<?php echo date('Y-m-d'); ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <!-- Gider Kategorisi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Gider Kategorisi *</label>
                                <select name="gider_adi" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Kategori Seçiniz</option>
                                    <?php foreach ($kategoriler as $k): ?>
                                        <option value="<?php echo htmlspecialchars($k['kategori_adi']); ?>">
                                            <?php echo htmlspecialchars($k['kategori_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div style="margin-top: 8px;">
                                    <a href="gider-kategoriler.php" style="color: #f59e0b; text-decoration: none; font-size: 13px;">
                                        <i class="fas fa-cog"></i> Kategorileri Yönet
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Personel -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Gideri Yapan *</label>
                                <select name="personel_id" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Personel Seçiniz</option>
                                    <?php foreach ($personeller as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo (!isAdmin() && $p['id'] == $_SESSION['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Tutar -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Tutar (₺) *</label>
                                <input type="number" name="tutar" step="0.01" required placeholder="0.00"
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            </div>
                        </div>
                        
                        <!-- Açıklama (Tam genişlik) -->
                        <div style="margin-top: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Açıklama</label>
                            <textarea name="aciklama" rows="3" 
                                      style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; resize: vertical;"
                                      placeholder="Gider ile ilgili detaylar..."></textarea>
                        </div>
                        
                        <!-- Butonlar -->
                        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
                            <a href="kasa.php" style="padding: 12px 24px; background: #64748b; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <button type="submit" style="padding: 12px 24px; background: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>
</body>
</html>
