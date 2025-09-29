<?php
/**
 * Primew Panel - Header Component
 * Merkezi header yönetimi
 */

// Giriş kontrolü
require_once 'auth.php';
requireLogin();
?>

<!-- Top Bar -->
<div class="top-bar">
    <div style="display: flex; align-items: center;">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?php echo $page_title ?? 'Ana Sayfa'; ?></h1>
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <?php 
            $ad_soyad = '';
            if (($_SESSION['kullanici_adi'] ?? '') === 'admin') {
                $ad_soyad = 'Uğur İlhan';
            } elseif (($_SESSION['kullanici_adi'] ?? '') === 'seyma') {
                $ad_soyad = 'Şeyma';
            } elseif (($_SESSION['kullanici_adi'] ?? '') === 'mehmet') {
                $ad_soyad = 'Mehmet Kaya';
            } else {
                $ad_soyad = $_SESSION['ad_soyad'] ?? 'Kullanıcı';
            }
            
            // İlk karakteri al (Türkçe karakterler için mb_substr kullan)
            $initials = mb_substr($ad_soyad, 0, 1, 'UTF-8');
            echo strtoupper($initials);
            ?>
        </div>
        <div>
            <div class="user-name">
                <?php 
                if (($_SESSION['kullanici_adi'] ?? '') === 'admin') {
                    echo 'Uğur İlhan';
                } elseif (($_SESSION['kullanici_adi'] ?? '') === 'seyma') {
                    echo 'Şeyma';
                } elseif (($_SESSION['kullanici_adi'] ?? '') === 'mehmet') {
                    echo 'Mehmet Kaya';
                } else {
                    echo htmlspecialchars($_SESSION['ad_soyad'] ?? 'Kullanıcı');
                }
                ?>
            </div>
            <div style="font-size: 12px; color: #64748b;">
                <?php echo ($_SESSION['rol'] ?? '') === 'admin' ? 'Sistem Yöneticisi' : 'Satış Temsilcisi'; ?>
            </div>
        </div>
        <a href="logout.php" class="logout-btn" title="Çıkış Yap">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<style>
    .logout-btn {
        color: #64748b;
        text-decoration: none;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.3s;
    }

    .logout-btn:hover {
        color: #dc2626;
        background: #fef2f2;
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }
</script>
