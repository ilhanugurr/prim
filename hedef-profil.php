<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş kontrolü
requireLogin();

// Personel ID kontrolü
if (!isset($_GET['personel_id']) || !is_numeric($_GET['personel_id'])) {
    header("Location: hedefler.php");
    exit;
}

$personel_id = (int)$_GET['personel_id'];

// Rol bazlı erişim kontrolü
if (isSatisci() && $_SESSION['personel_id'] != $personel_id) {
    // Satışçı sadece kendi hedeflerine erişebilir
    header("Location: hedefler.php");
    exit;
}

// Personel bilgilerini al
$personel = $db->select('personel', ['id' => $personel_id]);
if (empty($personel)) {
    header("Location: hedefler.php");
    exit;
}
$personel = $personel[0];

// Bu personelin hedeflerini al - sadece alt firmalar veya alt firması olmayan ana firmalar
$hedefler = $db->query("
    SELECT h.*, f.firma_adi 
    FROM hedefler h
    LEFT JOIN firmalar f ON h.firma_id = f.id
    WHERE h.personel_id = ?
    AND (
        -- Ana firmaya sahip olan alt firmalar
        f.ust_firma_id IS NOT NULL
        OR
        -- Ana firma ama alt firması olmayan
        (f.ust_firma_id IS NULL AND NOT EXISTS (
            SELECT 1 FROM firmalar f2 WHERE f2.ust_firma_id = f.id AND f2.durum = 'aktif'
        ))
    )
    ORDER BY h.yil DESC, h.ay ASC
", [$personel_id]);

// Yılları grupla
$yillar = [];
foreach ($hedefler as $hedef) {
    if (!isset($yillar[$hedef['yil']])) {
        $yillar[$hedef['yil']] = [];
    }
    $yillar[$hedef['yil']][] = $hedef;
}

$stats = getStats();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - <?php echo htmlspecialchars($personel['ad_soyad']); ?> Hedef Profili</title>
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
            $page_title = htmlspecialchars($personel['ad_soyad']) . ' - Hedef Profili';
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
                        <span><?php echo htmlspecialchars($personel['ad_soyad']); ?> Hedef Profili</span>
                    </nav>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="hedef-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Hedef Ekle
                    </a>
                    <a href="hedefler.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Hedefler Listesi
                    </a>
                </div>

                <!-- Personel Bilgisi -->
                <div class="personel-info-card">
                    <div class="personel-header">
                        <div class="personel-info">
                            <i class="fas fa-user"></i>
                            <h2><?php echo htmlspecialchars($personel['ad_soyad']); ?></h2>
                        </div>
                        <div class="personel-stats">
                            <span class="hedef-count"><?php echo count($hedefler); ?> Hedef</span>
                        </div>
                    </div>
                </div>

                <!-- Hedefler Listesi -->
                <div class="hedefler-container">
                    <?php if (!empty($hedefler)): ?>
                        <?php 
                        // Hedefleri yıl/ay bazında grupla
                        $hedefler_grouped = [];
                        foreach ($hedefler as $hedef) {
                            $key = $hedef['yil'] . '_' . $hedef['ay'];
                            if (!isset($hedefler_grouped[$key])) {
                                $hedefler_grouped[$key] = [
                                    'yil' => $hedef['yil'],
                                    'ay' => $hedef['ay'],
                                    'yillik_hedef' => $hedef['yillik_hedef'],
                                    'firmalar' => []
                                ];
                            }
                            $hedefler_grouped[$key]['firmalar'][] = [
                                'firma_adi' => $hedef['firma_adi'],
                                'aylik_hedef' => $hedef['aylik_hedef']
                            ];
                        }
                        
                        // Yıllara ve aylara göre sırala (sayısal)
                        uksort($hedefler_grouped, function($a, $b) {
                            $parts_a = explode('_', $a);
                            $parts_b = explode('_', $b);
                            $yil_a = (int)$parts_a[0];
                            $yil_b = (int)$parts_b[0];
                            $ay_a = (int)$parts_a[1];
                            $ay_b = (int)$parts_b[1];
                            
                            // Önce yıla göre sırala (en yeni önce)
                            if ($yil_a != $yil_b) {
                                return $yil_b - $yil_a; // DESC
                            }
                            // Aynı yılda ise aya göre sırala (en eski önce)
                            return $ay_a - $ay_b; // ASC
                        });
                        
                        $aylar = [
                            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                        ];
                        
                        foreach ($hedefler_grouped as $key => $hedef_group):
                            $toplam_aylik = array_sum(array_column($hedef_group['firmalar'], 'aylik_hedef'));
                            
                            // Bu ay için onaylı satış verilerini al
                            $satis_verileri = $db->query("
                                SELECT 
                                    SUM(CASE WHEN s.durum = 'odendi' AND s.onay_durumu = 'onaylandi' THEN s.toplam_tutar ELSE 0 END) as tamamlanan_satis,
                                    COUNT(CASE WHEN s.durum = 'odendi' AND s.onay_durumu = 'onaylandi' THEN 1 END) as tamamlanan_satis_sayisi
                                FROM satislar s
                                WHERE s.personel_id = ? 
                                AND YEAR(s.satis_tarihi) = ? 
                                AND MONTH(s.satis_tarihi) = ?
                            ", [$personel_id, $hedef_group['yil'], $hedef_group['ay']]);
                            
                            $satis_data = $satis_verileri[0] ?? ['tamamlanan_satis' => 0, 'tamamlanan_satis_sayisi' => 0];
                            $tamamlanan_satis = (float)$satis_data['tamamlanan_satis'];
                            $progress_percentage = $toplam_aylik > 0 ? min(100, ($tamamlanan_satis / $toplam_aylik) * 100) : 0;
                        ?>
                        <div class="hedef-card">
                            <div class="hedef-header">
                                <div class="hedef-info">
                                    <h3><?php echo $aylar[$hedef_group['ay']]; ?> <?php echo $hedef_group['yil']; ?></h3>
                                    <span class="hedef-total">Toplam: ₺<?php echo number_format($toplam_aylik, 0, ',', '.'); ?></span>
                                </div>
                                <div class="hedef-actions">
                                    <a href="hedef-duzenle.php?id=<?php echo $personel_id; ?>_<?php echo $hedef_group['yil']; ?>_<?php echo $hedef_group['ay']; ?>" 
                                       class="btn-edit">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="hedefler.php?action=delete&personel_id=<?php echo $personel_id; ?>&yil=<?php echo $hedef_group['yil']; ?>&ay=<?php echo $hedef_group['ay']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Bu hedefi silmek istediğinizden emin misiniz?')">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </div>
                            </div>
                            
                            <div class="firma-list">
                                <?php 
                                foreach ($hedef_group['firmalar'] as $firma):
                                    // Bu firma için onaylı satış verilerini al
                                    // Önce firma ID'sini al
                                    $firma_id = $db->query("SELECT id FROM firmalar WHERE firma_adi = ?", [$firma['firma_adi']]);
                                    $firma_id = $firma_id[0]['id'] ?? null;
                                    
                                    if ($firma_id) {
                                        // Bu firma için yapılan satışların ID'lerini al
                                        $satis_ids = $db->query("
                                            SELECT DISTINCT s.id
                                            FROM satislar s
                                            INNER JOIN satis_detay sd ON s.id = sd.satis_id
                                            WHERE s.personel_id = ? 
                                            AND YEAR(s.satis_tarihi) = ? 
                                            AND MONTH(s.satis_tarihi) = ?
                                            AND sd.firma_id = ?
                                            AND s.durum = 'odendi'
                                            AND s.onay_durumu = 'onaylandi'
                                        ", [$personel_id, $hedef_group['yil'], $hedef_group['ay'], $firma_id]);
                                        
                                        // Her satış için net tutarı hesapla
                                        $firma_net_satis = 0;
                                        foreach ($satis_ids as $satis_row) {
                                            $satis_id = $satis_row['id'];
                                            
                                            // Bu satışın toplam tutarını ve maliyetini al
                                            $satis_detay = $db->query("
                                                SELECT 
                                                    s.toplam_tutar,
                                                    COALESCE(SUM(sm.maliyet_tutari), 0) as toplam_maliyet
                                                FROM satislar s
                                                LEFT JOIN satis_maliyetler sm ON s.id = sm.satis_id
                                                WHERE s.id = ?
                                                GROUP BY s.id
                                            ", [$satis_id]);
                                            
                                            if (!empty($satis_detay)) {
                                                $toplam = (float)$satis_detay[0]['toplam_tutar'];
                                                $maliyet = (float)$satis_detay[0]['toplam_maliyet'];
                                                $firma_net_satis += ($toplam - $maliyet);
                                            }
                                        }
                                        
                                        // Eğer satis_detay'da veri yoksa, bu firma için satış yok demektir
                                        // Fallback sistemi kaldırıldı - sadece gerçek veriler kullanılacak
                                    } else {
                                        $firma_net_satis = 0;
                                    }
                                    
                                    $firma_progress = $firma['aylik_hedef'] > 0 ? ($firma_net_satis / $firma['aylik_hedef']) * 100 : 0;
                                ?>
                                <div class="firma-item">
                                    <div class="firma-header">
                                        <span class="firma-name"><?php echo htmlspecialchars($firma['firma_adi']); ?></span>
                                        <span class="firma-amount">₺<?php echo number_format($firma['aylik_hedef'], 0, ',', '.'); ?></span>
                                    </div>
                                    
                                    <!-- Firma Progress Bar -->
                                    <div class="firma-progress">
                                        <div class="progress-header">
                                            <span class="progress-title"><?php echo htmlspecialchars($firma['firma_adi']); ?> İlerlemesi</span>
                                            <span class="progress-percentage" style="color: <?php echo $firma_progress >= 100 ? '#10b981' : '#3b82f6'; ?>;">
                                                %<?php echo number_format($firma_progress, 1); ?>
                                                <?php if ($firma_progress > 100): ?>
                                                    <span style="font-size: 12px; color: #10b981;">(+<?php echo number_format($firma_progress - 100, 1); ?>%)</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar" style="width: <?php echo min(100, $firma_progress); ?>%; background: <?php echo $firma_progress >= 100 ? '#10b981' : '#3b82f6'; ?>;"></div>
                                        </div>
                                        <div class="progress-info">
                                            <span class="progress-amount">Net Satış: ₺<?php echo number_format($firma_net_satis, 0, ',', '.'); ?></span>
                                            <?php if ($firma_progress >= 100): ?>
                                                <span class="progress-amount" style="color: #10b981;">✓ Hedef Aşıldı: +₺<?php echo number_format($firma_net_satis - $firma['aylik_hedef'], 0, ',', '.'); ?></span>
                                            <?php else: ?>
                                                <span class="progress-amount">Kalan: ₺<?php echo number_format($firma['aylik_hedef'] - $firma_net_satis, 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bullseye"></i>
                            <h3>Henüz hedef eklenmemiş</h3>
                            <p>Bu personel için henüz hedef eklenmemiş.</p>
                            <a href="hedef-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Yeni Hedef Ekle
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
            sidebar.classList.toggle('collapsed');
        }
    </script>

    <style>
        .personel-info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .personel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .personel-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .personel-info i {
            font-size: 24px;
            color: #3b82f6;
        }

        .personel-info h2 {
            margin: 0;
            color: #1e293b;
            font-size: 24px;
            font-weight: 600;
        }

        .personel-stats {
            background: #f0f9ff;
            color: #0369a1;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 500;
        }

        .hedefler-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .hedef-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .hedef-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .hedef-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .hedef-info h3 {
            margin: 0;
            color: #1e293b;
            font-size: 20px;
            font-weight: 600;
        }

        .hedef-total {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-block;
        }

        .hedef-actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-edit:hover {
            background: #e5e7eb;
            color: #1f2937;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: #fecaca;
            color: #b91c1c;
        }

        .firma-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .firma-item {
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            margin-bottom: 15px;
        }

        .firma-item:hover {
            background: #f1f5f9;
            border-color: #3b82f6;
        }

        .firma-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .firma-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 16px;
        }

        .firma-amount {
            font-weight: 700;
            color: #3b82f6;
            font-size: 16px;
        }

        .firma-progress {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #94a3b8;
            margin-bottom: 25px;
        }

        .progress-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .progress-title {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .progress-percentage {
            font-weight: 700;
            color: #3b82f6;
            font-size: 14px;
        }

        .progress-bar-container {
            background: #f1f5f9;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
        }

        .progress-amount {
            font-weight: 600;
        }
    </style>
</body>
</html>
