<?php
require_once 'includes/SupabaseDB.php';

class DatabaseFixer {
    private $db;

    public function __construct() {
        $this->db = new SupabaseDB();
    }

    public function fixDatabase() {
        echo "<h2>DasHouse Database Fix</h2>\n";
        
        if (!$this->testConnection()) {
            echo "❌ Cannot connect to database\n";
            return;
        }

        echo "<h3>1. Adding Missing Column</h3>\n";
        $this->addMissingColumn();

        echo "<h3>2. Verifying Fix</h3>\n";
        $this->verifyFix();

        echo "<h3>3. Testing Menu Item Insert</h3>\n";
        $this->testMenuItemInsert();

        echo "<h3>✅ Database fix completed!</h3>\n";
        echo "<p>You can now run the seeding script successfully.</p>\n";
    }

    private function testConnection() {
        try {
            $result = $this->db->query("SELECT 1");
            return $result !== false;
        } catch (Exception $e) {
            echo "Connection error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function addMissingColumn() {
        echo "Adding missing 'is_available' column to menu_items table...\n";
        
        // Check if column already exists
        $checkSql = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'menu_items' AND column_name = 'is_available'";
        $result = $this->db->query($checkSql);
        
        if ($result && $result->fetch()) {
            echo "✅ Column 'is_available' already exists\n";
            return;
        }

        // Add the missing column
        $sql = "ALTER TABLE menu_items ADD COLUMN is_available BOOLEAN DEFAULT true";
        
        try {
            if ($this->db->query($sql)) {
                echo "✅ Column 'is_available' added successfully\n";
            } else {
                echo "❌ Failed to add column\n";
            }
        } catch (Exception $e) {
            echo "❌ Error adding column: " . $e->getMessage() . "\n";
        }
    }

    private function verifyFix() {
        echo "Verifying table structure...\n";
        
        $sql = "SELECT column_name, data_type, is_nullable, column_default 
                FROM information_schema.columns 
                WHERE table_name = 'menu_items' 
                ORDER BY ordinal_position";
        
        $result = $this->db->query($sql);
        if ($result) {
            echo "Current menu_items table structure:\n";
            while ($row = $result->fetch()) {
                echo "  - {$row['column_name']}: {$row['data_type']}";
                if ($row['is_nullable'] === 'NO') {
                    echo " (NOT NULL)";
                }
                if ($row['column_default']) {
                    echo " (default: {$row['column_default']})";
                }
                echo "\n";
            }
        }
    }

    private function testMenuItemInsert() {
        echo "Testing menu item insert with the new column...\n";
        
        // Get a valid category ID
        $result = $this->db->query("SELECT id FROM categories LIMIT 1");
        if (!$result || !($row = $result->fetch())) {
            echo "❌ No categories available for testing\n";
            return;
        }
        
        $categoryId = $row['id'];
        echo "Using category ID: {$categoryId}\n";

        // Test insert with all required columns including is_available
        // Use explicit boolean values and show exactly what we're passing
        $params = [
            'Test Fix Item',           // name
            'Test Description',        // description  
            '9.99',                    // price
            $categoryId,               // category_id
            true,                      // is_vegetarian
            false,                     // is_vegan
            false,                     // is_gluten_free
            true                       // is_available
        ];
        
        echo "Parameters being passed:\n";
        foreach ($params as $i => $param) {
            $type = gettype($param);
            $value = var_export($param, true);
            echo "  Param {$i}: {$value} (type: {$type})\n";
        }
        
        $sql = "INSERT INTO menu_items (name, description, price, category_id, is_vegetarian, is_vegan, is_gluten_free, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        echo "SQL: {$sql}\n";
        
        try {
            $result = $this->db->query($sql, $params);
            if ($result) {
                echo "✅ Test menu item insert successful!\n";
                
                // Clean up test data
                $this->db->query("DELETE FROM menu_items WHERE name = ?", ['Test Fix Item']);
                echo "🧹 Test item cleaned up\n";
            } else {
                echo "❌ Test insert still failed\n";
            }
        } catch (Exception $e) {
            echo "❌ Exception during test: " . $e->getMessage() . "\n";
            
            // Show more details about the error
            echo "Error details:\n";
            echo "  - Error code: " . $e->getCode() . "\n";
            echo "  - Error message: " . $e->getMessage() . "\n";
        }
    }
}

// Always run fixer when this file is loaded
$fixer = new DatabaseFixer();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DasHouse Database Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>DasHouse Database Fix</h3>
                <p class="mb-0">This will add the missing 'is_available' column to fix the seeding issue.</p>
            </div>
            <div class="card-body">
                <h5>Fix Results:</h5>
                <pre class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;"><?php
                    $fixer->fixDatabase();
                ?></pre>
                
                <div class="mt-4">
                    <h6>Next Steps:</h6>
                    <ol>
                        <li>After the fix completes successfully, go to: <a href="seed-database.php" class="btn btn-success btn-sm">Seed Database</a></li>
                        <li>This will now work and add all your menu items!</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
