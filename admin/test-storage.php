<?php
/**
 * Test different storage configurations to find the correct bucket
 */

require_once 'config/supabase.php';
require_once 'includes/SupabaseStorage.php';

echo "<h1>Storage Configuration Test</h1>\n";
echo "<pre>\n";

// Test different bucket names
$possibleBuckets = [
    'dashouse-bucket',
    'dashouse_bucket', 
    'dashousebucket',
    'category-images',
    'images',
    'uploads'
];

echo "Testing different bucket configurations...\n\n";

foreach ($possibleBuckets as $bucketName) {
    echo "Testing bucket: '$bucketName'\n";
    
    try {
        $storage = new SupabaseStorage($bucketName);
        
        // Test bucket access
        $bucketExists = $storage->ensureBucketExists();
        
        if ($bucketExists) {
            echo "✓ SUCCESS: Bucket '$bucketName' is accessible!\n";
            echo "  URL: " . SUPABASE_URL . "/storage/v1/object/public/$bucketName/\n";
            
            // Try to upload a test file
            echo "  Testing upload...\n";
            $testImagePath = sys_get_temp_dir() . '/test.png';
            $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
            file_put_contents($testImagePath, $testImageData);
            
            $uploadResult = $storage->uploadFile($testImagePath, 'test.png', 'image/png');
            
            if ($uploadResult['success']) {
                echo "  ✓ Upload successful!\n";
                echo "  Public URL: " . $uploadResult['url'] . "\n";
                
                // Clean up test file
                $storage->deleteFile($uploadResult['filename']);
                echo "  ✓ Test file cleaned up\n";
                
                // Found working configuration
                echo "\n🎉 WORKING CONFIGURATION FOUND!\n";
                echo "Use bucket name: '$bucketName'\n";
                break;
            } else {
                echo "  ✗ Upload failed: " . $uploadResult['error'] . "\n";
            }
            
            // Clean up temp file
            if (file_exists($testImagePath)) {
                unlink($testImagePath);
            }
            
        } else {
            echo "✗ Bucket '$bucketName' not accessible\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error with bucket '$bucketName': " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Test completed.\n";
echo "</pre>\n";
?>
