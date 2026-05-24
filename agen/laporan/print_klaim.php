<?php
session_start();
require_once __DIR__ . "/../../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Agen') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../../index.php';</script>";
    exit;
}

$username = $_SESSION['username'];
$stmtUser = $conn->prepare("SELECT a.id_agen, a.nama_agen, a.kode_agen FROM users u JOIN agen a ON u.id_agen = a.id_agen WHERE u.username = ?");
$stmtUser->execute([$username]);
$agen = $stmtUser->fetch();
$id_agen = $agen['id_agen'];

$bulan = $_GET['bulan'] ?? date('Y-m');
$namaBulan = date('F Y', strtotime($bulan . '-01'));

// Ambil data klaim bulan ini
$stmtKlaim = $conn->prepare("
    SELECT km.no_klaim, km.tanggal_masuk, km.total_tagihan_faskes as total_tagihan, km.status_klaim,
           p.no_polis, pp.nama_lengkap as nama_nasabah
    FROM klaim_medis km
    JOIN polis p ON km.no_polis = p.no_polis
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    WHERE p.id_agen = ? AND DATE_FORMAT(km.tanggal_masuk, '%Y-%m') = ?
    ORDER BY km.tanggal_masuk DESC
");
$stmtKlaim->execute([$id_agen, $bulan]);
$klaim_list = $stmtKlaim->fetchAll(PDO::FETCH_ASSOC);

$tglCetak = date('d F Y, H:i') . ' WIB';

$totalKlaimDiajukan = 0;
$totalKlaimDisetujui = 0;

foreach ($klaim_list as $klm) {
    $totalKlaimDiajukan += $klm['total_tagihan'];
    if ($klm['status_klaim'] === 'Approved') {
        $totalKlaimDisetujui += $klm['total_tagihan'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Klaim Nasabah - <?php echo htmlspecialchars($namaBulan); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #f59e0b;
            --primary-lt: #fffbeb;
            --ink: #1e293b;
            --ink-2: #475569;
            --ink-3: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --white: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --success-bg: #ecfdf5;
            --warning-bg: #fffbeb;
            --danger-bg: #fef2f2;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--ink); font-size: 10px; line-height: 1.45; padding: 20px 16px; }

        .no-print { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 18px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 22px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-ghost { background: #fff; color: var(--ink-2); border: 1.5px solid var(--line); }

        .paper { max-width: 820px; margin: 0 auto; background: var(--white); border-radius: 10px; box-shadow: 0 2px 24px rgba(0, 0, 0, .07); overflow: hidden; }
        .paper-stripe { height: 4px; background: linear-gradient(90deg, var(--primary) 0%, #fbbf24 100%); }
        .paper-head { padding: 16px 26px 13px; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--line); }

        .brand { display: flex; align-items: flex-start; gap: 11px; }
        .brand-icon { width: 42px; height: 42px; border-radius: 8px; background: var(--primary-lt); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .brand-name { font-size: 12.5px; font-weight: 800; color: var(--primary); line-height: 1.3; }
        .brand-addr { font-size: 8px; color: var(--ink-3); margin-top: 3px; }
        
        .section { padding: 12px 26px; border-bottom: 1px solid var(--line); }
        .section-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--ink-3); margin-bottom: 9px; display: flex; align-items: center; gap: 6px; }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--line); }
        
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { padding: 6px 9px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; color: var(--ink-3); border-bottom: 1px solid var(--line); background: var(--bg); }
        td { padding: 5px 9px; color: var(--ink-2); border-bottom: 1px solid var(--line); }
        th.r, td.r { text-align: right; }
        th.c, td.c { text-align: center; }

        .sb { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 8px; font-weight: 700; }
        .sb-v { background: var(--success-bg); color: var(--success); }
        .sb-p { background: var(--warning-bg); color: var(--warning); }
        .sb-r { background: var(--danger-bg); color: var(--danger); }

        .paper-foot { padding: 13px 26px 16px; display: flex; justify-content: space-between; align-items: flex-end; }
        .foot-note { font-size: 8px; color: var(--ink-3); max-width: 410px; }
        .sign-col { text-align: center; }
        .sign-line { width: 160px; border-bottom: 1px solid var(--ink-3); margin: 28px auto 4px; }
        .sign-name { font-size: 9px; font-weight: 700; color: var(--ink-2); }

        @media print {
            body { background: #fff; padding: 0; font-size: 9px; }
            .paper { box-shadow: none; }
            .no-print { display: none !important; }
            .paper-stripe { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
        <a href="index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>

    <div class="paper">
        <div class="paper-stripe"></div>
        <div class="paper-head">
            <div class="brand">
                <div class="brand-icon"><i class="fa-solid fa-file-medical"></i></div>
                <div>
                    <div class="brand-name">ASURANSIKU</div>
                    <div class="brand-addr">
                        Laporan Pengajuan Klaim<br>
                        Dicetak: <?php echo htmlspecialchars($tglCetak); ?>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; font-weight: 800; color: var(--ink); text-transform: uppercase;">LAPORAN KLAIM NASABAH</div>
                <div style="font-size: 10px; color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($namaBulan); ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-label">Informasi Keagenan</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 10px;">
                <div>
                    <div style="color: var(--ink-3);">Nama Agen</div>
                    <div style="font-weight: 700; color: var(--ink); font-size: 12px;"><?php echo htmlspecialchars($agen['nama_agen']); ?></div>
                </div>
                <div>
                    <div style="color: var(--ink-3);">Kode Agen</div>
                    <div style="font-weight: 700; color: var(--ink);"><?php echo htmlspecialchars($agen['kode_agen']); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-label">Riwayat Klaim Periode <?php echo htmlspecialchars($namaBulan); ?></div>
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>Nasabah (No Polis)</th>
                        <th>Tgl Pengajuan</th>
                        <th class="r">Nominal (IDR)</th>
                        <th class="c">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($klaim_list) > 0): ?>
                        <?php foreach($klaim_list as $i => $row): ?>
                            <tr>
                                <td class="c"><?php echo $i + 1; ?></td>
                                <td style="font-weight: 600; color: var(--ink);">
                                    <?php echo htmlspecialchars($row['nama_nasabah']); ?><br>
                                    <span style="font-size: 8px; color: var(--ink-3); font-weight: 400;"><?php echo htmlspecialchars($row['no_polis']); ?></span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_masuk'])); ?></td>
                                <td class="r" style="font-weight: 600;">Rp <?php echo number_format($row['total_tagihan'], 0, ',', '.'); ?></td>
                                <td class="c">
                                    <?php if($row['status_klaim'] === 'Approved'): ?>
                                        <span class="sb sb-v">Disetujui</span>
                                    <?php elseif($row['status_klaim'] === 'Rejected'): ?>
                                        <span class="sb sb-r">Ditolak</span>
                                    <?php else: ?>
                                        <span class="sb sb-p"><?php echo htmlspecialchars($row['status_klaim']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="c" style="padding: 15px;">Tidak ada klaim medis yang diajukan pada bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="r" style="font-size: 9px; font-weight: 800; color: var(--ink); padding-right: 15px;">TOTAL DIAJUKAN</td>
                        <td class="r" style="font-weight: 800; color: var(--ink);">Rp <?php echo number_format($totalKlaimDiajukan, 0, ',', '.'); ?></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="r" style="font-size: 9px; font-weight: 800; color: var(--success); padding-right: 15px;">TOTAL DISETUJUI</td>
                        <td class="r" style="font-weight: 800; color: var(--success);">Rp <?php echo number_format($totalKlaimDisetujui, 0, ',', '.'); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="paper-foot">
            <div class="foot-note">
                <div style="font-weight:600;color:var(--ink-2);margin-bottom:2px;">Keterangan Laporan</div>
                Laporan ini diterbitkan otomatis oleh sistem Asuransiku.<br>
                Nilai klaim disetujui dapat berubah jika masih ada klaim berstatus Pending/Investigasi.
            </div>
            <div class="sign-col">
                <div style="font-size:8px;color:var(--ink-3);margin-bottom:2px;">Disahkan Oleh,</div>
                <div style="font-size:8px;color:var(--ink-2);font-weight:600;">Sistem Asuransiku</div>
                <div class="sign-line"></div>
                <div class="sign-name">Asuransiku Keagenan</div>
            </div>
        </div>
    </div>
</body>
</html>
