<?php
/**
 * Primew Panel - Banka Yönetimi
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$stats = getStats();

// Yeni banka ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && !empty($_POST['banka_adi'])) {
        $db->insert('bankalar', ['banka_adi' => trim($_POST['banka_adi'])]);
        header('Location: bankalar.php?success=1');
        exit;
    }
    
    // Düzenleme
    if ($_POST['action'] === 'edit' && isset($_POST['id']) && !empty($_POST['banka_adi'])) {
        $db->update('bankalar', ['banka_adi' => trim($_POST['banka_adi'])], ['id' => (int)$_POST['id']]);
        header('Location: bankalar.php?updated=1');
        exit;
    }
    
    // Silme
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $banka_id = (int)$_POST['id'];
        
        // Bu bankayı kullanan kasa kaydı var mı kontrol et
        $kasa_check = $db->select('tahsilatlar', ['banka_id' => $banka_id]);
        
        if (!empty($kasa_check)) {
            // Kullanımda - sadece pasif yap
            $db->update('bankalar', ['durum' => 'pasif'], ['id' => $banka_id]);
            header('Location: bankalar.php?deleted=1&warning=1');
        } else {
            // Kullanımda değil - fiziksel olarak sil
            $db->delete('bankalar', ['id' => $banka_id]);
            header('Location: bankalar.php?deleted=1');
        }
        exit;
    }
}

// Bankaları al
$bankalar = $db->select('bankalar', ['durum' => 'aktif'], 'banka_adi ASC');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Banka Yönetimi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .banka-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
        }
        .banka-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .edit-btn, .delete-btn {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .banka-item:hover .edit-btn,
        .banka-item:hover .delete-btn {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Banka Yönetimi';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: var(--text-secondary);">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="kasa.php" style="color: #3b82f6; text-decoration: none;">Kasa</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: var(--text-primary);">Banka Yönetimi</span>
                    </nav>
                </div>

                <?php if (isset($_GET['success'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> Banka başarıyla eklendi!
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div style="background: #dbeafe; color: #1e40af; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> Banka başarıyla güncellendi!
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div style="background: <?php echo isset($_GET['warning']) ? '#fef3c7' : '#fee2e2'; ?>; color: <?php echo isset($_GET['warning']) ? '#92400e' : '#dc2626'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-<?php echo isset($_GET['warning']) ? 'exclamation-triangle' : 'trash'; ?>"></i>
                    <?php if (isset($_GET['warning'])): ?>
                        Banka kullanımda olduğu için pasif yapıldı! (Kasa kayıtlarında kullanılmış)
                    <?php else: ?>
                        Banka başarıyla silindi!
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Yeni Banka Ekleme -->
                <div class="white-card-small" style="padding: 20px; margin-bottom: 30px;">
                    <form method="POST" action="bankalar.php" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="action" value="add">
                        <div style="flex: 1;">
                            <input type="text" name="banka_adi" placeholder="Yeni banka adı..." required 
                                   style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 15px;">
                        </div>
                        <button type="submit" style="padding: 12px 24px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-plus"></i> Banka Ekle
                        </button>
                    </form>
                </div>

                <!-- Banka Listesi -->
                <div class="white-card" style="padding: 30px;">
                    <h2 class="text-primary" style="font-size: 20px; font-weight: 600; margin-bottom: 20px;">
                        <i class="fas fa-university"></i> Kayıtlı Bankalar (<?php echo count($bankalar); ?>)
                    </h2>
                    
                    <?php if (!empty($bankalar)): ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($bankalar as $banka): ?>
                        <div class="banka-item" id="banka_<?php echo $banka['id']; ?>">
                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-university" style="color: white; font-size: 16px;"></i>
                            </div>
                            
                            <span class="banka-text text-primary" id="text_<?php echo $banka['id']; ?>" style="flex: 1; font-size: 15px; font-weight: 600;">
                                <?php echo htmlspecialchars($banka['banka_adi']); ?>
                            </span>
                            
                            <form method="POST" action="bankalar.php" style="display: none; flex: 1;" id="edit_form_<?php echo $banka['id']; ?>">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $banka['id']; ?>">
                                <input type="text" name="banka_adi" value="<?php echo htmlspecialchars($banka['banka_adi']); ?>" 
                                       style="width: 100%; padding: 8px 12px; border: 2px solid #3b82f6; border-radius: 6px; font-size: 15px;">
                            </form>
                            
                            <div style="display: flex; gap: 8px;">
                                <button onclick="editBanka(<?php echo $banka['id']; ?>)" class="edit-btn"
                                        style="padding: 8px 14px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <form method="POST" action="bankalar.php" style="display: inline;" onsubmit="return confirm('Bu bankayı silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $banka['id']; ?>">
                                    <button type="submit" class="delete-btn"
                                            style="padding: 8px 14px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-secondary" style="text-align: center; padding: 40px;">
                        <i class="fas fa-university" style="font-size: 48px; margin-bottom: 15px; color: #cbd5e1;"></i>
                        <p>Henüz banka kaydı yok.</p>
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
        
        function editBanka(id) {
            const textSpan = document.getElementById('text_' + id);
            const editForm = document.getElementById('edit_form_' + id);
            
            // Text'i gizle, formu göster
            textSpan.style.display = 'none';
            editForm.style.display = 'flex';
            
            // Input'a focus
            const input = editForm.querySelector('input[name="banka_adi"]');
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
