<?php
   $host = '127.0.0.1';
   $db   = 'asuransi';
   $user = 'root';
   $pass = '';
   $port = '3307';
   $charset = 'utf8mb4';

   $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
   $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
   ];

   $conn = new PDO($dsn, $user, $pass, $options);

   try {
      $stmt = $conn->query("SELECT 1");
      $stmt->execute();
   } catch (PDOException $e) {
      die("Koneksi gagal: " . $e->getMessage());
   }
?>  