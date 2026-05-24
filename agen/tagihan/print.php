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

$no_tagihan = $_GET['no_tagihan'] ?? '';

if (empty($no_tagihan)) {
    die("No Tagihan tidak valid.");
}

/** @var PDO $conn */
$stmt = $conn->prepare("
    SELECT 
        t.no_tagihan, t.periode_bulan, t.jumlah_tagihan, t.jatuh_tempo, t.status_tagihan,
        p.no_polis, p.tanggal_terbit,
        pr.nama_produk, pr.limit_tahunan, pr.jenis_kategori,
        pp.nama_lengkap, pp.nik, pp.alamat, pp.no_telepon, pp.email
    FROM tagihan_premi t
    JOIN polis p ON t.no_polis = p.no_polis
    JOIN produk_asuransi pr ON p.id_produk = pr.id_produk
    JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
    WHERE t.no_tagihan = ? AND p.id_agen = ?
");
$stmt->execute([$no_tagihan, $id_agen]);
$tagihan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tagihan) {
    die("Data tagihan tidak ditemukan atau bukan milik klien Anda.");
}

// Ambil riwayat pembayaran untuk tagihan ini (jika ada)
$stmt_pay = $conn->prepare("SELECT * FROM pembayaran_premi WHERE no_tagihan = ? ORDER BY tanggal_bayar ASC");
$stmt_pay->execute([$no_tagihan]);
$payments = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);
$totalBayar = 0;
foreach($payments as $p) {
    if ($p['status_pembayaran'] === 'Verified') {
        $totalBayar += $p['nominal_bayar'];
    }
}

