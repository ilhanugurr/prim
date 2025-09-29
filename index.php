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
                        <div class="sales-card" style="background: white; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; height: 140px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                                    <i class="fas fa-receipt" style="color: white; font-size: 14px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1e293b; font-size: 13px;">#<?php echo $sale['id']; ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?php echo date('d.m.Y', strtotime($sale['olusturma_tarihi'])); ?></div>
                                </div>
                            </div>
                            
                            <div style="flex: 1; margin-bottom: 8px;">
                                <div style="font-weight: 500; color: #374151; font-size: 12px; margin-bottom: 3px; line-height: 1.3;">
                                    <?php echo htmlspecialchars($sale['musteri_adi'] ?: 'Müşteri bilgisi yok'); ?>
                                </div>
                                <div style="font-size: 11px; color: #64748b;">
                                    <strong><?php echo htmlspecialchars($sale['personel_adi'] ?: 'Personel bilgisi yok'); ?></strong>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
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
                        <div class="sales-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; grid-column: 1 / -1; text-align: center;">
                            <div style="color: #6b7280; font-size: 14px;">
                                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                                Henüz satış bulunmuyor
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions" style="margin-bottom: 40px;">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i>
                        Hızlı İşlemler
                    </h2>
                    <div class="actions-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <a href="firma-ekle.php" class="action-card" style="padding: 15px; text-align: center;">
                            <div class="action-icon" style="width: 40px; height: 40px; margin: 0 auto 10px;">
                                <i class="fas fa-industry" style="font-size: 16px;"></i>
                            </div>
                            <div class="action-title" style="font-size: 14px; margin-bottom: 0;">Yeni Firma Ekle</div>
                        </a>

                        <a href="personel-ekle.php" class="action-card" style="padding: 15px; text-align: center;">
                            <div class="action-icon" style="width: 40px; height: 40px; margin: 0 auto 10px;">
                                <i class="fas fa-users" style="font-size: 16px;"></i>
                            </div>
                            <div class="action-title" style="font-size: 14px; margin-bottom: 0;">Yeni Personel Ekle</div>
                        </a>

                        <a href="urun-ekle.php" class="action-card" style="padding: 15px; text-align: center;">
                            <div class="action-icon" style="width: 40px; height: 40px; margin: 0 auto 10px;">
                                <i class="fas fa-link" style="font-size: 16px;"></i>
                            </div>
                            <div class="action-title" style="font-size: 14px; margin-bottom: 0;">Ürün/Hizmet Ekle</div>
                        </a>

                        <a href="satis-ekle.php" class="action-card" style="padding: 15px; text-align: center;">
                            <div class="action-icon" style="width: 40px; height: 40px; margin: 0 auto 10px;">
                                <i class="fas fa-chart-line" style="font-size: 16px;"></i>
                            </div>
                            <div class="action-title" style="font-size: 14px; margin-bottom: 0;">Yeni Satış Ekle</div>
                        </a>

                        <a href="musteri-ekle.php" class="action-card" style="padding: 15px; text-align: center;">
                            <div class="action-icon" style="width: 40px; height: 40px; margin: 0 auto 10px;">
                                <i class="fas fa-user-tie" style="font-size: 16px;"></i>
                            </div>
                            <div class="action-title" style="font-size: 14px; margin-bottom: 0;">Yeni Müşteri Ekle</div>
                        </a>
                    </div>
                </div>



            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

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
                        card.style.color = '#1e293b';
                    }, 300);
                }
            });
        }, 5000);
    </script>

    <style>
        .logout-btn {
            color: #64748b;
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
