<?php
$base_path = "../";
include "../layouts/agen/header.php";
include "../db.php";

$username = $_SESSION['username'] ?? '';

// Ambil id_agen
$stmtUser = $conn->prepare("SELECT u.id_agen, a.nama_agen, a.kode_agen, a.persentase_komisi FROM users u JOIN agen a ON u.id_agen = a.id_agen WHERE u.username = ?");
$stmtUser->execute([$username]);
$agen = $stmtUser->fetch();

$id_agen = $agen['id_agen'];
$persentase = $agen['persentase_komisi'];

// 1. Total Nasabah
$stmtNasabah = $conn->prepare("SELECT COUNT(DISTINCT id_pemegang) as total FROM polis WHERE id_agen = ?");
$stmtNasabah->execute([$id_agen]);
$totalNasabah = $stmtNasabah->fetch()['total'] ?? 0;

// 2. Polis Aktif
$stmtPolis = $conn->prepare("SELECT COUNT(*) as total FROM polis WHERE id_agen = ? AND status_polis = 'Inforce'");
$stmtPolis->execute([$id_agen]);
$totalPolis = $stmtPolis->fetch()['total'] ?? 0;

// 3. Komisi Bulan Ini
$bulanIni = date('Y-m');
$stmtKomisi = $conn->prepare("
    SELECT pb.nominal_bayar, p.id_pemegang
    FROM pembayaran_premi pb
    JOIN tagihan_premi tp ON pb.no_tagihan = tp.no_tagihan
    JOIN polis p ON tp.no_polis = p.no_polis 
    WHERE p.id_agen = ? AND pb.status_pembayaran = 'Verified' AND DATE_FORMAT(pb.tanggal_bayar, '%Y-%m') = ?
");
$stmtKomisi->execute([$id_agen, $bulanIni]);
$pembayaranBulanIni = $stmtKomisi->fetchAll();

$komisiBulanIni = 0;
$nasabahUnik = [];
foreach ($pembayaranBulanIni as $bayar) {
    // Hitung komisi per tagihan
    $komisiPerTagihan = ($bayar['nominal_bayar'] * $persentase) / 100;
    $komisiBulanIni += $komisiPerTagihan;
    $nasabahUnik[$bayar['id_pemegang']] = true;
}
$nasabahBayar = count($nasabahUnik);

// 4. Klaim Berjalan
$stmtKlaim = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM klaim_medis km 
    JOIN polis p ON km.no_polis = p.no_polis 
    WHERE p.id_agen = ? AND km.status_klaim IN ('Pending', 'Investigasi')
");
$stmtKlaim->execute([$id_agen]);
$klaimPending = $stmtKlaim->fetch()['total'] ?? 0;

// Daftar Nasabah & Polis Terbaru (5 terbaru)
$stmtTerbaru = $conn->prepare("
    SELECT p.no_polis, p.status_polis, p.tanggal_terbit, pp.nama_lengkap, prd.nama_produk
    FROM polis p
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    JOIN produk_asuransi prd ON p.id_produk = prd.id_produk
    WHERE p.id_agen = ?
    ORDER BY p.tanggal_terbit DESC LIMIT 5
");
$stmtTerbaru->execute([$id_agen]);
$polisTerbaru = $stmtTerbaru->fetchAll();
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Dashboard Overview</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Halo, <?php echo htmlspecialchars($agen['nama_agen']); ?>. Pantau perkembangan nasabah Anda.</p>
</div>

<!-- Referral Link Section -->
<div class="admin-card" style="margin-bottom: 25px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6;">
    <div class="admin-card-body" style="padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 style="margin: 0 0 5px 0; color: #1e3a8a; font-size: 18px;"><i class="fa-solid fa-link" style="margin-right: 8px;"></i> Link Referral Pendaftaran</h3>
            <p style="margin: 0; color: #1e40af; font-size: 14px;">Bagikan link ini agar klien langsung terhubung dengan akun agen Anda saat mendaftar.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" id="refLink" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . '/register.php?ref=' . urlencode($agen['kode_agen']); ?>" readonly style="padding: 10px 15px; border: 1px solid #bfdbfe; border-radius: 6px; outline: none; width: 300px; background: #fff; color: #1e293b;">
            <button onclick="copyToClipboard('refLink')" class="btn" style="background: #2563eb; color: white; padding: 10px 15px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer;"><i class="fa-solid fa-copy" style="margin-right: 5px;"></i> Salin Link</button>
            <button onclick="copyText('<?php echo htmlspecialchars($agen['kode_agen']); ?>')" class="btn" style="background: #fff; color: #2563eb; padding: 10px 15px; border-radius: 6px; font-weight: 600; border: 1px solid #2563eb; cursor: pointer;"><i class="fa-solid fa-code" style="margin-right: 5px;"></i> Salin Kode</button>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(() => {
        alert("Link referral berhasil disalin: " + copyText.value);
    });
}
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert("Kode agen berhasil disalin: " + text);
    });
}
</script>

