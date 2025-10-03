<?php
/**
 * Primew Panel - Raporlar
 * Aylık satış, prim ve kar raporları
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/prim_hesapla.php';

requireLogin();
requireAdmin(); // Sadece admin görebilir

$stats = getStats();

// Yıl ve ay seçimi
$selected_yil = isset($_GET['yil']) ? (int)$_GET['yil'] : date('Y');
$selected_ay = isset($_GET['ay']) ? (int)$_GET['ay'] : date('n');

// Ay isimleri
$aylar = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

// Personelleri al
$personeller = $db->select('personel', ['rol' => 'satisci', 'durum' => 'aktif'], 'ad_soyad ASC');

// Toplam satış ve prim hesapla
$toplam_net_satis = 0;
$toplam_prim = 0;
$personel_detaylari = [];

foreach ($personeller as $personel) {
    // Bu personel için prim hesapla
    $prim_data = hesaplaAylikPrim($personel['id'], $selected_yil, $selected_ay, $db);
    
    $toplam_net_satis += $prim_data['toplam_satis'];
    $toplam_prim += $prim_data['toplam_prim'];
    
    if ($prim_data['toplam_satis'] > 0 || $prim_data['toplam_prim'] > 0) {
        $personel_detaylari[] = [
            'ad_soyad' => $personel['ad_soyad'],
            'net_satis' => $prim_data['toplam_satis'],
            'prim' => $prim_data['toplam_prim'],
            'prim_detaylari' => $prim_data['prim_detaylari']
        ];
    }
}

$sirkete_kalan = $toplam_net_satis - $toplam_prim;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Raporlar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Raporlar';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Raporlar</span>
                    </nav>
                </div>

                <!-- Filtre -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
                    <form method="GET" action="raporlar.php" style="display: flex; gap: 15px; align-items: end;">
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Yıl</label>
                            <select name="yil" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $selected_yil == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ay</label>
                            <select name="ay" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px;">
                                <?php foreach ($aylar as $ay_num => $ay_adi): ?>
                                    <option value="<?php echo $ay_num; ?>" <?php echo $selected_ay == $ay_num ? 'selected' : ''; ?>><?php echo $ay_adi; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">
                            <i class="fas fa-filter"></i> Filtrele
                        </button>
                    </form>
                </div>

                <h2 style="color: #1f2937; margin-bottom: 20px;">
                    <?php echo $aylar[$selected_ay]; ?> <?php echo $selected_yil; ?> Raporu
                </h2>

                <!-- Özet Kartlar -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                    <!-- Toplam Net Satış -->
                    <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-chart-line" style="font-size: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Toplam Net Satış</div>
                                <div style="font-size: 28px; font-weight: 700;">₺<?php echo number_format($toplam_net_satis, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Toplam Prim -->
                    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-coins" style="font-size: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Toplam Prim</div>
                                <div style="font-size: 28px; font-weight: 700;">₺<?php echo number_format($toplam_prim, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Şirkete Kalan -->
                    <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-building" style="font-size: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Şirkete Kalan</div>
                                <div style="font-size: 28px; font-weight: 700;">₺<?php echo number_format($sirkete_kalan, 0, ',', '.'); ?></div>
                                <div style="font-size: 12px; opacity: 0.8; margin-top: 4px;">
                                    (Net Satış - Toplam Prim)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personel Detayları -->
                <?php if (!empty($personel_detaylari)): ?>
                <div style="background: var(--bg-card); border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    <h3 style="color: #1f2937; margin-bottom: 20px;">Personel Bazında Detaylar</h3>
                    
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--bg-secondary); border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Personel</th>
                                <th style="padding: 12px; text-align: right; color: var(--text-secondary); font-weight: 600;">Net Satış</th>
                                <th style="padding: 12px; text-align: right; color: var(--text-secondary); font-weight: 600;">Prim</th>
                                <th style="padding: 12px; text-align: right; color: var(--text-secondary); font-weight: 600;">Prim Oranı</th>
                                <th style="padding: 12px; text-align: right; color: var(--text-secondary); font-weight: 600;">Firmalar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personel_detaylari as $detay): 
                                $prim_orani_ort = $detay['net_satis'] > 0 ? ($detay['prim'] / $detay['net_satis']) * 100 : 0;
                            ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 16px; color: #1f2937; font-weight: 600;">
                                    <?php echo htmlspecialchars($detay['ad_soyad']); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #3b82f6; font-weight: 600;">
                                    ₺<?php echo number_format($detay['net_satis'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #f59e0b; font-weight: 600;">
                                    ₺<?php echo number_format($detay['prim'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: var(--text-secondary);">
                                    %<?php echo number_format($prim_orani_ort, 1); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: var(--text-secondary);">
                                    <?php echo count($detay['prim_detaylari']); ?> firma
                                </td>
                            </tr>
                            <!-- Firma detayları -->
                            <tr>
                                <td colspan="5" style="padding: 0 16px 16px 48px;">
                                    <div style="background: var(--bg-secondary); padding: 12px; border-radius: 8px;">
                                        <?php foreach ($detay['prim_detaylari'] as $firma_detay): ?>
                                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                                            <span style="color: #475569; font-size: 14px;">
                                                <i class="fas fa-building" style="color: #94a3b8; margin-right: 8px;"></i>
                                                <?php echo htmlspecialchars($firma_detay['firma_adi']); ?>
                                            </span>
                                            <div style="display: flex; gap: 20px; font-size: 14px;">
                                                <span style="color: #3b82f6;">₺<?php echo number_format($firma_detay['satis_tutari'], 0, ',', '.'); ?></span>
                                                <span style="color: #94a3b8;">×</span>
                                                <span style="color: var(--text-secondary);">%<?php echo $firma_detay['prim_orani']; ?></span>
                                                <span style="color: #94a3b8;">=</span>
                                                <span style="color: #f59e0b; font-weight: 600;">₺<?php echo number_format($firma_detay['prim_tutari'], 0, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--bg-secondary); font-weight: 700; border-top: 2px solid #e2e8f0;">
                                <td style="padding: 16px; color: #1f2937;">TOPLAM</td>
                                <td style="padding: 16px; text-align: right; color: #3b82f6;">₺<?php echo number_format($toplam_net_satis, 0, ',', '.'); ?></td>
                                <td style="padding: 16px; text-align: right; color: #f59e0b;">₺<?php echo number_format($toplam_prim, 0, ',', '.'); ?></td>
                                <td style="padding: 16px; text-align: right; color: var(--text-secondary);">
                                    %<?php echo $toplam_net_satis > 0 ? number_format(($toplam_prim / $toplam_net_satis) * 100, 1) : 0; ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Şirkete Kalan Detayı -->
                <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3); margin-top: 30px;">
                    <h3 style="margin-bottom: 20px; font-size: 24px;">
                        <i class="fas fa-calculator"></i> Kar Hesaplama
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; font-size: 16px;">
                        <div>
                            <div style="opacity: 0.9; margin-bottom: 8px;">Toplam Net Satış</div>
                            <div style="font-size: 24px; font-weight: 700;">₺<?php echo number_format($toplam_net_satis, 0, ',', '.'); ?></div>
                        </div>
                        <div>
                            <div style="opacity: 0.9; margin-bottom: 8px;">- Toplam Prim</div>
                            <div style="font-size: 24px; font-weight: 700;">₺<?php echo number_format($toplam_prim, 0, ',', '.'); ?></div>
                        </div>
                        <div style="border-left: 2px solid rgba(255,255,255,0.3); padding-left: 20px;">
                            <div style="opacity: 0.9; margin-bottom: 8px;">= Şirkete Kalan</div>
                            <div style="font-size: 32px; font-weight: 800;">₺<?php echo number_format($sirkete_kalan, 0, ',', '.'); ?></div>
                            <div style="font-size: 14px; opacity: 0.8; margin-top: 4px;">
                                (Kar Marjı: %<?php echo $toplam_net_satis > 0 ? number_format(($sirkete_kalan / $toplam_net_satis) * 100, 1) : 0; ?>)
                            </div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <div style="background: var(--bg-card); border-radius: 12px; padding: 60px; text-align: center; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    <i class="fas fa-chart-line" style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-secondary); margin-bottom: 8px;">Veri Bulunamadı</h3>
                    <p style="color: #94a3b8;">Bu ay için satış verisi bulunmamaktadır.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
