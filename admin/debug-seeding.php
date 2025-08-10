<?php
require_once 'includes/SupabaseDB.php';

class SeedingDebugger {
    private $db;

    public function __construct() {
        $this->db = new SupabaseDB();
    }

    public function debugSeeding() {
        echo "<h2>DasHouse Seeding Debug - Detailed Analysis</h2>\n";
        
        if (!$this->testConnection()) {
            echo "❌ Cannot connect to database\n";
            return;
        }

        echo "<h3>1. Current Database State</h3>\n";
        $this->showCurrentState();

        echo "<h3>2. Test Category Insert (Step by Step)</h3>\n";
        $this->testCategoryInsert();

        echo "<h3>3. Test Menu Item Insert (Step by Step)</h3>\n";
        $this->testMenuItemInsert();

        echo "<h3>4. Check Database Constraints</h3>\n";
        $this->checkConstraints();

        echo "<h3>5. Manual SQL Test</h3>\n";
        $this->manualSQLTest();
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

    private function showCurrentState() {
        // Check categories
        $result = $this->db->query("SELECT COUNT(*) as count FROM categories");
        if ($result && $row = $result->fetch()) {
            echo "Categories: {$row['count']}\n";
        }

        // Check menu items
        $result = $this->db->query("SELECT COUNT(*) as count FROM menu_items");
        if ($result && $row = $result->fetch()) {
            echo "Menu items: {$row['count']}\n";
        }

        // Show existing categories
        $result = $this->db->query("SELECT id, name FROM categories ORDER BY id");
        if ($result) {
            echo "Existing categories:\n";
            while ($row = $result->fetch()) {
                echo "  ID: {$row['id']}, Name: {$row['name']}\n";
            }
        }
    }

    private function testCategoryInsert() {
        echo "Testing category insert...\n";
        
        // First, check if category already exists
        $checkSql = "SELECT id FROM categories WHERE name = ?";
        $result = $this->db->query($checkSql, ['Test Category Debug']);
        
        if ($result && $result->fetch()) {
            echo "  Category already exists, skipping insert test\n";
            return;
        }

        // Try to insert a test category
        $sql = "INSERT INTO categories (name, description, sort_order, is_active) VALUES (?, ?, ?, ?)";
        echo "  SQL: {$sql}\n";
        echo "  Params: ['Test Category Debug', 'Test Description', 999, true]\n";
        
        try {
            $result = $this->db->query($sql, ['Test Category Debug', 'Test Description', 999, true]);
            if ($result) {
                echo "  ✅ Category insert successful\n";
                
                // Clean up
                $this->db->query("DELETE FROM categories WHERE name = ?", ['Test Category Debug']);
                echo "  🧹 Test category cleaned up\n";
            } else {
                echo "  ❌ Category insert failed\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Exception: " . $e->getMessage() . "\n";
        }
    }

    private function testMenuItemInsert() {
        echo "Testing menu item insert...\n";
        
        // First, get a valid category ID
        $result = $this->db->query("SELECT id FROM categories LIMIT 1");
        if (!$result || !($row = $result->fetch())) {
            echo "  ❌ No categories available for testing\n";
            return;
        }
        
        $categoryId = $row['id'];
        echo "  Using category ID: {$categoryId}\n";

        // Check if test item exists
        $checkSql = "SELECT id FROM menu_items WHERE name = ?";
        $result = $this->db->query($checkSql, ['Test Menu Item Debug']);
        
        if ($result && $result->fetch()) {
            echo "  Menu item already exists, skipping insert test\n";
            return;
        }

        // Try to insert a test menu item
        $sql = "INSERT INTO menu_items (name, description, price, category_id, is_vegetarian, is_vegan, is_gluten_free, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        echo "  SQL: {$sql}\n";
        echo "  Params: ['Test Menu Item Debug', 'Test Description', 9.99, {$categoryId}, true, false, false, true]\n";
        
        try {
            $result = $this->db->query($sql, ['Test Menu Item Debug', 'Test Description', 9.99, $categoryId, true, false, false, true]);
            if ($result) {
                echo "  ✅ Menu item insert successful\n";
                
                // Clean up
                $this->db->query("DELETE FROM menu_items WHERE name = ?", ['Test Menu Item Debug']);
                echo "  🧹 Test menu item cleaned up\n";
            } else {
                echo "  ❌ Menu item insert failed\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Exception: " . $e->getMessage() . "\n";
        }
    }

    private function checkConstraints() {
        echo "Checking database constraints...\n";
        
        // Check foreign key constraints
        $sql = "SELECT 
                    tc.table_name, 
                    kcu.column_name, 
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name 
                FROM 
                    information_schema.table_constraints AS tc 
                    JOIN information_schema.key_column_usage AS kcu
                      ON tc.constraint_name = kcu.constraint_name
                      AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                      ON ccu.constraint_name = tc.constraint_name
                      AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY' 
                AND tc.table_name IN ('menu_items', 'categories')";
        
        $result = $this->db->query($sql);
        if ($result) {
            echo "  Foreign key constraints:\n";
            while ($row = $result->fetch()) {
                echo "    {$row['table_name']}.{$row['column_name']} -> {$row['foreign_table_name']}.{$row['foreign_column_name']}\n";
            }
        } else {
            echo "  No foreign key constraints found\n";
        }
    }

    private function manualSQLTest() {
        echo "Manual SQL test...\n";
        
        // Test a simple SELECT
        echo "  Testing SELECT...\n";
        $result = $this->db->query("SELECT 1 as test");
        if ($result && $row = $result->fetch()) {
            echo "  ✅ SELECT works: {$row['test']}\n";
        } else {
            echo "  ❌ SELECT failed\n";
        }

        // Test table existence
        echo "  Testing table existence...\n";
        $result = $this->db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('categories', 'menu_items')");
        if ($result) {
            echo "  ✅ Tables found:\n";
            while ($row = $result->fetch()) {
                echo "    - {$row['table_name']}\n";
            }
        } else {
            echo "  ❌ Table check failed\n";
        }

        // Test column information
        echo "  Testing column information...\n";
        $result = $this->db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'menu_items' ORDER BY ordinal_position");
        if ($result) {
            echo "  ✅ Menu items columns:\n";
            while ($row = $result->fetch()) {
                echo "    - {$row['column_name']}: {$row['data_type']} (nullable: {$row['is_nullable']})\n";
            }
        } else {
            echo "  ❌ Column check failed\n";
        }
    }
}

// Always run debugger when this file is loaded
$debugger = new SeedingDebugger();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DasHouse Seeding Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>DasHouse Seeding Debug - Detailed Analysis</h3>
                <p class="mb-0">This will show exactly what's failing during the seeding process.</p>
            </div>
            <div class="card-body">
                <h5>Debug Results:</h5>
                <pre class="bg-dark text-light p-3 rounded" style="max-height: 800px; overflow-y: auto;"><?php
                    $debugger->debugSeeding();
                ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
