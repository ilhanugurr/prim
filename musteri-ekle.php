<?php
/**
 * Primew Panel - Yeni Müşteri Ekleme Sayfası
 * Müşteri ekleme formu
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

$success_message = '';
$error_message = '';

// Form işleme
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_musteri') {
    $data = [
        'firma_adi' => trim($_POST['firma_adi']),
        'yetkili_ad_soyad' => trim($_POST['yetkili_ad_soyad']),
        'telefon' => trim($_POST['telefon']),
        'email' => trim($_POST['email']),
        'adres' => trim($_POST['adres']),
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    if (empty($data['firma_adi'])) {
        $error_message = "Firma adı gereklidir!";
    } elseif (empty($data['yetkili_ad_soyad'])) {
        $error_message = "Yetkili ad soyad gereklidir!";
    } else {
        if (addMusteri($data)) {
            $success_message = "Müşteri başarıyla eklendi!";
            // Form verilerini temizle
            $data = [
                'firma_adi' => '',
                'yetkili_ad_soyad' => '',
                'telefon' => '',
                'email' => '',
                'adres' => '',
                'durum' => 'aktif'
            ];
        } else {
            $error_message = "Müşteri eklenirken hata oluştu!";
        }
    }
} else {
    $data = [
        'firma_adi' => '',
        'yetkili_ad_soyad' => '',
        'telefon' => '',
        'email' => '',
        'adres' => '',
        'durum' => 'aktif'
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Müşteri Ekle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="seomew-logo.png" alt="SyncMEW Logo" style="height: 52px; margin-bottom: 0;">
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Ana Sayfa</span>
                </a>
                <a href="firmalar.php" class="nav-item">
                    <i class="fas fa-industry nav-icon"></i>
                    <span class="nav-text">Firmalar</span>
                    <span class="nav-badge"><?php echo $stats['firmalar']; ?></span>
                </a>
                <a href="personel.php" class="nav-item">
                    <i class="fas fa-users nav-icon"></i>
                    <span class="nav-text">Personel</span>
                    <span class="nav-badge"><?php echo $stats['personel']; ?></span>
                </a>
                <a href="urun-hizmet.php" class="nav-item">
                    <i class="fas fa-link nav-icon"></i>
                    <span class="nav-text">Ürün / Hizmet</span>
                    <span class="nav-badge"><?php echo $stats['urun_hizmet']; ?></span>
                </a>
                <a href="satislar.php" class="nav-item">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span class="nav-text">Satışlar</span>
                </a>
                <a href="musteriler.php" class="nav-item active">
                    <i class="fas fa-user-tie nav-icon"></i>
                    <span class="nav-text">Müşteriler</span>
                    <span class="nav-badge"><?php echo $stats['musteriler']; ?></span>
                </a>
                
                <a href="hedefler.php" class="nav-item">
                    <i class="fas fa-bullseye nav-icon"></i>
                    <span class="nav-text">Hedefler</span>
                    <span class="nav-badge"><?php echo $stats['hedefler']; ?></span>
                </a>
                <a href="mail.php" class="nav-item">
                    <i class="fas fa-envelope nav-icon"></i>
                    <span class="nav-text">Mail</span>
                </a>
                <a href="checklist.php" class="nav-item">
                    <i class="fas fa-check-square nav-icon"></i>
                    <span class="nav-text">Checklist</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Yeni Müşteri Ekle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="index.php">Ana Sayfa</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="musteriler.php">Müşteriler</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Yeni Müşteri Ekle</span>
                </div>

                <!-- Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <div class="form-container">
                    <h2 class="form-title">Yeni Müşteri Bilgileri</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_musteri">
                        
                        <div class="form-group">
                            <label class="form-label">Firma Adı *</label>
                            <input type="text" name="firma_adi" class="form-input" value="<?php echo htmlspecialchars($data['firma_adi']); ?>" placeholder="Firma adını giriniz" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Yetkili Ad Soyad *</label>
                            <input type="text" name="yetkili_ad_soyad" class="form-input" value="<?php echo htmlspecialchars($data['yetkili_ad_soyad']); ?>" placeholder="Yetkili ad soyadını giriniz" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="telefon" class="form-input" value="<?php echo htmlspecialchars($data['telefon']); ?>" placeholder="Telefon numarasını giriniz">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($data['email']); ?>" placeholder="E-posta adresini giriniz">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-textarea" placeholder="Firma adresini giriniz"><?php echo htmlspecialchars($data['adres']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Durum</label>
                            <select name="durum" class="form-select">
                                <option value="aktif" <?php echo $data['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="pasif" <?php echo $data['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Müşteri Ekle
                            </button>
                            <a href="musteriler.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                        </div>
                    </form>
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