<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";
    
    /** @var PDO $conn */
    $id_user = $_SESSION['id_user'];

    // Get id_pemegang from users table
    $stmt_user = $conn->prepare("SELECT id_pemegang FROM users WHERE id_user = ?");
    $stmt_user->execute([$id_user]);
    $user_data = $stmt_user->fetch();

    if (!$user_data || empty($user_data['id_pemegang'])) {
        $_SESSION['toast_error'] = 'Data pemegang polis tidak ditemukan.';
        header("Location: ../index.php");
        exit;
    }

    $id_pemegang = $user_data['id_pemegang'];

    // Ambil data profil pemegang polis saat ini
    $stmt = $conn->prepare("SELECT * FROM pemegang_polis WHERE id_pemegang = ?");
    $stmt->execute([$id_pemegang]);
    $profil = $stmt->fetch();

    // Proses Submit Form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profil') {
        $pekerjaan = $_POST['pekerjaan'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $no_telepon = $_POST['no_telepon'] ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($alamat) || empty($no_telepon)) {
            $error = "Alamat dan Nomor Telepon wajib diisi.";
        } else {
            try {
                $stmt_update = $conn->prepare("UPDATE pemegang_polis SET pekerjaan = ?, alamat = ?, no_telepon = ?, email = ? WHERE id_pemegang = ?");
                $stmt_update->execute([$pekerjaan, $alamat, $no_telepon, $email, $id_pemegang]);
                
                $_SESSION['toast_success'] = 'Profil pemegang polis berhasil diperbarui!';
                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { 
                    $error = "Email tersebut sudah digunakan.";
                } else {
                    error_log("Error update profil pemegang polis: " . $e->getMessage());
                    $error = "Terjadi kesalahan sistem saat memperbarui data.";
                }
            }
            }
        }
    
    // Proses Cancel Polis
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_polis') {
            $no_polis = $_POST['no_polis'] ?? '';
            $konfirmasi = $_POST['konfirmasi'] ?? '';
            
            if (!empty($no_polis)) {
                // Verifikasi kepemilikan
                $stmt_cek = $conn->prepare("SELECT p.no_polis, p.status_polis FROM polis p JOIN users u ON p.id_pemegang = u.id_pemegang WHERE p.no_polis = ? AND u.id_user = ?");
                $stmt_cek->execute([$no_polis, $id_user]);
                $polis_cek = $stmt_cek->fetch();
                
                if (!$polis_cek) {
                    $error = "Akses ditolak. Polis tidak valid atau bukan milik Anda.";
                } else {
                    $status_tidak_bisa_batal = ['Pending Cancellation', 'Surrender', 'Lapse', 'Claimed', 'Rejected'];
                    if (in_array($polis_cek['status_polis'], $status_tidak_bisa_batal)) {
                        $error = "Polis ini tidak dapat dibatalkan atau sedang dalam proses pembatalan.";
                    } elseif ($konfirmasi === 'YAKIN') {
                        try {
                            $stmt_update = $conn->prepare("UPDATE polis SET status_polis = 'Pending Cancellation' WHERE no_polis = ?");
                            $stmt_update->execute([$no_polis]);
                            
                            $_SESSION['toast_success'] = 'Permintaan pembatalan polis berhasil dikirim. Menunggu persetujuan Admin.';
                            header("Location: index.php");
                            exit;
                        } catch (PDOException $e) {
                            error_log("Error pembatalan polis: " . $e->getMessage());
                            $error = "Terjadi kesalahan sistem saat memproses pembatalan.";
                        }
                    } else {
                        $error = "Ketik 'YAKIN' untuk mengonfirmasi pembatalan.";
                    }
                }
            }
        }

    // Proses Create Tanggungan
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_tanggungan') {
        $no_polis = $_POST['no_polis'] ?? '';
        $nik = $_POST['nik'] ?? '';
        $nama_lengkap = $_POST['nama_lengkap'] ?? '';
        $hubungan = $_POST['hubungan'] ?? '';
        $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';

        if (empty($nik) || empty($nama_lengkap) || empty($hubungan) || empty($tanggal_lahir) || empty($jenis_kelamin)) {
            $error = "Semua kolom wajib diisi.";
        } elseif (strlen($nik) !== 16) {
            $error = "NIK harus berjumlah 16 digit.";
        } else {
            try {
                $stmt_insert = $conn->prepare("INSERT INTO tanggungan_polis (no_polis, nik, nama_lengkap, hubungan, tanggal_lahir, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->execute([$no_polis, $nik, $nama_lengkap, $hubungan, $tanggal_lahir, $jenis_kelamin]);
                
                $_SESSION['toast_success'] = 'Anggota keluarga berhasil ditambahkan dan saat ini berstatus Pending.';
                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "NIK tersebut sudah terdaftar di sistem.";
                } else {
                    $error = "Terjadi kesalahan sistem saat menyimpan data.";
                }
            }
        }
    }

    // Proses Update Tanggungan
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tanggungan') {
        $id_tanggungan = $_POST['id_tanggungan'] ?? 0;
        $nik = $_POST['nik'] ?? '';
        $nama_lengkap = $_POST['nama_lengkap'] ?? '';
        $hubungan = $_POST['hubungan'] ?? '';
        $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';

        if (empty($nik) || empty($nama_lengkap) || empty($hubungan) || empty($tanggal_lahir) || empty($jenis_kelamin)) {
            $error = "Semua kolom wajib diisi.";
        } elseif (strlen($nik) !== 16) {
            $error = "NIK harus berjumlah 16 digit.";
        } else {
            try {
                $stmt_update = $conn->prepare("UPDATE tanggungan_polis SET nik = ?, nama_lengkap = ?, hubungan = ?, tanggal_lahir = ?, jenis_kelamin = ?, status_tanggungan = 'Pending' WHERE id_tanggungan = ?");
                $stmt_update->execute([$nik, $nama_lengkap, $hubungan, $tanggal_lahir, $jenis_kelamin, $id_tanggungan]);
                
                $_SESSION['toast_success'] = 'Data anggota keluarga berhasil diperbarui dan berstatus Pending.';
                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "NIK tersebut sudah terdaftar pada anggota lain.";
                } else {
                    $error = "Terjadi kesalahan sistem saat memperbarui data.";
                }
            }
        }
    }

    // Proses Delete Tanggungan
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_tanggungan') {
        $id_tanggungan = $_POST['id_tanggungan'] ?? 0;
        
        $stmt_cek = $conn->prepare("SELECT status_tanggungan FROM tanggungan_polis WHERE id_tanggungan = ?");
        $stmt_cek->execute([$id_tanggungan]);
        $t_cek = $stmt_cek->fetch();
        
        if ($t_cek) {
            try {
                if ($t_cek['status_tanggungan'] === 'Pending') {
                    $stmt_delete = $conn->prepare("DELETE FROM tanggungan_polis WHERE id_tanggungan = ?");
                    $stmt_delete->execute([$id_tanggungan]);
                    $_SESSION['toast_success'] = 'Data anggota keluarga berhasil dihapus.';
                    header("Location: index.php");
                    exit;
                } elseif ($t_cek['status_tanggungan'] === 'Active') {
                    $stmt_update = $conn->prepare("UPDATE tanggungan_polis SET status_tanggungan = 'Pending Deletion' WHERE id_tanggungan = ?");
                    $stmt_update->execute([$id_tanggungan]);
                    $_SESSION['toast_success'] = 'Permintaan hapus anggota keluarga berhasil dikirim.';
                    header("Location: index.php");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan sistem saat menghapus data.";
            }
        }
    }
