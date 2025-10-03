<?php
/**
 * Primew Panel - Envanter Yönetimi
 * Envanter listesi ve yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// İzin kontrolü
if (!hasPagePermission('envanter', 'goruntuleme')) {
    header("Location: index.php");
    exit;
}

// İstatistikleri al
$stats = getStats();

// Filtreleme
$filtre_personel = isset($_GET['personel_id']) ? (int)$_GET['personel_id'] : '';
$filtre_kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$filtre_durum = isset($_GET['durum']) ? $_GET['durum'] : 'aktif';

// Envanter silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $envanter_id = (int)$_GET['id'];
    
    if ($db->delete('envanter', ['id' => $envanter_id])) {
        $success_message = "Envanter kaydı başarıyla silindi!";
    } else {
        $error_message = "Envanter kaydı silinirken hata oluştu!";
    }
}

// Envanter listesini al
$where_conditions = [];
$params = [];

if (!empty($filtre_personel)) {
    $where_conditions[] = "e.personel_id = ?";
    $params[] = $filtre_personel;
}

if (!empty($filtre_kategori)) {
    $where_conditions[] = "e.kategori = ?";
    $params[] = $filtre_kategori;
}

if (!empty($filtre_durum)) {
    $where_conditions[] = "e.durum = ?";
    $params[] = $filtre_durum;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$envanter_listesi = $db->query("
    SELECT e.*, p.ad_soyad as personel_adi, ek.renk as kategori_renk
    FROM envanter e
    LEFT JOIN personel p ON e.personel_id = p.id
    LEFT JOIN envanter_kategoriler ek ON e.kategori = ek.kategori_adi
    {$where_sql}
    ORDER BY e.olusturma_tarihi DESC
", $params);

// Personelleri al (filtre için)
$personeller = $db->select('personel', ['durum' => 'aktif'], 'ad_soyad ASC');

// Kategorileri al (filtre için)
$kategoriler_filtre = $db->query("SELECT DISTINCT kategori FROM envanter WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Envanter Yönetimi</title>
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
            $page_title = 'Envanter Yönetimi';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Envanter Yönetimi</span>
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
                    <a href="envanter-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Envanter Ekle
                    </a>
                    <a href="envanter-kategoriler.php" class="btn btn-secondary">
                        <i class="fas fa-tags"></i>
                        Kategori Yönetimi
                    </a>
                </div>

                <!-- Filtreler -->
                <div class="white-card" style="padding: 20px; margin-bottom: 20px;">
                    <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Personel</label>
                            <select name="personel_id" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                <option value="">Tüm Personeller</option>
                                <?php foreach ($personeller as $personel): ?>
                                    <option value="<?php echo $personel['id']; ?>" <?php echo $filtre_personel == $personel['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Kategori</label>
                            <select name="kategori" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                <option value="">Tüm Kategoriler</option>
                                <?php foreach ($kategoriler_filtre as $kategori): ?>
                                    <option value="<?php echo htmlspecialchars($kategori['kategori']); ?>" <?php echo $filtre_kategori == $kategori['kategori'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kategori['kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Durum</label>
                            <select name="durum" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                <option value="">Tüm Durumlar</option>
                                <option value="aktif" <?php echo $filtre_durum == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="pasif" <?php echo $filtre_durum == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-secondary" style="padding: 10px 20px; font-size: 14px;">
                                <i class="fas fa-search"></i> Filtrele
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Envanter Listesi -->
                <div class="white-card" style="padding: 30px;">
                    <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Envanter Listesi</h2>
                    
                    <?php if (!empty($envanter_listesi)): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid #e2e8f0;">
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Ürün Adı</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Kategori</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Personel</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: var(--text-primary);">Durum</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 600; color: var(--text-primary);">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($envanter_listesi as $envanter): ?>
                                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;">
                                            <td style="padding: 15px;">
                                                <div style="font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($envanter['urun_adi']); ?></div>
                                                <?php if (!empty($envanter['aciklama'])): ?>
                                                    <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;"><?php echo htmlspecialchars($envanter['aciklama']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px;">
                                                <?php if (!empty($envanter['kategori'])): ?>
                                                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 20px; background: <?php echo !empty($envanter['kategori_renk']) ? $envanter['kategori_renk'] . '15' : '#f3f4f6'; ?>; border: 1px solid <?php echo !empty($envanter['kategori_renk']) ? $envanter['kategori_renk'] . '40' : '#e5e7eb'; ?>;">
                                                        <?php if (!empty($envanter['kategori_renk'])): ?>
                                                            <div style="width: 8px; height: 8px; background: <?php echo $envanter['kategori_renk']; ?>; border-radius: 50%;"></div>
                                                        <?php endif; ?>
                                                        <span style="font-size: 13px; font-weight: 500; color: var(--text-primary);"><?php echo htmlspecialchars($envanter['kategori']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; color: var(--text-secondary);">
                                                <?php echo !empty($envanter['personel_adi']) ? htmlspecialchars($envanter['personel_adi']) : '-'; ?>
                                            </td>
                                            <td style="padding: 15px; text-align: center;">
                                                <span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; background: <?php echo $envanter['durum'] == 'aktif' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $envanter['durum'] == 'aktif' ? '#166534' : '#dc2626'; ?>;">
                                                    <?php echo ucfirst($envanter['durum']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="display: flex; gap: 6px; justify-content: center;">
                                                    <a href="envanter-duzenle.php?id=<?php echo $envanter['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <a href="envanter.php?action=delete&id=<?php echo $envanter['id']; ?>" 
                                                       class="btn btn-danger" style="padding: 6px 12px; font-size: 11px;"
                                                       onclick="return confirm('Bu envanter kaydını silmek istediğinizden emin misiniz?')">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Toplam Özet -->
                        <div style="margin-top: 20px; padding: 20px; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: #3b82f6;"><?php echo count($envanter_listesi); ?></div>
                                <div style="font-size: 14px; color: var(--text-secondary); font-weight: 500;">Toplam Envanter Kaydı</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-secondary" style="text-align: center; padding: 60px;">
                            <i class="fas fa-boxes" style="font-size: 64px; margin-bottom: 20px; color: #d1d5db;"></i>
                            <h3 class="text-primary" style="font-size: 20px; margin-bottom: 8px;">Henüz envanter kaydı bulunmuyor</h3>
                            <p style="font-size: 16px; margin-bottom: 20px;">İlk envanter kaydınızı ekleyerek başlayın.</p>
                            <a href="envanter-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                İlk Envanter Kaydını Ekle
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
