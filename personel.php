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
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                            <?php foreach ($personeller as $personel): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; transition: all 0.3s ease;">
                                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                            <i class="fas fa-user" style="color: white; font-size: 20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                                                <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">
                                                <i class="fas fa-user-tag" style="margin-right: 5px;"></i>
                                                <?php echo isset($personel['rol']) ? ucfirst($personel['rol']) : 'Satışçı'; ?>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                                Son güncelleme: <?php echo date('d.m.Y H:i', strtotime($personel['son_guncelleme'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <span class="status-badge status-<?php echo $personel['durum'] == 'aktif' ? 'active' : 'inactive'; ?>">
                                            <?php echo ucfirst($personel['durum']); ?>
                                        </span>
                                        <div style="font-size: 12px; color: #64748b;">
                                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                            Kayıtlı Personel
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <?php if (isAdmin()): ?>
                                        <a href="personel-duzenle.php?id=<?php echo $personel['id']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 12px;">
                                            <i class="fas fa-eye"></i> Görüntüle
                                        </a>
                                        <a href="personel-duzenle.php?id=<?php echo $personel['id']; ?>" class="btn btn-secondary" style="padding: 8px 16px; font-size: 12px;">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <a href="personel.php?action=delete&id=<?php echo $personel['id']; ?>" 
                                           class="btn btn-danger" 
                                           style="padding: 8px 16px; font-size: 12px;"
                                           onclick="return confirm('Bu personeli silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i> Sil
                                        </a>
                                        <?php endif; ?>
                                        <a href="hedef-profil.php?personel_id=<?php echo $personel['id']; ?>" class="btn btn-info" style="padding: 8px 16px; font-size: 12px;">
                                            <i class="fas fa-bullseye"></i> Hedefler
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
