<?php
    require_once "../db.php";
    require_once "../layouts/admin/header.php";

    // --- 1. Statistik ---
    // Total Premi Dibayar (Bulan ini)
    $stmt_premi = $conn->prepare("SELECT SUM(nominal_bayar) as total FROM pembayaran_premi WHERE status_pembayaran = 'Verified' AND MONTH(tanggal_bayar) = MONTH(CURRENT_DATE())");
    $stmt_premi->execute();
    $total_premi = $stmt_premi->fetch()['total'] ?? 0;

    // Total Polis Aktif
    $stmt_polis_aktif = $conn->prepare("SELECT COUNT(*) as total FROM polis WHERE status_polis = 'Inforce'");
    $stmt_polis_aktif->execute();
    $total_polis_aktif = $stmt_polis_aktif->fetch()['total'] ?? 0;

    // Total Klaim Pending
    $stmt_klaim_pending = $conn->prepare("SELECT COUNT(*) as total FROM klaim_medis WHERE status_klaim = 'Pending'");
    $stmt_klaim_pending->execute();
    $total_klaim_pending = $stmt_klaim_pending->fetch()['total'] ?? 0;

    // Total Nasabah (Pemegang Polis)
    $stmt_nasabah = $conn->prepare("SELECT COUNT(*) as total FROM pemegang_polis");
    $stmt_nasabah->execute();
    $total_nasabah = $stmt_nasabah->fetch()['total'] ?? 0;

    // --- 2. Pembayaran Terbaru (Limit 5) ---
    $stmt_recent_payments = $conn->prepare("
        SELECT pp.id_pembayaran, pp.tanggal_bayar, pp.nominal_bayar, pp.status_pembayaran, pp.metode_bayar, p.no_polis 
        FROM pembayaran_premi pp
        JOIN tagihan_premi tp ON pp.no_tagihan = tp.no_tagihan
        JOIN polis p ON tp.no_polis = p.no_polis
        ORDER BY pp.tanggal_bayar DESC LIMIT 5
    ");
    $stmt_recent_payments->execute();
    $recent_payments = $stmt_recent_payments->fetchAll();

    // --- 3. Request Polis Terbaru (Limit 5) ---
    $stmt_requests = $conn->prepare("
        SELECT 'Polis Baru' as jenis_request, no_polis, tanggal_terbit as tanggal_request, status_polis as status 
        FROM polis WHERE status_polis = 'Pending Approval'
        UNION ALL
        SELECT 'Batal Polis' as jenis_request, no_polis, tanggal_terbit as tanggal_request, status_polis as status 
        FROM polis WHERE status_polis = 'Pending Cancellation'
        UNION ALL
        SELECT 'Tambah Tanggungan' as jenis_request, no_polis, CURRENT_DATE() as tanggal_request, status_tanggungan as status 
        FROM tanggungan_polis WHERE status_tanggungan = 'Pending'
        UNION ALL
        SELECT 'Hapus Tanggungan' as jenis_request, no_polis, CURRENT_DATE() as tanggal_request, status_tanggungan as status 
        FROM tanggungan_polis WHERE status_tanggungan = 'Pending Deletion'
        ORDER BY tanggal_request DESC LIMIT 5
    ");
    $stmt_requests->execute();
    $recent_requests = $stmt_requests->fetchAll();

    // --- 4. User Akun Terbaru (Limit 10) ---
    $stmt_users = $conn->prepare("
        SELECT u.username, u.role, u.created_at, u.status_akun,
               COALESCE(a.nama_agen, p.nama_lengkap) as nama
        FROM users u
        LEFT JOIN agen a ON u.id_agen = a.id_agen
        LEFT JOIN pemegang_polis p ON u.id_pemegang = p.id_pemegang
        ORDER BY u.created_at DESC LIMIT 10
    ");
    $stmt_users->execute();
    $recent_users = $stmt_users->fetchAll();
    // --- 5. Data for Income Chart (Dynamic Filter) ---
    $filter_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $filter_month = isset($_GET['month']) ? $_GET['month'] : 'all';

    if ($filter_month === 'all') {
        $stmt_chart = $conn->prepare("
            SELECT DATE_FORMAT(tanggal_bayar, '%b') as label, SUM(nominal_bayar) as total
            FROM pembayaran_premi
            WHERE status_pembayaran = 'Verified' AND YEAR(tanggal_bayar) = ?
            GROUP BY MONTH(tanggal_bayar), label
            ORDER BY MONTH(tanggal_bayar)
        ");
        $stmt_chart->execute([$filter_year]);
        $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

        $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $income_values = array_fill(0, 12, 0);

        foreach($chart_data as $row) {
            $idx = array_search($row['label'], $chart_labels);
            if($idx !== false) {
                $income_values[$idx] = (float)$row['total'];
            }
        }
    } else {
        $filter_month_int = (int)$filter_month;
        $stmt_chart = $conn->prepare("
            SELECT DAY(tanggal_bayar) as label, SUM(nominal_bayar) as total
            FROM pembayaran_premi
            WHERE status_pembayaran = 'Verified' AND YEAR(tanggal_bayar) = ? AND MONTH(tanggal_bayar) = ?
            GROUP BY DAY(tanggal_bayar)
            ORDER BY DAY(tanggal_bayar)
        ");
        $stmt_chart->execute([$filter_year, $filter_month_int]);
        $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $filter_month_int, $filter_year);
        $chart_labels = range(1, $days_in_month);
        $income_values = array_fill(0, $days_in_month, 0);

        foreach($chart_data as $row) {
            $day = (int)$row['label'];
            if($day >= 1 && $day <= $days_in_month) {
                $income_values[$day - 1] = (float)$row['total'];
            }
        }
    }
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Dashboard Overview</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Ringkasan aktivitas asuransi hari ini.</p>
</div>

<!-- Stats Grid -->
<div class="admin-stats-grid">
    <div class="admin-stat-card blue">
        <div class="admin-stat-icon blue"><i class="fa-solid fa-rupiah-sign"></i></div>
        <div class="admin-stat-content">
            <h4>Premi Dibayar (Bulan Ini)</h4>
            <h2>Rp <?php echo number_format($total_premi, 0, ',', '.'); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card green">
        <div class="admin-stat-icon green"><i class="fa-solid fa-file-shield"></i></div>
        <div class="admin-stat-content">
            <h4>Total Polis Aktif</h4>
            <h2><?php echo number_format($total_polis_aktif); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card orange">
        <div class="admin-stat-icon orange"><i class="fa-solid fa-notes-medical"></i></div>
        <div class="admin-stat-content">
            <h4>Klaim Pending</h4>
            <h2><?php echo number_format($total_klaim_pending); ?></h2>
        </div>
    </div>
    <div class="admin-stat-card purple">
        <div class="admin-stat-icon purple"><i class="fa-solid fa-users"></i></div>
        <div class="admin-stat-content">
            <h4>Total Nasabah</h4>
            <h2><?php echo number_format($total_nasabah); ?></h2>
        </div>
    </div>
</div>

<!-- Income Chart -->
<div class="admin-card" style="margin-bottom: 25px;">
    <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 16px;">Grafik Pendapatan Premi</h3>
        <form method="GET" action="index.php" style="display: flex; gap: 10px; align-items: center;">
            <select name="month" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--color-border-light); font-size: 13px; color: #475569; outline: none; background: #fff;" onchange="this.form.submit()">
                <option value="all" <?php echo $filter_month == 'all' ? 'selected' : ''; ?>>Semua Bulan</option>
                <?php
                    $month_names = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    for ($i = 1; $i <= 12; $i++) {
                        $sel = ($filter_month == (string)$i) ? 'selected' : '';
                        echo "<option value=\"$i\" $sel>" . $month_names[$i-1] . "</option>";
                    }
                ?>
            </select>
            <select name="year" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--color-border-light); font-size: 13px; color: #475569; outline: none; background: #fff;" onchange="this.form.submit()">
                <?php 
                    $current_y = (int)date('Y');
                    for($y = $current_y - 3; $y <= $current_y; $y++) {
                        $sel = ($filter_year == $y) ? 'selected' : '';
                        echo "<option value=\"$y\" $sel>$y</option>";
                    }
                ?>
            </select>
        </form>
    </div>
    <div class="admin-card-body" style="padding: 20px;">
        <canvas id="incomeChart" height="80"></canvas>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
    <!-- Pembayaran Terbaru -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Pembayaran Terbaru</h3>
            <a href="tagihan/index.php" class="btn-admin btn-admin-sm btn-admin-ghost-primary">Lihat Semua</a>
        </div>
        <div class="admin-card-body" style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No Polis</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($recent_payments) > 0): ?>
                        <?php foreach($recent_payments as $pay): ?>
                        <tr>
                            <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($pay['no_polis']); ?></td>
                            <td>Rp <?php echo number_format($pay['nominal_bayar'], 0, ',', '.'); ?></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($pay['metode_bayar']); ?></td>
                            <td>
                                <?php if($pay['status_pembayaran'] == 'Verified'): ?>
                                    <span class="badge badge-success" style="font-size: 11px;">Verified</span>
                                <?php elseif($pay['status_pembayaran'] == 'Pending'): ?>
                                    <span class="badge badge-warning" style="font-size: 11px;">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="font-size: 11px;">Rejected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada data pembayaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Request Polis Terbaru -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Request Operasional Terbaru</h3>
            <a href="polis/index.php" class="btn-admin btn-admin-sm btn-admin-ghost-primary">Verifikasi</a>
        </div>
        <div class="admin-card-body" style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Jenis Request</th>
                        <th>No Polis</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($recent_requests) > 0): ?>
                        <?php foreach($recent_requests as $req): ?>
                        <tr>
                            <td>
                                <?php
                                    $badge_class = 'badge-info';
                                    if(strpos($req['jenis_request'], 'Batal') !== false || strpos($req['jenis_request'], 'Hapus') !== false) {
                                        $badge_class = 'badge-danger';
                                    } elseif(strpos($req['jenis_request'], 'Tanggungan') !== false) {
                                        $badge_class = 'badge-primary';
                                    }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>" style="font-size: 11px;"><?php echo htmlspecialchars($req['jenis_request']); ?></span>
                            </td>
                            <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($req['no_polis']); ?></td>
                            <td style="color: #64748b;"><?php echo date('d M Y', strtotime($req['tanggal_request'])); ?></td>
                            <td><span style="font-size: 12px; font-weight: 600; color: #f59e0b;"><i class="fa-solid fa-clock"></i> Pending</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">Tidak ada request baru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Akun Terbaru -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Pendaftaran Akun Terbaru</h3>
        <a href="user/index.php" class="btn-admin btn-admin-sm btn-admin-ghost-primary">Manajemen User</a>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Pengguna</th>
                    <th>Role</th>
                    <th>Tgl Mendaftar</th>
                    <th>Status Akun</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($recent_users) > 0): ?>
                    <?php foreach($recent_users as $user): ?>
                    <tr>
                        <td style="font-weight: 600; color: #1e293b;"><i class="fa-solid fa-circle-user" style="color: #cbd5e1; margin-right: 8px;"></i><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['nama'] ?? '-'); ?></td>
                        <td>
                            <?php if($user['role'] == 'Admin'): ?>
                                <span class="badge badge-neutral" style="background: #1e293b; color: white;">Admin</span>
                            <?php elseif($user['role'] == 'Agen'): ?>
                                <span class="badge badge-info">Agen</span>
                            <?php else: ?>
                                <span class="badge badge-primary">Customer</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #64748b;"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if($user['status_akun'] == 'Aktif'): ?>
                                <span class="badge badge-success badge-dot">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger badge-dot">Diblokir</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada pengguna.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('incomeChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?php echo json_encode($income_values); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000) + ' Jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000) + ' Rb';
                            }
                            return 'Rp ' + value;
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once "../layouts/admin/footer.php"; ?>
