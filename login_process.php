<?php
    include "db.php";

    if(isset($_POST['login'])){
        $username = $_POST['username'];
        $password = $_POST['password'];

        $password_hashed = md5($password);

        // Menggunakan prepared statements untuk mencegah SQL Injection
        $stmt = $conn->prepare("SELECT id_user, username, role FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password_hashed);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
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
        }else{
            header("Location: login.php?error=1");
        }
        $stmt->close();
    } else {
        header("Location: login.php");
    }
?>