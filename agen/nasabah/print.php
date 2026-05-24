<?php
session_start();
require_once __DIR__ . "/../../db.php";

// Pastikan hanya Agen yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Agen') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='../../index.php';</script>";
    exit;
}

$username = $_SESSION['username'];
$stmtUser = $conn->prepare("SELECT id_agen FROM users WHERE username = ?");
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();
$id_agen = $user['id_agen'];

$no_polis = $_GET['no_polis'] ?? '';

if (empty($no_polis)) {
    die("No Polis tidak valid.");
}

/** @var PDO $conn */
$stmt = $conn->prepare("
    SELECT 
        p.no_polis, p.tanggal_terbit, p.tanggal_jatuh_tempo, p.status_polis,
        pr.nama_produk, pr.limit_tahunan, pr.jenis_kategori,
        pp.nama_lengkap, pp.nik, pp.alamat, pp.no_telepon, pp.email
    FROM polis p
    JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    WHERE p.no_polis = ? AND p.id_agen = ?
");
$stmt->execute([$no_polis, $id_agen]);
$polis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$polis) {
    die("Data polis tidak ditemukan atau Anda tidak berhak mengakses polis ini.");
}

// Ambil data tanggungan keluarga
$stmt_t = $conn->prepare("SELECT * FROM tanggungan_polis WHERE no_polis = ?");
$stmt_t->execute([$no_polis]);
$tanggungan = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

