<?php
/**
 * Primew Panel - Personel Ekleme
 * Yeni personel ekleme sayfası
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_personel') {
    $data = [
        'ad_soyad' => trim($_POST['ad_soyad']),
        'kullanici_adi' => trim($_POST['kullanici_adi']),
        'durum' => $_POST['durum'],
        'rol' => $_POST['rol'],
        'sifre' => md5($_POST['sifre'])
    ];
    
    // Validasyon
    $errors = [];
    if (empty($data['ad_soyad'])) {
        $errors[] = "Ad Soyad zorunludur!";
    }
    
    if (empty($data['kullanici_adi'])) {
        $errors[] = "Kullanıcı adı zorunludur!";
    }
    
    if (empty($_POST['sifre'])) {
        $errors[] = "Şifre zorunludur!";
    } elseif (strlen($_POST['sifre']) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır!";
    }
    
    if (empty($errors)) {
        if ($db->insert('personel', $data)) {
            $success_message = "Personel başarıyla eklendi!";
            // Formu temizle
            $data = [
                'ad_soyad' => '',
                'kullanici_adi' => '',
                'durum' => 'aktif',
                'rol' => 'satisci',
                'sifre' => ''
            ];
        } else {
            $error_message = "Personel eklenirken hata oluştu!";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
} else {
    // Form varsayılan değerleri
    $data = [
        'ad_soyad' => '',
        'kullanici_adi' => '',
        'durum' => 'aktif',
        'rol' => 'satisci',
        'sifre' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Personel Ekle</title>
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
                <a href="personel.php" class="nav-item active">
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
                <a href="musteriler.php" class="nav-item">
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
            $page_title = 'Yeni Personel Ekle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="personel.php" style="color: #3b82f6; text-decoration: none;">Personel</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Yeni Personel Ekle</span>
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
                        <h2 style="font-size: 24px; font-weight: 600; color: #1e293b;">Yeni Personel Bilgileri</h2>
                        <a href="personel.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Personel Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="personel-ekle.php">
                        <input type="hidden" name="action" value="add_personel">
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Ad Soyad *</label>
                            <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($data['ad_soyad']); ?>" required 
                                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                   placeholder="Örn: Ahmet Yılmaz">
                        </div>
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Kullanıcı Adı *</label>
                            <input type="text" name="kullanici_adi" value="<?php echo htmlspecialchars($data['kullanici_adi'] ?? ''); ?>" required 
                                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                   placeholder="Örn: ahmet (küçük harf, boşluksuz)">
                            <small style="color: #6b7280;">Giriş yaparken kullanılacak kullanıcı adı</small>
                        </div>
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Rol *</label>
                            <select name="rol" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="satisci" <?php echo $data['rol'] == 'satisci' ? 'selected' : ''; ?>>Satışçı</option>
                                <option value="admin" <?php echo $data['rol'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Şifre *</label>
                            <input type="password" name="sifre" value="<?php echo htmlspecialchars($data['sifre']); ?>" required 
                                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                   placeholder="En az 6 karakter">
                            <small style="color: #64748b; font-size: 12px; margin-top: 5px; display: block;">En az 6 karakter olmalıdır</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $data['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $data['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="personel.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Personel Kaydet
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
