<?php
/**
 * Primew Panel - Firma Ekleme Sayfası
 * Yeni firma ekleme formu
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Ana firmaları al (üst firma seçimi için)
$ana_firmalar_list = $db->query("SELECT * FROM firmalar WHERE ust_firma_id IS NULL ORDER BY firma_adi ASC");

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_firma') {
    $data = [
        'firma_adi' => trim($_POST['firma_adi']),
        'ust_firma_id' => !empty($_POST['ust_firma_id']) ? (int)$_POST['ust_firma_id'] : null,
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    $errors = [];
    if (empty($data['firma_adi'])) {
        $errors[] = "Firma adı zorunludur!";
    }
    
    
    if (empty($errors)) {
        if ($db->insert('firmalar', $data)) {
            $success_message = "Firma başarıyla eklendi!";
            // Formu temizle
            $data = [
                'firma_adi' => '',
                'ust_firma_id' => null,
                'durum' => 'aktif'
            ];
        } else {
            $error_message = "Firma eklenirken hata oluştu!";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
} else {
    // Form varsayılan değerleri
    $data = [
        'firma_adi' => '',
        'ust_firma_id' => null,
        'durum' => 'aktif'
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Firma Ekle</title>
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
            $page_title = 'Yeni Firma Ekle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="firmalar.php" style="color: #3b82f6; text-decoration: none;">Firmalar</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Yeni Firma Ekle</span>
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
                <div class="white-card" style="padding: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 class="text-primary" style="font-size: 24px; font-weight: 600;">Firma Bilgileri</h2>
                        <a href="firmalar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Geri Dön
                        </a>
                    </div>
                    
                    <form method="POST" action="firma-ekle.php">
                        <input type="hidden" name="action" value="add_firma">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Firma Adı *</label>
                                <input type="text" name="firma_adi" value="<?php echo htmlspecialchars($data['firma_adi']); ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Üst Firma (Opsiyonel)</label>
                                <select name="ust_firma_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Ana Firma (Üst Firma Yok)</option>
                                    <?php foreach ($ana_firmalar_list as $ana_firma): ?>
                                        <option value="<?php echo $ana_firma['id']; ?>" <?php echo (isset($data['ust_firma_id']) && $data['ust_firma_id'] == $ana_firma['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ana_firma['firma_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                                    <i class="fas fa-info-circle"></i> Alt firma olarak eklemek için üst firma seçin
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $data['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $data['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="firmalar.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Firma Kaydet
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
