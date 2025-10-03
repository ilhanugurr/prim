<?php
/**
 * Primew Panel - Ana Sayfa
 * Dashboard ve genel istatistikler
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Veritabanı karakter setini ayarla
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("SET CHARACTER SET utf8mb4");

// Giriş kontrolü
requireLogin();

// İstatistikleri al
$stats = getStats();

// Son aktiviteleri al
$recent_activities = $db->query("
    SELECT 'firma' as type, firma_adi as title, son_guncelleme as time 
    FROM firmalar 
    WHERE son_guncelleme >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'personel' as type, ad_soyad as title, son_guncelleme as time 
    FROM personel 
    WHERE son_guncelleme >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY time DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi</title>
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
            $page_title = 'Ana Sayfa';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Recent Sales -->
                <div class="recent-activity" style="margin-bottom: 40px;">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Son Satışlar
                    </h2>
                    <div class="sales-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px; max-height: 320px; overflow-y: auto;">
                        <style>
                            @media (max-width: 1200px) {
                                .sales-grid { grid-template-columns: repeat(3, 1fr) !important; }
                            }
                            @media (max-width: 900px) {
                                .sales-grid { grid-template-columns: repeat(2, 1fr) !important; }
                            }
                            @media (max-width: 600px) {
                                .sales-grid { grid-template-columns: 1fr !important; }
                            }
                        </style>
                        <?php 
                        // Son satışları al (en fazla 8 satış - 2 satır x 4 sütun)
                        $recent_sales = $db->query("
                            SELECT s.*, p.ad_soyad as personel_adi 
                            FROM satislar s 
                            LEFT JOIN personel p ON s.personel_id = p.id 
                            ORDER BY s.olusturma_tarihi DESC 
                            LIMIT 8
                        ");
                        
                        if (!empty($recent_sales)):
                            foreach ($recent_sales as $sale):
                                $durum_text = '';
                                $durum_color = '';
                                switch($sale['durum']) {
                                    case 'odendi':
                                        $durum_text = 'Ödendi';
                                        $durum_color = '#10b981';
                                        break;
                                    case 'odenmedi':
                                        $durum_text = 'Ödenmedi';
                                        $durum_color = '#ef4444';
                                        break;
                                    case 'odeme_bekleniyor':
                                        $durum_text = 'Ödeme Bekleniyor';
                                        $durum_color = '#f59e0b';
                                        break;
                                    case 'iptal':
                                        $durum_text = 'İptal';
                                        $durum_color = '#6b7280';
                                        break;
                                }
                        ?>
                        <div class="sales-card" style="background: var(--bg-card); border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; height: 140px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                                    <i class="fas fa-receipt" style="color: white; font-size: 14px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-primary); font-size: 13px;">#<?php echo $sale['id']; ?></div>
                                    <div style="font-size: 11px; color: var(--text-secondary);"><?php echo date('d.m.Y', strtotime($sale['olusturma_tarihi'])); ?></div>
                                </div>
                            </div>
                            
                            <div style="flex: 1; margin-bottom: 8px;">
                                <div style="font-weight: 500; color: var(--text-primary); font-size: 12px; margin-bottom: 3px; line-height: 1.3;">
                                    <?php echo htmlspecialchars($sale['musteri_adi'] ?: 'Müşteri bilgisi yok'); ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-secondary);">
                                    <strong><?php echo htmlspecialchars($sale['personel_adi'] ?: 'Personel bilgisi yok'); ?></strong>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 14px;">
                                    ₺<?php echo number_format($sale['toplam_tutar'], 0, ',', '.'); ?>
                                </div>
                                <div style="padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: 500; background: <?php echo $durum_color; ?>20; color: <?php echo $durum_color; ?>;">
                                    <?php echo $durum_text; ?>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <div class="sales-card" style="background: var(--bg-card); border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; grid-column: 1 / -1; text-align: center;">
                            <div style="color: #6b7280; font-size: 14px;">
                                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                                Henüz satış bulunmuyor
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions (Sadece Admin) -->
                <?php if (isAdmin()): 
                    // Son 3 ayın verilerini hazırla
                    $aylar_data = [];
                    $ay_isimleri = [
                        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                    ];
                    
                    // Son 3 ay için veri topla
                    for ($i = 0; $i < 3; $i++) {
                        $tarih = strtotime("-$i months");
                        $yil = date('Y', $tarih);
                        $ay = (int)date('n', $tarih);
                        $ay_adi = $ay_isimleri[$ay] . ' ' . $yil;
                        
                        $firma_satislari = $db->query("
                            SELECT 
                                f.firma_adi,
                                f.id as firma_id,
                                SUM(sd.toplam_fiyat) as toplam_satis,
                                COUNT(DISTINCT s.id) as satis_adedi
                            FROM satislar s
                            INNER JOIN satis_detay sd ON s.id = sd.satis_id
                            INNER JOIN firmalar f ON sd.firma_id = f.id
                            WHERE YEAR(s.satis_tarihi) = ?
                            AND MONTH(s.satis_tarihi) = ?
                            AND s.durum = 'odendi'
                            AND s.onay_durumu = 'onaylandi'
                            AND (
                                f.ust_firma_id IS NOT NULL
                                OR
                                (f.ust_firma_id IS NULL AND NOT EXISTS (
                                    SELECT 1 FROM firmalar f2 WHERE f2.ust_firma_id = f.id AND f2.durum = 'aktif'
                                ))
                            )
                            GROUP BY f.id, f.firma_adi
                            ORDER BY toplam_satis DESC
                        ", [$yil, $ay]);
                        
                        $toplam = array_sum(array_column($firma_satislari, 'toplam_satis'));
                        
                        $aylar_data[] = [
                            'ay_adi' => $ay_adi,
                            'firmalar' => $firma_satislari,
                            'toplam' => $toplam,
                            'index' => $i
                        ];
                    }
                ?>
                <div class="sales-chart-section" style="margin-bottom: 40px;">
                    <h2 class="section-title">
                        <i class="fas fa-chart-pie"></i>
                        Son 3 Ay Firma Bazlı Satış Dağılımı
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <?php 
                        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
                        
                        foreach ($aylar_data as $ay_data): 
                        ?>
                        <div class="white-card" style="padding: 25px;">
                            <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; text-align: center;">
                                <?php echo $ay_data['ay_adi']; ?>
                            </h3>
                            <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 20px; text-align: center;">
                                Toplam: <strong style="color: #3b82f6;">₺<?php echo number_format($ay_data['toplam'], 0, ',', '.'); ?></strong>
                            </div>
                            
                            <!-- Grafik -->
                            <div style="position: relative; margin-bottom: 20px;">
                                <canvas id="chart_<?php echo $ay_data['index']; ?>" width="280" height="280"></canvas>
                            </div>
                            
                            <!-- Firma Listesi -->
                            <?php if (!empty($ay_data['firmalar'])): ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php 
                                $color_index = 0;
                                foreach ($ay_data['firmalar'] as $firma): 
                                    $yuzde = $ay_data['toplam'] > 0 ? ($firma['toplam_satis'] / $ay_data['toplam']) * 100 : 0;
                                    $color = $colors[$color_index % count($colors)];
                                    $color_index++;
                                ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background: var(--bg-secondary); border-radius: 6px; border-left: 3px solid <?php echo $color; ?>;">
                                    <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
                                        <div style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $color; ?>; flex-shrink: 0;"></div>
                                        <span style="font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($firma['firma_adi']); ?></span>
                                    </div>
                                    <div style="text-align: right; flex-shrink: 0; margin-left: 8px;">
                                        <div style="font-size: 13px; font-weight: 700; color: <?php echo $color; ?>;">
                                            ₺<?php echo number_format($firma['toplam_satis'], 0, ',', '.'); ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-secondary);">
                                            %<?php echo number_format($yuzde, 1); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 14px;">
                                <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                Satış yok
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>



            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Firma Satış Grafikleri
        <?php if (isAdmin()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
            
            <?php foreach ($aylar_data as $ay_data): ?>
            <?php if (!empty($ay_data['firmalar'])): ?>
            {
                const ctx = document.getElementById('chart_<?php echo $ay_data['index']; ?>');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: [
                                <?php foreach ($ay_data['firmalar'] as $firma): ?>
                                '<?php echo addslashes($firma['firma_adi']); ?>',
                                <?php endforeach; ?>
                            ],
                            datasets: [{
                                data: [
                                    <?php foreach ($ay_data['firmalar'] as $firma): ?>
                                    <?php echo $firma['toplam_satis']; ?>,
                                    <?php endforeach; ?>
                                ],
                                backgroundColor: colors,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return label + ': ₺' + value.toLocaleString('tr-TR') + ' (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
            <?php endif; ?>
            <?php endforeach; ?>
        });
        <?php endif; ?>

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });

        // Add loading animation to action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                const icon = this.querySelector('.action-icon i');
                const originalClass = icon.className;
                
                icon.className = 'fas fa-spinner fa-spin';
                
                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            });
        });

        // Simulate real-time updates
        setInterval(() => {
            const cards = document.querySelectorAll('.card-value');
            cards.forEach(card => {
                if (Math.random() < 0.1) { // 10% chance to update
                    const currentValue = parseInt(card.textContent);
                    const newValue = currentValue + Math.floor(Math.random() * 3);
                    card.textContent = newValue;
                    
                    // Add animation
                    card.style.transform = 'scale(1.1)';
                    card.style.color = '#10b981';
                    setTimeout(() => {
                        card.style.transform = 'scale(1)';
                        card.style.color = 'var(--text-primary)';
                    }, 300);
                }
            });
        }, 5000);
    </script>

    <style>
        .logout-btn {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            color: #dc2626;
            background: #fef2f2;
        }
    </style>
</body>
</html>
