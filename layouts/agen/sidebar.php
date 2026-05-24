<?php $base_url = '/asuransi'; ?>
<div class="sidebar admin-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fa-solid fa-shield-heart" style="color: #10b981;"></i>
        <span>AsuransiKu <small style="color: #94a3b8; font-size: 12px; margin-left: 5px;">AGEN</small></span>
    </div>
    <div class="sidebar-menu">
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Menu Utama</div>
        <a href="<?php echo $base_url; ?>/agen/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/index.php') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Manajemen Nasabah</div>
        <a href="<?php echo $base_url; ?>/agen/nasabah/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/nasabah') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Daftar Nasabah
        </a>
        <a href="<?php echo $base_url; ?>/agen/tagihan/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/tagihan') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Pantau Tagihan
        </a>
        <a href="<?php echo $base_url; ?>/agen/klaim/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/klaim') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-notes-medical"></i> Progres Klaim
        </a>
        <a href="<?php echo $base_url; ?>/agen/laporan/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/laporan') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice"></i> Laporan Bulanan
        </a>
        
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Katalog</div>
        <a href="<?php echo $base_url; ?>/agen/katalog/produk.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/katalog/produk') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-list"></i> Produk Asuransi
        </a>
        <a href="<?php echo $base_url; ?>/agen/katalog/faskes.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/katalog/faskes') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-hospital"></i> Faskes Rekanan
        </a>
        
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Sistem</div>
        <a href="<?php echo $base_url; ?>/agen/profile/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/agen/profile') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-gear"></i> Profil Saya
        </a>
        <a href="<?php echo $base_url; ?>/index.php" style="color: #cbd5e1;">
            <i class="fa-solid fa-earth-asia"></i> Beranda Web
        </a>
        <a href="<?php echo $base_url; ?>/logout.php" style="color: #ef4444;">
            <i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Logout
        </a>
    </div>
</div>
