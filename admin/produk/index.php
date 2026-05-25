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
            $kode_produk = trim($_POST['kode_produk'] ?? '');
            $nama_produk = trim($_POST['nama_produk'] ?? '');
            $jenis_kategori = $_POST['jenis_kategori'] ?? '';
            $limit_tahunan = $_POST['limit_tahunan'] ?? 0;
            $premi_dasar = $_POST['premi_dasar'] ?? 0;

            try {
                $stmt = $conn->prepare("INSERT INTO produk_asuransi (kode_produk, nama_produk, jenis_kategori, limit_tahunan, premi_dasar) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$kode_produk, $nama_produk, $jenis_kategori, $limit_tahunan, $premi_dasar]);
                $_SESSION['toast_success'] = 'Produk asuransi berhasil ditambahkan.';
            } catch (PDOException $e) {
                // Check for duplicate entry error code (1062)
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Kode Produk sudah digunakan. Silakan gunakan kode lain.';
                } else {
                    error_log("Error create produk: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat menyimpan data.';
                }
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

        } elseif ($action === 'update') {
            $id_produk = $_POST['id_produk'] ?? '';
            $kode_produk = trim($_POST['kode_produk'] ?? '');
            $nama_produk = trim($_POST['nama_produk'] ?? '');
            $jenis_kategori = $_POST['jenis_kategori'] ?? '';
            $limit_tahunan = $_POST['limit_tahunan'] ?? 0;
            $premi_dasar = $_POST['premi_dasar'] ?? 0;

            try {
                $stmt = $conn->prepare("UPDATE produk_asuransi SET kode_produk=?, nama_produk=?, jenis_kategori=?, limit_tahunan=?, premi_dasar=? WHERE id_produk=?");
                $stmt->execute([$kode_produk, $nama_produk, $jenis_kategori, $limit_tahunan, $premi_dasar, $id_produk]);
                $_SESSION['toast_success'] = 'Produk asuransi berhasil diperbarui.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Kode Produk sudah digunakan oleh produk lain.';
                } else {
                    error_log("Error update produk: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat memperbarui data.';
                }
            }
            echo "<script>window.location.href='index.php';</script>";
            exit;

        } elseif ($action === 'delete') {
            $id_produk = $_POST['id_produk'] ?? '';

            try {
                $stmt = $conn->prepare("DELETE FROM produk_asuransi WHERE id_produk = ?");
                $stmt->execute([$id_produk]);
                $_SESSION['toast_success'] = 'Produk asuransi berhasil dihapus.';
            } catch (PDOException $e) {
                // Check for foreign key constraint violation (1451)
                if ($e->getCode() == 23000) {
                    $_SESSION['toast_error'] = 'Gagal dihapus! Produk ini sedang digunakan oleh Polis aktif atau riwayat tagihan.';
                } else {
                    error_log("Error delete produk: " . $e->getMessage());
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
                        
                        $kategori_valid = ['Individu', 'Keluarga', 'Kumpulan/Perusahaan'];
                        
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $row_count++;
                            // Format: KodeProduk(0), NamaProduk(1), JenisKategori(2), LimitTahunan(3), PremiDasar(4)
                            if (count($data) < 5) continue;

                            $kode_produk = trim($data[0]);
                            $nama_produk = trim($data[1]);
                            $jenis_kategori = trim($data[2]);
                            $limit_tahunan = floatval($data[3]);
                            $premi_dasar = floatval($data[4]);

                            if (empty($kode_produk) || empty($nama_produk)) continue;

                            // Validasi kategori
                            if (!in_array($jenis_kategori, $kategori_valid)) {
                                throw new Exception("Error baris $row_count: Jenis Kategori '$jenis_kategori' tidak valid. Gunakan: Individu, Keluarga, atau Kumpulan/Perusahaan.");
                            }

                            // Cek duplikasi kode_produk
                            $stmt_cek = $conn->prepare("SELECT kode_produk FROM produk_asuransi WHERE kode_produk = ?");
                            $stmt_cek->execute([$kode_produk]);
                            if ($stmt_cek->rowCount() > 0) throw new Exception("Duplikat pada baris $row_count: Kode Produk '$kode_produk' sudah ada.");

                            // Insert
                            $stmt = $conn->prepare("INSERT INTO produk_asuransi (kode_produk, nama_produk, jenis_kategori, limit_tahunan, premi_dasar) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$kode_produk, $nama_produk, $jenis_kategori, $limit_tahunan, $premi_dasar]);
                            $sukses++;
                        }
                        $conn->commit();
                        $_SESSION['toast_success'] = "Import Berhasil! $sukses Produk Asuransi baru telah ditambahkan.";

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
        $whereClause .= " AND (kode_produk LIKE ? OR nama_produk LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($kategori !== '') {
        $whereClause .= " AND jenis_kategori = ?";
        $params[] = $kategori;
    }

    // Ambil semua data produk
    $query = "SELECT * FROM produk_asuransi $whereClause ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Manajemen Produk Asuransi</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Kelola daftar plan asuransi, kategori, dan premi dasar.</p>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" placeholder="Cari Kode/Nama Plan..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
            </div>
            <select name="kategori" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
                <option value="">Semua Kategori</option>
                <option value="Individu" <?php echo $kategori === 'Individu' ? 'selected' : ''; ?>>Individu</option>
                <option value="Keluarga" <?php echo $kategori === 'Keluarga' ? 'selected' : ''; ?>>Keluarga</option>
                <option value="Kumpulan/Perusahaan" <?php echo $kategori === 'Kumpulan/Perusahaan' ? 'selected' : ''; ?>>Kumpulan/Perusahaan</option>
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
                <i class="fa-solid fa-plus"></i> Tambah Plan Baru
            </button>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Produk / Plan</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Limit Tahunan</th>
                    <th>Premi Dasar / Bln</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($produk_list) > 0): ?>
                    <?php foreach ($produk_list as $row): ?>
                    <tr>
                        <td style="font-weight: 600; color: #3b82f6;"><?php echo htmlspecialchars($row['kode_produk']); ?></td>
                        <td style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                        <td>
                            <?php 
                                $cat = $row['jenis_kategori'];
                                $badge = 'badge-neutral';
                                if ($cat == 'Individu') $badge = 'badge-info';
                                else if ($cat == 'Keluarga') $badge = 'badge-success';
                                else if ($cat == 'Kumpulan/Perusahaan') $badge = 'badge-primary';
                            ?>
                            <span class="badge <?php echo $badge; ?>" style="font-size: 11px;">
                                <?php echo htmlspecialchars($cat); ?>
                            </span>
                        </td>
                        <td style="color: #64748b; font-weight: 500;">Rp <?php echo number_format($row['limit_tahunan'], 0, ',', '.'); ?></td>
                        <td style="color: #f59e0b; font-weight: 600;">Rp <?php echo number_format($row['premi_dasar'], 0, ',', '.'); ?></td>
                        <td style="text-align: right;">
                            <button onclick="openEditModal(this)" 
                                data-id="<?php echo htmlspecialchars($row['id_produk']); ?>"
                                data-kode="<?php echo htmlspecialchars($row['kode_produk']); ?>"
                                data-nama="<?php echo htmlspecialchars($row['nama_produk']); ?>"
                                data-kategori="<?php echo htmlspecialchars($row['jenis_kategori']); ?>"
                                data-limit="<?php echo htmlspecialchars($row['limit_tahunan']); ?>"
                                data-premi="<?php echo htmlspecialchars($row['premi_dasar']); ?>"
                                class="btn-admin btn-admin-sm btn-admin-ghost-primary" title="Edit Plan">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button onclick="openDeleteModal('<?php echo htmlspecialchars($row['id_produk']); ?>')" 
                                class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Hapus Plan">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">Belum ada produk asuransi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL FORM PRODUK (CREATE & UPDATE) -->
<div id="modalFormProduk" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 500px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalFormTitle">Tambah Produk Baru</h3>
            <button onclick="closeModal('modalFormProduk')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="overflow-y: auto; padding: 20px;">
            <form id="formProduk" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_produk" id="inputId" value="">

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Kode Produk *</label>
                    <input type="text" name="kode_produk" id="inputKode" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Misal: IND-001">
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Produk *</label>
                    <input type="text" name="nama_produk" id="inputNama" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Misal: Asuransi Kesehatan Individu Plus">
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Jenis Kategori *</label>
                    <select name="jenis_kategori" id="inputKategori" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Individu">Individu</option>
                        <option value="Keluarga">Keluarga</option>
                        <option value="Kumpulan/Perusahaan">Kumpulan/Perusahaan</option>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Limit Tahunan (Rp) *</label>
                    <input type="number" name="limit_tahunan" id="inputLimit" required min="0" step="1000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="0">
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Premi Dasar / Bulan (Rp) *</label>
                    <input type="number" name="premi_dasar" id="inputPremi" required min="0" step="1000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="0">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalFormProduk')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
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
            <h3 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Hapus Produk</h3>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <p>Apakah Anda yakin ingin menghapus produk asuransi ini? Tindakan ini tidak dapat diurungkan.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_produk" id="delIdProduk" value="">
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal('modalDelete')" class="btn-admin btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-danger">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT CSV PRODUK -->
<div id="modalImportProduk" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 500px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;"><i class="fa-solid fa-file-import" style="color: #10b981; margin-right: 8px;"></i> Import Data Produk (CSV)</h3>
            <button onclick="closeModal('modalImportProduk')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            
            <div style="background: #fffbeb; padding: 15px; border-radius: 6px; border: 1px solid #fde68a; margin-bottom: 20px;">
                <h4 style="font-size: 13px; color: #b45309; margin: 0 0 5px 0;">Instruksi Format CSV:</h4>
                <p style="font-size: 12px; color: #92400e; margin: 0 0 10px 0;">Sistem akan melewati (mengabaikan) baris pertama karena dianggap sebagai Judul Header. Pastikan urutan kolom di file CSV Anda <b>wajib</b> seperti ini:</p>
                <div style="background: #fff; border: 1px dashed #d97706; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 11px; color: #451a03; overflow-x: auto;">
                    Kode Produk, Nama Produk, Jenis Kategori, Limit Tahunan, Premi Dasar<br>
                    IND-001, Kesehatan Individu Basic, Individu, 50000000, 250000<br>
                    KEL-001, Kesehatan Keluarga Silver, Keluarga, 100000000, 500000
                </div>
                <p style="font-size: 11px; color: #92400e; margin: 10px 0 0 0;"><b>Jenis Kategori</b> yang valid: <code>Individu</code>, <code>Keluarga</code>, <code>Kumpulan/Perusahaan</code></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                
                <div class="input-group" style="margin-bottom: 25px; text-align: center; padding: 30px 10px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 32px; color: #94a3b8; margin-bottom: 10px;"></i><br>
                    <input type="file" name="file_csv" accept=".csv" required style="font-size: 13px; color: #475569;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalImportProduk')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-success">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openImportModal() { document.getElementById('modalImportProduk').style.display = 'flex'; }

    function openCreateModal() {
        document.getElementById('modalFormTitle').textContent = 'Tambah Produk Baru';
        document.getElementById('formAction').value = 'create';
        document.getElementById('formProduk').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalFormProduk').style.display = 'flex';
    }

    function openEditModal(btn) {
        document.getElementById('modalFormTitle').textContent = 'Edit Produk';
        document.getElementById('formAction').value = 'update';
        
        document.getElementById('inputId').value = btn.getAttribute('data-id');
        document.getElementById('inputKode').value = btn.getAttribute('data-kode');
        document.getElementById('inputNama').value = btn.getAttribute('data-nama');
        document.getElementById('inputKategori').value = btn.getAttribute('data-kategori');
        document.getElementById('inputLimit').value = btn.getAttribute('data-limit');
        document.getElementById('inputPremi').value = btn.getAttribute('data-premi');

        document.getElementById('modalFormProduk').style.display = 'flex';
    }

    function openDeleteModal(id) {
        document.getElementById('delIdProduk').value = id;
        document.getElementById('modalDelete').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
