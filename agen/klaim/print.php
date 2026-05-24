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

$no_klaim = $_GET['no_klaim'] ?? '';

if (empty($no_klaim)) {
    die("No Klaim tidak valid.");
}

/** @var PDO $conn */
$stmt = $conn->prepare("
    SELECT 
        k.no_klaim, k.tanggal_masuk, k.tanggal_keluar, k.jenis_perawatan, k.status_klaim, k.total_tagihan_faskes, k.total_dibayarkan_asuransi, k.catatan_analis,
        p.no_polis, p.tanggal_terbit,
        pr.nama_produk, pr.limit_tahunan,
        f.nama_faskes, f.alamat as alamat_faskes, f.kota as kota_faskes,
        kp.nama_penyakit, kp.kode_icd,
        COALESCE(t.nama_lengkap, pp.nama_lengkap) as nama_pasien, 
        pp.nama_lengkap as nama_pemegang, pp.no_telepon, pp.email
    FROM klaim_medis k
    JOIN polis p ON k.no_polis = p.no_polis
    JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
    JOIN faskes f ON k.id_faskes = f.id_faskes
    JOIN kategori_penyakit kp ON k.kode_icd = kp.kode_icd
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    LEFT JOIN tanggungan_polis t ON k.id_tanggungan = t.id_tanggungan
    WHERE k.no_klaim = ? AND p.id_agen = ?
");
$stmt->execute([$no_klaim, $id_agen]);
$klaim = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$klaim) {
    die("Data klaim tidak ditemukan atau bukan milik klien Anda.");
}

