<?php
/**
 * Primew Panel - Envanter Kategorileri
 * Envanter kategorileri yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Admin kontrolü
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

// İstatistikleri al
$stats = getStats();

// Kategori silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $kategori_id = (int)$_GET['id'];
    
    // Bu kategoriyi kullanan envanter kayıtları var mı kontrol et
    $kullanim = $db->query("SELECT COUNT(*) as sayi FROM envanter WHERE kategori = (SELECT kategori_adi FROM envanter_kategoriler WHERE id = ?)", [$kategori_id]);
    
    if ($kullanim[0]['sayi'] > 0) {
        $error_message = "Bu kategori kullanımda olduğu için silinemez!";
    } else {
        if ($db->delete('envanter_kategoriler', ['id' => $kategori_id])) {
            $success_message = "Kategori başarıyla silindi!";
        } else {
            $error_message = "Kategori silinirken hata oluştu!";
        }
    }
}

// Kategorileri al
$kategoriler = $db->query("
    SELECT ek.*, COUNT(e.id) as kullanim_sayisi
    FROM envanter_kategoriler ek
    LEFT JOIN envanter e ON ek.kategori_adi = e.kategori
    GROUP BY ek.id
    ORDER BY ek.kategori_adi ASC
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Envanter Kategorileri</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Envanter Kategorileri';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="envanter.php" style="color: #3b82f6; text-decoration: none;">Envanter</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Kategoriler</span>
                    </nav>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="envanter-kategori-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Kategori Ekle
                    </a>
                    <a href="envanter.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Envanter Listesi
                    </a>
                </div>

                <!-- Kategoriler Listesi -->
                <div class="white-card" style="padding: 30px;">
                    <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Envanter Kategorileri</h2>
                    
                    <?php if (!empty($kategoriler)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                            <?php foreach ($kategoriler as $kategori): ?>
                                <div style="background: var(--bg-secondary); border: 2px solid <?php echo $kategori['renk']; ?>20; border-radius: 12px; padding: 20px; transition: all 0.3s ease; position: relative;">
                                    <!-- Kategori Rengi Göstergesi -->
                                    <div style="position: absolute; top: 15px; right: 15px; width: 20px; height: 20px; background: <?php echo $kategori['renk']; ?>; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    
                                    <!-- Kategori Adı -->
                                    <div style="margin-bottom: 15px;">
                                        <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin: 0 0 5px 0; padding-right: 35px;">
                                            <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                        </h3>
                                        <?php if (!empty($kategori['aciklama'])): ?>
                                            <p style="color: var(--text-secondary); font-size: 14px; margin: 0; line-height: 1.4;">
                                                <?php echo htmlspecialchars($kategori['aciklama']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Kullanım Bilgisi -->
                                    <div style="margin-bottom: 20px;">
                                        <div style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 14px;">
                                            <i class="fas fa-boxes" style="color: #3b82f6;"></i>
                                            <span><?php echo $kategori['kullanim_sayisi']; ?> envanter kaydı</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Durum ve İşlemler -->
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; background: <?php echo $kategori['durum'] == 'aktif' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $kategori['durum'] == 'aktif' ? '#166534' : '#dc2626'; ?>;">
                                            <?php echo ucfirst($kategori['durum']); ?>
                                        </span>
                                        
                                        <div style="display: flex; gap: 8px;">
                                            <a href="envanter-kategori-duzenle.php?id=<?php echo $kategori['id']; ?>" 
                                               class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <?php if ($kategori['kullanim_sayisi'] == 0): ?>
                                                <a href="envanter-kategoriler.php?action=delete&id=<?php echo $kategori['id']; ?>" 
                                                   class="btn btn-danger" style="padding: 6px 12px; font-size: 11px;"
                                                   onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </a>
                                            <?php else: ?>
                                                <span style="padding: 6px 12px; font-size: 11px; color: var(--text-secondary); background: #f3f4f6; border-radius: 6px; cursor: not-allowed;">
                                                    <i class="fas fa-lock"></i> Kullanımda
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Oluşturma Tarihi -->
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
                                        <i class="fas fa-calendar-plus" style="margin-right: 5px;"></i>
                                        <?php echo date('d.m.Y', strtotime($kategori['olusturma_tarihi'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-secondary" style="text-align: center; padding: 60px;">
                            <i class="fas fa-tags" style="font-size: 64px; margin-bottom: 20px; color: #d1d5db;"></i>
                            <h3 class="text-primary" style="font-size: 20px; margin-bottom: 8px;">Henüz kategori bulunmuyor</h3>
                            <p style="font-size: 16px; margin-bottom: 20px;">İlk kategoriyi ekleyerek başlayın.</p>
                            <a href="envanter-kategori-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                İlk Kategoriyi Ekle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
