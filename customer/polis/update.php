<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";
    
    $id_user = $_SESSION['id_user'] ?? 0;

    // Get id_pemegang from users table
    $stmt_user = $conn->prepare("SELECT id_pemegang FROM users WHERE id_user = ?");
    $stmt_user->execute([$id_user]);
    $user_data = $stmt_user->fetch();

    if (!$user_data || empty($user_data['id_pemegang'])) {
        echo "<script>alert('Data pemegang polis tidak ditemukan.'); window.location.href='index.php';</script>";
        exit;
    }

    $id_pemegang = $user_data['id_pemegang'];

    // Ambil data profil pemegang polis saat ini
    $stmt = $conn->prepare("SELECT * FROM pemegang_polis WHERE id_pemegang = ?");
    $stmt->execute([$id_pemegang]);
    $profil = $stmt->fetch();

    if (!$profil) {
        echo "<script>alert('Data profil tidak ditemukan.'); window.location.href='index.php';</script>";
        exit;
    }

    // Proses Submit Form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                
                echo "<script>alert('Profil pemegang polis berhasil diperbarui!'); window.location.href='index.php';</script>";
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Constraint violation (e.g., unique email)
                    $error = "Email tersebut sudah digunakan.";
                } else {
                    error_log("Error update profil pemegang polis: " . $e->getMessage());
                    $error = "Terjadi kesalahan sistem saat memperbarui data.";
                }
            }
        }
    }
?>

<div class="card" style="max-width: 600px; margin: 20px auto;">
    <div style="border-bottom: 2px solid var(--color-light); padding-bottom: 15px; margin-bottom: 20px;">
        <h3 style="color: var(--color-dark); margin: 0;"><i class="fa-solid fa-user-pen" style="color: var(--color-aqua);"></i> Edit Profil Pemegang Polis</h3>
        <p style="color: var(--color-slate); font-size: 14px; margin-top: 5px;">Perbarui informasi kontak dan data diri Anda.</p>
    </div>

    <?php if (isset($error)): ?>
        <div style="background-color: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fca5a5;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Read-only fields -->
        <div class="input-group" style="opacity: 0.7;">
            <label>NIK (Tidak dapat diubah)</label>
            <input type="text" value="<?php echo htmlspecialchars($profil['nik']); ?>" readonly style="background: #e5e7eb; cursor: not-allowed;">
        </div>
        
        <div class="input-group" style="opacity: 0.7;">
            <label>Nama Lengkap (Tidak dapat diubah)</label>
            <input type="text" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" readonly style="background: #e5e7eb; cursor: not-allowed;">
        </div>

        <!-- Editable fields -->
        <div class="input-group">
            <label for="pekerjaan">Pekerjaan</label>
            <input type="text" id="pekerjaan" name="pekerjaan" placeholder="Contoh: Wiraswasta" value="<?php echo htmlspecialchars($_POST['pekerjaan'] ?? $profil['pekerjaan']); ?>">
        </div>

        <div class="input-group">
            <label for="alamat">Alamat Lengkap</label>
            <input type="text" id="alamat" name="alamat" required placeholder="Masukkan alamat domisili" value="<?php echo htmlspecialchars($_POST['alamat'] ?? $profil['alamat']); ?>">
        </div>

        <div class="input-group">
            <label for="no_telepon">No. Telepon / WhatsApp</label>
            <input type="text" id="no_telepon" name="no_telepon" required placeholder="Contoh: 08123456789" value="<?php echo htmlspecialchars($_POST['no_telepon'] ?? $profil['no_telepon']); ?>">
        </div>

        <div class="input-group">
            <label for="email">Alamat Email</label>
            <input type="email" id="email" name="email" required placeholder="Contoh: email@domain.com" value="<?php echo htmlspecialchars($_POST['email'] ?? $profil['email']); ?>">
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <a href="index.php" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none; padding: 14px; border-radius: 10px; border: 1px solid var(--color-blue); color: var(--color-blue); font-weight: 600;">Batal</a>
            <button type="submit" style="flex: 1; background: linear-gradient(135deg, var(--color-slate), var(--color-blue)); color: white; border: none; border-radius: 10px; padding: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(109, 114, 195, 0.3);">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . "/../../layouts/customer/footer.php"; ?>
