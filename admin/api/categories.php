<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/SupabaseDB.php';
require_once '../includes/SupabaseStorage.php';

$db = new SupabaseDB();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all categories
            $is_active = $_GET['is_active'] ?? true;
            
            $sql = "SELECT * FROM categories WHERE is_active = :is_active ORDER BY sort_order, name";
            $categories = $db->fetchAll($sql, ['is_active' => $is_active]);
            
            if ($categories !== false) {
                echo json_encode([
                    'success' => true,
                    'data' => $categories,
                    'count' => count($categories)
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to fetch categories'
                ]);
            }
            break;
            
        case 'POST':
            // Add new category
            if (!isset($_POST['name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Category name is required'
                ]);
                break;
            }
            
            $imageUrl = '';
            
            // Handle file upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $storage = new SupabaseStorage();
                $uploadResult = $storage->handleFormUpload($_FILES['image']);
                
                if ($uploadResult['success']) {
                    $imageUrl = $uploadResult['url'];
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Image upload failed: ' . $uploadResult['error']
                    ]);
                    break;
                }
            } else {
                // Use provided image_url if no file uploaded
                $imageUrl = $_POST['image_url'] ?? '';
            }
            
            $category = [
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'image_url' => $imageUrl,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_active' => $_POST['is_active'] ?? true
            ];
            
            $result = $db->insert('categories', $category);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category added successfully',
                    'id' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to add category'
                ]);
            }
            break;
            
        case 'PUT':
            // Update category
            parse_str(file_get_contents("php://input"), $_PUT);
            
            if (!isset($_PUT['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Category ID is required'
                ]);
                break;
            }
            
            $update_data = [];
            $allowed_fields = ['name', 'description', 'image_url', 'sort_order', 'is_active'];
            
            foreach ($allowed_fields as $field) {
                if (isset($_PUT[$field])) {
                    $update_data[$field] = $_PUT[$field];
                }
            }
            
            if (empty($update_data)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'No fields to update'
                ]);
                break;
            }
            
            $result = $db->update('categories', $update_data, 'id = :id', ['id' => $_PUT['id']]);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'rows_affected' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update category'
                ]);
            }
            break;
            
        case 'DELETE':
            // Delete category (soft delete by setting inactive)
            parse_str(file_get_contents("php://input"), $_DELETE);
            
            if (!isset($_DELETE['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Category ID is required'
                ]);
                break;
            }
            
            // Check if category has menu items
            $check_sql = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = :id";
            $check_result = $db->fetch($check_sql, ['id' => $_DELETE['id']]);
            
            if ($check_result && $check_result['count'] > 0) {
                // Soft delete - set inactive
                $result = $db->update('categories', ['is_active' => false], 'id = :id', ['id' => $_DELETE['id']]);
                $message = 'Category deactivated (has menu items)';
            } else {
                // Hard delete if no menu items
                $result = $db->delete('categories', 'id = :id', ['id' => $_DELETE['id']]);
                $message = 'Category deleted successfully';
            }
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'rows_affected' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete category'
                ]);
            }
            break;
            
        case 'PATCH':
            // Handle image upload for existing category
            if (!isset($_POST['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Category ID is required'
                ]);
                break;
            }
            
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Image file is required'
                ]);
                break;
            }
            
            $storage = new SupabaseStorage();
            $uploadResult = $storage->handleFormUpload($_FILES['image']);
            
            if ($uploadResult['success']) {
                // Update category with new image URL
                $result = $db->update('categories', ['image_url' => $uploadResult['url']], 'id = :id', ['id' => $_POST['id']]);
                
                if ($result !== false) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Category image updated successfully',
                        'image_url' => $uploadResult['url']
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to update category image'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Image upload failed: ' . $uploadResult['error']
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}

$db->close();
?>
