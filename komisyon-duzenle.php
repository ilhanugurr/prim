<?php
/**
 * Primew Panel - Komisyon Düzenleme
 * Firma komisyon oranı düzenleme sayfası
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Komisyon ID kontrolü
if (!isset($_GET['komisyon_id']) || !is_numeric($_GET['komisyon_id'])) {
    header("Location: firmalar.php");
    exit;
}

$komisyon_id = (int)$_GET['komisyon_id'];

// Komisyon bilgilerini al
$komisyon = $db->select('firma_komisyon', ['id' => $komisyon_id]);
if (empty($komisyon)) {
    header("Location: firmalar.php");
    exit;
}
$komisyon = $komisyon[0];

// Firma bilgilerini al
$firma = $db->select('firmalar', ['id' => $komisyon['firma_id']]);
if (empty($firma)) {
    header("Location: firmalar.php");
    exit;
}
$firma = $firma[0];

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_komisyon') {
    $data = [
        'min_fiyat' => (float)$_POST['min_fiyat'],
        'max_fiyat' => (float)$_POST['max_fiyat'],
        'komisyon_orani' => (float)$_POST['komisyon_orani'],
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    $errors = [];
    if ($data['min_fiyat'] < 0) {
        $errors[] = "Minimum fiyat 0'dan küçük olamaz!";
    }
    if ($data['max_fiyat'] <= $data['min_fiyat']) {
        $errors[] = "Maksimum fiyat minimum fiyattan büyük olmalıdır!";
    }
    if ($data['komisyon_orani'] < 0 || $data['komisyon_orani'] > 100) {
        $errors[] = "Komisyon oranı 0-100 arasında olmalıdır!";
    }
    
    if (empty($errors)) {
        if (updateFirmaKomisyon($data, $komisyon_id)) {
            $success_message = "Komisyon oranı başarıyla güncellendi!";
            // Güncellenmiş veriyi al
            $komisyon = $db->select('firma_komisyon', ['id' => $komisyon_id])[0];
        } else {
            $error_message = "Komisyon güncellenirken hata oluştu!";
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
    <title>SeoMEW Prim Sistemi - Komisyon Düzenle</title>
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
            $page_title = 'Komisyon Düzenle - ' . htmlspecialchars($firma['firma_adi']);
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
                        <a href="firma-komisyon.php?firma_id=<?php echo $firma['id']; ?>" style="color: #3b82f6; text-decoration: none;"><?php echo htmlspecialchars($firma['firma_adi']); ?> Komisyonları</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Komisyon Düzenle</span>
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

                <!-- Form -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 style="font-size: 24px; font-weight: 600; color: #1e293b;">Komisyon Oranı Düzenle</h2>
                        <a href="firma-komisyon.php?firma_id=<?php echo $firma['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Komisyon Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="komisyon-duzenle.php?komisyon_id=<?php echo $komisyon_id; ?>">
                        <input type="hidden" name="action" value="update_komisyon">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Minimum Fiyat (₺) *</label>
                                <input type="number" name="min_fiyat" step="0.01" min="0" value="<?php echo $komisyon['min_fiyat']; ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Maksimum Fiyat (₺) *</label>
                                <input type="number" name="max_fiyat" step="0.01" min="0" value="<?php echo $komisyon['max_fiyat']; ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Komisyon Oranı (%) *</label>
                                <input type="number" name="komisyon_orani" step="0.01" min="0" max="100" value="<?php echo $komisyon['komisyon_orani']; ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $komisyon['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $komisyon['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Mevcut Bilgiler -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 15px;">Mevcut Bilgiler</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                                <div>
                                    <span style="color: #64748b;">Firma:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo htmlspecialchars($firma['firma_adi']); ?></span>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Oluşturma Tarihi:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($komisyon['olusturma_tarihi'])); ?></span>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Son Güncelleme:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($komisyon['son_guncelleme'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="firma-komisyon.php?firma_id=<?php echo $firma['id']; ?>" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Komisyon Oranı Güncelle</button>
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
