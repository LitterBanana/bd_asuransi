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
            if ($action === 'create_admin') {
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $email = trim($_POST['email']);
                $no_telp = trim($_POST['no_telp']);
                
                // Cek username unik
                $stmt_cek = $conn->prepare("SELECT id_user FROM users WHERE username = ?");
                $stmt_cek->execute([$username]);
                if ($stmt_cek->rowCount() > 0) {
                    $_SESSION['toast_error'] = "Username '$username' sudah terdaftar.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, no_telp, role) VALUES (?, ?, ?, ?, 'Admin')");
                    $stmt->execute([$username, $hashed_password, $email, $no_telp]);
                    $_SESSION['toast_success'] = "Akun Admin baru berhasil dibuat.";
                }

            } elseif ($action === 'update_user') {
                $id_user = $_POST['id_user'];
                $email = trim($_POST['email']);
                $no_telp = trim($_POST['no_telp']);
                
                $stmt = $conn->prepare("UPDATE users SET email = ?, no_telp = ? WHERE id_user = ?");
                $stmt->execute([$email, $no_telp, $id_user]);
                $_SESSION['toast_success'] = "Informasi kontak pengguna berhasil diperbarui.";

            } elseif ($action === 'toggle_status') {
                $id_user = $_POST['id_user'];
                $status_baru = $_POST['status_baru']; // 'Aktif' atau 'Blokir'
                
                // Cegah blokir diri sendiri
                if ($id_user == $_SESSION['id_user']) {
                    $_SESSION['toast_error'] = "Anda tidak dapat memblokir akun Anda sendiri yang sedang aktif.";
                } else {
                    $stmt = $conn->prepare("UPDATE users SET status_akun = ? WHERE id_user = ?");
                    $stmt->execute([$status_baru, $id_user]);
                    $_SESSION['toast_success'] = "Status akun berhasil diubah menjadi $status_baru.";
                }

            } elseif ($action === 'reset_password') {
                $id_user = $_POST['id_user'];
                $default_pass = "Asuransi123";
                $hashed_password = password_hash($default_pass, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                $stmt->execute([$hashed_password, $id_user]);
                $_SESSION['toast_success'] = "Password berhasil di-reset menjadi: $default_pass";
            }

        } catch (PDOException $e) {
            error_log("Error user management: " . $e->getMessage());
            $_SESSION['toast_error'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
        
        echo "<script>window.location.href='index.php';</script>";
        exit;
    }

    // Ambil data filter
    $search = $_GET['search'] ?? '';
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($search !== '') {
        $whereClause .= " AND (username LIKE ? OR email LIKE ? OR no_telp LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($role !== '') {
        $whereClause .= " AND role = ?";
        $params[] = $role;
    }

    if ($status !== '') {
        $whereClause .= " AND status_akun = ?";
        $params[] = $status;
    }

    // Ambil data semua users
    $query = "SELECT * FROM users $whereClause ORDER BY role ASC, created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $user_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Manajemen Pengguna</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Kontrol akses, blokir akun, dan reset sandi untuk seluruh pengguna sistem.</p>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center; background: white; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <div style="position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" placeholder="Cari Username/Kontak..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 200px; outline: none;">
            </div>
            <select name="role" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
                <option value="">Semua Role</option>
                <option value="Admin" <?php echo $role === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="Agen" <?php echo $role === 'Agen' ? 'selected' : ''; ?>>Agen</option>
                <option value="Customer" <?php echo $role === 'Customer' ? 'selected' : ''; ?>>Customer</option>
            </select>
            <select name="status" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white;">
                <option value="">Semua Status</option>
                <option value="Aktif" <?php echo $status === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="Blokir" <?php echo $status === 'Blokir' ? 'selected' : ''; ?>>Blokir</option>
            </select>
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">Cari</button>
            <?php if ($search || $role || $status): ?>
                <a href="index.php" class="btn-admin btn-admin-ghost" style="padding: 8px 16px;">Reset</a>
            <?php endif; ?>
        </form>

        <button onclick="openCreateAdminModal()" class="btn-admin btn-admin-primary" style="padding: 10px 20px;">
            <i class="fa-solid fa-user-shield"></i> Tambah Admin Baru
        </button>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Kredensial Pengguna</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username & Role</th>
                    <th>Kontak (Email / Telp)</th>
                    <th>Terakhir Login</th>
                    <th>Status Akun</th>
                    <th style="text-align: right;">Aksi Keamanan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($user_list) > 0): ?>
                    <?php foreach ($user_list as $row): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($row['username']); ?></div>
                            <div style="margin-top: 4px;">
                                <?php 
                                    $role = $row['role'];
                                    $badgeRole = 'badge-neutral';
                                    if ($role == 'Admin') $badgeRole = 'badge-primary';
                                    else if ($role == 'Agen') $badgeRole = 'badge-warning';
                                    else if ($role == 'Customer') $badgeRole = 'badge-success';
                                ?>
                                <span class="badge <?php echo $badgeRole; ?>" style="font-size: 10px; padding: 2px 6px;">
                                    <?php echo htmlspecialchars($role); ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px; color: #475569;"><i class="fa-regular fa-envelope" style="margin-right:5px; color:#94a3b8;"></i><?php echo htmlspecialchars($row['email'] ?: '-'); ?></div>
                            <div style="font-size: 13px; color: #475569; margin-top: 4px;"><i class="fa-solid fa-phone" style="margin-right:5px; color:#94a3b8;"></i><?php echo htmlspecialchars($row['no_telp'] ?: '-'); ?></div>
                        </td>
                        <td style="color: #64748b; font-size: 13px;">
                            <?php echo $row['last_login'] ? date('d M Y, H:i', strtotime($row['last_login'])) : 'Belum pernah'; ?>
                        </td>
                        <td>
                            <?php if ($row['status_akun'] === 'Aktif'): ?>
                                <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fa-solid fa-ban"></i> Diblokir</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <button onclick="openEditModal('<?php echo $row['id_user']; ?>', '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo htmlspecialchars($row['no_telp']); ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-primary" title="Edit Kontak">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            
                            <button onclick="confirmResetPassword('<?php echo $row['id_user']; ?>', '<?php echo htmlspecialchars($row['username']); ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-warning" title="Reset Password">
                                <i class="fa-solid fa-key"></i> Reset
                            </button>

                            <?php if ($row['status_akun'] === 'Aktif'): ?>
                                <button onclick="confirmToggleStatus('<?php echo $row['id_user']; ?>', 'Blokir', '<?php echo htmlspecialchars($row['username']); ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-danger" title="Blokir Akun">
                                    <i class="fa-solid fa-lock"></i> Blokir
                                </button>
                            <?php else: ?>
                                <button onclick="confirmToggleStatus('<?php echo $row['id_user']; ?>', 'Aktif', '<?php echo htmlspecialchars($row['username']); ?>')" class="btn-admin btn-admin-sm btn-admin-ghost-success" title="Buka Blokir">
                                    <i class="fa-solid fa-lock-open"></i> Aktifkan
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">Tidak ada data pengguna.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH ADMIN -->
<div id="modalCreateAdmin" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 450px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;">Tambah Admin Baru</h3>
            <button onclick="closeModal('modalCreateAdmin')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_admin">
                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Username *</label>
                    <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Password *</label>
                    <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Email</label>
                    <input type="email" name="email" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">No Telepon</label>
                    <input type="text" name="no_telp" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalCreateAdmin')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-primary">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT KONTAK -->
<div id="modalEditUser" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div class="admin-card animate-fade-in-up" style="width: 100%; max-width: 450px; margin: 20px;">
        <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;">Edit Info Kontak</h3>
            <button onclick="closeModal('modalEditUser')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="admin-card-body" style="padding: 20px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="id_user" id="editIdUser" value="">
                
                <div style="margin-bottom: 15px; padding: 10px; background: #f8fafc; border-radius: 6px; border: 1px dashed #cbd5e1;">
                    <span style="font-size: 12px; color: #64748b; display: block;">Username</span>
                    <strong id="editUsernameDisplay" style="color: #1e293b;"></strong>
                </div>

                <div class="input-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Email</label>
                    <input type="email" name="email" id="editEmail" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="input-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">No Telepon</label>
                    <input type="text" name="no_telp" id="editTelp" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('modalEditUser')" class="btn-admin btn-admin-lg btn-admin-ghost">Batal</button>
                    <button type="submit" class="btn-admin btn-admin-lg btn-admin-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FORM INVISIBLE UNTUK AKSI CEPAT (BLOKIR & RESET) -->
<form id="formQuickAction" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" id="qaAction" value="">
    <input type="hidden" name="id_user" id="qaIdUser" value="">
    <input type="hidden" name="status_baru" id="qaStatusBaru" value="">
</form>

<script>
    function openCreateAdminModal() {
        document.getElementById('modalCreateAdmin').style.display = 'flex';
    }

    function openEditModal(id, username, email, telp) {
        document.getElementById('editIdUser').value = id;
        document.getElementById('editUsernameDisplay').textContent = username;
        document.getElementById('editEmail').value = email;
        document.getElementById('editTelp').value = telp;
        document.getElementById('modalEditUser').style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function confirmToggleStatus(idUser, statusBaur, username) {
        let msg = statusBaur === 'Blokir' 
            ? `Peringatan: Akun '${username}' tidak akan bisa login lagi. Lanjutkan Blokir?`
            : `Buka blokir untuk akun '${username}' agar bisa login kembali?`;
        
        if (confirm(msg)) {
            document.getElementById('qaAction').value = 'toggle_status';
            document.getElementById('qaIdUser').value = idUser;
            document.getElementById('qaStatusBaru').value = statusBaur;
            document.getElementById('formQuickAction').submit();
        }
    }

    function confirmResetPassword(idUser, username) {
        if (confirm(`PERINGATAN! Anda akan me-reset password akun '${username}'. Password baru mereka akan diatur menjadi: Asuransi123\n\nLanjutkan?`)) {
            document.getElementById('qaAction').value = 'reset_password';
            document.getElementById('qaIdUser').value = idUser;
            document.getElementById('formQuickAction').submit();
        }
    }
</script>

<?php require_once __DIR__ . "/../../layouts/admin/footer.php"; ?>
