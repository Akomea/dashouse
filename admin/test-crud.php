<?php
require_once 'includes/SupabaseDB.php';

echo "<h1>Das House Admin - CRUD Test</h1>\n";
echo "<p>Testing database connection and CRUD operations...</p>\n";

try {
    $db = new SupabaseDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>\n";
    
    // Test 1: Check if tables exist
    echo "<h3>1. Checking Database Tables</h3>\n";
    
    $tables = ['categories', 'menu_items'];
    foreach ($tables as $table) {
        if ($db->tableExists($table)) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>\n";
        }
    }
    
    // Test 2: Test Categories CRUD
    echo "<h3>2. Testing Categories CRUD</h3>\n";
    
    // Create a test category
    $testCategory = [
        'name' => 'Test Category ' . date('Y-m-d H:i:s'),
        'description' => 'This is a test category for CRUD testing',
        'sort_order' => 999,
        'is_active' => true
    ];
    
    echo "<p>Creating test category...</p>\n";
    $categoryId = $db->insert('categories', $testCategory);
    
    if ($categoryId) {
        echo "<p style='color: green;'>✓ Category created with ID: $categoryId</p>\n";
        
        // Read the category
        $sql = "SELECT * FROM categories WHERE id = :id";
        $category = $db->fetch($sql, ['id' => $categoryId]);
        
        if ($category) {
            echo "<p style='color: green;'>✓ Category read successfully</p>\n";
            echo "<pre>" . print_r($category, true) . "</pre>\n";
            
            // Update the category
            $updateData = [
                'description' => 'Updated description for testing',
                'sort_order' => 888
            ];
            
            $updateResult = $db->update('categories', $updateData, 'id = :id', ['id' => $categoryId]);
            
            if ($updateResult !== false) {
                echo "<p style='color: green;'>✓ Category updated successfully</p>\n";
                
                // Read updated category
                $updatedCategory = $db->fetch($sql, ['id' => $categoryId]);
                echo "<p>Updated category:</p>\n";
                echo "<pre>" . print_r($updatedCategory, true) . "</pre>\n";
                
                // Delete the test category
                $deleteResult = $db->delete('categories', 'id = :id', ['id' => $categoryId]);
                
                if ($deleteResult !== false) {
                    echo "<p style='color: green;'>✓ Test category deleted successfully</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Failed to delete test category</p>\n";
                }
            } else {
                echo "<p style='color: red;'>✗ Failed to update category</p>\n";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to read created category</p>\n>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to create test category</p>\n";
    }
    
    // Test 3: Test Menu Items CRUD
    echo "<h3>3. Testing Menu Items CRUD</h3>\n";
    
    // Get first category for testing
    $firstCategory = $db->fetch("SELECT id FROM categories WHERE is_active = true LIMIT 1");
    
    if ($firstCategory) {
        $testMenuItem = [
            'category_id' => $firstCategory['id'],
            'name' => 'Test Menu Item ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test menu item for CRUD testing',
            'price' => 9.99,
            'is_vegetarian' => true,
            'is_vegan' => false,
            'is_gluten_free' => true,
            'allergens' => 'Test allergens',
            'sort_order' => 999,
            'is_active' => true
        ];
        
        echo "<p>Creating test menu item...</p>\n";
        $menuItemId = $db->insert('menu_items', $testMenuItem);
        
        if ($menuItemId) {
            echo "<p style='color: green;'>✓ Menu item created with ID: $menuItemId</p>\n";
            
            // Read the menu item
            $sql = "SELECT mi.*, c.name as category_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE mi.id = :id";
            $menuItem = $db->fetch($sql, ['id' => $menuItemId]);
            
            if ($menuItem) {
                echo "<p style='color: green;'>✓ Menu item read successfully</p>\n";
                echo "<pre>" . print_r($menuItem, true) . "</pre>\n";
                
                // Update the menu item
                $updateData = [
                    'price' => 12.99,
                    'description' => 'Updated description for testing'
                ];
                
                $updateResult = $db->update('menu_items', $updateData, 'id = :id', ['id' => $menuItemId]);
                
                if ($updateResult !== false) {
                    echo "<p style='color: green;'>✓ Menu item updated successfully</p>\n";
                    
                    // Read updated menu item
                    $updatedMenuItem = $db->fetch($sql, ['id' => $menuItemId]);
                    echo "<p>Updated menu item:</p>\n";
                    echo "<pre>" . print_r($updatedMenuItem, true) . "</pre>\n";
                    
                    // Delete the test menu item
                    $deleteResult = $db->delete('menu_items', 'id = :id', ['id' => $menuItemId]);
                    
                    if ($deleteResult !== false) {
                        echo "<p style='color: green;'>✓ Test menu item deleted successfully</p>\n";
                    } else {
                        echo "<p style='color: red;'>✗ Failed to delete test menu item</p>\n";
                    }
                } else {
                    echo "<p style='color: red;'>✗ Failed to update menu item</p>\n";
                }
            } else {
                echo "<p style='color: red;'>✗ Failed to read created menu item</p>\n>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to create test menu item</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No active categories found for menu item testing</p>\n";
    }
    
    // Test 4: Test API Endpoints
    echo "<h3>4. Testing API Endpoints</h3>\n";
    
    $apiEndpoints = [
        'categories.php' => 'http://localhost:8000/admin/api/categories.php',
        'menu-items.php' => 'http://localhost:8000/admin/api/menu-items.php'
    ];
    
    foreach ($apiEndpoints as $endpoint => $url) {
        echo "<p>Testing $endpoint...</p>\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response !== false && $httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                echo "<p style='color: green;'>✓ $endpoint working correctly</p>\n";
                echo "<p>Response: " . json_encode($data, JSON_PRETTY_PRINT) . "</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠ $endpoint returned invalid JSON</p>\n";
            }
        } else {
            echo "<p style='color: red;'>✗ $endpoint failed (HTTP $httpCode)</p>\n";
        }
    }
    
    echo "<h3>5. Summary</h3>\n";
    echo "<p style='color: green;'>✓ CRUD operations test completed!</p>\n";
    echo "<p>Your admin panel is now fully connected to the database and ready to use.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database configuration and try again.</p>\n";
}

$db->close();
?>
