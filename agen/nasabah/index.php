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

    $success_msg = '';
    $error_msg = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_nasabah') {
        $nik = $_POST['nik'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $pekerjaan = $_POST['pekerjaan'];
        $alamat = $_POST['alamat'];
        $no_telepon = $_POST['no_telepon'];
        $email = $_POST['email'];

        try {
            $stmtInsert = $conn->prepare("INSERT INTO pemegang_polis (id_agen, nik, nama_lengkap, tanggal_lahir, jenis_kelamin, pekerjaan, alamat, no_telepon, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$id_agen, $nik, $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $pekerjaan, $alamat, $no_telepon, $email]);
            $success_msg = "Nasabah baru berhasil didaftarkan ke dalam sistem. Anda dapat memilih nasabah ini saat membuat polis.";
        } catch (Exception $e) {
            $error_msg = "Gagal menambahkan nasabah: " . $e->getMessage();
        }
    }

    // Get list of nasabah for this agent
    $stmt = $conn->prepare("
        SELECT c.*,
            (SELECT COUNT(*) FROM polis WHERE id_pemegang = c.id_pemegang AND id_agen = ?) as total_polis,
            (SELECT status_polis FROM polis WHERE id_pemegang = c.id_pemegang AND id_agen = ? ORDER BY tanggal_terbit DESC LIMIT 1) as status_polis_terbaru,
            (SELECT no_polis FROM polis WHERE id_pemegang = c.id_pemegang AND id_agen = ? ORDER BY tanggal_terbit DESC LIMIT 1) as no_polis_terbaru
        FROM (
            SELECT p.id_pemegang, p.nik, p.nama_lengkap, p.no_telepon, p.email
            FROM pemegang_polis p
            LEFT JOIN polis pol ON p.id_pemegang = pol.id_pemegang
            WHERE p.id_agen = ? OR pol.id_agen = ?

            UNION

            SELECT NULL as id_pemegang, '-' as nik, CONCAT(u.username, ' (Profil Belum Lengkap)') as nama_lengkap, u.no_telp as no_telepon, u.email
            FROM users u
            WHERE u.id_agen = ? AND u.id_pemegang IS NULL AND u.role = 'Customer'
        ) as c
        ORDER BY c.nama_lengkap ASC
    ");
    $stmt->execute([$id_agen, $id_agen, $id_agen, $id_agen, $id_agen, $id_agen]);
    $nasabah_list = $stmt->fetchAll();
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Daftar Nasabah</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola dan pantau daftar pemegang polis yang menjadi klien Anda.</p>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success" style="padding: 15px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="alert alert-danger" style="padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<?php if (count($nasabah_list) > 0): ?>
<div class="admin-card">
    <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Klien Anda</h3>
        <button onclick="openCreateModal()" class="btn btn-sm" style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; border: none; cursor: pointer;">
            <i class="fa-solid fa-user-plus" style="margin-right: 5px;"></i> Tambah Nasabah
        </button>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama & Kontak</th>
                    <th>NIK</th>
                    <th style="text-align: center;">Total Polis</th>
                    <th style="text-align: center;">Status Polis</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nasabah_list as $row): ?>
                <tr>
                    <td style="font-weight: 600; color: #1e293b;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 35px; height: 35px; border-radius: 8px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($row['nama_lengkap']); ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: normal;">
                                    <?php echo htmlspecialchars($row['email']); ?> &bull; <?php echo htmlspecialchars($row['no_telepon']); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="color: #64748b;"><?php echo htmlspecialchars($row['nik']); ?></td>
                    <td style="text-align: center; font-weight: 600; font-size: 15px; color: #0f172a;">
                        <?php echo $row['total_polis']; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($row['total_polis'] == 0): ?>
                            <span class="badge badge-neutral" style="font-size: 11px;">Prospek (Tanpa Polis)</span>
                        <?php else: ?>
                            <?php if ($row['status_polis_terbaru'] === 'Inforce'): ?>
                                <span class="badge badge-success" style="font-size: 11px;">Inforce</span>
                            <?php elseif ($row['status_polis_terbaru'] === 'Pending Approval'): ?>
                                <span class="badge badge-warning" style="font-size: 11px;">Pending Approval (Verif Admin)</span>
                            <?php elseif ($row['status_polis_terbaru'] === 'Rejected'): ?>
                                <span class="badge badge-danger" style="font-size: 11px;">Ditolak</span>
                            <?php else: ?>
                                <span class="badge badge-neutral" style="font-size: 11px;"><?php echo htmlspecialchars($row['status_polis_terbaru']); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center; display: flex; gap: 5px; justify-content: center; align-items: center; min-height: 35px;">
                        <?php if ($row['status_polis_terbaru'] === 'Inforce' && $row['no_polis_terbaru']): ?>
                            <button type="button" onclick="openBayarModal('<?php echo htmlspecialchars($row['no_polis_terbaru']); ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" class="btn" style="background: #10b981; color: white; padding: 5px 10px; border-radius: 6px; border: none; font-size: 11px; cursor: pointer;" title="Bayar Tagihan">
                                <i class="fa-solid fa-money-bill-wave"></i> Bayar
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($row['no_polis_terbaru']): ?>
                            <a href="print.php?no_polis=<?php echo urlencode($row['no_polis_terbaru']); ?>" class="btn" style="background: #3b82f6; color: white; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 11px;" title="Cetak Polis Terbaru">
                                <i class="fa-solid fa-print"></i> Cetak
                            </a>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="empty-state" style="padding: 40px 20px; text-align: center; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 40px; margin-bottom: 15px; color: #94a3b8;"><i class="fa-solid fa-users-slash"></i></div>
        <h3 style="margin: 0 0 10px 0; color: #1e293b;">Belum Ada Nasabah</h3>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 14px;">Anda belum mendaftarkan nasabah/klien sama sekali.</p>
        <button onclick="openCreateModal()" class="btn" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; border: none; cursor: pointer;">
            <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i> Daftar Nasabah Baru
        </button>
    </div>
<?php endif; ?>

<!-- MODAL FORM CREATE NASABAH -->
<div id="modalCreateNasabah" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Daftarkan Nasabah Baru</h3>
            <button onclick="closeModal('modalCreateNasabah')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="overflow-y: auto; padding: 20px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_nasabah">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">NIK (No. KTP) *</label>
                        <input type="text" name="nik" required pattern="[0-9]{16}" title="Harus 16 digit angka" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Pekerjaan</label>
                        <input type="text" name="pekerjaan" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Email</label>
                        <input type="email" name="email" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">No. Telepon / WhatsApp</label>
                    <input type="text" name="no_telepon" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Alamat Lengkap *</label>
                    <textarea name="alamat" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalCreateNasabah')" class="btn btn-ghost" style="padding: 10px 20px;">Batal</button>
                    <button type="submit" class="btn" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px;">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('modalCreateNasabah').style.display = 'flex';
    }

    function openBayarModal(noPolis, namaNasabah) {
        document.getElementById('bayar_no_polis').value = noPolis;
        document.getElementById('bayar_nama_nasabah').value = namaNasabah + ' (Polis: ' + noPolis + ')';
        document.getElementById('modalBayarNasabah').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<!-- MODAL FORM BAYAR TAGIHAN -->
<div id="modalBayarNasabah" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 450px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Input Pembayaran Tagihan</h3>
            <button onclick="closeModal('modalBayarNasabah')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Sistem akan mencari dan memproses tagihan yang belum dibayar (Unpaid/Overdue) untuk polis ini secara otomatis.</p>
            <form action="bayar_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="no_polis" id="bayar_no_polis">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nasabah & Polis</label>
                    <input type="text" id="bayar_nama_nasabah" readonly style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; color: #64748b; outline: none;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Metode Pembayaran</label>
                    <select name="metode_bayar" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        <option value="Cash">Cash (Titip Agen)</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Bukti Pembayaran / Tanda Terima</label>
                    <input type="file" name="bukti_pembayaran" required accept=".jpg,.jpeg,.png,.pdf" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">Format: JPG, PNG, PDF (Maks 2MB).</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalBayarNasabah')" class="btn btn-ghost" style="padding: 10px 20px;">Batal</button>
                    <button type="submit" class="btn" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 6px;">Kirim Data Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../layouts/agen/footer.php"; ?>
