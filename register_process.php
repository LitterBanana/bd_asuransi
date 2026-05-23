<?php
    include "db.php";

    if(isset($_POST['register'])){
        $username = $_POST['username'];
        $email = $_POST['email'];
        $no_telp = $_POST['no_telp'];
        $password = $_POST['password'];
        $role = $_POST['role'];

        $password_hashed = md5($password);
        
        // Cek apakah username atau email sudah ada
        $stmt_check = $conn->prepare("SELECT id_user FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if($stmt_check->num_rows > 0) {
            header("Location: register.php?error=username_or_email_exists");
            $stmt_check->close();
            exit();
        }
        $stmt_check->close();

        // Prepared statement untuk insert data dengan email dan no telp
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, no_telp, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password_hashed, $email, $no_telp, $role);
        
        if($stmt->execute()){
            header("Location: login.php?success=1");
        }else{
            header("Location: register.php?error=1");
        }
        $stmt->close();
    } else {
        header("Location: register.php");
    }
?>
