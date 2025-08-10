<?php
/**
 * List all available Supabase storage buckets
 */

require_once 'config/supabase.php';

echo "<h1>Supabase Storage Buckets</h1>\n";
echo "<pre>\n";

echo "Project URL: " . SUPABASE_URL . "\n";
echo "Anon Key: " . substr(SUPABASE_ANON_KEY, 0, 20) . "...\n\n";

// Test different ways to list buckets
$endpoints = [
    'List buckets' => SUPABASE_URL . '/storage/v1/bucket',
    'List buckets with apikey header' => SUPABASE_URL . '/storage/v1/bucket',
    'List buckets with service role' => SUPABASE_URL . '/storage/v1/bucket'
];

$headers = [
    'List buckets' => [
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ],
    'List buckets with apikey header' => [
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'apikey: ' . SUPABASE_ANON_KEY
    ],
    'List buckets with service role' => [
        'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY
    ]
];

foreach ($endpoints as $name => $endpoint) {
    echo "=== $name ===\n";
    echo "Endpoint: $endpoint\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers[$name],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "cURL Error: $error\n";
    } elseif ($httpCode === 200) {
        echo "✓ Success!\n";
        if ($response) {
            $data = json_decode($response, true);
            if ($data && is_array($data)) {
                echo "Found " . count($data) . " bucket(s):\n";
                foreach ($data as $bucket) {
                    echo "  - Name: " . ($bucket['name'] ?? 'N/A') . "\n";
                    echo "    ID: " . ($bucket['id'] ?? 'N/A') . "\n";
                    echo "    Public: " . (isset($bucket['public']) ? ($bucket['public'] ? 'Yes' : 'No') : 'N/A') . "\n";
                    echo "    Created: " . ($bucket['created_at'] ?? 'N/A') . "\n";
                    echo "    Updated: " . ($bucket['updated_at'] ?? 'N/A') . "\n";
                    echo "\n";
                }
            } else {
                echo "Response (raw): " . substr($response, 0, 500) . "\n";
            }
        } else {
            echo "Empty response\n";
        }
    } else {
        echo "✗ Failed\n";
        if ($response) {
            echo "Response: " . substr($response, 0, 300) . "\n";
        }
    }
    
    echo "\n";
}

// Also try to get bucket info for specific bucket names
echo "=== Testing Specific Bucket Names ===\n";
$testBuckets = ['dashouse-bucket', 'dashouse_bucket', 'dashousebucket', 'category-images', 'images', 'uploads'];

foreach ($testBuckets as $bucketName) {
    echo "Testing bucket: $bucketName\n";
    
    $url = SUPABASE_URL . '/storage/v1/bucket/' . $bucketName;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
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
        echo "  ✓ Bucket '$bucketName' exists!\n";
        $data = json_decode($response, true);
        if ($data) {
            echo "    Public: " . (isset($data['public']) ? ($data['public'] ? 'Yes' : 'No') : 'N/A') . "\n";
            echo "    Created: " . ($data['created_at'] ?? 'N/A') . "\n";
        }
    } else {
        echo "  ✗ Bucket '$bucketName' not found\n";
        if ($response) {
            echo "    Response: " . substr($response, 0, 200) . "\n";
        }
    }
    echo "\n";
}

echo "Test completed.\n";
echo "</pre>\n";
?>
