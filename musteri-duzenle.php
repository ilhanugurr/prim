<?php
/**
 * Primew Panel - Müşteri Düzenleme Sayfası
 * Müşteri düzenleme formu
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

$success_message = '';
$error_message = '';

// Müşteri ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: musteriler.php');
    exit;
}

$musteri_id = (int)$_GET['id'];
$musteri = getMusteri($musteri_id);

if (!$musteri) {
    header('Location: musteriler.php');
    exit;
}

// Form işleme
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_musteri') {
    $data = [
        'firma_adi' => trim($_POST['firma_adi']),
        'yetkili_ad_soyad' => trim($_POST['yetkili_ad_soyad']),
        'telefon' => trim($_POST['telefon']),
        'email' => trim($_POST['email']),
        'website' => trim($_POST['website']),
        'adres' => trim($_POST['adres']),
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    if (empty($data['firma_adi'])) {
        $error_message = "Firma adı gereklidir!";
    } elseif (empty($data['yetkili_ad_soyad'])) {
        $error_message = "Yetkili ad soyad gereklidir!";
    } else {
        if (updateMusteri($data, $musteri_id)) {
            $success_message = "Müşteri başarıyla güncellendi!";
            // Güncellenmiş veriyi al
            $musteri = getMusteri($musteri_id);
        } else {
            $error_message = "Müşteri güncellenirken hata oluştu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Müşteri Düzenle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Müşteri Düzenle';
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
                    <span>Müşteri Düzenle</span>
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
                    <h2 class="form-title">Müşteri Bilgilerini Düzenle</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_musteri">
                        
                        <div class="form-group">
                            <label class="form-label">Firma Adı *</label>
                            <input type="text" name="firma_adi" class="form-input" value="<?php echo htmlspecialchars($musteri['firma_adi']); ?>" placeholder="Firma adını giriniz" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Yetkili Ad Soyad *</label>
                            <input type="text" name="yetkili_ad_soyad" class="form-input" value="<?php echo htmlspecialchars($musteri['yetkili_ad_soyad']); ?>" placeholder="Yetkili ad soyadını giriniz" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="telefon" class="form-input" value="<?php echo htmlspecialchars($musteri['telefon']); ?>" placeholder="Telefon numarasını giriniz">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($musteri['email']); ?>" placeholder="E-posta adresini giriniz">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Web Sitesi</label>
                            <input type="url" name="website" class="form-input" value="<?php echo htmlspecialchars($musteri['website'] ?? ''); ?>" placeholder="https://www.ornek.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-textarea" placeholder="Firma adresini giriniz"><?php echo htmlspecialchars($musteri['adres']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Durum</label>
                            <select name="durum" class="form-select">
                                <option value="aktif" <?php echo $musteri['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="pasif" <?php echo $musteri['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Müşteri Güncelle
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