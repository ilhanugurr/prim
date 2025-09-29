<?php
/**
 * Primew Panel - Firma Düzenleme Sayfası
 * Firma bilgilerini düzenleme formu
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş kontrolü
requireLogin();

// İstatistikleri al
$stats = getStats();

// Firma ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: firmalar.php");
    exit;
}

$firma_id = (int)$_GET['id'];

// Firma bilgilerini al
$firma = $db->select('firmalar', ['id' => $firma_id]);
if (empty($firma)) {
    header("Location: firmalar.php");
    exit;
}
$firma = $firma[0];

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_firma') {
    $data = [
        'firma_adi' => trim($_POST['firma_adi']),
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    $errors = [];
    if (empty($data['firma_adi'])) {
        $errors[] = "Firma adı zorunludur!";
    }
    
    
    if (empty($errors)) {
        if ($db->update('firmalar', $data, ['id' => $firma_id])) {
            $success_message = "Firma başarıyla güncellendi!";
            // Güncellenmiş veriyi al
            $firma = $db->select('firmalar', ['id' => $firma_id])[0];
        } else {
            $error_message = "Firma güncellenirken hata oluştu!";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Firma Düzenle</title>
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
            $page_title = 'Firma Düzenle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="firmalar.php" style="color: #3b82f6; text-decoration: none;">Firmalar</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;"><?php echo htmlspecialchars($firma['firma_adi']); ?> Düzenle</span>
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

                <!-- Form Card -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 style="font-size: 24px; font-weight: 600; color: #1e293b;">Firma Bilgileri</h2>
                        <div style="display: flex; gap: 10px;">
                            <a href="firmalar.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Geri Dön
                            </a>
                            <a href="firma-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Yeni Firma
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST" action="firma-duzenle.php?id=<?php echo $firma_id; ?>">
                        <input type="hidden" name="action" value="update_firma">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Firma Adı *</label>
                                <input type="text" name="firma_adi" value="<?php echo htmlspecialchars($firma['firma_adi']); ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $firma['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $firma['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Firma Bilgileri -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 15px;">Firma Detayları</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                                <div>
                                    <span style="color: #64748b;">Oluşturma Tarihi:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($firma['olusturma_tarihi'])); ?></span>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Son Güncelleme:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($firma['son_guncelleme'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="firmalar.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
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
