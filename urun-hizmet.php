<?php
/**
 * Primew Panel - Ürün/Hizmet Yönetimi
 * Ürün ve hizmet listesi ve yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Ürün/Hizmet silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if ($db->delete('urun_hizmet', ['id' => $_GET['id']])) {
        $success_message = "Ürün/Hizmet başarıyla silindi!";
    } else {
        $error_message = "Ürün/Hizmet silinirken hata oluştu!";
    }
}

// Firma filtresi
$firma_filter = isset($_GET['firma_id']) && $_GET['firma_id'] !== '' ? (int)$_GET['firma_id'] : null;

// Firmaları al (filtreleme için - sadece alt firmaları veya alt firması olmayan ana firmaları)
$firmalar_query = "
    SELECT f.* 
    FROM firmalar f
    LEFT JOIN (
        SELECT DISTINCT ust_firma_id 
        FROM firmalar 
        WHERE ust_firma_id IS NOT NULL
    ) alt ON f.id = alt.ust_firma_id
    WHERE f.ust_firma_id IS NOT NULL 
       OR alt.ust_firma_id IS NULL
    ORDER BY f.firma_adi ASC
";
$firmalar = $db->query($firmalar_query);

// Ürün/Hizmetleri al (firma bilgileri ile birlikte)
$query = "
    SELECT uh.*, f.firma_adi 
    FROM urun_hizmet uh 
    LEFT JOIN firmalar f ON uh.firma_id = f.id 
";

if ($firma_filter) {
    $query .= " WHERE uh.firma_id = " . $firma_filter;
}

$query .= " ORDER BY uh.urun_adi ASC";

$urunler = $db->query($query);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Ürün / Hizmet</title>
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
            $page_title = 'Ürün / Hizmet';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Ürün / Hizmet</span>
                    </nav>
                </div>

                <!-- Filtre ve Aksiyon Butonları -->
                <div class="white-card" style="padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <form method="GET" action="urun-hizmet.php" style="display: flex; gap: 10px; align-items: center;">
                                <label style="font-weight: 600; color: var(--text-primary); white-space: nowrap;">Firma Filtresi:</label>
                                <select name="firma_id" onchange="this.form.submit()" style="flex: 1; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: var(--bg-card); cursor: pointer;">
                                    <option value="">Tüm Firmalar</option>
                                    <?php foreach ($firmalar as $firma): ?>
                                        <option value="<?php echo $firma['id']; ?>" <?php echo $firma_filter == $firma['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($firma_filter): ?>
                                    <a href="urun-hizmet.php" style="padding: 10px 16px; background: #ef4444; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; white-space: nowrap;">
                                        <i class="fas fa-times"></i> Filtreyi Temizle
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <?php if (hasPagePermission('urun-hizmet', 'ekleme')): ?>
                        <a href="urun-ekle.php" class="btn btn-primary" style="white-space: nowrap;">
                            <i class="fas fa-plus"></i>
                            Yeni Ürün/Hizmet Ekle
                        </a>
                        <?php endif; ?>
                    </div>
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


                <!-- Ürün/Hizmet Listesi -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid var(--border-color); margin-bottom: 30px;">
                    <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Ürün/Hizmet Listesi</h2>
                    
                    <?php if (!empty($urunler)): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid #e2e8f0;">
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: var(--text-primary); width: 60px;">#</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary); width: 50px;"></th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Ürün/Hizmet Adı</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Firma</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Fiyat (KDV Dahil)</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Durum</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sira = 1; foreach ($urunler as $urun): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='var(--bg-secondary)'" onmouseout="this.style.backgroundColor='transparent'">
                                            <td style="padding: 15px; text-align: center; font-weight: 600; color: var(--text-secondary); font-size: 14px;">
                                                <?php echo $sira; ?>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-box" style="color: white; font-size: 14px;"></i>
                                                </div>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($urun['urun_adi']); ?>
                                                </div>
                                                <?php if ($urun['aciklama']): ?>
                                                    <div style="font-size: 12px; color: var(--text-secondary); line-height: 1.3;">
                                                        <?php echo htmlspecialchars(substr($urun['aciklama'], 0, 80)) . (strlen($urun['aciklama']) > 80 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="font-size: 14px; color: var(--text-secondary);">
                                                    <i class="fas fa-building" style="margin-right: 5px;"></i>
                                                    <?php echo htmlspecialchars($urun['firma_adi'] ?? 'Firma Yok'); ?>
                                                </div>
                                            </td>
                                            <td style="padding: 15px;">
                                                <?php 
                                                $kdv_aktif = isset($urun['kdv_dahil']) ? $urun['kdv_dahil'] : 1;
                                                
                                                if ($kdv_aktif) {
                                                    // KDV Aktif: Fiyat üzerine KDV ekle
                                                    $kdv_hariç_fiyat = $urun['fiyat'];
                                                    $kdv_dahil_fiyat = $kdv_hariç_fiyat * 1.20;
                                                } else {
                                                    // KDV Pasif: Fiyat zaten KDV dahil
                                                    $kdv_dahil_fiyat = $urun['fiyat'];
                                                    $kdv_hariç_fiyat = $kdv_dahil_fiyat / 1.20;
                                                }
                                                ?>
                                                <?php if ($kdv_aktif): ?>
                                                    <div style="font-size: 14px; font-weight: 600; color: #10b981;">
                                                        <i class="fas fa-check-circle" style="font-size: 12px;"></i>
                                                        <?php echo number_format($kdv_dahil_fiyat, 2, ',', '.'); ?>₺
                                                    </div>
                                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                                        KDV Hariç: <?php echo number_format($kdv_hariç_fiyat, 2, ',', '.'); ?>₺
                                                    </div>
                                                    <div style="font-size: 10px; color: #10b981; margin-top: 2px; font-weight: 600;">
                                                        <i class="fas fa-info-circle"></i> KDV Ekle Aktif
                                                    </div>
                                                <?php else: ?>
                                                    <div style="font-size: 14px; font-weight: 600; color: var(--text-secondary);">
                                                        <i class="fas fa-times-circle" style="font-size: 12px;"></i>
                                                        <?php echo number_format($kdv_dahil_fiyat, 2, ',', '.'); ?>₺
                                                    </div>
                                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                                        KDV Hariç: <?php echo number_format($kdv_hariç_fiyat, 2, ',', '.'); ?>₺
                                                    </div>
                                                    <div style="font-size: 10px; color: var(--text-secondary); margin-top: 2px; font-weight: 600;">
                                                        <i class="fas fa-info-circle"></i> KDV Dahil Fiyat
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px;">
                                                <span class="status-badge status-<?php echo $urun['durum'] == 'aktif' ? 'active' : 'inactive'; ?>">
                                                    <?php echo ucfirst($urun['durum']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <?php if (hasPagePermission('urun-hizmet', 'duzenleme')): ?>
                                                    <a href="urun-duzenle.php?id=<?php echo $urun['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <?php if (hasPagePermission('urun-hizmet', 'silme')): ?>
                                                    <a href="urun-hizmet.php?action=delete&id=<?php echo $urun['id']; ?>" 
                                                       class="btn btn-danger" 
                                                       style="padding: 6px 12px; font-size: 11px;"
                                                       onclick="return confirm('Bu ürün/hizmeti silmek istediğinizden emin misiniz?')">
                                                       <i class="fas fa-trash"></i> Sil
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php $sira++; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-secondary" style="text-align: center; padding: 60px;">
                            <i class="fas fa-box" style="font-size: 64px; margin-bottom: 20px; color: #d1d5db;"></i>
                            <h3 class="text-primary" style="font-size: 20px; margin-bottom: 8px;">Henüz ürün/hizmet eklenmemiş</h3>
                            <p style="font-size: 16px; margin-bottom: 20px;">Sisteme ürün/hizmet ekleyerek başlayın.</p>
                            <a href="urun-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                İlk Ürün/Hizmeti Ekle
                            </a>
                        </div>
                    <?php endif; ?>
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
