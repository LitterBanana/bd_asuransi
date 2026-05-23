<?php
    include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Asuransi</title>
    <link rel="stylesheet" href="layouts/css/style.css">
</head>
<body>
    <div class="split-layout">
        <div class="left-side">
            <div class="left-content">
                <h2>Selamat Datang di Portal Asuransi</h2>
                <p>Melindungi masa depan Anda dan keluarga dengan layanan asuransi kesehatan yang terpercaya, cepat, dan transparan. Silakan masuk untuk mengakses polis Anda.</p>
            </div>
        </div>
        
        <div class="right-side">
            <div class="auth-wrapper">
                <div class="login-container">
                    <a href="index.php" style="display: inline-block; margin-bottom: 20px; color: var(--color-slate); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda</a>
                    <h1>Login Akun</h1>
                    <form action="login_process.php" method="post">
                        <div class="input-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" placeholder="Masukkan username" required>
                        </div>
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                        </div>
                        <button type="submit" name="login">Login</button>
                    </form>
                    <a href="register.php" class="auth-link">Belum Punya Akun? Register Disini</a>
                </div>  
            </div>
        </div>
    </div>
</body>
</html>