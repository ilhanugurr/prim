<?php
/**
 * Primew Panel - Prim Oranları Yönetimi
 * Prim oranlarını düzenleme sayfası
 */

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// İstatistikleri al
$stats = getStats();

// Prim oranı ekleme işlemi
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add') {
    $min_tutar = (float)$_POST['min_tutar'];
    $max_tutar = (float)$_POST['max_tutar'];
    $prim_orani = (float)$_POST['prim_orani'];
    $aciklama = $_POST['aciklama'];
    
    if ($db->insert('prim_oranlari', [
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
    
    if ($db->update('prim_oranlari', [
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
    if ($db->delete('prim_oranlari', ['id' => $_GET['id']])) {
        $success_message = "Prim oranı başarıyla silindi!";
    } else {
        $error_message = "Prim oranı silinirken hata oluştu!";
    }
}

// Prim oranlarını al
$prim_oranlari = $db->select('prim_oranlari', [], 'min_tutar ASC');

// Düzenleme için seçili oran
$edit_oran = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_oran = $db->select('prim_oranlari', ['id' => $_GET['edit']]);
    $edit_oran = $edit_oran[0] ?? null;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Prim Oranları</title>
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
            $page_title = 'Prim Oranları';
            include 'includes/header.php'; 
            ?>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <a href="primler.php" style="color: #3b82f6; text-decoration: none;">Primler</a>
                        <i class="fas fa-chevron-right" style="font-size: 12px;"></i>
                        <span>Prim Oranları</span>
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
                                <a href="prim-oranlari.php" class="btn btn-secondary">
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
                                        <th>Tutar Aralığı</th>
                                        <th>Prim Oranı</th>
                                        <th>Açıklama</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prim_oranlari as $oran): ?>
                                    <tr>
                                        <td>
                                            <span class="tutar-aralik">
                                                ₺<?php echo number_format($oran['min_tutar'], 0, ',', '.'); ?> - 
                                                ₺<?php echo number_format($oran['max_tutar'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td class="prim-oran">%<?php echo $oran['prim_orani']; ?></td>
                                        <td class="aciklama"><?php echo htmlspecialchars($oran['aciklama']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $oran['durum'] == 'aktif' ? 'success' : 'inactive'; ?>">
                                                <?php echo $oran['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="prim-oranlari.php?edit=<?php echo $oran['id']; ?>" 
                                                   class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </a>
                                                <a href="prim-oranlari.php?action=delete&id=<?php echo $oran['id']; ?>" 
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
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .form-card h3 {
            margin: 0 0 20px 0;
            color: #1e293b;
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
            color: #374151;
        }

        .form-group input {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus {
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
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .prim-oranlari-container h3 {
            margin: 0 0 20px 0;
            color: #1e293b;
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
            background: #f8fafc;
            color: #374151;
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

        .tutar-aralik {
            font-weight: 600;
            color: #1e293b;
        }

        .prim-oran {
            font-weight: 700;
            color: #3b82f6;
            font-size: 16px;
        }

        .aciklama {
            color: #64748b;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.inactive {
            background: #f3f4f6;
            color: #6b7280;
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
            color: #64748b;
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #94a3b8;
        }
    </style>
</body>
</html>
