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

        if ($action === 'create') {
            $kode_icd = trim($_POST['kode_icd'] ?? '');
            $nama_penyakit = trim($_POST['nama_penyakit'] ?? '');
            $kategori_berat = $_POST['kategori_berat'] ?? 'Ringan';

            try {
                $stmt = $conn->prepare("INSERT INTO kategori_penyakit (kode_icd, nama_penyakit, kategori_berat) VALUES (?, ?, ?)");
                $stmt->execute([$kode_icd, $nama_penyakit, $kategori_berat]);
                $_SESSION['toast_success'] = 'Kategori Penyakit berhasil ditambahkan.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Kode ICD sudah terdaftar. Silakan gunakan kode lain.';
                } else {
                    error_log("Error create penyakit: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat menyimpan data.';
                }
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

        } elseif ($action === 'update') {
            $kode_icd = trim($_POST['kode_icd'] ?? '');
            $nama_penyakit = trim($_POST['nama_penyakit'] ?? '');
            $kategori_berat = $_POST['kategori_berat'] ?? 'Ringan';

            try {
                // Update hanya nama dan kategori, kode ICD adalah primary key
                $stmt = $conn->prepare("UPDATE kategori_penyakit SET nama_penyakit=?, kategori_berat=? WHERE kode_icd=?");
                $stmt->execute([$nama_penyakit, $kategori_berat, $kode_icd]);
                $_SESSION['toast_success'] = 'Data Penyakit berhasil diperbarui.';
            } catch (PDOException $e) {
                error_log("Error update penyakit: " . $e->getMessage());
                $_SESSION['toast_error'] = 'Terjadi kesalahan saat memperbarui data.';
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

        } elseif ($action === 'delete') {
            $kode_icd = $_POST['kode_icd'] ?? '';

            try {
                $stmt = $conn->prepare("DELETE FROM kategori_penyakit WHERE kode_icd = ?");
                $stmt->execute([$kode_icd]);
                $_SESSION['toast_success'] = 'Penyakit berhasil dihapus dari sistem.';
            } catch (PDOException $e) {
                // Constraint violation
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Gagal dihapus! Penyakit ini sudah terikat dengan riwayat Klaim Medis.';
                } else {
                    error_log("Error delete penyakit: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat menghapus data.';
                }
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

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
                        
                        $kategori_valid = ['Ringan', 'Sedang', 'Berat', 'Kritis'];
                        
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $row_count++;
                            // Format: KodeICD(0), NamaPenyakit(1), KategoriBerat(2)
                            if (count($data) < 3) continue;

                            $kode_icd = trim($data[0]);
                            $nama_penyakit = trim($data[1]);
                            $kategori_berat = trim($data[2]);

                            if (empty($kode_icd) || empty($nama_penyakit)) continue;

                            // Validasi kategori berat
                            if (!in_array($kategori_berat, $kategori_valid)) {
                                throw new Exception("Error baris $row_count: Kategori Berat '$kategori_berat' tidak valid. Gunakan: Ringan, Sedang, Berat, atau Kritis.");
                            }

                            // Cek duplikasi kode_icd
                            $stmt_cek = $conn->prepare("SELECT kode_icd FROM kategori_penyakit WHERE kode_icd = ?");
                            $stmt_cek->execute([$kode_icd]);
                            if ($stmt_cek->rowCount() > 0) throw new Exception("Duplikat pada baris $row_count: Kode ICD '$kode_icd' sudah ada.");

                            // Insert
                            $stmt = $conn->prepare("INSERT INTO kategori_penyakit (kode_icd, nama_penyakit, kategori_berat) VALUES (?, ?, ?)");
                            $stmt->execute([$kode_icd, $nama_penyakit, $kategori_berat]);
                            $sukses++;
                        }
                        $conn->commit();
                        $_SESSION['toast_success'] = "Import Berhasil! $sukses Kategori Penyakit baru telah ditambahkan.";

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
            echo "<script>window.location.href='index.php';</script>";
            exit;
        }
    }

    // Ambil data filter
    $search = $_GET['search'] ?? '';
    $kategori = $_GET['kategori'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $whereClause .= " AND (kode_icd LIKE ? OR nama_penyakit LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($kategori !== '') {
        $whereClause .= " AND kategori_berat = ?";
        $params[] = $kategori;
    }

    // Ambil semua data penyakit
    $query = "SELECT * FROM kategori_penyakit $whereClause ORDER BY kode_icd ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $penyakit_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Manajemen Kategori Penyakit (ICD-10)</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola referensi daftar penyakit dan kategori keparahannya.</p>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" placeholder="Cari Kode/Nama Penyakit..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
            </div>
            <select name="kategori" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
                <option value="">Semua Kategori</option>
                <option value="Ringan" <?php echo $kategori === 'Ringan' ? 'selected' : ''; ?>>Ringan</option>
                <option value="Sedang" <?php echo $kategori === 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                <option value="Berat" <?php echo $kategori === 'Berat' ? 'selected' : ''; ?>>Berat</option>
                <option value="Kritis" <?php echo $kategori === 'Kritis' ? 'selected' : ''; ?>>Kritis</option>
            </select>
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">Cari</button>
            <?php if ($search || $kategori): ?>
                <a href="index.php" class="btn-admin btn-admin-ghost" style="padding: 8px 16px;">Reset</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; gap: 10px; height: 100%; align-items: center;">
            <button onclick="openImportModal()" class="btn-admin btn-admin-ghost" style="height: 100%;">
                <i class="fa-solid fa-file-csv" style="color: #10b981;"></i> Import CSV
            </button>
            <button onclick="openCreateModal()" class="btn-admin btn-admin-primary" style="padding: 10px 20px;">
                <i class="fa-solid fa-plus"></i> Tambah Penyakit
            </button>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Kategori Penyakit</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kode ICD</th>
                    <th>Nama Penyakit</th>
                    <th>Kategori Berat</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($penyakit_list) > 0): ?>
                    <?php foreach ($penyakit_list as $row): ?>
                    <tr>
                        <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['kode_icd']); ?></td>
                        <td style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($row['nama_penyakit']); ?></td>
                        <td>
                            <?php 
                                $cat = $row['kategori_berat'];
                                $badge = 'badge-neutral';
                                if ($cat == 'Ringan') $badge = 'badge-success';
                                else if ($cat == 'Sedang') $badge = 'badge-warning';
                                else if ($cat == 'Berat') $badge = 'badge-info'; // orange-ish later if customized, or use badge-danger below
                                else if ($cat == 'Kritis') $badge = 'badge-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?>" style="font-size: 11px;">
                                <?php echo htmlspecialchars($cat); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button onclick="openEditModal(this)" 
                                data-kode="<?php echo htmlspecialchars($row['kode_icd']); ?>"
                                data-nama="<?php echo htmlspecialchars($row['nama_penyakit']); ?>"
                                data-kategori="<?php echo htmlspecialchars($row['kategori_berat']); ?>"
                                class="btn-admin btn-admin-sm btn-admin-ghost-primary" title="Edit Kategori">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button onclick="openDeleteModal('<?php echo htmlspecialchars($row['kode_icd']); ?>')" 
                                class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Hapus Kategori">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada daftar penyakit yang terdaftar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL FORM PENYAKIT (CREATE & UPDATE) -->
