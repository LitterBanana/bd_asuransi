<?php
session_start();
require_once __DIR__ . "/../../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_tagihan = $_POST['no_tagihan'] ?? '';
    $nominal = $_POST['nominal_bayar'] ?? 0;
    $metode = $_POST['metode_bayar'] ?? '';
    
    $username = $_SESSION['username'] ?? '';
    $stmtUser = $conn->prepare("SELECT id_agen FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $agent = $stmtUser->fetch();
    
    if (!$agent) {
        die("Akses ditolak.");
    }

    // Verify tagihan belongs to agent's customer
    $stmtTagihan = $conn->prepare("
        SELECT t.no_tagihan
        FROM tagihan_premi t
        JOIN polis p ON t.no_polis = p.no_polis
        LEFT JOIN pemegang_polis pp ON p.id_pemegang = pp.id_pemegang
        WHERE t.no_tagihan = ? AND (p.id_agen = ? OR pp.id_agen = ?)
    ");
    $stmtTagihan->execute([$no_tagihan, $agent['id_agen'], $agent['id_agen']]);
    if (!$stmtTagihan->fetch()) {
        header("Location: index.php?error=" . urlencode("Tagihan tidak valid atau bukan milik nasabah Anda."));
        exit;
    }

    $uploadDir = '../../uploads/payments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $bukti = '';
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $bukti = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $no_tagihan) . '.' . $ext;
            move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $uploadDir . $bukti);
        } else {
            header("Location: index.php?error=Format+file+tidak+valid.");
            exit;
        }
    } else {
        header("Location: index.php?error=Bukti+pembayaran+wajib+diunggah.");
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO pembayaran_premi (no_tagihan, tanggal_bayar, nominal_bayar, metode_bayar, bukti_pembayaran, status_pembayaran)
        VALUES (?, NOW(), ?, ?, ?, 'Pending')
    ");
    
    if ($stmt->execute([$no_tagihan, $nominal, $metode, $bukti])) {
        header("Location: index.php?success=" . urlencode("Pembayaran berhasil diinput dan menunggu verifikasi admin."));
    } else {
        header("Location: index.php?error=Gagal+input+pembayaran.");
    }
} else {
    header("Location: index.php");
}
?>
