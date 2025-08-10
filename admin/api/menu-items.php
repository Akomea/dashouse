<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/SupabaseDB.php';

$db = new SupabaseDB();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get menu items with optional filtering
            $category_id = $_GET['category_id'] ?? null;
            $is_active = $_GET['is_active'] ?? true;
            
            if ($category_id) {
                $sql = "SELECT mi.*, c.name as category_name 
                        FROM menu_items mi 
                        JOIN categories c ON mi.category_id = c.id 
                        WHERE mi.category_id = :category_id AND mi.is_active = :is_active 
                        ORDER BY mi.sort_order, mi.name";
                $params = ['category_id' => $category_id, 'is_active' => $is_active];
            } else {
                $sql = "SELECT mi.*, c.name as category_name 
                        FROM menu_items mi 
                        JOIN categories c ON mi.category_id = c.id 
                        WHERE mi.is_active = :is_active 
                        ORDER BY c.sort_order, mi.sort_order, mi.name";
                $params = ['is_active' => $is_active];
            }
            
            $menu_items = $db->fetchAll($sql, $params);
            
            if ($menu_items !== false) {
                echo json_encode([
                    'success' => true,
                    'data' => $menu_items,
                    'count' => count($menu_items)
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to fetch menu items'
                ]);
            }
            break;
            
        case 'POST':
            // Add new menu item (admin only)
            if (!isset($_POST['name']) || !isset($_POST['price']) || !isset($_POST['category_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields: name, price, category_id'
                ]);
                break;
            }
            
            $menu_item = [
                'category_id' => $_POST['category_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'],
                'image_url' => $_POST['image_url'] ?? '',
                'is_vegetarian' => $_POST['is_vegetarian'] ?? false,
                'is_vegan' => $_POST['is_vegan'] ?? false,
                'is_gluten_free' => $_POST['is_gluten_free'] ?? false,
                'allergens' => $_POST['allergens'] ?? '',
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_active' => $_POST['is_active'] ?? true
            ];
            
            $result = $db->insert('menu_items', $menu_item);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item added successfully',
                    'id' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to add menu item'
                ]);
            }
            break;
            
        case 'PUT':
            // Update menu item (admin only)
            parse_str(file_get_contents("php://input"), $_PUT);
            
            if (!isset($_PUT['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing menu item ID'
                ]);
                break;
            }
            
            $update_data = [];
            $allowed_fields = ['name', 'description', 'price', 'image_url', 'is_vegetarian', 'is_vegan', 'is_gluten_free', 'allergens', 'sort_order', 'is_active', 'category_id'];
            
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
            
            $result = $db->update('menu_items', $update_data, 'id = :id', ['id' => $_PUT['id']]);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item updated successfully',
                    'rows_affected' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update menu item'
                ]);
            }
            break;
            
        case 'DELETE':
            // Delete menu item (admin only)
            parse_str(file_get_contents("php://input"), $_DELETE);
            
            if (!isset($_DELETE['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing menu item ID'
                ]);
                break;
            }
            
            $result = $db->delete('menu_items', 'id = :id', ['id' => $_DELETE['id']]);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item deleted successfully',
                    'rows_affected' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete menu item'
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
