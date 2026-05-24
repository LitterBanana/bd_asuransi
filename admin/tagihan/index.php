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
        $id_pembayaran = $_POST['id_pembayaran'] ?? '';
        $no_tagihan = $_POST['no_tagihan'] ?? '';

        if (!$id_pembayaran || !$no_tagihan) {
            $_SESSION['toast_error'] = 'Data pembayaran tidak valid.';
            echo "<script>window.location.href='index.php';</script>";
            exit;
        }

        try {
            $conn->beginTransaction();

            if ($action === 'verify') {
                // 1. Update status pembayaran menjadi Verified
                $stmt1 = $conn->prepare("UPDATE pembayaran_premi SET status_pembayaran = 'Verified' WHERE id_pembayaran = ? AND status_pembayaran = 'Pending'");
                $stmt1->execute([$id_pembayaran]);

                // 2. Update status tagihan menjadi Paid
                $stmt2 = $conn->prepare("UPDATE tagihan_premi SET status_tagihan = 'Paid' WHERE no_tagihan = ?");
                $stmt2->execute([$no_tagihan]);

                $conn->commit();
                $_SESSION['toast_success'] = "Pembayaran untuk tagihan $no_tagihan berhasil diverifikasi dan dilunaskan.";
            
            } elseif ($action === 'reject') {
                // Hanya update status pembayaran menjadi Rejected. Tagihan tetap Unpaid.
                $stmt = $conn->prepare("UPDATE pembayaran_premi SET status_pembayaran = 'Rejected' WHERE id_pembayaran = ? AND status_pembayaran = 'Pending'");
                $stmt->execute([$id_pembayaran]);
                
                $conn->commit();
                $_SESSION['toast_success'] = "Bukti pembayaran telah ditolak. Tagihan $no_tagihan tetap berstatus Unpaid.";
            }

        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error verifikasi pembayaran: " . $e->getMessage());
            $_SESSION['toast_error'] = 'Terjadi kesalahan sistem saat memproses verifikasi.';
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
        $whereClause .= " AND pb.status_pembayaran = ?";
        $params[] = $status;
    }

    // Ambil data semua riwayat pembayaran
    $query = "
        SELECT 
            pb.id_pembayaran,
            pb.no_tagihan,
            pb.tanggal_bayar,
            pb.nominal_bayar,
            pb.metode_bayar,
            pb.bank_name,
            pb.referensi_pembayaran,
            pb.bukti_pembayaran,
            pb.status_pembayaran,
            t.periode_bulan,
            p.no_polis,
            pem.nama_lengkap AS nama_pemegang
        FROM pembayaran_premi pb
        JOIN tagihan_premi t ON pb.no_tagihan = t.no_tagihan
        JOIN polis p ON t.no_polis = p.no_polis
        JOIN pemegang_polis pem ON p.id_pemegang = pem.id_pemegang
        $whereClause
        ORDER BY 
            CASE WHEN pb.status_pembayaran = 'Pending' THEN 1 ELSE 2 END ASC, 
            pb.tanggal_bayar DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $pembayaran_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Verifikasi Pembayaran & Tagihan</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Periksa bukti transfer nasabah dan lunaskan tagihan premi bulanan.</p>
    </div>
    
    <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <div style="position: relative;">
            <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input type="text" name="search" placeholder="Cari No Polis / Nama..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
        </div>
        <select name="status" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
            <option value="">Semua Status</option>
            <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Verified" <?php echo $status === 'Verified' ? 'selected' : ''; ?>>Verified</option>
            <option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">Cari</button>
        <?php if ($search || $status): ?>
            <a href="index.php" class="btn-admin btn-admin-ghost" style="padding: 8px 16px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Antrean Verifikasi Pembayaran</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Waktu Submit</th>
                    <th>No. Tagihan</th>
                    <th>Nasabah</th>
                    <th>Nominal</th>
                    <th>Metode & Bank</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pembayaran_list) > 0): ?>
                    <?php foreach ($pembayaran_list as $row): ?>
                    <tr>
                        <td style="color: #64748b; font-size: 13px;">
                            <?php echo date('d M Y', strtotime($row['tanggal_bayar'])); ?><br>
                            <small><?php echo date('H:i', strtotime($row['tanggal_bayar'])); ?></small>
                        </td>
                        <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['no_tagihan']); ?></td>
                        <td style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($row['nama_pemegang']); ?></td>
                        <td style="color: #10b981; font-weight: 600;">Rp <?php echo number_format($row['nominal_bayar'], 0, ',', '.'); ?></td>
                        <td>
                            <div style="font-size: 13px; color: #334155; font-weight: 500;"><?php echo htmlspecialchars($row['metode_bayar']); ?></div>
                            <?php if ($row['bank_name']): ?>
                                <div style="font-size: 11px; color: #94a3b8;"><?php echo htmlspecialchars($row['bank_name']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $status = $row['status_pembayaran'];
                                $badge = 'badge-neutral';
                                if ($status == 'Pending') $badge = 'badge-warning';
                                else if ($status == 'Verified') $badge = 'badge-success';
                                else if ($status == 'Rejected') $badge = 'badge-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($status === 'Pending'): ?>
                                <button onclick="openVerificationModal(
                                        '<?php echo htmlspecialchars($row['id_pembayaran']); ?>', 
                                        '<?php echo htmlspecialchars($row['no_tagihan']); ?>', 
                                        '<?php echo htmlspecialchars($row['nama_pemegang']); ?>', 
                                        '<?php echo number_format($row['nominal_bayar'], 0, ',', '.'); ?>', 
                                        '<?php echo htmlspecialchars($row['bank_name']); ?>', 
                                        '<?php echo htmlspecialchars($row['referensi_pembayaran']); ?>',
                                        '<?php echo htmlspecialchars($row['bukti_pembayaran']); ?>'
                                    )" 
                                    class="btn-admin btn-admin-sm btn-admin-primary" title="Cek & Verifikasi">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cek & Verifikasi
                                </button>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #94a3b8;">
                                    <?php if ($status === 'Verified') echo '<i class="fa-solid fa-check-double" style="color:#10b981;"></i> Lunas'; else echo 'Ditolak'; ?>
                                </span>
                            <?php endif; ?>
                            <a href="print.php?no_tagihan=<?php echo urlencode($row['no_tagihan']); ?>" target="_blank" class="btn-admin btn-admin-sm btn-admin-ghost" title="Cetak Tagihan" style="margin-left: 5px;">
                                <i class="fa-solid fa-print"></i> Cetak
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada antrean pembayaran masuk.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL VERIFIKASI PEMBAYARAN -->
<div id="modalVerify" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 500px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;">Verifikasi Bukti Transfer</h3>
            <button onclick="closeModal('modalVerify')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px; overflow-y: auto;">
            
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">No. Tagihan</span>
                        <strong id="vNoTagihan" style="color: #3b82f6; font-size: 14px;">-</strong>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Nasabah</span>
                        <strong id="vNasabah" style="color: #1e293b; font-size: 14px;">-</strong>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Nominal (Rp)</span>
                        <strong id="vNominal" style="color: #10b981; font-size: 16px;">-</strong>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Bank Pengirim</span>
                        <strong id="vBank" style="color: #475569; font-size: 14px;">-</strong>
                    </div>
                </div>
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
                    <span style="font-size: 11px; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.5px;">No Referensi Transfer</span>
                    <strong id="vRef" style="color: #475569; font-size: 14px;">-</strong>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 10px;">Lampiran Bukti Bayar:</label>
                <div id="vBuktiContainer" style="background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 20px; text-align: center;">
                    <!-- Gambar bukti masuk sini -->
                </div>
            </div>

            <form method="POST" action="" id="formVerify">
                <input type="hidden" name="action" id="vAction" value="">
                <input type="hidden" name="id_pembayaran" id="vIdPembayaran" value="">
                <input type="hidden" name="no_tagihan" id="vInputNoTagihan" value="">
                
                <div style="display: flex; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    <button type="button" onclick="submitVerifikasi('reject')" class="btn-admin btn-admin-lg btn-admin-danger" style="flex: 1;">
                        <i class="fa-solid fa-xmark"></i> Tolak Bukti Bayar
                    </button>
                    <button type="button" onclick="submitVerifikasi('verify')" class="btn-admin btn-admin-lg btn-admin-success" style="flex: 1;">
                        <i class="fa-solid fa-check-double"></i> Verifikasi & Lunas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openVerificationModal(idPem, noTagihan, nasabah, nominal, bank, referensi, bukti) {
        document.getElementById('vIdPembayaran').value = idPem;
        document.getElementById('vInputNoTagihan').value = noTagihan;
        
        document.getElementById('vNoTagihan').textContent = noTagihan;
        document.getElementById('vNasabah').textContent = nasabah;
        document.getElementById('vNominal').textContent = nominal;
        document.getElementById('vBank').textContent = bank || '-';
        document.getElementById('vRef').textContent = referensi || 'Tidak ada referensi';

        const buktiContainer = document.getElementById('vBuktiContainer');
        if (bukti) {
            const ext = bukti.split('.').pop().toLowerCase();
            const filePath = `../../uploads/payments/${bukti}`;
            if (ext === 'pdf') {
                buktiContainer.innerHTML = `<div style="padding: 20px;">
                    <i class="fa-solid fa-file-pdf" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i><br>
                    <a href="${filePath}" target="_blank" class="btn btn-sm" style="background: #3b82f6; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px;">Lihat PDF</a>
                </div>`;
            } else {
                buktiContainer.innerHTML = `<a href="${filePath}" target="_blank"><img src="${filePath}" alt="Bukti Pembayaran" style="max-width: 100%; max-height: 300px; border-radius: 4px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); cursor: pointer;" title="Klik untuk memperbesar"></a>`;
            }
        } else {
            buktiContainer.innerHTML = `<div style="color: #94a3b8;"><i class="fa-solid fa-image" style="font-size: 32px; margin-bottom: 10px;"></i><br>Tidak ada lampiran foto/file.</div>`;
        }

        document.getElementById('modalVerify').style.display = 'flex';
    }

    function submitVerifikasi(actionType) {
        document.getElementById('vAction').value = actionType;
        
        let confirmMsg = actionType === 'verify' 
            ? "Anda yakin ingin MENYETUJUI bukti ini? Tagihan otomatis akan dilunaskan." 
            : "Anda yakin ingin MENOLAK bukti ini? Tagihan akan tetap berstatus Unpaid.";
            
        if(confirm(confirmMsg)) {
            document.getElementById('formVerify').submit();
        }
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
