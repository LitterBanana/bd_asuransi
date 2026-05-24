<?php
    include "db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Asuransi</title>
    <link rel="stylesheet" href="layouts/css/style.css">
</head>
<body>
    <div class="split-layout">
        <div class="left-side">
            <div class="left-content">
                <h2>Bergabung Bersama Kami</h2>
                <p>Dapatkan ketenangan pikiran dengan jaminan kesehatan terbaik. Daftar sekarang dan nikmati kemudahan mengelola asuransi Anda secara digital.</p>
            </div>
        </div>
        
        <div class="right-side">
            <div class="auth-wrapper">
                <div class="register-container">
                    <a href="index.php" style="display: inline-block; margin-bottom: 20px; color: var(--color-slate); text-decoration: none; font-weight: 600;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda</a>
                    <h2>Buat Akun Anda</h2>
                    <form action="register_process.php" method="post">
                        <div class="input-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" placeholder="Masukkan username" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" placeholder="Masukkan email aktif" required style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; font-family: inherit; transition: all 0.3s ease; background: #f9fafb; color: var(--color-dark);">
                        </div>
                        
                        <div class="input-group">
                            <label for="no_telp">Nomor Telepon</label>
                            <input type="text" name="no_telp" id="no_telp" placeholder="Masukkan nomor telepon" required style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; font-family: inherit; transition: all 0.3s ease; background: #f9fafb; color: var(--color-dark);">
                        </div>

                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                        </div>

                        <div class="input-group">
                            <label for="kode_agen">Kode Agen / Referral (Opsional)</label>
                            <input type="text" name="kode_agen" id="kode_agen" placeholder="Contoh: AG-001" value="<?php echo isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : ''; ?>" style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; font-family: inherit; transition: all 0.3s ease; background: #f9fafb; color: var(--color-dark);">
                        </div>

                        <input type="hidden" name="role" id="role" value="Customer">

                        <button type="submit" name="register">Register</button>
                    </form>
                    <a href="login.php" class="auth-link">Sudah Punya Akun? Login Disini</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>