<!-- Stats Grid -->
<div class="admin-stats-grid">
    <div class="admin-stat-card blue">
        <div class="admin-stat-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="admin-stat-content">
            <h4>Total Nasabah</h4>
            <h2><?php echo number_format($totalNasabah); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card green">
        <div class="admin-stat-icon green"><i class="fa-solid fa-file-shield"></i></div>
        <div class="admin-stat-content">
            <h4>Polis Aktif</h4>
            <h2><?php echo number_format($totalPolis); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card purple">
        <div class="admin-stat-icon purple"><i class="fa-solid fa-rupiah-sign"></i></div>
        <div class="admin-stat-content">
            <h4>Komisi Bulan Ini (<?php echo floatval($persentase); ?>%)</h4>
            <h2 style="margin-bottom: 2px;">Rp <?php echo number_format($komisiBulanIni, 0, ',', '.'); ?></h2>
            <div style="font-size: 11px; color: #8b5cf6; font-weight: 500;">Dari <?php echo $nasabahBayar; ?> nasabah yang bayar</div>
        </div>
    </div>
    <div class="admin-stat-card orange">
        <div class="admin-stat-icon orange"><i class="fa-solid fa-truck-medical"></i></div>
        <div class="admin-stat-content">
            <h4>Klaim Berjalan</h4>
            <h2><?php echo number_format($klaimPending); ?></h2>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 25px;">
    
    <!-- Recent Policies -->
    <div class="admin-card" style="display: flex; flex-direction: column; height: 100%;">
        <div class="admin-card-header">
            <h3>Polis Nasabah Terbaru</h3>
            <a href="nasabah/index.php" class="btn btn-ghost btn-sm" style="font-size: 12px; color: #3b82f6;">Lihat Semua</a>
        </div>
        <div class="admin-card-body" style="overflow-x: auto; padding: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No. Polis</th>
                        <th>Nasabah</th>
                        <th>Produk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($polisTerbaru) > 0): ?>
                        <?php foreach($polisTerbaru as $p): ?>
                        <tr>
                            <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($p['no_polis']); ?></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($p['nama_produk']); ?></td>
                            <td>
                                <?php if($p['status_polis'] == 'Inforce'): ?>
                                    <span class="badge badge-success" style="font-size: 11px;">Inforce</span>
                                <?php elseif($p['status_polis'] == 'Pending Approval'): ?>
                                    <span class="badge badge-warning" style="font-size: 11px;">Pending Approval</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral" style="font-size: 11px;"><?php echo htmlspecialchars($p['status_polis']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada polis terbaru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-card" style="display: flex; flex-direction: column; height: 100%;">
        <div class="admin-card-header">
            <h3>Aksi Cepat</h3>
        </div>
        <div class="admin-card-body" style="padding: 25px; display: flex; flex-direction: column; flex-grow: 1; justify-content: center; gap: 15px;">
            <a href="nasabah/index.php" style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--color-border-light); border-radius: 10px; text-decoration: none; transition: 0.2s;">
                <div style="width: 45px; height: 45px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">Tambah Nasabah</div>
                    <div style="font-size: 13px; color: #64748b;">Daftarkan prospek baru</div>
                </div>
            </a>
            <a href="katalog/produk.php" style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--color-border-light); border-radius: 10px; text-decoration: none; transition: 0.2s;">
                <div style="width: 45px; height: 45px; border-radius: 10px; background: #f5f3ff; color: var(--color-slate); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">Katalog Produk</div>
                    <div style="font-size: 13px; color: #64748b;">Lihat daftar produk asuransi</div>
                </div>
            </a>
            <a href="tagihan/index.php" style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--color-border-light); border-radius: 10px; text-decoration: none; transition: 0.2s;">
                <div style="width: 45px; height: 45px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">Pantau Tagihan</div>
                    <div style="font-size: 13px; color: #64748b;">Cek tagihan premi klien</div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include "../layouts/agen/footer.php"; ?>
