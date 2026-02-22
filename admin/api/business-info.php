<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/SupabaseDB.php';

$db = new SupabaseDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get business information
            $result = $db->apiCall('business_info', 'GET', null, ['id' => 'eq.1']);
            
            if ($result && is_array($result) && count($result) > 0) {
                echo json_encode([
                    'success' => true,
                    'data' => $result[0]
                ]);
            } else {
                // Return default data if none exists
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'business_name' => 'Das House',
                        'email' => '',
                        'phone' => '',
                        'address' => '',
                        'description' => '',
                        'website' => '',
                        'monday_open' => '09:00',
                        'monday_close' => '17:00',
                        'tuesday_open' => '09:00',
                        'tuesday_close' => '17:00',
                        'wednesday_open' => '09:00',
                        'wednesday_close' => '17:00',
                        'thursday_open' => '09:00',
                        'thursday_close' => '17:00',
                        'friday_open' => '09:00',
                        'friday_close' => '17:00',
                        'saturday_open' => '10:00',
                        'saturday_close' => '16:00',
                        'sunday_open' => '',
                        'sunday_close' => ''
                    ]
                ]);
            }
            break;
            
        case 'POST':
        case 'PUT':
        case 'PATCH':
            // Update business information
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ]);
                exit;
            }
            
            // Validate required fields
            if (empty($input['business_name'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Business name is required'
                ]);
                exit;
            }
            
            // Update business info
            $result = $db->apiCall('business_info', 'PATCH', $input, ['id' => 'eq.1']);
            
            if ($result !== false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Business information updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update business information'
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
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
