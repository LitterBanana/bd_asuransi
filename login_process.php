<?php
    include "db.php";

    if(isset($_POST['login'])){
        $username = $_POST['username'];
        $password = $_POST['password'];

        $password_hashed = md5($password);

        // Menggunakan prepared statements
        $stmt = $conn->prepare("SELECT id_user, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if($row){
            // Verifikasi password (Mendukung plain text dari asuransi.sql, md5, dan bcrypt)
            $isPasswordValid = false;
            
            if ($row['password'] === $password) {
                $isPasswordValid = true; // Plain text
            } elseif ($row['password'] === md5($password)) {
                $isPasswordValid = true; // MD5 fallback
            } elseif (password_verify($password, $row['password'])) {
                $isPasswordValid = true; // Bcrypt (Standard)
            }

            if ($isPasswordValid) {
                // Update last_login
                $stmt_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
                $stmt_login->execute([$row['id_user']]);

                session_start();
                $_SESSION['id_user'] = $row['id_user'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Case-insensitive check untuk role
                $role = strtolower($row['role']);
                if($role == 'admin'){
                    header("Location: admin/index.php");
                }elseif($role == 'agen'){
                    header("Location: agen/index.php");
                }elseif($role == 'customer'){
                    header("Location: customer/index.php");
                } else {
                    header("Location: login.php?error=invalid_role");
                }
            } else {
                header("Location: login.php?error=1"); // Wrong password
            }
        }else{
            header("Location: login.php?error=1"); // User not found
        }
        $stmt = null;
    } else {
        header("Location: login.php");
    }
?>