<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/supabase.php';
require_once '../includes/SupabaseDB.php';
require_once '../includes/EgressOptimizer.php';

// Use optimized SupabaseDB class instead of duplicate function
$db = new SupabaseDB();
$optimizer = new EgressOptimizer();

// Simple cache for gift shop data (5 minutes)
$cacheFile = '/tmp/gift_shop_cache.json';
$cacheExpiry = 300; // 5 minutes

function getCachedGiftShopData() {
    global $cacheFile, $cacheExpiry;
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if (!$cacheData || !isset($cacheData['timestamp']) || !isset($cacheData['data'])) {
        return null;
    }
    
    if (time() - $cacheData['timestamp'] > $cacheExpiry) {
        unlink($cacheFile);
        return null;
    }
    
    error_log("Gift Shop API: Using cached data");
    return $cacheData['data'];
}

function setCachedGiftShopData($data) {
    global $cacheFile;
    
    $cacheData = [
        'timestamp' => time(),
        'data' => $data
    ];
    
    file_put_contents($cacheFile, json_encode($cacheData));
    error_log("Gift Shop API: Cached " . count($data) . " items");
}

function clearGiftShopCache() {
    global $cacheFile;
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        error_log("Gift Shop API: Cache cleared");
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all gift shop items, optionally filter by active status
            $is_active = $_GET['is_active'] ?? null;
            
            // Try cache first for GET requests
            $gift_shop_data = getCachedGiftShopData();
            
            if ($gift_shop_data === null) {
                // Cache miss - fetch from API with optimized query
                $queryParams = [
                    'order' => 'sort_order,name',
                    'select' => 'id,name,description,image_url,active,sort_order,created_at' // Only needed fields
                ];
                
                if ($is_active !== null) {
                    $queryParams['active'] = 'eq.' . ($is_active ? 'true' : 'false');
                }
                
                if ($optimizer->shouldMakeRequest('gift_shop_items', 'GET', $queryParams)) {
                    $gift_shop_data = $db->apiCall('gift_shop_items', 'GET', null, $queryParams);
                    
                    if ($gift_shop_data !== false) {
                        $optimizer->logRequest('gift_shop_items', 'GET', $queryParams, strlen(json_encode($gift_shop_data)));
                        setCachedGiftShopData($gift_shop_data);
                    }
                } else {
                    $gift_shop_data = [];
                }
            }
            
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
            
            $result = $db->apiCall('gift_shop_items', 'POST', $new_item);
            
            // Clear cache after successful POST
            if ($result !== false) {
                clearGiftShopCache();
            }
            
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
            $result = $db->apiCall('gift_shop_items', 'PATCH', $update_data, $queryParams);
            
            // Clear cache after successful PATCH
            if ($result !== false) {
                clearGiftShopCache();
            }
            
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
            $result = $db->apiCall('gift_shop_items', 'DELETE', null, $queryParams);
            
            // Clear cache after successful DELETE
            if ($result !== false) {
                clearGiftShopCache();
            }
            
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
