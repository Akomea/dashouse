<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Load categories from database
require_once 'includes/SupabaseDB.php';
require_once 'includes/SupabaseStorage.php';

$db = new SupabaseDB();

try {
    $categories_sql = "SELECT * FROM categories WHERE is_active = true ORDER BY sort_order, name";
    $categories_data = $db->fetchAll($categories_sql);
    
    if ($categories_data === false) {
        $categories_data = [];
    }
} catch (Exception $e) {
    $categories_data = [];
    error_log("Error loading categories: " . $e->getMessage());
}

// Load photo data
$photos_file = '../data/photos.json';
$photos_data = [];

if (file_exists($photos_file)) {
    $photos_data = json_decode(file_get_contents($photos_file), true) ?: [];
}

// Handle file uploads
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $filename = $file['name'];
        $category_id = $_POST['category_id'];
        $description = $_POST['description'] ?? '';
        $set_as_category_image = isset($_POST['set_as_category_image']);
        
        // Upload to Supabase storage
        $storage = new SupabaseStorage();
        $uploadResult = $storage->handleFormUpload($file);
        
        if ($uploadResult['success']) {
            // Add to photos data
            $new_photo = [
                'id' => uniqid(),
                'filename' => $uploadResult['filename'],
                'original_name' => $filename,
                'category_id' => $category_id,
                'description' => $description,
                'path' => $uploadResult['url'],
                'uploaded_at' => date('Y-m-d H:i:s'),
                'active' => true
            ];
            
            $photos_data[] = $new_photo;
            
            // Save to file
            if (!is_dir('../data')) {
                mkdir('../data', 0755, true);
            }
            file_put_contents($photos_file, json_encode($photos_data, JSON_PRETTY_PRINT));
            
            // If set as category image, update the category
            if ($set_as_category_image && $category_id) {
                $result = $db->update('categories', ['image_url' => $uploadResult['url']], 'id = :id', ['id' => $category_id]);
                if ($result !== false) {
                    // Redirect to refresh and show updated category image
                    header('Location: photo-manager.php?success=category_image_set');
                    exit;
                } else {
                    // Redirect with error message
                    header('Location: photo-manager.php?error=category_image_failed');
                    exit;
                }
            } else {
                // Redirect with success message
                header('Location: photo-manager.php?success=photo_uploaded');
                exit;
            }
        } else {
            header('Location: photo-manager.php?error=upload_failed');
            exit;
        }
    } else {
        header('Location: photo-manager.php?error=no_file');
        exit;
    }
}

// Handle photo actions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'delete':
            $photo_id = $_POST['photo_id'];
            foreach ($photos_data as $key => $photo) {
                if ($photo['id'] === $photo_id) {
                    // Delete file from server
                    $file_path = '../' . $photo['path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    // Remove from data
                    unset($photos_data[$key]);
                    break;
                }
            }
            // Reindex array
            $photos_data = array_values($photos_data);
            break;
            
        case 'toggle':
            $photo_id = $_POST['photo_id'];
            foreach ($photos_data as &$photo) {
                if ($photo['id'] === $photo_id) {
                    $photo['active'] = !$photo['active'];
                    break;
                }
            }
            break;
            
        case 'set_category_image':
            $photo_id = $_POST['photo_id'];
            $category_id = $_POST['category_id'];
            
            // Find the photo
            foreach ($photos_data as $photo) {
                if ($photo['id'] === $photo_id) {
                    // Update category with this photo's URL
                    $result = $db->update('categories', ['image_url' => $photo['path']], 'id = :id', ['id' => $category_id]);
                    if ($result !== false) {
                        // Redirect to refresh and show updated category image
                        header('Location: photo-manager.php?success=category_image_set');
                        exit;
                    } else {
                        header('Location: photo-manager.php?error=category_image_failed');
                        exit;
                    }
                }
            }
            break;
    }
    
    // Save changes
    if (!is_dir('../data')) {
        mkdir('../data', 0755, true);
    }
    file_put_contents($photos_file, json_encode($photos_data, JSON_PRETTY_PRINT));
    
    // Redirect to refresh
    header('Location: photo-manager.php?success=1');
    exit;
}