$tglCetak = date('d F Y, H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Polis - <?php echo htmlspecialchars($polis['no_polis']); ?></title>
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
            --radius: 8px;
            --radius-sm: 5px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            font-size: 10px;
            line-height: 1.45;
            padding: 20px 16px;
        }

        /* ── ACTIONS (hidden on print) ── */
        .no-print {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 22px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: inherit;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn:hover {
            opacity: .85;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-ghost {
            background: #fff;
            color: var(--ink-2);
            border: 1.5px solid var(--line);
        }

        /* ── PAPER ── */
        .paper {
            max-width: 820px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 24px rgba(0, 0, 0, .07);
            overflow: hidden;
            position: relative;
        }

        /* ── TOP STRIPE ── */
        .paper-stripe {
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, #2563eb 100%);
        }

        /* ── HEADER ── */
        .paper-head {
            padding: 16px 26px 13px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: flex-start;
            gap: 11px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            flex-shrink: 0;
            background: var(--primary-lt);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand-name {
            font-size: 12.5px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.3;
        }

        .brand-addr {
            font-size: 8px;
            color: var(--ink-3);
            margin-top: 3px;
            line-height: 1.5;
        }

        .brand-addr span {
            display: block;
        }

        /* ── SECTION WRAPPER ── */
        .section {
            padding: 12px 26px;
            border-bottom: 1px solid var(--line);
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--ink-3);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 9px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        /* ── INFO GRID ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .info-block h4 {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ink-3);
            padding-bottom: 5px;
            border-bottom: 1px solid var(--line);
            margin-bottom: 7px;
        }

        .info-row {
            display: flex;
            gap: 0;
            margin-bottom: 3px;
            font-size: 9px;
        }

        .info-lbl {
            min-width: 115px;
            color: var(--ink-3);
            font-weight: 400;
        }

        .info-val {
            color: var(--ink);
            font-weight: 600;
            flex: 1;
        }

        /* ── TABLE (shared base) ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        thead tr {
            background: var(--bg);
        }

        th {
            padding: 6px 9px;
            text-align: left;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: var(--ink-3);
            border-bottom: 1px solid var(--line);
        }

        th.r {
            text-align: right;
        }

        th.c {
            text-align: center;
        }

        td {
            padding: 5px 9px;
            color: var(--ink-2);
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }

        td.r {
            text-align: right;
        }

        td.c {
            text-align: center;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        /* ── Badge ── */
        .tag {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            background: #f0f0f8;
            color: var(--ink-2);
            border: 1px solid var(--line);
        }

        .tag-primary {
            background: var(--primary-lt);
            color: var(--primary);
            border-color: #bfdbfe;
        }

        /* ── Footer ── */
        .paper-foot {
            padding: 13px 26px 16px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            background: var(--bg);
        }

        .foot-note {
            font-size: 8px;
            color: var(--ink-3);
            line-height: 1.6;
            max-width: 410px;
        }

        .sign-col {
            text-align: center;
        }

        .sign-line {
            width: 160px;
            border-bottom: 1px solid var(--ink-3);
            margin: 28px auto 4px;
        }

        .sign-name {
            font-size: 9px;
            font-weight: 700;
            color: var(--ink-2);
        }

        @media print {
            @page {
                size: A4;
                margin: 9mm;
            }
            .paper-layout-table > thead > tr > td,
            .paper-layout-table > tbody > tr > td,
            .paper-layout-table > tfoot > tr > td {
                padding: 0 !important;
                border: none !important;
                background: transparent !important;
            }

            body {
                background: #fff;
                padding: 0;
                font-size: 9px;
            }

            .paper {
                box-shadow: none;
                border-radius: 0;
            }

            .paper-stripe {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .section,
            .paper-head,
            .paper-foot {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .paper-head {
                padding-top: 10px;
                padding-bottom: 9px;
            }

            .section {
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .paper-foot {
                padding-top: 10px;
                padding-bottom: 12px;
            }

            .sign-line {
                margin-top: 20px;
                margin-bottom: 3px;
            }
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
        <a href="javascript:history.back()" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>

    <div class="paper">
        <table class="paper-layout-table" style="width: 100%; border: none; border-collapse: collapse;">
            <thead>
                <tr>
                    <td style="padding: 0; border: none;">
                        <div class="paper-stripe"></div>


                        <div class="paper-head">
                            <div class="brand">
                                <div class="brand-icon"><i class="fa-solid fa-shield-heart"></i></div>
                                <div>
                                    <div class="brand-name">ASURANSIKU</div>
                                    <div class="brand-addr">
                        <span>Kantor Pusat: Gedung Asuransi Tower Lt. 12, Jl. Jendral Sudirman No. 1, Jakarta</span>
                        <span>Tlp: 1500-123 | Email: cs@asuransi.co.id</span>
                        <span>www.asuransi.co.id</span>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <h1 style="color: var(--primary); font-size: 16px; margin:0;">SERTIFIKAT POLIS</h1>
                <div style="font-size: 9px; color: var(--ink-3);">Polis No: <?php echo htmlspecialchars($polis['no_polis']); ?></div>
            </div>
        </div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 0; border: none;">


        <div class="section">
            <div class="section-label">Informasi Pemegang Polis & Ketentuan</div>
            <div class="info-grid">


                <div class="info-block">
                    <h4>Data Tertanggung Utama</h4>
                    <div class="info-row"><span class="info-lbl">Nama Lengkap</span><span class="info-val"><?php echo htmlspecialchars($polis['nama_lengkap']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">No. KTP / NIK</span><span class="info-val"><?php echo htmlspecialchars($polis['nik']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Alamat Domisili</span><span class="info-val"><?php echo htmlspecialchars($polis['alamat']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">No. Telepon</span><span class="info-val"><?php echo htmlspecialchars($polis['no_telepon']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Alamat Email</span><span class="info-val"><?php echo htmlspecialchars($polis['email']); ?></span></div>
                </div>


                <div class="info-block">
                    <h4>Identitas Polis Asuransi</h4>
                    <div class="info-row"><span class="info-lbl">No. Polis</span><span class="info-val" style="color:var(--ink);"><?php echo htmlspecialchars($polis['no_polis']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Produk / Plan</span><span class="info-val" style="color:var(--primary); font-weight:700;"><?php echo htmlspecialchars($polis['nama_produk']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Tipe Pertanggungan</span><span class="info-val"><?php echo htmlspecialchars($polis['jenis_kategori']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Mulai Berlaku</span><span class="info-val"><?php echo date('d F Y', strtotime($polis['tanggal_terbit'])); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Jatuh Tempo / Akhir</span><span class="info-val"><?php echo date('d F Y', strtotime($polis['tanggal_jatuh_tempo'])); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Status Polis</span>
                        <span class="info-val">
                            <?php if($polis['status_polis'] === 'Inforce'): ?> <span style="color:var(--success);font-weight:700;">INFORCE (AKTIF)</span>
                            <?php elseif(in_array($polis['status_polis'], ['Lapse', 'Surrender', 'Rejected'])): ?> <span style="color:var(--danger);font-weight:700;"><?php echo strtoupper($polis['status_polis']); ?></span>
                            <?php else: ?> <span style="color:var(--warning);font-weight:700;"><?php echo strtoupper($polis['status_polis']); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>


        <div class="section">
            <div class="section-label">Manfaat Utama (Limit Tahunan)</div>
            <table>
                <thead>
                    <tr>
                        <th>Jenis Manfaat Asuransi</th>
                        <th>Kategori</th>
                        <th class="c">Mata Uang</th>
                        <th class="r">Batas Limit (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;color:var(--ink);">
                            Limit Pertanggungan Medis Tahunan - Keseluruhan
                        </td>
                        <td><?php echo htmlspecialchars($polis['jenis_kategori']); ?></td>
                        <td class="c"><span class="tag tag-primary">IDR</span></td>
                        <td class="r" style="font-weight:700;color:var(--primary);font-size:11px;">Rp <?php echo number_format($polis['limit_tahunan'], 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>


        <div class="section">
            <div class="section-label">
                Daftar Tanggungan Keluarga Tertanggung
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>Nama Lengkap</th>
                        <th>NIK</th>
                        <th>Tanggal Lahir</th>
                        <th>Jenis Kelamin</th>
                        <th>Hubungan</th>
                        <th class="c">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tanggungan) > 0): ?>
                        <?php foreach($tanggungan as $i => $row): ?>
                            <tr>
                                <td class="c" style="color:var(--ink-3);font-size:8px;"><?php echo $i + 1; ?></td>
                                <td style="color:var(--ink);font-weight:600;letter-spacing:.2px;"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($row['nik']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_lahir'])); ?></td>
                                <td><?php echo $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                <td><?php echo htmlspecialchars($row['hubungan']); ?></td>
                                <td class="c">
                                    <?php if($row['status_tanggungan'] === 'Active'): ?>
                                        <span style="color:var(--success);font-weight:700;font-size:8px;">AKTIF</span>
                                    <?php else: ?>
                                        <span style="color:var(--warning);font-weight:700;font-size:8px;"><?php echo strtoupper($row['status_tanggungan']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="c" style="color:var(--ink-3);padding:10px;">Tidak ada tanggungan anggota keluarga (Polis Individu).</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td style="padding: 0; border: none;">


        <div class="paper-foot" style="margin-top: 40px;">
            <div class="foot-note">
                <div style="font-weight:600;color:var(--ink-2);margin-bottom:2px;">Ketentuan Hukum</div>
                Sertifikat Polis ini diterbitkan oleh sistem sebagai ringkasan pertanggungan. 
                Syarat dan ketentuan selengkapnya mengacu pada Buku Polis asli.<br>
                Harap tunjukkan dokumen ini (cetak/digital) dan KTP saat mengajukan klaim di fasilitas kesehatan rekanan kami.<br>
                <span style="color:#c0c0d0;">Dicetak: <?php echo htmlspecialchars($tglCetak); ?></span>
            </div>
            <div class="sign-col">
                <div style="font-size:8px;color:var(--ink-3);margin-bottom:2px;">Diterbitkan di Jakarta,</div>
                <div style="font-size:8px;color:var(--ink-2);font-weight:600;">Direktur Utama,</div>
                <div class="sign-line"></div>
                <div class="sign-name">Budi Santoso, SE, MM.</div>
            </div>
        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
