<?php
/**
 * Primew Panel - Yeni Tahsilat Ekle
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$errors = [];
$success_message = '';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $musteri_id = !empty($_POST['musteri_id']) ? (int)$_POST['musteri_id'] : null;
    $personel_id = !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null;
    $banka_id = !empty($_POST['banka_id']) ? (int)$_POST['banka_id'] : null;
    $odeme_tarihi = $_POST['odeme_tarihi'] ?? null;
    $fatura_tarihi = !empty($_POST['fatura_tarihi']) ? $_POST['fatura_tarihi'] : null;
    $tutar_kdv_haric = !empty($_POST['tutar_kdv_haric']) ? (float)$_POST['tutar_kdv_haric'] : 0;
    $aciklama = !empty($_POST['aciklama']) ? trim($_POST['aciklama']) : null;
    
    // Validasyon
    if (!$musteri_id) $errors[] = "Müşteri seçimi zorunludur!";
    if (!$banka_id) $errors[] = "Banka seçimi zorunludur!";
    if (!$odeme_tarihi) $errors[] = "Ödeme tarihi zorunludur!";
    if ($tutar_kdv_haric <= 0) $errors[] = "Geçerli bir tutar giriniz!";
    
    if (empty($errors)) {
        // KDV hesapla (20%)
        $kdv_tutari = $tutar_kdv_haric * 0.20;
        $tutar_kdv_dahil = $tutar_kdv_haric + $kdv_tutari;
        
        $data = [
            'musteri_id' => $musteri_id,
            'personel_id' => $personel_id,
            'banka_id' => $banka_id,
            'odeme_tarihi' => $odeme_tarihi,
            'fatura_tarihi' => $fatura_tarihi,
            'tutar_kdv_haric' => $tutar_kdv_haric,
            'kdv_tutari' => $kdv_tutari,
            'tutar_kdv_dahil' => $tutar_kdv_dahil,
            'aciklama' => $aciklama
        ];
        
        if ($db->insert('tahsilatlar', $data)) {
            header('Location: tahsilatlar.php?success=1');
            exit;
        } else {
            $errors[] = "Tahsilat eklenirken hata oluştu!";
        }
    }
}

$musteriler = $db->select('musteriler', ['durum' => 'aktif'], 'firma_adi ASC');
$bankalar = $db->select('bankalar', ['durum' => 'aktif'], 'banka_adi ASC');
$personeller = $db->select('personel', ['rol' => 'satisci', 'durum' => 'aktif'], 'ad_soyad ASC');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Tahsilat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Yeni Tahsilat Ekle';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="tahsilatlar.php" style="color: #3b82f6; text-decoration: none;">Tahsilat</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Yeni Tahsilat</span>
                    </nav>
                </div>

                <?php if (!empty($errors)): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin: 8px 0 0 20px; padding: 0;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Form -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    <form method="POST" action="tahsilat-ekle.php">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <!-- Müşteri Seçimi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Müşteri *</label>
                                <select name="musteri_id" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="">Müşteri Seçiniz</option>
                                    <?php foreach ($musteriler as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['firma_adi']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Personel Seçimi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Personel</label>
                                <select name="personel_id" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="">Personel Seçiniz (Opsiyonel)</option>
                                    <?php foreach ($personeller as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['ad_soyad']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Banka Seçimi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Banka *</label>
                                <select name="banka_id" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="">Banka Seçiniz</option>
                                    <?php foreach ($bankalar as $b): ?>
                                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['banka_adi']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Ödeme Tarihi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Ödeme Tarihi *</label>
                                <input type="date" name="odeme_tarihi" value="<?php echo date('Y-m-d'); ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <!-- Fatura Tarihi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Fatura Tarihi</label>
                                <input type="date" name="fatura_tarihi" 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <!-- Tutar KDV Hariç -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Tutar (KDV Hariç) *</label>
                                <input type="number" name="tutar_kdv_haric" id="tutar_kdv_haric" step="0.01" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                       oninput="hesaplaKDV()">
                            </div>
                            
                            <!-- KDV ve Toplam (Otomatik) -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">KDV Dahil Toplam</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <input type="text" id="kdv_tutari_display" readonly 
                                               style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; color: #f59e0b; font-weight: 600;"
                                               placeholder="KDV: ₺0">
                                    </div>
                                    <div>
                                        <input type="text" id="kdv_dahil_display" readonly 
                                               style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc; color: #3b82f6; font-weight: 700;"
                                               placeholder="Toplam: ₺0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Açıklama (Tam genişlik) -->
                        <div style="margin-top: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Açıklama</label>
                            <textarea name="aciklama" rows="3" 
                                      style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;"
                                      placeholder="Ödeme ile ilgili notlar..."></textarea>
                        </div>
                        
                        <!-- Butonlar -->
                        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
                            <a href="tahsilatlar.php" style="padding: 12px 24px; background: #64748b; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <button type="submit" style="padding: 12px 24px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        }
        
        function hesaplaKDV() {
            const kdvHaric = parseFloat(document.getElementById('tutar_kdv_haric').value) || 0;
            const kdv = kdvHaric * 0.20;
            const kdvDahil = kdvHaric + kdv;
            
            document.getElementById('kdv_tutari_display').value = 'KDV: ₺' + kdv.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('kdv_dahil_display').value = 'Toplam: ₺' + kdvDahil.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    </script>
</body>
</html>

