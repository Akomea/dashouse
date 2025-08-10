<?php
require_once 'includes/SupabaseDB.php';

echo "<h2>Testing Boolean Parameter Fix</h2>\n";

// Test the SupabaseDB class directly
$db = new SupabaseDB();

echo "<h3>1. Testing Connection</h3>\n";
// Test connection by doing a simple query
try {
    $result = $db->query("SELECT 1 as test");
    if ($result) {
        echo "✅ Connection successful\n";
    } else {
        echo "❌ Connection failed\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "\n";
    exit;
}

echo "<h3>2. Testing Simple Boolean Insert</h3>\n";

// Test with explicit boolean values
$sql = "INSERT INTO menu_items (name, description, price, category_id, is_vegetarian, is_vegan, is_gluten_free, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

// Get a category ID first
$result = $db->query("SELECT id FROM categories LIMIT 1");
if ($result && $row = $result->fetch()) {
    $categoryId = $row['id'];
    echo "Using category ID: {$categoryId}\n";
    
    $params = [
        'Boolean Test Item',    // name
        'Test Description',     // description
        9.99,                   // price
        $categoryId,            // category_id
        true,                   // is_vegetarian
        false,                  // is_vegan
        false,                  // is_gluten_free
        true                    // is_available
    ];
    
    echo "Parameters:\n";
    foreach ($params as $i => $param) {
        $type = gettype($param);
        $value = var_export($param, true);
        echo "  Param {$i}: {$value} (type: {$type})\n";
    }
    
    try {
        $result = $db->query($sql, $params);
        if ($result) {
            echo "✅ Boolean insert successful!\n";
            
            // Clean up
            $db->query("DELETE FROM menu_items WHERE name = ?", ['Boolean Test Item']);
            echo "🧹 Test item cleaned up\n";
        } else {
            echo "❌ Boolean insert failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ No categories available\n";
}

echo "<h3>3. Testing with String Booleans</h3>\n";

// Test with string boolean values to see if fixBooleanParams works
$result = $db->query("SELECT id FROM categories LIMIT 1");
if ($result && $row = $result->fetch()) {
    $categoryId = $row['id'];
    
    $params = [
        'String Boolean Test',  // name
        'Test Description',     // description
        8.99,                   // price
        $categoryId,            // category_id
        'true',                 // is_vegetarian (string)
        'false',                // is_vegan (string)
        'false',                // is_gluten_free (string)
        'true'                  // is_available (string)
    ];
    
    echo "String boolean parameters:\n";
    foreach ($params as $i => $param) {
        $type = gettype($param);
        $value = var_export($param, true);
        echo "  Param {$i}: {$value} (type: {$type})\n";
    }
    
    try {
        $result = $db->query($sql, $params);
        if ($result) {
            echo "✅ String boolean insert successful!\n";
            
            // Clean up
            $db->query("DELETE FROM menu_items WHERE name = ?", ['String Boolean Test']);
            echo "🧹 Test item cleaned up\n";
        } else {
            echo "❌ String boolean insert failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boolean Fix Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>Boolean Parameter Fix Test</h3>
                <p class="mb-0">Testing if the boolean parameter fixing is working correctly.</p>
            </div>
            <div class="card-body">
                <pre class="bg-dark text-light p-3 rounded"><?php
                    // The test code above will output here
                ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
