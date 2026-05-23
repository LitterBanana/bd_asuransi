<?php 
    include "db.php";
    include "layouts/public/header.php"; 
?>

<div class="page-header">
    <h1>Fasilitas Kesehatan Rekanan</h1>
    <p>Kami bekerja sama dengan ratusan rumah sakit dan klinik terpercaya di seluruh Indonesia.</p>
</div>

<section class="page-content" style="text-align: center;">
    <h2 style="margin-bottom: 20px; color: var(--color-dark);">Temukan Dokter dan Rumah Sakit Terdekat</h2>
    <p style="max-width: 600px; margin: 0 auto 40px; color: #666;">
        Jaringan fasilitas kesehatan AsuransiKu terus bertumbuh untuk memastikan Anda selalu mendapatkan penanganan terbaik dengan fasilitas bebas uang muka (cashless).
    </p>
    
    <div style="background: var(--color-light); padding: 40px; border-radius: 16px; display: inline-block; width: 100%; max-width: 800px; margin-bottom: 50px;">
        <i class="fa-solid fa-map-location-dot" style="font-size: 60px; color: var(--color-aqua); margin-bottom: 20px;"></i>
        <h3>Daftar Fasilitas Kesehatan (Live Data)</h3>
        <p style="margin-bottom: 20px; color: #555;">Berikut adalah beberapa fasilitas kesehatan yang telah tergabung dalam jaringan kami:</p>
        
        <div style="text-align: left; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background-color: var(--color-dark); color: white;">
                        <th style="padding: 15px; text-align: left;">Nama Faskes</th>
                        <th style="padding: 15px; text-align: left;">Tingkat</th>
                        <th style="padding: 15px; text-align: left;">Kota</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $query = "SELECT * FROM faskes WHERE status_kerjasama = 'Aktif' ORDER BY kota ASC";
                        $result = mysqli_query($conn, $query);

                        if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr style='border-bottom: 1px solid #eee;'>";
                                echo "<td style='padding: 15px;'><strong>" . htmlspecialchars($row['nama_faskes']) . "</strong><br><small style='color: #888;'>" . htmlspecialchars($row['alamat']) . "</small></td>";
                                echo "<td style='padding: 15px;'><span style='background: var(--color-light); color: var(--color-slate); padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;'>" . htmlspecialchars($row['tingkat_faskes']) . "</span></td>";
                                echo "<td style='padding: 15px;'>" . htmlspecialchars($row['kota']) . "</td>";
                                echo "<td style='padding: 15px;'><span style='color: #4CAF50; font-weight: bold;'><i class='fa-solid fa-check-circle'></i> " . htmlspecialchars($row['status_kerjasama']) . "</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='padding: 20px; text-align: center;'>Tidak ada faskes rekanan aktif.</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include "layouts/public/footer.php"; ?>
