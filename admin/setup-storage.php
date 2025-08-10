<?php
/**
 * Setup Supabase Storage for Category Images
 * This script will create the storage bucket and test the connection
 */

require_once 'config/supabase.php';
require_once 'includes/SupabaseStorage.php';

echo "<h1>Supabase Storage Setup</h1>\n";
echo "<pre>\n";

try {
    echo "Testing Supabase storage connection...\n";
    
    $storage = new SupabaseStorage();
    
    echo "✓ Storage class initialized\n";
    
    // Test bucket creation
    echo "Checking storage bucket 'dashouse-bucket'...\n";
    $bucketExists = $storage->ensureBucketExists();
    
    if ($bucketExists) {
        echo "✓ Storage bucket 'dashouse-bucket' is ready\n";
    } else {
        echo "✗ Failed to access storage bucket\n";
        exit(1);
    }
    
    // Test file upload with a sample image
    echo "Testing file upload...\n";
    
    // Create a test image file
    $testImagePath = sys_get_temp_dir() . '/test-category-image.png';
    $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    file_put_contents($testImagePath, $testImageData);
    
    $uploadResult = $storage->uploadFile($testImagePath, 'test-image.png', 'image/png');
    
    if ($uploadResult['success']) {
        echo "✓ Test image uploaded successfully\n";
        echo "  URL: " . $uploadResult['url'] . "\n";
        echo "  Key: " . $uploadResult['key'] . "\n";
        
        // Test file deletion
        $deleteResult = $storage->deleteFile($uploadResult['filename']);
        if ($deleteResult) {
            echo "✓ Test image deleted successfully\n";
        } else {
            echo "✗ Failed to delete test image\n";
        }
    } else {
        echo "✗ Test image upload failed: " . $uploadResult['error'] . "\n";
    }
    
    // Clean up test file
    if (file_exists($testImagePath)) {
        unlink($testImagePath);
    }
    
    echo "\nStorage setup completed successfully!\n";
    echo "You can now upload category images through the admin panel.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>
