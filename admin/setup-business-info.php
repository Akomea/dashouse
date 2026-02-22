<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'includes/SupabaseDB.php';

$db = new SupabaseDB();
$message = '';
$error = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'setup_business_info') {
    try {
        // First, try to get existing business info
        $existing = $db->apiCall('business_info', 'GET', null, ['id' => 'eq.1']);
        
        if ($existing && is_array($existing) && count($existing) > 0) {
            $message = "Business info table already exists and has data.";
        } else {
            // Try to create the table using SQL (this might not work with Supabase REST API)
            // For now, we'll try to insert default data
            $defaultData = [
                'business_name' => 'Das House',
                'email' => 'info@dashouse.com',
                'phone' => '(555) 123-4567',
                'address' => 'Gumpendorfer strasse 51, Vienna, Austria',
                'description' => 'Welcome to Das House - your favorite local restaurant and gift shop!',
                'website' => 'https://dashouse.com',
                'monday_open' => '09:00',
                'monday_close' => '17:00',
                'tuesday_open' => '09:00',
                'tuesday_close' => '17:00',
                'wednesday_open' => '09:00',
                'wednesday_close' => '17:00',
                'thursday_open' => '09:00',
                'thursday_close' => '17:00',
                'friday_open' => '09:00',
                'friday_close' => '17:00',
                'saturday_open' => '10:00',
                'saturday_close' => '16:00',
                'sunday_open' => '',
                'sunday_close' => ''
            ];
            
            // Try to insert default data
            $result = $db->apiCall('business_info', 'POST', $defaultData);
            
            if ($result !== false) {
                $message = "Business info table initialized successfully with default data!";
            } else {
                $error = "Failed to initialize business info table. You may need to create the table manually using the SQL script.";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Business Info - Das House Admin</title>
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
                margin-left: 250px;
            }
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .btn-setup {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
            color: white;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
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
                        <a class="nav-link" href="category-manager.php">
                            <i class="fas fa-tags me-2"></i>Category Manager
                        </a>
                        <a class="nav-link" href="photo-manager.php">
                            <i class="fas fa-images me-2"></i>Photo Manager
                        </a>
                        <a class="nav-link" href="gift-shop-manager.php">
                            <i class="fas fa-gifts me-2"></i>Gift Shop Manager
                        </a>
                        <a class="nav-link" href="business-info-manager.php">
                            <i class="fas fa-building me-2"></i>Business Info
                        </a>
                        <a class="nav-link" href="settings.php">
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
                        <h2><i class="fas fa-database me-2"></i>Setup Business Info Table</h2>
                        <span class="text-muted">Initialize business information system</span>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Setup Instructions</h5>
                                </div>
                                <div class="card-body">
                                    <p>This setup will initialize the business information table with default data. The table stores:</p>
                                    <ul>
                                        <li>Business contact information (name, email, phone)</li>
                                        <li>Address details</li>
                                        <li>Operating hours for each day</li>
                                        <li>Business description and website</li>
                                    </ul>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Note:</strong> If the business_info table doesn't exist in your database, you'll need to create it first using the SQL script.
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="action" value="setup_business_info">
                                        <button type="submit" class="btn btn-setup">
                                            <i class="fas fa-play me-2"></i>Initialize Business Info Table
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-code me-2"></i>SQL Script</h5>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted">If the table doesn't exist, run this SQL script in your database:</p>
                                    <div class="code-block small">
                                        <a href="create-business-info-table.sql" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-download me-2"></i>Download SQL Script
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="business-info-manager.php" class="btn btn-outline-primary">
                                            <i class="fas fa-building me-2"></i>Business Info Manager
                                        </a>
                                        <a href="dashboard.php" class="btn btn-outline-secondary">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
