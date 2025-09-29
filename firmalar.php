<?php
/**
 * Primew Panel - Firmalar Sayfası
 * Firma yönetimi ve CRUD işlemleri
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Firmaları al
$firmalar = getFirmalar();


// Firma silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if ($db->delete('firmalar', ['id' => $_GET['id']])) {
        $success_message = "Firma başarıyla silindi!";
        header("Location: firmalar.php?success=1");
        exit;
    } else {
        $error_message = "Firma silinirken hata oluştu!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Firmalar</title>
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
            $page_title = 'Firmalar';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> İşlem başarıyla tamamlandı!
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if (isAdmin()): ?>
                <div class="action-buttons">
                    <a href="firma-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Firma Ekle
                    </a>
                </div>
                <?php endif; ?>

                <!-- Firms Grid -->
                <div class="dashboard-grid">
                    <?php foreach ($firmalar as $firma): ?>
                        <div class="dashboard-card">
                            <div class="card-header">
                                <div class="card-icon">
                                    <i class="fas fa-industry"></i>
                                </div>
                                <div class="card-title"><?php echo htmlspecialchars($firma['firma_adi']); ?></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; font-size: 14px;">
                                    <i class="fas fa-clock" style="width: 20px; color: #64748b; margin-right: 10px;"></i>
                                    <span>Son güncelleme: <?php echo date('d.m.Y H:i', strtotime($firma['son_guncelleme'])); ?></span>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <span class="status-badge status-<?php echo $firma['durum'] == 'aktif' ? 'active' : 'inactive'; ?>">
                                    <?php echo ucfirst($firma['durum']); ?>
                                </span>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="firma-duzenle.php?id=<?php echo $firma['id']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;">
                                    <i class="fas fa-eye"></i> Görüntüle
                                </a>
                                <?php if (isAdmin()): ?>
                                <a href="firma-duzenle.php?id=<?php echo $firma['id']; ?>" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px;">
                                    <i class="fas fa-edit"></i> Düzenle
                                </a>
                                <a href="firma-komisyon.php?firma_id=<?php echo $firma['id']; ?>" class="btn btn-info" style="padding: 8px 16px; font-size: 12px; background: #0ea5e9; color: white;">
                                    <i class="fas fa-percentage"></i> Komisyon
                                </a>
                                <a href="firmalar.php?action=delete&id=<?php echo $firma['id']; ?>" 
                                   class="btn btn-danger" 
                                   style="padding: 8px 16px; font-size: 12px;"
                                   onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">
                                    <i class="fas fa-trash"></i> Sil
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
