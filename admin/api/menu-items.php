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
            $input = json_decode(file_get_contents('php://input'), true);
            $data = $input ?: $_POST; // Fallback to $_POST if not JSON
            
            if (!isset($data['name']) || !isset($data['price']) || !isset($data['category_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields: name, price, category_id'
                ]);
                break;
            }
            
            $menu_item = [
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => $data['price'],
                'image_url' => $data['image_url'] ?? '',
                'is_vegetarian' => $data['is_vegetarian'] ?? false,
                'is_vegan' => $data['is_vegan'] ?? false,
                'is_gluten_free' => $data['is_gluten_free'] ?? false,
                'allergens' => $data['allergens'] ?? '',
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true
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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                parse_str(file_get_contents("php://input"), $_PUT);
                $input = $_PUT;
            }
            
            if (!isset($input['id'])) {
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
                if (isset($input[$field])) {
                    $update_data[$field] = $input[$field];
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
            
            $result = $db->update('menu_items', $update_data, 'id = :id', ['id' => $input['id']]);
            
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
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                parse_str(file_get_contents("php://input"), $_DELETE);
                $input = $_DELETE;
            }
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing menu item ID'
                ]);
                break;
            }
            
            $result = $db->delete('menu_items', 'id = :id', ['id' => $input['id']]);
            
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
