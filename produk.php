<?php 
    include "db.php";
    include "layouts/public/header.php"; 
?>

<div class="page-header">
    <h1>Produk Asuransi</h1>
    <p>Pilih paket perlindungan yang paling sesuai untuk Anda dan keluarga.</p>
</div>

<section class="page-content">
    <div class="products-grid">
        <?php
            $query = "SELECT * FROM produk_asuransi ORDER BY premi_dasar ASC";
            $result = mysqli_query($conn, $query);

            if(mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $nama_produk = htmlspecialchars($row['nama_produk']);
                    $jenis_kategori = htmlspecialchars($row['jenis_kategori']);
                    $limit = number_format($row['limit_tahunan'], 0, ',', '.');
                    $premi = number_format($row['premi_dasar'], 0, ',', '.');
                    
                    // Highlight logic
                    $highlight_style = "";
                    $badge = "";
                    if($jenis_kategori == 'Keluarga') {
                        $highlight_style = 'border: 2px solid var(--color-slate); transform: scale(1.05);';
                        $badge = '<div style="background: var(--color-slate); color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; margin-bottom: 15px;">PALING DIMINATI</div>';
                    }
        ?>
        <div class="product-card" style="<?php echo $highlight_style; ?>">
            <?php echo $badge; ?>
            <h3><?php echo $nama_produk; ?></h3>
            <div class="price">Rp <?php echo $premi; ?> <span>/ bulan</span></div>
            <p style="margin-bottom: 20px; color: #666; font-size: 14px;">Kategori Perlindungan: <strong><?php echo $jenis_kategori; ?></strong></p>
            <ul>
                <li><i class="fa-solid fa-check"></i> Limit Tahunan: Rp <?php echo $limit; ?></li>
                <li><i class="fa-solid fa-check"></i> Perlindungan Komprehensif</li>
                <li><i class="fa-solid fa-check"></i> Akses Jaringan Faskes Luas</li>
            </ul>
            <a href="register.php" class="<?php echo ($jenis_kategori == 'Keluarga') ? 'btn btn-solid' : 'btn btn-outline'; ?>" style="width: 100%; text-align: center;">Pilih Paket</a>
        </div>
        <?php 
                }
            } else {
                echo "<p style='text-align:center; width: 100%;'>Belum ada data produk asuransi.</p>";
            }
        ?>
    </div>
</section>

<?php include "layouts/public/footer.php"; ?>
