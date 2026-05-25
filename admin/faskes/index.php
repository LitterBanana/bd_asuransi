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
            $kode_faskes = trim($_POST['kode_faskes'] ?? '');
            $nama_faskes = trim($_POST['nama_faskes'] ?? '');
            $tingkat_faskes = $_POST['tingkat_faskes'] ?? '';
            $kota = trim($_POST['kota'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            $status_kerjasama = $_POST['status_kerjasama'] ?? 'Aktif';

            try {
                $stmt = $conn->prepare("INSERT INTO faskes (kode_faskes, nama_faskes, tingkat_faskes, kota, alamat, status_kerjasama) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$kode_faskes, $nama_faskes, $tingkat_faskes, $kota, $alamat, $status_kerjasama]);
                $_SESSION['toast_success'] = 'Fasilitas kesehatan berhasil ditambahkan.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Kode Faskes sudah digunakan. Silakan gunakan kode lain.';
                } else {
                    error_log("Error create faskes: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat menyimpan data.';
                }
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

        } elseif ($action === 'update') {
            $id_faskes = $_POST['id_faskes'] ?? '';
            $kode_faskes = trim($_POST['kode_faskes'] ?? '');
            $nama_faskes = trim($_POST['nama_faskes'] ?? '');
            $tingkat_faskes = $_POST['tingkat_faskes'] ?? '';
            $kota = trim($_POST['kota'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            $status_kerjasama = $_POST['status_kerjasama'] ?? 'Aktif';

            try {
                $stmt = $conn->prepare("UPDATE faskes SET kode_faskes=?, nama_faskes=?, tingkat_faskes=?, kota=?, alamat=?, status_kerjasama=? WHERE id_faskes=?");
                $stmt->execute([$kode_faskes, $nama_faskes, $tingkat_faskes, $kota, $alamat, $status_kerjasama, $id_faskes]);
                $_SESSION['toast_success'] = 'Data fasilitas kesehatan berhasil diperbarui.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Kode Faskes sudah digunakan oleh faskes lain.';
                } else {
                    error_log("Error update faskes: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat memperbarui data.';
                }
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

        } elseif ($action === 'delete') {
            $id_faskes = $_POST['id_faskes'] ?? '';

            try {
                $stmt = $conn->prepare("DELETE FROM faskes WHERE id_faskes = ?");
                $stmt->execute([$id_faskes]);
                $_SESSION['toast_success'] = 'Fasilitas kesehatan berhasil dihapus dari sistem.';
            } catch (PDOException $e) {
                // Constraint violation
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Gagal dihapus! Faskes ini sudah terikat dengan riwayat Klaim Medis. Ubah statusnya menjadi Putus Kontrak.';
                } else {
                    error_log("Error delete faskes: " . $e->getMessage());
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
                        
                        $tingkat_valid = ['Klinik Pratama', 'Klinik Utama', 'RS Tipe C', 'RS Tipe B', 'RS Tipe A'];
                        $status_valid = ['Aktif', 'Putus Kontrak'];
                        
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $row_count++;
                            // Format: KodeFaskes(0), NamaFaskes(1), TingkatFaskes(2), Kota(3), Alamat(4), StatusKerjasama(5)
                            if (count($data) < 6) continue;

                            $kode_faskes = trim($data[0]);
                            $nama_faskes = trim($data[1]);
                            $tingkat_faskes = trim($data[2]);
                            $kota = trim($data[3]);
                            $alamat = trim($data[4]);
                            $status_kerjasama = trim($data[5]);

                            if (empty($kode_faskes) || empty($nama_faskes)) continue;

                            // Validasi tingkat faskes
                            if (!in_array($tingkat_faskes, $tingkat_valid)) {
                                throw new Exception("Error baris $row_count: Tingkat Faskes '$tingkat_faskes' tidak valid. Gunakan: Klinik Pratama, Klinik Utama, RS Tipe C, RS Tipe B, atau RS Tipe A.");
                            }

                            // Validasi status kerjasama
                            if (!in_array($status_kerjasama, $status_valid)) {
                                throw new Exception("Error baris $row_count: Status Kerjasama '$status_kerjasama' tidak valid. Gunakan: Aktif atau Putus Kontrak.");
                            }

                            // Cek duplikasi kode_faskes
                            $stmt_cek = $conn->prepare("SELECT kode_faskes FROM faskes WHERE kode_faskes = ?");
                            $stmt_cek->execute([$kode_faskes]);
                            if ($stmt_cek->rowCount() > 0) throw new Exception("Duplikat pada baris $row_count: Kode Faskes '$kode_faskes' sudah ada.");

                            // Insert
                            $stmt = $conn->prepare("INSERT INTO faskes (kode_faskes, nama_faskes, tingkat_faskes, kota, alamat, status_kerjasama) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$kode_faskes, $nama_faskes, $tingkat_faskes, $kota, $alamat, $status_kerjasama]);
                            $sukses++;
                        }
                        $conn->commit();
                        $_SESSION['toast_success'] = "Import Berhasil! $sukses Fasilitas Kesehatan baru telah ditambahkan.";

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
    $tingkat = $_GET['tingkat'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $whereClause .= " AND (kode_faskes LIKE ? OR nama_faskes LIKE ? OR kota LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($tingkat !== '') {
        $whereClause .= " AND tingkat_faskes = ?";
        $params[] = $tingkat;
    }

    // Ambil semua data faskes
    $query = "SELECT * FROM faskes $whereClause ORDER BY nama_faskes ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $faskes_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Manajemen Fasilitas Kesehatan</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola daftar rumah sakit dan klinik rekanan asuransi.</p>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" placeholder="Cari Kode/Nama/Kota..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
            </div>
            <select name="tingkat" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
                <option value="">Semua Tingkat</option>
                <option value="Klinik Pratama" <?php echo $tingkat === 'Klinik Pratama' ? 'selected' : ''; ?>>Klinik Pratama</option>
                <option value="Klinik Utama" <?php echo $tingkat === 'Klinik Utama' ? 'selected' : ''; ?>>Klinik Utama</option>
                <option value="RS Tipe C" <?php echo $tingkat === 'RS Tipe C' ? 'selected' : ''; ?>>RS Tipe C</option>
                <option value="RS Tipe B" <?php echo $tingkat === 'RS Tipe B' ? 'selected' : ''; ?>>RS Tipe B</option>
                <option value="RS Tipe A" <?php echo $tingkat === 'RS Tipe A' ? 'selected' : ''; ?>>RS Tipe A</option>
            </select>
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">Cari</button>
            <?php if ($search || $tingkat): ?>
                <a href="index.php" class="btn-admin btn-admin-ghost" style="padding: 8px 16px;">Reset</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; gap: 10px; height: 100%; align-items: center;">
            <button onclick="openImportModal()" class="btn-admin btn-admin-ghost" style="height: 100%;">
                <i class="fa-solid fa-file-csv" style="color: #10b981;"></i> Import CSV
            </button>
            <button onclick="openCreateModal()" class="btn-admin btn-admin-primary" style="padding: 10px 20px;">
                <i class="fa-solid fa-plus"></i> Tambah Faskes Baru
            </button>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Fasilitas Kesehatan Rekanan</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Faskes</th>
                    <th>Tingkat</th>
                    <th>Lokasi</th>
                    <th>Status Kerjasama</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($faskes_list) > 0): ?>
                    <?php foreach ($faskes_list as $row): ?>
                    <tr>
                        <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['kode_faskes']); ?></td>
                        <td style="font-weight: 500; color: #1e293b;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 30px; height: 30px; border-radius: 6px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                    <?php if(strpos($row['tingkat_faskes'], 'Klinik') !== false): ?>
                                        <i class="fa-solid fa-stethoscope"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-hospital"></i>
                                    <?php endif; ?>
                                </div>
                                <?php echo htmlspecialchars($row['nama_faskes']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['tingkat_faskes']); ?></td>
                        <td>
                            <div style="color: #1e293b;"><?php echo htmlspecialchars($row['kota']); ?></div>
                            <div style="font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;" title="<?php echo htmlspecialchars($row['alamat']); ?>">
                                <?php echo htmlspecialchars($row['alamat']); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($row['status_kerjasama'] === 'Aktif'): ?>
                                <span class="badge badge-success badge-dot">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger badge-dot">Putus Kontrak</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <button onclick="openEditModal(this)" 
                                data-id="<?php echo htmlspecialchars($row['id_faskes']); ?>"
                                data-kode="<?php echo htmlspecialchars($row['kode_faskes']); ?>"
                                data-nama="<?php echo htmlspecialchars($row['nama_faskes']); ?>"
                                data-tingkat="<?php echo htmlspecialchars($row['tingkat_faskes']); ?>"
                                data-kota="<?php echo htmlspecialchars($row['kota']); ?>"
                                data-alamat="<?php echo htmlspecialchars($row['alamat']); ?>"
                                data-status="<?php echo htmlspecialchars($row['status_kerjasama']); ?>"
                                class="btn-admin btn-admin-sm btn-admin-ghost-primary" title="Edit Faskes">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button onclick="openDeleteModal('<?php echo htmlspecialchars($row['id_faskes']); ?>')" 
                                class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Hapus Faskes">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada fasilitas kesehatan yang terdaftar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL FORM FASKES (CREATE & UPDATE) -->
<div id="modalFormFaskes" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 500px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalFormTitle">Tambah Faskes Baru</h3>
            <button onclick="closeModal('modalFormFaskes')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="overflow-y: auto; padding: 20px;">
            <form id="formFaskes" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_faskes" id="inputId" value="">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Kode Faskes *</label>
                        <input type="text" name="kode_faskes" id="inputKode" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="RS-001">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Status Kerjasama *</label>
                        <select name="status_kerjasama" id="inputStatus" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="Aktif">Aktif</option>
                            <option value="Putus Kontrak">Putus Kontrak</option>
                        </select>
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Fasilitas Kesehatan *</label>
                    <input type="text" name="nama_faskes" id="inputNama" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Siloam Hospitals">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tingkat Faskes *</label>
                        <select name="tingkat_faskes" id="inputTingkat" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="">-- Pilih --</option>
                            <option value="Klinik Pratama">Klinik Pratama</option>
                            <option value="Klinik Utama">Klinik Utama</option>
                            <option value="RS Tipe C">RS Tipe C</option>
                            <option value="RS Tipe B">RS Tipe B</option>
                            <option value="RS Tipe A">RS Tipe A</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Kota *</label>
                        <input type="text" name="kota" id="inputKota" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Jakarta">
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Alamat Lengkap *</label>
                    <textarea name="alamat" id="inputAlamat" required rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;" placeholder="Jl. Sudirman No..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalFormFaskes')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
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
            <h3 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Hapus Faskes</h3>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <p>Apakah Anda yakin ingin menghapus fasilitas kesehatan ini? Jika faskes ini memiliki riwayat klaim, sistem akan memblokir penghapusan.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_faskes" id="delIdFaskes" value="">
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal('modalDelete')" class="btn-admin btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-danger">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT CSV FASKES -->
<div id="modalImportFaskes" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 550px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;"><i class="fa-solid fa-file-import" style="color: #10b981; margin-right: 8px;"></i> Import Data Faskes (CSV)</h3>
            <button onclick="closeModal('modalImportFaskes')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            
            <div style="background: #fffbeb; padding: 15px; border-radius: 6px; border: 1px solid #fde68a; margin-bottom: 20px;">
                <h4 style="font-size: 13px; color: #b45309; margin: 0 0 5px 0;">Instruksi Format CSV:</h4>
                <p style="font-size: 12px; color: #92400e; margin: 0 0 10px 0;">Sistem akan melewati (mengabaikan) baris pertama karena dianggap sebagai Judul Header. Pastikan urutan kolom di file CSV Anda <b>wajib</b> seperti ini:</p>
                <div style="background: #fff; border: 1px dashed #d97706; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 11px; color: #451a03; overflow-x: auto;">
                    Kode Faskes, Nama Faskes, Tingkat, Kota, Alamat, Status Kerjasama<br>
                    RS-010, RS Siloam Kebon Jeruk, RS Tipe B, Jakarta, Jl. Perjuangan No.8, Aktif<br>
                    KL-005, Klinik Sehat Sentosa, Klinik Pratama, Bandung, Jl. Merdeka No.12, Aktif
                </div>
                <p style="font-size: 11px; color: #92400e; margin: 10px 0 0 0;"><b>Tingkat</b>: <code>Klinik Pratama</code>, <code>Klinik Utama</code>, <code>RS Tipe C</code>, <code>RS Tipe B</code>, <code>RS Tipe A</code><br><b>Status</b>: <code>Aktif</code> atau <code>Putus Kontrak</code></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                
                <div class="input-group" style="margin-bottom: 25px; text-align: center; padding: 30px 10px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 32px; color: #94a3b8; margin-bottom: 10px;"></i><br>
                    <input type="file" name="file_csv" accept=".csv" required style="font-size: 13px; color: #475569;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalImportFaskes')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-success">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openImportModal() { document.getElementById('modalImportFaskes').style.display = 'flex'; }

    function openCreateModal() {
        document.getElementById('modalFormTitle').textContent = 'Tambah Faskes Baru';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formFaskes').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalFormFaskes').style.display = 'flex';
    }

    function openEditModal(btn) {
        document.getElementById('modalFormTitle').textContent = 'Edit Data Faskes';
        document.getElementById('formAction').value = 'update';
        
        document.getElementById('inputId').value = btn.getAttribute('data-id');
        document.getElementById('inputKode').value = btn.getAttribute('data-kode');
        document.getElementById('inputNama').value = btn.getAttribute('data-nama');
        document.getElementById('inputTingkat').value = btn.getAttribute('data-tingkat');
        document.getElementById('inputKota').value = btn.getAttribute('data-kota');
        document.getElementById('inputAlamat').value = btn.getAttribute('data-alamat');
        document.getElementById('inputStatus').value = btn.getAttribute('data-status');

        document.getElementById('modalFormFaskes').style.display = 'flex';
    }

    function openDeleteModal(id) {
        document.getElementById('delIdFaskes').value = id;
        document.getElementById('modalDelete').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