?>

<style>
    .btn-action-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .btn-edit-modern {
        background-color: white;
        color: var(--color-blue);
        border-color: #e2e8f0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .btn-edit-modern:hover {
        border-color: var(--color-blue);
        background-color: #f8fafc;
    }
    .btn-delete-modern {
        background-color: white;
        color: var(--color-danger);
        border-color: #e2e8f0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .btn-delete-modern:hover {
        border-color: var(--color-danger);
        background-color: #fef2f2;
    }
    .badge-status-modern {
        color: white; 
        padding: 5px 12px; 
        border-radius: 20px; 
        font-weight: 600; 
        font-size: 12px;
        letter-spacing: 0.3px;
    }
</style>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Detail Polis Asuransi Anda</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Berikut adalah informasi lengkap mengenai polis asuransi Anda beserta tanggungan yang terdaftar.</p>
</div>

<?php if (isset($error)): ?>
    <div style="background-color: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fca5a5;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php
    // Ambil detail polis
    $stmt_polis = $conn->prepare("SELECT 
                                    p.no_polis, p.tanggal_terbit, p.tanggal_jatuh_tempo, p.status_polis,
                                    pr.nama_produk, pr.limit_tahunan, pr.jenis_kategori,
                                    pp.nama_lengkap, pp.nik, pp.alamat, pp.no_telepon, pp.email
                                  FROM polis p
                                  JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
                                  JOIN users u ON p.id_pemegang = u.id_pemegang
                                  JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
                                  WHERE u.id_user = ?");
    $stmt_polis->execute([$id_user]);
    $result_polis = $stmt_polis->fetchAll();

    if(count($result_polis) > 0) {
        foreach($result_polis as $polis) {
        
        $status_color = 'var(--color-slate)';
        if($polis['status_polis'] == 'Inforce') $status_color = 'var(--color-aqua)';
        elseif($polis['status_polis'] == 'Pending Approval') $status_color = '#f59e0b';
        elseif($polis['status_polis'] == 'Pending Cancellation') $status_color = '#f97316';
        elseif($polis['status_polis'] == 'Lapse' || $polis['status_polis'] == 'Surrender' || $polis['status_polis'] == 'Rejected') $status_color = '#ef4444';
?>
    <!-- Wrapper per Polis -->
    <div style="margin-bottom: var(--space-8);">
    
    <div class="admin-card animate-fade-in-up delay-2" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; border: none;">
      <div class="admin-card-body" style="padding: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2 style="margin: 0 0 8px 0; font-size: 24px; color: white;">Polis: <?php echo htmlspecialchars($polis['no_polis']); ?></h2>
            <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; opacity: 0.9;">
                Status: <span style="display: inline-block; background: white; color: #1e293b; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 12px;"><?php echo htmlspecialchars($polis['status_polis']); ?></span>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="print.php?no_polis=<?php echo urlencode($polis['no_polis']); ?>" target="_blank" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); font-weight: 600; padding: 10px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;"><i class="fa-solid fa-print" style="margin-right: 5px;"></i> Cetak Sertifikat</a>
            <button onclick="openEditModal()" class="btn" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); font-weight: 600; padding: 10px 20px; border-radius: 8px; cursor: pointer;"><i class="fa-solid fa-pen-to-square" style="margin-right: 5px;"></i> Edit Keterangan</button>
            <?php 
                $status_tidak_bisa_batal = ['Pending Cancellation', 'Surrender', 'Lapse', 'Claimed', 'Rejected'];
                if (!in_array($polis['status_polis'], $status_tidak_bisa_batal)): 
            ?>
                <button onclick="openCancelModal('<?php echo htmlspecialchars($polis['no_polis']); ?>')" class="btn" style="background: #ef4444; color: white; font-weight: 600; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;"><i class="fa-solid fa-ban" style="margin-right: 5px;"></i> Request Batal</button>
            <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="admin-stats-grid animate-fade-in-up delay-3">
      <div class="admin-stat-card blue">
        <div class="admin-stat-icon blue"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="admin-stat-content">
          <h4>Produk Asuransi</h4>
          <h2 style="font-size: 18px;"><?php echo htmlspecialchars($polis['nama_produk']); ?></h2>
          <div style="font-size: 12px; color: #64748b; margin-top: 5px;">Kategori: <?php echo htmlspecialchars($polis['jenis_kategori']); ?></div>
        </div>
      </div>
      <div class="admin-stat-card green">
        <div class="admin-stat-icon green"><i class="fa-solid fa-wallet"></i></div>
        <div class="admin-stat-content">
          <h4>Limit Tahunan</h4>
          <h2 style="font-size: 18px;">Rp <?php echo number_format($polis['limit_tahunan'], 0, ',', '.'); ?></h2>
        </div>
      </div>
      <div class="admin-stat-card orange">
        <div class="admin-stat-icon orange"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="admin-stat-content">
          <h4>Periode Polis</h4>
          <h2 style="font-size: 18px;"><?php echo date('d M', strtotime($polis['tanggal_terbit'])); ?> - <?php echo date('d M Y', strtotime($polis['tanggal_jatuh_tempo'])); ?></h2>
        </div>
      </div>
      <div class="admin-stat-card purple">
        <div class="admin-stat-icon purple"><i class="fa-solid fa-user-check"></i></div>
        <div class="admin-stat-content">
          <h4>Pemegang Polis</h4>
          <h2 style="font-size: 18px;"><?php echo htmlspecialchars($polis['nama_lengkap']); ?></h2>
          <div style="font-size: 12px; color: #64748b; margin-top: 5px;">NIK: <?php echo htmlspecialchars($polis['nik']); ?></div>
        </div>
      </div>
    </div>

    <!-- Tanggungan Polis -->
    <div class="admin-card animate-fade-in-up delay-4">
      <div class="admin-card-header">
        <h3>Daftar Tanggungan Keluarga</h3>
        <?php if($polis['jenis_kategori'] === 'Keluarga'): ?>
            <button onclick="openCreateTanggunganModal('<?php echo htmlspecialchars($polis['no_polis']); ?>')" class="btn btn-sm" style="background: #3b82f6; color: white; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 600; border: none; cursor: pointer;"><i class="fa-solid fa-user-plus"></i> Tambah Anggota</button>
        <?php endif; ?>
      </div>
      
      <div class="admin-card-body" style="overflow-x: auto;">
        <?php
            $stmt_tanggungan = $conn->prepare("SELECT 
                                                t.id_tanggungan, t.nik, t.nama_lengkap, t.hubungan, t.tanggal_lahir, t.jenis_kelamin, t.status_tanggungan
                                              FROM tanggungan_polis t
                                              WHERE t.no_polis = ?");
            $stmt_tanggungan->execute([$polis['no_polis']]);
            $result_tanggungan = $stmt_tanggungan->fetchAll();

            if(count($result_tanggungan) > 0) {
        ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama Anggota</th>
                        <th>Status Hubungan</th>
                        <th>Detail</th>
                        <th>Status Verifikasi</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($result_tanggungan as $t_row): 
                        $badge_class = 'badge-neutral';
                        if ($t_row['status_tanggungan'] === 'Active') $badge_class = 'badge-success';
                        elseif ($t_row['status_tanggungan'] === 'Pending') $badge_class = 'badge-warning';
                        elseif ($t_row['status_tanggungan'] === 'Pending Deletion') $badge_class = 'badge-danger';
                        elseif ($t_row['status_tanggungan'] === 'Rejected') $badge_class = 'badge-danger';
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: #1e293b;">
                            <div style="display: flex; align-items: center;">
                                <div style="width: 35px; height: 35px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <?php echo htmlspecialchars($t_row['nama_lengkap']); ?>
                            </div>
                        </td>
                        <td>
                            <span style="display: inline-block; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                <?php echo htmlspecialchars($t_row['hubungan']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="color: #1e293b;"><?php echo ($t_row['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?></div>
                            <div style="font-size: 12px; color: #64748b;">Lahir: <?php echo date('d M Y', strtotime($t_row['tanggal_lahir'])); ?></div>
                        </td>
                        <td>
                            <span class="badge <?php echo $badge_class; ?>" style="font-size: 11px;">
                                <?php echo htmlspecialchars($t_row['status_tanggungan']); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <?php if($t_row['status_tanggungan'] !== 'Pending Deletion' && $t_row['status_tanggungan'] !== 'Rejected'): ?>
                                <button onclick="openUpdateTanggunganModal('<?php echo $t_row['id_tanggungan']; ?>', '<?php echo $t_row['nik']; ?>', '<?php echo htmlspecialchars($t_row['nama_lengkap'], ENT_QUOTES); ?>', '<?php echo $t_row['hubungan']; ?>', '<?php echo $t_row['tanggal_lahir']; ?>', '<?php echo $t_row['jenis_kelamin']; ?>')" class="btn btn-ghost btn-sm" style="color: #3b82f6; font-size: 12px; padding: 5px 10px; border: none; cursor: pointer; background: transparent;"><i class="fa-solid fa-pen"></i> Edit</button>
                                
                                <button onclick="openDeleteTanggunganModal('<?php echo $t_row['id_tanggungan']; ?>', '<?php echo htmlspecialchars($t_row['nama_lengkap'], ENT_QUOTES); ?>')" class="btn btn-ghost btn-sm" style="color: #ef4444; font-size: 12px; padding: 5px 10px; border: none; cursor: pointer; background: transparent;"><i class="fa-solid fa-trash"></i> Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php
            } else {
        ?>
            <div style="padding: 30px; text-align: center; color: #94a3b8;">
                Tidak ada tanggungan anggota keluarga yang terdaftar pada polis ini.
            </div>
        <?php
            }
        ?>
      </div>
    </div>
    </div> <!-- End of Wrapper per Polis -->

<?php
        } // End of while loop
    } else {
?>
    <div class="empty-state" style="padding: var(--space-8); text-align: center;">
        <div class="empty-state-icon" style="font-size: 2.5rem; margin-bottom: var(--space-2); color: var(--color-warning);"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="empty-state-title" style="margin-bottom: var(--space-2);">Polis Tidak Ditemukan</h3>
        <p class="empty-state-text" style="color: var(--color-text-secondary);">Anda belum memiliki polis asuransi yang aktif atau belum terhubung dengan akun ini.</p>
    </div>
<?php
    }
