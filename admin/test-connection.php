<?php
/**
 * Test basic Supabase connection and list available resources
 */

require_once 'config/supabase.php';
require_once 'includes/SupabaseDB.php';

echo "<h1>Supabase Connection Test</h1>\n";
echo "<pre>\n";

echo "Testing Supabase connection...\n";
echo "Project URL: " . SUPABASE_URL . "\n";
echo "Anon Key: " . substr(SUPABASE_ANON_KEY, 0, 20) . "...\n";
echo "Service Role Key: " . SUPABASE_SERVICE_ROLE_KEY . "\n\n";

try {
    // Test database connection
    echo "Testing database connection...\n";
    $db = new SupabaseDB();
    
    // Test a simple query
    $result = $db->query("SELECT current_timestamp as time, version() as version");
    if ($result) {
        echo "✓ Database connection successful\n";
        foreach ($result as $row) {
            echo "  Time: " . $row['time'] . "\n";
            echo "  Version: " . $row['version'] . "\n";
        }
    } else {
        echo "✗ Database connection failed\n";
    }
    
    echo "\n";
    
    // Test storage API endpoints
    echo "Testing storage API endpoints...\n";
    
    $endpoints = [
        'buckets' => SUPABASE_URL . '/storage/v1/bucket',
        'auth' => SUPABASE_URL . '/auth/v1/user',
        'rest' => SUPABASE_URL . '/rest/v1/'
    ];
    
    foreach ($endpoints as $name => $endpoint) {
        echo "Testing $name endpoint: $endpoint\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . SUPABASE_ANON_KEY,
                'apikey: ' . SUPABASE_ANON_KEY
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "  HTTP Code: $httpCode\n";
        if ($httpCode === 200) {
            echo "  ✓ Endpoint accessible\n";
            if ($name === 'buckets') {
                $data = json_decode($response, true);
                if ($data && is_array($data)) {
                    echo "  Available buckets:\n";
                    foreach ($data as $bucket) {
                        echo "    - " . $bucket['name'] . " (id: " . $bucket['id'] . ")\n";
                    }
                }
            }
        } else {
            echo "  ✗ Endpoint not accessible\n";
            if ($response) {
                echo "  Response: " . substr($response, 0, 200) . "\n";
            }
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
echo "</pre>\n";
?>
