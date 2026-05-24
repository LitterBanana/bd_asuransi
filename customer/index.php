<?php
    include "../layouts/customer/header.php";
    include "../db.php";

    $username = $_SESSION['username'] ?? '';
    $id_pemegang = 0;
    $id_agen_referral = null;
    
    if ($username) {
        $stmt = $conn->prepare("SELECT id_pemegang FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user) {
            $id_pemegang = $user['id_pemegang'];
        }
    }
    
    if ($id_pemegang) {
        $stmtAgenPolis = $conn->prepare("SELECT id_agen FROM polis WHERE id_pemegang = ? AND id_agen IS NOT NULL LIMIT 1");
        $stmtAgenPolis->execute([$id_pemegang]);
        $agenPolis = $stmtAgenPolis->fetch();
        if ($agenPolis) {
            $id_agen_referral = $agenPolis['id_agen'];
        } else {
            $stmtAgenPem = $conn->prepare("SELECT id_agen FROM pemegang_polis WHERE id_pemegang = ? AND id_agen IS NOT NULL");
            $stmtAgenPem->execute([$id_pemegang]);
            $agenPem = $stmtAgenPem->fetch();
            if ($agenPem) {
                $id_agen_referral = $agenPem['id_agen'];
            }
        }
    }

    $agen_referral = null;
    if ($id_agen_referral) {
        $stmtAgenRef = $conn->prepare("SELECT kode_agen, nama_agen FROM agen WHERE id_agen = ?");
        $stmtAgenRef->execute([$id_agen_referral]);
        $agen_referral = $stmtAgenRef->fetch();
    }

    $totalCost = 0;
    $paidAmount = 0;
    $polisAktif = false;
    $no_polis = '';

    // Check if user has an active policy
    $stmtPolis = $conn->prepare("SELECT no_polis, status_polis, is_referral_used FROM polis WHERE id_pemegang = ? AND status_polis = 'Inforce'");
    $stmtPolis->execute([$id_pemegang]);
    $polis = $stmtPolis->fetch();

    $is_referral_used = false;
    $referralMsg = '';

    if ($polis) {
        $polisAktif = true;
        $no_polis = $polis['no_polis'];
        $is_referral_used = $polis['is_referral_used'];

        // Get total tagihan
        $stmtTagihan = $conn->prepare("SELECT SUM(jumlah_tagihan) as total FROM tagihan_premi WHERE no_polis = ?");
        $stmtTagihan->execute([$no_polis]);
        $totalCost = $stmtTagihan->fetch()['total'] ?? 0;

        // Get tagihan terbayar
        $stmtPaid = $conn->prepare("SELECT SUM(jumlah_tagihan) as paid FROM tagihan_premi WHERE no_polis = ? AND status_tagihan = 'Paid'");
        $stmtPaid->execute([$no_polis]);
        $paidAmount = $stmtPaid->fetch()['paid'] ?? 0;
    }

    $remaining = $totalCost - $paidAmount;
    $isPaid = $totalCost > 0 && $remaining <= 0;
    $percentage = ($totalCost > 0) ? round(($paidAmount / $totalCost) * 100) : 0;
    
    // Ambil data transaksi terbaru
    $recentPayments = [];
    if ($no_polis) {
        $stmtTrans = $conn->prepare("
            SELECT pp.metode_bayar as payment_method, pp.bank_name, pp.tanggal_bayar as payment_date, pp.nominal_bayar as amount, pp.status_pembayaran as status
            FROM pembayaran_premi pp
            JOIN tagihan_premi tp ON pp.no_tagihan = tp.no_tagihan
            WHERE tp.no_polis = ?
            ORDER BY pp.tanggal_bayar DESC LIMIT 5
        ");
        $stmtTrans->execute([$no_polis]);
        $recentPayments = $stmtTrans->fetchAll(PDO::FETCH_OBJ);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['referral_code'])) {
        $referral_code = trim($_POST['referral_code']);
        // Validasi agen
        $stmtAgen = $conn->prepare("SELECT id_agen FROM agen WHERE kode_agen = ? AND status_aktif = 'Aktif'");
        $stmtAgen->execute([$referral_code]);
        $agen = $stmtAgen->fetch();
        if ($agen) {
            $id_agen = $agen['id_agen'];
            try {
                $conn->beginTransaction();
                
                // Update polis
                $stmtUpdatePolis = $conn->prepare("UPDATE polis SET id_agen = ?, is_referral_used = TRUE WHERE no_polis = ?");
                $stmtUpdatePolis->execute([$id_agen, $no_polis]);

                // Beri diskon ke tagihan Unpaid terdekat
                $stmtTagihan = $conn->prepare("SELECT no_tagihan, jumlah_tagihan FROM tagihan_premi WHERE no_polis = ? AND status_tagihan = 'Unpaid' ORDER BY jatuh_tempo ASC LIMIT 1");
                $stmtTagihan->execute([$no_polis]);
                $tagihan = $stmtTagihan->fetch();
                
                if ($tagihan) {
                    $diskon = 50000;
                    $newAmount = max(0, $tagihan['jumlah_tagihan'] - $diskon);
                    $stmtUpdateTagihan = $conn->prepare("UPDATE tagihan_premi SET jumlah_tagihan = ? WHERE no_tagihan = ?");
                    $stmtUpdateTagihan->execute([$newAmount, $tagihan['no_tagihan']]);
                    // Update totalCost for display
                    $totalCost -= ($tagihan['jumlah_tagihan'] - $newAmount);
                    $remaining = $totalCost - $paidAmount;
                    $percentage = ($totalCost > 0) ? round(($paidAmount / $totalCost) * 100) : 0;
                }
                
                $conn->commit();
                $referralMsg = "<div class='alert alert-success' style='padding: 15px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;'>Kode referral berhasil digunakan. Anda mendapatkan diskon Rp 50.000 pada tagihan berikutnya!</div>";
                $is_referral_used = true;
            } catch (Exception $e) {
                $conn->rollBack();
                $referralMsg = "<div class='alert alert-danger' style='padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;'>Terjadi kesalahan sistem.</div>";
            }
        } else {
            $referralMsg = "<div class='alert alert-danger' style='padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;'>Kode referral tidak valid atau agen tidak aktif.</div>";
        }
    }