?>



<!-- Edit Modal -->
<div id="editProfilModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div class="admin-card animate-fade-in-up" style="max-width: 600px; width: 100%; margin: 0 auto; background: white; max-height: 90vh; overflow-y: auto;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="margin-bottom: 5px;"><i class="fa-solid fa-user-pen" style="color: #3b82f6; margin-right: 8px;"></i> Edit Profil Pemegang Polis</h3>
                <p style="color: #64748b; font-size: 13px; margin: 0; font-weight: normal;">Perbarui informasi kontak dan data diri Anda.</p>
            </div>
            <button onclick="closeEditModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 30px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profil">
                
                <!-- Read-only fields -->
                <div class="input-group" style="margin-bottom: 20px; opacity: 0.7;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">NIK (Tidak dapat diubah)</label>
                    <input type="text" value="<?php echo htmlspecialchars($profil['nik']); ?>" readonly style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px; background: #e5e7eb; cursor: not-allowed;">
                </div>
                
                <div class="input-group" style="margin-bottom: 20px; opacity: 0.7;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Nama Lengkap (Tidak dapat diubah)</label>
                    <input type="text" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" readonly style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px; background: #e5e7eb; cursor: not-allowed;">
                </div>

                <!-- Editable fields -->
                <div class="input-group" style="margin-bottom: 20px;">
                    <label for="pekerjaan" style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Pekerjaan</label>
                    <input type="text" id="pekerjaan" name="pekerjaan" placeholder="Contoh: Wiraswasta" value="<?php echo htmlspecialchars($_POST['pekerjaan'] ?? $profil['pekerjaan']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label for="alamat" style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Alamat Lengkap</label>
                    <input type="text" id="alamat" name="alamat" required placeholder="Masukkan alamat domisili" value="<?php echo htmlspecialchars($_POST['alamat'] ?? $profil['alamat']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label for="no_telepon" style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">No. Telepon / WhatsApp</label>
                    <input type="text" id="no_telepon" name="no_telepon" required placeholder="Contoh: 08123456789" value="<?php echo htmlspecialchars($_POST['no_telepon'] ?? $profil['no_telepon']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label for="email" style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Alamat Email</label>
                    <input type="email" id="email" name="email" required placeholder="Contoh: email@domain.com" value="<?php echo htmlspecialchars($_POST['email'] ?? $profil['email']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-ghost" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn" style="flex: 1; background: #3b82f6; color: white; padding: 12px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Polis Modal -->
