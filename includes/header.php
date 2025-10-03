<?php
/**
 * Primew Panel - Header Component
 * Merkezi header yönetimi
 */

// Giriş kontrolü
require_once 'auth.php';
requireLogin();
?>

<!-- Dark Mode Script - Must be before any content to prevent FOUC -->
<script>
    (function() {
        // Apply dark mode immediately to prevent flash of white content
        const savedTheme = localStorage.getItem('darkMode');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.classList.add('dark-mode');
        }
    })();
</script>

<!-- Top Bar -->
<div class="top-bar">
    <div style="display: flex; align-items: center;">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?php echo $page_title ?? 'Ana Sayfa'; ?></h1>
    </div>
    
    <div class="user-info">
        <!-- Dark Mode Toggle -->
        <button id="darkModeToggle" class="dark-mode-toggle" title="Dark Mode">
            <i class="fas fa-moon" id="darkModeIcon"></i>
        </button>
        
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

    .dark-mode-toggle {
        background: transparent;
        border: 2px solid #e2e8f0;
        color: #64748b;
        padding: 8px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-right: 10px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark-mode-toggle:hover {
        background: #f8fafc;
        border-color: #3b82f6;
        color: #3b82f6;
        transform: scale(1.05);
    }

    .dark-mode-toggle i {
        font-size: 16px;
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    }

    // Dark Mode Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeIcon = document.getElementById('darkModeIcon');
        const body = document.body;
        const html = document.documentElement;

        // Get saved theme from localStorage
        const savedTheme = localStorage.getItem('darkMode');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Apply saved theme or system preference
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            body.classList.add('dark-mode');
            html.classList.add('dark-mode');
            darkModeIcon.className = 'fas fa-sun';
            darkModeToggle.title = 'Light Mode';
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            html.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'dark');
                darkModeIcon.className = 'fas fa-sun';
                darkModeToggle.title = 'Light Mode';
            } else {
                localStorage.setItem('darkMode', 'light');
                darkModeIcon.className = 'fas fa-moon';
                darkModeToggle.title = 'Dark Mode';
            }
        });
    });
</script>
