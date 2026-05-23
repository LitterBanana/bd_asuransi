<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";
    
    /** @var mysqli $conn */
    $id_user = $_SESSION['id_user'];
?>

<div class="card">
    <h3><i class="fa-solid fa-file-contract"></i> Detail Polis Asuransi Anda</h3>
    <p>Berikut adalah informasi lengkap mengenai polis asuransi Anda beserta tanggungan yang terdaftar.</p>
</div>

<?php
    // Ambil detail polis
    $stmt_polis = $conn->prepare("SELECT 
                                    p.no_polis, p.tanggal_terbit, p.tanggal_jatuh_tempo, p.status_polis,
                                    pr.nama_produk, pr.limit_tahunan, pr.jenis_kategori
                                  FROM polis p
                                  JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
                                  JOIN users u ON p.id_pemegang = u.id_pemegang
                                  WHERE u.id_user = ?");
    $stmt_polis->execute([$id_user]);
    $result_polis = $stmt_polis->fetchAll();

    if(count($result_polis) > 0) {
        foreach($result_polis as $polis) {
        
        $status_color = ($polis['status_polis'] == 'Inforce') ? 'var(--color-aqua)' : 'var(--color-slate)';
?>
    <!-- Wrapper per Polis -->
    <div style="background: white; border: 1px solid #ddd; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        
        <div style="border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: var(--color-dark); margin: 0;"><i class="fa-solid fa-shield-halved" style="color: var(--color-blue);"></i> Polis: <?php echo htmlspecialchars($polis['no_polis']); ?></h3>
            <span style="background: <?php echo $status_color; ?>; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 14px;">
                <?php echo htmlspecialchars($polis['status_polis']); ?>
            </span>
        </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div class="card" style="margin-bottom: 0;">
            <h4 style="color: var(--color-blue); margin-bottom: 15px;">Informasi Produk</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Nomor Polis</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($polis['no_polis']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Produk</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($polis['nama_produk']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Kategori</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo htmlspecialchars($polis['jenis_kategori']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Batas Limit Tahunan</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">Rp <?php echo number_format($polis['limit_tahunan'], 0, ',', '.'); ?></td>
                </tr>
            </table>
        </div>

        <div class="card" style="margin-bottom: 0; box-shadow: none; border: 1px solid #eee;">
            <h4 style="color: var(--color-blue); margin-bottom: 15px;">Status & Periode</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Tanggal Terbit</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo date('d M Y', strtotime($polis['tanggal_terbit'])); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><strong>Tanggal Jatuh Tempo</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo date('d M Y', strtotime($polis['tanggal_jatuh_tempo'])); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tanggungan Polis -->
    <div class="card" style="margin-top: 20px; box-shadow: none; border: 1px solid #eee;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h4 style="color: var(--color-blue); margin: 0;">Daftar Anggota Keluarga (Tanggungan)</h4>
            <?php if($polis['jenis_kategori'] === 'Keluarga'): ?>
                <a href="create.php?no_polis=<?php echo urlencode($polis['no_polis']); ?>" style="background: var(--color-blue); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: background 0.3s;"><i class="fa-solid fa-user-plus"></i> Tambah Anggota</a>
            <?php endif; ?>
        </div>
        
        <?php
            $stmt_tanggungan = $conn->prepare("SELECT 
                                                t.nama_lengkap, t.hubungan, t.tanggal_lahir, t.jenis_kelamin
                                              FROM tanggungan_polis t
                                              WHERE t.no_polis = ?");
            $stmt_tanggungan->execute([$polis['no_polis']]);
            $result_tanggungan = $stmt_tanggungan->fetchAll();

            if(count($result_tanggungan) > 0) {
        ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #eee;">
                    <thead>
                        <tr style="background-color: var(--color-dark); color: white;">
                            <th style="padding: 12px 15px; text-align: left;">Nama Lengkap</th>
                            <th style="padding: 12px 15px; text-align: left;">Hubungan</th>
                            <th style="padding: 12px 15px; text-align: left;">Jenis Kelamin</th>
                            <th style="padding: 12px 15px; text-align: left;">Tanggal Lahir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($result_tanggungan as $t_row): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px 15px;"><strong><?php echo htmlspecialchars($t_row['nama_lengkap']); ?></strong></td>
                                <td style="padding: 12px 15px;">
                                    <span style="background: var(--color-light); color: var(--color-slate); padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                                        <?php echo htmlspecialchars($t_row['hubungan']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 15px;"><?php echo ($t_row['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?></td>
                                <td style="padding: 12px 15px;"><?php echo date('d M Y', strtotime($t_row['tanggal_lahir'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
            } else {
                echo "<p style='color: #666;'>Tidak ada tanggungan anggota keluarga yang terdaftar pada polis ini.</p>";
            }
        ?>
    </div>
    
    </div> <!-- End of Wrapper per Polis -->

<?php
        } // End of while loop
    } else {
?>
    <div class="card" style="margin-top: 20px; text-align: center; padding: 40px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: #ff9800; margin-bottom: 15px;"></i>
        <h3 style="color: var(--color-dark); margin-bottom: 10px;">Polis Tidak Ditemukan</h3>
        <p style="color: #666;">Anda belum memiliki polis asuransi yang aktif atau belum terhubung dengan akun ini.</p>
    </div>
<?php
    }
?>

<?php
    include "../../layouts/customer/footer.php";
?>
