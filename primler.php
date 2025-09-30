<?php
/**
 * Primew Panel - Prim Yönetimi
 * Personel prim hesaplama ve yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/prim_hesapla.php';

// Giriş kontrolü
requireLogin();

// İstatistikleri al
$stats = getStats();

// Personelleri al (rol bazlı filtreleme)
if (isAdmin()) {
    // Admin tüm personelleri görür
    $personeller = $db->select('personel', [], 'ad_soyad ASC');
} else {
    // Satışçı sadece kendini görür
    $personeller = $db->select('personel', ['id' => $_SESSION['personel_id']], 'ad_soyad ASC');
}

// Yıl ve ay seçimi
$selected_year = isset($_GET['yil']) ? (int)$_GET['yil'] : date('Y');
$selected_month = isset($_GET['ay']) ? (int)$_GET['ay'] : date('n');

$aylar = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Primler</title>
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
            $page_title = 'Primler';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <span>Primler</span>
                    </nav>
                </div>

                <!-- Filtre Seçimi -->
                <div class="filter-section">
                    <div class="filter-card">
                        <h3>Prim Hesaplama Filtresi</h3>
                        <form method="GET" class="filter-form">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label>Yıl Seçimi</label>
                                    <select name="yil" onchange="this.form.submit()">
                                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Ay Seçimi</label>
                                    <select name="ay" onchange="this.form.submit()">
                                        <?php foreach ($aylar as $ay_no => $ay_adi): ?>
                                            <option value="<?php echo $ay_no; ?>" <?php echo ($ay_no == $selected_month) ? 'selected' : ''; ?>>
                                                <?php echo $ay_adi; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if (isAdmin()): ?>
                <div class="action-buttons">
                    <a href="personel-prim-oranlari.php" class="btn btn-primary">
                        <i class="fas fa-percentage"></i>
                        Personel Prim Oranları
                    </a>
                    <a href="hedefler.php" class="btn btn-secondary">
                        <i class="fas fa-bullseye"></i>
                        Hedefler
                    </a>
                </div>
                <?php endif; ?>

                <!-- Prim Hesaplama Tablosu -->
                <div class="primler-container">
                    <?php 
                    // Toplam prim hesaplama
                    $toplam_prim = 0;
                    foreach ($personeller as $personel) {
                        $prim_data = hesaplaAylikPrim($personel['id'], $selected_year, $selected_month, $db);
                        $toplam_prim += $prim_data['toplam_prim'];
                    }
                    ?>
                    <div class="primler-header">
                        <h2><?php echo $aylar[$selected_month]; ?> <?php echo $selected_year; ?> Prim Hesaplamaları</h2>
                        <div class="prim-summary">
                            <span class="total-prim">Toplam Prim: ₺<?php echo number_format($toplam_prim, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <div class="primler-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Personel</th>
                                    <th>Toplam Satış</th>
                                    <th>Toplam Prim</th>
                                    <th>Firma Detayları</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $toplam_prim = 0;
                                foreach ($personeller as $personel): 
                                    $prim_data = hesaplaAylikPrim($personel['id'], $selected_year, $selected_month, $db);
                                    $toplam_prim += $prim_data['toplam_prim'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="personel-info">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($personel['ad_soyad']); ?></span>
                                        </div>
                                    </td>
                                    <td class="amount">₺<?php echo number_format($prim_data['toplam_satis'], 0, ',', '.'); ?></td>
                                    <td class="prim-amount">₺<?php echo number_format($prim_data['toplam_prim'], 0, ',', '.'); ?></td>
                                    <td class="firma-detaylari">
                                        <?php if (!empty($prim_data['prim_detaylari'])): ?>
                                            <div class="firma-list">
                                                <?php foreach ($prim_data['prim_detaylari'] as $detay): ?>
                                                    <div class="firma-item">
                                                        <span class="firma-name"><?php echo htmlspecialchars($detay['firma_adi']); ?></span>
                                                        <span class="firma-satis">₺<?php echo number_format($detay['satis_tutari'], 0, ',', '.'); ?></span>
                                                        <span class="firma-prim">%<?php echo $detay['prim_orani']; ?> = ₺<?php echo number_format($detay['prim_tutari'], 0, ',', '.'); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-data">Satış verisi yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($prim_data['toplam_prim'] > 0): ?>
                                            <span class="status-badge success">Prim Kazandı</span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">Prim Yok</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Toplam Özet -->
                    <div class="prim-summary-card">
                        <div class="summary-row">
                            <span class="summary-label">Toplam Prim Dağıtımı:</span>
                            <span class="summary-amount">₺<?php echo number_format($toplam_prim, 0, ',', '.'); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Prim Kazanan Personel:</span>
                            <span class="summary-count">
                                <?php 
                                $prim_kazanan = 0;
                                foreach ($personeller as $personel) {
                                    $prim_data = hesaplaAylikPrim($personel['id'], $selected_year, $selected_month, $db);
                                    if ($prim_data['toplam_prim'] > 0) $prim_kazanan++;
                                }
                                echo $prim_kazanan;
                                ?>
                            </span>
                        </div>
                    </div>
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
        .filter-section {
            margin-bottom: 30px;
        }

        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .filter-card h3 {
            margin: 0 0 20px 0;
            color: #1e293b;
            font-size: 18px;
            font-weight: 600;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            align-items: end;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            flex: 1;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }

        .primler-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .primler-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .primler-header h2 {
            margin: 0;
            color: #1e293b;
            font-size: 20px;
            font-weight: 600;
        }

        .prim-summary {
            background: #f0f9ff;
            color: #0369a1;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .primler-table {
            overflow-x: auto;
        }

        .primler-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .primler-table th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }

        .primler-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .personel-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .personel-info i {
            color: #3b82f6;
            font-size: 16px;
        }

        .amount, .prim-amount {
            font-weight: 600;
            color: #1e293b;
        }

        .percentage {
            font-weight: 600;
            color: #3b82f6;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.inactive {
            background: #f3f4f6;
            color: #6b7280;
        }

        .prim-summary-card {
            margin-top: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: 500;
            color: #374151;
        }

        .summary-amount {
            font-weight: 700;
            color: #3b82f6;
            font-size: 18px;
        }

        .summary-count {
            font-weight: 600;
            color: #10b981;
            font-size: 16px;
        }

        .firma-detaylari {
            max-width: 300px;
        }

        .firma-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .firma-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 3px solid #3b82f6;
        }

        .firma-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 13px;
        }

        .firma-satis {
            color: #64748b;
            font-size: 12px;
        }

        .firma-prim {
            color: #3b82f6;
            font-weight: 600;
            font-size: 12px;
        }

        .no-data {
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</body>
</html>
