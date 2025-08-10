<?php
/**
 * Create Supabase storage bucket for category images
 */

require_once 'config/supabase.php';

echo "<h1>Create Supabase Storage Bucket</h1>\n";
echo "<pre>\n";

echo "Project URL: " . SUPABASE_URL . "\n";
echo "Anon Key: " . substr(SUPABASE_ANON_KEY, 0, 20) . "...\n";
echo "Service Role Key: " . SUPABASE_SERVICE_ROLE_KEY . "\n\n";

// First, let's check if we can create buckets with the current keys
echo "=== Testing Bucket Creation ===\n";

$bucketName = 'dashouse-bucket';
$createUrl = SUPABASE_URL . '/storage/v1/bucket';

$bucketData = [
    'id' => $bucketName,
    'name' => $bucketName,
    'public' => true,
    'file_size_limit' => 5242880, // 5MB
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
];

echo "Attempting to create bucket: $bucketName\n";
echo "Bucket data: " . json_encode($bucketData, JSON_PRETTY_PRINT) . "\n\n";

// Try with anon key first
echo "1. Trying with anon key...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $createUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($bucketData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  HTTP Code: $httpCode\n";
if ($httpCode === 200 || $httpCode === 201) {
    echo "  ✓ Bucket created successfully with anon key!\n";
} else {
    echo "  ✗ Failed with anon key\n";
    if ($response) {
        echo "  Response: " . substr($response, 0, 300) . "\n";
    }
    
    // Try with service role key if anon key failed
    echo "\n2. Trying with service role key...\n";
    
    if (SUPABASE_SERVICE_ROLE_KEY === '16384') {
        echo "  ✗ Service role key is invalid (16384)\n";
        echo "  You need to provide the correct service role key to create buckets\n";
    } else {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $createUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($bucketData),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
                'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "  HTTP Code: $httpCode\n";
        if ($httpCode === 200 || $httpCode === 201) {
            echo "  ✓ Bucket created successfully with service role key!\n";
        } else {
            echo "  ✗ Failed with service role key\n";
            if ($response) {
                echo "  Response: " . substr($response, 0, 300) . "\n";
            }
        }
    }
}

echo "\n=== Verification ===\n";
// Check if bucket was created
$checkUrl = SUPABASE_URL . '/storage/v1/bucket/' . $bucketName;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $checkUrl,
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

echo "Checking if bucket '$bucketName' exists...\n";
echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    echo "✓ Bucket '$bucketName' exists and is accessible!\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "  Public: " . (isset($data['public']) ? ($data['public'] ? 'Yes' : 'No') : 'N/A') . "\n";
        echo "  Created: " . ($data['created_at'] ?? 'N/A') . "\n";
    }
} else {
    echo "✗ Bucket '$bucketName' not accessible\n";
    if ($response) {
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n=== Next Steps ===\n";
if ($httpCode === 200) {
    echo "✓ Bucket created successfully! You can now:\n";
    echo "  1. Test image uploads with: php admin/setup-storage.php\n";
    echo "  2. Use the admin panel to upload category images\n";
    echo "  3. Images will be stored in the '$bucketName' bucket\n";
} else {
    echo "✗ Bucket creation failed. You need to:\n";
    echo "  1. Check your Supabase project settings\n";
    echo "  2. Verify the correct service role key\n";
    echo "  3. Ensure storage is enabled in your project\n";
    echo "  4. Or create the bucket manually in the Supabase dashboard\n";
}

echo "\nTest completed.\n";
echo "</pre>\n";
?>
