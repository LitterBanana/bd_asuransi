<?php
    require_once __DIR__ . "/../../db.php";
    require_once __DIR__ . "/../../layouts/customer/header.php";
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0 0 5px 0; color: #1e293b; font-size: 22px;">Fasilitas Kesehatan (Faskes) Rekanan</h2>
    <p style="margin: 0; color: #64748b; font-size: 14px;">Daftar rumah sakit dan klinik yang bekerja sama dengan kami. Gunakan fasilitas ini untuk berobat menggunakan asuransi Anda.</p>
</div>

<?php
    $stmt = $conn->prepare("
        SELECT 
            kode_faskes, nama_faskes, tingkat_faskes, alamat, kota
        FROM faskes
        WHERE status_kerjasama = 'Aktif'
        ORDER BY kota ASC, nama_faskes ASC
    ");
    $stmt->execute();
    $faskes_list = $stmt->fetchAll();

    if (count($faskes_list) > 0) {
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Daftar Faskes Rekanan Aktif</h3>
    </div>
    <div class="admin-card-body" style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama Fasilitas</th>
                    <th>Tingkat</th>
                    <th>Kota / Kabupaten</th>
                    <th>Alamat Lengkap</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faskes_list as $faskes): 
                    $icon = 'fa-hospital';
                    $icon_bg = '#eff6ff';
                    $icon_color = '#3b82f6';
                    if (strpos($faskes['tingkat_faskes'], 'Klinik') !== false) {
                        $icon = 'fa-house-medical';
                        $icon_bg = '#ecfdf5';
                        $icon_color = '#10b981';
                    }
                ?>
                <tr>
                    <td style="font-weight: 600; color: #1e293b;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 35px; height: 35px; border-radius: 8px; background: <?php echo $icon_bg; ?>; color: <?php echo $icon_color; ?>; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($faskes['nama_faskes']); ?>
                                <div style="font-size: 12px; color: #64748b; font-weight: normal;">Kode: <?php echo htmlspecialchars($faskes['kode_faskes']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="display: inline-block; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                            <?php echo htmlspecialchars($faskes['tingkat_faskes']); ?>
                        </span>
                    </td>
                    <td style="font-weight: 500; color: #1e293b;">
                        <i class="fa-solid fa-location-dot" style="color: #cbd5e1; margin-right: 5px;"></i> <?php echo htmlspecialchars($faskes['kota']); ?>
                    </td>
                    <td style="font-size: 13px; color: #64748b; max-width: 300px; line-height: 1.5;">
                        <?php echo htmlspecialchars($faskes['alamat']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
    } else {
?>
    <div class="empty-state" style="padding: var(--space-8); text-align: center;">
        <div class="empty-state-icon" style="font-size: 2.5rem; margin-bottom: var(--space-2); color: var(--color-slate);"><i class="fa-solid fa-hospital-user"></i></div>
        <h3 class="empty-state-title" style="margin-bottom: var(--space-2);">Data Faskes Tidak Ditemukan</h3>
        <p class="empty-state-text" style="color: var(--color-text-secondary);">Saat ini tidak ada daftar fasilitas kesehatan rekanan yang tersedia.</p>
    </div>
<?php
    }
?>

<?php require_once __DIR__ . "/../../layouts/customer/footer.php"; ?>
