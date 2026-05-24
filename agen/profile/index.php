<?php
$base_path = "../../";
include "../../layouts/agen/header.php";
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
    $nama = trim($_POST['nama_agen']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['no_telepon']);
    $password = $_POST['password']; // optional

    try {
        $conn->beginTransaction();

        // Get user details
        $stmtUser = $conn->prepare("SELECT id_user, id_agen, foto_profil FROM users WHERE username = ?");
        $stmtUser->execute([$username]);
        $currUser = $stmtUser->fetch();
        
        $id_agen = $currUser['id_agen'];
        $foto_profil = $currUser['foto_profil'];

        // Handle File Upload
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['foto_profil']['tmp_name'];
            $fileName = time() . '_' . basename($_FILES['foto_profil']['name']);
            $targetFile = $uploadDir . $fileName;
            
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
                if (move_uploaded_file($fileTmp, $targetFile)) {
                    // Hapus foto lama jika ada
                    if ($foto_profil && $foto_profil !== 'default.png' && file_exists($uploadDir . $foto_profil)) {
                        unlink($uploadDir . $foto_profil);
                    }
                    $foto_profil = $fileName;
                }
            } else {
                throw new Exception("Format foto tidak valid. Gunakan JPG atau PNG.");
            }
        }

        // Update agen
        $stmtAgen = $conn->prepare("UPDATE agen SET nama_agen = ?, no_telepon = ? WHERE id_agen = ?");
        $stmtAgen->execute([$nama, $telepon, $id_agen]);

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
        
        // Update session photo for header
        $_SESSION['foto_profil'] = $foto_profil;

    } catch (Exception $e) {
        $conn->rollBack();
        $message = "<div class='alert alert-danger' style='padding: 15px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px;'>Gagal memperbarui profil: " . $e->getMessage() . "</div>";
    }
}

// Fetch Latest Data
$stmt = $conn->prepare("
    SELECT u.foto_profil, u.email, a.nama_agen, a.kode_agen, a.no_telepon, a.persentase_komisi, a.created_at 
    FROM users u 
    JOIN agen a ON u.id_agen = a.id_agen 
    WHERE u.username = ?
");
$stmt->execute([$username]);
$profile = $stmt->fetch();
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <h2 style="margin: 0 0 5px 0; color: #0f172a; font-size: 24px; font-weight: 600; letter-spacing: -0.5px;">Account Settings</h2>
        <p style="margin: 0; color: #64748b; font-size: 14px;">Manage your agent profile information and account security.</p>
    </div>

    <?php echo $message; ?>

    <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; align-items: start;">
        
        <!-- Left Sidebar: Profile Summary -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); padding: 24px; text-align: center;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: #f1f5f9; margin: 0 auto 16px auto; overflow: hidden; border: 4px solid #ffffff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <?php if(!empty($profile['foto_profil']) && $profile['foto_profil'] !== 'default.png'): ?>
                    <img src="../../uploads/profiles/<?php echo htmlspecialchars($profile['foto_profil']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 36px; color: #94a3b8;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h3 style="margin: 0 0 4px 0; color: #0f172a; font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($profile['nama_agen']); ?></h3>
            <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">Agen Asuransi</p>
            
            <div style="border-top: 1px solid #f1f5f9; padding-top: 16px; text-align: left;">
                <div style="display: flex; align-items: center; margin-bottom: 12px; color: #475569; font-size: 13px;">
                    <i class="fa-solid fa-id-badge" style="width: 20px; color: #94a3b8;"></i>
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($profile['kode_agen']); ?></span>
                </div>
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
                        <?php if(!empty($profile['foto_profil']) && $profile['foto_profil'] !== 'default.png'): ?>
                            <img id="preview-img" src="../../uploads/profiles/<?php echo htmlspecialchars($profile['foto_profil']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div id="preview-img-placeholder" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #94a3b8;">
                                <i class="fa-solid fa-user-tie"></i>
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Kode Agen</label>
                        <input type="text" value="<?php echo htmlspecialchars($profile['kode_agen']); ?>" readonly style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; color: #94a3b8; font-size: 14px; outline: none; box-sizing: border-box; cursor: not-allowed;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Persentase Komisi</label>
                        <input type="text" value="<?php echo floatval($profile['persentase_komisi']); ?>%" readonly style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; color: #94a3b8; font-size: 14px; outline: none; box-sizing: border-box; cursor: not-allowed;">
                    </div>
                    <div style="grid-column: 1 / -1; margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Nama Lengkap</label>
                        <input type="text" name="nama_agen" value="<?php echo htmlspecialchars($profile['nama_agen']); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px; font-weight: 500;">Phone Number</label>
                        <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($profile['no_telepon']); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-size: 14px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 1px #3b82f6';" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
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
include "../../layouts/agen/footer.php";
?>
