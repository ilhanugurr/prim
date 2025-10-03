<?php
/**
 * Primew Panel - Ayarlar Sayfası
 * Role ve sayfa izinleri yönetimi
 */

// UTF-8 encoding - EN BAŞTA OLMALI
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş ve admin kontrolü
requireLogin();
requireAdmin();

// İstatistikleri al
$stats = getStats();

$success_message = '';
$error_message = '';

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_role':
                    if (!empty($_POST['rol_adi'])) {
                        $stmt = $pdo->prepare("INSERT INTO roller (rol_adi, aciklama, renk) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $_POST['rol_adi'],
                            $_POST['aciklama'] ?? '',
                            $_POST['renk'] ?? '#3b82f6'
                        ]);
                        $success_message = "Yeni rol başarıyla eklendi!";
                    }
                    break;
                    
                case 'update_permissions':
                    try {
                        // Mevcut izinleri sil
                        $stmt = $pdo->prepare("DELETE FROM rol_sayfa_izinleri WHERE rol_id = ?");
                        $stmt->execute([$_POST['rol_id']]);
                        
                        // Yeni izinleri ekle (sadece boş olmayanları)
                        if (isset($_POST['permissions'])) {
                            $stmt = $pdo->prepare("INSERT INTO rol_sayfa_izinleri (rol_id, sayfa_id, yetki_tipi) VALUES (?, ?, ?)");
                            foreach ($_POST['permissions'] as $sayfa_id => $yetki_tipi) {
                                if (!empty($yetki_tipi)) {
                                    $stmt->execute([$_POST['rol_id'], $sayfa_id, $yetki_tipi]);
                                }
                            }
                        }
                        $success_message = "İzinler başarıyla güncellendi!";
                    } catch (Exception $e) {
                        $error_message = "İzinler güncellenirken hata oluştu: " . $e->getMessage();
                    }
                    break;
                    
                case 'delete_role':
                    // Bu rolü kullanan personel var mı kontrol et
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM personel WHERE rol = ?");
                    $stmt->execute([$_POST['rol_adi']]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $error_message = "Bu rolü kullanan personel bulunduğu için silinemez!";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM roller WHERE id = ?");
                        $stmt->execute([$_POST['rol_id']]);
                        $success_message = "Rol başarıyla silindi!";
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}

// Rolleri getir
$stmt = $pdo->query("SELECT * FROM roller ORDER BY rol_adi");
$roller = $stmt->fetchAll();

// Sayfaları getir
$stmt = $pdo->query("SELECT * FROM sayfalar ORDER BY menu_adi");
$sayfalar = $stmt->fetchAll();

// Rol izinlerini getir
$role_permissions = [];
if (!empty($roller)) {
    $stmt = $pdo->query("SELECT rol_id, sayfa_id, yetki_tipi FROM rol_sayfa_izinleri");
    while ($row = $stmt->fetch()) {
        $role_permissions[$row['rol_id']][$row['sayfa_id']] = $row['yetki_tipi'];
    }
}

