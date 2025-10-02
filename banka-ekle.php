<?php
/**
 * Primew Panel - Yeni Banka Ekle
 */

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $banka_adi = trim($_POST['banka_adi']);
    
    if (empty($banka_adi)) {
        $errors[] = "Banka adı zorunludur!";
    }
    
    if (empty($errors)) {
        if ($db->insert('bankalar', ['banka_adi' => $banka_adi])) {
            header('Location: bankalar.php?success=1');
            exit;
        } else {
            $errors[] = "Banka eklenirken hata oluştu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Yeni Banka Ekle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php 
            $page_title = 'Yeni Banka Ekle';
            include 'includes/header.php'; 
            ?>

            <div class="content-area">
                <div style="margin-bottom: 20px;">
                    <nav style="font-size: 14px; color: #64748b;">
                        <a href="index.php" style="color: #3b82f6; text-decoration: none;">Ana Sayfa</a>
                        <span style="margin: 0 8px;">›</span>
                        <a href="tahsilatlar.php" style="color: #3b82f6; text-decoration: none;">Tahsilat</a>
                        <span style="margin: 0 8px;">›</span>
                        <span style="color: #1e293b;">Yeni Banka</span>
                    </nav>
                </div>

                <?php if (!empty($errors)): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); max-width: 600px;">
                    <h2 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px;">
                        <i class="fas fa-university"></i> Yeni Banka Ekle
                    </h2>
                    
                    <form method="POST" action="banka-ekle.php">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Banka Adı *</label>
                            <input type="text" name="banka_adi" required 
                                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                                   placeholder="Örn: Ziraat Bankası">
                        </div>
                        
                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <a href="bankalar.php" style="padding: 12px 24px; background: #64748b; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <button type="submit" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
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
    </script>
</body>
</html>

