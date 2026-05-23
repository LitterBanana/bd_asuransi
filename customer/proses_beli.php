<?php
session_start();
require_once "../db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'customer') {
    header("Location: ../index.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$id_produk = intval($_POST['id_produk']);

// Start Transaction
$conn->beginTransaction();

try {
    // 1. Get Product Detail
    $stmt_prod = $conn->prepare("SELECT premi_dasar FROM produk_asuransi WHERE id_produk = ?");
    $stmt_prod->execute([$id_produk]);
    $produk = $stmt_prod->fetch();
    if(!$produk) throw new Exception("Produk tidak ditemukan.");

    // 2. Cek apakah user sudah punya id_pemegang
    $stmt_user = $conn->prepare("SELECT id_pemegang, email, no_telp FROM users WHERE id_user = ?");
    $stmt_user->execute([$id_user]);
    $user_data = $stmt_user->fetch();

    $id_pemegang = $user_data['id_pemegang'];

    // Jika belum punya id_pemegang, insert ke pemegang_polis
    if (empty($id_pemegang)) {
        $nik = $_POST['nik'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $tanggal_lahir = $_POST['tanggal_lahir'];
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $pekerjaan = $_POST['pekerjaan'];
        $alamat = $_POST['alamat'];
        $email = $user_data['email'];
        $no_telp = $user_data['no_telp'];

        $stmt_pemegang = $conn->prepare("INSERT INTO pemegang_polis (nik, nama_lengkap, tanggal_lahir, jenis_kelamin, pekerjaan, alamat, no_telepon, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_pemegang->execute([$nik, $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $pekerjaan, $alamat, $no_telp, $email]);
        $id_pemegang = $conn->lastInsertId();

        // Update id_pemegang di tabel users
        $stmt_upd_user = $conn->prepare("UPDATE users SET id_pemegang = ? WHERE id_user = ?");
        $stmt_upd_user->execute([$id_pemegang, $id_user]);
    }

    // 3. Insert ke tabel polis
    // Format no_polis: POL-YYYY-XXXXX
    $tahun = date('Y');
    $no_polis = 'POL-' . $tahun . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $id_agen = null; // Beli langsung tidak pakai agen
    $tanggal_terbit = date('Y-m-d');
    $tanggal_jatuh_tempo = date('Y-m-d', strtotime('+1 year')); // 1 tahun dari sekarang
    $status_polis = 'Inforce';
    $total_premi_berjalan = 0.00;

    $stmt_polis = $conn->prepare("INSERT INTO polis (no_polis, id_pemegang, id_produk, id_agen, tanggal_terbit, tanggal_jatuh_tempo, status_polis, total_premi_berjalan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_polis->execute([$no_polis, $id_pemegang, $id_produk, $id_agen, $tanggal_terbit, $tanggal_jatuh_tempo, $status_polis, $total_premi_berjalan]);

    // 4. Buat tagihan premi bulan pertama
    $no_tagihan = 'INV-' . date('Ym') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $periode_bulan = date('Y-m');
    $jumlah_tagihan = $produk['premi_dasar'];
    $tanggal_cetak = date('Y-m-d');
    $jatuh_tempo_tagihan = date('Y-m-d', strtotime('+14 days')); // 14 hari waktu bayar
    $status_tagihan = 'Unpaid';

    $stmt_tagihan = $conn->prepare("INSERT INTO tagihan_premi (no_tagihan, no_polis, periode_bulan, jumlah_tagihan, tanggal_cetak, jatuh_tempo, status_tagihan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_tagihan->execute([$no_tagihan, $no_polis, $periode_bulan, $jumlah_tagihan, $tanggal_cetak, $jatuh_tempo_tagihan, $status_tagihan]);

    $conn->commit();
    
    // Redirect ke halaman polis dengan pesan sukses
    echo "<script>alert('Pembelian paket berhasil! Polis Anda telah diterbitkan. Silakan cek detail polis dan segera lakukan pembayaran premi pertama.'); window.location.href='polis/index.php';</script>";

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error proses beli: " . $e->getMessage());
    echo "<script>alert('Terjadi kesalahan saat memproses pembelian paket. Silakan coba lagi nanti.'); window.history.back();</script>";
}
?>
