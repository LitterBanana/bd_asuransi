<?php
session_start();
require_once __DIR__ . "/../../db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../../index.php';</script>";
    exit;
}

$bulan = $_GET['bulan'] ?? date('Y-m');
$namaBulan = date('F Y', strtotime($bulan . '-01'));

// Ambil data nasabah baru bulan ini
$stmtNasabah = $conn->prepare("
    SELECT pp.nik, pp.nama_lengkap, pp.alamat, pp.no_telepon, pp.created_at, a.nama_agen
    FROM pemegang_polis pp
    LEFT JOIN agen a ON pp.id_agen = a.id_agen
    WHERE DATE_FORMAT(pp.created_at, '%Y-%m') = ?
    ORDER BY pp.created_at DESC
");
$stmtNasabah->execute([$bulan]);
$nasabah_baru = $stmtNasabah->fetchAll(PDO::FETCH_ASSOC);

// Ambil data polis baru bulan ini
$stmtPolis = $conn->prepare("
    SELECT p.no_polis, p.tanggal_terbit, pr.nama_produk, pp.nama_lengkap, a.nama_agen
    FROM polis p
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
    LEFT JOIN agen a ON p.id_agen = a.id_agen
    WHERE DATE_FORMAT(p.tanggal_terbit, '%Y-%m') = ?
    ORDER BY p.tanggal_terbit DESC
");
$stmtPolis->execute([$bulan]);
$polis_baru = $stmtPolis->fetchAll(PDO::FETCH_ASSOC);

$tglCetak = date('d F Y, H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pertumbuhan - <?php echo htmlspecialchars($namaBulan); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-lt: #ecfdf5;
            --ink: #1e293b;
            --ink-2: #475569;
            --ink-3: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --white: #ffffff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--ink); font-size: 10px; line-height: 1.45; padding: 20px 16px; }

        .no-print { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 18px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 22px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-ghost { background: #fff; color: var(--ink-2); border: 1.5px solid var(--line); }

        .paper { max-width: 820px; margin: 0 auto; background: var(--white); border-radius: 10px; box-shadow: 0 2px 24px rgba(0, 0, 0, .07); overflow: hidden; }
        .paper-stripe { height: 4px; background: linear-gradient(90deg, var(--primary) 0%, #34d399 100%); }
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
        th.c, td.c { text-align: center; }

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
                <div class="brand-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="brand-name">ASURANSIKU</div>
                    <div class="brand-addr">
                        Laporan Operasional Pusat<br>
                        Dicetak: <?php echo htmlspecialchars($tglCetak); ?>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; font-weight: 800; color: var(--ink); text-transform: uppercase;">LAPORAN PERTUMBUHAN</div>
                <div style="font-size: 10px; color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($namaBulan); ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-label">Akuisisi Nasabah Baru (<?php echo count($nasabah_baru); ?> Nasabah)</div>
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>Nama Lengkap</th>
                        <th>NIK</th>
                        <th>Agen / Cabang</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($nasabah_baru) > 0): ?>
                        <?php foreach($nasabah_baru as $i => $row): ?>
                            <tr>
                                <td class="c"><?php echo $i + 1; ?></td>
                                <td style="font-weight: 600; color: var(--ink);"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['nik']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_agen'] ?? 'Pusat'); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="c" style="padding: 15px;">Tidak ada nasabah baru yang didaftarkan pada bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-label">Penerbitan Polis Baru (<?php echo count($polis_baru); ?> Polis)</div>
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>No. Polis</th>
                        <th>Nama Nasabah</th>
                        <th>Produk Asuransi</th>
                        <th>Agen Pengelola</th>
                        <th>Tanggal Terbit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($polis_baru) > 0): ?>
                        <?php foreach($polis_baru as $j => $row): ?>
                            <tr>
                                <td class="c"><?php echo $j + 1; ?></td>
                                <td style="font-weight: 600; color: var(--ink);"><?php echo htmlspecialchars($row['no_polis']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_agen'] ?? 'Pusat'); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_terbit'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="c" style="padding: 15px;">Tidak ada polis baru yang diterbitkan pada bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="paper-foot">
            <div class="foot-note">
                <div style="font-weight:600;color:var(--ink-2);margin-bottom:2px;">Keterangan Laporan</div>
                Laporan ini adalah dokumen rahasia perusahaan.<br>
                Digunakan untuk evaluasi kinerja keagenan dan pertumbuhan portofolio secara.
            </div>
            <div class="sign-col">
                <div style="font-size:8px;color:var(--ink-3);margin-bottom:2px;">Disahkan Oleh,</div>
                <div style="font-size:8px;color:var(--ink-2);font-weight:600;">Direktur Operasional</div>
                <div class="sign-line"></div>
                <div class="sign-name">Asuransiku Pusat</div>
            </div>
        </div>
    </div>
</body>
</html>
