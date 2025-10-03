<?php
/**
 * Primew Panel - Müşteriler Sayfası
 * Müşteri yönetimi ve CRUD işlemleri
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Müşterileri al
$musteriler = getMusteriler();

// Müşteri silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (deleteMusteri($_GET['id'])) {
        $success_message = "Müşteri başarıyla silindi!";
        header("Location: musteriler.php?success=1");
        exit;
    } else {
        $error_message = "Müşteri silinirken hata oluştu!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Müşteriler</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        /* Müşteriler sayfası için özel grid düzeni - alt alta */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Müşteriler';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Müşteriler</span>
                    </nav>
                </div>

                <!-- Messages -->
                <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                        Müşteri başarıyla silindi!
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="musteri-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Müşteri Ekle
                    </a>
                </div>

                <!-- Müşteriler Grid -->
                <div class="dashboard-grid">
                    <?php if (!empty($musteriler)): ?>
                        <?php foreach ($musteriler as $musteri): ?>
                            <div class="dashboard-card">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 20px;">
                                        <div style="display: flex; align-items: center;">
                                            <i class="fas fa-user-tie" style="width: 20px; color: #64748b; margin-right: 10px;"></i>
                                            <span style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($musteri['firma_adi']); ?></span>
                                        </div>
                                        <div style="display: flex; align-items: center; font-size: 14px; color: #64748b;">
                                            <i class="fas fa-user" style="width: 16px; margin-right: 8px;"></i>
                                            <span><?php echo htmlspecialchars($musteri['yetkili_ad_soyad']); ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <span class="status-badge status-<?php echo $musteri['durum'] == 'aktif' ? 'active' : 'inactive'; ?>">
                                            <?php echo ucfirst($musteri['durum']); ?>
                                        </span>
                                        <div style="display: flex; gap: 6px;">
                                            <a href="musteri-duzenle.php?id=<?php echo $musteri['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <a href="musteriler.php?action=delete&id=<?php echo $musteri['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 11px;" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dashboard-card">
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-user-tie" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                <h3 style="color: #64748b; margin-bottom: 8px;">Henüz müşteri bulunmuyor</h3>
                                <p style="color: #9ca3af;">İlk müşterinizi ekleyerek başlayın</p>
                                <a href="musteri-ekle.php" class="btn btn-primary" style="margin-top: 16px;">
                                    <i class="fas fa-plus"></i>
                                    Yeni Müşteri Ekle
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.style.transform = sidebar.style.transform === 'translateX(-100%)' ? 'translateX(0)' : 'translateX(-100%)';
        }
    </script>
</body>
</html>