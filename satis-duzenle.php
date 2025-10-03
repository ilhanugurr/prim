<?php
/**
 * Primew Panel - Satış Düzenleme
 * Satış düzenleme sayfası
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş kontrolü
requireLogin();

// AJAX isteği - Firma seçildiğinde ürün/hizmetleri getir (önce kontrol et)
if (isset($_GET['action']) && $_GET['action'] == 'get_products' && isset($_GET['firma_id'])) {
    $firma_id = (int)$_GET['firma_id'];
    $urunler = $db->select('urun_hizmet', ['firma_id' => $firma_id, 'durum' => 'aktif'], 'urun_adi ASC');
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($urunler, JSON_UNESCAPED_UNICODE);
    exit;
}

// İstatistikleri al
$stats = getStats();

// Satış ID'sini al
$satis_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$satis_id) {
    header('Location: satislar.php');
    exit;
}

// Satış bilgilerini al
$satis = $db->select('satislar', ['id' => $satis_id])[0] ?? null;

if (!$satis) {
    header('Location: satislar.php');
    exit;
}

// Rol bazlı erişim kontrolü
if (isSatisci() && $satis['personel_id'] != $_SESSION['personel_id']) {
    // Satışçı sadece kendi satışlarını düzenleyebilir
    header('Location: satislar.php');
    exit;
}

// Satış detaylarını al
$satis_detaylar = $db->query("
    SELECT sd.*, uh.urun_adi, f.firma_adi 
    FROM satis_detay sd 
    LEFT JOIN urun_hizmet uh ON sd.urun_hizmet_id = uh.id 
    LEFT JOIN firmalar f ON sd.firma_id = f.id 
    WHERE sd.satis_id = ? 
    ORDER BY sd.id ASC
", [$satis_id]);

// Maliyetleri al
$maliyetler = $db->select('satis_maliyetler', ['satis_id' => $satis_id], 'id ASC');

// Personelleri al
$personeller = $db->select('personel', [], 'ad_soyad ASC');

// Firmaları al - ana firması olmayanlar veya alt firması olmayanlar
$firmalar = $db->query("
    SELECT f.*
    FROM firmalar f
    WHERE f.durum = 'aktif'
    AND (
        -- Ana firmaya sahip olan alt firmalar
        f.ust_firma_id IS NOT NULL
        OR
        -- Ana firma ama alt firması olmayan
        (f.ust_firma_id IS NULL AND NOT EXISTS (
            SELECT 1 FROM firmalar f2 WHERE f2.ust_firma_id = f.id AND f2.durum = 'aktif'
        ))
    )
    ORDER BY f.firma_adi ASC
");

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_satis') {
    $personel_id = !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null;
    $toplam_tutar = 0;
    
        // Satış detaylarını hesapla
        if (isset($_POST['urun_id']) && is_array($_POST['urun_id'])) {
            for ($i = 0; $i < count($_POST['urun_id']); $i++) {
                if (!empty($_POST['urun_id'][$i]) && !empty($_POST['miktar'][$i]) && !empty($_POST['fiyat'][$i])) {
                    $ara_toplam = (float)$_POST['miktar'][$i] * (float)$_POST['fiyat'][$i];
                    $indirim = (float)($_POST['indirim'][$i] ?? 0);
                    $indirim_tutari = $ara_toplam * ($indirim / 100);
                    $final_toplam = $ara_toplam - $indirim_tutari;
                    $toplam_tutar += $final_toplam;
                }
            }
        }
    
    // Müşteri bilgisini al
    $musteri_id = $_POST['musteri_id'] ?? null;
    $musteri_adi = '';
    if ($musteri_id) {
        $musteri = getMusteri($musteri_id);
        $musteri_adi = $musteri ? $musteri['firma_adi'] : '';
    }
    
    // Satışı güncelle
    $satis_data = [
        'personel_id' => $personel_id,
        'musteri_adi' => $musteri_adi,
        'satis_tarihi' => $_POST['satis_tarihi'] ?? date('Y-m-d'),
        'toplam_tutar' => $toplam_tutar,
        'durum' => $_POST['durum'],
        'onay_durumu' => 'beklemede', // Düzenleme sonrası tekrar onaya düşsün
        'onay_tarihi' => null,
        'onaylayan_id' => null
    ];
    
    $update_result = $db->update('satislar', $satis_data, ['id' => $satis_id]);
    
    if ($update_result) {
        // Mevcut satış detaylarını sil
        $db->delete('satis_detay', ['satis_id' => $satis_id]);
        
        // Mevcut maliyetleri sil
        $db->delete('satis_maliyetler', ['satis_id' => $satis_id]);
        
        // Yeni satış detaylarını ekle
        if (isset($_POST['urun_id']) && is_array($_POST['urun_id'])) {
            for ($i = 0; $i < count($_POST['urun_id']); $i++) {
                if (!empty($_POST['urun_id'][$i]) && !empty($_POST['miktar'][$i]) && !empty($_POST['fiyat'][$i])) {
                    // Firma ID'yi ürün/hizmet tablosundan al
                    $urun = $db->select('urun_hizmet', ['id' => $_POST['urun_id'][$i]])[0];
                    
                    $ara_toplam = (float)$_POST['miktar'][$i] * (float)$_POST['fiyat'][$i];
                    $indirim = (float)($_POST['indirim'][$i] ?? 0);
                    $indirim_tutari = $ara_toplam * ($indirim / 100);
                    $final_toplam = $ara_toplam - $indirim_tutari;
                    
                    $detay_data = [
                        'satis_id' => $satis_id,
                        'firma_id' => $urun['firma_id'],
                        'urun_hizmet_id' => (int)$_POST['urun_id'][$i],
                        'miktar' => (int)$_POST['miktar'][$i],
                        'birim_fiyat' => (float)$_POST['fiyat'][$i],
                        'toplam_fiyat' => $final_toplam,
                        'indirim_orani' => $indirim
                    ];
                    $db->insert('satis_detay', $detay_data);
                }
            }
        }
        
        // Maliyetleri ekle
        if (isset($_POST['maliyet_adi']) && is_array($_POST['maliyet_adi'])) {
            for ($i = 0; $i < count($_POST['maliyet_adi']); $i++) {
                if (!empty($_POST['maliyet_adi'][$i]) && !empty($_POST['maliyet_tutari'][$i])) {
                    $maliyet_data = [
                        'satis_id' => $satis_id,
                        'maliyet_adi' => $_POST['maliyet_adi'][$i],
                        'maliyet_aciklama' => $_POST['maliyet_aciklama'][$i] ?? '',
                        'maliyet_tutari' => (float)$_POST['maliyet_tutari'][$i]
                    ];
                    $db->insert('satis_maliyetler', $maliyet_data);
                }
            }
        }
        
        $success_message = "Satış başarıyla güncellendi!";
        
        // Güncellenmiş verileri tekrar al
        $satis = $db->select('satislar', ['id' => $satis_id])[0];
        $satis_detaylar = $db->query("
            SELECT sd.*, uh.urun_adi, f.firma_adi 
            FROM satis_detay sd 
            LEFT JOIN urun_hizmet uh ON sd.urun_hizmet_id = uh.id 
            LEFT JOIN firmalar f ON sd.firma_id = f.id 
            WHERE sd.satis_id = ? 
            ORDER BY sd.id ASC
        ", [$satis_id]);
        $maliyetler = $db->select('satis_maliyetler', ['satis_id' => $satis_id], 'id ASC');
    } else {
        $error_message = "Satış güncellenirken hata oluştu!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Satış Düzenle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php 
            $page_title = 'Satış Düzenle - #' . $satis['id'];
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="satislar.php" style="color: #3b82f6; text-decoration: none;">Satışlar</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Satış Düzenle #<?php echo $satis['id']; ?></span>
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

                <!-- Form -->
                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 style="font-size: 24px; font-weight: 600; color: #1e293b;">Satış Bilgileri</h2>
                        <a href="satislar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Satış Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="satis-duzenle.php?id=<?php echo $satis['id']; ?>" id="satisForm">
                        <input type="hidden" name="action" value="update_satis">
                        
                        <!-- Personel Seçimi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Personel Seçimi</label>
                            <select name="personel_id" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Personel Seçiniz</option>
                                <?php foreach ($personeller as $personel): ?>
                                    <option value="<?php echo $personel['id']; ?>" <?php echo $satis['personel_id'] == $personel['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Müşteri Seçimi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Müşteri Seçimi</label>
                            <select name="musteri_id" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                <option value="">Müşteri seçiniz</option>
                                <?php 
                                $musteriler = getMusteriler();
                                foreach ($musteriler as $musteri): 
                                ?>
                                <option value="<?php echo $musteri['id']; ?>" <?php echo ($satis['musteri_adi'] == $musteri['firma_adi']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($musteri['firma_adi'] . ' - ' . $musteri['yetkili_ad_soyad']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Firma ve Ürün/Hizmet Seçimi -->
                        <div id="firma-urun-container">
                            <?php if (!empty($satis_detaylar)): ?>
                                <?php foreach ($satis_detaylar as $index => $detay): ?>
                                    <div class="firma-urun-group" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f8fafc;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                            <h3 style="font-size: 18px; font-weight: 600; color: #1e293b;">Firma ve Ürün/Hizmet Seçimi</h3>
                                            <button type="button" onclick="removeFirmaGroup(this)" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                                <i class="fas fa-trash"></i> Kaldır
                                            </button>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Firma Seçimi *</label>
                                                <select name="firma_id[]" class="firma-select" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="loadProducts(this)">
                                                    <option value="">Firma Seçiniz</option>
                                                    <?php foreach ($firmalar as $firma): ?>
                                                        <option value="<?php echo $firma['id']; ?>" <?php echo $detay['firma_id'] == $firma['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Ürün/Hizmet Seçimi *</label>
                                                <select name="urun_id[]" class="urun-select" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="updatePrice(this)" data-selected="<?php echo $detay['urun_hizmet_id']; ?>">
                                                    <option value="">Önce firma seçiniz</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                                            <div>
                                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Miktar *</label>
                                                <input type="number" name="miktar[]" min="1" value="<?php echo $detay['miktar']; ?>" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                            </div>
                                            
                                            <div>
                                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Birim Fiyat (KDV Dahil - ₺)</label>
                                                <input type="number" name="fiyat[]" step="0.01" min="0" value="<?php echo $detay['birim_fiyat'] * 1.20; ?>" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                            </div>
                                            
                                            <div>
                                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">İndirim (%)</label>
                                                <input type="number" name="indirim[]" step="0.01" min="0" max="100" value="<?php echo $detay['indirim_orani'] ?? 0; ?>" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                            </div>
                                            
                                            <div>
                                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Toplam (₺)</label>
                                                <input type="number" name="toplam[]" step="0.01" value="<?php echo $detay['toplam_fiyat']; ?>" readonly style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f8fafc; font-weight: 600;">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="firma-urun-group" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; background: #f8fafc;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                        <h3 style="font-size: 18px; font-weight: 600; color: #1e293b;">Firma ve Ürün/Hizmet Seçimi</h3>
                                        <button type="button" onclick="removeFirmaGroup(this)" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                            <i class="fas fa-trash"></i> Kaldır
                                        </button>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Firma Seçimi *</label>
                                            <select name="firma_id[]" class="firma-select" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="loadProducts(this)">
                                                <option value="">Firma Seçiniz</option>
                                                <?php foreach ($firmalar as $firma): ?>
                                                    <option value="<?php echo $firma['id']; ?>">
                                                        <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Ürün/Hizmet Seçimi *</label>
                                            <select name="urun_id[]" class="urun-select" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="updatePrice(this)">
                                                <option value="">Önce firma seçiniz</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Miktar *</label>
                                            <input type="number" name="miktar[]" min="1" value="1" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                        </div>
                                        
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Birim Fiyat (KDV Dahil - ₺)</label>
                                            <input type="number" name="fiyat[]" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                        </div>
                                        
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">İndirim (%)</label>
                                            <input type="number" name="indirim[]" step="0.01" min="0" max="100" value="0" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                        </div>
                                        
                                        <div>
                                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Toplam (₺)</label>
                                            <input type="number" name="toplam[]" step="0.01" readonly style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f8fafc; font-weight: 600;">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Yeni Ürün/Hizmet Ekle Butonu -->
                        <div style="margin-bottom: 30px;">
                            <button type="button" onclick="addFirmaGroup()" class="btn btn-secondary">
                                <i class="fas fa-plus"></i>
                                Yeni Ürün/Hizmet Ekle
                            </button>
                        </div>
                        
                        <!-- Maliyetler -->
                        <div style="margin-bottom: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <label style="font-weight: 600; color: #374151; font-size: 16px;">Maliyetler</label>
                                <button type="button" id="addMaliyet" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                                    <i class="fas fa-plus"></i>
                                    Yeni Maliyet Ekle
                                </button>
                            </div>
                            
                            <div id="maliyetContainer">
                                <?php if (!empty($maliyetler)): ?>
                                    <?php foreach ($maliyetler as $maliyet): ?>
                                        <div class="maliyet-item" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 14px;">Maliyet Adı</label>
                                                    <input type="text" name="maliyet_adi[]" value="<?php echo htmlspecialchars($maliyet['maliyet_adi']); ?>" placeholder="Örn: Reklam, Komisyon" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" required>
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 14px;">Açıklama</label>
                                                    <input type="text" name="maliyet_aciklama[]" value="<?php echo htmlspecialchars($maliyet['maliyet_aciklama']); ?>" placeholder="Maliyet açıklaması" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                                                </div>
                                                <div>
                                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 14px;">Tutar (₺)</label>
                                                    <input type="number" name="maliyet_tutari[]" step="0.01" min="0" value="<?php echo $maliyet['maliyet_tutari']; ?>" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" onchange="updateMaliyetToplam()" required>
                                                </div>
                                                <div>
                                                    <button type="button" onclick="removeMaliyet(this)" style="background: #dc2626; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 600; color: #374151;">Toplam Maliyet:</span>
                                    <span id="toplamMaliyet" style="font-weight: 700; color: #dc2626; font-size: 18px;">₺0,00</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Satış Tarihi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Satış Tarihi</label>
                            <input type="date" name="satis_tarihi" value="<?php echo $satis['satis_tarihi'] ?? date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <!-- Ödeme Durumu ve Genel Toplam -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Ödeme Durumu</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="odendi" <?php echo $satis['durum'] == 'odendi' ? 'selected' : ''; ?>>Ödendi</option>
                                    <option value="odenmedi" <?php echo $satis['durum'] == 'odenmedi' ? 'selected' : ''; ?>>Ödenmedi</option>
                                    <option value="odeme_bekleniyor" <?php echo $satis['durum'] == 'odeme_bekleniyor' ? 'selected' : ''; ?>>Ödeme Bekleniyor</option>
                                    <option value="iptal" <?php echo $satis['durum'] == 'iptal' ? 'selected' : ''; ?>>İptal</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Genel Toplam (₺)</label>
                                <input type="number" id="genel-toplam" step="0.01" value="<?php echo $satis['toplam_tutar']; ?>" readonly style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f8fafc; font-weight: 600; font-size: 18px;">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="satislar.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Satış Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Firma seçildiğinde ürün/hizmetleri yükle
        function loadProducts(selectElement) {
            const firmaId = selectElement.value;
            const urunSelect = selectElement.closest('.firma-urun-group').querySelector('.urun-select');
            const selectedId = urunSelect.dataset.selected;
            
            if (firmaId) {
                fetch(`satis-duzenle.php?action=get_products&firma_id=${firmaId}`)
                    .then(response => response.json())
                    .then(products => {
                        urunSelect.innerHTML = '<option value="">Ürün/Hizmet Seçiniz</option>';
                        products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.urun_adi;
                            option.dataset.price = product.fiyat || 0;
                            
                            // Mevcut seçimi koru
                            if (selectedId && product.id == selectedId) {
                                option.selected = true;
                                // Fiyatı da güncelle
                                const priceInput = selectElement.closest('.firma-urun-group').querySelector('input[name="fiyat[]"]');
                                priceInput.value = product.fiyat || 0;
                                calculateTotal(priceInput);
                            }
                            
                            urunSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        urunSelect.innerHTML = '<option value="">Hata oluştu</option>';
                    });
            } else {
                urunSelect.innerHTML = '<option value="">Önce firma seçiniz</option>';
            }
        }

        // Ürün seçildiğinde fiyatı güncelle (KDV dahil)
        function updatePrice(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const priceInput = selectElement.closest('.firma-urun-group').querySelector('input[name="fiyat[]"]');
            
            if (selectedOption.dataset.price) {
                // KDV hariç fiyatı al ve %20 KDV ekle
                const kdvHariçFiyat = parseFloat(selectedOption.dataset.price);
                const kdvDahilFiyat = kdvHariçFiyat * 1.20; // %20 KDV
                priceInput.value = kdvDahilFiyat.toFixed(2);
                calculateTotal(priceInput);
            }
        }

        // Toplam hesapla
        function calculateTotal(inputElement) {
            const group = inputElement.closest('.firma-urun-group');
            const miktar = group.querySelector('input[name="miktar[]"]').value || 0;
            const fiyat = group.querySelector('input[name="fiyat[]"]').value || 0;
            const indirim = group.querySelector('input[name="indirim[]"]').value || 0;
            const toplam = group.querySelector('input[name="toplam[]"]');
            
            // Ara toplam hesapla
            const araToplam = parseFloat(miktar) * parseFloat(fiyat);
            
            // İndirim hesapla
            const indirimTutari = araToplam * (parseFloat(indirim) / 100);
            
            // Final toplam
            const finalToplam = araToplam - indirimTutari;
            
            toplam.value = finalToplam.toFixed(2);
            updateGenelToplam();
        }

        // Genel toplamı güncelle
        function updateGenelToplam() {
            const toplamInputs = document.querySelectorAll('input[name="toplam[]"]');
            let genelToplam = 0;
            
            toplamInputs.forEach(input => {
                if (input.value) {
                    genelToplam += parseFloat(input.value);
                }
            });
            
            document.getElementById('genel-toplam').value = genelToplam.toFixed(2);
        }

        // Yeni firma grubu ekle
        function addFirmaGroup() {
            const container = document.getElementById('firma-urun-container');
            const template = container.querySelector('.firma-urun-group').cloneNode(true);
            
            // Input değerlerini temizle
            template.querySelectorAll('input, select').forEach(input => {
                if (input.type === 'text' || input.type === 'number') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
            });
            
            container.appendChild(template);
        }

        // Firma grubunu kaldır
        function removeFirmaGroup(button) {
            const container = document.getElementById('firma-urun-container');
            if (container.children.length > 1) {
                button.closest('.firma-urun-group').remove();
                updateGenelToplam();
            } else {
                alert('En az bir firma seçimi olmalıdır!');
            }
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

        // Sayfa yüklendiğinde mevcut firmaların ürün/hizmetlerini yükle
        document.addEventListener('DOMContentLoaded', function() {
            const firmaSelects = document.querySelectorAll('.firma-select');
            firmaSelects.forEach(select => {
                if (select.value) {
                    loadProducts(select);
                }
            });
        });

        // Maliyet yönetimi
        let maliyetCounter = 0;

        // Yeni maliyet ekle
        function addMaliyet() {
            maliyetCounter++;
            const container = document.getElementById('maliyetContainer');
            
            const maliyetDiv = document.createElement('div');
            maliyetDiv.className = 'maliyet-item';
            maliyetDiv.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 10px;';
            
            maliyetDiv.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 14px;">Maliyet Adı</label>
                        <input type="text" name="maliyet_adi[]" placeholder="Örn: Reklam, Komisyon" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 14px;">Açıklama</label>
                        <input type="text" name="maliyet_aciklama[]" placeholder="Maliyet açıklaması" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #374151; font-size: 14px;">Tutar (₺)</label>
                        <input type="number" name="maliyet_tutari[]" step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;" onchange="updateMaliyetToplam()" required>
                    </div>
                    <div>
                        <button type="button" onclick="removeMaliyet(this)" style="background: #dc2626; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(maliyetDiv);
        }

        // Maliyet kaldır
        function removeMaliyet(button) {
            button.closest('.maliyet-item').remove();
            updateMaliyetToplam();
        }

        // Maliyet toplamını güncelle
        function updateMaliyetToplam() {
            const maliyetInputs = document.querySelectorAll('input[name="maliyet_tutari[]"]');
            let toplam = 0;
            
            maliyetInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                toplam += value;
            });
            
            document.getElementById('toplamMaliyet').textContent = '₺' + toplam.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Maliyet ekleme butonuna event listener ekle
        document.getElementById('addMaliyet').addEventListener('click', addMaliyet);
        
        // Sayfa yüklendiğinde maliyet toplamını hesapla
        document.addEventListener('DOMContentLoaded', function() {
            updateMaliyetToplam();
        });
    </script>
</body>
</html>
