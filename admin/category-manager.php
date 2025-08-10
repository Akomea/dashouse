<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Load categories from database
require_once 'includes/SupabaseDB.php';
$db = new SupabaseDB();

try {
    $categories_sql = "SELECT * FROM categories ORDER BY sort_order, name";
    $categories = $db->fetchAll($categories_sql);
    
    if ($categories === false) {
        $categories = [];
    }
} catch (Exception $e) {
    $categories = [];
    error_log("Error loading categories: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Manager - Das House Admin</title>
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
        .btn-add {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        .category-item {
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .category-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .category-item.inactive {
            border-left-color: #6c757d;
            opacity: 0.6;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .alert {
            border-radius: 10px;
        }
        .status-badge {
            font-size: 0.75rem;
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
                        <a class="nav-link active" href="category-manager.php">
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
                        <h2><i class="fas fa-tags me-2"></i>Category Manager</h2>
                        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                    </div>
                    
                    <!-- Alert Container -->
                    <div id="alertContainer"></div>
                    
                    <!-- Categories Container -->
                    <div id="categoriesContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading categories...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCategoryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the category..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Category Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Upload an image to represent this category (JPEG, PNG, GIF, WebP up to 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="addCategoryBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCategoryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Category Image</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                            <div class="form-text">Leave empty to keep current image, or upload a new one</div>
                            <div id="current_image_preview" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editCategoryBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class CategoryManager {
            constructor() {
                this.init();
            }
            
            init() {
                this.loadCategories();
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                // Add category form
                document.getElementById('addCategoryForm').addEventListener('submit', (e) => this.handleAddCategory(e));
                
                // Edit category form
                document.getElementById('editCategoryForm').addEventListener('submit', (e) => this.handleEditCategory(e));
            }
            
            async loadCategories() {
                try {
                    const response = await fetch('api/categories.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.renderCategories(result.data);
                    } else {
                        this.showAlert('Error loading categories: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error loading categories:', error);
                    this.showAlert('Failed to load categories. Please try again.', 'danger');
                }
            }
            
            renderCategories(categories) {
                const container = document.getElementById('categoriesContainer');
                
                if (!categories || categories.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No categories found</h5>
                            <p class="text-muted">Start by adding your first category!</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                categories.forEach(category => {
                    const statusBadge = category.is_active 
                        ? '<span class="badge bg-success status-badge">Active</span>'
                        : '<span class="badge bg-secondary status-badge">Inactive</span>';
                    
                    html += `
                        <div class="col-12 mb-3">
                            <div class="card category-item ${category.is_active ? '' : 'inactive'}">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            ${category.image_url ? 
                                                `<img src="${category.image_url}" alt="${this.escapeHtml(category.name)}" class="img-fluid rounded" style="max-width: 80px; max-height: 80px; object-fit: cover;">` : 
                                                `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                    <i class="fas fa-image text-muted fa-2x"></i>
                                                </div>`
                                            }
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="mb-1">${this.escapeHtml(category.name)}</h6>
                                            <p class="text-muted mb-2">${this.escapeHtml(category.description || '')}</p>
                                            <div class="d-flex align-items-center gap-2">
                                                ${statusBadge}
                                                <small class="text-muted">Sort: ${category.sort_order || 0}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="categoryManager.editCategory('${category.id}', '${this.escapeHtml(category.name)}', '${this.escapeHtml(category.description || '')}', ${category.sort_order || 0}, ${category.is_active}, '${category.image_url || ''}')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger me-2" onclick="categoryManager.deleteCategory('${category.id}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button class="btn btn-sm ${category.is_active ? 'btn-outline-warning' : 'btn-outline-success'}" onclick="categoryManager.toggleCategory('${category.id}', ${category.is_active})">
                                                <i class="fas fa-${category.is_active ? 'eye-slash' : 'eye'}"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
            }
            
            async handleAddCategory(e) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = document.getElementById('addCategoryBtn');
                const spinner = submitBtn.querySelector('.spinner-border');
                
                // Show loading state
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                
                try {
                    const formData = new FormData(form);
                    
                    // Check if image is selected
                    const imageFile = formData.get('image');
                    if (imageFile && imageFile.size > 0) {
                        // File upload - send as FormData
                        const response = await fetch('api/categories.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.showAlert('Category added successfully!', 'success');
                            form.reset();
                            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                            this.loadCategories(); // Refresh the list
                        } else {
                            this.showAlert('Error adding category: ' + result.error, 'danger');
                        }
                    } else {
                        // No image - send as JSON
                        const data = {
                            name: formData.get('name'),
                            description: formData.get('description') || '',
                            sort_order: parseInt(formData.get('sort_order')) || 0,
                            is_active: formData.get('is_active') === '1'
                        };
                        
                        const response = await fetch('api/categories.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(data)
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.showAlert('Category added successfully!', 'success');
                            form.reset();
                            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                            this.loadCategories(); // Refresh the list
                        } else {
                            this.showAlert('Error adding category: ' + result.error, 'danger');
                        }
                    }
                } catch (error) {
                    console.error('Error adding category:', error);
                    this.showAlert('Failed to add category. Please try again.', 'danger');
                } finally {
                    // Reset loading state
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                }
            }
            
            async handleEditCategory(e) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = document.getElementById('editCategoryBtn');
                const spinner = submitBtn.querySelector('.spinner-border');
                
                // Show loading state
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                
                try {
                    const formData = new FormData(form);
                    const imageFile = formData.get('image');
                    
                    if (imageFile && imageFile.size > 0) {
                        // Handle image upload separately
                        const imageFormData = new FormData();
                        imageFormData.append('id', formData.get('id'));
                        imageFormData.append('image', imageFile);
                        
                        const imageResponse = await fetch('api/categories.php', {
                            method: 'PATCH',
                            body: imageFormData
                        });
                        
                        const imageResult = await imageResponse.json();
                        if (!imageResult.success) {
                            this.showAlert('Error updating image: ' + imageResult.error, 'danger');
                            return;
                        }
                    }
                    
                    // Update other fields
                    const data = {
                        name: formData.get('name'),
                        description: formData.get('description') || '',
                        sort_order: parseInt(formData.get('sort_order')) || 0,
                        is_active: formData.get('is_active') === '1'
                    };
                    
                    const response = await fetch(`api/categories.php`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: new URLSearchParams(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Category updated successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
                        this.loadCategories(); // Refresh the list
                    } else {
                        this.showAlert('Error updating category: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error updating category:', error);
                    this.showAlert('Failed to update category. Please try again.', 'danger');
                } finally {
                    // Reset loading state
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                }
            }
            
            async deleteCategory(id) {
                if (!confirm('Are you sure you want to delete this category? This will also affect any menu items in this category.')) {
                    return;
                }
                
                try {
                    const response = await fetch(`api/categories.php`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: new URLSearchParams({ id })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Category deleted successfully!', 'success');
                        this.loadCategories(); // Refresh the list
                    } else {
                        this.showAlert('Error deleting category: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error deleting category:', error);
                    this.showAlert('Failed to delete category. Please try again.', 'danger');
                }
            }
            
            async toggleCategory(id, currentStatus) {
                try {
                    const response = await fetch(`api/categories.php`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: new URLSearchParams({
                            id,
                            is_active: !currentStatus
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert(`Category ${!currentStatus ? 'activated' : 'deactivated'} successfully!`, 'success');
                        this.loadCategories(); // Refresh the list
                    } else {
                        this.showAlert('Error updating category: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error updating category:', error);
                    this.showAlert('Failed to update category. Please try again.', 'danger');
                }
            }
            
            editCategory(id, name, description, sortOrder, isActive, imageUrl = '') {
                // Set form values
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_sort_order').value = sortOrder;
                document.getElementById('edit_is_active').checked = isActive;
                
                // Show current image preview
                const imagePreview = document.getElementById('current_image_preview');
                if (imageUrl) {
                    imagePreview.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <img src="${imageUrl}" alt="Current image" class="img-thumbnail" style="max-width: 60px; max-height: 60px; object-fit: cover;">
                            <small class="text-muted">Current image</small>
                        </div>
                    `;
                } else {
                    imagePreview.innerHTML = '<small class="text-muted">No image uploaded</small>';
                }
                
                // Show modal
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            }
            
            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertId = 'alert-' + Date.now();
                
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" id="${alertId}" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                `;
                
                alertContainer.innerHTML = alertHtml;
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    const alert = document.getElementById(alertId);
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Initialize category manager when DOM is ready
        let categoryManager;
        document.addEventListener('DOMContentLoaded', () => {
            categoryManager = new CategoryManager();
        });
    </script>
</body>
</html>
