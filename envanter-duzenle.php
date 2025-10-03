<?php
/**
 * Primew Panel - Envanter Düzenleme
 * Envanter kaydı düzenleme sayfası
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

// Envanter ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: envanter.php");
    exit;
}

$envanter_id = (int)$_GET['id'];

// Envanter bilgilerini al
$envanter = $db->select('envanter', ['id' => $envanter_id]);
if (empty($envanter)) {
    header("Location: envanter.php");
    exit;
}
$envanter = $envanter[0];

// Personelleri al
$personeller = $db->select('personel', ['durum' => 'aktif'], 'ad_soyad ASC');

// Kategorileri al
$kategoriler = $db->query("SELECT * FROM envanter_kategoriler WHERE durum = 'aktif' ORDER BY kategori_adi ASC");

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_envanter') {
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
        if ($db->update('envanter', $data, ['id' => $envanter_id])) {
            $success_message = "Envanter kaydı başarıyla güncellendi!";
            // Güncellenmiş veriyi al
            $envanter = $db->select('envanter', ['id' => $envanter_id])[0];
        } else {
            $error_message = "Envanter kaydı güncellenirken hata oluştu!";
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
    <title>SeoMEW Prim Sistemi - Envanter Düzenle</title>
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
            $page_title = 'Envanter Düzenle';
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
                        <span style="color: var(--text-primary);">Envanter Düzenle</span>
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
                        <h2 class="text-primary" style="font-size: 24px; font-weight: 600;">Envanter Kaydı Düzenle</h2>
                        <a href="envanter.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Envanter Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="envanter-duzenle.php?id=<?php echo $envanter_id; ?>">
                        <input type="hidden" name="action" value="update_envanter">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ürün Adı *</label>
                                <input type="text" name="urun_adi" value="<?php echo htmlspecialchars($envanter['urun_adi']); ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;"
                                       placeholder="Örn: Laptop, Telefon, Masa">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Kategori</label>
                                <select name="kategori" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Kategori Seçiniz</option>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <option value="<?php echo htmlspecialchars($kategori['kategori_adi']); ?>" <?php echo $envanter['kategori'] == $kategori['kategori_adi'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Açıklama</label>
                            <textarea name="aciklama" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; resize: vertical;" 
                                      placeholder="Ürün hakkında detaylı açıklama"><?php echo htmlspecialchars($envanter['aciklama']); ?></textarea>
                        </div>
                        
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Sorumlu Personel</label>
                                <select name="personel_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Personel Seçiniz</option>
                                    <?php foreach ($personeller as $personel): ?>
                                        <option value="<?php echo $personel['id']; ?>" <?php echo $envanter['personel_id'] == $personel['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $envanter['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $envanter['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Mevcut Bilgiler -->
                        <div style="background: var(--bg-secondary); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 15px;">Mevcut Bilgiler</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                                <div>
                                    <span style="color: var(--text-secondary);">Oluşturma Tarihi:</span>
                                    <span style="color: var(--text-primary); font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($envanter['olusturma_tarihi'])); ?></span>
                                </div>
                                <div>
                                    <span style="color: var(--text-secondary);">Son Güncelleme:</span>
                                    <span style="color: var(--text-primary); font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($envanter['son_guncelleme'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="envanter.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Envanter Kaydı Güncelle</button>
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
