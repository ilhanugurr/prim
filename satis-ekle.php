<?php
/**
 * Primew Panel - Satış Ekleme
 * Yeni satış ekleme sayfası (personel seçimi, firma seçimi, ürün/hizmet seçimi ile)
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

// Personelleri al (rol bazlı filtreleme)
if (hasPagePermission('satislar', 'ekleme')) {
    // Yetkili kullanıcı tüm personelleri görür
    $personeller = $db->select('personel', [], 'ad_soyad ASC');
} else {
    // Satışçı sadece kendini görür
    $personeller = $db->select('personel', ['id' => $_SESSION['personel_id']], 'ad_soyad ASC');
}

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

// Bankaları al
$bankalar = $db->select('bankalar', ['durum' => 'aktif'], 'banka_adi ASC');

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_satis') {
    $personel_id = !empty($_POST['personel_id']) ? (int)$_POST['personel_id'] : null;
    
    // Rol bazlı personel ID kontrolü
    if (isSatisci()) {
        // Satışçı sadece kendi adına satış ekleyebilir
        $personel_id = $_SESSION['personel_id'];
    }
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
    
    // Debug için POST verilerini logla
    error_log("POST Data: " . print_r($_POST, true));
    error_log("Toplam Tutar: " . $toplam_tutar);
    
    // Müşteri bilgisini al
    $musteri_id = $_POST['musteri_id'] ?? null;
    $musteri_adi = '';
    if ($musteri_id) {
        $musteri = getMusteri($musteri_id);
        $musteri_adi = $musteri ? $musteri['firma_adi'] : '';
    }
    
    // Satışı ekle
    $satis_data = [
        'personel_id' => $personel_id,
        'musteri_adi' => $musteri_adi,
        'satis_tarihi' => $_POST['satis_tarihi'] ?? date('Y-m-d'),
        'toplam_tutar' => $toplam_tutar,
        'durum' => $_POST['durum'],
        'onay_durumu' => 'beklemede'  // Yeni satışlar otomatik beklemede
    ];
    
    // Banka_id kolonunu kontrol et ve varsa ekle
    $columns = $db->query("SHOW COLUMNS FROM satislar LIKE 'banka_id'");
    if (!empty($columns) && !empty($_POST['banka_id'])) {
        $satis_data['banka_id'] = (int)$_POST['banka_id'];
    }
    
    $satis_id = $db->insert('satislar', $satis_data);
    
    if ($satis_id) {
        // Satış detaylarını ekle
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
        
        $success_message = "Satış başarıyla eklendi!";
    } else {
        $error_message = "Satış eklenirken hata oluştu!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Satış Ekle</title>
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
            $page_title = 'Yeni Satış Ekle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="satislar.php" style="color: #3b82f6; text-decoration: none;">Satışlar</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Yeni Satış Ekle</span>
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
                <div style="background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid var(--border-color); margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h2 class="text-primary" style="font-size: 24px; font-weight: 600;">Yeni Satış Bilgileri</h2>
                        <a href="satislar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Satış Listesi
                        </a>
                    </div>
                    
                    <?php if (!isAdmin()): ?>
                    <!-- Satışçı için bilgi mesajı -->
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle"></i>
                        <span>Bu satış <strong><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></strong> adına kaydedilecektir.</span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="satis-ekle.php" id="satisForm">
                        <input type="hidden" name="action" value="add_satis">
                        
                        <!-- Personel Seçimi -->
                        <?php if (isAdmin()): ?>
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Personel Seçimi</label>
                            <select name="personel_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                <option value="">Personel Seçiniz</option>
                                <?php foreach ($personeller as $personel): ?>
                                    <option value="<?php echo $personel['id']; ?>">
                                        <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <!-- Satışçı için gizli personel ID -->
                        <input type="hidden" name="personel_id" value="<?php echo $_SESSION['personel_id']; ?>">
                        <?php endif; ?>
                        
                        <!-- Müşteri Seçimi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Müşteri Seçimi</label>
                            <select name="musteri_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                <option value="">Müşteri seçiniz</option>
                                <?php 
                                $musteriler = getMusteriler();
                                foreach ($musteriler as $musteri): 
                                ?>
                                <option value="<?php echo $musteri['id']; ?>">
                                    <?php echo htmlspecialchars($musteri['firma_adi'] . ' - ' . $musteri['yetkili_ad_soyad']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Ödeme Yeri Seçimi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ödeme Nereye Yapıldı</label>
                            <select name="banka_id" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                <option value="">Banka seçiniz</option>
                                <?php foreach ($bankalar as $banka): ?>
                                <option value="<?php echo $banka['id']; ?>">
                                    <?php echo htmlspecialchars($banka['banka_adi']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Firma ve Ürün/Hizmet Seçimi -->
                        <div id="firma-urun-container">
                            <div class="firma-urun-group" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 20px; background: var(--bg-secondary);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary);">Firma ve Ürün/Hizmet Seçimi</h3>
                                    <button type="button" onclick="removeFirmaGroup(this)" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fas fa-trash"></i> Kaldır
                                    </button>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Firma Seçimi *</label>
                                        <select name="firma_id[]" class="firma-select" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" onchange="loadProducts(this)">
                                            <option value="">Firma Seçiniz</option>
                                            <?php foreach ($firmalar as $firma): ?>
                                                <option value="<?php echo $firma['id']; ?>">
                                                    <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ürün/Hizmet Seçimi *</label>
                                        <select name="urun_id[]" class="urun-select" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" onchange="updatePrice(this)">
                                            <option value="">Önce firma seçiniz</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Miktar *</label>
                                        <input type="number" name="miktar[]" min="1" value="1" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Birim Fiyat (KDV Dahil - ₺)</label>
                                        <input type="number" name="fiyat[]" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">İndirim (%)</label>
                                        <input type="number" name="indirim[]" step="0.01" min="0" max="100" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;" onchange="calculateTotal(this)">
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Toplam (₺)</label>
                                        <input type="number" name="toplam[]" step="0.01" readonly style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: var(--bg-secondary); font-weight: 600;">
                                    </div>
                                </div>
                            </div>
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
                                <label style="font-weight: 600; color: var(--text-primary); font-size: 16px;">Maliyetler</label>
                                <button type="button" id="addMaliyet" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px;">
                                    <i class="fas fa-plus"></i>
                                    Yeni Maliyet Ekle
                                </button>
                            </div>
                            
                            <div id="maliyetContainer">
                                <!-- Maliyet alanları buraya dinamik olarak eklenecek -->
                            </div>
                            
                            <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 600; color: var(--text-primary);">Toplam Maliyet:</span>
                                    <span id="toplamMaliyet" style="font-weight: 700; color: #dc2626; font-size: 18px;">₺0,00</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Satış Tarihi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Satış Tarihi</label>
                            <input type="date" name="satis_tarihi" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                        </div>
                        
                        <!-- Ödeme Durumu ve Genel Toplam -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Ödeme Durumu</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                                    <option value="odendi">Ödendi</option>
                                    <option value="odenmedi">Ödenmedi</option>
                                    <option value="odeme_bekleniyor">Ödeme Bekleniyor</option>
                                    <option value="iptal">İptal</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">Genel Toplam (₺)</label>
                                <input type="number" id="genel-toplam" step="0.01" readonly style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: var(--bg-secondary); font-weight: 600; font-size: 18px;">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="satislar.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Satış Kaydet
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
            
            if (firmaId) {
                fetch(`satis-ekle.php?action=get_products&firma_id=${firmaId}`)
                    .then(response => response.json())
                    .then(products => {
                        urunSelect.innerHTML = '<option value="">Ürün/Hizmet Seçiniz</option>';
                        products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.urun_adi;
                            option.dataset.price = product.fiyat || 0;
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
            const firstGroup = container.querySelector('.firma-urun-group');
            const template = firstGroup.cloneNode(true);
            
            // Input değerlerini temizle
            template.querySelectorAll('input, select').forEach(input => {
                if (input.type === 'text' || input.type === 'number') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
            });
            
            // Event listener'ları yeniden ekle
            template.querySelector('.firma-select').onchange = function() { loadProducts(this); };
            template.querySelector('.urun-select').onchange = function() { updatePrice(this); };
            template.querySelector('input[name="miktar[]"]').onchange = function() { calculateTotal(this); };
            
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

        // Maliyet yönetimi
        let maliyetCounter = 0;

        // Yeni maliyet ekle
        function addMaliyet() {
            maliyetCounter++;
            const container = document.getElementById('maliyetContainer');
            
            const maliyetDiv = document.createElement('div');
            maliyetDiv.className = 'maliyet-item';
            maliyetDiv.style.cssText = 'background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; margin-bottom: 10px;';
            
            maliyetDiv.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-primary); font-size: 14px;">Maliyet Adı</label>
                        <input type="text" name="maliyet_adi[]" placeholder="Örn: Reklam, Komisyon" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-primary); font-size: 14px;">Açıklama</label>
                        <input type="text" name="maliyet_aciklama[]" placeholder="Maliyet açıklaması" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-primary); font-size: 14px;">Tutar (₺)</label>
                        <input type="number" name="maliyet_tutari[]" step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px;" onchange="updateMaliyetToplam()" required>
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
    </script>
</body>
</html>
