<?php
session_start();
// Security check for role customer
if(!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'customer'){
    header("Location: /asuransi/login.php");
    exit();
}
$base_url = '/asuransi';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - AsuransiKu</title>
    <!-- CSS Universal -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/layouts/css/style.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
        <!-- Sidebar -->
        <?php include __DIR__ . "/sidebar.php"; ?>
        
        <!-- Main Content Wrapper -->
        <div class="main-content">
            <!-- Top Header -->
            <div class="top-header" style="display: flex; align-items: center;">
                <div class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div class="user-profile" style="margin-left: auto;">
                    <i class="fa-solid fa-circle-user fa-lg"></i>
                    <span>Halo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
