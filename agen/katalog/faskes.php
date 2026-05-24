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

    // Handle Claim Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_klaim') {
        $id_faskes = $_POST['id_faskes'];
        $no_polis = $_POST['no_polis'];
        $kode_icd = $_POST['kode_icd'];
        $tanggal_masuk = $_POST['tanggal_masuk'];
        $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? $_POST['tanggal_keluar'] : null;
        $jenis_perawatan = $_POST['jenis_perawatan'];
        $total_tagihan = $_POST['total_tagihan_faskes'];

        $no_klaim = 'KLM-' . date('Ym') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        try {
            $stmtKlaim = $conn->prepare("INSERT INTO klaim_medis (no_klaim, no_polis, id_faskes, kode_icd, tanggal_masuk, tanggal_keluar, jenis_perawatan, total_tagihan_faskes, status_klaim) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmtKlaim->execute([$no_klaim, $no_polis, $id_faskes, $kode_icd, $tanggal_masuk, $tanggal_keluar, $jenis_perawatan, $total_tagihan]);
            $success_msg = "Pengajuan klaim berhasil dikirim! Status klaim saat ini adalah Pending.";
        } catch (Exception $e) {
            $error_msg = "Gagal mengajukan klaim: " . $e->getMessage();
        }
    }

    // Fetch Faskes
    $stmt = $conn->query("SELECT * FROM faskes WHERE status_kerjasama = 'Aktif' ORDER BY tingkat_faskes ASC, nama_faskes ASC");
    $faskes_list = $stmt->fetchAll();

    // Fetch Agent's Nasabah (who have active policies)
    $stmtPolis = $conn->prepare("
        SELECT p.no_polis, pp.nama_lengkap, prd.nama_produk 
        FROM polis p 
        JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang 
        JOIN produk_asuransi prd ON p.id_produk = prd.id_produk
        WHERE p.id_agen = ? AND p.status_polis = 'Inforce'
        ORDER BY pp.nama_lengkap ASC
    ");
    $stmtPolis->execute([$id_agen]);
    $polis_list = $stmtPolis->fetchAll();

    // Fetch ICD-10 Diseases
    $stmtIcd = $conn->query("SELECT kode_icd, nama_penyakit FROM kategori_penyakit ORDER BY nama_penyakit ASC");
    $icd_list = $stmtIcd->fetchAll();
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Fasilitas Kesehatan Rekanan</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Daftar Rumah Sakit dan Klinik yang bekerjasama dengan perusahaan asuransi.</p>
    </div>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success" style="padding: 15px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="alert alert-danger" style="padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<?php if (count($faskes_list) > 0): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        <?php foreach ($faskes_list as $row): 
            $icon = 'fa-hospital';
            $icon_color = '#3b82f6';
            $bg_color = '#eff6ff';
            
            if ($row['tingkat_faskes'] === 'Klinik Pratama' || $row['tingkat_faskes'] === 'Klinik Utama') {
                $icon = 'fa-house-medical';
                $icon_color = '#10b981';
                $bg_color = '#ecfdf5';
            }
        ?>
            <div class="admin-card" style="display: flex; flex-direction: column; height: 100%; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div class="admin-card-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; padding: 20px;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 45px; height: 45px; border-radius: 12px; background: <?php echo $bg_color; ?>; color: <?php echo $icon_color; ?>; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 20px; flex-shrink: 0;">
                            <i class="fa-solid <?php echo $icon; ?>"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.3;"><?php echo htmlspecialchars($row['nama_faskes']); ?></h3>
                            <span class="badge badge-neutral" style="font-size: 11px;"><?php echo htmlspecialchars($row['tingkat_faskes']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="admin-card-body" style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; margin-bottom: 10px; color: #475569; font-size: 13px;">
                            <i class="fa-solid fa-location-dot" style="color: #94a3b8; margin-top: 3px; margin-right: 10px; width: 14px; text-align: center;"></i>
                            <span style="line-height: 1.5;"><?php echo htmlspecialchars($row['alamat']); ?>, <?php echo htmlspecialchars($row['kota']); ?></span>
                        </div>
                    </div>
                    
                    <div style="padding-top: 15px; border-top: 1px dashed #cbd5e1; display: flex; justify-content: center;">
                        <button onclick="openKlaimModal('<?php echo $row['id_faskes']; ?>', '<?php echo addslashes(htmlspecialchars($row['nama_faskes'])); ?>')" class="btn" style="background: #10b981; color: white; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; width: 100%; display: flex; justify-content: center; align-items: center;">
                            <i class="fa-solid fa-file-medical" style="margin-right: 8px;"></i> Ajukan Klaim
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state" style="padding: 40px 20px; text-align: center; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="font-size: 40px; margin-bottom: 15px; color: #94a3b8;"><i class="fa-solid fa-hospital-user"></i></div>
        <h3 style="margin: 0 0 10px 0; color: #1e293b;">Belum Ada Faskes</h3>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Belum ada daftar fasilitas kesehatan rekanan yang aktif.</p>
    </div>
<?php endif; ?>

<!-- MODAL FORM AJUKAN KLAIM -->
<div id="modalAjukanKlaim" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; background: #ecfdf5; border-bottom: 1px solid #d1fae5;">
            <h3 style="color: #065f46;"><i class="fa-solid fa-file-medical" style="margin-right: 8px;"></i> Form Pengajuan Klaim</h3>
            <button onclick="closeModal('modalAjukanKlaim')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #065f46;">&times;</button>
        </div>
        <div class="admin-card-body" style="overflow-y: auto; padding: 25px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="submit_klaim">
                <input type="hidden" name="id_faskes" id="hidden_id_faskes" value="">

                <div style="margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Fasilitas Kesehatan</div>
                    <div id="display_nama_faskes" style="font-weight: 600; color: #0f172a; font-size: 15px;">-</div>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Pilih Nasabah & Polis (Inforce) *</label>
                    <select name="no_polis" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Nasabah --</option>
                        <?php foreach($polis_list as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['no_polis']); ?>">
                                <?php echo htmlspecialchars($p['nama_lengkap']); ?> - <?php echo htmlspecialchars($p['nama_produk']); ?> (<?php echo htmlspecialchars($p['no_polis']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Diagnosa / Penyakit (ICD-10) *</label>
                    <select name="kode_icd" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Penyakit --</option>
                        <?php foreach($icd_list as $icd): ?>
                            <option value="<?php echo htmlspecialchars($icd['kode_icd']); ?>">
                                <?php echo htmlspecialchars($icd['kode_icd']); ?> - <?php echo htmlspecialchars($icd['nama_penyakit']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Jenis Perawatan *</label>
                        <select name="jenis_perawatan" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="Rawat Jalan">Rawat Jalan</option>
                            <option value="Rawat Inap">Rawat Inap</option>
                            <option value="Pembedahan">Pembedahan</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Total Tagihan Faskes (Rp) *</label>
                        <input type="number" name="total_tagihan_faskes" required min="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tanggal Masuk *</label>
                        <input type="date" name="tanggal_masuk" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tanggal Keluar</label>
                        <input type="date" name="tanggal_keluar" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalAjukanKlaim')" class="btn btn-ghost" style="padding: 10px 20px;">Batal</button>
                    <button type="submit" class="btn" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500;">
                        Kirim Pengajuan Klaim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openKlaimModal(idFaskes, namaFaskes) {
        document.getElementById('hidden_id_faskes').value = idFaskes;
        document.getElementById('display_nama_faskes').innerText = namaFaskes;
        document.getElementById('modalAjukanKlaim').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/agen/footer.php"; ?>