<div id="cancelPolisModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div class="admin-card animate-fade-in-up" style="max-width: 500px; width: 100%; margin: 0 auto; background: white; border-top: 4px solid #ef4444;">
        <div class="admin-card-body" style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
                <h3 style="color: #1e293b; margin: 0;">Konfirmasi Pembatalan Polis</h3>
                <p style="color: #64748b; font-size: 15px; margin-top: 5px;">Polis No: <strong id="cancelPolisNumberDisplay"></strong></p>
            </div>

            <div style="background-color: #fffbeb; color: #b45309; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #fde68a; font-size: 14px; line-height: 1.5;">
                <strong>Perhatian!</strong> Permintaan pembatalan polis ini akan dikirimkan ke Admin untuk disetujui. Selama proses menunggu, status polis akan berubah menjadi <em>Pending Cancellation</em> dan Anda mungkin tidak dapat menggunakan manfaat asuransi.
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="cancel_polis">
                <input type="hidden" name="no_polis" id="cancelPolisNumberInput" value="">
                
                <div class="input-group">
                    <label for="konfirmasi" style="display: block; text-align: center; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Ketik <strong>YAKIN</strong> untuk melanjutkan:</label>
                    <input type="text" id="konfirmasi" name="konfirmasi" required placeholder="YAKIN" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px; text-align: center; text-transform: uppercase;">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="button" onclick="closeCancelModal()" class="btn btn-ghost" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn" style="flex: 1; background: #ef4444; color: white; padding: 12px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);">Kirim Request Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Tanggungan Modal -->
