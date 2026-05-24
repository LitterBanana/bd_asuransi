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
            $conn->beginTransaction();
            $stmtInsert = $conn->prepare("INSERT INTO pemegang_polis (id_agen, nik, nama_lengkap, tanggal_lahir, jenis_kelamin, pekerjaan, alamat, no_telepon, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$id_agen, $nik, $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $pekerjaan, $alamat, $no_telepon, $email]);
            $new_id_pemegang = $conn->lastInsertId();

            if (!empty($_POST['id_produk'])) {
                $id_produk = $_POST['id_produk'];
                $no_polis = 'POL-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $tgl_terbit = date('Y-m-d');
                $tgl_jatuh_tempo = date('Y-m-d', strtotime('+1 year'));

                $stmtPolis = $conn->prepare("INSERT INTO polis (no_polis, id_pemegang, id_produk, id_agen, tanggal_terbit, tanggal_jatuh_tempo, status_polis) VALUES (?, ?, ?, ?, ?, ?, 'Pending Approval')");
                $stmtPolis->execute([$no_polis, $new_id_pemegang, $id_produk, $id_agen, $tgl_terbit, $tgl_jatuh_tempo]);
                $success_msg = "Nasabah baru dan aplikasi Polis berhasil didaftarkan! Menunggu verifikasi admin (Pending Approval).";
            } else {
                $success_msg = "Nasabah baru berhasil didaftarkan ke dalam sistem sebagai prospek.";
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error_msg = "Gagal menambahkan data: " . $e->getMessage();
        }
    }

    $stmt = $conn->query("SELECT * FROM produk_asuransi ORDER BY nama_produk ASC");
    $produk_list = $stmt->fetchAll();
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Katalog Produk Asuransi</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Daftar produk asuransi aktif yang dapat Anda tawarkan kepada calon klien.</p>
    </div>
    <button onclick="openCreateModal()" class="btn" style="background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; display: flex; align-items: center; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);">
        <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i> Daftar Nasabah Baru
    </button>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success" style="padding: 15px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="alert alert-danger" style="padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<?php if (count($produk_list) > 0): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach ($produk_list as $row): ?>
            <div class="admin-card" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="admin-card-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; color: #0f172a; font-size: 18px;"><?php echo htmlspecialchars($row['nama_produk']); ?></h3>
                        <span class="badge badge-success" style="font-size: 11px;">Aktif</span>
                    </div>
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>
                <div class="admin-card-body" style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                    <div style="margin-bottom: 20px; flex-grow: 1;">
                        <p style="margin: 0 0 15px 0; color: #475569; font-size: 14px; line-height: 1.5;">
                            Kategori Produk: <strong><?php echo htmlspecialchars($row['jenis_kategori']); ?></strong>
                        </p>
                        
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 10px; font-size: 14px;"></i>
                            <span style="font-size: 14px; color: #1e293b; font-weight: 500;">Limit: Rp <?php echo number_format($row['limit_tahunan'], 0, ',', '.'); ?>/tahun</span>
                        </div>
                    </div>
                    
                    <div style="padding-top: 15px; border-top: 1px dashed #cbd5e1; display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Premi Dasar</div>
                                <div style="font-size: 20px; font-weight: 700; color: #3b82f6;">Rp <?php echo number_format($row['premi_dasar'], 0, ',', '.'); ?> <span style="font-size: 12px; color: #64748b; font-weight: normal;">/bln</span></div>
                            </div>
                        </div>
                        <button onclick="openCreateModal(<?php echo $row['id_produk']; ?>)" class="btn" style="background: #3b82f6; color: white; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; width: 100%; display: flex; justify-content: center; align-items: center;">
                            <i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i> Daftarkan Nasabah
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state" style="padding: 40px 20px; text-align: center; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 40px; margin-bottom: 15px; color: #94a3b8;"><i class="fa-solid fa-box-open"></i></div>
        <h3 style="margin: 0 0 10px 0; color: #1e293b;">Katalog Kosong</h3>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Belum ada produk asuransi yang aktif saat ini.</p>
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
                <input type="hidden" name="id_produk" id="hidden_id_produk" value="">

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
    function openCreateModal(idProduk = '') {
        document.getElementById('hidden_id_produk').value = idProduk;
        document.getElementById('modalCreateNasabah').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/agen/footer.php"; ?>
