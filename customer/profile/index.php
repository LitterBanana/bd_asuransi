<?php
$base_path = "../../";
include "../../layouts/customer/header.php";
include "../../db.php";

$username = $_SESSION['username'] ?? '';
if (!$username) {
    echo "<script>window.location.href='../../login.php';</script>";
    exit;
}

// Ensure upload directory exists
$uploadDir = '../../uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    $password = $_POST['password']; // optional

    try {
        $conn->beginTransaction();

        // Get user details
        $stmtUser = $conn->prepare("SELECT id_user, id_pemegang, foto_profil, id_agen FROM users WHERE username = ?");
        $stmtUser->execute([$username]);
        $currUser = $stmtUser->fetch();
        
        $id_pemegang = $currUser['id_pemegang'];
        $foto_profil = $currUser['foto_profil'];

        $id_agen = $currUser['id_agen'];

        // Handle File Upload
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto_profil']['tmp_name'];
            $fileName = time() . '_' . basename($_FILES['foto_profil']['name']);
            $targetFile = $uploadDir . $fileName;
            
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
                if (move_uploaded_file($fileTmp, $targetFile)) {
                    // Hapus foto lama jika ada
                    if ($foto_profil && file_exists($uploadDir . $foto_profil)) {
                        unlink($uploadDir . $foto_profil);
                    }
                    $foto_profil = $fileName;
                }
            } else {
                throw new Exception("Format foto tidak valid. Gunakan JPG atau PNG.");
            }
        }

        // Update atau Insert pemegang_polis
        if (empty($id_pemegang)) {
            $nik = trim($_POST['nik'] ?? '');
            $tgl_lahir = $_POST['tanggal_lahir'] ?? '';
            $jk = $_POST['jenis_kelamin'] ?? '';

            if(empty($nik) || empty($tgl_lahir) || empty($jk)) {
                throw new Exception("Lengkapi NIK, Tanggal Lahir, dan Jenis Kelamin untuk profil pertama kali.");
            }

            $stmtPP = $conn->prepare("INSERT INTO pemegang_polis (id_agen, nik, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, no_telepon, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtPP->execute([$id_agen, $nik, $nama, $tgl_lahir, $jk, $alamat, $telepon, $email]);
            $id_pemegang = $conn->lastInsertId();

            // Link ke users
            $stmtLink = $conn->prepare("UPDATE users SET id_pemegang = ? WHERE username = ?");
            $stmtLink->execute([$id_pemegang, $username]);
        } else {
            $stmtPP = $conn->prepare("UPDATE pemegang_polis SET nama_lengkap = ?, email = ?, no_telepon = ?, alamat = ? WHERE id_pemegang = ?");
            $stmtPP->execute([$nama, $email, $telepon, $alamat, $id_pemegang]);
        }

        // Update users
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtUsr = $conn->prepare("UPDATE users SET email = ?, no_telp = ?, password = ?, foto_profil = ? WHERE username = ?");
            $stmtUsr->execute([$email, $telepon, $hashedPassword, $foto_profil, $username]);
        } else {
            $stmtUsr = $conn->prepare("UPDATE users SET email = ?, no_telp = ?, foto_profil = ? WHERE username = ?");
            $stmtUsr->execute([$email, $telepon, $foto_profil, $username]);
        }

        $conn->commit();
        $message = "<div class='alert alert-success' style='padding: 15px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px;'>Profil berhasil diperbarui.</div>";

    } catch (Exception $e) {
        $conn->rollBack();
        $message = "<div class='alert alert-danger' style='padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;'>Gagal memperbarui profil: " . $e->getMessage() . "</div>";
    }
}

