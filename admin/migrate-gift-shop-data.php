<?php
/**
 * Gift Shop Data Migration Script
 * This script migrates gift shop data from JSON file to Supabase table
 */

require_once 'config/supabase.php';

echo "<h1>Gift Shop Data Migration</h1>";

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
        'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY, // Use service role for admin operations
        'apikey: ' . SUPABASE_SERVICE_ROLE_KEY
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
        echo "<p style='color: red;'>cURL Error: $curlError</p>";
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        echo "<p style='color: red;'>API Error (HTTP $httpCode): $response</p>";
        return false;
    }
}

// Check if table exists
echo "<h2>Step 1: Checking if gift_shop_items table exists</h2>";
$result = supabaseAPICall('gift_shop_items?limit=1');
if ($result === false) {
    echo "<p style='color: red;'>❌ Table 'gift_shop_items' does not exist. Please run the SQL script first!</p>";
    echo "<p>Instructions:</p>";
    echo "<ol>";
    echo "<li>Go to your Supabase dashboard</li>";
    echo "<li>Navigate to SQL Editor</li>";
    echo "<li>Copy and paste the contents of 'admin/create-gift-shop-table.sql'</li>";
    echo "<li>Run the SQL script</li>";
    echo "<li>Then come back and run this migration script</li>";
    echo "</ol>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Table exists!</p>";
}

// Load JSON data
echo "<h2>Step 2: Loading existing JSON data</h2>";
$jsonFile = __DIR__ . '/../data/gift-shop.json';
if (!file_exists($jsonFile)) {
    echo "<p style='color: orange;'>⚠️ No JSON file found at: $jsonFile</p>";
    echo "<p>Nothing to migrate. The table is ready for new data.</p>";
    exit;
}

$jsonContent = file_get_contents($jsonFile);
$giftShopItems = json_decode($jsonContent, true);

if (!$giftShopItems || !is_array($giftShopItems)) {
    echo "<p style='color: red;'>❌ Invalid JSON data in file</p>";
    exit;
}

echo "<p>Found " . count($giftShopItems) . " items in JSON file</p>";

// Check if data already exists
echo "<h2>Step 3: Checking for existing data</h2>";
$existingData = supabaseAPICall('gift_shop_items');
if ($existingData && count($existingData) > 0) {
    echo "<p style='color: orange;'>⚠️ Table already contains " . count($existingData) . " items</p>";
    echo "<p>Do you want to proceed? This will add the JSON items to the existing data.</p>";
    echo "<p><a href='?force=1' style='background: #ff6b6b; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Yes, Proceed</a></p>";
    
    if (!isset($_GET['force'])) {
        exit;
    }
}

// Migrate data
echo "<h2>Step 4: Migrating data</h2>";
$successCount = 0;
$errorCount = 0;

foreach ($giftShopItems as $item) {
    echo "<div style='margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";
    echo "<h4>Migrating: " . htmlspecialchars($item['name']) . "</h4>";
    
    // Prepare data for Supabase
    $supabaseItem = [
        'name' => $item['name'],
        'description' => $item['description'] ?? '',
        'image_url' => $item['image_url'] ?? '',
        'filename' => $item['filename'] ?? '',
        'original_name' => $item['original_name'] ?? '',
        'active' => $item['active'] ?? true,
        'sort_order' => (int)($item['sort_order'] ?? 0)
    ];
    
    // Add created_at if available
    if (isset($item['uploaded_at'])) {
        $supabaseItem['created_at'] = $item['uploaded_at'];
    }
    
    $result = supabaseAPICall('gift_shop_items', 'POST', $supabaseItem);
    
    if ($result !== false) {
        echo "<p style='color: green;'>✅ Successfully migrated</p>";
        $successCount++;
    } else {
        echo "<p style='color: red;'>❌ Failed to migrate</p>";
        $errorCount++;
    }
    
    echo "</div>";
}

echo "<h2>Migration Complete!</h2>";
echo "<p><strong>Successfully migrated:</strong> $successCount items</p>";
echo "<p><strong>Errors:</strong> $errorCount items</p>";

if ($successCount > 0) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Migration Successful!</h3>";
    echo "<p>Your gift shop items have been migrated to Supabase. You can now:</p>";
    echo "<ul>";
    echo "<li>Visit your gift shop page to see the items loading from Supabase</li>";
    echo "<li>Use the admin panel to manage gift shop items</li>";
    echo "<li>Optionally, backup and remove the old JSON file</li>";
    echo "</ul>";
    echo "</div>";
}

// Show current data
echo "<h2>Current Data in Supabase</h2>";
$currentData = supabaseAPICall('gift_shop_items?order=sort_order,name');
if ($currentData && is_array($currentData)) {
    echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Name</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Description</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Active</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>Sort Order</th>";
    echo "</tr>";
    
    foreach ($currentData as $item) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $item['id'] . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($item['description']) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . ($item['active'] ? 'Yes' : 'No') . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $item['sort_order'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No data found in table.</p>";
}
?>
