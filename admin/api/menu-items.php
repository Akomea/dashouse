<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/supabase.php';

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
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'apikey: ' . SUPABASE_ANON_KEY
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
        error_log("cURL error in supabaseAPICall: " . $curlError);
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        error_log("Supabase API error: HTTP $httpCode - $response");
        return false;
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get menu items with optional filtering
            $id = $_GET['id'] ?? null;
            $category_id = $_GET['category_id'] ?? null;
            $is_active = $_GET['is_active'] ?? true;
            
            // Build query parameters for Supabase REST API
            $queryParams = [
                'select' => '*,categories(*)',
                'order' => 'sort_order,name'
            ];
            
            // If getting a specific item by ID, don't filter by active status
            if ($id) {
                $queryParams['id'] = 'eq.' . $id;
            } else {
                $queryParams['is_active'] = 'eq.' . ($is_active ? 'true' : 'false');
            }
            
            if ($category_id) {
                $queryParams['category_id'] = 'eq.' . $category_id;
            }
            
            $menu_items = supabaseAPICall('menu_items', 'GET', null, $queryParams);
            
            if ($menu_items !== false) {
                // Transform the data to match the expected format
                $transformed_items = array_map(function($item) {
                    $item['category_name'] = $item['categories']['name'] ?? '';
                    return $item;
                }, $menu_items);
                
                echo json_encode([
                    'success' => true,
                    'data' => $transformed_items,
                    'count' => count($transformed_items)
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
                'category_id' => (int)$data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'price' => (float)$data['price'],
                'image_url' => $data['image_url'] ?? '',
                'is_vegetarian' => $data['is_vegetarian'] ?? false,
                'is_vegan' => $data['is_vegan'] ?? false,
                'is_gluten_free' => $data['is_gluten_free'] ?? false,
                'allergens' => $data['allergens'] ?? '',
                'sort_order' => (int)($data['sort_order'] ?? 0),
                'is_active' => $data['is_active'] ?? true
            ];
            
            $result = supabaseAPICall('menu_items', 'POST', $menu_item);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item added successfully',
                    'data' => $result
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
                    $value = $input[$field];
                    // Type casting for specific fields
                    if (in_array($field, ['price'])) {
                        $value = (float)$value;
                    } elseif (in_array($field, ['category_id', 'sort_order'])) {
                        $value = (int)$value;
                    } elseif (in_array($field, ['is_vegetarian', 'is_vegan', 'is_gluten_free', 'is_active'])) {
                        $value = (bool)$value;
                    }
                    $update_data[$field] = $value;
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
            
            $queryParams = ['id' => 'eq.' . $input['id']];
            $result = supabaseAPICall('menu_items', 'PATCH', $update_data, $queryParams);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item updated successfully',
                    'data' => $result
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
            
            $queryParams = ['id' => 'eq.' . $input['id']];
            $result = supabaseAPICall('menu_items', 'DELETE', null, $queryParams);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Menu item deleted successfully'
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
?>