// Fetch Latest Data
$stmt = $conn->prepare("
    SELECT u.foto_profil, u.email as u_email, u.no_telp as u_telp, 
           p.nama_lengkap, p.email, p.no_telepon, p.alamat, p.nik, p.tanggal_lahir, p.pekerjaan, p.jenis_kelamin 
    FROM users u 
    LEFT JOIN pemegang_polis p ON u.id_pemegang = p.id_pemegang 
    WHERE u.username = ?
");
$stmt->execute([$username]);
$profile = $stmt->fetch();

// Provide defaults if no profile exists yet
$profile['nama_lengkap'] = $profile['nama_lengkap'] ?? $username;
$profile['email'] = $profile['email'] ?? $profile['u_email'] ?? '';
$profile['no_telepon'] = $profile['no_telepon'] ?? $profile['u_telp'] ?? '';
$profile['alamat'] = $profile['alamat'] ?? '';
$profile['nik'] = $profile['nik'] ?? '';
$profile['tanggal_lahir'] = $profile['tanggal_lahir'] ?? '';
$profile['jenis_kelamin'] = $profile['jenis_kelamin'] ?? '';
$profile['pekerjaan'] = $profile['pekerjaan'] ?? '';

$has_profile = !empty($profile['nik']);
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <h2 style="margin: 0 0 5px 0; color: #0f172a; font-size: 24px; font-weight: 600; letter-spacing: -0.5px;">Account Settings</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Manage your profile information and account security.</p>
    </div>

    <?php echo $message; ?>

    <div class="profile-layout">
        
        <!-- Left Sidebar: Profile Summary -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 24px; text-align: center;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: #f1f5f9; margin: 0 auto 16px auto; overflow: hidden; border: 4px solid #ffffff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <?php if(!empty($profile['foto_profil'])): ?>
                    <img src="../../uploads/profiles/<?php echo htmlspecialchars($profile['foto_profil']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 36px; color: #94a3b8;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h3 style="margin: 0 0 4px 0; color: #0f172a; font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($profile['nama_lengkap']); ?></h3>
            <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">Customer</p>
            
            <div style="border-top: 1px solid #f1f5f9; padding-top: 16px; text-align: left;">
                <div style="display: flex; align-items: center; margin-bottom: 12px; color: #475569; font-size: 13px;">
                    <i class="fa-regular fa-envelope" style="width: 20px; color: #94a3b8;"></i>
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($profile['email']); ?></span>
                </div>
                <div style="display: flex; align-items: center; color: #475569; font-size: 13px;">
                    <i class="fa-solid fa-phone" style="width: 20px; color: #94a3b8;"></i>
                    <span><?php echo htmlspecialchars($profile['no_telepon']); ?></span>
                </div>
            </div>
        </div>

        <!-- Right Side: Edit Form -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 600;">Personal Information</h3>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Update your personal details here.</p>
            </div>
            
            <form method="POST" enctype="multipart/form-data" style="padding: 24px;">
                
                <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: #f1f5f9; overflow: hidden; border: 1px solid #e2e8f0;">
                        <?php if(!empty($profile['foto_profil'])): ?>
                            <img id="preview-img" src="../../uploads/profiles/<?php echo htmlspecialchars($profile['foto_profil']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div id="preview-img-placeholder" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #94a3b8;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <img id="preview-img" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="foto_profil" style="display: inline-block; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                            Change Avatar
                        </label>
                        <input type="file" id="foto_profil" name="foto_profil" accept="image/jpeg, image/png" style="display: none;" onchange="previewImage(event)">
                        <div style="margin-top: 6px; font-size: 12px; color: #64748b;">JPG or PNG, max 2MB.</div>
                    </div>
                </div>

                <div class="profile-form-grid">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">NIK</label>
                        <input type="text" name="nik" value="<?php echo htmlspecialchars($profile['nik']); ?>" <?php echo $has_profile ? 'readonly' : 'required placeholder="Masukkan 16 digit NIK"'; ?> style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: <?php echo $has_profile ? '#f8fafc' : '#ffffff'; ?>; color: <?php echo $has_profile ? '#94a3b8' : '#0f172a'; ?>; font-size: 14px; outline: none; box-sizing: border-box; <?php echo $has_profile ? 'cursor: not-allowed;' : ''; ?>">
                    </div>
                    <?php if(!$has_profile): ?>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Jenis Kelamin</label>
                        <select name="jenis_kelamin" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box;">
                            <option value="">Pilih...</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Full Name</label>
                        <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($profile['nama_lengkap']); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Phone Number</label>
                        <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($profile['no_telepon']); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                    </div>
                    <div style="grid-column: 1 / -1; margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Home Address</label>
                        <textarea name="alamat" rows="3" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s; resize: vertical;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"><?php echo htmlspecialchars($profile['alamat']); ?></textarea>
                    </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; margin: 24px 0;"></div>
                
                <h4 style="margin: 0 0 16px 0; color: #0f172a; font-size: 16px; font-weight: 600;">Security Settings</h4>
                <div style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" style="background: #0f172a; color: #ffffff; padding: 10px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" onmouseover="this.style.background='#1e293b'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#0f172a'; this.style.transform='translateY(0)';">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const input = event.target;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const placeholder = document.getElementById('preview-img-placeholder');
            const img = document.getElementById('preview-img');
            if(placeholder) placeholder.style.display = 'none';
            img.style.display = 'block';
            img.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php
include "../../layouts/customer/footer.php";
?>