// UTF-8 encoding ayarları
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Ayarlar</title>
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
            $page_title = 'Ayarlar';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Ayarlar</span>
                    </nav>
                </div>

                <!-- Başarı/Hata Mesajları -->
                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button class="tab-btn active" onclick="switchTab('auth')">
                        <i class="fas fa-shield-alt"></i>
                        Yetkilendirme
                    </button>
                    <button class="tab-btn" onclick="switchTab('general')">
                        <i class="fas fa-cog"></i>
                        Genel Ayarlar
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Yetkilendirme Tab -->
                    <div id="auth-tab" class="tab-panel active">
                        <!-- Yeni Rol Ekleme -->
                        <div class="white-card-small" style="padding: 20px; margin-bottom: 30px;">
                            <h3 class="text-primary" style="font-size: 18px; font-weight: 600; margin-bottom: 20px;">
                                Yeni Rol Ekle
                            </h3>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="add_role">
                                <div class="form-group">
                                    <label class="form-label">Rol Adı *</label>
                                    <input type="text" name="rol_adi" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Açıklama</label>
                                    <input type="text" name="aciklama" class="form-input" placeholder="Rol açıklaması">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Renk</label>
                                    <input type="color" name="renk" class="form-input" value="#3b82f6">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Rol Ekle
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Roller Listesi ve İzin Yönetimi -->
                        <div class="white-card" style="padding: 30px;">
                            <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">
                                <i class="fas fa-users-cog"></i> Mevcut Roller ve İzinler
                            </h2>

                            <?php foreach ($roller as $rol): ?>
                            <div class="role-card">
                                <div class="role-header">
                                    <div class="role-info">
                                        <div class="role-color" style="background: <?php echo htmlspecialchars($rol['renk']); ?>;"></div>
                                        <div>
                                            <h4 class="text-primary role-name">
                                                <?php echo htmlspecialchars($rol['rol_adi']); ?>
                                            </h4>
                                            <?php if ($rol['aciklama']): ?>
                                    <p class="text-secondary role-description">
                                        <?php echo htmlspecialchars($rol['aciklama']); ?>
                                    </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="role-actions">
                                        <?php if (!in_array($rol['rol_adi'], ['admin', 'satisci'])): ?>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Bu rolü silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete_role">
                                            <input type="hidden" name="rol_id" value="<?php echo $rol['id']; ?>">
                                            <input type="hidden" name="rol_adi" value="<?php echo htmlspecialchars($rol['rol_adi']); ?>">
                                            <button type="submit" class="btn-secondary btn-small">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn-secondary btn-small" onclick="togglePermissions(<?php echo $rol['id']; ?>)">
                                            <i class="fas fa-key"></i>
                                            İzinler
                                        </button>
                                    </div>
                                </div>

                                <!-- İzin Yönetimi Formu (Gizli) -->
                                <div id="permissions-<?php echo $rol['id']; ?>" class="permissions-form">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_permissions">
                                        <input type="hidden" name="rol_id" value="<?php echo $rol['id']; ?>">
                                        
                                        <div class="permissions-grid">
                                            <?php foreach ($sayfalar as $sayfa): ?>
                                            <div class="permission-item">
                                                <div class="permission-header">
                                                    <i class="fas <?php echo htmlspecialchars($sayfa['sayfa_icon']); ?>" class="permission-icon"></i>
                                    <span class="text-primary permission-title">
                                        <?php echo htmlspecialchars($sayfa['menu_adi']); ?>
                                    </span>
                                                </div>
                                                <select name="permissions[<?php echo $sayfa['id']; ?>]" class="form-select">
                                                    <option value="">İzin Yok</option>
                                                    <option value="goruntuleme" <?php echo (isset($role_permissions[$rol['id']][$sayfa['id']]) && $role_permissions[$rol['id']][$sayfa['id']] === 'goruntuleme') ? 'selected' : ''; ?>>
                                                        Sadece Görüntüleme
                                                    </option>
                                                    <option value="ekleme" <?php echo (isset($role_permissions[$rol['id']][$sayfa['id']]) && $role_permissions[$rol['id']][$sayfa['id']] === 'ekleme') ? 'selected' : ''; ?>>
                                                        Ekleme
                                                    </option>
                                                    <option value="duzenleme" <?php echo (isset($role_permissions[$rol['id']][$sayfa['id']]) && $role_permissions[$rol['id']][$sayfa['id']] === 'duzenleme') ? 'selected' : ''; ?>>
                                                        Düzenleme
                                                    </option>
                                                    <option value="silme" <?php echo (isset($role_permissions[$rol['id']][$sayfa['id']]) && $role_permissions[$rol['id']][$sayfa['id']] === 'silme') ? 'selected' : ''; ?>>
                                                        Silme
                                                    </option>
                                                    <option value="tam_yetki" <?php echo (isset($role_permissions[$rol['id']][$sayfa['id']]) && $role_permissions[$rol['id']][$sayfa['id']] === 'tam_yetki') ? 'selected' : ''; ?>>
                                                        Tam Yetki
                                                    </option>
                                                </select>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="permissions-save-section">
                                            <button type="submit" class="btn btn-primary" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Kaydediliyor...'; this.disabled=true; this.form.submit();">
                                                <i class="fas fa-save"></i>
                                                İzinleri Kaydet
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Genel Ayarlar Tab -->
                    <div id="general-tab" class="tab-panel">
                        <div class="white-card" style="padding: 30px;">
                            <h2 class="text-primary" style="font-size: 24px; font-weight: 600; margin-bottom: 30px;">
                                <i class="fas fa-cog"></i>
                                Genel Ayarlar
                            </h2>
                            <div class="text-secondary" style="text-align: center; padding: 60px;">
                                <i class="fas fa-tools" style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px;"></i>
                                <h3 class="text-primary" style="font-size: 20px; margin-bottom: 8px;">Genel Ayarlar</h3>
                                <p>Bu bölüm yakında kullanıma sunulacak.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function switchTab(tabName) {
        // Tüm tab panellerini gizle
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        
        // Tüm tab butonlarını pasif yap
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Seçilen tab'ı aktif yap
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }

    function togglePermissions(roleId) {
        const permissionsForm = document.getElementById('permissions-' + roleId);
        permissionsForm.classList.toggle('active');
    }

    // Sayfa yüklendiğinde
    document.addEventListener('DOMContentLoaded', function() {
        // İlk tab'ı aktif yap
        switchTab('auth');
        
        // Tüm izin formlarını başlangıçta gizle
        document.querySelectorAll('.permissions-form').forEach(form => {
            form.classList.remove('active');
        });
    });
    </script>
</body>
</html>