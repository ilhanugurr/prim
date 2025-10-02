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
        <img src="seomew-logo.png" alt="SyncMEW Logo" style="height: 52px; margin-bottom: 0;">
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-home nav-icon"></i>
            <span class="nav-text">Ana Sayfa</span>
        </a>
        <a href="firmalar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'firmalar.php') ? 'active' : ''; ?>">
            <i class="fas fa-industry nav-icon"></i>
            <span class="nav-text">Firmalar</span>
            <?php if (isAdmin()): ?>
            <span class="nav-badge"><?php echo $stats['firmalar']; ?></span>
            <?php endif; ?>
        </a>
        <a href="personel.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'personel.php') ? 'active' : ''; ?>">
            <i class="fas fa-users nav-icon"></i>
            <span class="nav-text">Personel</span>
            <?php if (isAdmin()): ?>
            <span class="nav-badge"><?php echo $stats['personel']; ?></span>
            <?php endif; ?>
        </a>
        <a href="urun-hizmet.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'urun-hizmet.php') ? 'active' : ''; ?>">
            <i class="fas fa-link nav-icon"></i>
            <span class="nav-text">Ürün / Hizmet</span>
            <?php if (isAdmin()): ?>
            <span class="nav-badge"><?php echo $stats['urun_hizmet']; ?></span>
            <?php endif; ?>
        </a>
        <a href="satislar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'satislar.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line nav-icon"></i>
            <span class="nav-text">Satışlar</span>
        </a>
        <a href="musteriler.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'musteriler.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-tie nav-icon"></i>
            <span class="nav-text">Müşteriler</span>
            <?php if (isAdmin()): ?>
            <span class="nav-badge"><?php echo $stats['musteriler']; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="hedefler.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'hedefler.php') ? 'active' : ''; ?>">
            <i class="fas fa-bullseye nav-icon"></i>
            <span class="nav-text">Hedefler</span>
            <?php if (isAdmin()): ?>
            <span class="nav-badge"><?php echo $stats['hedefler']; ?></span>
            <?php endif; ?>
        </a>
                <a href="primler.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'primler.php' || basename($_SERVER['PHP_SELF']) == 'prim-oranlari.php') ? 'active' : ''; ?>">
                    <i class="fas fa-coins nav-icon"></i>
                    <span class="nav-text">Primler</span>
                </a>
        <?php if (isAdmin()): ?>
        <a href="tahsilatlar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'tahsilatlar.php' || basename($_SERVER['PHP_SELF']) == 'tahsilat-ekle.php' || basename($_SERVER['PHP_SELF']) == 'tahsilat-duzenle.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave nav-icon"></i>
            <span class="nav-text">Tahsilat</span>
        </a>
        <a href="raporlar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'raporlar.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line nav-icon"></i>
            <span class="nav-text">Raporlar</span>
        </a>
        <a href="checklist.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'checklist.php') ? 'active' : ''; ?>">
            <i class="fas fa-tasks nav-icon"></i>
            <span class="nav-text">Checklist</span>
        </a>
        <?php endif; ?>
    </nav>
</div>
