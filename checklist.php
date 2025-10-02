<?php
/**
 * Primew Panel - Checklist
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$stats = getStats();

// İşlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && !empty($_POST['madde'])) {
        $max_sira = $db->query("SELECT MAX(sira) as max_sira FROM checklist");
        $sira = ($max_sira && $max_sira[0]['max_sira']) ? $max_sira[0]['max_sira'] + 1 : 0;
        
        $db->insert('checklist', [
            'madde' => trim($_POST['madde']),
            'sira' => $sira
        ]);
        header('Location: checklist.php?success=1');
        exit;
    }
    
    // Düzenleme
    if ($_POST['action'] === 'edit' && isset($_POST['id']) && !empty($_POST['madde'])) {
        $db->update('checklist', [
            'madde' => trim($_POST['madde'])
        ], ['id' => (int)$_POST['id']]);
        header('Location: checklist.php?updated=1');
        exit;
    }
    
    // Tamamlama durumu değiştirme
    if ($_POST['action'] === 'toggle' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $item = $db->select('checklist', ['id' => $id]);
        
        if (!empty($item)) {
            $new_status = $item[0]['tamamlandi'] ? 0 : 1;
            $db->update('checklist', [
                'tamamlandi' => $new_status,
                'tamamlanma_tarihi' => $new_status ? date('Y-m-d H:i:s') : null
            ], ['id' => $id]);
        }
        
        header('Location: checklist.php');
        exit;
    }
    
    // Silme
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $db->delete('checklist', ['id' => (int)$_POST['id']]);
        header('Location: checklist.php');
        exit;
    }
}

// Tüm checklist maddelerini al
$items = $db->query("SELECT * FROM checklist ORDER BY tamamlandi ASC, sira ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Checklist</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .checklist-item {
            background: white;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .checklist-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .checklist-item.completed {
            background: #f0fdf4;
            opacity: 0.8;
        }
        .checkbox-custom {
            width: 24px;
            height: 24px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .checkbox-custom:hover {
            border-color: #3b82f6;
        }
        .checkbox-custom.checked {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        .item-text {
            flex: 1;
            font-size: 15px;
            color: #1e293b;
        }
        .item-text.completed {
            text-decoration: line-through;
            color: #64748b;
        }
        .delete-btn {
            color: #ef4444;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            opacity: 0;
        }
        .checklist-item:hover .delete-btn {
            opacity: 1;
        }
        .delete-btn:hover {
            background: #fee2e2;
        }
        .add-form {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Checklist';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <!-- Breadcrumb -->
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Checklist</span>
                    </nav>
                </div>

                <?php if (isset($_GET['success'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <span>Madde başarıyla eklendi!</span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div style="background: #dbeafe; color: #1e40af; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i>
                    <span>Madde başarıyla güncellendi!</span>
                </div>
                <?php endif; ?>

                <!-- Yeni Madde Ekleme -->
                <div class="add-form">
                    <form method="POST" action="checklist.php" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="action" value="add">
                        <input type="text" name="madde" placeholder="Yeni yapılacak madde ekle..." 
                               required
                               style="flex: 1; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; transition: border-color 0.2s ease;"
                               onfocus="this.style.borderColor='#3b82f6'" 
                               onblur="this.style.borderColor='#e2e8f0'">
                        <button type="submit" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s ease;">
                            <i class="fas fa-plus"></i> Ekle
                        </button>
                    </form>
                </div>

                <!-- Checklist İstatistikleri -->
                <?php 
                $total = count($items);
                $completed = count(array_filter($items, function($item) { return $item['tamamlandi']; }));
                $percentage = $total > 0 ? ($completed / $total) * 100 : 0;
                ?>
                <?php if ($total > 0): ?>
                <div style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 14px; color: #64748b;">İlerleme</span>
                        <span style="font-size: 14px; font-weight: 600; color: #1e293b;"><?php echo $completed; ?> / <?php echo $total; ?> tamamlandı</span>
                    </div>
                    <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #3b82f6, #10b981); height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Checklist Maddeleri -->
                <div>
                    <?php if (empty($items)): ?>
                    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                        <i class="fas fa-clipboard-list" style="font-size: 64px; color: #cbd5e1; margin-bottom: 20px;"></i>
                        <h3 style="color: #64748b; font-size: 18px; margin: 0;">Henüz checklist maddesi yok</h3>
                        <p style="color: #94a3b8; margin: 8px 0 0;">Yukarıdaki formdan yeni madde ekleyebilirsiniz.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <div class="checklist-item <?php echo $item['tamamlandi'] ? 'completed' : ''; ?>" id="item_<?php echo $item['id']; ?>">
                            <form method="POST" action="checklist.php" style="display: contents;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="checkbox-custom <?php echo $item['tamamlandi'] ? 'checked' : ''; ?>" 
                                        style="background: <?php echo $item['tamamlandi'] ? '#10b981' : 'transparent'; ?>; border: 2px solid <?php echo $item['tamamlandi'] ? '#10b981' : '#cbd5e1'; ?>; cursor: pointer;">
                                    <?php if ($item['tamamlandi']): ?>
                                        <i class="fas fa-check" style="color: white;"></i>
                                    <?php endif; ?>
                                </button>
                            </form>
                            
                            <span class="item-text <?php echo $item['tamamlandi'] ? 'completed' : ''; ?>" id="text_<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['madde']); ?>
                            </span>
                            
                            <form method="POST" action="checklist.php" style="display: none;" id="edit_form_<?php echo $item['id']; ?>">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <input type="text" name="madde" value="<?php echo htmlspecialchars($item['madde']); ?>" 
                                       style="flex: 1; padding: 8px 12px; border: 2px solid #3b82f6; border-radius: 6px; font-size: 15px;">
                            </form>
                            
                            <?php if ($item['tamamlandi'] && $item['tamamlanma_tarihi']): ?>
                            <span style="font-size: 12px; color: #94a3b8; white-space: nowrap;">
                                <i class="fas fa-check-circle"></i> <?php echo date('d.m.Y', strtotime($item['tamamlanma_tarihi'])); ?>
                            </span>
                            <?php endif; ?>
                            
                            <button onclick="editItem(<?php echo $item['id']; ?>)" class="delete-btn" 
                                    style="background: none; border: none; color: #3b82f6;">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <form method="POST" action="checklist.php" style="display: contents;" onsubmit="return confirm('Bu maddeyi silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="delete-btn" style="background: none; border: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
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
        
        function editItem(id) {
            const textSpan = document.getElementById('text_' + id);
            const editForm = document.getElementById('edit_form_' + id);
            const item = document.getElementById('item_' + id);
            
            // Text'i gizle, formu göster
            textSpan.style.display = 'none';
            editForm.style.display = 'flex';
            editForm.style.flex = '1';
            
            // Input'a focus
            const input = editForm.querySelector('input[name="madde"]');
            input.focus();
            input.select();
            
            // Enter'a basınca kaydet
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    editForm.submit();
                }
            });
            
            // Esc'e basınca iptal
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    cancelEdit(id);
                }
            });
            
            // Input dışına tıklayınca kaydet
            input.addEventListener('blur', function(e) {
                setTimeout(() => {
                    if (document.activeElement !== input) {
                        editForm.submit();
                    }
                }, 200);
            });
        }
        
        function cancelEdit(id) {
            const textSpan = document.getElementById('text_' + id);
            const editForm = document.getElementById('edit_form_' + id);
            
            textSpan.style.display = '';
            editForm.style.display = 'none';
        }
    </script>
</body>
</html>