// Get existing photos by category
$photos_by_category = [];
foreach ($categories_data as $category) {
    $photos_by_category[$category['id']] = array_filter($photos_data, function($photo) use ($category) {
        return isset($photo['category_id']) ? $photo['category_id'] == $category['id'] : $photo['category'] === $category['name'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Manager - Das House Admin</title>
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
        .btn-upload {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
            color: white;
        }
        .photo-item {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .photo-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .photo-item.inactive {
            opacity: 0.6;
            border-color: #6c757d;
        }
        .photo-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #f8f9fa;
        }
        .upload-area {
            border: 2px dashed #4ecdc4;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background-color: #f8fffe;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #44a08d;
            background-color: #f0fffd;
        }
        .upload-area.dragover {
            border-color: #44a08d;
            background-color: #e0fffa;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4ecdc4;
            box-shadow: 0 0 0 0.2rem rgba(78, 205, 196, 0.25);
        }
        .category-header {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
            color: white;
            border-radius: 10px 10px 0 0;
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
                        <a class="nav-link active" href="photo-manager.php">
                            <i class="fas fa-images me-2"></i>Photo Manager
                        </a>
                        <a class="nav-link" href="gift-shop-manager.php">
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
                        <h2><i class="fas fa-images me-2"></i>Photo Manager</h2>
                        <button class="btn btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-2"></i>Upload Photos
                        </button>
                    </div>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php 
                            switch($_GET['success']) {
                                case 'category_image_set':
                                    echo 'Photo set as category image successfully!';
                                    break;
                                case 'photo_uploaded':
                                    echo 'Photo uploaded successfully!';
                                    break;
                                case '1':
                                default:
                                    echo 'Photos updated successfully!';
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
                                case 'category_image_failed':
                                    echo 'Failed to set photo as category image.';
                                    break;
                                case 'upload_failed':
                                    echo 'Failed to upload photo.';
                                    break;
                                case 'no_file':
                                    echo 'Please select a valid photo file.';
                                    break;
                                default:
                                    echo 'An error occurred.';
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
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Drag & Drop Photos Here</h5>
                                <p class="text-muted">or click the upload button above</p>
                                <small class="text-muted">Supports: JPG, PNG, GIF, WebP (Max: 5MB)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Photos by Category -->
                    <div class="row">
                        <?php foreach ($categories_data as $category): ?>
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header category-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($category['name']); ?>
                                        </h5>
                                        <?php if (!empty($category['image_url'])): ?>
                                            <div class="current-category-image">
                                                <small class="text-light me-2">Current image:</small>
                                                <img src="<?php echo htmlspecialchars($category['image_url']); ?>" 
                                                     alt="Category image" 
                                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px; border: 2px solid rgba(255,255,255,0.3);">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        $category_photos = $photos_by_category[$category['id']] ?? [];
                                        ?>
                                        
                                        <?php if (empty($category_photos)): ?>
                                            <p class="text-muted text-center">No photos in this category yet</p>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($category_photos as $photo): ?>
                                                    <div class="col-md-4 col-lg-3 mb-3">
                                                        <div class="photo-item <?php echo $photo['active'] ? '' : 'inactive'; ?>">
                                                            <img src="<?php 
                                                                    // Handle both Supabase URLs and local paths
                                                                    $imagePath = $photo['path'];
                                                                    if (strpos($imagePath, 'http') === 0) {
                                                                        // Full URL (Supabase), use as is
                                                                        echo htmlspecialchars($imagePath);
                                                                    } else {
                                                                        // Local path, add ../ prefix
                                                                        echo '../' . htmlspecialchars($imagePath);
                                                                    }
                                                                ?>" 
                                                                 alt="<?php echo htmlspecialchars($photo['description'] ?: $photo['original_name']); ?>"
                                                                 class="photo-preview"
                                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                                                            <div class="p-3">
                                                                <h6 class="mb-2"><?php echo htmlspecialchars($photo['original_name']); ?></h6>
                                                                <?php if ($photo['description']): ?>
                                                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($photo['description']); ?></p>
                                                                <?php endif; ?>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($photo['uploaded_at'])); ?></small>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <form method="POST" class="d-inline">
                                                                            <input type="hidden" name="action" value="set_category_image">
                                                                            <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                                            <button type="submit" class="btn btn-outline-primary" title="Set as Category Image">
                                                                                <i class="fas fa-star"></i>
                                                                            </button>
                                                                        </form>
                                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this photo?')">
                                                                            <input type="hidden" name="action" value="delete">
                                                                            <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                                                            <button type="submit" class="btn btn-outline-danger">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                        <form method="POST" class="d-inline">
                                                                            <input type="hidden" name="action" value="toggle">
                                                                            <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                                                            <button type="submit" class="btn <?php echo $photo['active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                                                                                <i class="fas fa-<?php echo $photo['active'] ? 'eye-slash' : 'eye'; ?>"></i>
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
                        <?php endforeach; ?>
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
                    <h5 class="modal-title">Upload New Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-3">
                            <label for="photo" class="form-label">Select Photo *</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                            <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_data as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the photo, what it shows, etc."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="set_as_category_image" name="set_as_category_image">
                                <label class="form-check-label" for="set_as_category_image">
                                    <strong>Set as Category Image</strong>
                                    <br><small class="text-muted">This photo will become the featured image for the selected category</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tip:</strong> Use descriptive names and descriptions to make photos easier to find and manage later.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Upload Photo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                
                // Open upload modal
                const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
                uploadModal.show();
            }
        }
        
        // File size validation
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.size > 5 * 1024 * 1024) { // 5MB
                alert('File size must be less than 5MB');
                this.value = '';
            }
        });
    </script>
</body>
</html>
