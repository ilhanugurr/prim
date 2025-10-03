<?php
/**
 * Primew Panel - Kategori Düzenleme
 * Envanter kategorisi düzenleme sayfası
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// Admin kontrolü
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

// İstatistikleri al
$stats = getStats();

// Kategori ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: envanter-kategoriler.php");
    exit;
}

$kategori_id = (int)$_GET['id'];

// Kategori bilgilerini al
$kategori = $db->select('envanter_kategoriler', ['id' => $kategori_id]);
if (empty($kategori)) {
    header("Location: envanter-kategoriler.php");
    exit;
}
$kategori = $kategori[0];

// Form gönderildi mi?
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_kategori') {
    $data = [
        'kategori_adi' => trim($_POST['kategori_adi']),
        'aciklama' => trim($_POST['aciklama']),
        'renk' => $_POST['renk'],
        'durum' => $_POST['durum']
    ];
    
    // Validasyon
    $errors = [];
    if (empty($data['kategori_adi'])) {
        $errors[] = "Kategori adı zorunludur!";
    }
    if (strlen($data['kategori_adi']) > 100) {
        $errors[] = "Kategori adı 100 karakterden uzun olamaz!";
    }
    
    // Kategori adı benzersiz mi kontrol et (kendisi hariç)
    if (empty($errors)) {
        $existing = $db->query("SELECT id FROM envanter_kategoriler WHERE kategori_adi = ? AND id != ?", [$data['kategori_adi'], $kategori_id]);
        if (!empty($existing)) {
            $errors[] = "Bu kategori adı zaten kullanılıyor!";
        }
    }
    
    if (empty($errors)) {
        if ($db->update('envanter_kategoriler', $data, ['id' => $kategori_id])) {
            $success_message = "Kategori başarıyla güncellendi!";
            // Güncellenmiş veriyi al
            $kategori = $db->select('envanter_kategoriler', ['id' => $kategori_id])[0];
        } else {
            $error_message = "Kategori güncellenirken hata oluştu!";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Önceden tanımlı renkler
$renkler = [
    '#3b82f6' => 'Mavi',
    '#10b981' => 'Yeşil',
    '#f59e0b' => 'Turuncu',
    '#ef4444' => 'Kırmızı',
    '#8b5cf6' => 'Mor',
    '#06b6d4' => 'Cyan',
    '#84cc16' => 'Lime',
    '#f97316' => 'Orange',
    '#ec4899' => 'Pink',
    '#6b7280' => 'Gray'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Kategori Düzenle</title>
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
            $page_title = 'Kategori Düzenle';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="envanter.php" style="color: #3b82f6; text-decoration: none;">Envanter</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="envanter-kategoriler.php" style="color: #3b82f6; text-decoration: none;">Kategoriler</span>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Kategori Düzenle</span>
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
                        <h2 style="font-size: 24px; font-weight: 600; color: #1e293b;">Kategori Düzenle</h2>
                        <a href="envanter-kategoriler.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Kategori Listesi
                        </a>
                    </div>
                    
                    <form method="POST" action="envanter-kategori-duzenle.php?id=<?php echo $kategori_id; ?>">
                        <input type="hidden" name="action" value="update_kategori">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Kategori Adı *</label>
                                <input type="text" name="kategori_adi" value="<?php echo htmlspecialchars($kategori['kategori_adi']); ?>" required 
                                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                       placeholder="Örn: Teknoloji, Mobilya, Ofis Malzemeleri" maxlength="100">
                            </div>
                            
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Durum</label>
                                <select name="durum" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                                    <option value="aktif" <?php echo $kategori['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $kategori['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Açıklama</label>
                            <textarea name="aciklama" rows="3" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;" 
                                      placeholder="Kategori hakkında detaylı açıklama"><?php echo htmlspecialchars($kategori['aciklama']); ?></textarea>
                        </div>
                        
                        <!-- Renk Seçimi -->
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; margin-bottom: 15px; font-weight: 600; color: #374151;">Kategori Rengi</label>
                            
                            <!-- Önceden Tanımlı Renkler -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px;">
                                <?php foreach ($renkler as $renk_kodu => $renk_adi): ?>
                                    <label style="display: flex; align-items: center; gap: 10px; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; <?php echo $kategori['renk'] == $renk_kodu ? 'border-color: ' . $renk_kodu . '; background: ' . $renk_kodu . '15;' : ''; ?>" 
                                           onmouseover="this.style.borderColor='<?php echo $renk_kodu; ?>'; this.style.backgroundColor='<?php echo $renk_kodu; ?>15';" 
                                           onmouseout="if (!this.querySelector('input').checked) { this.style.borderColor='#e2e8f0'; this.style.backgroundColor='transparent'; }">
                                        <input type="radio" name="renk" value="<?php echo $renk_kodu; ?>" 
                                               <?php echo $kategori['renk'] == $renk_kodu ? 'checked' : ''; ?>
                                               style="margin: 0;" onchange="updateColorPreview()">
                                        <div style="width: 20px; height: 20px; background: <?php echo $renk_kodu; ?>; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
                                        <span style="font-weight: 500; color: #374151;"><?php echo $renk_adi; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Özel Renk Girişi -->
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Özel Renk (Hex Kodu)</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="color" id="custom_color" value="<?php echo $kategori['renk']; ?>" 
                                           style="width: 60px; height: 40px; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;" 
                                           onchange="selectCustomColor()">
                                    <input type="text" id="custom_color_text" value="<?php echo $kategori['renk']; ?>" 
                                           style="flex: 1; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: monospace;"
                                           placeholder="#3b82f6" maxlength="7" onchange="validateCustomColor()">
                                    <button type="button" onclick="applyCustomColor()" class="btn btn-secondary" style="padding: 12px 20px;">
                                        <i class="fas fa-check"></i> Uygula
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Renk Önizleme -->
                            <div style="margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <span style="font-weight: 600; color: #374151;">Önizleme:</span>
                                    <div id="color_preview" style="display: flex; align-items: center; gap: 10px; padding: 8px 15px; border-radius: 20px; background: <?php echo $kategori['renk']; ?>15; border: 2px solid <?php echo $kategori['renk']; ?>;">
                                        <div style="width: 12px; height: 12px; background: <?php echo $kategori['renk']; ?>; border-radius: 50%;"></div>
                                        <span style="font-weight: 500; color: #374151;"><?php echo htmlspecialchars($kategori['kategori_adi']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mevcut Bilgiler -->
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 15px;">Mevcut Bilgiler</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                                <div>
                                    <span style="color: #64748b;">Oluşturma Tarihi:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($kategori['olusturma_tarihi'])); ?></span>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Son Güncelleme:</span>
                                    <span style="color: #1e293b; font-weight: 500;"><?php echo date('d.m.Y H:i', strtotime($kategori['son_guncelleme'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; justify-content: flex-end;">
                            <a href="envanter-kategoriler.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Kategori Güncelle</button>
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

        function updateColorPreview() {
            const selectedColor = document.querySelector('input[name="renk"]:checked').value;
            const preview = document.getElementById('color_preview');
            preview.style.backgroundColor = selectedColor + '15';
            preview.style.borderColor = selectedColor;
            preview.querySelector('div').style.backgroundColor = selectedColor;
        }

        function selectCustomColor() {
            const colorInput = document.getElementById('custom_color');
            const textInput = document.getElementById('custom_color_text');
            textInput.value = colorInput.value;
        }

        function validateCustomColor() {
            const textInput = document.getElementById('custom_color_text');
            const colorInput = document.getElementById('custom_color');
            const value = textInput.value;
            
            // Hex kodu validasyonu
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                colorInput.value = value;
                textInput.style.borderColor = '#10b981';
            } else {
                textInput.style.borderColor = '#ef4444';
            }
        }

        function applyCustomColor() {
            const textInput = document.getElementById('custom_color_text');
            const value = textInput.value;
            
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                // Önceki seçimleri kaldır
                document.querySelectorAll('input[name="renk"]').forEach(input => {
                    input.checked = false;
                    input.closest('label').style.borderColor = '#e2e8f0';
                    input.closest('label').style.backgroundColor = 'transparent';
                });
                
                // Özel rengi seç
                const customRadio = document.createElement('input');
                customRadio.type = 'radio';
                customRadio.name = 'renk';
                customRadio.value = value;
                customRadio.checked = true;
                customRadio.onchange = updateColorPreview;
                
                updateColorPreview();
                
                // Geçici mesaj göster
                const preview = document.getElementById('color_preview');
                const originalHTML = preview.innerHTML;
                preview.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check"></i> Özel renk uygulandı!</span>';
                setTimeout(() => {
                    preview.innerHTML = originalHTML;
                    updateColorPreview();
                }, 2000);
            } else {
                alert('Geçerli bir hex renk kodu girin (örn: #3b82f6)');
            }
        }

        // Sayfa yüklendiğinde önizlemeyi güncelle
        document.addEventListener('DOMContentLoaded', function() {
            updateColorPreview();
        });

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
