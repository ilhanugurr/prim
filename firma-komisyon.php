<?php
/**
 * Primew Panel - Firma Komisyon Yönetimi
 * Firma bazlı komisyon oranları yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Firma ID kontrolü
if (!isset($_GET['firma_id']) || !is_numeric($_GET['firma_id'])) {
    header("Location: firmalar.php");
    exit;
}

$firma_id = (int)$_GET['firma_id'];

// Firma bilgilerini al
$firma = $db->select('firmalar', ['id' => $firma_id]);
if (empty($firma)) {
    header("Location: firmalar.php");
    exit;
}
$firma = $firma[0];

// Komisyon ekleme işlemi
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_komisyon') {
    $data = [
        'firma_id' => $firma_id,
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
        if (addFirmaKomisyon($data)) {
            $success_message = "Komisyon oranı başarıyla eklendi!";
        } else {
            $error_message = "Komisyon eklenirken hata oluştu!";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Komisyon silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete_komisyon' && isset($_GET['komisyon_id'])) {
    if (deleteFirmaKomisyon($_GET['komisyon_id'])) {
        $success_message = "Komisyon oranı başarıyla silindi!";
    } else {
        $error_message = "Komisyon silinirken hata oluştu!";
    }
}

// Komisyonları al
$komisyonlar = getFirmaKomisyonlar($firma_id);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - <?php echo htmlspecialchars($firma['firma_adi']); ?> Komisyon</title>
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
            $page_title = htmlspecialchars($firma['firma_adi']) . ' - Komisyon Yönetimi';
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
                        <span style="color: var(--text-primary);"><?php echo htmlspecialchars($firma['firma_adi']); ?> Komisyonları</span>
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
                <div class="action-buttons">
                    <button onclick="openAddModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Komisyon Oranı Ekle
                    </button>
                    <a href="firmalar.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Firmalar Listesi
                    </a>
                </div>

                <!-- Komisyon Listesi -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid var(--border-color); margin-bottom: 30px;">
                    <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Komisyon Oranları</h2>
                    
                    <?php if (!empty($komisyonlar)): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid #e2e8f0;">
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--text-primary);">Fiyat Aralığı</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--text-primary);">Komisyon Oranı</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--text-primary);">Durum</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--text-primary);">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($komisyonlar as $komisyon): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 12px; color: var(--text-primary);">
                                                <?php echo number_format($komisyon['min_fiyat'], 2); ?>₺ - <?php echo number_format($komisyon['max_fiyat'], 2); ?>₺
                                            </td>
                                            <td style="padding: 12px; color: var(--text-primary); font-weight: 600;">
                                                %<?php echo number_format($komisyon['komisyon_orani'], 2); ?>
                                            </td>
                                            <td style="padding: 12px;">
                                                <span class="status-badge status-<?php echo $komisyon['durum'] == 'aktif' ? 'active' : 'inactive'; ?>">
                                                    <?php echo ucfirst($komisyon['durum']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px;">
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <a href="komisyon-duzenle.php?komisyon_id=<?php echo $komisyon['id']; ?>" 
                                                       class="btn btn-secondary" 
                                                       style="padding: 6px 12px; font-size: 12px;">
                                                       <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <a href="firma-komisyon.php?firma_id=<?php echo $firma_id; ?>&action=delete_komisyon&komisyon_id=<?php echo $komisyon['id']; ?>" 
                                                       class="btn btn-danger" 
                                                       style="padding: 6px 12px; font-size: 12px;"
                                                       onclick="return confirm('Bu komisyon oranını silmek istediğinizden emin misiniz?')">
                                                       <i class="fas fa-trash"></i> Sil
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-secondary" style="text-align: center; padding: 40px;">
                            <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 16px; color: #d1d5db;"></i>
                            <p style="font-size: 16px; margin-bottom: 8px;">Henüz komisyon oranı tanımlanmamış</p>
                            <p style="font-size: 14px;">Bu firma için komisyon oranları ekleyerek başlayın.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Komisyon Modal -->
    <div id="addModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 class="text-primary" style="font-size: 24px; font-weight: 600;">Yeni Komisyon Oranı Ekle</h2>
                <span onclick="closeAddModal()" style="cursor: pointer; font-size: 24px; color: var(--text-secondary);">&times;</span>
            </div>
            
            <form method="POST" action="firma-komisyon.php?firma_id=<?php echo $firma_id; ?>">
                <input type="hidden" name="action" value="add_komisyon">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Minimum Fiyat (₺) *</label>
                        <input type="number" name="min_fiyat" step="0.01" min="0" required 
                               style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Maksimum Fiyat (₺) *</label>
                        <input type="number" name="max_fiyat" step="0.01" min="0" required 
                               style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Komisyon Oranı (%) *</label>
                        <input type="number" name="komisyon_orani" step="0.01" min="0" max="100" required 
                               style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Durum</label>
                        <select name="durum" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" onclick="closeAddModal()" class="btn btn-secondary">İptal</button>
                    <button type="submit" class="btn btn-primary">Komisyon Oranı Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target == modal) {
                closeAddModal();
            }
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
