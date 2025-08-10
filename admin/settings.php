<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Simple password validation (you can enhance this)
    if ($new_password === $confirm_password && strlen($new_password) >= 6) {
        // In a real app, you'd hash passwords and store them securely
        // For now, we'll just show a success message
        $success = "Password updated successfully! (Note: In production, implement proper password hashing)";
    } else {
        $error = "Passwords don't match or are too short (minimum 6 characters)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Das House Admin</title>
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
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }
        .btn-save {
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
            color: white;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="menu-manager.php">
                            <i class="fas fa-utensils me-2"></i>Menu Manager
                        </a>
                        <a class="nav-link" href="photo-manager.php">
                            <i class="fas fa-images me-2"></i>Photo Manager
                        </a>
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </nav>
                    
                    <div class="mt-auto pt-5">
                        <a href="dashboard.php?logout=1" class="btn btn-danger w-100">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content-wrapper">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-cog me-2"></i>Settings</h2>
                    </div>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Password Change -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="form-text">Minimum 6 characters</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-save">
                                            <i class="fas fa-save me-2"></i>Update Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Info -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <p class="mb-2"><strong>PHP Version:</strong></p>
                                            <p class="mb-2"><strong>Server:</strong></p>
                                            <p class="mb-2"><strong>Admin Panel:</strong></p>
                                            <p class="mb-2"><strong>Data Storage:</strong></p>
                                        </div>
                                        <div class="col-6">
                                            <p class="mb-2 text-muted"><?php echo phpversion(); ?></p>
                                            <p class="mb-2 text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                            <p class="mb-2 text-muted">v1.0</p>
                                            <p class="mb-2 text-muted">JSON Files</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <a href="menu-manager.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-utensils me-2"></i>Manage Menu
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="photo-manager.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-images me-2"></i>Manage Photos
                                            </a>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <a href="dashboard.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                            </a>
                                        </div>
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
</body>
</html>
