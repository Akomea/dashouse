<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/supabase.php';
require_once '../includes/SupabaseStorage.php';

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
            // Get all categories
            $is_active = $_GET['is_active'] ?? true;
            
            $queryParams = [
                'is_active' => 'eq.' . ($is_active ? 'true' : 'false'),
                'order' => 'sort_order,name'
            ];
            
            $categories = supabaseAPICall('categories', 'GET', null, $queryParams);
            
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
            // Add new category - handle both JSON and form data
            $data = [];
            $isFormData = isset($_POST['name']);
            
            if ($isFormData) {
                // Form data submission
                $data = $_POST;
            } else {
                // JSON data submission
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
            }
            
            if (!isset($data['name']) || empty($data['name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Category name is required'
                ]);
                break;
            }
            
            $imageUrl = '';
            
            // Handle file upload if present (only for form data)
            if ($isFormData && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
                $imageUrl = $data['image_url'] ?? '';
            }
            
            $category = [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'image_url' => $imageUrl,
                'sort_order' => (int)($data['sort_order'] ?? 0),
                'is_active' => $data['is_active'] ?? true
            ];
            
            $result = supabaseAPICall('categories', 'POST', $category);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category added successfully',
                    'data' => $result
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
            
            $queryParams = ['id' => 'eq.' . $_PUT['id']];
            $result = supabaseAPICall('categories', 'PATCH', $update_data, $queryParams);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'data' => $result
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
            
            // Check if category has menu items using Supabase REST API
            $checkQueryParams = [
                'category_id' => 'eq.' . $_DELETE['id'],
                'select' => 'id'
            ];
            
            $menuItems = supabaseAPICall('menu_items', 'GET', null, $checkQueryParams);
            
            if ($menuItems !== false && count($menuItems) > 0) {
                // Soft delete - set inactive
                $updateData = ['is_active' => false];
                $queryParams = ['id' => 'eq.' . $_DELETE['id']];
                $result = supabaseAPICall('categories', 'PATCH', $updateData, $queryParams);
                $message = 'Category deactivated (has menu items)';
            } else {
                // Hard delete if no menu items
                $queryParams = ['id' => 'eq.' . $_DELETE['id']];
                $result = supabaseAPICall('categories', 'DELETE', null, $queryParams);
                $message = 'Category deleted successfully';
            }
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'data' => $result
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
                // Update category with new image URL using Supabase REST API
                $updateData = ['image_url' => $uploadResult['url']];
                $queryParams = ['id' => 'eq.' . $_POST['id']];
                $result = supabaseAPICall('categories', 'PATCH', $updateData, $queryParams);
                
                if ($result !== false) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Category image updated successfully',
                        'image_url' => $uploadResult['url'],
                        'data' => $result
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

// No database connection to close with REST API
?>
