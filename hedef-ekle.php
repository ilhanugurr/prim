<?php
require_once 'config/database.php';

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

$personeller = getPersonel();
$firmalar = getFirmalar();
$stats = getStats();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Hedef Ekle</title>
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
            $page_title = 'Yeni Hedef Ekle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <a href="hedefler.php" style="color: #3b82f6; text-decoration: none;">Hedefler</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <span>Yeni Hedef Ekle</span>
                    </nav>
                </div>

                <!-- Messages -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <div class="form-container">
                    <h2 class="form-title">Yeni Hedef Bilgileri</h2>
                    
                    <form method="POST" action="hedef-ekle.php">
                        <input type="hidden" name="action" value="add_hedef">
                        
                        <!-- 1. Personel Seçimi -->
                        <div class="form-group">
                            <label class="form-label">1. Personel Seçimi</label>
                            <select name="personel_id" class="form-select" required>
                                <option value="">Personel seçiniz</option>
                                <?php foreach ($personeller as $personel): ?>
                                    <option value="<?php echo $personel['id']; ?>">
                                        <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 2. Yıl Seçimi -->
                        <div class="form-group">
                            <label class="form-label">2. Yıl Seçimi</label>
                            <div class="yil-tabs">
                                <?php for ($i = date('Y') - 1; $i <= date('Y') + 2; $i++): ?>
                                    <button type="button" class="yil-tab <?php echo ($i == date('Y')) ? 'active' : ''; ?>" 
                                            onclick="selectYil(<?php echo $i; ?>)">
                                        <?php echo $i; ?>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="yil" id="selected-yil" value="<?php echo date('Y'); ?>">
                        </div>

                        <!-- 3. Ay Seçimi -->
                        <div class="form-group">
                            <label class="form-label">3. Ay Seçimi</label>
                            <div class="ay-tabs">
                                <?php 
                                $aylar = [
                                    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                                    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                                    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                                ];
                                foreach ($aylar as $ay_no => $ay_adi): 
                                ?>
                                    <button type="button" class="ay-tab <?php echo ($ay_no == date('n')) ? 'active' : ''; ?>" 
                                            onclick="selectAy(<?php echo $ay_no; ?>)">
                                        <?php echo $ay_adi; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="ay" id="selected-ay" value="<?php echo date('n'); ?>">
                        </div>

                        <!-- 4. Firma Hedefleri -->
                        <div class="form-group">
                            <label class="form-label">4. Firma Hedefleri</label>
                            <div id="firma-hedefleri">
                                <?php foreach ($firmalar as $firma): ?>
                                <div class="firma-hedef-item">
                                    <div class="firma-header">
                                        <label class="firma-label"><?php echo htmlspecialchars($firma['firma_adi']); ?></label>
                                        <input type="number" 
                                               name="firma_hedef[<?php echo $firma['id']; ?>]" 
                                               class="form-input firma-hedef-input" 
                                               placeholder="Firma hedefi (₺)" 
                                               onchange="calculateTotal()">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Toplam Aylık Hedef (₺)</label>
                            <input type="number" name="aylik_hedef" id="toplam-aylik-hedef" class="form-input" 
                                   placeholder="Otomatik hesaplanacak" readonly>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Yıllık Hedef (₺)</label>
                            <input type="number" name="yillik_hedef" class="form-input" 
                                   placeholder="Yıllık hedef tutarını girin" required>
                        </div>

                        <div class="form-actions">
                            <a href="hedefler.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Hedef Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }

        function selectYil(yil) {
            // Tüm yıl tablarını pasif yap
            document.querySelectorAll('.yil-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Seçilen yılı aktif yap
            event.target.classList.add('active');
            
            // Hidden input'a değeri yaz
            document.getElementById('selected-yil').value = yil;
        }

        function selectAy(ay) {
            // Tüm ay tablarını pasif yap
            document.querySelectorAll('.ay-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Seçilen ayı aktif yap
            event.target.classList.add('active');
            
            // Hidden input'a değeri yaz
            document.getElementById('selected-ay').value = ay;
        }

        function calculateTotal() {
            const firmaInputs = document.querySelectorAll('.firma-hedef-input');
            let total = 0;
            
            firmaInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            document.getElementById('toplam-aylik-hedef').value = total;
        }

        // Sayfa yüklendiğinde toplamı hesapla
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
    </script>

    <style>
        .yil-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .yil-tab {
            padding: 10px 20px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            color: #374151;
        }

        .yil-tab.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .ay-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .ay-tab {
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            color: #374151;
        }

        .ay-tab.active {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .firma-hedef-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }

        .firma-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .firma-label {
            font-weight: 600;
            color: #374151;
            min-width: 120px;
        }

        .firma-hedef-input {
            flex: 1;
        }
    </style>
</body>
</html>
