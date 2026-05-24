<?php
    require_once __DIR__ . "/../db.php";
    require_once "../layouts/customer/header.php";
    
    if(!isset($_GET['id_produk'])) {
        $_SESSION['toast_error'] = 'Pilih produk terlebih dahulu.';
        header("Location: ../produk.php");
        exit;
    }
    
    $id_produk = intval($_GET['id_produk']);
    
    // Get product detail
    $stmt = $conn->prepare("SELECT * FROM produk_asuransi WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $produk = $stmt->fetch();
    if(!$produk) {
        $_SESSION['toast_error'] = 'Produk tidak ditemukan.';
        header("Location: ../produk.php");
        exit;
    }
    
    // Get user & pemegang_polis data
    $id_user = $_SESSION['id_user'];
    $stmt = $conn->prepare("SELECT u.*, p.nik, p.nama_lengkap, p.tanggal_lahir, p.jenis_kelamin, p.pekerjaan, p.alamat 
                            FROM users u 
                            LEFT JOIN pemegang_polis p ON u.id_pemegang = p.id_pemegang 
                            WHERE u.id_user = ?");
    $stmt->execute([$id_user]);
    $user_data = $stmt->fetch();
    
    $is_new_pemegang = empty($user_data['id_pemegang']);
?>

<<div class="app-page-title" style="margin-bottom: 30px;">
    <div class="page-title-wrapper" style="display: flex; align-items: center; gap: 15px;">
        <div class="page-title-icon" style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); color: #3b82f6; font-size: 24px; display: flex; justify-content: center; align-items: center; width: 50px; height: 50px;">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
        <div>
            <h2 style="margin: 0; font-size: 20px; color: #1e293b; font-weight: 700;">Konfirmasi Pembelian Paket</h2>
            <div style="color: #64748b; font-size: 14px; margin-top: 4px;">Silakan lengkapi data dan setujui syarat ketentuan untuk mengkonfirmasi pilihan asuransi Anda.</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; align-items: start;">
    <!-- Rincian Produk -->
    <div class="admin-card animate-fade-in-up" style="border-top: 4px solid #3b82f6;">
        <div class="admin-card-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px 20px;">
            <h3 style="margin: 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-box-open" style="color: #3b82f6; margin-right: 8px;"></i> Rincian Paket Asuransi</h3>
        </div>
        <div class="admin-card-body" style="padding: 0;">
            <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Nama Produk</div>
                    <div style="font-size: 16px; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($produk['nama_produk']); ?></div>
                </div>
                <div style="background: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                    <?php echo htmlspecialchars($produk['jenis_kategori']); ?>
                </div>
            </div>
            
            <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; background: #fafaf9;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #ecfccb; color: #65a30d; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">Limit Tahunan Maksimal</div>
                        <div style="font-size: 18px; font-weight: 700; color: #1e293b;">Rp <?php echo number_format($produk['limit_tahunan'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <div style="padding: 20px; background: #fffbeb;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <div style="font-size: 13px; color: #d97706; margin-bottom: 4px;">Premi Dasar (Per Bulan)</div>
                        <div style="font-size: 22px; font-weight: 800; color: #b45309;">Rp <?php echo number_format($produk['premi_dasar'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Data Diri -->
    <div class="admin-card animate-fade-in-up delay-2">
        <div class="admin-card-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px 20px;">
            <h3 style="margin: 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-id-card" style="color: #3b82f6; margin-right: 8px;"></i> Data Pemegang Polis</h3>
        </div>
        <div class="admin-card-body" style="padding: 25px;">
            <form id="formKonfirmasi" action="proses_beli.php" method="POST">
                <input type="hidden" name="id_produk" value="<?php echo $id_produk; ?>">
                
                <?php if($is_new_pemegang): ?>
                    <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 0 8px 8px 0; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 5px 0; color: #1e3a8a; font-size: 14px;"><i class="fa-solid fa-circle-info" style="margin-right: 5px;"></i> Lengkapi Profil Anda</h4>
                        <p style="margin: 0; color: #1e40af; font-size: 13px;">Anda belum mendaftarkan profil pemegang polis. Data ini akan digunakan sebagai dasar kepemilikan polis asuransi Anda seterusnya.</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="input-group" style="grid-column: span 2;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">NIK (16 Digit) *</label>
                            <input type="text" name="nik" required minlength="16" maxlength="16" pattern="\d{16}" placeholder="Masukkan NIK Sesuai KTP" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px;">
                        </div>
                        
                        <div class="input-group" style="grid-column: span 2;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Nama Lengkap (Sesuai KTP) *</label>
                            <input type="text" name="nama_lengkap" required placeholder="Nama Lengkap Pemegang Polis" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px;">
                        </div>

                        <div class="input-group">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Tanggal Lahir *</label>
                            <input type="date" name="tanggal_lahir" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px;">
                        </div>

                        <div class="input-group">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px; background: white;">
                                <option value="">-- Pilih --</option>
                                <option value="L">Laki-Laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <div class="input-group" style="grid-column: span 2;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Pekerjaan *</label>
                            <input type="text" name="pekerjaan" required placeholder="Pekerjaan saat ini" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px;">
                        </div>

                        <div class="input-group" style="grid-column: span 2;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Alamat Lengkap *</label>
                            <textarea name="alamat" required placeholder="Alamat domisili saat ini" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px;" rows="3"></textarea>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="width: 40px; height: 40px; background: #e0f2fe; color: #0ea5e9; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 18px; margin-right: 15px;">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0 0 2px 0; color: #0f172a; font-size: 15px;">Profil Tersedia</h4>
                                <p style="margin: 0; font-size: 12px; color: #64748b;">Data berikut akan digunakan sebagai pemilik polis.</p>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px 15px; font-size: 13px;">
                            <div style="color: #64748b; font-weight: 500;">NIK</div>
                            <div style="color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($user_data['nik']); ?></div>
                            
                            <div style="color: #64748b; font-weight: 500;">Nama Lengkap</div>
                            <div style="color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></div>
                            
                            <div style="color: #64748b; font-weight: 500;">Tanggal Lahir</div>
                            <div style="color: #1e293b; font-weight: 600;"><?php echo date('d M Y', strtotime($user_data['tanggal_lahir'])); ?></div>
                            
                            <div style="color: #64748b; font-weight: 500;">Alamat</div>
                            <div style="color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($user_data['alamat']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 25px;">
                    <label style="display: flex; align-items: flex-start; cursor: pointer; font-size: 13px; color: #334155;">
                        <input type="checkbox" id="chkSyarat" required style="margin-top: 3px; margin-right: 12px; width: 16px; height: 16px; accent-color: #3b82f6;">
                        <span style="line-height: 1.5;">Saya telah membaca, memahami, dan menyetujui seluruh <a href="#" id="btnModal" style="color: #2563eb; text-decoration: none; font-weight: 600; border-bottom: 1px dashed #2563eb;">Syarat & Ketentuan</a> dari produk asuransi ini tanpa paksaan dari pihak manapun.</span>
                    </label>
                </div>
                
                <button type="submit" id="btnSubmit" class="btn" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 700; background: #3b82f6; color: white; border: none; border-radius: 8px; opacity: 0.5; cursor: not-allowed; transition: all 0.3s;" disabled>
                    <i class="fa-solid fa-file-signature" style="margin-right: 8px;"></i> Ajukan Pembelian Polis
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Syarat & Ketentuan -->
<div id="tncModal" style="display: none; position: fixed; z-index: 1000; inset: 0; background-color: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); justify-content: center; align-items: center; padding: 20px;">
    <div class="admin-card animate-fade-in-up" style="background: white; width: 100%; max-width: 650px; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; max-height: 85vh;">
        <!-- Modal Header -->
        <div class="admin-card-header" style="padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; color: #1e293b; font-size: 18px; font-weight: 700;"><i class="fa-solid fa-scale-balanced" style="color: #3b82f6; margin-right: 10px;"></i>Syarat & Ketentuan Asuransi</h3>
            <button id="closeModal" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: color 0.2s; padding: 0;">&times;</button>
        </div>
        
        <!-- Modal Body (Scrollable) -->
        <div class="admin-card-body" style="padding: 25px; overflow-y: auto; font-size: 14px; line-height: 1.7; color: #475569; background: white;">
            <ol style="padding-left: 20px; margin: 0;">
                <li style="margin-bottom: 15px;"><strong>Kebenaran Data:</strong> Pemegang polis wajib memberikan informasi data diri yang benar, akurat, dan dapat dipertanggungjawabkan sesuai identitas resmi (KTP/Paspor). Segala bentuk pemalsuan data dapat membatalkan polis.</li>
                <li style="margin-bottom: 15px;"><strong>Kewajiban Pembayaran:</strong> Pembayaran premi harus dilakukan selambat-lambatnya pada tanggal jatuh tempo yang telah ditetapkan setiap bulannya untuk menjaga polis tetap Inforce.</li>
                <li style="margin-bottom: 15px;"><strong>Masa Tenggang (Grace Period):</strong> Keterlambatan pembayaran premi lebih dari masa tenggang 30 hari kalender akan mengakibatkan polis berstatus lapse (tidak aktif).</li>
                <li style="margin-bottom: 15px;"><strong>Hak Verifikasi:</strong> Perusahaan Asuransi berhak melakukan verifikasi, investigasi medis, atau meminta dokumen tambahan untuk setiap klaim yang diajukan.</li>
                <li style="margin-bottom: 15px;"><strong>Pre-existing Condition:</strong> Penyakit bawaan (congenital) atau penyakit yang sudah ada sebelum polis terbit tunduk pada masa tunggu (waiting period) sesuai ketentuan buku polis.</li>
                <li style="margin-bottom: 15px;"><strong>Pengecualian Klaim:</strong> Klaim tidak akan dibayarkan untuk kasus yang berkaitan dengan tindakan melanggar hukum, percobaan bunuh diri, kesengajaan mencederai diri, atau bencana alam skala nasional.</li>
                <li style="margin-bottom: 15px;"><strong>Masa Tunggu (Waiting Period):</strong> Masa tunggu asuransi kesehatan adalah 30 hari sejak tanggal terbit polis untuk penyakit umum, dan 12 bulan untuk penyakit kritis tertentu.</li>
                <li style="margin-bottom: 15px;"><strong>Masa Pelajari Polis (Free-Look Period):</strong> Pemegang polis dapat mengajukan pembatalan polis dalam kurun waktu 14 hari kerja setelah dokumen polis diterima untuk mendapatkan pengembalian premi penuh.</li>
                <li style="margin-bottom: 15px;"><strong>Batas Waktu Klaim:</strong> Segala bentuk klaim wajib diajukan maksimal 30 hari kalender sejak tanggal perawatan selesai atau kwitansi dikeluarkan oleh fasilitas kesehatan.</li>
                <li style="margin-bottom: 0;"><strong>Perubahan Kebijakan:</strong> Perusahaan Asuransi dapat mengubah syarat dan ketentuan sewaktu-waktu dengan memberikan pemberitahuan tertulis kepada pemegang polis sekurang-kurangnya 30 hari sebelumnya.</li>
            </ol>
        </div>
        
        <!-- Modal Footer -->
        <div style="padding: 20px 25px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" id="btnBatal" class="btn btn-ghost" style="padding: 10px 20px; font-weight: 600; border-radius: 8px;">Batal</button>
            <button type="button" id="btnMengerti" class="btn" style="background: #3b82f6; color: white; padding: 10px 24px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);">Saya Setuju & Mengerti</button>
        </div>
    </div>
</div>

<script>
    // JS for form validation and modal is handled in script.js or we can keep it simple here
    document.addEventListener("DOMContentLoaded", () => {
        const chkSyarat = document.getElementById("chkSyarat");
        const btnSubmit = document.getElementById("btnSubmit");
        const tncModal = document.getElementById("tncModal");
        const btnModal = document.getElementById("btnModal");
        const btnBatal = document.getElementById("btnBatal");
        const btnMengerti = document.getElementById("btnMengerti");
        const closeModal = document.getElementById("closeModal");

        // Toggle submit button
        chkSyarat.addEventListener("change", function() {
            if(this.checked) {
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = "1";
                btnSubmit.style.cursor = "pointer";
                btnSubmit.style.boxShadow = "0 10px 15px -3px rgba(59, 130, 246, 0.4)";
            } else {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = "0.5";
                btnSubmit.style.cursor = "not-allowed";
                btnSubmit.style.boxShadow = "none";
            }
        });

        // Open modal
        btnModal.addEventListener("click", function(e) {
            e.preventDefault();
            tncModal.style.display = "flex";
            const card = tncModal.querySelector('.admin-card');
            card.style.animation = 'none';
            card.offsetHeight; 
            card.style.animation = null;
        });

        // Close modal actions
        const hideModal = () => tncModal.style.display = "none";
        
        closeModal.addEventListener("click", hideModal);
        btnBatal.addEventListener("click", hideModal);
        
        tncModal.addEventListener("click", function(e) {
            if(e.target === tncModal) hideModal();
        });

        // Agree button
        btnMengerti.addEventListener("click", function() {
            chkSyarat.checked = true;
            chkSyarat.dispatchEvent(new Event('change')); // trigger the event to update submit button
            hideModal();
        });
    });
</script>

<?php include "../layouts/customer/footer.php"; ?>
