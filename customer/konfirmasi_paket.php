<?php
    require_once __DIR__ . "/../db.php";
    require_once "../layouts/customer/header.php";
    
    if(!isset($_GET['id_produk'])) {
        echo "<script>alert('Pilih produk terlebih dahulu.'); window.location.href='../produk.php';</script>";
        exit;
    }
    
    $id_produk = intval($_GET['id_produk']);
    
    // Get product detail
    $stmt = $conn->prepare("SELECT * FROM produk_asuransi WHERE id_produk = ?");
    $stmt->execute([$id_produk]);
    $produk = $stmt->fetch();
    if(!$produk) {
        echo "<script>alert('Produk tidak ditemukan.'); window.location.href='../produk.php';</script>";
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

<div class="card">
    <h3><i class="fa-solid fa-cart-shopping"></i> Konfirmasi Pembelian Paket</h3>
    <p>Silakan lengkapi data dan setujui syarat ketentuan untuk mengkonfirmasi pilihan asuransi Anda.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
    <!-- Rincian Produk -->
    <div class="card" style="margin-bottom: 0;">
        <h4 style="color: var(--color-blue); margin-bottom: 15px;">Rincian Paket Asuransi</h4>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Produk</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Kategori</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($produk['jenis_kategori']); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Limit Tahunan</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">Rp <?php echo number_format($produk['limit_tahunan'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Premi Dasar (per bulan)</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right; font-weight: bold; color: var(--color-dark);">Rp <?php echo number_format($produk['premi_dasar'], 0, ',', '.'); ?></td>
            </tr>
        </table>
    </div>

    <!-- Form Data Diri -->
    <div class="card" style="margin-bottom: 0;">
        <h4 style="color: var(--color-blue); margin-bottom: 15px;">Data Pemegang Polis</h4>
        <form id="formKonfirmasi" action="proses_beli.php" method="POST">
            <input type="hidden" name="id_produk" value="<?php echo $id_produk; ?>">
            
            <?php if($is_new_pemegang): ?>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">NIK (16 Digit) *</label>
                    <input type="text" name="nik" required pattern="\d{16}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Nama Lengkap (Sesuai KTP) *</label>
                    <input type="text" name="nama_lengkap" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Tanggal Lahir *</label>
                    <input type="date" name="tanggal_lahir" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="L">Laki-Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Pekerjaan *</label>
                    <input type="text" name="pekerjaan" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Alamat Lengkap *</label>
                    <textarea name="alamat" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" rows="3"></textarea>
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>NIK</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($user_data['nik']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Nama Lengkap</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Tanggal Lahir</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo date('d M Y', strtotime($user_data['tanggal_lahir'])); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Alamat</strong></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($user_data['alamat']); ?></td>
                    </tr>
                </table>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Data diri Anda sudah terdaftar dan akan digunakan untuk pembuatan polis ini.</p>
            <?php endif; ?>

            <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; border: 1px solid #eee;">
                <label style="display: flex; align-items: flex-start; cursor: pointer; font-size: 14px;">
                    <input type="checkbox" id="chkSyarat" required style="margin-top: 4px; margin-right: 10px;">
                    <span>Saya telah membaca dan menyetujui <a href="#" id="btnModal" style="color: var(--color-blue); text-decoration: underline;">Syarat & Ketentuan</a> asuransi ini.</span>
                </label>
            </div>
            
            <button type="submit" id="btnSubmit" class="btn btn-solid" style="width: 100%; margin-top: 20px; opacity: 0.5; cursor: not-allowed;" disabled>Konfirmasi Paket</button>
        </form>
    </div>
</div>

<!-- Modal Syarat & Ketentuan -->
<div id="tncModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px); justify-content: center; align-items: center;">
    <div style="background: white; width: 90%; max-width: 650px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; position: relative; display: flex; flex-direction: column; max-height: 90vh;">
        <!-- Modal Header -->
        <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; color: var(--color-dark); font-size: 20px; font-weight: 700;"><i class="fa-solid fa-scale-balanced" style="color: var(--color-blue); margin-right: 10px;"></i>Syarat & Ketentuan Asuransi</h3>
            <span id="closeModal" style="font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">&times;</span>
        </div>
        
        <!-- Modal Body (Scrollable) -->
        <div style="padding: 25px; overflow-y: auto; font-size: 14px; line-height: 1.7; color: #475569;">
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
        <div style="padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #f8fafc; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" id="btnBatal" class="btn btn-outline" style="padding: 10px 20px; font-weight: 600;">Batal</button>
            <button type="button" id="btnMengerti" class="btn btn-solid" style="padding: 10px 24px; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.5);">Saya Setuju & Mengerti</button>
        </div>
    </div>
</div>



<?php include "../layouts/customer/footer.php"; ?>
