<?php
    include "../layouts/customer/header.php";
    include "../db.php";

    $username = $_SESSION['username'] ?? '';
    $id_pemegang = 0;
    
    if ($username) {
        $stmt = $conn->prepare("SELECT id_pemegang FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user) {
            $id_pemegang = $user['id_pemegang'];
        }
    }

    $totalCost = 0;
    $paidAmount = 0;
    $polisAktif = false;
    $no_polis = '';

    // Check if user has an active policy
    $stmtPolis = $conn->prepare("SELECT no_polis, status_polis FROM polis WHERE id_pemegang = ? AND status_polis = 'Inforce'");
    $stmtPolis->execute([$id_pemegang]);
    $polis = $stmtPolis->fetch();

    if ($polis) {
        $polisAktif = true;
        $no_polis = $polis['no_polis'];

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
?>



<!-- Welcome Banner -->
<section class="welcome-banner animate-fade-in-up" aria-label="Banner selamat datang">
  <div class="welcome-content">
    <h2>Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Customer'); ?>!</h2>
    <p>Pantau progress pembayaran premi asuransi Anda dan lakukan pembayaran dengan mudah melalui sistem kami.</p>
    <a href="#" class="btn btn-lg" style="display: inline-flex; align-items: center; gap: 8px;">
      <i class="fa-solid fa-credit-card"></i> Bayar Sekarang
    </a>
  </div>
</section>

<!-- Stats Grid -->
<section class="stats-grid" aria-label="Statistik pembayaran">
  <div class="stat-card animate-fade-in-up delay-1">
    <div class="stat-card-icon primary"><i class="fa-solid fa-file-invoice-dollar"></i></div>
    <div class="stat-card-label">Total Tagihan Premi</div>
    <div class="stat-card-value">Rp <?php echo number_format($totalCost, 0, ',', '.'); ?></div>
  </div>
  <div class="stat-card animate-fade-in-up delay-2">
    <div class="stat-card-icon success"><i class="fa-solid fa-check-to-slot"></i></div>
    <div class="stat-card-label">Sudah Dibayar</div>
    <div class="stat-card-value">Rp <?php echo number_format($paidAmount, 0, ',', '.'); ?></div>
  </div>
  <div class="stat-card animate-fade-in-up delay-3">
    <div class="stat-card-icon warning"><i class="fa-solid fa-hourglass-half"></i></div>
    <div class="stat-card-label">Sisa Tagihan</div>
    <div class="stat-card-value">Rp <?php echo number_format($remaining, 0, ',', '.'); ?></div>
  </div>
  <div class="stat-card animate-fade-in-up delay-4">
    <div class="stat-card-icon info"><i class="fa-solid fa-chart-pie"></i></div>
    <div class="stat-card-label">Status</div>
    <div class="stat-card-value">
      <?php if($totalCost == 0): ?>
        <span class="badge badge-neutral badge-dot">Belum Ada Tagihan</span>
      <?php elseif($isPaid): ?>
        <span class="badge badge-success badge-dot">Lunas</span>
      <?php else: ?>
        <span class="badge badge-warning badge-dot">Belum Lunas</span>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Quick Actions -->
<section aria-label="Aksi cepat">
  <div class="section-header" style="margin-bottom: var(--space-4);">
    <div>
      <h2 class="section-title" style="margin-bottom: 0;">Aksi Cepat</h2>
      <p class="section-subtitle" style="color: var(--color-text-secondary); margin-top: 5px;">Akses fitur utama dengan cepat</p>
    </div>
  </div>
  <div class="quick-actions">
    <a href="#" class="quick-action-card animate-fade-in-up delay-2">
      <div class="quick-action-icon pay"><i class="fa-solid fa-credit-card"></i></div>
      <div>
        <div class="quick-action-title">Bayar Premi</div>
        <p class="quick-action-desc">Lakukan pembayaran tagihan premi Anda</p>
      </div>
    </a>
    <a href="#" class="quick-action-card animate-fade-in-up delay-3">
      <div class="quick-action-icon invoice"><i class="fa-solid fa-file-invoice"></i></div>
      <div>
        <div class="quick-action-title">Riwayat Tagihan</div>
        <p class="quick-action-desc">Lihat & cetak invoice pembayaran</p>
      </div>
    </a>
    <a href="#" class="quick-action-card animate-fade-in-up delay-4">
      <div class="quick-action-icon profile"><i class="fa-solid fa-user-gear"></i></div>
      <div>
        <div class="quick-action-title">Profil Saya</div>
        <p class="quick-action-desc">Kelola informasi akun polis Anda</p>
      </div>
    </a>
  </div>
</section>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
  <!-- Payment Progress -->
  <section class="payment-progress-card animate-fade-in-up delay-3" aria-label="Progress pembayaran">
    <div class="card-header" style="border-bottom: none; padding-bottom: var(--space-4); display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin-bottom: 0; font-size: var(--text-base);">Progress Pembayaran</h3>
      <?php if($polisAktif): ?>
        <span class="badge badge-info badge-dot">Polis Aktif</span>
      <?php else: ?>
        <span class="badge badge-neutral badge-dot">Belum Ada Polis</span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <div class="progress-header">
        <div class="progress-info">
          <span class="progress-percentage"><?php echo $percentage; ?>%</span>
          <span class="progress-label">terbayar</span>
        </div>
      </div>
      <div class="progress-bar" style="height: 12px; border-radius: var(--radius-full);">
        <div class="progress-fill <?php echo $isPaid ? 'success' : ''; ?>" style="width: <?php echo $percentage; ?>%;"></div>
      </div>
      <div class="progress-amounts">
        <div class="progress-amount-item">
          <span class="progress-amount-label">Dibayar</span>
          <span class="progress-amount-value">Rp <?php echo number_format($paidAmount, 0, ',', '.'); ?></span>
        </div>
        <div class="progress-amount-item">
          <span class="progress-amount-label">Total</span>
          <span class="progress-amount-value">Rp <?php echo number_format($totalCost, 0, ',', '.'); ?></span>
        </div>
        <div class="progress-amount-item">
          <span class="progress-amount-label">Sisa</span>
          <span class="progress-amount-value" style="color: var(--color-danger);">Rp <?php echo number_format($remaining, 0, ',', '.'); ?></span>
        </div>
      </div>
    </div>
  </section>

  <!-- Recent Transactions -->
  <section class="recent-transactions animate-fade-in-up delay-4" aria-label="Transaksi terbaru">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border-light); padding-bottom: var(--space-4); margin-bottom: var(--space-4);">
      <h3 style="margin-bottom: 0; font-size: var(--text-base);">Transaksi Terbaru</h3>
      <a href="#" class="btn btn-ghost btn-sm" style="text-decoration: none; color: var(--color-primary); font-size: var(--text-sm);">Lihat Semua</a>
    </div>
    <div>
      <?php if(!empty($recentPayments)): ?>
        <?php foreach($recentPayments as $payment): ?>
        <div class="transaction-item">
          <div class="transaction-icon <?php echo $payment->payment_method === 'transfer' ? 'transfer' : 'cash'; ?>">
            <?php echo $payment->payment_method === 'transfer' ? '<i class="fa-solid fa-building-columns"></i>' : '<i class="fa-solid fa-money-bill-wave"></i>'; ?>
          </div>
          <div class="transaction-details">
            <div class="transaction-method">
              <?php echo ucfirst($payment->payment_method); ?><?php echo $payment->bank_name ? ' - ' . strtoupper($payment->bank_name) : ''; ?>
            </div>
            <div class="transaction-date"><?php echo date('d M Y', strtotime($payment->payment_date)); ?></div>
          </div>
          <div class="transaction-amount">
            <div class="transaction-value">Rp <?php echo number_format($payment->amount, 0, ',', '.'); ?></div>
            <div class="transaction-status">
              <?php if($payment->status === 'Verified'): ?>
                <span class="badge badge-success">Terverifikasi</span>
              <?php elseif($payment->status === 'Rejected'): ?>
                <span class="badge badge-danger">Ditolak</span>
              <?php else: ?>
                <span class="badge badge-warning">Pending</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="padding: var(--space-8); text-align: center;">
          <div class="empty-state-icon" style="font-size: 2.5rem; margin-bottom: var(--space-2); color: var(--color-slate);"><i class="fa-solid fa-receipt"></i></div>
          <h3 class="empty-state-title" style="margin-bottom: var(--space-2);">Belum Ada Transaksi</h3>
          <p class="empty-state-text" style="color: var(--color-text-secondary);">Mulai pembayaran pertama Anda sekarang.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php
    include "../layouts/customer/footer.php";
?>