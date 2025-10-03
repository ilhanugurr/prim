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

// Filtreleme parametreleri
$filter_personel = isset($_GET['personel_id']) && !empty($_GET['personel_id']) ? (int)$_GET['personel_id'] : null;
$filter_baslangic = isset($_GET['baslangic_tarihi']) && !empty($_GET['baslangic_tarihi']) ? $_GET['baslangic_tarihi'] : null;
$filter_bitis = isset($_GET['bitis_tarihi']) && !empty($_GET['bitis_tarihi']) ? $_GET['bitis_tarihi'] : null;
$filter_durum = isset($_GET['durum']) && !empty($_GET['durum']) ? $_GET['durum'] : null;
$filter_onay = isset($_GET['onay_durumu']) && !empty($_GET['onay_durumu']) ? $_GET['onay_durumu'] : null;

// Satışları al (rol bazlı ve filtre)
$where_conditions = [];
$where_params = [];

if (isSatisci()) {
    // Satışçı sadece kendi satışlarını görür
    $where_conditions[] = "s.personel_id = ?";
    $where_params[] = $_SESSION['personel_id'];
} else {
    // Admin için personel filtresi
    if ($filter_personel) {
        $where_conditions[] = "s.personel_id = ?";
        $where_params[] = $filter_personel;
    }
}

// Tarih filtreleri
if ($filter_baslangic) {
    $where_conditions[] = "s.satis_tarihi >= ?";
    $where_params[] = $filter_baslangic;
}

if ($filter_bitis) {
    $where_conditions[] = "s.satis_tarihi <= ?";
    $where_params[] = $filter_bitis;
}

// Durum filtreleri
if ($filter_durum) {
    $where_conditions[] = "s.durum = ?";
    $where_params[] = $filter_durum;
}

if ($filter_onay) {
    $where_conditions[] = "s.onay_durumu = ?";
    $where_params[] = $filter_onay;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$satislar = $db->query("
    SELECT s.*, p.ad_soyad as personel_adi,
           COALESCE(SUM(sm.maliyet_tutari), 0) as toplam_maliyet
    FROM satislar s 
    LEFT JOIN personel p ON s.personel_id = p.id 
    LEFT JOIN satis_maliyetler sm ON s.id = sm.satis_id
    $where_clause
    GROUP BY s.id
    ORDER BY s.satis_tarihi DESC, s.olusturma_tarihi DESC
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
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Satışlar</span>
                    </nav>
                </div>

                <!-- Filtreleme -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
                    <form method="GET" action="satislar.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <?php if (isAdmin()): ?>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Satışçı</label>
                            <select name="personel_id" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                <option value="">Tüm Satışçılar</option>
                                <?php
                                $personeller = $db->select('personel', ['rol' => 'satisci', 'durum' => 'aktif'], 'ad_soyad ASC');
                                foreach ($personeller as $p):
                                ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $filter_personel == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Başlangıç Tarihi</label>
                            <input type="date" name="baslangic_tarihi" value="<?php echo htmlspecialchars($filter_baslangic ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Bitiş Tarihi</label>
                            <input type="date" name="bitis_tarihi" value="<?php echo htmlspecialchars($filter_bitis ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Ödeme Durumu</label>
                            <select name="durum" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                <option value="">Tümü</option>
                                <option value="odendi" <?php echo $filter_durum == 'odendi' ? 'selected' : ''; ?>>Ödendi</option>
                                <option value="odenmedi" <?php echo $filter_durum == 'odenmedi' ? 'selected' : ''; ?>>Ödenmedi</option>
                                <option value="odeme_bekleniyor" <?php echo $filter_durum == 'odeme_bekleniyor' ? 'selected' : ''; ?>>Ödeme Bekleniyor</option>
                                <option value="iptal" <?php echo $filter_durum == 'iptal' ? 'selected' : ''; ?>>İptal</option>
                            </select>
                        </div>
                        
                        <?php if (isAdmin()): ?>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">Onay Durumu</label>
                            <select name="onay_durumu" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                <option value="">Tümü</option>
                                <option value="beklemede" <?php echo $filter_onay == 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                                <option value="onaylandi" <?php echo $filter_onay == 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                                <option value="reddedildi" <?php echo $filter_onay == 'reddedildi' ? 'selected' : ''; ?>>Reddedildi</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                                <i class="fas fa-filter"></i> Filtrele
                            </button>
                            <a href="satislar.php" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-block;">
                                <i class="fas fa-redo"></i> Sıfırla
                            </a>
                        </div>
                    </form>
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
                <div class="action-buttons" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 15px; color: var(--text-secondary);">
                        <i class="fas fa-list"></i> Toplam <strong><?php echo count($satislar); ?></strong> satış bulundu
                    </div>
                    <a href="satis-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Satış Ekle
                    </a>
                </div>

                <!-- Satış Listesi -->
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid var(--border-color); margin-bottom: 30px;">
                    <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">Satış Listesi</h2>
                    
                    <?php if (!empty($satislar)): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid #e2e8f0;">
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">#</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Personel</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Müşteri</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Toplam Tutar</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Maliyet</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Net Tutar</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Ödeme</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Onay Durumu</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">Satış Tarihi</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 600; color: var(--text-primary);">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($satislar as $satis): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='var(--bg-secondary)'" onmouseout="this.style.backgroundColor='var(--bg-card)'">
                                            <td style="padding: 15px; color: var(--text-primary); font-weight: 600;">
                                                #<?php echo $satis['id']; ?>
                                            </td>
                                            <td style="padding: 15px; color: var(--text-secondary); max-width: 300px;">
                                                <?php if ($satis['personel_adi']): ?>
                                                    <?php echo htmlspecialchars($satis['personel_adi']); ?>
                                                <?php else: ?>
                                                    <span style="color: #d1d5db;">Personel atanmamış</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; color: var(--text-secondary); max-width: 300px;">
                                                <?php if ($satis['musteri_adi']): ?>
                                                    <?php echo htmlspecialchars($satis['musteri_adi']); ?>
                                                <?php else: ?>
                                                    <span style="color: #d1d5db;">Müşteri bilgisi yok</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; color: var(--text-primary); font-weight: 600;">
                                                <?php echo number_format($satis['toplam_tutar'], 2); ?>₺
                                            </td>
                                            <td style="padding: 15px; color: #dc2626; font-weight: 600;">
                                                <?php echo number_format($satis['toplam_maliyet'], 2); ?>₺
                                            </td>
                                            <td style="padding: 15px; color: #059669; font-weight: 600;">
                                                <?php 
                                                $net_tutar = $satis['toplam_tutar'] - $satis['toplam_maliyet'];
                                                echo number_format($net_tutar, 2); ?>₺
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
                                            <td style="padding: 15px; color: var(--text-secondary); font-size: 14px;">
                                                <?php echo date('d.m.Y', strtotime($satis['satis_tarihi'])); ?>
                                            </td>
                                            <td style="padding: 15px;">
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
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
                        <div class="text-secondary" style="text-align: center; padding: 60px;">
                            <i class="fas fa-chart-line" style="font-size: 64px; margin-bottom: 20px; color: #d1d5db;"></i>
                            <h3 class="text-primary" style="font-size: 20px; margin-bottom: 8px;">Henüz satış eklenmemiş</h3>
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
