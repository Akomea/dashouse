<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Das House Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin: 20px;
            padding: 30px;
        }
        .main-content-wrapper {
            margin-left: 0;
            min-height: 100vh;
            overflow-y: auto;
        }
        @media (min-width: 768px) {
            .main-content-wrapper {
                margin-left: 250px; /* Width of sidebar on md+ screens */
            }
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .menu-card {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }
        .photo-card {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            color: white;
        }
        .logout-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-cat fa-2x mb-2"></i>
                        <h5>Das House</h5>
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="menu-manager.php">
                            <i class="fas fa-utensils me-2"></i>Menu Manager
                        </a>
                        <a class="nav-link" href="category-manager.php">
                            <i class="fas fa-tags me-2"></i>Category Manager
                        </a>
                        <a class="nav-link" href="photo-manager.php">
                            <i class="fas fa-images me-2"></i>Photo Manager
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </nav>
                    
                    <div class="mt-auto pt-5">
                        <a href="?logout=1" class="btn logout-btn w-100">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content-wrapper">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                        <span class="text-muted">Welcome back, Admin!</span>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card stats-card text-center p-4">
                                <i class="fas fa-utensils card-icon"></i>
                                <h3 id="menu-count">0</h3>
                                <p class="mb-0">Menu Items</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stats-card text-center p-4">
                                <i class="fas fa-images card-icon"></i>
                                <h3 id="photo-count">0</h3>
                                <p class="mb-0">Photos</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stats-card text-center p-4">
                                <i class="fas fa-eye card-icon"></i>
                                <h3 id="category-count">0</h3>
                                <p class="mb-0">Categories</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card menu-card text-center p-4">
                                <i class="fas fa-plus card-icon"></i>
                                <h4>Add Menu Item</h4>
                                <p class="mb-3">Quickly add new dishes, drinks, or snacks to your menu</p>
                                <a href="menu-manager.php?action=add" class="btn btn-light">Add Item</a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card photo-card text-center p-4">
                                <i class="fas fa-upload card-icon"></i>
                                <h4>Upload Photos</h4>
                                <p class="mb-3">Add new images for your menu items and categories</p>
                                <a href="photo-manager.php?action=upload" class="btn btn-light">Upload Photos</a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card stats-card text-center p-4">
                                <i class="fas fa-tags card-icon"></i>
                                <h4>Manage Categories</h4>
                                <p class="mb-3">Organize your menu with categories and subcategories</p>
                                <a href="category-manager.php" class="btn btn-light">Manage Categories</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div id="recent-activity">
                                        <p class="text-muted text-center">No recent activity</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load dashboard stats
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
        });
        
        function loadDashboardStats() {
            // This would normally fetch from your data files
            // For now, showing sample data
            document.getElementById('menu-count').textContent = '24';
            document.getElementById('photo-count').textContent = '18';
            document.getElementById('category-count').textContent = '4';
        }
    </script>
</body>
</html>