$tglCetak = date('d F Y, H:i') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Klaim - <?php echo htmlspecialchars($klaim['no_klaim']); ?></title>
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

        /* tfoot subtotal */
        tfoot tr td {
            background: var(--bg);
            font-weight: 700;
            color: var(--ink);
            border-top: 1.5px solid var(--line);
            border-bottom: none;
        }

        /* ── Grand total panel ── */
        .totals-panel {
            padding: 12px 26px 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 22px;
        }

        .terms-note {
            font-size: 8px;
            color: var(--ink-3);
            line-height: 1.6;
            flex: 1;
            max-width: 310px;
        }

        .terms-note ul {
            margin: 3px 0 0 13px;
            padding: 0;
        }

        .terms-note li {
            margin-bottom: 1px;
        }

        .totals-table {
            width: 300px;
            border-collapse: collapse;
            font-size: 10px;
        }

        .totals-table td {
            padding: 3px 6px;
        }

        .totals-table .t-sub {
            color: var(--ink-3);
        }

        .totals-table .t-sub td:last-child {
            text-align: right;
            font-weight: 600;
            color: var(--ink-2);
        }

        .totals-table .t-divider td {
            padding: 0;
        }

        .totals-table .t-divider-line {
            height: 1px;
            background: var(--line);
            margin: 4px 0;
        }

        .totals-table .t-grand td {
            font-size: 12px;
            font-weight: 800;
            color: var(--primary);
            padding: 6px;
            background: var(--primary-lt);
            border-radius: 5px;
        }

        .totals-table .t-grand td:last-child {
            text-align: right;
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
            .paper-foot,
            .totals-panel {
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

            .totals-panel {
                padding-top: 8px;
                padding-bottom: 10px;
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
                                <div class="brand-icon"><i class="fa-solid fa-notes-medical"></i></div>
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
                <h1 style="color: var(--primary); font-size: 16px; margin:0;">LAPORAN KLAIM</h1>
                <div style="font-size: 9px; color: var(--ink-3);">Klaim No: <?php echo htmlspecialchars($klaim['no_klaim']); ?></div>
            </div>
        </div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 0; border: none;">

        <div class="section">
            <div class="section-label">Informasi Pasien & Polis</div>
            <div class="info-grid">

                <div class="info-block">
                    <h4>Data Pasien Tertanggung</h4>
                    <div class="info-row"><span class="info-lbl">Nama Pasien</span><span class="info-val"><?php echo htmlspecialchars($klaim['nama_pasien']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Pemegang Polis</span><span class="info-val"><?php echo htmlspecialchars($klaim['nama_pemegang']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">No. Telepon Klien</span><span class="info-val"><?php echo htmlspecialchars($klaim['no_telepon']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Email Klien</span><span class="info-val"><?php echo htmlspecialchars($klaim['email']); ?></span></div>
                </div>

                <div class="info-block">
                    <h4>Identitas Polis</h4>
                    <div class="info-row"><span class="info-lbl">No. Polis</span><span class="info-val" style="color:var(--ink);"><?php echo htmlspecialchars($klaim['no_polis']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Produk Asuransi</span><span class="info-val" style="color:var(--primary); font-weight:700;"><?php echo htmlspecialchars($klaim['nama_produk']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Limit Tahunan</span><span class="info-val">Rp <?php echo number_format($klaim['limit_tahunan'], 0, ',', '.'); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Status Klaim</span>
                        <span class="info-val">
                            <?php if($klaim['status_klaim'] === 'Approved'): ?> <span style="color:var(--success);font-weight:700;">APPROVED / DISETUJUI</span>
                            <?php elseif($klaim['status_klaim'] === 'Rejected'): ?> <span style="color:var(--danger);font-weight:700;">DITOLAK</span>
                            <?php elseif($klaim['status_klaim'] === 'Pending'): ?> <span style="color:var(--warning);font-weight:700;">PENDING / MENUNGGU</span>
                            <?php else: ?> <span style="color:#0ea5e9;font-weight:700;">SEDANG INVESTIGASI</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="section">
            <div class="section-label">Detail Perawatan Medis</div>
            <div class="info-grid">
                <div class="info-block">
                    <h4>Diagnosa Penyakit (ICD-10)</h4>
                    <div class="info-row"><span class="info-lbl">Kode ICD</span><span class="info-val"><?php echo htmlspecialchars($klaim['kode_icd']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Nama Penyakit</span><span class="info-val"><?php echo htmlspecialchars($klaim['nama_penyakit']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Jenis Perawatan</span><span class="info-val"><?php echo htmlspecialchars($klaim['jenis_perawatan']); ?></span></div>
                </div>

                <div class="info-block">
                    <h4>Informasi Rumah Sakit / Klinik</h4>
                    <div class="info-row"><span class="info-lbl">Nama Faskes</span><span class="info-val"><?php echo htmlspecialchars($klaim['nama_faskes']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Lokasi</span><span class="info-val"><?php echo htmlspecialchars($klaim['kota_faskes']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Tanggal Masuk</span><span class="info-val"><?php echo date('d M Y', strtotime($klaim['tanggal_masuk'])); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Tanggal Keluar</span><span class="info-val"><?php echo $klaim['tanggal_keluar'] ? date('d M Y', strtotime($klaim['tanggal_keluar'])) : '-'; ?></span></div>
                </div>
            </div>
        </div>

        <div class="totals-panel">
            <div class="terms-note">
                <?php if ($klaim['catatan_analis']): ?>
                    <div style="margin-top:4px;font-weight:700;color:var(--ink-2);">Catatan dari Tim Klaim Asuransi:</div>
                    <p style="margin: 3px 0; color: var(--ink);"><?php echo nl2br(htmlspecialchars($klaim['catatan_analis'])); ?></p>
                <?php else: ?>
                    <div style="margin-top:4px;font-weight:700;">Catatan Tambahan:</div>
                    <ul>
                        <li>Persetujuan nominal yang dibayarkan berdasarkan kebijakan limit produk asuransi.</li>
                        <li>Proses klaim memakan waktu 3-5 hari kerja setelah dokumen lengkap.</li>
                    </ul>
                <?php endif; ?>
            </div>
            <table class="totals-table">
                <tr class="t-sub">
                    <td>Total Tagihan Rumah Sakit</td>
                    <td style="text-align:right;font-weight:600;color:var(--ink-2);">Rp <?php echo number_format($klaim['total_tagihan_faskes'], 0, ',', '.'); ?></td>
                </tr>
                <tr class="t-divider">
                    <td colspan="2">
                        <div class="t-divider-line"></div>
                    </td>
                </tr>
                <tr class="t-grand">
                    <td>Total Ditanggung Asuransi</td>
                    <td>Rp <?php echo number_format($klaim['total_dibayarkan_asuransi'], 0, ',', '.'); ?></td>
                </tr>
                <?php 
                    $selisih = max(0, $klaim['total_tagihan_faskes'] - $klaim['total_dibayarkan_asuransi']); 
                    if($selisih > 0 && $klaim['status_klaim'] === 'Approved'): 
                ?>
                <tr class="t-remaining">
                    <td colspan="2" style="font-size:11px;font-weight:700;color:var(--warning);padding:6px;background:#fffbeb;border-radius:5px; margin-top:10px; display:block;">
                        Excess / Bayar Mandiri &nbsp;&nbsp; Rp <?php echo number_format($selisih, 0, ',', '.'); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td style="padding: 0; border: none;">
        <div class="paper-foot">
            <div class="foot-note">
                <div style="font-weight:600;color:var(--ink-2);margin-bottom:2px;">Keterangan Dokumen</div>
                Dokumen ini merupakan laporan ringkasan penyelesaian klaim medis dari sistem Asuransiku.<br>
                Validitas dokumen ini sah tanpa perlu stempel basah.<br>
                <span style="color:#c0c0d0;">Dicetak: <?php echo htmlspecialchars($tglCetak); ?></span>
            </div>
            <div class="sign-col">
                <div style="font-size:8px;color:var(--ink-3);margin-bottom:2px;">Diverifikasi oleh,</div>
                <div style="font-size:8px;color:var(--ink-2);font-weight:600;">Bagian Klaim (Claim Dept),</div>
                <div class="sign-line"></div>
                <div class="sign-name">Asuransiku</div>
            </div>
        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
