<?php
/**
 * Primew Panel - Giriş Sayfası
 */

// Session'ı en başta başlat
session_start();

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

// Veritabanı karakter setini ayarla
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("SET CHARACTER SET utf8mb4");

$error_message = '';

// Giriş işlemi
if ($_POST && isset($_POST['kullanici_adi']) && isset($_POST['sifre'])) {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];
    
    if (!empty($kullanici_adi) && !empty($sifre)) {
        // Kullanıcıyı veritabanından bul
        $kullanici = $db->query("
            SELECT k.*, p.ad_soyad as personel_adi 
            FROM kullanicilar k
            LEFT JOIN personel p ON k.personel_id = p.id
            WHERE k.kullanici_adi = ? AND k.durum = 'aktif'
        ", [$kullanici_adi]);
        
        if (!empty($kullanici)) {
            $user = $kullanici[0];
            
            // Şifre kontrolü (MD5 ile şifrelenmiş)
            if (md5($sifre) === $user['sifre']) {
                // Giriş başarılı
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                
                // Kullanıcı adına göre ad_soyad belirle
                if ($user['kullanici_adi'] === 'admin') {
                    $_SESSION['ad_soyad'] = 'Uğur İlhan';
                } elseif ($user['kullanici_adi'] === 'seyma') {
                    $_SESSION['ad_soyad'] = 'Şeyma';
                } elseif ($user['kullanici_adi'] === 'mehmet') {
                    $_SESSION['ad_soyad'] = 'Mehmet Kaya';
                } else {
                    $_SESSION['ad_soyad'] = $user['ad_soyad'];
                }
                
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['personel_id'] = $user['personel_id'];
                $_SESSION['personel_adi'] = $user['personel_adi'];
                
                // Son giriş tarihini güncelle
                $db->update('kullanicilar', ['son_giris' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                $error_message = 'Kullanıcı adı veya şifre hatalı!';
            }
        } else {
            $error_message = 'Kullanıcı adı veya şifre hatalı!';
        }
    } else {
        $error_message = 'Kullanıcı adı ve şifre gereklidir!';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeoMEW Prim Sistemi - Giriş</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="seomew-logo.png" alt="SeoMEW Logo" class="login-logo">
                <h1>SeoMEW Prim Sistemi</h1>
                <p>Lütfen giriş yapın</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="kullanici_adi" name="kullanici_adi" 
                               value="<?php echo isset($_POST['kullanici_adi']) ? htmlspecialchars($_POST['kullanici_adi']) : ''; ?>" 
                               required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sifre">Şifre</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="sifre" name="sifre" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </button>
            </form>
            
            <div class="login-footer">
                
            </div>
        </div>
    </div>

    <style>
        .login-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-logo {
            height: 60px;
            margin-bottom: 20px;
        }

        .login-header h1 {
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .login-header p {
            color: #64748b;
            margin: 0;
        }

        .login-form {
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }

        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 14px;
        }

        .login-footer p {
            margin: 5px 0;
        }

        .login-footer strong {
            color: #1e293b;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
    </style>
</body>
</html>
