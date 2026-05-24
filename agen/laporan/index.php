<?php
$base_path = "../../";
include "../../layouts/agen/header.php";
include "../../db.php";

$bulan = $_GET['bulan'] ?? date('Y-m');
$nama_bulan = date('F Y', strtotime($bulan . '-01'));
?>
<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Laporan Bulanan Agen</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Pilih periode bulan dan cetak laporan yang dibutuhkan.</p>
</div>

<div class="admin-card" style="margin-bottom: 25px;">
    <div class="admin-card-body" style="padding: 20px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div>
                <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px; font-weight: 500;">Pilih Bulan Laporan</label>
                <input type="month" name="bulan" value="<?php echo htmlspecialchars($bulan); ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; width: 250px;">
            </div>
            <button type="submit" class="btn" style="background: #1e293b; color: white; padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600;">
                Tampilkan
            </button>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
    
    <!-- Laporan Pembayaran & Komisi -->
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 25px; text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 15px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 15px;">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #1e293b;">Laporan Pembayaran & Komisi</h3>
            <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px;">Daftar tagihan yang dibayar beserta perhitungan komisi agen.</p>
            <a href="print_pembayaran.php?bulan=<?php echo urlencode($bulan); ?>" target="_blank" class="btn" style="display: inline-block; background: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; width: 100%; box-sizing: border-box;">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </a>
        </div>
    </div>

    <!-- Pertumbuhan Nasabah -->
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 25px; text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 15px; background: #f0fdf4; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 15px;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #1e293b;">Laporan Pertumbuhan</h3>
            <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px;">Daftar nasabah baru dan polis baru yang diakuisisi bulan ini.</p>
            <a href="print_pertumbuhan.php?bulan=<?php echo urlencode($bulan); ?>" target="_blank" class="btn" style="display: inline-block; background: #10b981; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; width: 100%; box-sizing: border-box;">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </a>
        </div>
    </div>

    <!-- Laporan Klaim -->
    <div class="admin-card">
        <div class="admin-card-body" style="padding: 25px; text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 15px; background: #fffbeb; color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 15px;">
                <i class="fa-solid fa-file-medical"></i>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #1e293b;">Laporan Klaim Nasabah</h3>
            <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px;">Riwayat pengajuan klaim medis dari seluruh klien Anda.</p>
            <a href="print_klaim.php?bulan=<?php echo urlencode($bulan); ?>" target="_blank" class="btn" style="display: inline-block; background: #f59e0b; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; width: 100%; box-sizing: border-box;">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </a>
        </div>
    </div>

</div>

<?php include "../../layouts/agen/footer.php"; ?>
