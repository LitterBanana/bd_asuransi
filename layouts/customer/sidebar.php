<?php $base_url = '/asuransi'; ?>
<div class="sidebar admin-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fa-solid fa-shield-heart" style="color: #10b981;"></i>
        <span>AsuransiKu <small style="color: #94a3b8; font-size: 12px; margin-left: 5px;">CUST</small></span>
    </div>
    <div class="sidebar-menu">
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Menu Utama</div>
        <a href="<?php echo $base_url; ?>/customer/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/index.php') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Layanan Asuransi</div>
        <a href="<?php echo $base_url; ?>/customer/polis/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/polis') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-contract"></i> Polis Saya
        </a>
        <a href="<?php echo $base_url; ?>/customer/tagihan/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/tagihan') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Tagihan Premi
        </a>
        <a href="<?php echo $base_url; ?>/customer/klaim/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/klaim') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-notes-medical"></i> Klaim Medis
        </a>
        <a href="<?php echo $base_url; ?>/customer/faskes/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/faskes') !== false) ? 'active' : ''; ?>">
            <i class="fa-solid fa-hospital"></i> Faskes Rekanan
        </a>
        
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Sistem</div>
        <a href="<?php echo $base_url; ?>/customer/profile/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/customer/profile') !== false) ? 'active' : ''; ?>">
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