<div id="modalFormPenyakit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 450px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalFormTitle">Tambah Penyakit Baru</h3>
            <button onclick="closeModal('modalFormPenyakit')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="overflow-y: auto; padding: 20px;">
            <form id="formPenyakit" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create">

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Kode ICD-10 *</label>
                    <input type="text" name="kode_icd" id="inputKode" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Misal: J00">
                    <small id="kodeHelp" style="display: none; color: #f59e0b; font-size: 11px; margin-top: 4px;">Kode ICD tidak dapat diubah (Primary Key).</small>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Penyakit *</label>
                    <input type="text" name="nama_penyakit" id="inputNama" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Misal: Acute nasopharyngitis">
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Kategori Keparahan *</label>
                    <select name="kategori_berat" id="inputKategori" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="Ringan">Ringan</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Berat">Berat</option>
                        <option value="Kritis">Kritis</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalFormPenyakit')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI DELETE -->
<div id="modalDelete" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 400px; margin: 20px;">
        <div class="admin-card-header" style="background: #fef2f2; border-bottom: 1px solid #fee2e2;">
            <h3 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Hapus Penyakit</h3>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <p>Apakah Anda yakin ingin menghapus kategori penyakit ini? Jika penyakit ini pernah tercatat dalam riwayat klaim medis, sistem akan memblokir penghapusan.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="kode_icd" id="delKodeIcd" value="">
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal('modalDelete')" class="btn-admin btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-danger">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT CSV PENYAKIT -->
<div id="modalImportPenyakit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 500px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;"><i class="fa-solid fa-file-import" style="color: #10b981; margin-right: 8px;"></i> Import Kategori Penyakit (CSV)</h3>
            <button onclick="closeModal('modalImportPenyakit')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            
            <div style="background: #fffbeb; padding: 15px; border-radius: 6px; border: 1px solid #fde68a; margin-bottom: 20px;">
                <h4 style="font-size: 13px; color: #b45309; margin: 0 0 5px 0;">Instruksi Format CSV:</h4>
                <p style="font-size: 12px; color: #92400e; margin: 0 0 10px 0;">Sistem akan melewati (mengabaikan) baris pertama karena dianggap sebagai Judul Header. Pastikan urutan kolom di file CSV Anda <b>wajib</b> seperti ini:</p>
                <div style="background: #fff; border: 1px dashed #d97706; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 11px; color: #451a03; overflow-x: auto;">
                    Kode ICD, Nama Penyakit, Kategori Berat<br>
                    J00, Acute nasopharyngitis (common cold), Ringan<br>
                    I21, Acute myocardial infarction, Kritis
                </div>
                <p style="font-size: 11px; color: #92400e; margin: 10px 0 0 0;"><b>Kategori Berat</b> yang valid: <code>Ringan</code>, <code>Sedang</code>, <code>Berat</code>, <code>Kritis</code></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                
                <div class="input-group" style="margin-bottom: 25px; text-align: center; padding: 30px 10px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 32px; color: #94a3b8; margin-bottom: 10px;"></i><br>
                    <input type="file" name="file_csv" accept=".csv" required style="font-size: 13px; color: #475569;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalImportPenyakit')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-success">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openImportModal() { document.getElementById('modalImportPenyakit').style.display = 'flex'; }

    function openCreateModal() {
        document.getElementById('modalFormTitle').textContent = 'Tambah Penyakit Baru';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formPenyakit').reset();
        
        // Buka kunci input kode ICD
        document.getElementById('inputKode').readOnly = false;
        document.getElementById('inputKode').style.background = '#ffffff';
        document.getElementById('kodeHelp').style.display = 'none';

        document.getElementById('modalFormPenyakit').style.display = 'flex';
    }

    function openEditModal(btn) {
        document.getElementById('modalFormTitle').textContent = 'Edit Data Penyakit';
        document.getElementById('formAction').value = 'update';
        
        document.getElementById('inputKode').value = btn.getAttribute('data-kode');
        document.getElementById('inputNama').value = btn.getAttribute('data-nama');
        document.getElementById('inputKategori').value = btn.getAttribute('data-kategori');

        // Kunci input kode ICD
        document.getElementById('inputKode').readOnly = true;
        document.getElementById('inputKode').style.background = '#f8fafc';
        document.getElementById('kodeHelp').style.display = 'block';

        document.getElementById('modalFormPenyakit').style.display = 'flex';
    }

    function openDeleteModal(kode) {
        document.getElementById('delKodeIcd').value = kode;
        document.getElementById('modalDelete').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
