<?php
/**
 * Primew Panel - Personel Düzenleme
 * Personel bilgilerini düzenleme sayfası
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

// Personel ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: personel.php");
    exit;
}

$personel_id = (int)$_GET['id'];

// Personel bilgilerini al
$personel = $db->select('personel', ['id' => $personel_id]);
if (empty($personel)) {
    header("Location: personel.php");
    exit;
}
$personel = $personel[0];

// Eğer rol alanı yoksa varsayılan değer ekle
if (!isset($personel['rol'])) {
    $personel['rol'] = 'satisci';
}
if (!isset($personel['sifre'])) {
    $personel['sifre'] = '';
}

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_personel') {
    $data = [
        'ad_soyad' => trim($_POST['ad_soyad']),
        'durum' => $_POST['durum']
    ];
    
    // Admin ise rol güncelleme
    if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') {
        $data['rol'] = $_POST['rol'];
    }
    
    // Şifre güncelleme (eğer girilmişse)
    if (!empty($_POST['yeni_sifre'])) {
        $data['sifre'] = md5($_POST['yeni_sifre']);
    }
    
    // Validasyon
    $errors = [];
    if (empty($data['ad_soyad'])) {
        $errors[] = "Ad Soyad zorunludur!";
    }
    
    if (!empty($_POST['yeni_sifre']) && strlen($_POST['yeni_sifre']) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır!";
    }
    
    if (empty($errors)) {
        if ($db->update('personel', $data, ['id' => $personel_id])) {
            // Personel güncellendikten sonra kullanıcı kaydını da güncelle
            $kullanici_data = [
                'ad_soyad' => $data['ad_soyad']
            ];
            
            // Admin ise rol güncelleme
            if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin') {
                $kullanici_data['rol'] = $data['rol'];
            }
            
            // Şifre güncelleme (eğer girilmişse)
            if (!empty($_POST['yeni_sifre'])) {
                $kullanici_data['sifre'] = $data['sifre'];
            }
            
            $db->update('kullanicilar', $kullanici_data, ['personel_id' => $personel_id]);
            
            $success_message = "Personel başarıyla güncellendi!";
            // Güncellenmiş veriyi al
            $personel = $db->select('personel', ['id' => $personel_id])[0];
        } else {
            $error_message = "Personel güncellenirken hata oluştu!";
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
    <title>SeoMEW Prim Sistemi - Personel Düzenle</title>
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
            $page_title = 'Personel Düzenle';
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
                        <span style="color: #1e293b;">Personel Düzenle</span>
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
                        <h2 style="font-size: 24px; font-weight: 600; color: #1e293b;">Personel Bilgilerini Düzenle</h2>
                        <a href="personel.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Personel Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="personel-duzenle.php?id=<?php echo $personel_id; ?>">
                        <input type="hidden" name="action" value="update_personel">
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Ad Soyad *</label>
                            <input type="text" name="ad_soyad" value="<?php echo htmlspecialchars($personel['ad_soyad']); ?>" required 
                                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                   placeholder="Örn: Ahmet Yılmaz">
                        </div>
                        
                        <?php if (isAdmin()): ?>
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Rol *</label>
                            <select name="rol" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="satisci" <?php echo (isset($personel['rol']) && $personel['rol'] == 'satisci') ? 'selected' : ''; ?>>Satışçı</option>
                                <option value="admin" <?php echo (isset($personel['rol']) && $personel['rol'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Yeni Şifre</label>
                            <input type="password" name="yeni_sifre" 
                                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                   placeholder="Yeni şifre girin (boş bırakırsanız değişmez)">
                            <small style="color: #64748b; font-size: 12px; margin-top: 5px; display: block;">En az 6 karakter olmalıdır</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $personel['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $personel['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Mevcut Bilgiler -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 15px;">Mevcut Bilgiler</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                                <div>
                                    <span style="color: #64748b;">Oluşturma Tarihi:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($personel['olusturma_tarihi'])); ?></span>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Son Güncelleme:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($personel['son_guncelleme'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="personel.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Personel Güncelle</button>
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
