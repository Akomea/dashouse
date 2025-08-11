<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/supabase.php';
require_once 'includes/SupabaseStorage.php';

/**
 * Make a REST API call to Supabase
 */
function supabaseAPICall($endpoint, $method = 'GET', $data = null, $queryParams = null) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    
    if ($queryParams) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY, // Use service role for admin operations
        'apikey: ' . SUPABASE_SERVICE_ROLE_KEY
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("cURL error in gift-shop-manager: " . $curlError);
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        error_log("Supabase API error in gift-shop-manager: HTTP $httpCode - $response");
        return false;
    }
}

// Load gift shop data from Supabase
$gift_shop_data = supabaseAPICall('gift_shop_items', 'GET', null, ['order' => 'sort_order,name']) ?: [];

// Handle file uploads
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $filename = $file['name'];
        $name = $_POST['name'] ?? pathinfo($filename, PATHINFO_FILENAME);
        $description = $_POST['description'] ?? '';
        
        // Upload to Supabase storage
        $storage = new SupabaseStorage();
        $uploadResult = $storage->handleFormUpload($file);
        
        if ($uploadResult['success']) {
            // Add to Supabase database
            $new_item = [
                'name' => $name,
                'description' => $description,
                'image_url' => $uploadResult['url'],
                'filename' => $uploadResult['filename'],
                'original_name' => $filename,
                'active' => true,
                'sort_order' => count($gift_shop_data)
            ];
            
            $result = supabaseAPICall('gift_shop_items', 'POST', $new_item);
            
            if ($result !== false) {
                header('Location: gift-shop-manager.php?success=photo_uploaded');
                exit;
            } else {
                header('Location: gift-shop-manager.php?error=database_failed');
                exit;
            }
        } else {
            header('Location: gift-shop-manager.php?error=upload_failed');
            exit;
        }
    } else {
        header('Location: gift-shop-manager.php?error=no_file');
        exit;
    }
}

