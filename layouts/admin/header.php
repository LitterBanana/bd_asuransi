<?php
session_start();
// Security check for role admin
if(!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'admin'){
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
    <title>Admin Dashboard - AsuransiKu</title>
    <!-- CSS Universal -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/layouts/css/style.css?v=<?php echo time(); ?>">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Admin specific layout adjustments matching modern dashboard */
        .admin-sidebar {
            background: #1e293b; /* Slate 800 */
        }
        .admin-sidebar .sidebar-logo {
            color: #ffffff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .admin-sidebar .sidebar-menu a {
            color: #cbd5e1;
        }
        .admin-sidebar .sidebar-menu a:hover,
        .admin-sidebar .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
            border-radius: 8px;
        }
        .admin-sidebar .sidebar-menu i {
            color: #94a3b8;
        }
        .admin-sidebar .sidebar-menu a.active i {
            color: #38bdf8; /* Light Blue */
        }
        
        .admin-top-header {
            background: #ffffff;
            border-bottom: 1px solid var(--color-border-light);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            padding: 0 25px;
            height: 70px;
        }
        
        /* Modern Stats Grid for Admin */
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .admin-stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            border: 1px solid var(--color-border-light);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .admin-stat-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
        }
        .admin-stat-card.blue::before { background: #3b82f6; }
        .admin-stat-card.green::before { background: #10b981; }
        .admin-stat-card.orange::before { background: #f59e0b; }
        .admin-stat-card.purple::before { background: #8b5cf6; }
        
        .admin-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 15px;
        }
        .admin-stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .admin-stat-icon.green { background: #ecfdf5; color: #10b981; }
        .admin-stat-icon.orange { background: #fffbeb; color: #f59e0b; }
        .admin-stat-icon.purple { background: #f5f3ff; color: #8b5cf6; }
        
        .admin-stat-content h4 {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 5px 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .admin-stat-content h2 {
            font-size: 24px;
            color: #1e293b;
            margin: 0;
            font-weight: 700;
        }
        
        /* Dashboard Tables / Cards */
        .admin-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--color-border-light);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .admin-card-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--color-border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .admin-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        .admin-card-body {
            padding: 0; /* Removing padding for edge-to-edge tables */
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th, .admin-table td {
            padding: 15px 25px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .admin-table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .admin-table tr:last-child td {
            border-bottom: none;
        }
        .admin-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Admin Standard Button System */
        .btn-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .btn-admin:active {
            transform: translateY(0);
        }
        .btn-admin-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }
        .btn-admin-lg {
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        /* Variants */
        .btn-admin-primary { background: #3b82f6; color: white; }
        .btn-admin-primary:hover { background: #2563eb; }
        
        .btn-admin-success { background: #10b981; color: white; }
        .btn-admin-success:hover { background: #059669; }
        
        .btn-admin-danger { background: #ef4444; color: white; }
        .btn-admin-danger:hover { background: #dc2626; }
        
        .btn-admin-warning { background: #f59e0b; color: white; }
        .btn-admin-warning:hover { background: #d97706; }
        
        .btn-admin-ghost { background: transparent; color: #64748b; border: 1px solid transparent; }
        .btn-admin-ghost:hover { background: #f1f5f9; color: #1e293b; box-shadow: none; transform: none; }
        .btn-admin-ghost-danger { background: transparent; color: #ef4444; border: 1px solid transparent; }
        .btn-admin-ghost-danger:hover { background: #fef2f2; color: #dc2626; box-shadow: none; transform: none; }
        .btn-admin-ghost-success { background: transparent; color: #10b981; border: 1px solid transparent; }
        .btn-admin-ghost-success:hover { background: #ecfdf5; color: #059669; box-shadow: none; transform: none; }
        .btn-admin-ghost-primary { background: transparent; color: #3b82f6; border: 1px solid transparent; }
        .btn-admin-ghost-primary:hover { background: #eff6ff; color: #2563eb; box-shadow: none; transform: none; }
    </style>
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
                <div class="user-profile" style="margin-left: auto;">
                    <span style="margin-right: 15px; font-weight: 600; color: #1e293b;">Halo, Administrator (<?php echo htmlspecialchars($_SESSION['username']); ?>)</span>
                    <i class="fa-solid fa-circle-user fa-2xl" style="color: #cbd5e1;"></i>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
