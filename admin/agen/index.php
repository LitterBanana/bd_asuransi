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

        try {
            if ($action === 'create') {
                $kode_agen = trim($_POST['kode_agen']);
                $nama_agen = trim($_POST['nama_agen']);
                $no_telepon = trim($_POST['no_telepon']);
                $komisi = floatval($_POST['persentase_komisi']);
                
                $username = trim($_POST['username']);
                $password = $_POST['password'];

                $conn->beginTransaction();

                // 1. Cek duplikasi kode agen & username
                $stmt_cek = $conn->prepare("SELECT kode_agen FROM agen WHERE kode_agen = ?");
                $stmt_cek->execute([$kode_agen]);
                if ($stmt_cek->rowCount() > 0) throw new Exception("Kode Agen '$kode_agen' sudah digunakan.");

                $stmt_cek2 = $conn->prepare("SELECT username FROM users WHERE username = ?");
                $stmt_cek2->execute([$username]);
                if ($stmt_cek2->rowCount() > 0) throw new Exception("Username '$username' sudah terdaftar.");

                // 2. Insert ke agen
                $stmt = $conn->prepare("INSERT INTO agen (kode_agen, nama_agen, no_telepon, persentase_komisi) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_agen, $nama_agen, $no_telepon, $komisi]);
                $id_agen = $conn->lastInsertId();

                // 3. Insert ke users
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, id_agen, no_telp) VALUES (?, ?, 'Agen', ?, ?)");
                $stmt2->execute([$username, $hashed_password, $id_agen, $no_telepon]);

                $conn->commit();
                $_SESSION['toast_success'] = "Agen $nama_agen berhasil didaftarkan.";

            } elseif ($action === 'update_komisi') {
                $id_agen = $_POST['id_agen'];
                $komisi = floatval($_POST['persentase_komisi']);
                
                $stmt = $conn->prepare("UPDATE agen SET persentase_komisi = ? WHERE id_agen = ?");
                $stmt->execute([$komisi, $id_agen]);
                
                $_SESSION['toast_success'] = "Komisi agen berhasil diperbarui menjadi " . number_format($komisi, 2) . "%.";

            } elseif ($action === 'toggle_status') {
                $id_agen = $_POST['id_agen'];
                $status_baru = $_POST['status_baru']; // 'Aktif' atau 'Resign'
                
                $conn->beginTransaction();
                
                // Update status di tabel agen
                $stmt = $conn->prepare("UPDATE agen SET status_aktif = ? WHERE id_agen = ?");
                $stmt->execute([$status_baru, $id_agen]);

                // Update status login di tabel users (Resign = Blokir login)
                $status_akun = ($status_baru === 'Aktif') ? 'Aktif' : 'Blokir';
                $stmt2 = $conn->prepare("UPDATE users SET status_akun = ? WHERE id_agen = ?");
                $stmt2->execute([$status_akun, $id_agen]);

                $conn->commit();
                $_SESSION['toast_success'] = "Status kepegawaian Agen berhasil diubah menjadi $status_baru.";

            } elseif ($action === 'import_csv') {
                if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['file_csv']['tmp_name'];
                    
                    if (($handle = fopen($file_tmp, "r")) !== FALSE) {
                        $conn->beginTransaction();
                        $row_count = 0;
                        $sukses = 0;
                        
                        try {
                            // Baca baris header (abaikan datanya)
                            $header = fgetcsv($handle, 1000, ",");
                            
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                $row_count++;
                                // Format asumsi: KodeAgen(0), Nama(1), Telp(2), Komisi(3), Username(4), Password(5)
                                if (count($data) < 6) continue; // Skip jika kolom kurang

                                $kode_agen = trim($data[0]);
                                $nama_agen = trim($data[1]);
                                $no_telepon = trim($data[2]);
                                $komisi = floatval($data[3]);
                                $username = trim($data[4]);
                                $password = trim($data[5]);

                                // Lewati baris jika kosong
                                if (empty($kode_agen) || empty($username) || empty($password)) continue;

                                // 1. Cek duplikasi untuk row ini
                                $stmt_cek = $conn->prepare("SELECT kode_agen FROM agen WHERE kode_agen = ?");
                                $stmt_cek->execute([$kode_agen]);
                                if ($stmt_cek->rowCount() > 0) throw new Exception("Duplikat pada baris $row_count: Kode Agen '$kode_agen' sudah ada.");

                                $stmt_cek2 = $conn->prepare("SELECT username FROM users WHERE username = ?");
                                $stmt_cek2->execute([$username]);
                                if ($stmt_cek2->rowCount() > 0) throw new Exception("Duplikat pada baris $row_count: Username '$username' sudah terdaftar.");

                                // 2. Insert ke agen
                                $stmt = $conn->prepare("INSERT INTO agen (kode_agen, nama_agen, no_telepon, persentase_komisi) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$kode_agen, $nama_agen, $no_telepon, $komisi]);
                                $id_agen = $conn->lastInsertId();

                                // 3. Insert ke users
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, id_agen, no_telp) VALUES (?, ?, 'Agen', ?, ?)");
                                $stmt2->execute([$username, $hashed_password, $id_agen, $no_telepon]);

                                $sukses++;
                            }
                            $conn->commit();
                            $_SESSION['toast_success'] = "Import Berhasil! $sukses Agen baru telah didaftarkan.";

                        } catch (Exception $e) {
                            $conn->rollBack();
                            $_SESSION['toast_error'] = "Gagal Import. Dibatalkan seluruhnya. " . $e->getMessage();
                        }
                        fclose($handle);
                    } else {
                        $_SESSION['toast_error'] = "Gagal membaca file CSV.";
                    }
                } else {
                    $_SESSION['toast_error'] = "File tidak ditemukan atau terjadi error saat upload.";
                }
            }

        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            error_log("Error agen management: " . $e->getMessage());
            $_SESSION['toast_error'] = $e->getMessage();
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
        $whereClause .= " AND (a.kode_agen LIKE ? OR a.nama_agen LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status !== '') {
        $whereClause .= " AND a.status_aktif = ?";
        $params[] = $status;
    }

    // Ambil data semua agen + akun usernamenya
    $query = "
        SELECT a.*, u.username, u.status_akun 
        FROM agen a
        LEFT JOIN users u ON a.id_agen = u.id_agen
        $whereClause
        ORDER BY a.status_aktif ASC, a.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $agen_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Direktori Agen (Mitra)</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola pendaftaran, penugasan, dan status kemitraan agen penjual.</p>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" placeholder="Cari Kode/Nama/User..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
            </div>
            <select name="status" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
                <option value="">Semua Status</option>
                <option value="Aktif" <?php echo $status === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="Resign" <?php echo $status === 'Resign' ? 'selected' : ''; ?>>Resign</option>
            </select>
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">Cari</button>
            <?php if ($search || $status): ?>
                <a href="index.php" class="btn-admin btn-admin-ghost" style="padding: 8px 16px;">Reset</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; gap: 10px; height: 100%; align-items: center;">
            <button onclick="openImportModal()" class="btn-admin btn-admin-ghost" style="height: 100%;">
                <i class="fa-solid fa-file-csv" style="color: #10b981;"></i> Import CSV
            </button>
            <button onclick="openCreateModal()" class="btn-admin btn-admin-primary" style="height: 100%;">
                <i class="fa-solid fa-user-plus"></i> Tambah Agen
            </button>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Agen Aktif & Non-Aktif</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kode Agen</th>
                    <th>Nama Lengkap</th>
                    <th>Kontak / Telp</th>
                    <th>Komisi (%)</th>
                    <th>Akun Login</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($agen_list) > 0): ?>
                    <?php foreach ($agen_list as $row): ?>
                    <tr>
                        <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['kode_agen']); ?></td>
                        <td style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($row['nama_agen']); ?></td>
                        <td style="color: #475569; font-size: 13px;"><i class="fa-solid fa-phone" style="margin-right:5px; color:#94a3b8;"></i><?php echo htmlspecialchars($row['no_telepon'] ?: '-'); ?></td>
                        <td style="color: #10b981; font-weight: 600;"><?php echo number_format($row['persentase_komisi'], 2); ?>%</td>
                        <td style="color: #64748b;">
                            <i class="fa-solid fa-user-lock" style="margin-right:5px; font-size:12px;"></i>
                            <?php echo htmlspecialchars($row['username'] ?? 'Tidak Terhubung'); ?>
                        </td>
                        <td>
                            <?php if ($row['status_aktif'] === 'Aktif'): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-neutral"><i class="fa-solid fa-user-xmark"></i> Resign</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <button onclick="openEditKomisiModal('<?php echo $row['id_agen']; ?>', '<?php echo addslashes(htmlspecialchars($row['nama_agen'])); ?>', '<?php echo $row['persentase_komisi']; ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-primary" title="Edit Komisi" style="margin-right: 5px;">
                                <i class="fa-solid fa-percent"></i> Edit
                            </button>
                            <?php if ($row['status_aktif'] === 'Aktif'): ?>
                                <button onclick="confirmToggleStatus('<?php echo $row['id_agen']; ?>', 'Resign', '<?php echo addslashes(htmlspecialchars($row['nama_agen'])); ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Tandai Resign">
                                    <i class="fa-solid fa-user-minus"></i> Resign
                                </button>
                            <?php else: ?>
                                <button onclick="confirmToggleStatus('<?php echo $row['id_agen']; ?>', 'Aktif', '<?php echo addslashes(htmlspecialchars($row['nama_agen'])); ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-success" title="Aktifkan Kembali">
                                    <i class="fa-solid fa-user-check"></i> Aktifkan
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada data agen terdaftar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH AGEN -->
<div id="modalCreate" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;">Pendaftaran Agen Baru</h3>
            <button onclick="closeModal('modalCreate')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                
                <h4 style="font-size: 14px; color: #3b82f6; border-bottom: 1px dashed #cbd5e1; padding-bottom: 5px; margin-bottom: 15px;">Informasi Kepegawaian</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Kode Agen *</label>
                        <input type="text" name="kode_agen" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Misal: AGN-001">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Komisi Dasar (%) *</label>
                        <input type="number" name="persentase_komisi" step="0.01" value="5.00" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>
                
                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Lengkap Agen *</label>
                    <input type="text" name="nama_agen" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">No. Telepon / WhatsApp</label>
                    <input type="text" name="no_telepon" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <h4 style="font-size: 14px; color: #3b82f6; border-bottom: 1px dashed #cbd5e1; padding-bottom: 5px; margin-bottom: 15px;">Pembuatan Akun Sistem</h4>
                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="input-group">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Username Login *</label>
                            <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="input-group">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Password Login *</label>
                            <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalCreate')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-primary">Daftarkan Agen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT CSV -->
<div id="modalImport" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 500px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;"><i class="fa-solid fa-file-import" style="color: #10b981; margin-right: 8px;"></i> Import Data Agen (CSV)</h3>
            <button onclick="closeModal('modalImport')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            
            <div style="background: #fffbeb; padding: 15px; border-radius: 6px; border: 1px solid #fde68a; margin-bottom: 20px;">
                <h4 style="font-size: 13px; color: #b45309; margin: 0 0 5px 0;">Instruksi Format CSV:</h4>
                <p style="font-size: 12px; color: #92400e; margin: 0 0 10px 0;">Sistem akan melewati (mengabaikan) baris pertama karena dianggap sebagai Judul Header. Pastikan urutan kolom di file CSV Anda <b>wajib</b> seperti ini:</p>
                <div style="background: #fff; border: 1px dashed #d97706; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 11px; color: #451a03; overflow-x: auto;">
                    Kode Agen, Nama Agen, Telp, Komisi, Username, Password<br>
                    AGN-002, Budi Santoso, 081234, 5.00, budi01, SandiRahasia<br>
                    AGN-003, Siti Aminah, 085678, 6.50, siti22, SandiRahasia
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                
                <div class="input-group" style="margin-bottom: 25px; text-align: center; padding: 30px 10px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 32px; color: #94a3b8; margin-bottom: 10px;"></i><br>
                    <input type="file" name="file_csv" accept=".csv" required style="font-size: 13px; color: #475569;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalImport')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-success">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT KOMISI -->
<div id="modalEditKomisi" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 400px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;">Edit Komisi Agen</h3>
            <button type="button" onclick="closeModal('modalEditKomisi')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_komisi">
                <input type="hidden" name="id_agen" id="editKomisiIdAgen" value="">
                
                <div style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; margin-bottom: 20px; border: 1px dashed #cbd5e1;">
                    <span style="font-size: 12px; color: #64748b; display: block;">Nama Agen:</span>
                    <strong id="editKomisiNamaAgen" style="color: #3b82f6; font-size: 15px;"></strong>
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Komisi Baru (%) *</label>
                    <input type="number" name="persentase_komisi" id="editKomisiInput" step="0.01" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalEditKomisi')" class="btn-admin btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM INVISIBLE UNTUK AKSI CEPAT (RESIGN/AKTIF) -->
<form id="formQuickAction" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="toggle_status">
    <input type="hidden" name="id_agen" id="qaIdAgen" value="">
    <input type="hidden" name="status_baru" id="qaStatusBaru" value="">
</form>

<script>
    function openCreateModal() { document.getElementById('modalCreate').style.display = 'flex'; }
    function openImportModal() { document.getElementById('modalImport').style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openEditKomisiModal(idAgen, namaAgen, komisi) {
        document.getElementById('editKomisiIdAgen').value = idAgen;
        document.getElementById('editKomisiNamaAgen').textContent = namaAgen;
        document.getElementById('editKomisiInput').value = komisi;
        document.getElementById('modalEditKomisi').style.display = 'flex';
    }

    function confirmToggleStatus(idAgen, statusBaru, namaAgen) {
        let msg = statusBaru === 'Resign' 
            ? `Tandai Agen '${namaAgen}' sebagai RESIGN?\n\nPeringatan: Akun login agen ini juga akan otomatis diblokir oleh sistem.`
            : `Aktifkan kembali status kepegawaian Agen '${namaAgen}'?\n\nAkun login mereka juga akan kembali dipulihkan.`;
        
        if (confirm(msg)) {
            document.getElementById('qaIdAgen').value = idAgen;
            document.getElementById('qaStatusBaru').value = statusBaru;
            document.getElementById('formQuickAction').submit();
        }
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
