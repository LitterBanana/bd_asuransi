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
        if ($_POST['action'] === 'update_status') {
            $no_klaim = $_POST['no_klaim'] ?? '';
            $status_klaim = $_POST['status_klaim'] ?? 'Pending';
            $catatan_analis = trim($_POST['catatan_analis'] ?? '');
            
            // Nominal pencairan hanya disave jika status Approved
            $total_dibayarkan = 0;
            if ($status_klaim === 'Approved') {
                $total_dibayarkan = floatval($_POST['total_dibayarkan_asuransi'] ?? 0);
            }

            try {
                $stmt = $conn->prepare("UPDATE klaim_medis SET status_klaim = ?, total_dibayarkan_asuransi = ?, catatan_analis = ? WHERE no_klaim = ?");
                $stmt->execute([$status_klaim, $total_dibayarkan, $catatan_analis, $no_klaim]);
                $_SESSION['toast_success'] = "Evaluasi klaim $no_klaim berhasil disimpan.";
            } catch (PDOException $e) {
                error_log("Error update klaim: " . $e->getMessage());
                $_SESSION['toast_error'] = 'Terjadi kesalahan sistem saat menyimpan evaluasi.';
            }
            
            echo "<script>window.location.href='index.php';</script>";
            exit;
        }
    }

    // Ambil data filter
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $whereClause .= " AND (km.no_klaim LIKE ? OR p.no_polis LIKE ? OR pem.nama_lengkap LIKE ? OR tg.nama_lengkap LIKE ? OR f.nama_faskes LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status !== '') {
        $whereClause .= " AND km.status_klaim = ?";
        $params[] = $status;
    }

    // Ambil data semua klaim
    $query = "
        SELECT 
            km.no_klaim,
            km.no_polis,
            km.tanggal_masuk,
            km.jenis_perawatan,
            km.status_klaim,
            km.total_tagihan_faskes,
            km.total_dibayarkan_asuransi,
            km.catatan_analis,
            f.nama_faskes,
            kp.nama_penyakit,
            kp.kategori_berat,
            pem.nama_lengkap AS nama_pemegang,
            tg.nama_lengkap AS nama_tanggungan
        FROM klaim_medis km
        JOIN faskes f ON km.id_faskes = f.id_faskes
        JOIN kategori_penyakit kp ON km.kode_icd = kp.kode_icd
        JOIN polis p ON km.no_polis = p.no_polis
        JOIN pemegang_polis pem ON p.id_pemegang = pem.id_pemegang
        LEFT JOIN tanggungan_polis tg ON km.id_tanggungan = tg.id_tanggungan
        $whereClause
        ORDER BY 
            CASE WHEN km.status_klaim = 'Pending' THEN 1 
                 WHEN km.status_klaim = 'Investigasi' THEN 2 
                 ELSE 3 END ASC, 
            km.tanggal_masuk DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $klaim_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Investigasi & Persetujuan Klaim</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Evaluasi riwayat medis, setujui penjaminan, dan catat hasil analisis.</p>
    </div>
    
    <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <div style="position: relative;">
            <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input type="text" name="search" placeholder="Cari Pasien / Faskes..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
        </div>
        <select name="status" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
            <option value="">Semua Status</option>
            <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Investigasi" <?php echo $status === 'Investigasi' ? 'selected' : ''; ?>>Investigasi</option>
            <option value="Approved" <?php echo $status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
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
        <h3>Antrean Klaim Medis</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No. Klaim / Pasien</th>
                    <th>Faskes & Penyakit</th>
                    <th>Perawatan</th>
                    <th>Total Tagihan (Rp)</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($klaim_list) > 0): ?>
                    <?php foreach ($klaim_list as $row): ?>
                    <?php 
                        $nama_pasien = $row['nama_tanggungan'] ? $row['nama_tanggungan'] . " (Tanggungan)" : $row['nama_pemegang'] . " (Pemegang Polis)";
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['no_klaim']); ?></div>
                            <div style="font-size: 12px; color: #1e293b; margin-top: 4px;"><i class="fa-solid fa-user-injured" style="margin-right:5px; color:#94a3b8;"></i><?php echo htmlspecialchars($nama_pasien); ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: #334155;"><i class="fa-regular fa-hospital" style="margin-right:5px; color:#94a3b8;"></i><?php echo htmlspecialchars($row['nama_faskes']); ?></div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                <i class="fa-solid fa-virus" style="margin-right:5px; color:#94a3b8;"></i><?php echo htmlspecialchars($row['nama_penyakit']); ?> 
                                (<?php echo htmlspecialchars($row['kategori_berat']); ?>)
                            </div>
                        </td>
                        <td style="color: #64748b;">
                            <?php echo htmlspecialchars($row['jenis_perawatan']); ?><br>
                            <small><?php echo date('d M Y', strtotime($row['tanggal_masuk'])); ?></small>
                        </td>
                        <td style="color: #ef4444; font-weight: 600;">
                            <?php echo number_format($row['total_tagihan_faskes'], 0, ',', '.'); ?>
                        </td>
                        <td>
                            <?php 
                                $status = $row['status_klaim'];
                                $badge = 'badge-neutral';
                                if ($status == 'Pending') $badge = 'badge-warning';
                                else if ($status == 'Investigasi') $badge = 'badge-primary';
                                else if ($status == 'Approved') $badge = 'badge-success';
                                else if ($status == 'Rejected') $badge = 'badge-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button onclick="openEvaluasiModal(
                                    '<?php echo htmlspecialchars($row['no_klaim']); ?>',
                                    '<?php echo htmlspecialchars($nama_pasien); ?>',
                                    '<?php echo number_format($row['total_tagihan_faskes'], 0, ',', '.'); ?>',
                                    '<?php echo htmlspecialchars($row['status_klaim']); ?>',
                                    '<?php echo htmlspecialchars($row['total_dibayarkan_asuransi']); ?>',
                                    '<?php echo htmlspecialchars($row['catatan_analis'], ENT_QUOTES); ?>'
                                )" 
                                class="btn-admin btn-admin-sm btn-admin-primary" title="Buka Form Evaluasi">
                                <i class="fa-solid fa-clipboard-user"></i> Evaluasi Klaim
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada antrean klaim medis.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EVALUASI KLAIM -->
<div id="modalEvaluasi" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 550px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h3 style="color: #1e293b;"><i class="fa-solid fa-file-medical" style="margin-right: 8px; color:#3b82f6;"></i>Evaluasi & Keputusan Klaim</h3>
            <button onclick="closeModal('modalEvaluasi')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px; overflow-y: auto;">
            
            <div style="background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 12px; color: #64748b;">No. Klaim</span>
                    <strong id="eNoKlaim" style="color: #3b82f6; font-size: 13px;">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 12px; color: #64748b;">Nama Pasien</span>
                    <strong id="ePasien" style="color: #1e293b; font-size: 13px;">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">Total Tagihan Faskes</span>
                    <strong id="eTagihan" style="color: #ef4444; font-size: 14px;">-</strong>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="no_klaim" id="eInputNoKlaim" value="">
                
                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Status Keputusan *</label>
                    <select name="status_klaim" id="eStatus" required onchange="togglePencairan()" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 500;">
                        <option value="Pending">Pending (Antrean)</option>
                        <option value="Investigasi">Investigasi (Butuh pendalaman)</option>
                        <option value="Approved">Approved (Disetujui untuk dicairkan)</option>
                        <option value="Rejected">Rejected (Ditolak / Tidak dijamin)</option>
                    </select>
                </div>

                <div id="pencairanBox" style="display: none; background: #ecfdf5; padding: 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #10b981;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #065f46; margin-bottom: 6px;">Nominal Disetujui/Dibayarkan (Rp) *</label>
                    <input type="number" name="total_dibayarkan_asuransi" id="eNominalCair" min="0" step="1000" style="width: 100%; padding: 10px; border: 1px solid #34d399; border-radius: 6px; font-size: 16px; font-weight: bold; color: #065f46;" placeholder="Misal: 5000000">
                    <small style="display: block; color: #059669; font-size: 11px; margin-top: 6px;">Masukkan jumlah uang yang disetujui untuk ditanggung asuransi.</small>
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Catatan Analis</label>
                    <textarea name="catatan_analis" id="eCatatan" rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;" placeholder="Tuliskan alasan persetujuan, penolakan, atau hasil investigasi..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                    <button type="button" onclick="closeModal('modalEvaluasi')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-primary">Simpan Evaluasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEvaluasiModal(noKlaim, pasien, tagihan, status, dibayarkan, catatan) {
        document.getElementById('eInputNoKlaim').value = noKlaim;
        document.getElementById('eNoKlaim').textContent = noKlaim;
        document.getElementById('ePasien').textContent = pasien;
        document.getElementById('eTagihan').textContent = "Rp " + tagihan;
        
        document.getElementById('eStatus').value = status;
        document.getElementById('eCatatan').value = catatan || '';
        document.getElementById('eNominalCair').value = dibayarkan > 0 ? dibayarkan : '';

        // Panggil toggle untuk mengatur visibilitas form input Nominal
        togglePencairan();

        document.getElementById('modalEvaluasi').style.display = 'flex';
    }

    function togglePencairan() {
        const status = document.getElementById('eStatus').value;
        const box = document.getElementById('pencairanBox');
        const input = document.getElementById('eNominalCair');
        
        if (status === 'Approved') {
            box.style.display = 'block';
            input.required = true;
        } else {
            box.style.display = 'none';
            input.required = false;
            input.value = ''; // Reset nilai
        }
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
