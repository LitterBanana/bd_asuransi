<?php
    include "db.php";

    if(isset($_POST['register'])){
        $username = $_POST['username'];
        $email = $_POST['email'];
        $no_telp = $_POST['no_telp'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        $kode_agen = trim($_POST['kode_agen'] ?? '');
        $id_agen = null;

        $password_hashed = md5($password);
        
        // Cek apakah username atau email sudah ada
        $stmt_check = $conn->prepare("SELECT id_user FROM users WHERE username = ? OR email = ?");
        $stmt_check->execute([$username, $email]);
        
        if($stmt_check->rowCount() > 0) {
            header("Location: register.php?error=username_or_email_exists");
            exit();
        }

        // Cek kode referral agen jika ada
        if (!empty($kode_agen)) {
            $stmtAgen = $conn->prepare("SELECT id_agen FROM agen WHERE kode_agen = ? AND status_aktif = 'Aktif'");
            $stmtAgen->execute([$kode_agen]);
            if ($agenData = $stmtAgen->fetch()) {
                $id_agen = $agenData['id_agen'];
            }
        }

        // Prepared statement untuk insert data dengan email, no telp, dan id_agen (referral)
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, no_telp, role, id_agen) VALUES (?, ?, ?, ?, ?, ?)");
        
        if($stmt->execute([$username, $password_hashed, $email, $no_telp, $role, $id_agen])){
            header("Location: login.php?success=1");
        }else{
            header("Location: register.php?error=1");
        }
    } else {
        header("Location: register.php");
    }
?>
