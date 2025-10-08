<?php
// Sidebar menü yapısı - tüm sayfalarda kullanılır

// Giriş kontrolü
require_once 'auth.php';
requireLogin();

$stats = getStats();
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="seomew-logo.png" alt="SyncMEW Logo" class="logo-img" style="height: 52px; margin-bottom: 0;">
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-home nav-icon"></i>
            <span class="nav-text">Ana Sayfa</span>
        </a>
        <?php if (hasPagePermission('firmalar', 'goruntuleme')): ?>
        <a href="firmalar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'firmalar.php') ? 'active' : ''; ?>">
            <i class="fas fa-industry nav-icon"></i>
            <span class="nav-text">Firmalar</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('personel', 'goruntuleme')): ?>
        <a href="personel.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'personel.php') ? 'active' : ''; ?>">
            <i class="fas fa-users nav-icon"></i>
            <span class="nav-text">Personel</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('urun-hizmet', 'goruntuleme')): ?>
        <a href="urun-hizmet.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'urun-hizmet.php') ? 'active' : ''; ?>">
            <i class="fas fa-link nav-icon"></i>
            <span class="nav-text">Ürün / Hizmet</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('envanter', 'goruntuleme')): ?>
        <a href="envanter.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'envanter.php' || basename($_SERVER['PHP_SELF']) == 'envanter-ekle.php' || basename($_SERVER['PHP_SELF']) == 'envanter-duzenle.php' || basename($_SERVER['PHP_SELF']) == 'envanter-kategoriler.php' || basename($_SERVER['PHP_SELF']) == 'envanter-kategori-ekle.php' || basename($_SERVER['PHP_SELF']) == 'envanter-kategori-duzenle.php') ? 'active' : ''; ?>">
            <i class="fas fa-boxes nav-icon"></i>
            <span class="nav-text">Envanter</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('satislar', 'goruntuleme')): ?>
        <a href="satislar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'satislar.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line nav-icon"></i>
            <span class="nav-text">Satışlar</span>
            <?php 
            // Beklemedeki satış sayısını sadece admin'e göster
            if (isAdmin()):
                $bekleyen_satislar = $db->query("SELECT COUNT(*) as sayi FROM satislar WHERE onay_durumu = 'beklemede'");
                $bekleyen_sayi = !empty($bekleyen_satislar) ? (int)$bekleyen_satislar[0]['sayi'] : 0;
                if ($bekleyen_sayi > 0): 
            ?>
            <span class="nav-badge nav-badge-warning"><?php echo $bekleyen_sayi; ?></span>
            <?php 
                endif;
            endif; 
            ?>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('musteriler', 'goruntuleme')): ?>
        <a href="musteriler.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'musteriler.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-tie nav-icon"></i>
            <span class="nav-text">Müşteriler</span>
        </a>
        <?php endif; ?>
        
        <?php if (hasPagePermission('hedefler', 'goruntuleme')): ?>
        <a href="hedefler.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'hedefler.php') ? 'active' : ''; ?>">
            <i class="fas fa-bullseye nav-icon"></i>
            <span class="nav-text">Hedefler</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('primler', 'goruntuleme')): ?>
        <a href="primler.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'primler.php' || basename($_SERVER['PHP_SELF']) == 'prim-oranlari.php') ? 'active' : ''; ?>">
            <i class="fas fa-coins nav-icon"></i>
            <span class="nav-text">Primler</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('tahsilat', 'goruntuleme')): ?>
        <a href="kasa.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'kasa.php' || basename($_SERVER['PHP_SELF']) == 'odeme-ekle.php' || basename($_SERVER['PHP_SELF']) == 'odeme-duzenle.php' || basename($_SERVER['PHP_SELF']) == 'gider-ekle.php' || basename($_SERVER['PHP_SELF']) == 'gider-duzenle.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave nav-icon"></i>
            <span class="nav-text">Kasa</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('raporlar', 'goruntuleme')): ?>
        <a href="raporlar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'raporlar.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line nav-icon"></i>
            <span class="nav-text">Raporlar</span>
        </a>
        <?php endif; ?>
        <?php if (hasPagePermission('checklist', 'goruntuleme')): ?>
        <a href="checklist.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'checklist.php') ? 'active' : ''; ?>">
            <i class="fas fa-tasks nav-icon"></i>
            <span class="nav-text">Checklist</span>
        </a>
        <?php endif; ?>
        
        <!-- Ayarlar menüsü (en altta) -->
        <?php if (hasPagePermission('ayarlar', 'goruntuleme')): ?>
        <a href="ayarlar.php" class="nav-item settings-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ayarlar.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog nav-icon"></i>
            <span class="nav-text">Ayarlar</span>
        </a>
        <?php endif; ?>
    </nav>
</div>