?>



<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Dashboard Overview</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Customer'); ?>! Pantau progress pembayaran premi asuransi Anda.</p>
</div>

<?php if ($agen_referral): ?>
<div style="margin-bottom: 25px; display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 18px; border-radius: 8px; font-size: 14px; color: #1e293b; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
    <i class="fa-solid fa-user-tie" style="color: #3b82f6; margin-right: 8px; font-size: 16px;"></i>
    Agen Pendamping Anda: <strong><?php echo htmlspecialchars($agen_referral['nama_agen']); ?></strong> 
    <span style="color: #64748b; font-size: 13px; margin-left: 5px;">(Kode: <?php echo htmlspecialchars($agen_referral['kode_agen']); ?>)</span>
</div>
<?php endif; ?>

<?php echo $referralMsg; ?>

<?php if($polisAktif && !$is_referral_used && !$agen_referral): ?>
<div class="admin-card" style="margin-bottom: 25px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6;">
    <div class="admin-card-body" style="padding: 20px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h3 style="margin: 0 0 5px 0; color: #1e3a8a; font-size: 18px;"><i class="fa-solid fa-gift" style="margin-right: 8px;"></i> Punya Kode Referral?</h3>
            <p style="margin: 0; color: #1e40af; font-size: 14px;">Masukkan kode agen Anda untuk mendapatkan potongan Rp 50.000 pada tagihan bulan ini!</p>
        </div>
        <form method="POST" style="display: flex; gap: 10px;">
            <input type="text" name="referral_code" placeholder="Kode Agen (Contoh: AG-001)" required style="padding: 10px 15px; border: 1px solid #bfdbfe; border-radius: 6px; outline: none;">
            <button type="submit" class="btn" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer;">Klaim</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="admin-stats-grid">
    <div class="admin-stat-card blue">
        <div class="admin-stat-icon blue"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="admin-stat-content">
            <h4>Total Tagihan Premi</h4>
            <h2>Rp <?php echo number_format($totalCost, 0, ',', '.'); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card green">
        <div class="admin-stat-icon green"><i class="fa-solid fa-check-to-slot"></i></div>
        <div class="admin-stat-content">
            <h4>Sudah Dibayar</h4>
            <h2>Rp <?php echo number_format($paidAmount, 0, ',', '.'); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card orange">
        <div class="admin-stat-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="admin-stat-content">
            <h4>Sisa Tagihan</h4>
            <h2>Rp <?php echo number_format($remaining, 0, ',', '.'); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card purple">
        <div class="admin-stat-icon purple"><i class="fa-solid fa-chart-pie"></i></div>
        <div class="admin-stat-content">
            <h4>Status</h4>
            <div style="margin-top: 5px;">
              <?php if($totalCost == 0): ?>
                <span class="badge badge-neutral" style="font-size: 13px;">Belum Ada Tagihan</span>
              <?php elseif($isPaid): ?>
                <span class="badge badge-success" style="font-size: 13px;">Lunas</span>
              <?php else: ?>
                <span class="badge badge-warning" style="font-size: 13px;">Belum Lunas</span>
              <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-grid" style="margin-bottom: 25px;">
    <!-- Payment Progress -->
    <div class="admin-card" style="display: flex; flex-direction: column; height: 100%;">
        <div class="admin-card-header">
            <h3>Progress Pembayaran</h3>
            <?php if($polisAktif): ?>
              <span class="badge badge-info badge-dot">Polis Aktif</span>
            <?php else: ?>
              <span class="badge badge-neutral badge-dot">Belum Ada Polis</span>
            <?php endif; ?>
        </div>
        <div class="admin-card-body" style="padding: 25px; display: flex; flex-direction: column; flex-grow: 1;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                <span style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo $percentage; ?>% <small style="font-size: 14px; color: #64748b; font-weight: normal;">terbayar</small></span>
            </div>
            <div style="background: #e2e8f0; border-radius: 10px; height: 12px; width: 100%; overflow: hidden; margin-bottom: 20px;">
                <div style="background: <?php echo $isPaid ? '#10b981' : '#3b82f6'; ?>; height: 100%; width: <?php echo $percentage; ?>%; border-radius: 10px; transition: 0.5s ease;"></div>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Dibayar</div>
                    <div style="font-size: 15px; font-weight: 600; color: #1e293b;">Rp <?php echo number_format($paidAmount, 0, ',', '.'); ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Sisa</div>
                    <div style="font-size: 15px; font-weight: 600; color: #f59e0b;">Rp <?php echo number_format($remaining, 0, ',', '.'); ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Total</div>
                    <div style="font-size: 15px; font-weight: 600; color: #1e293b;">Rp <?php echo number_format($totalCost, 0, ',', '.'); ?></div>
                </div>
            </div>
            <div style="margin-top: auto; padding-top: 25px;">
                <a href="tagihan/index.php" class="btn" style="width: 100%; text-align: center; display: block; background: #3b82f6; color: white; padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                    Lihat Detail Tagihan
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-card" style="display: flex; flex-direction: column; height: 100%;">
        <div class="admin-card-header">
            <h3>Aksi Cepat</h3>
        </div>
        <div class="admin-card-body" style="padding: 25px; display: flex; flex-direction: column; flex-grow: 1; justify-content: center; gap: 15px;">
            <a href="tagihan/index.php" style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--color-border-light); border-radius: 10px; text-decoration: none; transition: 0.2s;">
                <div style="width: 45px; height: 45px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">Bayar Premi</div>
                    <div style="font-size: 13px; color: #64748b;">Lakukan pembayaran tagihan premi Anda</div>
                </div>
            </a>
            <a href="polis/index.php" style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--color-border-light); border-radius: 10px; text-decoration: none; transition: 0.2s;">
                <div style="width: 45px; height: 45px; border-radius: 10px; background: #f5f3ff; color: var(--color-slate); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">Profil Polis Saya</div>
                    <div style="font-size: 13px; color: #64748b;">Kelola informasi akun dan tanggungan</div>
                </div>
            </a>
            <a href="klaim/index.php" style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--color-border-light); border-radius: 10px; text-decoration: none; transition: 0.2s;">
                <div style="width: 45px; height: 45px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <div>
                    <div style="font-weight: 600; color: #1e293b; font-size: 15px;">Pengajuan Klaim</div>
                    <div style="font-size: 13px; color: #64748b;">Pantau dan ajukan klaim asuransi</div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Riwayat Pembayaran Terbaru</h3>
        <a href="tagihan/index.php" class="btn btn-ghost btn-sm" style="font-size: 12px; color: #3b82f6;">Lihat Semua</a>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Metode Pembayaran</th>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($recentPayments)): ?>
                    <?php foreach($recentPayments as $payment): ?>
                    <tr>
                        <td style="font-weight: 600; color: #1e293b;">
                            <i class="fa-solid <?php echo $payment->payment_method === 'transfer' ? 'fa-building-columns' : 'fa-money-bill-wave'; ?>" style="color: #94a3b8; margin-right: 8px;"></i>
                            <?php echo ucfirst($payment->payment_method); ?><?php echo $payment->bank_name ? ' - ' . strtoupper($payment->bank_name) : ''; ?>
                        </td>
                        <td style="color: #64748b;"><?php echo date('d M Y', strtotime($payment->payment_date)); ?></td>
                        <td style="font-weight: 600;">Rp <?php echo number_format($payment->amount, 0, ',', '.'); ?></td>
                        <td>
                            <?php if($payment->status === 'Verified'): ?>
                                <span class="badge badge-success" style="font-size: 11px;">Terverifikasi</span>
                            <?php elseif($payment->status === 'Rejected'): ?>
                                <span class="badge badge-danger" style="font-size: 11px;">Ditolak</span>
                            <?php else: ?>
                                <span class="badge badge-warning" style="font-size: 11px;">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada riwayat transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include "../layouts/customer/footer.php";
?>