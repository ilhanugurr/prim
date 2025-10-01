<?php
/**
 * Primew Panel - Personel Yönetimi
 * Personel listesi ve yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Personel silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $personel_id = (int)$_GET['id'];
    
    if ($db->delete('personel', ['id' => $personel_id])) {
        $success_message = "Personel başarıyla silindi!";
    } else {
        $error_message = "Personel silinirken hata oluştu!";
    }
}

// Personelleri al
$personeller = $db->select('personel', [], 'ad_soyad ASC');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Personel</title>
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
            $page_title = 'Personel';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Personel</span>
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
                <?php if (isAdmin()): ?>
                <div class="action-buttons">
                    <a href="personel-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Personel Ekle
                    </a>
                </div>
                <?php endif; ?>

                <!-- Personel Listesi -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; margin-bottom: 30px;">
                    <h2 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">Personel Listesi</h2>
                    
                    <?php if (!empty($personeller)): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($personeller as $personel): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 16px; transition: all 0.2s ease;">
                                    <!-- İkon -->
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-user" style="color: white; font-size: 16px;"></i>
                                    </div>
                                    
                                    <!-- Ad Soyad -->
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 15px; font-weight: 600; color: #1e293b;">
                                            <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                        </div>
                                        <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
                                            <i class="fas fa-at" style="margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($personel['kullanici_adi']); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Rol -->
                                    <div style="flex-shrink: 0;">
                                        <span style="display: inline-block; padding: 6px 12px; background: <?php echo $personel['rol'] == 'admin' ? '#dbeafe' : '#f3f4f6'; ?>; color: <?php echo $personel['rol'] == 'admin' ? '#1e40af' : '#4b5563'; ?>; border-radius: 6px; font-size: 13px; font-weight: 500;">
                                            <i class="fas <?php echo $personel['rol'] == 'admin' ? 'fa-crown' : 'fa-user-tag'; ?>" style="margin-right: 4px;"></i>
                                            <?php echo ucfirst($personel['rol']); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Durum -->
                                    <div style="flex-shrink: 0;">
                                        <span style="display: inline-block; padding: 6px 12px; background: <?php echo $personel['durum'] == 'aktif' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $personel['durum'] == 'aktif' ? '#166534' : '#dc2626'; ?>; border-radius: 6px; font-size: 13px; font-weight: 500;">
                                            <i class="fas fa-circle" style="font-size: 8px; margin-right: 4px;"></i>
                                            <?php echo ucfirst($personel['durum']); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Aksiyonlar -->
                                    <div style="display: flex; gap: 8px; flex-shrink: 0;">
                                        <?php if (isAdmin()): ?>
                                        <a href="personel-duzenle.php?id=<?php echo $personel['id']; ?>" 
                                           style="padding: 8px 14px; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; font-size: 13px; transition: all 0.2s ease;"
                                           title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="personel.php?action=delete&id=<?php echo $personel['id']; ?>" 
                                           style="padding: 8px 14px; background: #ef4444; color: white; border-radius: 6px; text-decoration: none; font-size: 13px; transition: all 0.2s ease;"
                                           title="Sil"
                                           onclick="return confirm('Bu personeli silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="hedef-profil.php?personel_id=<?php echo $personel['id']; ?>" 
                                           style="padding: 8px 14px; background: #10b981; color: white; border-radius: 6px; text-decoration: none; font-size: 13px; transition: all 0.2s ease;"
                                           title="Hedefler">
                                            <i class="fas fa-bullseye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 60px; color: #64748b;">
                            <i class="fas fa-users" style="font-size: 64px; margin-bottom: 20px; color: #d1d5db;"></i>
                            <h3 style="font-size: 20px; margin-bottom: 8px; color: #374151;">Henüz personel eklenmemiş</h3>
                            <p style="font-size: 16px; margin-bottom: 20px;">Sisteme personel ekleyerek başlayın.</p>
                            <a href="personel-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                İlk Personeli Ekle
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