// Handle other actions
if ($_POST && isset($_POST['action'])) {
    $success = false;
    $error_message = '';
    
    switch ($_POST['action']) {
        case 'delete':
            $item_id = $_POST['item_id'];
            $queryParams = ['id' => 'eq.' . $item_id];
            $result = supabaseAPICall('gift_shop_items', 'DELETE', null, $queryParams);
            
            if ($result !== false) {
                $success = true;
            } else {
                $error_message = 'Failed to delete item from database';
            }
            break;
            
        case 'toggle':
            $item_id = $_POST['item_id'];
            
            // First, get the current state
            $current_item = null;
            foreach ($gift_shop_data as $item) {
                if ($item['id'] == $item_id) {
                    $current_item = $item;
                    break;
                }
            }
            
            if ($current_item) {
                $new_active_state = !$current_item['active'];
                $queryParams = ['id' => 'eq.' . $item_id];
                $update_data = ['active' => $new_active_state];
                $result = supabaseAPICall('gift_shop_items', 'PATCH', $update_data, $queryParams);
                
                if ($result !== false) {
                    $success = true;
                } else {
                    $error_message = 'Failed to toggle item status';
                }
            } else {
                $error_message = 'Item not found';
            }
            break;
            
        case 'update':
            $item_id = $_POST['item_id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            
            $queryParams = ['id' => 'eq.' . $item_id];
            $update_data = [
                'name' => $name,
                'description' => $description
            ];
            $result = supabaseAPICall('gift_shop_items', 'PATCH', $update_data, $queryParams);
            
            if ($result !== false) {
                $success = true;
            } else {
                $error_message = 'Failed to update item';
            }
            break;
    }
    
    // Redirect with appropriate message
    if ($success) {
        header('Location: gift-shop-manager.php?success=1');
    } else {
        header('Location: gift-shop-manager.php?error=' . urlencode($error_message));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Shop Manager - Das House Admin</title>
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
        .btn-upload {
            background: linear-gradient(135deg, #e67e22, #d35400);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230, 126, 34, 0.3);
            color: white;
        }
        .gift-item {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
        }
        .gift-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .gift-item.inactive {
            opacity: 0.6;
            border-color: #6c757d;
        }
        .gift-preview {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: #f8f9fa;
        }
        .upload-area {
            border: 2px dashed #e67e22;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background-color: #fef9f3;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #d35400;
            background-color: #fdf4e9;
        }
        .upload-area.dragover {
            border-color: #d35400;
            background-color: #fcf1e0;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #e67e22;
            box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.25);
        }
        .gift-header {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .edit-form {
            display: none;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
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
                        <a class="nav-link active" href="gift-shop-manager.php">
                            <i class="fas fa-gifts me-2"></i>Gift Shop Manager
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
                        <h2><i class="fas fa-gifts me-2"></i>Gift Shop Manager</h2>
                        <button class="btn btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-2"></i>Add Gift Item
                        </button>
                    </div>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php 
                            switch($_GET['success']) {
                                case 'photo_uploaded':
                                    echo 'Gift shop item added successfully!';
                                    break;
                                case '1':
                                default:
                                    echo 'Gift shop updated successfully!';
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php 
                            switch($_GET['error']) {
                                case 'upload_failed':
                                    echo 'Failed to upload gift item photo.';
                                    break;
                                case 'no_file':
                                    echo 'Please select a valid photo file.';
                                    break;
                                case 'database_failed':
                                    echo 'Failed to save gift item to database.';
                                    break;
                                default:
                                    echo htmlspecialchars(urldecode($_GET['error']));
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Upload Area -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-gifts fa-3x text-muted mb-3"></i>
                                <h5>Drag & Drop Gift Item Photos Here</h5>
                                <p class="text-muted">or click the "Add Gift Item" button above</p>
                                <small class="text-muted">Supports: JPG, PNG, GIF, WebP (Max: 5MB)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gift Shop Items -->
                    <div class="card">
                        <div class="gift-header">
                            <h4 class="mb-0">
                                <i class="fas fa-gifts me-2"></i>Gift Shop Items
                                <span class="badge bg-light text-dark ms-2"><?php echo count($gift_shop_data); ?> items</span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($gift_shop_data)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-gifts fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No gift items yet</h5>
                                    <p class="text-muted">Start by adding some photos of your café merchandise!</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($gift_shop_data as $item): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="gift-item <?php echo $item['active'] ? '' : 'inactive'; ?>">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="gift-preview"
                                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkdpZnQ8L3RleHQ+PC9zdmc+'">
                                                
                                                <div class="p-3">
                                                    <div class="item-display-<?php echo $item['id']; ?>">
                                                        <h6 class="mb-2"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <?php if ($item['description']): ?>
                                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Edit Form (hidden by default) -->
                                                    <div class="edit-form" id="edit-form-<?php echo $item['id']; ?>">
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                            <div class="mb-2">
                                                                <input type="text" class="form-control form-control-sm" name="name" 
                                                                       value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-2">
                                                                <textarea class="form-control form-control-sm" name="description" rows="2"
                                                                          placeholder="Description"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                            </div>
                                                            <div class="d-flex gap-2">
                                                                <button type="submit" class="btn btn-success btn-sm">Save</button>
                                                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEdit('<?php echo $item['id']; ?>')">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></small>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-primary" onclick="toggleEdit('<?php echo $item['id']; ?>')" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="toggle">
                                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                                <button type="submit" class="btn <?php echo $item['active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $item['active'] ? 'Hide' : 'Show'; ?>">
                                                                    <i class="fas fa-<?php echo $item['active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this gift item?')">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-gifts me-2"></i>Add Gift Shop Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Photo *</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                            <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Item Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   placeholder="e.g., Das House T-Shirt, Coffee Mug, Coaster">
                            <div class="form-text">Give your gift item a descriptive name</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Optional description of the gift item, materials, colors, etc."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tip:</strong> Upload high-quality photos that showcase your merchandise clearly. These will be displayed in the gift shop gallery.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-upload">Add Gift Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/supabase-client.js"></script>
    <script>
        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            uploadArea.classList.add('dragover');
        }
        
        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                // Auto-fill the file input and open modal
                const fileInput = document.getElementById('photo');
                fileInput.files = files;
                
                // Auto-fill name based on filename
                const filename = files[0].name;
                const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.')) || filename;
                document.getElementById('name').value = nameWithoutExt.replace(/[-_]/g, ' ');
                
                // Open upload modal
                const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
                uploadModal.show();
            }
        }
        
        // File size validation
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Auto-fill name if empty
                const nameField = document.getElementById('name');
                if (!nameField.value) {
                    const filename = file.name;
                    const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.')) || filename;
                    nameField.value = nameWithoutExt.replace(/[-_]/g, ' ');
                }
            }
        });
        
        // Toggle edit mode for items
        function toggleEdit(itemId) {
            const displayDiv = document.querySelector('.item-display-' + itemId);
            const editForm = document.getElementById('edit-form-' + itemId);
            
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                displayDiv.style.display = 'none';
                editForm.style.display = 'block';
            } else {
                displayDiv.style.display = 'block';
                editForm.style.display = 'none';
            }
        }
    </script>
</body>
</html>
