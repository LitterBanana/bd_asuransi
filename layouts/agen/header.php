<?php
session_start();
// Security check for role agen
if(!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'agen'){
    header("Location: /asuransi/login.php");
    exit();
}
$base_url = '/asuransi';

require_once __DIR__ . '/../../db.php';
$stmtHeader = $conn->prepare("SELECT foto_profil FROM users WHERE username = ?");
$stmtHeader->execute([$_SESSION['username']]);
$userHeader = $stmtHeader->fetch();
$foto_profil_header = $userHeader['foto_profil'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - AsuransiKu</title>
    <!-- CSS Universal -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/layouts/css/style.css?v=<?php echo time(); ?>">
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
            <div class="top-header admin-top-header" style="display: flex; align-items: center;">
                <div class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div class="user-profile" style="margin-left: auto; display: flex; align-items: center;">
                    <span style="margin-right: 15px; font-weight: 600; color: #1e293b;">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <?php if(!empty($foto_profil_header)): ?>
                        <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <img src="<?php echo $base_url; ?>/uploads/profiles/<?php echo htmlspecialchars($foto_profil_header); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-user fa-2xl" style="color: #cbd5e1;"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
