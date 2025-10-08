<?php
/**
 * Primew Panel - Gider Kategorileri
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

// Silme işlemi
if (isset($_GET['sil']) && !empty($_GET['sil'])) {
    $kategori_id = (int)$_GET['sil'];
    
    // Bu kategoriye ait gider var mı kontrol et
    $gider_check = $db->query("SELECT COUNT(*) as sayi FROM tahsilat_maliyetler WHERE maliyet_adi = (SELECT kategori_adi FROM gider_kategorileri WHERE id = ?) AND tahsilat_id IS NULL", [$kategori_id]);
    
    if ($gider_check && $gider_check[0]['sayi'] > 0) {
        header('Location: gider-kategoriler.php?error=kullaniliyor');
        exit;
    } else {
        $db->query("DELETE FROM gider_kategorileri WHERE id = ?", [$kategori_id]);
        header('Location: gider-kategoriler.php?success=silindi');
        exit;
    }
}

// Kategori ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategori_adi'])) {
    $kategori_adi = trim($_POST['kategori_adi']);
    
    if (!empty($kategori_adi)) {
        $db->query("INSERT INTO gider_kategorileri (kategori_adi) VALUES (?)", [$kategori_adi]);
        header('Location: gider-kategoriler.php?success=eklendi');
        exit;
    }
}

// Kategorileri al
$kategoriler = $db->query("SELECT * FROM gider_kategorileri ORDER BY kategori_adi ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Gider Kategorileri</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Gider Kategorileri';
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
                        <span style="color: var(--text-primary);">Gider Kategorileri</span>
                    </nav>
                </div>

                <?php if (isset($_GET['success'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> 
                    <?php 
                    if ($_GET['success'] == 'eklendi') echo 'Kategori başarıyla eklendi!';
                    if ($_GET['success'] == 'silindi') echo 'Kategori başarıyla silindi!';
                    ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php 
                    if ($_GET['error'] == 'kullaniliyor') echo 'Bu kategori giderlerde kullanıldığı için silinemez!';
                    ?>
                </div>
                <?php endif; ?>

                <!-- Kategori Ekleme Formu -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 30px;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text-primary);">
                        <i class="fas fa-plus-circle" style="color: #f59e0b;"></i> Yeni Kategori Ekle
                    </h3>
                    <form method="POST" style="display: flex; gap: 10px; align-items: end;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Kategori Adı</label>
                            <input type="text" name="kategori_adi" required placeholder="Örn: Kira, Elektrik, İnternet, Maaş" 
                                   style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                        </div>
                        <button type="submit" style="padding: 12px 24px; background: #f59e0b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                            <i class="fas fa-plus"></i> Ekle
                        </button>
                    </form>
                </div>

                <!-- Kategori Listesi -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text-primary);">
                        <i class="fas fa-list" style="color: #f59e0b;"></i> Kategoriler (<?php echo count($kategoriler); ?>)
                    </h3>
                    
                    <?php if (!empty($kategoriler)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($kategoriler as $kategori): ?>
                        <div style="background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 8px; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-tag" style="color: #f59e0b; font-size: 16px;"></i>
                                <span style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($kategori['kategori_adi']); ?></span>
                            </div>
                            <a href="gider-kategoriler.php?sil=<?php echo $kategori['id']; ?>" 
                               onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')"
                               style="padding: 6px 10px; background: #ef4444; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>Henüz kategori eklenmemiş. Yukarıdaki formdan yeni kategori ekleyebilirsiniz.</p>
                    </div>
                    <?php endif; ?>
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
