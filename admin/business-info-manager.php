<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'includes/SupabaseDB.php';
require_once 'includes/BusinessInfoCache.php';

$db = new SupabaseDB();
$cache = new BusinessInfoCache();
$success = '';
$error = '';

// Get current business info first - try cache first to reduce API calls
$currentInfo = null;
$cachedInfo = $cache->get();

if ($cachedInfo) {
    // Use cached data to reduce egress usage
    $currentInfo = $cachedInfo;
    error_log("BusinessInfoManager: Using cached business info");
} else {
    // Cache miss - fetch from API with minimal fields to reduce egress
    try {
        $result = $db->apiCall('business_info', 'GET', null, [
            'id' => 'eq.1',
            'select' => 'id,business_name,email,phone,address,description,website,monday_open,monday_close,tuesday_open,tuesday_close,wednesday_open,wednesday_close,thursday_open,thursday_close,friday_open,friday_close,saturday_open,saturday_close,sunday_open,sunday_close'
        ]);
        if ($result && is_array($result) && count($result) > 0) {
            $currentInfo = $result[0];
            // Cache the result
            $cache->set($currentInfo);
            error_log("BusinessInfoManager: Fetched and cached business info from API");
        } else {
            // No data found - avoid additional API call for table check
            error_log("BusinessInfoManager: No business info found, using defaults");
        }
    } catch (Exception $e) {
        $error = "Error loading business information: " . $e->getMessage();
    }
}

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_business_info') {
    error_log("=== Business Info Update Started ===");
    error_log("POST data received: " . print_r($_POST, true));
    
    try {
        $businessData = [
            'business_name' => $_POST['business_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'description' => $_POST['description'] ?? '',
            
            // Operating hours
            'monday_open' => $_POST['monday_open'] ?? '',
            'monday_close' => $_POST['monday_close'] ?? '',
            'tuesday_open' => $_POST['tuesday_open'] ?? '',
            'tuesday_close' => $_POST['tuesday_close'] ?? '',
            'wednesday_open' => $_POST['wednesday_open'] ?? '',
            'wednesday_close' => $_POST['wednesday_close'] ?? '',
            'thursday_open' => $_POST['thursday_open'] ?? '',
            'thursday_close' => $_POST['thursday_close'] ?? '',
            'friday_open' => $_POST['friday_open'] ?? '',
            'friday_close' => $_POST['friday_close'] ?? '',
            'saturday_open' => $_POST['saturday_open'] ?? '',
            'saturday_close' => $_POST['saturday_close'] ?? '',
            'sunday_open' => $_POST['sunday_open'] ?? '',
            'sunday_close' => $_POST['sunday_close'] ?? ''
        ];
        
        // Handle empty values - convert empty time strings to null for database
        // PostgreSQL TIME type doesn't accept empty strings, only NULL
        $filteredData = [];
        foreach ($businessData as $key => $value) {
            // For time fields, convert empty strings to null
            if (strpos($key, '_open') !== false || strpos($key, '_close') !== false) {
                $filteredData[$key] = ($value === '' || $value === null) ? null : $value;
            } else {
                // For other fields, only include non-empty values
                if ($value !== '' && $value !== null) {
                    $filteredData[$key] = $value;
                }
            }
        }
        $businessData = $filteredData;
        
        // Debug: Log the data being sent
        error_log("Business data being sent: " . json_encode($businessData));
        
        // Update business info - use POST for first time, PATCH for updates
        if ($currentInfo && isset($currentInfo['id'])) {
            // Update existing record
            error_log("Updating existing record with ID: " . $currentInfo['id']);
            $result = $db->apiCall('business_info', 'PATCH', $businessData, ['id' => 'eq.' . $currentInfo['id']]);
        } else {
            // Create new record
            error_log("Creating new business info record");
            $result = $db->apiCall('business_info', 'POST', $businessData);
        }
        
        // Debug: Log the API response
        error_log("API response: " . json_encode($result));
        
        if ($result !== false && $result !== null) {
            $success = "Business information updated successfully!";
            // Clear cache and update with the new data (avoid additional API call)
            $cache->clear();
            
            // If result is an array with data, use it; otherwise use submitted data
            if (is_array($result) && !empty($result)) {
                // Use the returned data from API
                $currentInfo = is_array($result[0] ?? null) ? $result[0] : $result;
            } else {
                // Use submitted data as fallback
                $currentInfo = array_merge($currentInfo ?: [], $businessData);
                $currentInfo['id'] = $currentInfo['id'] ?? 1;
            }
            
            // Cache the updated result
            $cache->set($currentInfo);
            
            error_log("Business info updated successfully");
        } else {
            $error = "Failed to update business information.";
            error_log("Update failed - result was false or null");
            
            // Check if this might be an egress limit issue
            if ($result === false) {
                $error .= " The API request failed. Check your PHP error log for detailed error information.";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Set default values if no data exists and ensure all fields have non-null values
if (!$currentInfo) {
    $currentInfo = [
        'business_name' => 'Das House',
        'email' => '',
        'phone' => '',
        'address' => '',
        'description' => '',
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
} else {
    // Ensure null values are converted to empty strings to prevent htmlspecialchars warnings
    foreach ($currentInfo as $key => $value) {
        if ($value === null) {
            $currentInfo[$key] = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Info Manager - Das House Admin</title>
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
            margin-bottom: 20px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }
        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .hours-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .day-row {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        .closed-day {
            border-left-color: #dc3545;
        }
        .time-input {
            max-width: 120px;
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
                        <a class="nav-link active" href="business-info-manager.php">
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
                        <div>
                            <h2><i class="fas fa-building me-2"></i>Business Information Manager</h2>
                            <span class="text-muted">Manage your business details and hours</span>
                        </div>
                        <button type="submit" form="business-info-form" class="btn btn-save btn-sm">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <?php if (strpos($error, "table doesn't exist") !== false): ?>
                                <div class="mt-2">
                                    <a href="setup-business-info.php" class="btn btn-warning btn-sm">
                                        <i class="fas fa-wrench me-1"></i>Setup Business Info Table
                                    </a>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="business-info-form">
                        <input type="hidden" name="action" value="update_business_info">
                        
                        <!-- Basic Business Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="business_name" class="form-label">Business Name</label>
                                        <input type="text" class="form-control" id="business_name" name="business_name" 
                                               value="<?php echo htmlspecialchars($currentInfo['business_name'] ?? 'Das House'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($currentInfo['email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($currentInfo['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Business Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              placeholder="Brief description of your business..."><?php echo htmlspecialchars($currentInfo['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="address" class="form-label">Street Address</label>
                                        <input type="text" class="form-control" id="address" name="address" 
                                               value="<?php echo htmlspecialchars($currentInfo['address'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Operating Hours -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Operating Hours</h5>
                            </div>
                            <div class="card-body">
                                <div class="hours-section">
                                    <?php
                                    $days = [
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday', 
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday'
                                    ];
                                    
                                    foreach ($days as $day => $dayName):
                                        $openKey = $day . '_open';
                                        $closeKey = $day . '_close';
                                        $isClosed = empty($currentInfo[$openKey]) && empty($currentInfo[$closeKey]);
                                    ?>
                                    <div class="day-row <?php echo $isClosed ? 'closed-day' : ''; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <h6 class="mb-0"><?php echo $dayName; ?></h6>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Open</label>
                                                <input type="time" class="form-control time-input" 
                                                       name="<?php echo $openKey; ?>" 
                                                       value="<?php echo htmlspecialchars($currentInfo[$openKey] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Close</label>
                                                <input type="time" class="form-control time-input" 
                                                       name="<?php echo $closeKey; ?>" 
                                                       value="<?php echo htmlspecialchars($currentInfo[$closeKey] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="<?php echo $day; ?>_closed" 
                                                           onchange="toggleDayHours('<?php echo $day; ?>')">
                                                    <label class="form-check-label small" for="<?php echo $day; ?>_closed">
                                                        Closed
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Tip:</strong> Leave both open and close times empty to mark a day as closed.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-save btn-lg">
                                <i class="fas fa-save me-2"></i>Save Business Information
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle day hours when closed checkbox is checked
        function toggleDayHours(day) {
            const openInput = document.querySelector(`input[name="${day}_open"]`);
            const closeInput = document.querySelector(`input[name="${day}_close"]`);
            const dayRow = openInput.closest('.day-row');
            const closedCheckbox = document.getElementById(`${day}_closed`);
            
            if (closedCheckbox.checked) {
                openInput.value = '';
                closeInput.value = '';
                // Don't disable inputs - just make them readonly so they still submit
                openInput.readOnly = true;
                closeInput.readOnly = true;
                openInput.style.backgroundColor = '#f8f9fa';
                closeInput.style.backgroundColor = '#f8f9fa';
                dayRow.classList.add('closed-day');
            } else {
                openInput.readOnly = false;
                closeInput.readOnly = false;
                openInput.style.backgroundColor = '';
                closeInput.style.backgroundColor = '';
                dayRow.classList.remove('closed-day');
            }
        }
        
        // Initialize closed days on page load
        document.addEventListener('DOMContentLoaded', function() {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            days.forEach(day => {
                const openInput = document.querySelector(`input[name="${day}_open"]`);
                const closeInput = document.querySelector(`input[name="${day}_close"]`);
                const closedCheckbox = document.getElementById(`${day}_closed`);
                
                if (openInput && closeInput && closedCheckbox) {
                    if (openInput.value === '' && closeInput.value === '') {
                        closedCheckbox.checked = true;
                        openInput.readOnly = true;
                        closeInput.readOnly = true;
                        openInput.style.backgroundColor = '#f8f9fa';
                        closeInput.style.backgroundColor = '#f8f9fa';
                        openInput.closest('.day-row').classList.add('closed-day');
                    }
                }
            });
            
            // Add form submission logging for debugging
            const form = document.getElementById('business-info-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    console.log('Form submitting...');
                    const formData = new FormData(form);
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        data[key] = value;
                        if (key.includes('_open') || key.includes('_close')) {
                            console.log(`${key}: "${value}"`);
                        }
                    }
                    console.log('All form data:', data);
                });
            }
        });
    </script>
</body>
</html>
