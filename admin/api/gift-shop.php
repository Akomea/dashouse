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
            // Get all gift shop items, optionally filter by active status
            $is_active = $_GET['is_active'] ?? null;
            
            $queryParams = ['order' => 'sort_order,name'];
            
            if ($is_active !== null) {
                $queryParams['active'] = 'eq.' . ($is_active ? 'true' : 'false');
            }
            
            $gift_shop_data = supabaseAPICall('gift_shop_items', 'GET', null, $queryParams);
            
            if ($gift_shop_data !== false) {
                echo json_encode([
                    'success' => true,
                    'data' => $gift_shop_data,
                    'count' => count($gift_shop_data)
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to fetch gift shop items'
                ]);
            }
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
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'image_url' => $data['image_url'],
                'filename' => $data['filename'] ?? '',
                'original_name' => $data['original_name'] ?? $data['name'],
                'active' => $data['active'] ?? true,
                'sort_order' => (int)($data['sort_order'] ?? 0)
            ];
            
            $result = supabaseAPICall('gift_shop_items', 'POST', $new_item);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gift shop item added successfully',
                    'data' => $result
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
            
            $update_data = [];
            $allowed_fields = ['name', 'description', 'active', 'sort_order'];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $value = $input[$field];
                    // Type casting for specific fields
                    if ($field === 'sort_order') {
                        $value = (int)$value;
                    } elseif ($field === 'active') {
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
            $result = supabaseAPICall('gift_shop_items', 'PATCH', $update_data, $queryParams);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gift shop item updated successfully',
                    'data' => $result
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
            
            $queryParams = ['id' => 'eq.' . $input['id']];
            $result = supabaseAPICall('gift_shop_items', 'DELETE', null, $queryParams);
            
            if ($result !== false) {
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
