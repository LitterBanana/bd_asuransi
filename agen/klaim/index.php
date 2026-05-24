<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/agen/header.php";

    $username = $_SESSION['username'] ?? '';
    
    // Get id_agen
    $stmtUser = $conn->prepare("SELECT id_agen FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $user = $stmtUser->fetch();
    
    if (!$user) {
        echo "Data agen tidak ditemukan.";
        exit;
    }
    $id_agen = $user['id_agen'];

    $stmt = $conn->prepare("
        SELECT 
            k.no_klaim, k.tanggal_masuk, k.jenis_perawatan, k.status_klaim, k.total_tagihan_faskes,
            p.no_polis, f.nama_faskes, kp.nama_penyakit,
            COALESCE(t.nama_lengkap, pp.nama_lengkap) as nama_pasien, pp.nama_lengkap as nama_pemegang
        FROM klaim_medis k
        JOIN polis p ON k.no_polis = p.no_polis
        JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
        JOIN faskes f ON k.id_faskes = f.id_faskes
        JOIN kategori_penyakit kp ON k.kode_icd = kp.kode_icd
        LEFT JOIN tanggungan_polis t ON k.id_tanggungan = t.id_tanggungan
        WHERE p.id_agen = ?
        ORDER BY k.tanggal_masuk DESC
    ");
    $stmt->execute([$id_agen]);
    $klaim_list = $stmt->fetchAll();
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Progress Klaim Klien</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Pantau status pengajuan klaim medis dari seluruh klien Anda.</p>
</div>

<?php if (count($klaim_list) > 0): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Klaim</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Pasien & Klien</th>
                    <th>Detail Perawatan</th>
                    <th>Faskes & Tagihan</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($klaim_list as $row): 
                    $badge_class = 'badge-neutral';
                    if ($row['status_klaim'] === 'Approved') {
                        $badge_class = 'badge-success';
                    } elseif ($row['status_klaim'] === 'Pending') {
                        $badge_class = 'badge-warning';
                    } elseif ($row['status_klaim'] === 'Investigasi') {
                        $badge_class = 'badge-info';
                    } elseif ($row['status_klaim'] === 'Rejected') {
                        $badge_class = 'badge-danger';
                    }
                ?>
                <tr>
                    <td style="font-weight: 600; color: #1e293b;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 35px; height: 35px; border-radius: 8px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fa-solid fa-notes-medical"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($row['nama_pasien']); ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: normal;">Klien: <?php echo htmlspecialchars($row['nama_pemegang']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="color: #1e293b; font-weight: 500;">Polis: <?php echo htmlspecialchars($row['no_polis']); ?> &bull; <?php echo htmlspecialchars($row['jenis_perawatan']); ?></div>
                        <div style="font-size: 12px; color: #64748b;">Tgl Masuk: <?php echo date('d M Y', strtotime($row['tanggal_masuk'])); ?> &bull; <?php echo htmlspecialchars($row['nama_penyakit']); ?></div>
                    </td>
                    <td>
                        <div style="color: #1e293b; font-weight: 500;"><?php echo htmlspecialchars($row['nama_faskes']); ?></div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 600;">Rp <?php echo number_format($row['total_tagihan_faskes'], 0, ',', '.'); ?></div>
                    </td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>" style="font-size: 11px;">
                            <?php echo htmlspecialchars($row['status_klaim']); ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <a href="print.php?no_klaim=<?php echo urlencode($row['no_klaim']); ?>" class="btn" style="background: #3b82f6; color: white; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 11px;" title="Cetak Laporan Klaim">
                            <i class="fa-solid fa-print"></i> Cetak
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="empty-state" style="padding: 40px 20px; text-align: center; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 40px; margin-bottom: 15px; color: #94a3b8;"><i class="fa-solid fa-file-medical"></i></div>
        <h3 style="margin: 0 0 10px 0; color: #1e293b;">Belum Ada Riwayat Klaim</h3>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Klien Anda belum pernah mengajukan klaim medis.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . "/../../layouts/agen/footer.php"; ?>
