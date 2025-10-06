<?php
/**
 * Primew Panel - Personel Prim Oranları Yönetimi
 * Personel bazlı, aylık, firma bazlı prim oranları
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

// Giriş kontrolü
requireLogin();

// Sadece admin prim oranlarını yönetebilir
if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

// İstatistikleri al
$stats = getStats();

// Personel, firma ve ay bilgilerini al
$personeller = $db->select('personel', [], 'ad_soyad ASC');
$firmalar = $db->select('firmalar', [], 'firma_adi ASC');

$aylar = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
];

// Filtreleme
$selected_personel = isset($_GET['personel_id']) ? (int)$_GET['personel_id'] : 0;
$selected_firma = isset($_GET['firma_id']) ? (int)$_GET['firma_id'] : 0;
$selected_year = isset($_GET['yil']) ? (int)$_GET['yil'] : date('Y');
$selected_month = isset($_GET['ay']) ? (int)$_GET['ay'] : date('n');

// Prim oranı ekleme işlemi
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add') {
    $personel_id = (int)$_POST['personel_id'];
    $firma_id = (int)$_POST['firma_id'];
    $yil = (int)$_POST['yil'];
    $ay = (int)$_POST['ay'];
    $min_tutar = (float)$_POST['min_tutar'];
    $max_tutar = (float)$_POST['max_tutar'];
    $prim_orani = (float)$_POST['prim_orani'];
    $aciklama = $_POST['aciklama'];
    
    if ($db->insert('personel_prim_oranlari', [
        'personel_id' => $personel_id,
        'firma_id' => $firma_id,
        'yil' => $yil,
        'ay' => $ay,
        'min_tutar' => $min_tutar,
        'max_tutar' => $max_tutar,
        'prim_orani' => $prim_orani,
        'aciklama' => $aciklama,
        'durum' => 'aktif'
    ])) {
        $success_message = "Prim oranı başarıyla eklendi!";
    } else {
        $error_message = "Prim oranı eklenirken hata oluştu!";
    }
}

// Prim oranı düzenleme işlemi
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = (int)$_POST['id'];
    $min_tutar = (float)$_POST['min_tutar'];
    $max_tutar = (float)$_POST['max_tutar'];
    $prim_orani = (float)$_POST['prim_orani'];
    $aciklama = $_POST['aciklama'];
    
    if ($db->update('personel_prim_oranlari', [
        'min_tutar' => $min_tutar,
        'max_tutar' => $max_tutar,
        'prim_orani' => $prim_orani,
        'aciklama' => $aciklama
    ], ['id' => $id])) {
        $success_message = "Prim oranı başarıyla güncellendi!";
    } else {
        $error_message = "Prim oranı güncellenirken hata oluştu!";
    }
}

// Prim oranı silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if ($db->delete('personel_prim_oranlari', ['id' => $_GET['id']])) {
        $success_message = "Prim oranı başarıyla silindi!";
    } else {
        $error_message = "Prim oranı silinirken hata oluştu!";
    }
}

// Prim oranlarını al (filtreli)
$where_conditions = [];
$where_params = [];

if ($selected_personel > 0) {
    $where_conditions[] = 'ppo.personel_id = ?';
    $where_params[] = $selected_personel;
}

if ($selected_firma > 0) {
    $where_conditions[] = 'ppo.firma_id = ?';
    $where_params[] = $selected_firma;
}

$where_conditions[] = 'ppo.yil = ?';
$where_params[] = $selected_year;

$where_conditions[] = 'ppo.ay = ?';
$where_params[] = $selected_month;

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$prim_oranlari = $db->query("
    SELECT ppo.*, p.ad_soyad as personel_adi, f.firma_adi
    FROM personel_prim_oranlari ppo
    LEFT JOIN personel p ON ppo.personel_id = p.id
    LEFT JOIN firmalar f ON ppo.firma_id = f.id
    $where_clause
    ORDER BY ppo.personel_id, ppo.firma_id, ppo.min_tutar ASC
", $where_params);

// Düzenleme için seçili oran
$edit_oran = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_oran = $db->select('personel_prim_oranlari', ['id' => $_GET['edit']]);
    $edit_oran = $edit_oran[0] ?? null;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Personel Prim Oranları</title>
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
            $page_title = 'Personel Prim Oranları';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <a href="primler.php" style="color: #3b82f6; text-decoration: none;">Primler</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <span>Personel Prim Oranları</span>
                    </nav>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Filtre Seçimi -->
                <div class="filter-section">
                    <div class="filter-card">
                        <h3>Filtre Seçimi</h3>
                        <form method="GET" class="filter-form">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label>Personel Seçimi</label>
                                    <select name="personel_id" onchange="this.form.submit()">
                                        <option value="0">Tüm Personel</option>
                                        <?php foreach ($personeller as $personel): ?>
                                            <option value="<?php echo $personel['id']; ?>" <?php echo ($personel['id'] == $selected_personel) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Firma Seçimi</label>
                                    <select name="firma_id" onchange="this.form.submit()">
                                        <option value="0">Tüm Firmalar</option>
                                        <?php foreach ($firmalar as $firma): ?>
                                            <option value="<?php echo $firma['id']; ?>" <?php echo ($firma['id'] == $selected_firma) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Yıl Seçimi</label>
                                    <select name="yil" onchange="this.form.submit()">
                                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($y == $selected_year) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label>Ay Seçimi</label>
                                    <select name="ay" onchange="this.form.submit()">
                                        <?php foreach ($aylar as $ay_no => $ay_adi): ?>
                                            <option value="<?php echo $ay_no; ?>" <?php echo ($ay_no == $selected_month) ? 'selected' : ''; ?>>
                                                <?php echo $ay_adi; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Prim Oranı Ekleme/Düzenleme Formu -->
                <div class="form-card">
                    <h3><?php echo $edit_oran ? 'Prim Oranı Düzenle' : 'Yeni Prim Oranı Ekle'; ?></h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_oran ? 'edit' : 'add'; ?>">
                        <?php if ($edit_oran): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_oran['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="personel_id">Personel Seçimi</label>
                                <select id="personel_id" name="personel_id" required>
                                    <option value="">Personel Seçin</option>
                                    <?php foreach ($personeller as $personel): ?>
                                        <option value="<?php echo $personel['id']; ?>" <?php echo ($edit_oran && $personel['id'] == $edit_oran['personel_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($personel['ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="firma_id">Firma Seçimi</label>
                                <select id="firma_id" name="firma_id" required>
                                    <option value="">Firma Seçin</option>
                                    <?php foreach ($firmalar as $firma): ?>
                                        <option value="<?php echo $firma['id']; ?>" <?php echo ($edit_oran && $firma['id'] == $edit_oran['firma_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="yil">Yıl</label>
                                <select id="yil" name="yil" required>
                                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo ($edit_oran && $y == $edit_oran['yil']) ? 'selected' : (($y == date('Y') && !$edit_oran) ? 'selected' : ''); ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ay">Ay</label>
                                <select id="ay" name="ay" required>
                                    <?php foreach ($aylar as $ay_no => $ay_adi): ?>
                                        <option value="<?php echo $ay_no; ?>" <?php echo ($edit_oran && $ay_no == $edit_oran['ay']) ? 'selected' : (($ay_no == date('n') && !$edit_oran) ? 'selected' : ''); ?>>
                                            <?php echo $ay_adi; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="min_tutar">Minimum Tutar (₺)</label>
                                <input type="number" id="min_tutar" name="min_tutar" 
                                       value="<?php echo $edit_oran ? $edit_oran['min_tutar'] : ''; ?>" 
                                       step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="max_tutar">Maksimum Tutar (₺)</label>
                                <input type="number" id="max_tutar" name="max_tutar" 
                                       value="<?php echo $edit_oran ? $edit_oran['max_tutar'] : ''; ?>" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prim_orani">Prim Oranı (%)</label>
                                <input type="number" id="prim_orani" name="prim_orani" 
                                       value="<?php echo $edit_oran ? $edit_oran['prim_orani'] : ''; ?>" 
                                       step="0.01" min="0" max="100" required>
                            </div>
                            <div class="form-group">
                                <label for="aciklama">Açıklama</label>
                                <input type="text" id="aciklama" name="aciklama" 
                                       value="<?php echo $edit_oran ? htmlspecialchars($edit_oran['aciklama']) : ''; ?>" 
                                       placeholder="Örn: 0-10.000 TL arası %5 prim" required>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?php echo $edit_oran ? 'Güncelle' : 'Ekle'; ?>
                            </button>
                            <?php if ($edit_oran): ?>
                                <a href="personel-prim-oranlari.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    İptal
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Prim Oranları Listesi -->
                <div class="prim-oranlari-container">
                    <h3>Mevcut Prim Oranları</h3>
                    
                    <?php if (!empty($prim_oranlari)): ?>
                        <div class="prim-oranlari-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Personel</th>
                                        <th>Firma</th>
                                        <th>Dönem</th>
                                        <th>Tutar Aralığı</th>
                                        <th>Prim Oranı</th>
                                        <th>Açıklama</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prim_oranlari as $oran): ?>
                                    <tr>
                                        <td>
                                            <div class="personel-info">
                                                <i class="fas fa-user"></i>
                                                <span><?php echo htmlspecialchars($oran['personel_adi']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="firma-name"><?php echo htmlspecialchars($oran['firma_adi']); ?></span>
                                        </td>
                                        <td>
                                            <span class="donem"><?php echo $aylar[$oran['ay']]; ?> <?php echo $oran['yil']; ?></span>
                                        </td>
                                        <td>
                                            <span class="tutar-aralik">
                                                ₺<?php echo number_format($oran['min_tutar'], 0, ',', '.'); ?> - 
                                                ₺<?php echo number_format($oran['max_tutar'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td class="prim-oran">%<?php echo $oran['prim_orani']; ?></td>
                                        <td class="aciklama"><?php echo htmlspecialchars($oran['aciklama']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="personel-prim-oranlari.php?edit=<?php echo $oran['id']; ?>" 
                                                   class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </a>
                                                <a href="personel-prim-oranlari.php?action=delete&id=<?php echo $oran['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu prim oranını silmek istediğinizden emin misiniz?')">
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
                        <div class="empty-state">
                            <i class="fas fa-percentage"></i>
                            <h3>Henüz prim oranı eklenmemiş</h3>
                            <p>Yukarıdaki formu kullanarak prim oranları ekleyebilirsiniz.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
        }
    </script>

    <style>
        .filter-section {
            margin-bottom: 30px;
        }

        .filter-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .filter-card h3 {
            margin: 0 0 20px 0;
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            align-items: end;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            flex: 1;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--bg-card);
            font-size: 14px;
        }

        .form-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .form-card h3 {
            margin: 0 0 20px 0;
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .prim-oranlari-container {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .prim-oranlari-container h3 {
            margin: 0 0 20px 0;
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
        }

        .prim-oranlari-table {
            overflow-x: auto;
        }

        .prim-oranlari-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .prim-oranlari-table th {
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }

        .prim-oranlari-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .personel-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .personel-info i {
            color: #3b82f6;
            font-size: 16px;
        }

        .firma-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .donem {
            background: #f0f9ff;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .tutar-aralik {
            font-weight: 600;
            color: var(--text-primary);
        }

        .prim-oran {
            font-weight: 700;
            color: #3b82f6;
            font-size: 16px;
        }

        .aciklama {
            color: var(--text-secondary);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--text-secondary);
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #94a3b8;
        }
    </style>
</body>
</html>