<div id="createTanggunganModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div class="admin-card animate-fade-in-up" style="max-width: 600px; width: 100%; margin: 0 auto; background: white; max-height: 90vh; overflow-y: auto;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="margin-bottom: 5px;"><i class="fa-solid fa-user-plus" style="color: #3b82f6; margin-right: 8px;"></i> Tambah Anggota Keluarga</h3>
                <p style="color: #64748b; font-size: 13px; margin: 0; font-weight: normal;">Polis No: <span id="createTanggunganPolisNo"></span></p>
            </div>
            <button onclick="closeCreateTanggunganModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 30px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_tanggungan">
                <input type="hidden" name="no_polis" id="createTanggunganPolisInput" value="">
                
                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" name="nik" required minlength="16" maxlength="16" pattern="\d{16}" placeholder="Masukkan 16 digit NIK" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required placeholder="Sesuai KTP/KK" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Hubungan dengan Pemegang Polis</label>
                    <select name="hubungan" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-family: inherit; background: #fff; color: #1e293b;">
                        <option value="">-- Pilih Hubungan --</option>
                        <option value="Suami">Suami</option>
                        <option value="Istri">Istri</option>
                        <option value="Anak">Anak</option>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Jenis Kelamin</label>
                    <select name="jenis_kelamin" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-family: inherit; background: #fff; color: #1e293b;">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                    <button type="button" onclick="closeCreateTanggunganModal()" class="btn btn-ghost" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn" style="flex: 1; background: #3b82f6; color: white; padding: 12px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer;">Simpan Anggota</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Tanggungan Modal -->
