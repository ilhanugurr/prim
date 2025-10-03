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

// Firmaları al (hiyerarşik olarak)
$tum_firmalar = $db->query("
    SELECT f.*, uf.firma_adi as ust_firma_adi
    FROM firmalar f
    LEFT JOIN firmalar uf ON f.ust_firma_id = uf.id
    ORDER BY COALESCE(f.ust_firma_id, f.id), f.firma_adi ASC
");

// Ana firmaları ve alt firmaları grupla
$ana_firmalar = [];
$alt_firmalar = [];

foreach ($tum_firmalar as $firma) {
    if ($firma['ust_firma_id'] === null) {
        $ana_firmalar[$firma['id']] = $firma;
        $ana_firmalar[$firma['id']]['alt_firmalar'] = [];
    } else {
        $alt_firmalar[$firma['ust_firma_id']][] = $firma;
    }
}

// Alt firmaları ana firmalara ekle
foreach ($alt_firmalar as $ust_id => $altlar) {
    if (isset($ana_firmalar[$ust_id])) {
        $ana_firmalar[$ust_id]['alt_firmalar'] = $altlar;
    }
}

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
                <?php if (hasPagePermission('firmalar', 'ekleme')): ?>
                <div class="action-buttons">
                    <a href="firma-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Firma Ekle
                    </a>
                </div>
                <?php endif; ?>

                <!-- Firms Grid (Hiyerarşik Görünüm) -->
                <div class="dashboard-grid">
                    <?php foreach ($ana_firmalar as $ana_firma): ?>
                        <!-- Ana Firma -->
                        <div class="dashboard-card <?php echo !empty($ana_firma['alt_firmalar']) ? 'main-company-card' : ''; ?>">
                            <div class="card-header">
                                <div class="card-icon <?php echo !empty($ana_firma['alt_firmalar']) ? 'main-company-icon' : ''; ?>">
                                    <i class="fas <?php echo !empty($ana_firma['alt_firmalar']) ? 'fa-building' : 'fa-industry'; ?>"></i>
                                </div>
                                <div class="card-title">
                                    <?php echo htmlspecialchars($ana_firma['firma_adi']); ?>
                                    <?php if (!empty($ana_firma['alt_firmalar'])): ?>
                                        <span style="font-size: 11px; color: #3b82f6; font-weight: 600; margin-left: 8px;">
                                            <i class="fas fa-layer-group"></i> Ana Firma
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; font-size: 14px;">
                                    <i class="fas fa-clock" style="width: 20px; color: var(--text-secondary); margin-right: 10px;"></i>
                                    <span>Son güncelleme: <?php echo date('d.m.Y H:i', strtotime($ana_firma['son_guncelleme'])); ?></span>
                                </div>
                                <?php if (!empty($ana_firma['alt_firmalar'])): ?>
                                    <div style="display: flex; align-items: center; font-size: 14px; margin-top: 8px;">
                                        <i class="fas fa-sitemap" style="width: 20px; color: #3b82f6; margin-right: 10px;"></i>
                                        <span style="color: #3b82f6; font-weight: 600;"><?php echo count($ana_firma['alt_firmalar']); ?> Alt Firma</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Alt Firmalar (Ana firmanın içinde) -->
                            <?php if (!empty($ana_firma['alt_firmalar'])): ?>
                                <div class="subsidiaries-container">
                                    <h4 style="font-size: 13px; font-weight: 600; color: #3b82f6; margin-bottom: 12px;">
                                        <i class="fas fa-sitemap"></i> Alt Firmalar
                                    </h4>
                                    <?php foreach ($ana_firma['alt_firmalar'] as $alt_firma): ?>
                                        <div class="subsidiary-item">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="fas fa-arrow-right" style="color: #3b82f6; font-size: 12px;"></i>
                                                    <span style="font-weight: 600; color: var(--text-primary); font-size: 14px;">
                                                        <?php echo htmlspecialchars($alt_firma['firma_adi']); ?>
                                                    </span>
                                                    <span class="status-badge status-<?php echo $alt_firma['durum'] == 'aktif' ? 'active' : 'inactive'; ?>" style="font-size: 10px; padding: 3px 8px;">
                                                        <?php echo ucfirst($alt_firma['durum']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                <?php if (hasPagePermission('firmalar', 'duzenleme')): ?>
                                                <a href="firma-duzenle.php?id=<?php echo $alt_firma['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </a>
                                                <a href="firma-komisyon.php?firma_id=<?php echo $alt_firma['id']; ?>" class="btn btn-info" style="padding: 6px 12px; font-size: 11px; background: #0ea5e9; color: white;">
                                                    <i class="fas fa-percentage"></i> Komisyon
                                                </a>
                                                <a href="firmalar.php?action=delete&id=<?php echo $alt_firma['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   style="padding: 6px 12px; font-size: 11px;"
                                                   onclick="return confirm('Bu alt firmayı silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <span class="status-badge status-<?php echo $ana_firma['durum'] == 'aktif' ? 'active' : 'inactive'; ?>">
                                    <?php echo ucfirst($ana_firma['durum']); ?>
                                </span>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php if (hasPagePermission('firmalar', 'duzenleme')): ?>
                                <a href="firma-duzenle.php?id=<?php echo $ana_firma['id']; ?>" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px;">
                                    <i class="fas fa-edit"></i> Düzenle
                                </a>
                                <?php if (empty($ana_firma['alt_firmalar'])): ?>
                                <a href="firma-komisyon.php?firma_id=<?php echo $ana_firma['id']; ?>" class="btn btn-info" style="padding: 8px 16px; font-size: 12px; background: #0ea5e9; color: white;">
                                    <i class="fas fa-percentage"></i> Komisyon
                                </a>
                                <?php endif; ?>
                                <a href="firmalar.php?action=delete&id=<?php echo $ana_firma['id']; ?>" 
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
