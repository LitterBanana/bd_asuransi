<div class="sidebar admin-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>AsuransiKu <small style="color: #94a3b8; font-size: 12px; margin-left: 5px;">ADMIN</small></span>
    </div>
    <div class="sidebar-menu">
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Menu Utama</div>
        <a href="<?php echo $base_url; ?>/admin/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/index.php') !== false || $_SERVER['REQUEST_URI'] == '/asuransi/admin/' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        
        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Data Master</div>
        <a href="<?php echo $base_url; ?>/admin/produk/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/produk/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-box-open"></i> Produk Asuransi
        </a>
        <a href="<?php echo $base_url; ?>/admin/faskes/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/faskes/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-hospital"></i> Faskes Rekanan
        </a>
        <a href="<?php echo $base_url; ?>/admin/agen/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/agen/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-tie"></i> Data Agen
        </a>
        <a href="<?php echo $base_url; ?>/admin/penyakit/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/penyakit/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-virus"></i> Kategori Penyakit
        </a>
        <a href="<?php echo $base_url; ?>/admin/user/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/user/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Akun Pengguna
        </a>

        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Operasional</div>
        <a href="<?php echo $base_url; ?>/admin/polis/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/polis/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-contract"></i> Manajemen Polis
        </a>
        <a href="<?php echo $base_url; ?>/admin/tagihan/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/tagihan/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Tagihan & Premi
        </a>
        <a href="<?php echo $base_url; ?>/admin/klaim/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/klaim/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-notes-medical"></i> Persetujuan Klaim
        </a>
        <a href="<?php echo $base_url; ?>/admin/laporan/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/laporan/') !== false ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice"></i> Laporan Bulanan
        </a>


        <div style="padding: 15px 25px 5px; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 1px;">Pengaturan</div>
        <a href="<?php echo $base_url; ?>/logout.php" style="color: #ef4444;">
            <i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Keluar
        </a>
    </div>
</div>