<div id="updateTanggunganModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div class="admin-card animate-fade-in-up" style="max-width: 600px; width: 100%; margin: 0 auto; background: white; max-height: 90vh; overflow-y: auto;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h3 style="margin-bottom: 5px;"><i class="fa-solid fa-user-pen" style="color: #3b82f6; margin-right: 8px;"></i> Edit Anggota Keluarga</h3>
                <p style="color: #64748b; font-size: 13px; margin: 0; font-weight: normal;">Perbarui data tanggungan.</p>
            </div>
            <button onclick="closeUpdateTanggunganModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 30px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_tanggungan">
                <input type="hidden" name="id_tanggungan" id="updateTanggunganId" value="">
                
                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" name="nik" id="updateTanggunganNik" required minlength="16" maxlength="16" pattern="\d{16}" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="updateTanggunganNama" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Hubungan dengan Pemegang Polis</label>
                    <select name="hubungan" id="updateTanggunganHubungan" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-family: inherit; background: #fff; color: #1e293b;">
                        <option value="">-- Pilih Hubungan --</option>
                        <option value="Suami">Suami</option>
                        <option value="Istri">Istri</option>
                        <option value="Anak">Anak</option>
                    </select>
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="updateTanggunganTanggal" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 15px;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px;">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="updateTanggunganJk" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-family: inherit; background: #fff; color: #1e293b;">
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                    <button type="button" onclick="closeUpdateTanggunganModal()" class="btn btn-ghost" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn" style="flex: 1; background: #3b82f6; color: white; padding: 12px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Tanggungan Modal -->
<div id="deleteTanggunganModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div class="admin-card animate-fade-in-up" style="max-width: 450px; width: 100%; margin: 0 auto; background: white; border-top: 4px solid #ef4444;">
        <div class="admin-card-body" style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #ef4444; margin-bottom: 15px;"></i>
                <h3 style="color: #1e293b; margin: 0;">Konfirmasi Hapus</h3>
                <p style="color: #64748b; font-size: 15px; margin-top: 5px;">Apakah Anda yakin ingin menghapus <strong id="deleteTanggunganNama"></strong>?</p>
            </div>
            <div style="background-color: #fffbeb; color: #b45309; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #fde68a; font-size: 14px; line-height: 1.5; text-align: center;">
                Bila anggota ini berstatus Aktif, proses hapus akan masuk ke tahap Pending untuk disetujui oleh Admin.
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_tanggungan">
                <input type="hidden" name="id_tanggungan" id="deleteTanggunganId" value="">
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="closeDeleteTanggunganModal()" class="btn btn-ghost" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn" style="flex: 1; background: #ef4444; color: white; padding: 12px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer;">Hapus Anggota</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Edit Profil Modal
    function openEditModal() {
        const modal = document.getElementById('editProfilModal');
        modal.style.display = 'flex';
        const card = modal.querySelector('.admin-card');
        card.style.animation = 'none';
        card.offsetHeight; 
        card.style.animation = null;
    }

    function closeEditModal() {
        document.getElementById('editProfilModal').style.display = 'none';
    }

    document.getElementById('editProfilModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    // Cancel Polis Modal
    function openCancelModal(noPolis) {
        const modal = document.getElementById('cancelPolisModal');
        document.getElementById('cancelPolisNumberDisplay').innerText = noPolis;
        document.getElementById('cancelPolisNumberInput').value = noPolis;
        document.getElementById('konfirmasi').value = ''; // Reset input
        modal.style.display = 'flex';
        
        const card = modal.querySelector('.admin-card');
        card.style.animation = 'none';
        card.offsetHeight; 
        card.style.animation = null;
    }

    function closeCancelModal() {
        document.getElementById('cancelPolisModal').style.display = 'none';
    }

    document.getElementById('cancelPolisModal').addEventListener('click', function(e) {
        if (e.target === this) closeCancelModal();
    });

    // Create Tanggungan Modal
    function openCreateTanggunganModal(noPolis) {
        const modal = document.getElementById('createTanggunganModal');
        document.getElementById('createTanggunganPolisNo').innerText = noPolis;
        document.getElementById('createTanggunganPolisInput').value = noPolis;
        modal.style.display = 'flex';
        
        const card = modal.querySelector('.admin-card');
        card.style.animation = 'none';
        card.offsetHeight; 
        card.style.animation = null;
    }

    function closeCreateTanggunganModal() {
        document.getElementById('createTanggunganModal').style.display = 'none';
    }

    document.getElementById('createTanggunganModal').addEventListener('click', function(e) {
        if (e.target === this) closeCreateTanggunganModal();
    });

    // Update Tanggungan Modal
    function openUpdateTanggunganModal(id, nik, nama, hubungan, tanggal, jk) {
        const modal = document.getElementById('updateTanggunganModal');
        document.getElementById('updateTanggunganId').value = id;
        document.getElementById('updateTanggunganNik').value = nik;
        document.getElementById('updateTanggunganNama').value = nama;
        document.getElementById('updateTanggunganHubungan').value = hubungan;
        document.getElementById('updateTanggunganTanggal').value = tanggal;
        document.getElementById('updateTanggunganJk').value = jk;
        modal.style.display = 'flex';
        
        const card = modal.querySelector('.admin-card');
        card.style.animation = 'none';
        card.offsetHeight; 
        card.style.animation = null;
    }

    function closeUpdateTanggunganModal() {
        document.getElementById('updateTanggunganModal').style.display = 'none';
    }

    document.getElementById('updateTanggunganModal').addEventListener('click', function(e) {
        if (e.target === this) closeUpdateTanggunganModal();
    });

    // Delete Tanggungan Modal
    function openDeleteTanggunganModal(id, nama) {
        const modal = document.getElementById('deleteTanggunganModal');
        document.getElementById('deleteTanggunganId').value = id;
        document.getElementById('deleteTanggunganNama').innerText = nama;
        modal.style.display = 'flex';
        
        const card = modal.querySelector('.admin-card');
        card.style.animation = 'none';
        card.offsetHeight; 
        card.style.animation = null;
    }

    function closeDeleteTanggunganModal() {
        document.getElementById('deleteTanggunganModal').style.display = 'none';
    }

    document.getElementById('deleteTanggunganModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteTanggunganModal();
    });
</script>

<?php
    include "../../layouts/customer/footer.php";
?>
