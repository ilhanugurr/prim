<?php
/**
 * Primew Panel - Satışlar Yönetimi
 * Satış listesi ve yönetimi
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş kontrolü
requireLogin();

// İstatistikleri al
$stats = getStats();

// Satış silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $satis_id = (int)$_GET['id'];
    
    // Önce satış detaylarını sil (foreign key constraint nedeniyle)
    $db->delete('satis_detay', ['satis_id' => $satis_id]);
    
    // Sonra satışı sil
    if ($db->delete('satislar', ['id' => $satis_id])) {
        $success_message = "Satış başarıyla silindi!";
    } else {
        $error_message = "Satış silinirken hata oluştu!";
    }
}

// Satış onaylama işlemi
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['id'])) {
    $satis_id = (int)$_GET['id'];
    
    if ($db->update('satislar', [
        'onay_durumu' => 'onaylandi'
    ], ['id' => $satis_id])) {
        $success_message = "Satış başarıyla onaylandı!";
    } else {
        $error_message = "Satış onaylanırken hata oluştu!";
    }
}

// Satış reddetme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'reject' && isset($_GET['id'])) {
    $satis_id = (int)$_GET['id'];
    
    if ($db->update('satislar', [
        'onay_durumu' => 'reddedildi'
    ], ['id' => $satis_id])) {
        $success_message = "Satış reddedildi!";
    } else {
        $error_message = "Satış reddedilirken hata oluştu!";
    }
}

// Satışları al (rol bazlı filtreleme)
$where_clause = "";
$where_params = [];

if (isSatisci()) {
    // Satışçı sadece kendi satışlarını görür
    $where_clause = "WHERE s.personel_id = ?";
    $where_params[] = $_SESSION['personel_id'];
}

$satislar = $db->query("
    SELECT s.*, p.ad_soyad as personel_adi 
    FROM satislar s 
    LEFT JOIN personel p ON s.personel_id = p.id 
    $where_clause
    ORDER BY s.olusturma_tarihi DESC
", $where_params);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Satışlar</title>
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
            $page_title = 'Satışlar';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Satışlar</span>
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
                    <a href="satis-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Satış Ekle
                    </a>
                </div>

                <!-- Satış Listesi -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; margin-bottom: 30px;">
                    <h2 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">Satış Listesi</h2>
                    
                    <?php if (!empty($satislar)): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">#</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">Personel</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">Müşteri</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">Toplam Tutar</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">Ödeme</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">Onay Durumu</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">Satış Tarihi</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: #374151;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($satislar as $satis): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='white'">
                                            <td style="padding: 15px; color: #1e293b; font-weight: 600;">
                                                #<?php echo $satis['id']; ?>
                                            </td>
                                            <td style="padding: 15px; color: #64748b; max-width: 300px;">
                                                <?php if ($satis['personel_adi']): ?>
                                                    <?php echo htmlspecialchars($satis['personel_adi']); ?>
                                                <?php else: ?>
                                                    <span style="color: #d1d5db;">Personel atanmamış</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; color: #64748b; max-width: 300px;">
                                                <?php if ($satis['musteri_adi']): ?>
                                                    <?php echo htmlspecialchars($satis['musteri_adi']); ?>
                                                <?php else: ?>
                                                    <span style="color: #d1d5db;">Müşteri bilgisi yok</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; color: #1e293b; font-weight: 600;">
                                                <?php echo number_format($satis['toplam_tutar'], 2); ?>₺
                                            </td>
                                            <td style="padding: 15px;">
                                                <span class="status-badge status-<?php echo $satis['durum'] == 'odendi' ? 'active' : ($satis['durum'] == 'odeme_bekleniyor' ? 'warning' : 'inactive'); ?>">
                                                    <?php 
                                                    $durum_text = [
                                                        'odendi' => 'Ödendi',
                                                        'odenmedi' => 'Ödenmedi',
                                                        'odeme_bekleniyor' => 'Ödeme Bekleniyor',
                                                        'iptal' => 'İptal'
                                                    ];
                                                    echo $durum_text[$satis['durum']] ?? ucfirst($satis['durum']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="padding: 15px;">
                                                <?php 
                                                $onay_durumu = $satis['onay_durumu'] ?? 'beklemede';
                                                $onay_class = '';
                                                $onay_text = '';
                                                
                                                switch($onay_durumu) {
                                                    case 'onaylandi':
                                                        $onay_class = 'status-active';
                                                        $onay_text = 'Onaylandı';
                                                        break;
                                                    case 'reddedildi':
                                                        $onay_class = 'status-inactive';
                                                        $onay_text = 'Reddedildi';
                                                        break;
                                                    default:
                                                        $onay_class = 'status-warning';
                                                        $onay_text = 'Beklemede';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $onay_class; ?>">
                                                    <?php echo $onay_text; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 15px; color: #64748b; font-size: 14px;">
                                                <?php echo date('d.m.Y', strtotime($satis['satis_tarihi'])); ?>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <a href="satis-duzenle.php?id=<?php echo $satis['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;">
                                                        <i class="fas fa-eye"></i> Görüntüle
                                                    </a>
                                                    <?php if (isAdmin() && $onay_durumu == 'beklemede'): ?>
                                                    <a href="satislar.php?action=approve&id=<?php echo $satis['id']; ?>" 
                                                       class="btn btn-success" 
                                                       style="padding: 6px 12px; font-size: 11px;"
                                                       onclick="return confirm('Bu satışı onaylamak istediğinizden emin misiniz?')">
                                                       <i class="fas fa-check"></i> Onayla
                                                    </a>
                                                    <a href="satislar.php?action=reject&id=<?php echo $satis['id']; ?>" 
                                                       class="btn btn-warning" 
                                                       style="padding: 6px 12px; font-size: 11px;"
                                                       onclick="return confirm('Bu satışı reddetmek istediğinizden emin misiniz?')">
                                                       <i class="fas fa-times"></i> Reddet
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="satis-duzenle.php?id=<?php echo $satis['id']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <a href="satislar.php?action=delete&id=<?php echo $satis['id']; ?>" 
                                                       class="btn btn-danger" 
                                                       style="padding: 6px 12px; font-size: 11px;"
                                                       onclick="return confirm('Bu satışı silmek istediğinizden emin misiniz?')">
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
                        <div style="text-align: center; padding: 60px; color: #64748b;">
                            <i class="fas fa-chart-line" style="font-size: 64px; margin-bottom: 20px; color: #d1d5db;"></i>
                            <h3 style="font-size: 20px; margin-bottom: 8px; color: #374151;">Henüz satış eklenmemiş</h3>
                            <p style="font-size: 16px; margin-bottom: 20px;">Sisteme satış ekleyerek başlayın.</p>
                            <a href="satis-ekle.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                İlk Satışı Ekle
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
