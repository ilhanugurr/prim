<?php
/**
 * Primew Panel - Tahsilat Düzenle
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$tahsilat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tahsilat = null;
$errors = [];

if ($tahsilat_id > 0) {
    $tahsilat = $db->select('tahsilatlar', ['id' => $tahsilat_id]);
    if (!empty($tahsilat)) {
        $tahsilat = $tahsilat[0];
    } else {
        header('Location: tahsilatlar.php');
        exit;
    }
} else {
    header('Location: tahsilatlar.php');
    exit;
}

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
        // KDV hesapla (KDV Yok checkbox'ına göre)
        $kdv_yok = isset($_POST['kdv_yok']) && $_POST['kdv_yok'] == '1';
        
        if ($kdv_yok) {
            // KDV yok - tutar direkt KDV dahil
            $kdv_tutari = 0;
            $tutar_kdv_dahil = $tutar_kdv_haric;
        } else {
            // KDV var - %20 hesapla
            $kdv_tutari = $tutar_kdv_haric * 0.20;
            $tutar_kdv_dahil = $tutar_kdv_haric + $kdv_tutari;
        }
        
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
        
        if ($db->update('tahsilatlar', $data, ['id' => $tahsilat_id])) {
            // Önce mevcut maliyetleri sil
            $db->delete('tahsilat_maliyetler', ['tahsilat_id' => $tahsilat_id]);
            
            // Yeni maliyetleri kaydet
            if (isset($_POST['maliyet_adi']) && is_array($_POST['maliyet_adi'])) {
                foreach ($_POST['maliyet_adi'] as $index => $maliyet_adi) {
                    if (!empty($maliyet_adi) && !empty($_POST['maliyet_tutari'][$index])) {
                        $db->insert('tahsilat_maliyetler', [
                            'tahsilat_id' => $tahsilat_id,
                            'maliyet_adi' => trim($maliyet_adi),
                            'maliyet_aciklama' => !empty($_POST['maliyet_aciklama'][$index]) ? trim($_POST['maliyet_aciklama'][$index]) : null,
                            'maliyet_tutari' => (float)$_POST['maliyet_tutari'][$index]
                        ]);
                    }
                }
            }
            
            header('Location: tahsilatlar.php?updated=1');
            exit;
        } else {
            $errors[] = "Tahsilat güncellenirken hata oluştu!";
        }
    }
}

$musteriler = $db->select('musteriler', ['durum' => 'aktif'], 'firma_adi ASC');
$bankalar = $db->select('bankalar', ['durum' => 'aktif'], 'banka_adi ASC');
$personeller = $db->select('personel', ['durum' => 'aktif'], 'ad_soyad ASC'); // Tüm personeller (admin + satışçı)
$maliyetler = $db->select('tahsilat_maliyetler', ['tahsilat_id' => $tahsilat_id], 'id ASC');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Tahsilat Düzenle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Tahsilat Düzenle';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="tahsilatlar.php" style="color: #3b82f6; text-decoration: none;">Tahsilat</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Düzenle</span>
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

                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
                    <form method="POST" action="tahsilat-duzenle.php?id=<?php echo $tahsilat_id; ?>" id="tahsilat-form">
                        <input type="hidden" name="kdv_yok" id="kdv_yok_hidden" value="<?php echo $tahsilat['kdv_tutari'] == 0 ? '1' : '0'; ?>">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Müşteri *</label>
                                <select name="musteri_id" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Müşteri Seçiniz</option>
                                    <?php foreach ($musteriler as $m): ?>
                                        <option value="<?php echo $m['id']; ?>" <?php echo $tahsilat['musteri_id'] == $m['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['firma_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Personel</label>
                                <select name="personel_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Personel Seçiniz (Opsiyonel)</option>
                                    <?php foreach ($personeller as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $tahsilat['personel_id'] == $p['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Banka *</label>
                                <select name="banka_id" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="">Banka Seçiniz</option>
                                    <?php foreach ($bankalar as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo $tahsilat['banka_id'] == $b['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['banka_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ödeme Tarihi *</label>
                                <input type="date" name="odeme_tarihi" value="<?php echo $tahsilat['odeme_tarihi']; ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Fatura Tarihi</label>
                                <input type="date" name="fatura_tarihi" value="<?php echo $tahsilat['fatura_tarihi']; ?>" 
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Tutar (KDV Hariç) *</label>
                                <input type="number" name="tutar_kdv_haric" id="tutar_kdv_haric" step="0.01" required 
                                       value="<?php echo $tahsilat['tutar_kdv_haric']; ?>"
                                       style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;"
                                       oninput="hesaplaKDV()">
                                <div style="margin-top: 8px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; color: var(--text-secondary);">
                                        <input type="checkbox" id="kdv_yok" onchange="toggleKDV()" 
                                               <?php echo $tahsilat['kdv_tutari'] == 0 ? 'checked' : ''; ?>
                                               style="width: 18px; height: 18px; cursor: pointer;">
                                        <span>KDV Yok</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">KDV Dahil Toplam</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <input type="text" id="kdv_tutari_display" readonly 
                                           style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: var(--bg-secondary); color: #f59e0b; font-weight: 600;">
                                    <input type="text" id="kdv_dahil_display" readonly 
                                           style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: var(--bg-secondary); color: #3b82f6; font-weight: 700;">
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Açıklama</label>
                            <textarea name="aciklama" rows="3" 
                                      style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; resize: vertical;"><?php echo htmlspecialchars($tahsilat['aciklama']); ?></textarea>
                        </div>
                        
                        <!-- Maliyetler -->
                        <div style="margin-top: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <label style="font-weight: 600; color: var(--text-primary); font-size: 16px;">
                                    <i class="fas fa-minus-circle" style="color: #ef4444;"></i> Maliyetler
                                </label>
                                <button type="button" onclick="addMaliyet()" 
                                        style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                    <i class="fas fa-plus"></i> Maliyet Ekle
                                </button>
                            </div>
                            
                            <div id="maliyetler-container" style="display: flex; flex-direction: column; gap: 12px;">
                                <?php if (!empty($maliyetler)): ?>
                                    <?php foreach ($maliyetler as $maliyet): ?>
                                    <div class="maliyet-item" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; display: grid; grid-template-columns: 2fr 3fr 1.5fr auto; gap: 12px; align-items: start;">
                                        <div>
                                            <input type="text" name="maliyet_adi[]" placeholder="Maliyet adı" 
                                                   value="<?php echo htmlspecialchars($maliyet['maliyet_adi']); ?>"
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                        </div>
                                        <div>
                                            <input type="text" name="maliyet_aciklama[]" placeholder="Açıklama (opsiyonel)" 
                                                   value="<?php echo htmlspecialchars($maliyet['maliyet_aciklama']); ?>"
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                        </div>
                                        <div>
                                            <input type="number" name="maliyet_tutari[]" step="0.01" placeholder="Tutar (₺)" 
                                                   value="<?php echo $maliyet['maliyet_tutari']; ?>"
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;"
                                                   oninput="updateTotalMaliyet()">
                                        </div>
                                        <button type="button" onclick="removeMaliyet(this)" 
                                                style="padding: 10px 14px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="maliyet-item" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; display: grid; grid-template-columns: 2fr 3fr 1.5fr auto; gap: 12px; align-items: start;">
                                        <div>
                                            <input type="text" name="maliyet_adi[]" placeholder="Maliyet adı" 
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                        </div>
                                        <div>
                                            <input type="text" name="maliyet_aciklama[]" placeholder="Açıklama (opsiyonel)" 
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                                        </div>
                                        <div>
                                            <input type="number" name="maliyet_tutari[]" step="0.01" placeholder="Tutar (₺)" 
                                                   style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;"
                                                   oninput="updateTotalMaliyet()">
                                        </div>
                                        <button type="button" onclick="removeMaliyet(this)" 
                                                style="padding: 10px 14px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 15px; padding: 12px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 600; color: #92400e;">Toplam Maliyet:</span>
                                    <span id="toplam-maliyet" style="font-size: 18px; font-weight: 700; color: #f59e0b;">₺0,00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
                            <a href="tahsilatlar.php" style="padding: 12px 24px; background: #64748b; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <button type="submit" style="padding: 12px 24px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                <i class="fas fa-save"></i> Güncelle
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
        
        function toggleKDV() {
            const kdvYok = document.getElementById('kdv_yok').checked;
            document.getElementById('kdv_yok_hidden').value = kdvYok ? '1' : '0';
            hesaplaKDV();
        }
        
        function hesaplaKDV() {
            const kdvYok = document.getElementById('kdv_yok').checked;
            const kdvHaric = parseFloat(document.getElementById('tutar_kdv_haric').value) || 0;
            
            if (kdvYok) {
                // KDV yok - tutar direkt KDV dahil olarak kabul edilir
                document.getElementById('kdv_tutari_display').value = 'KDV: ₺0,00';
                document.getElementById('kdv_dahil_display').value = 'Toplam: ₺' + kdvHaric.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                // KDV var - %20 hesapla
                const kdv = kdvHaric * 0.20;
                const kdvDahil = kdvHaric + kdv;
                
                document.getElementById('kdv_tutari_display').value = 'KDV: ₺' + kdv.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('kdv_dahil_display').value = 'Toplam: ₺' + kdvDahil.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }
        
        // Sayfa yüklendiğinde KDV'yi hesapla
        window.addEventListener('DOMContentLoaded', function() {
            hesaplaKDV();
            updateTotalMaliyet();
        });
        
        function addMaliyet() {
            const container = document.getElementById('maliyetler-container');
            const newItem = document.createElement('div');
            newItem.className = 'maliyet-item';
            newItem.style.cssText = 'background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; display: grid; grid-template-columns: 2fr 3fr 1.5fr auto; gap: 12px; align-items: start;';
            newItem.innerHTML = `
                <div>
                    <input type="text" name="maliyet_adi[]" placeholder="Maliyet adı" 
                           style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <input type="text" name="maliyet_aciklama[]" placeholder="Açıklama (opsiyonel)" 
                           style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <input type="number" name="maliyet_tutari[]" step="0.01" placeholder="Tutar (₺)" 
                           style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;"
                           oninput="updateTotalMaliyet()">
                </div>
                <button type="button" onclick="removeMaliyet(this)" 
                        style="padding: 10px 14px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newItem);
        }
        
        function removeMaliyet(button) {
            const item = button.closest('.maliyet-item');
            item.remove();
            updateTotalMaliyet();
        }
        
        function updateTotalMaliyet() {
            const inputs = document.querySelectorAll('input[name="maliyet_tutari[]"]');
            let total = 0;
            inputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('toplam-maliyet').textContent = '₺' + total.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    </script>
</body>
</html>

