<?php
    include "../layouts/customer/header.php";
?>

<div class="card">
    <h3>Selamat Datang di Portal Customer</h3>
    <p>Halo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>! Selamat datang di dashboard Anda.</p>
    <br>
    <p>Di sini Anda dapat melihat status polis asuransi Anda, mengecek riwayat tagihan premi, mencari fasilitas kesehatan terdekat, serta mengajukan dan melacak status klaim medis dengan mudah dan transparan.</p>
    <br>
    <p>Gunakan menu di sebelah kiri untuk menavigasi fitur-fitur yang tersedia sesuai dengan hak akses Anda sebagai nasabah.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
    <div class="card" style="margin-bottom: 0;">
        <h4 style="color: var(--color-blue); margin-bottom: 10px;">Status Polis Utama</h4>
        <h2 style="color: var(--color-aqua); margin-bottom: 5px;"><i class="fa-solid fa-circle-check"></i> Aktif</h2>
        <p style="font-size: 14px; color: #666;">Jatuh tempo berikutnya: <?php echo date('d M Y', strtotime('+1 month')); ?></p>
    </div>
    
    <div class="card" style="margin-bottom: 0;">
        <h4 style="color: var(--color-blue); margin-bottom: 10px;">Tagihan Tertunda</h4>
        <h2 style="color: var(--color-slate); margin-bottom: 5px;">Rp 0</h2>
        <p style="font-size: 14px; color: #666;">Semua tagihan Anda telah lunas.</p>
    </div>
    
    <div class="card" style="margin-bottom: 0;">
        <h4 style="color: var(--color-blue); margin-bottom: 10px;">Klaim dalam Proses</h4>
        <h2 style="color: var(--color-dark); margin-bottom: 5px;">0 Klaim</h2>
        <p style="font-size: 14px; color: #666;">Tidak ada pengajuan klaim saat ini.</p>
    </div>
</div>

<?php
    include "../layouts/customer/footer.php";
?>