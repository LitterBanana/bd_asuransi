<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";
    
    $id_user = $_SESSION['id_user'] ?? 0;
    $no_polis = $_GET['no_polis'] ?? '';

    if (empty($no_polis)) {
        echo "<script>alert('Nomor Polis tidak ditemukan.'); window.location.href='index.php';</script>";
        exit;
    }

    // Verifikasi kepemilikan polis dan jenis_kategori = 'Keluarga'
    $stmt = $conn->prepare("SELECT p.no_polis, pr.jenis_kategori 
                            FROM polis p 
                            JOIN users u ON p.id_pemegang = u.id_pemegang
                            JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
                            WHERE p.no_polis = ? AND u.id_user = ?");
    $stmt->execute([$no_polis, $id_user]);
    $polis = $stmt->fetch();

    if (!$polis) {
        echo "<script>alert('Akses ditolak. Polis tidak valid atau bukan milik Anda.'); window.location.href='index.php';</script>";
        exit;
    }

    if ($polis['jenis_kategori'] !== 'Keluarga') {
        echo "<script>alert('Fitur ini hanya tersedia untuk jenis polis Keluarga.'); window.location.href='index.php';</script>";
        exit;
    }

    // Proses Submit Form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                
                echo "<script>alert('Anggota keluarga berhasil ditambahkan!'); window.location.href='index.php';</script>";
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "NIK tersebut sudah terdaftar di sistem.";
                } else {
                    error_log("Error insert tanggungan: " . $e->getMessage());
                    $error = "Terjadi kesalahan sistem saat menyimpan data.";
                }
            }
        }
    }
?>

<div class="card" style="max-width: 600px; margin: 20px auto;">
    <div style="border-bottom: 2px solid var(--color-light); padding-bottom: 15px; margin-bottom: 20px;">
        <h3 style="color: var(--color-dark); margin: 0;"><i class="fa-solid fa-user-plus" style="color: var(--color-aqua);"></i> Tambah Anggota Keluarga</h3>
        <p style="color: var(--color-slate); font-size: 14px; margin-top: 5px;">Polis No: <?php echo htmlspecialchars($no_polis); ?></p>
    </div>

    <?php if (isset($error)): ?>
        <div style="background-color: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fca5a5;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label for="nik">Nomor Induk Kependudukan (NIK)</label>
            <input type="text" id="nik" name="nik" required minlength="16" maxlength="16" pattern="\d{16}" title="Masukkan 16 digit NIK" placeholder="Masukkan 16 digit NIK" value="<?php echo htmlspecialchars($_POST['nik'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <label for="nama_lengkap">Nama Lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" required placeholder="Sesuai KTP/KK" value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <label for="hubungan">Hubungan dengan Pemegang Polis</label>
            <select id="hubungan" name="hubungan" required style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; font-family: inherit; background: #f9fafb; color: var(--color-dark);">
                <option value="">-- Pilih Hubungan --</option>
                <option value="Suami" <?php echo (isset($_POST['hubungan']) && $_POST['hubungan'] == 'Suami') ? 'selected' : ''; ?>>Suami</option>
                <option value="Istri" <?php echo (isset($_POST['hubungan']) && $_POST['hubungan'] == 'Istri') ? 'selected' : ''; ?>>Istri</option>
                <option value="Anak" <?php echo (isset($_POST['hubungan']) && $_POST['hubungan'] == 'Anak') ? 'selected' : ''; ?>>Anak</option>
            </select>
        </div>

        <div class="input-group">
            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="date" id="tanggal_lahir" name="tanggal_lahir" required value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>">
        </div>

        <div class="input-group">
            <label for="jenis_kelamin">Jenis Kelamin</label>
            <select id="jenis_kelamin" name="jenis_kelamin" required style="width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; font-family: inherit; background: #f9fafb; color: var(--color-dark);">
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="L" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                <option value="P" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
            </select>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <a href="index.php" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none; padding: 14px; border-radius: 10px; border: 1px solid var(--color-blue); color: var(--color-blue); font-weight: 600;">Batal</a>
            <button type="submit" style="flex: 1; background: linear-gradient(135deg, var(--color-slate), var(--color-blue)); color: white; border: none; border-radius: 10px; padding: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(109, 114, 195, 0.3);">Simpan Anggota</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . "/../../layouts/customer/footer.php"; ?>
