<?php 
    include "db.php";
    include "layouts/public/header.php"; 
?>

<!-- Import style.css specifically for the layout below -->
<link rel="stylesheet" href="layouts/css/style.css?v=<?php echo time(); ?>">

<div class="page-header" style="background: linear-gradient(135deg, var(--color-dark), var(--color-blue)); padding: 80px 20px 60px; text-align: center; color: white;">
    <h1 style="font-size: 36px; margin-bottom: 15px; font-weight: 700; margin-top: 50px;">Syarat & Ketentuan</h1>
    <p style="font-size: 18px; color: rgba(255,255,255,0.9); max-width: 600px; margin: 0 auto;">Pahami hak dan kewajiban Anda sebagai pemegang polis di AsuransiKu.</p>
</div>



<div class="tc-container">
    <aside class="tc-sidebar">
        <h4>Daftar Isi</h4>
        <ul>
            <li><a href="#tc-1">1. Ketentuan Umum</a></li>
            <li><a href="#tc-2">2. Pembayaran Premi</a></li>
            <li><a href="#tc-3">3. Investigasi & Verifikasi</a></li>
            <li><a href="#tc-4">4. Penyakit Bawaan</a></li>
            <li><a href="#tc-5">5. Pengecualian Klaim</a></li>
            <li><a href="#tc-6">6. Masa Tunggu</a></li>
            <li><a href="#tc-7">7. Hak Pembatalan</a></li>
            <li><a href="#tc-8">8. Batas Waktu Klaim</a></li>
            <li><a href="#tc-9">9. Pembaruan Kebijakan</a></li>
            <li><a href="#tc-10">10. Hukum yang Berlaku</a></li>
        </ul>
        <div style="margin-top: 30px; text-align: center;">
            <a href="produk.php" class="btn btn-solid" style="width: 100%; display: block; padding: 12px; border-radius: 8px; color: #FFF;">Lihat Produk</a>
        </div>
    </aside>

    <div class="tc-content">
        <div id="tc-1" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-file-contract"></i></div> 1. Ketentuan Umum</h3>
            <p>Pemegang polis wajib memberikan informasi data diri yang benar, akurat, dan dapat dipertanggungjawabkan sesuai identitas resmi (KTP/Paspor). Segala bentuk pemalsuan data dapat membatalkan polis secara sepihak oleh perusahaan asuransi tanpa pengembalian premi.</p>
        </div>

        <div id="tc-2" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-money-bill-wave"></i></div> 2. Pembayaran Premi</h3>
            <p>Pembayaran premi harus dilakukan selambat-lambatnya pada tanggal jatuh tempo yang telah ditetapkan setiap bulannya. Keterlambatan pembayaran premi lebih dari masa tenggang (grace period) 30 hari akan mengakibatkan polis berstatus lapse (tidak aktif).</p>
        </div>

        <div id="tc-3" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-magnifying-glass"></i></div> 3. Investigasi & Verifikasi</h3>
            <p>Perusahaan Asuransi berhak melakukan verifikasi atau investigasi medis secara independen untuk setiap pengajuan klaim yang dinilai memerlukan peninjauan lebih lanjut, termasuk meminta rekam medis tambahan dari pihak fasilitas kesehatan.</p>
        </div>

        <div id="tc-4" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-staff-snake"></i></div> 4. Penyakit Bawaan & Pre-existing</h3>
            <p>Penyakit bawaan (congenital) atau penyakit yang sudah didiagnosis sebelum polis diterbitkan (pre-existing condition) akan tunduk pada masa tunggu (waiting period) khusus sesuai dengan pedoman polis yang dipilih pelanggan.</p>
        </div>

        <div id="tc-5" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-ban"></i></div> 5. Pengecualian Klaim</h3>
            <p>Klaim tidak akan dibayarkan untuk kasus perawatan medis yang berkaitan dengan tindakan melanggar hukum, percobaan bunuh diri, kesengajaan mencederai diri sendiri, konsumsi alkohol/narkotika, atau cedera akibat olahraga ekstrem yang tidak di-cover.</p>
        </div>

        <div id="tc-6" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-hourglass-half"></i></div> 6. Masa Tunggu (Waiting Period)</h3>
            <p>Masa tunggu asuransi kesehatan adalah 30 hari sejak tanggal terbit polis untuk penyakit umum, dan hingga 12 bulan untuk penyakit kritis atau penyakit khusus tertentu (seperti kanker, hernia, katarak) yang tertulis dalam lampiran polis.</p>
        </div>

        <div id="tc-7" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div> 7. Hak Pembatalan (Free-Look)</h3>
            <p>Pemegang polis memiliki hak untuk membatalkan polis dalam kurun waktu 14 hari kerja (free-look period) setelah dokumen polis diterbitkan. Jika dibatalkan pada masa ini, premi akan dikembalikan sepenuhnya tanpa potongan apapun.</p>
        </div>

        <div id="tc-8" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-calendar-check"></i></div> 8. Batas Waktu Pengajuan Klaim</h3>
            <p>Segala bentuk klaim reimbursement wajib diajukan maksimal 30 hari kalender sejak tanggal pasien keluar dari rumah sakit atau sejak kwitansi perawatan medis dikeluarkan oleh fasilitas kesehatan terkait. Lewat dari batas waktu, klaim dapat ditolak.</p>
        </div>

        <div id="tc-9" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-pen-to-square"></i></div> 9. Pembaruan Syarat & Ketentuan</h3>
            <p>Perusahaan Asuransi berhak untuk mengubah, memodifikasi, atau memperbarui syarat dan ketentuan ini sewaktu-waktu dengan memberikan pemberitahuan tertulis kepada pemegang polis sekurang-kurangnya 30 hari sebelum perubahan berlaku secara efektif.</p>
        </div>

        <div id="tc-10" class="tc-card">
            <h3><div class="tc-icon"><i class="fa-solid fa-scale-balanced"></i></div> 10. Hukum yang Berlaku</h3>
            <p>Seluruh ketentuan dalam polis dan perjanjian asuransi tunduk pada hukum yang berlaku di wilayah Republik Indonesia serta diawasi penuh oleh Otoritas Jasa Keuangan (OJK). Segala sengketa akan diselesaikan melalui jalur musyawarah atau badan arbitrase.</p>
        </div>
    </div>
</div>

<?php include "layouts/public/footer.php"; ?>
