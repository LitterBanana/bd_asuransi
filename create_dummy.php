<?php
require_once __DIR__ . '/db.php';

try {
    $conn->beginTransaction();

    // Ambil satu id_agen yang aktif
    $stmt = $conn->query("SELECT id_agen FROM users WHERE role = 'Agen' AND id_agen IS NOT NULL LIMIT 1");
    $agen = $stmt->fetch();
    if (!$agen) {
        die("Tidak ada akun agen untuk disematkan data dummy.");
    }
    $id_agen = $agen['id_agen'];

    // 1. Insert Pemegang Polis
    $nik = '320' . rand(1000000000000, 9999999999999);
    $email = 'dummy_' . uniqid() . '@example.com';
    $stmt = $conn->prepare("
        INSERT INTO pemegang_polis (nik, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, no_telepon, email, id_agen) 
        VALUES (?, 'Budi Dummy Test', '1990-01-01', 'L', 'Jl. Dummy No. 123', '081234567890', ?, ?)
    ");
    $stmt->execute([$nik, $email, $id_agen]);
    $id_pemegang = $conn->lastInsertId();

    // Buat akun user untuk nasabah (agar lengkap)
    $username = 'budi_' . uniqid();
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $stmtUser = $conn->prepare("INSERT INTO users (username, password, role, id_pemegang) VALUES (?, ?, 'Customer', ?)");
    $stmtUser->execute([$username, $password, $id_pemegang]);

    // 2. Ambil produk asuransi
    $stmtProd = $conn->query("SELECT id_produk FROM produk_asuransi LIMIT 1");
    $prod = $stmtProd->fetch();
    $id_produk = $prod['id_produk'] ?? 1;

    // 3. Insert Polis
    $no_polis = 'POL-' . date('Ymd') . '-' . rand(1000, 9999);
    $tanggal_terbit = date('Y-m-d', strtotime('-2 months')); // Polis diterbitkan 2 bulan lalu
    $tanggal_jatuh_tempo = date('Y-m-d', strtotime('+10 years'));
    $stmtPolis = $conn->prepare("
        INSERT INTO polis (no_polis, id_pemegang, id_produk, tanggal_terbit, tanggal_jatuh_tempo, status_polis, id_agen)
        VALUES (?, ?, ?, ?, ?, 'Inforce', ?)
    ");
    $stmtPolis->execute([$no_polis, $id_pemegang, $id_produk, $tanggal_terbit, $tanggal_jatuh_tempo, $id_agen]);

    // 4. Insert Tagihan Tertunggak (Bulan Lalu)
    $no_tagihan1 = 'INV-' . date('Ym', strtotime('-1 month')) . '-' . rand(1000,9999);
    $jatuh_tempo1 = date('Y-m-d', strtotime('-1 month +15 days')); // Jatuh tempo bulan lalu
    $stmtTagihan1 = $conn->prepare("
        INSERT INTO tagihan_premi (no_tagihan, no_polis, periode_bulan, jumlah_tagihan, jatuh_tempo, status_tagihan)
        VALUES (?, ?, ?, 500000, ?, 'Overdue')
    ");
    $stmtTagihan1->execute([$no_tagihan1, $no_polis, date('Y-m', strtotime('-1 month')), $jatuh_tempo1]);

    // 5. Insert Tagihan Bulan Ini (Unpaid)
    $no_tagihan2 = 'INV-' . date('Ym') . '-' . rand(1000,9999);
    $jatuh_tempo2 = date('Y-m-d', strtotime('+15 days')); // Jatuh tempo bulan ini
    $stmtTagihan2 = $conn->prepare("
        INSERT INTO tagihan_premi (no_tagihan, no_polis, periode_bulan, jumlah_tagihan, jatuh_tempo, status_tagihan)
        VALUES (?, ?, ?, 500000, ?, 'Unpaid')
    ");
    $stmtTagihan2->execute([$no_tagihan2, $no_polis, date('Y-m'), $jatuh_tempo2]);

    $conn->commit();
    echo "SUCCESS: Data dummy nasabah Budi Dummy Test (Polis: $no_polis) berhasil ditambahkan dengan 2 tagihan tertunggak (Overdue dan Unpaid).";

} catch (Exception $e) {
    $conn->rollBack();
    echo "ERROR: " . $e->getMessage();
}
