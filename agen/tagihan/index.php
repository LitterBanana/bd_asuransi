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

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');

    $query = "
        SELECT 
            t.no_tagihan, t.periode_bulan, t.jumlah_tagihan, t.jatuh_tempo, t.status_tagihan,
            p.no_polis, pr.nama_produk, pp.nama_lengkap as nama_nasabah,
            (SELECT status_pembayaran FROM pembayaran_premi WHERE no_tagihan = t.no_tagihan ORDER BY id_pembayaran DESC LIMIT 1) as status_pembayaran_terakhir
        FROM tagihan_premi t
        JOIN polis p ON t.no_polis = p.no_polis
        JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
        JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
        WHERE (p.id_agen = ? OR pp.id_agen = ?)
    ";
    $params = [$id_agen, $id_agen];

    if (!empty($search)) {
        $query .= " AND (pp.nama_lengkap LIKE ? OR p.no_polis LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($status)) {
        $query .= " AND t.status_tagihan = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY t.jatuh_tempo DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $tagihan_list = $stmt->fetchAll();

    $success_msg = $_GET['success'] ?? '';
    $error_msg = $_GET['error'] ?? '';
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Pantau Tagihan Klien</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Pantau status pembayaran tagihan premi dari seluruh klien Anda.</p>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success" style="padding: 15px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="alert alert-danger" style="padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <input type="text" name="search" placeholder="Cari nama nasabah / no polis..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; flex-grow: 1; outline: none; min-width: 250px;">
    <select name="status" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
        <option value="">Semua Status</option>
        <option value="Paid" <?php echo $status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
        <option value="Unpaid" <?php echo $status === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
        <option value="Overdue" <?php echo $status === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
    </select>
    <button type="submit" class="btn" style="background: #1e293b; color: white; padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer;">Filter</button>
</form>

<?php if (count($tagihan_list) > 0): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Riwayat Tagihan</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nasabah & Polis</th>
                    <th>Periode & Jatuh Tempo</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tagihan_list as $row): 
                    $badge_class = 'badge-neutral';
                    $status_display = $row['status_tagihan'];
                    if ($row['status_pembayaran_terakhir'] === 'Pending') {
                        $badge_class = 'badge-warning';
                        $status_display = 'Pending Verifikasi';
                    } elseif ($row['status_tagihan'] === 'Paid') {
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
                                <?php echo htmlspecialchars($row['nama_nasabah']); ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: normal;"><?php echo htmlspecialchars($row['no_polis']); ?> &bull; <?php echo htmlspecialchars($row['nama_produk']); ?></div>
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
                            <?php echo htmlspecialchars($status_display); ?>
                        </span>
                    </td>
                    <td style="text-align: center; display: flex; gap: 5px; justify-content: center;">
                        <?php if ($row['status_tagihan'] !== 'Paid' && $row['status_pembayaran_terakhir'] !== 'Pending'): ?>
                        <button onclick="openBayarModal('<?php echo htmlspecialchars($row['no_tagihan']); ?>', <?php echo $row['jumlah_tagihan']; ?>)" class="btn" style="background: #10b981; color: white; padding: 5px 10px; border-radius: 6px; border: none; cursor: pointer; font-size: 11px;" title="Input Pembayaran">
                            <i class="fa-solid fa-money-bill-wave"></i> Bayar
                        </button>
                        <?php endif; ?>
                        <a href="print.php?no_tagihan=<?php echo urlencode($row['no_tagihan']); ?>" class="btn" style="background: #3b82f6; color: white; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 11px;" title="Cetak Invoice">
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
        <div style="font-size: 40px; margin-bottom: 15px; color: #94a3b8;"><i class="fa-solid fa-file-invoice"></i></div>
        <h3 style="margin: 0 0 10px 0; color: #1e293b;">Belum Ada Tagihan</h3>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Klien Anda belum memiliki riwayat tagihan premi.</p>
    </div>
    </div>
<?php endif; ?>

<!-- Modal Bayar -->
<div id="bayarModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1e293b;">Input Pembayaran Nasabah</h3>
            <button onclick="closeBayarModal()" style="background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>
        <form action="bayar_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="no_tagihan" id="modal_no_tagihan">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">Nominal (Otomatis)</label>
                <input type="text" id="modal_nominal_display" readonly style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; color: #64748b; outline: none; box-sizing: border-box;">
                <input type="hidden" name="nominal_bayar" id="modal_nominal">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">Metode Pembayaran</label>
                <select name="metode_bayar" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box;">
                    <option value="Cash">Cash (Titip Agen)</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">Bukti Pembayaran / Tanda Terima</label>
                <input type="file" name="bukti_pembayaran" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box;">
                <small style="color: #64748b; font-size: 12px;">Format: JPG, PNG, PDF (Maks 2MB). Wajib unggah bukti.</small>
            </div>

            <button type="submit" style="width: 100%; padding: 12px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                Kirim Data Pembayaran
            </button>
        </form>
    </div>
</div>

<script>
function openBayarModal(noTagihan, nominal) {
    document.getElementById('modal_no_tagihan').value = noTagihan;
    document.getElementById('modal_nominal').value = nominal;
    document.getElementById('modal_nominal_display').value = 'Rp ' + nominal.toLocaleString('id-ID');
    document.getElementById('bayarModal').style.display = 'flex';
}
function closeBayarModal() {
    document.getElementById('bayarModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . "/../../layouts/agen/footer.php"; ?>
