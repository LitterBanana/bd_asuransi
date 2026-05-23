<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AsuransiKu - Lindungi Masa Depan Anda</title>
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="layouts/css/public.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-shield-heart"></i> AsuransiKu
        </a>
        <div class="menu-toggle" id="mobile-menu">
            <i class="fa-solid fa-bars"></i>
        </div>
        <div class="nav-menu" id="nav-menu">
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="keunggulan.php">Keunggulan</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="faskes.php">Faskes Rekanan</a></li>
            </ul>
            <div class="nav-actions">
                <?php if(isset($_SESSION['username'])): ?>
                    <?php 
                        $dashboard_link = "customer/index.php"; // default
                        if(isset($_SESSION['role'])) {
                            $role = strtolower($_SESSION['role']);
                            if($role == 'admin') $dashboard_link = "admin/index.php";
                            if($role == 'agen') $dashboard_link = "agen/index.php";
                        }
                    ?>
                    <a href="<?php echo $dashboard_link; ?>" class="btn btn-outline"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    <a href="logout.php" class="btn btn-solid"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Masuk</a>
                    <a href="register.php" class="btn btn-solid">Daftar Sekarang</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
