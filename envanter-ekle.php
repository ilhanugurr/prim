<?php
/**
 * Primew Panel - Envanter Ekleme
 * Yeni envanter kaydı ekleme sayfası
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Admin kontrolü
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

// İstatistikleri al
$stats = getStats();

// Personelleri al
$personeller = $db->select('personel', ['durum' => 'aktif'], 'ad_soyad ASC');

// Kategorileri al
$kategoriler = $db->query("SELECT * FROM envanter_kategoriler WHERE durum = 'aktif' ORDER BY kategori_adi ASC");

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_envanter') {
    $data = [
        'urun_adi' => trim($_POST['urun_adi']),
        'aciklama' => trim($_POST['aciklama']),
        'personel_id' => !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null,
        'kategori' => trim($_POST['kategori']),
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    $errors = [];
    if (empty($data['urun_adi'])) {
        $errors[] = "Ürün adı zorunludur!";
    }
    
    if (empty($errors)) {
        if ($db->insert('envanter', $data)) {
            $success_message = "Envanter kaydı başarıyla eklendi!";
            // Formu temizle
            $data = [
                'urun_adi' => '',
                'aciklama' => '',
                'personel_id' => '',
                'kategori' => '',
                'durum' => 'aktif'
            ];
        } else {
            $error_message = "Envanter kaydı eklenirken hata oluştu!";
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
    <title>SeoMEW Prim Sistemi - Envanter Ekle</title>
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
            $page_title = 'Yeni Envanter Ekle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="envanter.php" style="color: #3b82f6; text-decoration: none;">Envanter</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Yeni Envanter Ekle</span>
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
                <div class="white-card" style="padding: 30px; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 class="text-primary" style="font-size: 24px; font-weight: 600;">Yeni Envanter Kaydı Ekle</h2>
                        <a href="envanter.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Envanter Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="envanter-ekle.php">
                        <input type="hidden" name="action" value="add_envanter">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ürün Adı *</label>
                                <input type="text" name="urun_adi" value="<?php echo isset($data['urun_adi']) ? htmlspecialchars($data['urun_adi']) : ''; ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;"
                                       placeholder="Örn: Laptop, Telefon, Masa">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Kategori</label>
                                <select name="kategori" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Kategori Seçiniz</option>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <option value="<?php echo htmlspecialchars($kategori['kategori_adi']); ?>" <?php echo (isset($data['kategori']) && $data['kategori'] == $kategori['kategori_adi']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Açıklama</label>
                            <textarea name="aciklama" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; resize: vertical;" 
                                      placeholder="Ürün hakkında detaylı açıklama"><?php echo isset($data['aciklama']) ? htmlspecialchars($data['aciklama']) : ''; ?></textarea>
                        </div>
                        
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Sorumlu Personel</label>
                                <select name="personel_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Personel Seçiniz</option>
                                    <?php foreach ($personeller as $personel): ?>
                                        <option value="<?php echo $personel['id']; ?>" <?php echo (isset($data['personel_id']) && $data['personel_id'] == $personel['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo (!isset($data['durum']) || $data['durum'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo (isset($data['durum']) && $data['durum'] == 'pasif') ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="envanter.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Envanter Kaydı Ekle</button>
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
