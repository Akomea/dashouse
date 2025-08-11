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
    $categories_sql = "SELECT * FROM categories WHERE is_active = true ORDER BY sort_order, name";
    $categories = $db->fetchAll($categories_sql);
    
    if ($categories === false) {
        $categories = [];
    }
} catch (Exception $e) {
    $categories = [];
    error_log("Error loading categories: " . $e->getMessage());
}

// Get dietary options
$dietary_options = ['Vegan', 'Vegetarian', 'Gluten-Free', 'Dairy-Free', 'Halal', 'Kosher'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Manager - Das House Admin</title>
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
        .menu-item {
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        .menu-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .menu-item.inactive {
            border-left-color: #6c757d;
            opacity: 0.6;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .dietary-badge {
            font-size: 0.75rem;
            margin: 2px;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .alert {
            border-radius: 10px;
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
                        <a class="nav-link active" href="menu-manager.php">
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
                        <h2><i class="fas fa-utensils me-2"></i>Menu Manager</h2>
                        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus me-2"></i>Add Menu Item
                        </button>
                    </div>
                    
                    <!-- Alert Container -->
                    <div id="alertContainer"></div>
                    
                    <!-- Menu Items Container -->
                    <div id="menuItemsContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading menu items...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addItemForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (€) *</label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.10" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the item, ingredients, etc."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Dietary Options</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_vegetarian" value="1" id="is_vegetarian">
                                        <label class="form-check-label" for="is_vegetarian">Vegetarian</label>
                                    </div>
                                </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_vegan" value="1" id="is_vegan">
                                        <label class="form-check-label" for="is_vegan">Vegan</label>
                                    </div>
                                        </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_gluten_free" value="1" id="is_gluten_free">
                                        <label class="form-check-label" for="is_gluten_free">Gluten-Free</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="allergens" class="form-label">Allergens</label>
                            <input type="text" class="form-control" id="allergens" name="allergens" placeholder="e.g., Nuts, Dairy, Shellfish">
                        </div>
                        
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="addItemBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editItemForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_price" class="form-label">Price (€) *</label>
                                    <input type="number" class="form-control" id="edit_price" name="price" step="0.10" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category_id" class="form-label">Category *</label>
                            <select class="form-select" id="edit_category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Dietary Options</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_vegetarian" value="1" id="edit_is_vegetarian">
                                        <label class="form-check-label" for="edit_is_vegetarian">Vegetarian</label>
                                    </div>
                                </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_vegan" value="1" id="edit_is_vegan">
                                        <label class="form-check-label" for="edit_is_vegan">Vegan</label>
                                    </div>
                                        </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_gluten_free" value="1" id="edit_is_gluten_free">
                                        <label class="form-check-label" for="edit_is_gluten_free">Gluten-Free</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_allergens" class="form-label">Allergens</label>
                            <input type="text" class="form-control" id="edit_allergens" name="allergens">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editItemBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/supabase-client.js"></script>
    <script>
        class MenuManager {
            constructor() {
                this.init();
            }
            
            init() {
                this.loadMenuItems();
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                // Add item form
                document.getElementById('addItemForm').addEventListener('submit', (e) => this.handleAddItem(e));
                
                // Edit item form
                document.getElementById('editItemForm').addEventListener('submit', (e) => this.handleEditItem(e));
            }
            
            async loadMenuItems() {
                try {
                    const response = await fetch('api/menu-items.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.renderMenuItems(result.data);
                    } else {
                        this.showAlert('Error loading menu items: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error loading menu items:', error);
                    this.showAlert('Failed to load menu items. Please try again.', 'danger');
                }
            }
            
            renderMenuItems(menuItems) {
                const container = document.getElementById('menuItemsContainer');
                
                if (!menuItems || menuItems.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No menu items found</h5>
                            <p class="text-muted">Start by adding your first menu item!</p>
                        </div>
                    `;
                    return;
                }
                
                // Group items by category
                const groupedItems = {};
                menuItems.forEach(item => {
                    const categoryName = item.category_name || 'Uncategorized';
                    if (!groupedItems[categoryName]) {
                        groupedItems[categoryName] = [];
                    }
                    groupedItems[categoryName].push(item);
                });
                
                let html = '';
                Object.keys(groupedItems).forEach(categoryName => {
                    html += `
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tag me-2"></i>${this.escapeHtml(categoryName)}
                                    </h5>
                                </div>
                                <div class="card-body">
                    `;
                    
                    groupedItems[categoryName].forEach(item => {
                        const dietaryBadges = [];
                        if (item.is_vegetarian) dietaryBadges.push('<span class="badge bg-success dietary-badge">Vegetarian</span>');
                        if (item.is_vegan) dietaryBadges.push('<span class="badge bg-info dietary-badge">Vegan</span>');
                        if (item.is_gluten_free) dietaryBadges.push('<span class="badge bg-warning dietary-badge">Gluten-Free</span>');
                        
                        html += `
                            <div class="menu-item p-3 mb-3 ${item.is_active ? '' : 'inactive'}" data-id="${item.id}">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">${this.escapeHtml(item.name)}</h6>
                                        <p class="text-muted mb-2">${this.escapeHtml(item.description || '')}</p>
                                        ${dietaryBadges.length > 0 ? `<div class="mb-2">${dietaryBadges.join('')}</div>` : ''}
                                        ${item.allergens ? `<small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>${this.escapeHtml(item.allergens)}</small>` : ''}
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="h5 text-success">€${this.escapeHtml(item.price)}</span>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="menuManager.editItem('${item.id}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger me-2" onclick="menuManager.deleteItem('${item.id}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="btn btn-sm ${item.is_active ? 'btn-outline-warning' : 'btn-outline-success'}" onclick="menuManager.toggleItem('${item.id}', ${item.is_active})">
                                            <i class="fas fa-${item.is_active ? 'eye-slash' : 'eye'}"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
            }
            
            async handleAddItem(e) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = document.getElementById('addItemBtn');
                const spinner = submitBtn.querySelector('.spinner-border');
                
                // Show loading state
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                
                try {
                    const formData = new FormData(form);
                    
                    // Convert checkbox values to booleans
                    const data = {
                        name: formData.get('name'),
                        price: parseFloat(formData.get('price')),
                        category_id: formData.get('category_id'),
                        description: formData.get('description') || '',
                        is_vegetarian: formData.get('is_vegetarian') === '1',
                        is_vegan: formData.get('is_vegan') === '1',
                        is_gluten_free: formData.get('is_gluten_free') === '1',
                        allergens: formData.get('allergens') || '',
                        sort_order: parseInt(formData.get('sort_order')) || 0,
                        is_active: true
                    };
                    
                    const response = await fetch('api/menu-items.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Menu item added successfully!', 'success');
                        form.reset();
                        bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
                        this.loadMenuItems(); // Refresh the list
                    } else {
                        this.showAlert('Error adding menu item: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error adding menu item:', error);
                    this.showAlert('Failed to add menu item. Please try again.', 'danger');
                } finally {
                    // Reset loading state
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                }
            }
            
            async handleEditItem(e) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = document.getElementById('editItemBtn');
                const spinner = submitBtn.querySelector('.spinner-border');
                
                // Show loading state
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                
                try {
                    const formData = new FormData(form);
                    
                    // Convert checkbox values to booleans
                    const data = {
                        id: formData.get('id'),
                        name: formData.get('name'),
                        price: parseFloat(formData.get('price')),
                        category_id: formData.get('category_id'),
                        description: formData.get('description') || '',
                        is_vegetarian: formData.get('is_vegetarian') === '1',
                        is_vegan: formData.get('is_vegan') === '1',
                        is_gluten_free: formData.get('is_gluten_free') === '1',
                        allergens: formData.get('allergens') || '',
                        sort_order: parseInt(formData.get('sort_order')) || 0
                    };
                    
                    const response = await fetch(`api/menu-items.php`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: new URLSearchParams(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Menu item updated successfully!', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide();
                        this.loadMenuItems(); // Refresh the list
                    } else {
                        this.showAlert('Error updating menu item: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error updating menu item:', error);
                    this.showAlert('Failed to update menu item. Please try again.', 'danger');
                } finally {
                    // Reset loading state
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                }
            }
            
            async deleteItem(id) {
                if (!confirm('Are you sure you want to delete this menu item?')) {
                    return;
                }
                
                try {
                    const response = await fetch(`api/menu-items.php`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: new URLSearchParams({ id })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Menu item deleted successfully!', 'success');
                        this.loadMenuItems(); // Refresh the list
                    } else {
                        this.showAlert('Error deleting menu item: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error deleting menu item:', error);
                    this.showAlert('Failed to delete menu item. Please try again.', 'danger');
                }
            }
            
            async toggleItem(id, currentStatus) {
                try {
                    const response = await fetch(`api/menu-items.php`, {
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
                        this.showAlert(`Menu item ${!currentStatus ? 'activated' : 'deactivated'} successfully!`, 'success');
                        this.loadMenuItems(); // Refresh the list
                    } else {
                        this.showAlert('Error updating menu item: ' + result.error, 'danger');
                    }
                } catch (error) {
                    console.error('Error updating menu item:', error);
                    this.showAlert('Failed to update menu item. Please try again.', 'danger');
                }
            }
            
            editItem(id) {
                // Find the item in the current display
                const itemElement = document.querySelector(`[data-id="${id}"]`);
                if (!itemElement) return;
                
                // Extract data from the DOM
                const name = itemElement.querySelector('h6').textContent;
                const description = itemElement.querySelector('p').textContent;
                const price = itemElement.querySelector('.text-success').textContent.replace('€', '');
                const categoryName = itemElement.closest('.card').querySelector('.card-header h5').textContent.replace(/^.*?tag me-2\s*/, '');
                
                // Find category ID by name
                const categorySelect = document.getElementById('edit_category_id');
                const categoryOption = Array.from(categorySelect.options).find(option => option.textContent === categoryName);
                const categoryId = categoryOption ? categoryOption.value : '';
                
                // Set form values
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_category_id').value = categoryId;
                
                // Reset dietary checkboxes
                document.getElementById('edit_is_vegetarian').checked = false;
                document.getElementById('edit_is_vegan').checked = false;
                document.getElementById('edit_is_gluten_free').checked = false;
                
                // Check dietary options based on badges
                const badges = itemElement.querySelectorAll('.dietary-badge');
                badges.forEach(badge => {
                    const text = badge.textContent;
                    if (text === 'Vegetarian') document.getElementById('edit_is_vegetarian').checked = true;
                    if (text === 'Vegan') document.getElementById('edit_is_vegan').checked = true;
                    if (text === 'Gluten-Free') document.getElementById('edit_is_gluten_free').checked = true;
                });
                
                // Set allergens
                const allergensElement = itemElement.querySelector('.text-danger');
                if (allergensElement) {
                    const allergens = allergensElement.textContent.replace(/^.*?triangle me-1\s*/, '');
                    document.getElementById('edit_allergens').value = allergens;
                } else {
                    document.getElementById('edit_allergens').value = '';
                }
                
                // Show modal
                new bootstrap.Modal(document.getElementById('editItemModal')).show();
            }
            
            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertId = 'alert-' + Date.now();
                
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" id="${alertId}" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
        
        // Initialize menu manager when DOM is ready
        let menuManager;
        document.addEventListener('DOMContentLoaded', () => {
            menuManager = new MenuManager();
        });
    </script>
</body>
</html>
