<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";

    /** @var PDO $conn */
    $id_user = $_SESSION['id_user'];

    // Ambil data pemegang polis untuk user ini
    $stmt_pp = $conn->prepare("SELECT p.id_pemegang, p.nama_lengkap FROM users u JOIN pemegang_polis p ON u.id_pemegang = p.id_pemegang WHERE u.id_user = ?");
    $stmt_pp->execute([$id_user]);
    $pemegang = $stmt_pp->fetch();
    $nama_pemegang = $pemegang ? $pemegang['nama_lengkap'] : 'Pemegang Polis';

    // ==========================================
    // BACKEND LOGIC: POST HANDLER
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'create_klaim') {
            $no_polis = $_POST['no_polis'] ?? '';
            $id_tanggungan = !empty($_POST['id_tanggungan']) ? $_POST['id_tanggungan'] : NULL;
            $id_faskes = $_POST['id_faskes'] ?? '';
            $kode_icd = $_POST['kode_icd'] ?? '';
            $jenis_perawatan = $_POST['jenis_perawatan'] ?? '';
            $tanggal_masuk = $_POST['tanggal_masuk'] ?? '';
            $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? $_POST['tanggal_keluar'] : NULL;
            $total_tagihan = $_POST['total_tagihan_faskes'] ?? 0;

            // Generate No Klaim
            $prefix = 'KLM-' . date('Ym') . '-';
            $stmt_last = $conn->query("SELECT no_klaim FROM klaim_medis WHERE no_klaim LIKE '$prefix%' ORDER BY no_klaim DESC LIMIT 1");
            $last_klaim = $stmt_last->fetchColumn();
            if ($last_klaim) {
                $last_num = (int)substr($last_klaim, -3);
                $new_num = str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $new_num = '001';
            }
            $no_klaim = $prefix . $new_num;

            try {
                $stmt_insert = $conn->prepare("
                    INSERT INTO klaim_medis (no_klaim, no_polis, id_tanggungan, id_faskes, kode_icd, tanggal_masuk, tanggal_keluar, jenis_perawatan, status_klaim, total_tagihan_faskes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
                ");
                $stmt_insert->execute([$no_klaim, $no_polis, $id_tanggungan, $id_faskes, $kode_icd, $tanggal_masuk, $tanggal_keluar, $jenis_perawatan, $total_tagihan]);
                $_SESSION['toast_success'] = 'Klaim baru berhasil diajukan dan menunggu persetujuan.';
            } catch (Exception $e) {
                error_log("Error create klaim: " . $e->getMessage());
                $_SESSION['toast_error'] = 'Terjadi kesalahan saat mengajukan klaim.';
            }
            header("Location: index.php");
            exit;

        } elseif ($action === 'update_klaim') {
            $no_klaim = $_POST['no_klaim'] ?? '';
            // Verifikasi status harus Pending
            $stmt_cek = $conn->prepare("SELECT status_klaim FROM klaim_medis WHERE no_klaim = ?");
            $stmt_cek->execute([$no_klaim]);
            $klaim = $stmt_cek->fetch();

            if ($klaim && $klaim['status_klaim'] === 'Pending') {
                $no_polis = $_POST['no_polis'] ?? '';
                $id_tanggungan = !empty($_POST['id_tanggungan']) ? $_POST['id_tanggungan'] : NULL;
                $id_faskes = $_POST['id_faskes'] ?? '';
                $kode_icd = $_POST['kode_icd'] ?? '';
                $jenis_perawatan = $_POST['jenis_perawatan'] ?? '';
                $tanggal_masuk = $_POST['tanggal_masuk'] ?? '';
                $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? $_POST['tanggal_keluar'] : NULL;
                $total_tagihan = $_POST['total_tagihan_faskes'] ?? 0;

                try {
                    $stmt_update = $conn->prepare("
                        UPDATE klaim_medis 
                        SET no_polis=?, id_tanggungan=?, id_faskes=?, kode_icd=?, tanggal_masuk=?, tanggal_keluar=?, jenis_perawatan=?, total_tagihan_faskes=?
                        WHERE no_klaim=? AND status_klaim='Pending'
                    ");
                    $stmt_update->execute([$no_polis, $id_tanggungan, $id_faskes, $kode_icd, $tanggal_masuk, $tanggal_keluar, $jenis_perawatan, $total_tagihan, $no_klaim]);
                    $_SESSION['toast_success'] = 'Data klaim berhasil diperbarui.';
                } catch (Exception $e) {
                    error_log("Error update klaim: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat memperbarui klaim.';
                }
            } else {
                $_SESSION['toast_error'] = 'Klaim tidak dapat diubah karena sudah diproses oleh Admin.';
            }
            header("Location: index.php");
            exit;

        } elseif ($action === 'delete_klaim') {
            $no_klaim = $_POST['no_klaim'] ?? '';
            $stmt_cek = $conn->prepare("SELECT status_klaim FROM klaim_medis WHERE no_klaim = ?");
            $stmt_cek->execute([$no_klaim]);
            $klaim = $stmt_cek->fetch();

            if ($klaim && $klaim['status_klaim'] === 'Pending') {
                try {
                    $stmt_del = $conn->prepare("DELETE FROM klaim_medis WHERE no_klaim = ? AND status_klaim = 'Pending'");
                    $stmt_del->execute([$no_klaim]);
                    $_SESSION['toast_success'] = 'Pengajuan klaim berhasil dibatalkan/dihapus.';
                } catch (Exception $e) {
                    error_log("Error delete klaim: " . $e->getMessage());
                    $_SESSION['toast_error'] = 'Terjadi kesalahan saat menghapus klaim.';
                }
            } else {
                $_SESSION['toast_error'] = 'Klaim tidak dapat dihapus karena sudah diproses oleh Admin.';
            }
            header("Location: index.php");
            exit;
        }
    }

    // ==========================================
    // DATA UNTUK DROPDOWN MODAL
    // ==========================================
    // 1. Polis User (Inforce)
    $stmt_polis = $conn->prepare("SELECT p.no_polis, prd.nama_produk FROM polis p JOIN produk_asuransi prd ON p.id_produk = prd.id_produk JOIN users u ON p.id_pemegang = u.id_pemegang WHERE u.id_user = ? AND p.status_polis = 'Inforce'");
    $stmt_polis->execute([$id_user]);
    $polis_list = $stmt_polis->fetchAll(PDO::FETCH_ASSOC);

    // 2. Faskes
    $faskes_list = $conn->query("SELECT id_faskes, nama_faskes, tingkat_faskes FROM faskes WHERE status_kerjasama = 'Aktif' ORDER BY nama_faskes")->fetchAll(PDO::FETCH_ASSOC);

    // 3. ICD (Kategori Penyakit)
    $icd_list = $conn->query("SELECT kode_icd, nama_penyakit FROM kategori_penyakit ORDER BY nama_penyakit")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Tanggungan per Polis (Untuk JS Map)
    $stmt_tang = $conn->prepare("
        SELECT t.id_tanggungan, t.no_polis, t.nama_lengkap, t.hubungan 
        FROM tanggungan_polis t
        JOIN polis p ON t.no_polis = p.no_polis
        JOIN users u ON p.id_pemegang = u.id_pemegang
        WHERE u.id_user = ? AND t.status_tanggungan = 'Active'
    ");
    $stmt_tang->execute([$id_user]);
    $tanggungan_data = $stmt_tang->fetchAll(PDO::FETCH_ASSOC);

    $polis_tanggungan_map = [];
    foreach ($polis_list as $pl) {
        $polis_tanggungan_map[$pl['no_polis']] = [
            ['id' => '', 'nama' => $nama_pemegang . ' (Pemegang Polis)']
        ];
    }
    foreach ($tanggungan_data as $td) {
        if (isset($polis_tanggungan_map[$td['no_polis']])) {
            $polis_tanggungan_map[$td['no_polis']][] = [
                'id' => $td['id_tanggungan'],
                'nama' => $td['nama_lengkap'] . ' (' . $td['hubungan'] . ')'
            ];
        }
    }
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Riwayat Klaim Medis</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Pantau status pengajuan dan riwayat klaim asuransi kesehatan Anda.</p>
    </div>
    <button onclick="openCreateModal()" class="btn" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer;">
        <i class="fa-solid fa-plus"></i> Ajukan Klaim Baru
    </button>
</div>

<?php
    $stmt = $conn->prepare("
        SELECT 
            k.no_klaim, k.tanggal_masuk, k.tanggal_keluar, k.jenis_perawatan, k.status_klaim, k.total_tagihan_faskes,
            k.kode_icd, k.id_faskes, k.no_polis, k.id_tanggungan,
            p.no_polis, f.nama_faskes, kp.nama_penyakit,
            COALESCE(t.nama_lengkap, pp.nama_lengkap) as nama_pasien
        FROM klaim_medis k
        JOIN polis p ON k.no_polis = p.no_polis
        JOIN users u ON p.id_pemegang = u.id_pemegang
        JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
        JOIN faskes f ON k.id_faskes = f.id_faskes
        JOIN kategori_penyakit kp ON k.kode_icd = kp.kode_icd
        LEFT JOIN tanggungan_polis t ON k.id_tanggungan = t.id_tanggungan
        WHERE u.id_user = ?
        ORDER BY k.tanggal_masuk DESC
    ");
    $stmt->execute([$id_user]);
    $klaim_list = $stmt->fetchAll();

    if (count($klaim_list) > 0) {
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Klaim</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Pasien & Faskes</th>
                    <th>Detail Perawatan</th>
                    <th>Total Tagihan</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($klaim_list as $row): 
                    $badge_class = 'badge-neutral';
                    if ($row['status_klaim'] === 'Approved') {
                        $badge_class = 'badge-success';
                    } elseif ($row['status_klaim'] === 'Pending') {
                        $badge_class = 'badge-warning';
                    } elseif ($row['status_klaim'] === 'Investigasi') {
                        $badge_class = 'badge-info';
                    } elseif ($row['status_klaim'] === 'Rejected') {
                        $badge_class = 'badge-danger';
                    }
                ?>
                <tr>
                    <td style="font-weight: 600; color: #1e293b;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 35px; height: 35px; border-radius: 8px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fa-solid fa-notes-medical"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($row['nama_pasien']); ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: normal;"><?php echo htmlspecialchars($row['nama_faskes']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="color: #1e293b; font-weight: 500;">Polis: <?php echo htmlspecialchars($row['no_polis']); ?> &bull; <?php echo htmlspecialchars($row['jenis_perawatan']); ?></div>
                        <div style="font-size: 12px; color: #64748b;">Tgl Masuk: <?php echo date('d M Y', strtotime($row['tanggal_masuk'])); ?> &bull; <?php echo htmlspecialchars($row['nama_penyakit']); ?></div>
                    </td>
                    <td style="font-weight: 600;">Rp <?php echo number_format($row['total_tagihan_faskes'], 0, ',', '.'); ?></td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>" style="font-size: 11px;">
                            <?php echo htmlspecialchars($row['status_klaim']); ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($row['status_klaim'] === 'Pending'): ?>
                            <button onclick="openEditModal(this)" 
                                data-noklaim="<?php echo htmlspecialchars($row['no_klaim']); ?>"
                                data-nopolis="<?php echo htmlspecialchars($row['no_polis']); ?>"
                                data-idtanggungan="<?php echo htmlspecialchars($row['id_tanggungan'] ?? ''); ?>"
                                data-idfaskes="<?php echo htmlspecialchars($row['id_faskes']); ?>"
                                data-kodeicd="<?php echo htmlspecialchars($row['kode_icd']); ?>"
                                data-jenisperawatan="<?php echo htmlspecialchars($row['jenis_perawatan']); ?>"
                                data-tglmasuk="<?php echo htmlspecialchars($row['tanggal_masuk']); ?>"
                                data-tglkeluar="<?php echo htmlspecialchars($row['tanggal_keluar'] ?? ''); ?>"
                                data-total="<?php echo htmlspecialchars($row['total_tagihan_faskes']); ?>"
                                class="btn btn-ghost btn-sm" style="color: #f59e0b;" title="Edit Klaim">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('<?php echo htmlspecialchars($row['no_klaim']); ?>')" 
                                class="btn btn-ghost btn-sm" style="color: #ef4444;" title="Hapus Klaim">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-ghost btn-sm" style="color: #94a3b8; cursor: not-allowed;" title="Hanya klaim Pending yang bisa diubah/dihapus">
                                <i class="fa-solid fa-lock"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
    } else {
?>
    <div class="empty-state" style="padding: var(--space-8); text-align: center;">
        <div class="empty-state-icon" style="font-size: 2.5rem; margin-bottom: var(--space-2); color: var(--color-slate);"><i class="fa-solid fa-file-medical"></i></div>
        <h3 class="empty-state-title" style="margin-bottom: var(--space-2);">Belum Ada Riwayat Klaim</h3>
        <p class="empty-state-text" style="color: var(--color-text-secondary);">Anda belum pernah mengajukan klaim medis.</p>
    </div>
<?php
    }
?>

<!-- MODAL FORM KLAIM (CREATE & UPDATE) -->
<div id="modalFormKlaim" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalFormTitle">Ajukan Klaim Baru</h3>
            <button onclick="closeModal('modalFormKlaim')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="overflow-y: auto; padding: 20px;">
            <form id="formKlaim" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="create_klaim">
                <input type="hidden" name="no_klaim" id="inputNoKlaim" value="">

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Pilih Polis *</label>
                    <select name="no_polis" id="inputNoPolis" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="updateTanggunganOptions()">
                        <option value="">-- Pilih Polis Aktif --</option>
                        <?php foreach($polis_list as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['no_polis']); ?>"><?php echo htmlspecialchars($p['no_polis'] . ' - ' . $p['nama_produk']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Pilih Pasien *</label>
                    <select name="id_tanggungan" id="inputIdTanggungan" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc;">
                        <option value="">-- Pilih Polis Terlebih Dahulu --</option>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Fasilitas Kesehatan *</label>
                    <select name="id_faskes" id="inputIdFaskes" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Faskes --</option>
                        <?php foreach($faskes_list as $f): ?>
                            <option value="<?php echo htmlspecialchars($f['id_faskes']); ?>"><?php echo htmlspecialchars($f['nama_faskes'] . ' (' . $f['tingkat_faskes'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Diagnosis (ICD) *</label>
                    <select name="kode_icd" id="inputKodeIcd" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Diagnosis --</option>
                        <?php foreach($icd_list as $icd): ?>
                            <option value="<?php echo htmlspecialchars($icd['kode_icd']); ?>"><?php echo htmlspecialchars($icd['kode_icd'] . ' - ' . $icd['nama_penyakit']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Jenis Perawatan *</label>
                    <select name="jenis_perawatan" id="inputJenisPerawatan" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Pilih Perawatan --</option>
                        <option value="Rawat Jalan">Rawat Jalan</option>
                        <option value="Rawat Inap">Rawat Inap</option>
                        <option value="Pembedahan">Pembedahan</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tanggal Masuk *</label>
                        <input type="date" name="tanggal_masuk" id="inputTglMasuk" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="input-group">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tanggal Keluar</label>
                        <input type="date" name="tanggal_keluar" id="inputTglKeluar" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Total Tagihan Faskes (Rp) *</label>
                    <input type="number" name="total_tagihan_faskes" id="inputTotalTagihan" required min="0" step="1000" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="0">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalFormKlaim')" class="btn btn-ghost" style="padding: 10px 20px;">Batal</button>
                    <button type="submit" class="btn" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px;">Simpan Klaim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI DELETE -->
<div id="modalDelete" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 400px; margin: 20px;">
        <div class="admin-card-header" style="background: #fef2f2; border-bottom: 1px solid #fee2e2;">
            <h3 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Batalkan Klaim</h3>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <p>Apakah Anda yakin ingin membatalkan dan menghapus pengajuan klaim ini? Tindakan ini tidak dapat diurungkan.</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_klaim">
                <input type="hidden" name="no_klaim" id="delNoKlaim" value="">
                
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModal('modalDelete')" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn" style="background: #ef4444; color: white; border: none; border-radius: 6px;">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const polisTanggunganMap = <?php echo json_encode($polis_tanggungan_map); ?>;

    function updateTanggunganOptions(selectedId = '') {
        const noPolis = document.getElementById('inputNoPolis').value;
        const tanggunganSelect = document.getElementById('inputIdTanggungan');
        
        tanggunganSelect.innerHTML = '';
        if (noPolis && polisTanggunganMap[noPolis]) {
            tanggunganSelect.style.background = '#ffffff';
            polisTanggunganMap[noPolis].forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.nama;
                if (t.id == selectedId) opt.selected = true;
                tanggunganSelect.appendChild(opt);
            });
        } else {
            tanggunganSelect.style.background = '#f8fafc';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Pilih Polis Terlebih Dahulu --';
            tanggunganSelect.appendChild(opt);
        }
    }

    function openCreateModal() {
        document.getElementById('modalFormTitle').textContent = 'Ajukan Klaim Baru';
        document.getElementById('formAction').value = 'create_klaim';
        document.getElementById('formKlaim').reset();
        document.getElementById('inputNoKlaim').value = '';
        updateTanggunganOptions();
        document.getElementById('modalFormKlaim').style.display = 'flex';
    }

    function openEditModal(btn) {
        document.getElementById('modalFormTitle').textContent = 'Edit Klaim';
        document.getElementById('formAction').value = 'update_klaim';
        
        document.getElementById('inputNoKlaim').value = btn.getAttribute('data-noklaim');
        document.getElementById('inputNoPolis').value = btn.getAttribute('data-nopolis');
        
        // update tanggungan options and set selected
        updateTanggunganOptions(btn.getAttribute('data-idtanggungan'));
        
        document.getElementById('inputIdFaskes').value = btn.getAttribute('data-idfaskes');
        document.getElementById('inputKodeIcd').value = btn.getAttribute('data-kodeicd');
        document.getElementById('inputJenisPerawatan').value = btn.getAttribute('data-jenisperawatan');
        document.getElementById('inputTglMasuk').value = btn.getAttribute('data-tglmasuk');
        document.getElementById('inputTglKeluar').value = btn.getAttribute('data-tglkeluar');
        document.getElementById('inputTotalTagihan').value = btn.getAttribute('data-total');

        document.getElementById('modalFormKlaim').style.display = 'flex';
    }

    function openDeleteModal(noKlaim) {
        document.getElementById('delNoKlaim').value = noKlaim;
        document.getElementById('modalDelete').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once __DIR__ . "/../../layouts/customer/footer.php"; ?>
