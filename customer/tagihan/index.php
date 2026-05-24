<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";

    /** @var PDO $conn */
    $id_user = $_SESSION['id_user'];
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Riwayat Tagihan Premi</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Daftar tagihan premi asuransi bulanan Anda beserta status pembayarannya.</p>
</div>

<?php
    $stmt = $conn->prepare("
        SELECT 
            t.no_tagihan, t.periode_bulan, t.jumlah_tagihan, t.jatuh_tempo, t.status_tagihan,
            p.no_polis, pr.nama_produk
        FROM tagihan_premi t
        JOIN polis p ON t.no_polis = p.no_polis
        JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
        JOIN users u ON p.id_pemegang = u.id_pemegang
        WHERE u.id_user = ?
        ORDER BY t.jatuh_tempo DESC
    ");
    $stmt->execute([$id_user]);
    $tagihan_list = $stmt->fetchAll();

    if (count($tagihan_list) > 0) {
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Tagihan</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Informasi Polis</th>
                    <th>Periode & Jatuh Tempo</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tagihan_list as $row): 
                    $badge_class = 'badge-neutral';
                    if ($row['status_tagihan'] === 'Paid') {
                        $badge_class = 'badge-success';
                    } elseif ($row['status_tagihan'] === 'Unpaid') {
                        $badge_class = 'badge-warning';
                    } elseif ($row['status_tagihan'] === 'Overdue') {
                        $badge_class = 'badge-danger';
                    }
                ?>
                <tr>
                    <td style="font-weight: 600; color: #1e293b;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 35px; height: 35px; border-radius: 8px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($row['no_polis']); ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: normal;"><?php echo htmlspecialchars($row['nama_produk']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="color: #1e293b; font-weight: 500;"><?php echo htmlspecialchars($row['periode_bulan']); ?></div>
                        <div style="font-size: 12px; color: #64748b;">Jatuh Tempo: <?php echo date('d M Y', strtotime($row['jatuh_tempo'])); ?></div>
                    </td>
                    <td style="font-weight: 600;">Rp <?php echo number_format($row['jumlah_tagihan'], 0, ',', '.'); ?></td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>" style="font-size: 11px;">
                            <?php echo htmlspecialchars($row['status_tagihan']); ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($row['status_tagihan'] === 'Unpaid' || $row['status_tagihan'] === 'Overdue'): ?>
                            <a href="bayar.php?no_tagihan=<?php echo urlencode($row['no_tagihan']); ?>" class="btn" style="background: #3b82f6; color: white; padding: 6px 15px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block;">
                                <i class="fa-solid fa-credit-card" style="margin-right: 5px;"></i> Bayar
                            </a>
                        <?php endif; ?>
                        <a href="print.php?no_tagihan=<?php echo urlencode($row['no_tagihan']); ?>" class="btn" style="background: white; color: #475569; padding: 6px 15px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; border: 1px solid #cbd5e1; margin-left: 5px;">
                            <i class="fa-solid fa-print" style="margin-right: 5px;"></i> Cetak
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
    } else {
?>
    <div class="empty-state" style="padding: var(--space-8); text-align: center;">
        <div class="empty-state-icon" style="font-size: 2.5rem; margin-bottom: var(--space-2); color: var(--color-slate);"><i class="fa-solid fa-file-invoice"></i></div>
        <h3 class="empty-state-title" style="margin-bottom: var(--space-2);">Belum Ada Tagihan</h3>
        <p class="empty-state-text" style="color: var(--color-text-secondary);">Anda belum memiliki riwayat tagihan premi untuk saat ini.</p>
    </div>
<?php
    }
?>

<?php require_once __DIR__ . "/../../layouts/customer/footer.php"; ?>
