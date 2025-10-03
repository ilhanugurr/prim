<?php
/**
 * Primew Panel - Giriş Sayfası
 */

// Session başlat
session_start();

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once 'config/database.php';

// Veritabanı karakter setini ayarla
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("SET CHARACTER SET utf8mb4");

$error_message = '';

// Zaten giriş yapmış kullanıcıları ana sayfaya yönlendir
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kullanici_adi']) && isset($_POST['sifre'])) {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = trim($_POST['sifre']);
    
    if (!empty($kullanici_adi) && !empty($sifre)) {
        // Personel tablosundan kullanıcıyı kontrol et
        $personel = $db->query("
            SELECT * FROM personel 
            WHERE kullanici_adi = ? 
            AND durum = 'aktif'
        ", [$kullanici_adi]);
        
        if (!empty($personel)) {
            $user = $personel[0];
            
            // Şifre kontrolü (MD5 ile şifrelenmiş)
            if (md5($sifre) === $user['sifre']) {
                // Giriş başarılı - Session değerlerini ayarla
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['personel_id'] = $user['id'];
                $_SESSION['personel_adi'] = $user['ad_soyad'];
                $_SESSION['ad_soyad'] = $user['ad_soyad'];
                
                // Son giriş tarihini güncelle
                try {
                    $db->update('personel', ['son_guncelleme' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
                } catch (Exception $e) {
                    // Hata olsa bile devam et
                }
                
                // Ana sayfaya yönlendir
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Kullanıcı adı veya şifre hatalı!';
            }
        } else {
            $error_message = 'Kullanıcı adı veya şifre hatalı!';
        }
    } else {
        $error_message = 'Lütfen tüm alanları doldurun!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Giriş</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header img {
            max-width: 180px;
            height: auto;
            margin-bottom: 20px;
        }

        .login-header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            outline: none;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message i {
            font-size: 16px;
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 24px;
            }

            .login-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="seomew-logo.png" alt="SeoMEW Logo">
            <h1>Prim Sistemi</h1>
            <p>Hesabınıza giriş yapın</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="kullanici_adi">Kullanıcı Adı</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input 
                        type="text" 
                        id="kullanici_adi" 
                        name="kullanici_adi" 
                        class="form-control" 
                        placeholder="Kullanıcı adınızı girin"
                        required
                        autocomplete="username"
                        value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="sifre">Şifre</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input 
                        type="password" 
                        id="sifre" 
                        name="sifre" 
                        class="form-control" 
                        placeholder="Şifrenizi girin"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Giriş Yap
            </button>
        </form>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> SeoMEW. Tüm hakları saklıdır.
        </div>
    </div>
</body>
</html>