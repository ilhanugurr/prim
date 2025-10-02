<?php
/**
 * Primew Panel - Tahsilat Yönetimi
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$stats = getStats();

// Filtreleme
$filter_yil = isset($_GET['yil']) ? (int)$_GET['yil'] : date('Y');
$filter_ay = isset($_GET['ay']) && !empty($_GET['ay']) ? (int)$_GET['ay'] : null;
$filter_musteri = isset($_GET['musteri_id']) && !empty($_GET['musteri_id']) ? (int)$_GET['musteri_id'] : null;
$filter_banka = isset($_GET['banka_id']) && !empty($_GET['banka_id']) ? (int)$_GET['banka_id'] : null;
$filter_personel = isset($_GET['personel_id']) && !empty($_GET['personel_id']) ? (int)$_GET['personel_id'] : null;

// Tahsilatları al
$where_conditions = ["YEAR(t.odeme_tarihi) = ?"];
$where_params = [$filter_yil];

if ($filter_ay) {
    $where_conditions[] = "MONTH(t.odeme_tarihi) = ?";
    $where_params[] = $filter_ay;
}

if ($filter_musteri) {
    $where_conditions[] = "t.musteri_id = ?";
    $where_params[] = $filter_musteri;
}

if ($filter_banka) {
    $where_conditions[] = "t.banka_id = ?";
    $where_params[] = $filter_banka;
}

if ($filter_personel) {
    $where_conditions[] = "t.personel_id = ?";
    $where_params[] = $filter_personel;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions) . " AND t.durum = 'aktif'";

$tahsilatlar = $db->query("
    SELECT 
        t.*,
        m.firma_adi as musteri_adi,
        b.banka_adi,
        p.ad_soyad as personel_adi,
        COALESCE(SUM(tm.maliyet_tutari), 0) as toplam_maliyet
    FROM tahsilatlar t
    LEFT JOIN musteriler m ON t.musteri_id = m.id
    LEFT JOIN bankalar b ON t.banka_id = b.id
    LEFT JOIN personel p ON t.personel_id = p.id
    LEFT JOIN tahsilat_maliyetler tm ON t.id = tm.tahsilat_id
    $where_clause
    GROUP BY t.id
    ORDER BY t.odeme_tarihi DESC
", $where_params);

// Toplamları hesapla (maliyet düşüldükten sonra)
$toplam_kdv_dahil = 0;
$toplam_kdv_haric = 0;
$toplam_kdv = 0;
$toplam_maliyet = 0;

foreach ($tahsilatlar as $tahsilat) {
    $maliyet = (float)$tahsilat['toplam_maliyet'];
    $net_tutar = (float)$tahsilat['tutar_kdv_dahil'] - $maliyet;
    
    $toplam_kdv_dahil += $net_tutar;
    $toplam_kdv_haric += ((float)$tahsilat['tutar_kdv_haric'] - $maliyet);
    $toplam_kdv += (float)$tahsilat['kdv_tutari'];
    $toplam_maliyet += $maliyet;
}

// Müşterileri, bankaları ve personeli al
$musteriler = $db->select('musteriler', ['durum' => 'aktif'], 'firma_adi ASC');
$bankalar = $db->select('bankalar', ['durum' => 'aktif'], 'banka_adi ASC');
$personeller = $db->select('personel', ['durum' => 'aktif'], 'ad_soyad ASC'); // Tüm personeller (admin + satışçı)

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
    <title>SeoMEW Prim Sistemi - Tahsilat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Tahsilat';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Tahsilat</span>
                    </nav>
                </div>

                <?php if (isset($_GET['success'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> Tahsilat başarıyla eklendi!
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div style="background: #dbeafe; color: #1e40af; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> Tahsilat başarıyla güncellendi!
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-trash"></i> Tahsilat silindi!
                </div>
                <?php endif; ?>

                <!-- Filtreleme -->
                <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
                    <form method="GET" action="tahsilatlar.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Yıl</label>
                            <select name="yil" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $filter_yil == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Ay</label>
                            <select name="ay" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Tüm Aylar</option>
                                <?php foreach ($aylar as $ay_num => $ay_adi): ?>
                                    <option value="<?php echo $ay_num; ?>" <?php echo $filter_ay == $ay_num ? 'selected' : ''; ?>><?php echo $ay_adi; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Müşteri</label>
                            <select name="musteri_id" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Tüm Müşteriler</option>
                                <?php foreach ($musteriler as $m): ?>
                                    <option value="<?php echo $m['id']; ?>" <?php echo $filter_musteri == $m['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['firma_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Banka</label>
                            <select name="banka_id" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Tüm Bankalar</option>
                                <?php foreach ($bankalar as $b): ?>
                                    <option value="<?php echo $b['id']; ?>" <?php echo $filter_banka == $b['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['banka_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151; font-size: 14px;">Personel</label>
                            <select name="personel_id" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Tüm Personel</option>
                                <?php foreach ($personeller as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $filter_personel == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;">
                                <i class="fas fa-filter"></i> Filtrele
                            </button>
                            <a href="tahsilatlar.php" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-block;">
                                <i class="fas fa-redo"></i> Sıfırla
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Özet Kartlar (Filtreye göre) -->
                <?php if (!empty($tahsilatlar)): ?>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-lira-sign" style="font-size: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">
                                    <?php 
                                    if ($filter_ay) {
                                        echo $aylar[$filter_ay] . ' ' . $filter_yil;
                                    } else {
                                        echo $filter_yil . ' Yılı';
                                    }
                                    ?> - Tahsilat
                                </div>
                                <div style="font-size: 28px; font-weight: 700;">₺<?php echo number_format($toplam_kdv_dahil + $toplam_maliyet, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-minus-circle" style="font-size: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Toplam Maliyet</div>
                                <div style="font-size: 28px; font-weight: 700;">₺<?php echo number_format($toplam_maliyet, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-hand-holding-usd" style="font-size: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Net Tutar</div>
                                <div style="font-size: 28px; font-weight: 700;">₺<?php echo number_format($toplam_kdv_dahil, 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Aksiyon Butonları -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 15px; color: #64748b;">
                        <i class="fas fa-list"></i> Toplam <strong><?php echo count($tahsilatlar); ?></strong> tahsilat
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="bankalar.php" style="padding: 10px 20px; background: #64748b; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-university"></i> Banka Yönetimi
                        </a>
                        <a href="tahsilat-ekle.php" style="padding: 10px 20px; background: #10b981; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-plus"></i> Yeni Tahsilat
                        </a>
                    </div>
                </div>

                <!-- Tahsilat Listesi -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    <?php if (!empty($tahsilatlar)): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Ödeme Tarihi</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Müşteri</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Personel</th>
                                <th style="padding: 12px; text-align: left; color: #64748b; font-weight: 600;">Banka</th>
                                <th style="padding: 12px; text-align: right; color: #64748b; font-weight: 600;">Tahsilat</th>
                                <th style="padding: 12px; text-align: right; color: #64748b; font-weight: 600;">Maliyet</th>
                                <th style="padding: 12px; text-align: right; color: #64748b; font-weight: 600;">Net Tutar</th>
                                <th style="padding: 12px; text-align: center; color: #64748b; font-weight: 600;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tahsilatlar as $tahsilat): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 16px; color: #1f2937;">
                                    <div style="font-weight: 600;"><?php echo date('d.m.Y', strtotime($tahsilat['odeme_tarihi'])); ?></div>
                                    <?php if ($tahsilat['fatura_tarihi']): ?>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                        <i class="fas fa-file-invoice"></i> Fatura: <?php echo date('d.m.Y', strtotime($tahsilat['fatura_tarihi'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px; color: #1f2937; font-weight: 500;">
                                    <?php echo htmlspecialchars($tahsilat['musteri_adi']); ?>
                                </td>
                                <td style="padding: 16px; color: #64748b;">
                                    <?php if ($tahsilat['personel_adi']): ?>
                                        <i class="fas fa-user" style="margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($tahsilat['personel_adi']); ?>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px; color: #64748b;">
                                    <i class="fas fa-university" style="margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($tahsilat['banka_adi']); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #64748b; font-weight: 600;">
                                    ₺<?php echo number_format($tahsilat['tutar_kdv_dahil'], 2, ',', '.'); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #ef4444; font-weight: 600;">
                                    <?php if ($tahsilat['toplam_maliyet'] > 0): ?>
                                        -₺<?php echo number_format($tahsilat['toplam_maliyet'], 2, ',', '.'); ?>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">₺0,00</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #10b981; font-weight: 700; font-size: 16px;">
                                    ₺<?php echo number_format($tahsilat['tutar_kdv_dahil'] - $tahsilat['toplam_maliyet'], 2, ',', '.'); ?>
                                </td>
                                <td style="padding: 16px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <a href="tahsilat-duzenle.php?id=<?php echo $tahsilat['id']; ?>" 
                                           style="padding: 6px 12px; background: #3b82f6; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="tahsilat-sil.php?id=<?php echo $tahsilat['id']; ?>" 
                                           style="padding: 6px 12px; background: #ef4444; color: white; border-radius: 6px; text-decoration: none; font-size: 13px;"
                                           onclick="return confirm('Bu tahsilatı silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc; font-weight: 700; border-top: 2px solid #e2e8f0;">
                                <td colspan="4" style="padding: 16px; color: #1f2937;">TOPLAM</td>
                                <td style="padding: 16px; text-align: right; color: #64748b;">
                                    ₺<?php echo number_format($toplam_kdv_dahil + $toplam_maliyet, 2, ',', '.'); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #ef4444;">
                                    -₺<?php echo number_format($toplam_maliyet, 2, ',', '.'); ?>
                                </td>
                                <td style="padding: 16px; text-align: right; color: #10b981; font-size: 18px;">
                                    ₺<?php echo number_format($toplam_kdv_dahil, 2, ',', '.'); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 60px; color: #64748b;">
                        <i class="fas fa-money-bill-wave" style="font-size: 64px; margin-bottom: 20px; color: #cbd5e1;"></i>
                        <h3 style="font-size: 20px; margin-bottom: 8px; color: #374151;">Tahsilat kaydı bulunamadı</h3>
                        <p style="font-size: 16px; margin-bottom: 20px;">Seçili filtrelere göre tahsilat bulunamadı.</p>
                        <a href="tahsilat-ekle.php" style="padding: 12px 24px; background: #10b981; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block;">
                            <i class="fas fa-plus"></i> Yeni Tahsilat Ekle
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>
</body>
</html>

