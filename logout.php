<?php
session_start();

// Simpan role sebelum sesi dihancurkan
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';

// Menghapus semua variabel sesi
session_unset();

// Menghancurkan sesi
session_destroy();

// Mengarahkan kembali ke halaman yang sesuai
if ($role === 'customer') {
    header("Location: index.php");
} else {
    header("Location: login.php");
}
exit();
?>