$isLunas = $tagihan['status_tagihan'] === 'Paid';
$tglCetak = date('d F Y, H:i') . ' WIB';
$grandTotal = $tagihan['jumlah_tagihan'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Tagihan - <?php echo htmlspecialchars($tagihan['no_tagihan']); ?></title>
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

        .sb {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
        }

        .sb-v {
            background: var(--success-bg);
            color: var(--success);
        }

        .sb-p {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .sb-r {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .empty-row td {
            height: 22px;
            border-bottom: 1px dashed #e0e0e8;
            color: transparent;
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
        </div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 0; border: none;">


        <div class="section">
            <div class="section-label">Informasi Pemegang Polis & Invoice</div>
            <div class="info-grid">


                <div class="info-block">
                    <h4>Data Pemegang Polis</h4>
                    <div class="info-row"><span class="info-lbl">Nama Lengkap</span><span class="info-val"><?php echo htmlspecialchars($tagihan['nama_lengkap']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">No. Polis</span><span class="info-val"><?php echo htmlspecialchars($tagihan['no_polis']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Produk Asuransi</span><span class="info-val"><?php echo htmlspecialchars($tagihan['nama_produk']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Kategori</span><span class="info-val"><?php echo htmlspecialchars($tagihan['jenis_kategori']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">No. Telepon</span><span class="info-val"><?php echo htmlspecialchars($tagihan['no_telepon']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Email</span><span class="info-val"><?php echo htmlspecialchars($tagihan['email']); ?></span></div>
                </div>


                <div class="info-block">
                    <h4>Identitas Invoice Tagihan</h4>
                    <div class="info-row"><span class="info-lbl">No. Invoice</span><span class="info-val" style="color:var(--ink);"><?php echo htmlspecialchars($tagihan['no_tagihan']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Periode Tagihan</span><span class="info-val"><?php echo htmlspecialchars($tagihan['periode_bulan']); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Tanggal Cetak</span><span class="info-val"><?php echo htmlspecialchars($tglCetak); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Jatuh Tempo</span><span class="info-val" style="color:var(--danger);"><?php echo date('d M Y', strtotime($tagihan['jatuh_tempo'])); ?></span></div>
                    <div class="info-row"><span class="info-lbl">Status Tagihan</span>
                        <span class="info-val">
                            <?php if($isLunas): ?> <span style="color:var(--success);font-weight:700;">Lunas</span>
                            <?php elseif($tagihan['status_tagihan'] === 'Unpaid'): ?> <span style="color:var(--warning);font-weight:700;">Belum Dibayar</span>
                            <?php else: ?> <span style="color:var(--danger);font-weight:700;">Terlambat / Overdue</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>


        <div class="section">
            <div class="section-label">Rincian Tagihan Premi Asuransi</div>
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi Layanan</th>
                        <th>Periode</th>
                        <th class="c">Polis Status</th>
                        <th class="r">Total (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;color:var(--ink);">
                            Premi Asuransi Bulanan - <?php echo htmlspecialchars($tagihan['nama_produk']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($tagihan['periode_bulan']); ?></td>
                        <td class="c"><span class="tag tag-primary">Aktif</span></td>
                        <td class="r" style="font-weight:700;color:var(--ink);">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="r" style="font-size:8px;letter-spacing:.5px;text-transform:uppercase;padding-right:12px;">Subtotal Tagihan</td>
                        <td class="r">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>


        <div class="section">
            <div class="section-label">
                Riwayat Pembayaran untuk Tagihan Ini
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="c" style="width:24px;">#</th>
                        <th>ID Pembayaran</th>
                        <th>Tanggal Bayar</th>
                        <th>Metode Bank</th>
                        <th>No Referensi</th>
                        <th class="r">Jumlah Dibayar</th>
                        <th class="c">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach($payments as $i => $row): ?>
                            <tr>
                                <td class="c" style="color:var(--ink-3);font-size:8px;"><?php echo $i + 1; ?></td>
                                <td style="font-size:8px;color:var(--ink);font-weight:600;letter-spacing:.2px;"><?php echo htmlspecialchars($row['id_pembayaran']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_bayar'])); ?></td>
                                <td><?php echo htmlspecialchars($row['metode_bayar'] . ($row['bank_name'] ? ' - ' . $row['bank_name'] : '')); ?></td>
                                <td><?php echo htmlspecialchars($row['referensi_pembayaran']); ?></td>
                                <td class="r" style="font-weight:700;color:var(--ink);">Rp <?php echo number_format($row['nominal_bayar'], 0, ',', '.'); ?></td>
                                <td class="c">
                                    <?php if($row['status_pembayaran'] === 'Verified'): ?>
                                        <span class="sb sb-v">Terverifikasi</span>
                                    <?php elseif($row['status_pembayaran'] === 'Pending'): ?>
                                        <span class="sb sb-p">Pending</span>
                                    <?php else: ?>
                                        <span class="sb sb-r">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="c" style="color:var(--ink-3);padding:10px;">Belum ada riwayat pembayaran yang tercatat.</td>
                        </tr>
                    <?php endif; ?>

                    <?php
                        $existingCount = count($payments);
                        $emptySlots = max(0, 3 - $existingCount);
                        for($j = 0; $j < $emptySlots; $j++):
                    ?>
                        <tr class="empty-row">
                            <td class="c"><?php echo $existingCount + $j + 1; ?></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="r" style="font-size:8px;letter-spacing:.5px;text-transform:uppercase;padding-right:12px;">Total Terverifikasi</td>
                        <td class="r">Rp <?php echo number_format($totalBayar, 0, ',', '.'); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>


        <div class="totals-panel">
            <div class="terms-note">
                Mohon konfirmasi pembayaran sebelum tanggal jatuh tempo agar perlindungan asuransi Anda tetap aktif.
                <div style="margin-top:4px;font-weight:700;">Catatan:</div>
                <ul>
                    <li>Pastikan nominal transfer sesuai hingga digit terakhir.</li>
                    <li>Status "Pending" akan berubah setelah diverifikasi tim Finance maksimal 1x24 jam.</li>
                    <li>Keterlambatan pembayaran (*grace period* 30 hari) berisiko *Lapse*.</li>
                </ul>
            </div>
            <table class="totals-table">
                <tr class="t-sub">
                    <td>Subtotal Premi</td>
                    <td style="text-align:right;font-weight:600;color:var(--ink-2);">Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                </tr>
                <tr class="t-divider">
                    <td colspan="2">
                        <div class="t-divider-line"></div>
                    </td>
                </tr>
                <tr class="t-grand">
                    <td>Total Tagihan</td>
                    <td>Rp <?php echo number_format($grandTotal, 0, ',', '.'); ?></td>
                </tr>
                <?php 
                    $kekurangan = max(0, $grandTotal - $totalBayar); 
                    if($kekurangan > 0 && !$isLunas): 
                ?>
                <tr class="t-remaining">
                    <td colspan="2" style="font-size:11px;font-weight:700;color:var(--danger);padding:6px;background:#fff5f5;border-radius:5px; margin-top:10px; display:block;">
                        Sisa Belum Dibayar &nbsp;&nbsp; Rp <?php echo number_format($kekurangan, 0, ',', '.'); ?>
                    </td>
                </tr>
                <?php elseif($isLunas): ?>
                <tr class="t-remaining">
                    <td colspan="2" style="font-size:11px;font-weight:700;color:var(--success);padding:6px;background:#ecfdf5;border-radius:5px; margin-top:10px; display:block; text-align:center;">
                        LUNAS TERBAYAR
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
                Invoice ini diterbitkan secara otomatis oleh sistem Asuransiku.<br>
                Pembayaran dianggap sah setelah divalidasi dan berstatus Verified.<br>
                Simpan dokumen ini sebagai bukti tagihan dan pembayaran Anda.<br>
                <span style="color:#c0c0d0;">Dicetak: <?php echo htmlspecialchars($tglCetak); ?></span>
            </div>
            <div class="sign-col">
                <div style="font-size:8px;color:var(--ink-3);margin-bottom:2px;">Hormat Kami,</div>
                <div style="font-size:8px;color:var(--ink-2);font-weight:600;">Bagian Keuangan (Finance),</div>
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
