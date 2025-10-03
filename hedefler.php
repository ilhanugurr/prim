<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş kontrolü
requireLogin();

// Hedef ekleme işlemi
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_hedef') {
    $personel_id = $_POST['personel_id'] ?? null;
    $yillik_hedef = $_POST['yillik_hedef'] ?? null;
    $yil = $_POST['yil'] ?? date('Y');
    $ay = $_POST['ay'] ?? date('n');
    $firma_hedefleri = $_POST['firma_hedef'] ?? [];
    
    if ($personel_id && $yillik_hedef && !empty($firma_hedefleri)) {
        try {
            // Önce mevcut hedefleri sil
            $db->query("DELETE FROM hedefler WHERE personel_id = ? AND yil = ? AND ay = ?", 
                      [$personel_id, $yil, $ay]);
            
            $toplam_aylik = 0;
            
            // Her firma için ayrı hedef kaydı oluştur
            foreach ($firma_hedefleri as $firma_id => $firma_hedef) {
                if (!empty($firma_hedef) && $firma_hedef > 0) {
                    $hedef_data = [
                        'personel_id' => $personel_id,
                        'firma_id' => $firma_id,
                        'aylik_hedef' => $firma_hedef,
                        'yillik_hedef' => $yillik_hedef,
                        'yil' => $yil,
                        'ay' => $ay,
                        'durum' => 'aktif'
                    ];
                    
                    addHedef($hedef_data);
                    $toplam_aylik += $firma_hedef;
                }
            }
            
            header('Location: hedefler.php?success=1');
            exit;
        } catch (Exception $e) {
            $error_message = "Hedef eklenirken bir hata oluştu: " . $e->getMessage();
        }
    } else {
        $error_message = "Lütfen tüm alanları doldurun.";
    }
}

// Hedef silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['personel_id']) && isset($_GET['yil']) && isset($_GET['ay'])) {
    $personel_id = $_GET['personel_id'];
    $yil = $_GET['yil'];
    $ay = $_GET['ay'];
    
    try {
        $db->query("DELETE FROM hedefler WHERE personel_id = ? AND yil = ? AND ay = ?", 
                  [$personel_id, $yil, $ay]);
        header('Location: hedefler.php?success=1');
        exit;
    } catch (Exception $e) {
        $error_message = "Hedef silinirken bir hata oluştu: " . $e->getMessage();
    }
}

// Personelleri al (rol bazlı filtreleme)
if (hasPagePermission('hedefler', 'goruntuleme')) {
    // Yetkili kullanıcı tüm personelleri görür
    $personeller = getPersonel();
} else {
    // Satışçı sadece kendini görür
    $personeller = $db->select('personel', ['id' => $_SESSION['personel_id']], 'ad_soyad ASC');
}

$firmalar = getFirmalar();
$stats = getStats();

// Hedefleri al (rol bazlı filtreleme)
if (hasPagePermission('hedefler', 'goruntuleme')) {
    // Yetkili kullanıcı tüm hedefleri görür
    $hedefler = getHedefler();
} else {
    // Satışçı sadece kendi hedeflerini görür
    $hedefler = $db->query("
        SELECT h.*, p.ad_soyad as personel_adi, f.firma_adi
        FROM hedefler h
        LEFT JOIN personel p ON h.personel_id = p.id
        LEFT JOIN firmalar f ON h.firma_id = f.id
        WHERE h.personel_id = ? AND h.durum = 'aktif'
        ORDER BY h.yil DESC, h.ay DESC
    ", [$_SESSION['personel_id']]);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Hedefler</title>
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
            $page_title = 'Hedefler';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <span>Hedefler</span>
                    </nav>
                </div>

                <!-- Messages -->
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
                        <i class="fas fa-check-circle"></i>
                        Hedef başarıyla silindi.
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if (hasPagePermission('hedefler', 'ekleme')): ?>
                <div class="action-buttons">
                    <a href="hedef-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Hedef Ekle
                    </a>
                </div>
                <?php endif; ?>

                <!-- Hedefleri Olan Personeller -->
                <div class="dashboard-grid">
                    <?php if (!empty($hedefler)): ?>
                        <?php 
                        // Hedefleri personel bazında grupla
                        $personel_hedefler = [];
                        foreach ($hedefler as $hedef) {
                            if (!isset($personel_hedefler[$hedef['personel_id']])) {
                                $personel_hedefler[$hedef['personel_id']] = [
                                    'personel_adi' => $hedef['personel_adi'],
                                    'hedefler' => []
                                ];
                            }
                            $personel_hedefler[$hedef['personel_id']]['hedefler'][] = $hedef;
                        }
                        
                        foreach ($personel_hedefler as $personel_id => $personel_data):
                            // Bu personelin hedeflerini yıl/ay bazında grupla
                            $yillar = [];
                            foreach ($personel_data['hedefler'] as $hedef) {
                                if (!isset($yillar[$hedef['yil']])) {
                                    $yillar[$hedef['yil']] = [];
                                }
                                if (!isset($yillar[$hedef['yil']][$hedef['ay']])) {
                                    $yillar[$hedef['yil']][$hedef['ay']] = [];
                                }
                                $yillar[$hedef['yil']][$hedef['ay']][] = $hedef;
                            }
                            
                            $aylar = [
                                1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                                5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                                9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                            ];
                        ?>
                        <div class="personel-card">
                            <div class="personel-header">
                                <div class="personel-info">
                                    <i class="fas fa-user"></i>
                                    <h3><?php echo htmlspecialchars($personel_data['personel_adi']); ?></h3>
                                </div>
                                <div class="personel-actions">
                                    <span class="hedef-count"><?php echo count($personel_data['hedefler']); ?> Hedef</span>
                                    <a href="hedef-profil.php?personel_id=<?php echo $personel_id; ?>" class="btn-edit-profile">
                                        <i class="fas fa-<?php echo hasPagePermission('hedefler', 'duzenleme') ? 'edit' : 'eye'; ?>"></i>
                                        <?php echo hasPagePermission('hedefler', 'duzenleme') ? 'Hedef Düzenle' : 'Hedefleri Görüntüle'; ?>
                                    </a>
                                </div>
                            </div>
                            
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dashboard-card" style="text-align: center; padding: 40px;">
                            <i class="fas fa-bullseye" style="font-size: 48px; color: #d1d5db; margin-bottom: 20px;"></i>
                            <h3 style="color: var(--text-secondary); margin-bottom: 10px;">Henüz hedef eklenmemiş</h3>
                            <p style="color: var(--text-muted);">Yeni hedef eklemek için yukarıdaki butonu kullanın.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }

    </script>

    <style>
        .personel-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .personel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .personel-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .personel-info i {
            font-size: 20px;
            color: #3b82f6;
        }

        .personel-info h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 600;
        }

        .personel-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hedef-count {
            background: #f0f9ff;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-edit-profile {
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-edit-profile:hover {
            background: #2563eb;
            color: white;
        }

    </style>
</body>
</html>
