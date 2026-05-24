<?php
session_start();
require_once __DIR__ . "/../../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../../index.php';</script>";
    exit;
}

$bulan = $_GET['bulan'] ?? date('Y-m');
$namaBulan = date('F Y', strtotime($bulan . '-01'));

// Ambil data tagihan seluruh perusahaan pada bulan tersebut
$stmtTagihan = $conn->prepare("
    SELECT 
        t.no_tagihan, t.periode_bulan, t.jumlah_tagihan, t.status_tagihan, t.jatuh_tempo,
        p.no_polis, pp.nama_lengkap as nama_nasabah, a.nama_agen
    FROM tagihan_premi t
    JOIN polis p ON t.no_polis = p.no_polis
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    LEFT JOIN agen a ON p.id_agen = a.id_agen
    WHERE t.periode_bulan = ?
    ORDER BY t.jatuh_tempo ASC
");
$stmtTagihan->execute([$bulan]);
$tagihan_list = $stmtTagihan->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pendapatan dari pembayaran yang masuk di bulan tersebut
$stmtPendapatan = $conn->prepare("
    SELECT pb.nominal_bayar, pb.tanggal_bayar, pb.no_tagihan, pp.nama_lengkap, a.nama_agen
    FROM pembayaran_premi pb
    JOIN tagihan_premi tp ON pb.no_tagihan = tp.no_tagihan
    JOIN polis p ON tp.no_polis = p.no_polis 
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    LEFT JOIN agen a ON p.id_agen = a.id_agen
    WHERE pb.status_pembayaran = 'Verified' AND DATE_FORMAT(pb.tanggal_bayar, '%Y-%m') = ?
    ORDER BY pb.tanggal_bayar ASC
");
$stmtPendapatan->execute([$bulan]);
$pembayaran_list = $stmtPendapatan->fetchAll(PDO::FETCH_ASSOC);

$totalPendapatan = 0;
foreach ($pembayaran_list as $kom) {
    $totalPendapatan += $kom['nominal_bayar'];
}

$tglCetak = date('d F Y, H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendapatan Perusahaan - <?php echo htmlspecialchars($namaBulan); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-lt: #eff6ff;
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
        .paper-stripe { height: 4px; background: linear-gradient(90deg, var(--primary) 0%, #2563eb 100%); }
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
                <div class="brand-icon"><i class="fa-solid fa-building-columns"></i></div>
                <div>
                    <div class="brand-name">ASURANSIKU</div>
                    <div class="brand-addr">
                        Laporan Operasional Pusat<br>
                        Dicetak: <?php echo htmlspecialchars($tglCetak); ?>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; font-weight: 800; color: var(--ink); text-transform: uppercase;">LAPORAN PENDAPATAN</div>
                <div style="font-size: 10px; color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($namaBulan); ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-label">Rekap Tagihan Keseluruhan Periode <?php echo htmlspecialchars($namaBulan); ?></div>
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>Nasabah</th>
                        <th>Agen / Cabang</th>
                        <th>No Tagihan / Polis</th>
                        <th>Jatuh Tempo</th>
                        <th class="r">Nominal (IDR)</th>
                        <th class="c">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tagihan_list) > 0): ?>
                        <?php foreach($tagihan_list as $i => $row): ?>
                            <tr>
                                <td class="c"><?php echo $i + 1; ?></td>
                                <td style="font-weight: 600; color: var(--ink);"><?php echo htmlspecialchars($row['nama_nasabah']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_agen'] ?? 'Kantor Pusat'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['no_tagihan']); ?><br>
                                    <span style="font-size: 8px; color: var(--ink-3);"><?php echo htmlspecialchars($row['no_polis']); ?></span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['jatuh_tempo'])); ?></td>
                                <td class="r" style="font-weight: 600;">Rp <?php echo number_format($row['jumlah_tagihan'], 0, ',', '.'); ?></td>
                                <td class="c">
                                    <?php if($row['status_tagihan'] === 'Paid'): ?>
                                        <span class="sb sb-v">Lunas</span>
                                    <?php elseif($row['status_tagihan'] === 'Unpaid'): ?>
                                        <span class="sb sb-p">Unpaid</span>
                                    <?php else: ?>
                                        <span class="sb sb-r">Overdue</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="c" style="padding: 15px;">Tidak ada tagihan yang diterbitkan pada bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section" style="background: var(--bg);">
            <div class="section-label" style="color: var(--primary);">Rincian Pendapatan Premi (Gross Revenue) Bulan Ini</div>
            <p style="font-size: 9px; color: var(--ink-3); margin-bottom: 10px;">Berdasarkan pembayaran dari seluruh nasabah yang telah diverifikasi (Paid) pada bulan <?php echo htmlspecialchars($namaBulan); ?>.</p>
            
            <table style="background: #fff;">
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>Tanggal Bayar</th>
                        <th>Nasabah</th>
                        <th>Agen Pengelola</th>
                        <th>No Tagihan</th>
                        <th class="r">Premi Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($pembayaran_list) > 0): ?>
                        <?php foreach($pembayaran_list as $j => $kom): ?>
                            <tr>
                                <td class="c"><?php echo $j + 1; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($kom['tanggal_bayar'])); ?></td>
                                <td style="font-weight: 600; color: var(--ink);"><?php echo htmlspecialchars($kom['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($kom['nama_agen'] ?? 'Pusat'); ?></td>
                                <td><?php echo htmlspecialchars($kom['no_tagihan']); ?></td>
                                <td class="r" style="color: var(--success); font-weight: 600;">Rp <?php echo number_format($kom['nominal_bayar'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="c" style="padding: 15px;">Belum ada pembayaran terverifikasi bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="r" style="font-size: 9px; font-weight: 800; color: var(--ink); padding-right: 15px;">TOTAL GROSS REVENUE</td>
                        <td class="r" style="font-weight: 800; color: var(--primary); font-size: 11px;">Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="paper-foot">
            <div class="foot-note">
                <div style="font-weight:600;color:var(--ink-2);margin-bottom:2px;">Keterangan Laporan</div>
                Laporan ini adalah dokumen rahasia perusahaan.<br>
                Hanya digunakan untuk kepentingan audit internal dan evaluasi operasional.
            </div>
            <div class="sign-col">
                <div style="font-size:8px;color:var(--ink-3);margin-bottom:2px;">Disahkan Oleh,</div>
                <div style="font-size:8px;color:var(--ink-2);font-weight:600;">Direktur Keuangan</div>
                <div class="sign-line"></div>
                <div class="sign-name">Asuransiku Finance</div>
            </div>
        </div>
    </div>
</body>
</html>
