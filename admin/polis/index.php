<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/admin/header.php";

    // Pastikan hanya admin yang bisa akses
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        echo "<script>alert('Akses Ditolak!'); window.location.href='../../index.php';</script>";
        exit;
    }

    /** @var PDO $conn */

    // ==========================================
    // BACKEND LOGIC: POST HANDLER
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        $no_polis = $_POST['no_polis'] ?? '';

        if (!$no_polis) {
            $_SESSION['toast_error'] = 'Nomor Polis tidak ditemukan.';
            echo "<script>window.location.href='index.php';</script>";
            exit;
        }

        try {
            if ($action === 'approve_polis') {
                $stmt = $conn->prepare("UPDATE polis SET status_polis = 'Inforce' WHERE no_polis = ? AND status_polis = 'Pending Approval'");
                $stmt->execute([$no_polis]);
                if ($stmt->rowCount() > 0) $_SESSION['toast_success'] = "Polis $no_polis berhasil disetujui (Inforce).";
                else $_SESSION['toast_error'] = "Gagal menyetujui. Status polis mungkin sudah berubah.";
            
            } elseif ($action === 'reject_polis') {
                $stmt = $conn->prepare("UPDATE polis SET status_polis = 'Rejected' WHERE no_polis = ? AND status_polis = 'Pending Approval'");
                $stmt->execute([$no_polis]);
                if ($stmt->rowCount() > 0) $_SESSION['toast_success'] = "Permohonan Polis $no_polis telah ditolak.";
                else $_SESSION['toast_error'] = "Gagal menolak. Status polis mungkin sudah berubah.";
            
            } elseif ($action === 'mark_lapse') {
                $stmt = $conn->prepare("UPDATE polis SET status_polis = 'Lapse' WHERE no_polis = ? AND status_polis = 'Inforce'");
                $stmt->execute([$no_polis]);
                if ($stmt->rowCount() > 0) $_SESSION['toast_success'] = "Polis $no_polis ditandai sebagai Lapse (Gagal Bayar).";
                else $_SESSION['toast_error'] = "Gagal mengubah status menjadi Lapse.";
            
            } elseif ($action === 'approve_surrender') {
                $stmt = $conn->prepare("UPDATE polis SET status_polis = 'Surrender' WHERE no_polis = ? AND status_polis = 'Pending Cancellation'");
                $stmt->execute([$no_polis]);
                if ($stmt->rowCount() > 0) $_SESSION['toast_success'] = "Permintaan pembatalan disetujui. Polis $no_polis ditutup (Surrender).";
                else $_SESSION['toast_error'] = "Gagal menyetujui pembatalan.";
            
            } elseif ($action === 'reject_surrender') {
                $stmt = $conn->prepare("UPDATE polis SET status_polis = 'Inforce' WHERE no_polis = ? AND status_polis = 'Pending Cancellation'");
                $stmt->execute([$no_polis]);
                if ($stmt->rowCount() > 0) $_SESSION['toast_success'] = "Permintaan pembatalan ditolak. Polis $no_polis dikembalikan ke Inforce.";
                else $_SESSION['toast_error'] = "Gagal menolak pembatalan.";
            }

        } catch (PDOException $e) {
            error_log("Error ubah status polis: " . $e->getMessage());
            $_SESSION['toast_error'] = 'Terjadi kesalahan sistem saat memproses permintaan.';
        }
        
        echo "<script>window.location.href='index.php';</script>";
        exit;
    }

    // Ambil data filter
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $whereClause .= " AND (p.no_polis LIKE ? OR pem.nama_lengkap LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status !== '') {
        $whereClause .= " AND p.status_polis = ?";
        $params[] = $status;
    }

    // Ambil data semua polis
    $query = "
        SELECT 
            p.no_polis, 
            p.tanggal_terbit, 
            p.tanggal_jatuh_tempo, 
            p.status_polis,
            pr.nama_produk,
            pr.premi_dasar,
            pem.nama_lengkap AS nama_pemegang
        FROM polis p
        JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
        JOIN pemegang_polis pem ON p.id_pemegang = pem.id_pemegang
        $whereClause
        ORDER BY 
            CASE WHEN p.status_polis = 'Pending Approval' THEN 1
                 WHEN p.status_polis = 'Pending Cancellation' THEN 2
                 WHEN p.status_polis = 'Inforce' THEN 3
                 ELSE 4 END ASC, 
            p.tanggal_terbit DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $polis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Pengawasan Polis Nasabah</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Tinjau, setujui, atau tolak permohonan polis dan pembatalan.</p>
    </div>
    
    <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <div style="position: relative;">
            <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input type="text" name="search" placeholder="Cari No Polis / Nama..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
        </div>
        <select name="status" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
            <option value="">Semua Status</option>
            <option value="Pending Approval" <?php echo $status === 'Pending Approval' ? 'selected' : ''; ?>>Pending Approval</option>
            <option value="Inforce" <?php echo $status === 'Inforce' ? 'selected' : ''; ?>>Inforce</option>
            <option value="Lapse" <?php echo $status === 'Lapse' ? 'selected' : ''; ?>>Lapse</option>
            <option value="Pending Cancellation" <?php echo $status === 'Pending Cancellation' ? 'selected' : ''; ?>>Pending Cancellation</option>
            <option value="Surrender" <?php echo $status === 'Surrender' ? 'selected' : ''; ?>>Surrender</option>
        </select>
        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">Cari</button>
        <?php if ($search || $status): ?>
            <a href="index.php" class="btn-admin btn-admin-ghost" style="padding: 8px 16px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Polis Terdaftar</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No. Polis</th>
                    <th>Pemegang Polis</th>
                    <th>Produk (Plan)</th>
                    <th>Tgl Jatuh Tempo</th>
                    <th>Premi / Bln</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($polis_list) > 0): ?>
                    <?php foreach ($polis_list as $row): ?>
                    <tr>
                        <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['no_polis']); ?></td>
                        <td style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($row['nama_pemegang']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                        <td style="color: #64748b;"><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                        <td style="color: #f59e0b; font-weight: 600;">Rp <?php echo number_format($row['premi_dasar'], 0, ',', '.'); ?></td>
                        <td>
                            <?php 
                                $status = $row['status_polis'];
                                $badge = 'badge-neutral';
                                if ($status == 'Pending Approval') $badge = 'badge-warning';
                                else if ($status == 'Pending Cancellation') $badge = 'badge-warning';
                                else if ($status == 'Inforce') $badge = 'badge-success';
                                else if ($status == 'Lapse') $badge = 'badge-danger';
                                else if ($status == 'Surrender' || $status == 'Rejected') $badge = 'badge-neutral';
                            ?>
                            <span class="badge <?php echo $badge; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($status === 'Pending Approval'): ?>
                                <button onclick="openApprovalModal('<?php echo htmlspecialchars($row['no_polis']); ?>', 'approve_polis', 'Setujui Polis Baru', 'Apakah Anda yakin ingin menyetujui dan menerbitkan polis ini?')" 
                                    class="btn-admin btn-admin-sm btn-admin-ghost-success" title="Setujui Polis">
                                    <i class="fa-solid fa-check"></i> Setujui
                                </button>
                                <button onclick="openApprovalModal('<?php echo htmlspecialchars($row['no_polis']); ?>', 'reject_polis', 'Tolak Polis', 'Apakah Anda yakin ingin menolak permohonan polis ini?')" 
                                    class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Tolak Polis">
                                    <i class="fa-solid fa-xmark"></i> Tolak
                                </button>
                            <?php elseif ($status === 'Inforce'): ?>
                                <button onclick="openApprovalModal('<?php echo htmlspecialchars($row['no_polis']); ?>', 'mark_lapse', 'Tandai Lapse', 'Tandai polis ini sebagai Lapse (menunggak / gagal bayar)? Hak klaim nasabah akan terhenti sementara.')" 
                                    class="btn-admin btn-admin-sm btn-admin-ghost-warning" title="Tandai Lapse">
                                    <i class="fa-solid fa-exclamation-triangle"></i> Lapse
                                </button>
                            <?php elseif ($status === 'Pending Cancellation'): ?>
                                <button onclick="openApprovalModal('<?php echo htmlspecialchars($row['no_polis']); ?>', 'approve_surrender', 'Setujui Pembatalan (Surrender)', 'Setujui permintaan pembatalan dari nasabah? Polis ini akan ditutup selamanya (Surrender).')" 
                                    class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Setujui Surrender">
                                    <i class="fa-solid fa-ban"></i> Setujui Surrender
                                </button>
                                <button onclick="openApprovalModal('<?php echo htmlspecialchars($row['no_polis']); ?>', 'reject_surrender', 'Tolak Pembatalan', 'Tolak permintaan pembatalan dan kembalikan polis menjadi Inforce?')" 
                                    class="btn-admin btn-admin-sm btn-admin-ghost-primary" title="Tolak Pembatalan">
                                    <i class="fa-solid fa-undo"></i> Tolak Pembatalan
                                </button>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #94a3b8; display: inline-block; margin-right: 5px;">Tidak ada aksi</span>
                            <?php endif; ?>
                            <a href="print.php?no_polis=<?php echo urlencode($row['no_polis']); ?>" class="btn-admin btn-admin-sm btn-admin-ghost" title="Cetak Polis">
                                <i class="fa-solid fa-print"></i> Cetak
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada data polis.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL KONFIRMASI AKSI -->
<div id="modalAksiPolis" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 400px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 id="modalTitle" style="color: #1e293b;">Konfirmasi Aksi</h3>
            <button onclick="closeModal('modalAksiPolis')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <p id="modalMessage" style="color: #475569; line-height: 1.5; margin-bottom: 20px;">Pesan konfirmasi...</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="modalAction" value="">
                <input type="hidden" name="no_polis" id="modalNoPolis" value="">
                
                <div style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; margin-bottom: 20px; border: 1px dashed #cbd5e1;">
                    <span style="font-size: 12px; color: #64748b; display: block;">Nomor Polis target:</span>
                    <strong id="displayNoPolis" style="color: #3b82f6; font-size: 15px;"></strong>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalAksiPolis')" class="btn-admin btn-admin-ghost">Batal</button>
                    <button type="submit" id="btnSubmitAksi" class="btn-admin btn-admin-primary">Ya, Lanjutkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openApprovalModal(noPolis, actionType, title, message) {
        document.getElementById('modalNoPolis').value = noPolis;
        document.getElementById('displayNoPolis').textContent = noPolis;
        document.getElementById('modalAction').value = actionType;
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = message;

        const btnSubmit = document.getElementById('btnSubmitAksi');
        // Sesuaikan warna tombol dengan sentimen aksi
        if (actionType.includes('reject') || actionType.includes('lapse') || actionType.includes('surrender')) {
            btnSubmit.style.background = '#ef4444'; // Merah
        } else {
            btnSubmit.style.background = '#10b981'; // Hijau
        }

        document.getElementById('modalAksiPolis').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
