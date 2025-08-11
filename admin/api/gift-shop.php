<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$gift_shop_file = __DIR__ . '/../../data/gift-shop.json';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Load existing gift shop data
    $gift_shop_data = [];
    if (file_exists($gift_shop_file)) {
        $content = file_get_contents($gift_shop_file);
        $gift_shop_data = json_decode($content, true) ?: [];
    }
    
    switch ($method) {
        case 'GET':
            // Get all gift shop items, optionally filter by active status
            $is_active = $_GET['is_active'] ?? null;
            
            if ($is_active !== null) {
                $filtered_data = array_filter($gift_shop_data, function($item) use ($is_active) {
                    return $item['active'] == (bool)$is_active;
                });
                $gift_shop_data = array_values($filtered_data);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $gift_shop_data,
                'count' => count($gift_shop_data)
            ]);
            break;
            
        case 'POST':
            // Add new gift shop item (admin only)
            $input = json_decode(file_get_contents('php://input'), true);
            $data = $input ?: $_POST; // Fallback to $_POST if not JSON
            
            if (!isset($data['name']) || !isset($data['image_url'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields: name, image_url'
                ]);
                break;
            }
            
            $new_item = [
                'id' => uniqid(),
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'image_url' => $data['image_url'],
                'filename' => $data['filename'] ?? '',
                'original_name' => $data['original_name'] ?? $data['name'],
                'uploaded_at' => date('Y-m-d H:i:s'),
                'active' => $data['active'] ?? true,
                'sort_order' => $data['sort_order'] ?? count($gift_shop_data)
            ];
            
            $gift_shop_data[] = $new_item;
            
            // Save to file
            if (!is_dir('../../data')) {
                mkdir('../../data', 0755, true);
            }
            
            if (file_put_contents($gift_shop_file, json_encode($gift_shop_data, JSON_PRETTY_PRINT))) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gift shop item added successfully',
                    'data' => $new_item
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to save gift shop item'
                ]);
            }
            break;
            
        case 'PUT':
            // Update gift shop item (admin only)
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                parse_str(file_get_contents("php://input"), $_PUT);
                $input = $_PUT;
            }
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing item ID'
                ]);
                break;
            }
            
            $found = false;
            foreach ($gift_shop_data as &$item) {
                if ($item['id'] === $input['id']) {
                    // Update allowed fields
                    $allowed_fields = ['name', 'description', 'active', 'sort_order'];
                    foreach ($allowed_fields as $field) {
                        if (isset($input[$field])) {
                            $item[$field] = $input[$field];
                        }
                    }
                    $item['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Item not found'
                ]);
                break;
            }
            
            if (file_put_contents($gift_shop_file, json_encode($gift_shop_data, JSON_PRETTY_PRINT))) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gift shop item updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update gift shop item'
                ]);
            }
            break;
            
        case 'DELETE':
            // Delete gift shop item (admin only)
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                parse_str(file_get_contents("php://input"), $_DELETE);
                $input = $_DELETE;
            }
            
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing item ID'
                ]);
                break;
            }
            
            $found = false;
            foreach ($gift_shop_data as $key => $item) {
                if ($item['id'] === $input['id']) {
                    unset($gift_shop_data[$key]);
                    $gift_shop_data = array_values($gift_shop_data); // Reindex array
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Item not found'
                ]);
                break;
            }
            
            if (file_put_contents($gift_shop_file, json_encode($gift_shop_data, JSON_PRETTY_PRINT))) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gift shop item deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to delete gift shop item'
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
