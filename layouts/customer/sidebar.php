<?php $base_url = '/asuransi'; ?>
<div class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-shield-heart"></i> AsuransiKu
    </div>
    <a href="<?php echo $base_url; ?>/customer/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/index.php') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="<?php echo $base_url; ?>/customer/polis/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/polis') !== false) ? 'active' : ''; ?>"><i class="fa-solid fa-file-contract"></i> Polis Saya</a>
    <a href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Tagihan Premi</a>
    <a href="#"><i class="fa-solid fa-file-medical"></i> Klaim Medis</a>
    <a href="#"><i class="fa-solid fa-hospital"></i> Faskes Rekanan</a>
    <a href="<?php echo $base_url; ?>/index.php" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1);"><i class="fa-solid fa-earth-asia"></i> Beranda </a>
    <a href="<?php echo $base_url; ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>
