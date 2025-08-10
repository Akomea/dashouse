<?php
require_once 'includes/SupabaseDB.php';

class DatabaseDebugger {
    private $db;

    public function __construct() {
        $this->db = new SupabaseDB();
    }

    public function debugDatabase() {
        echo "<h2>DasHouse Database Debug Information</h2>\n";
        
        // Test connection
        echo "<h3>1. Connection Test</h3>\n";
        if ($this->testConnection()) {
            echo "✅ Database connection successful\n";
        } else {
            echo "❌ Database connection failed\n";
            return;
        }

        // Check table structure
        echo "<h3>2. Table Structure Check</h3>\n";
        $this->checkTableStructure();

        // Check existing data
        echo "<h3>3. Existing Data Check</h3>\n";
        $this->checkExistingData();

        // Test simple insert
        echo "<h3>4. Test Insert</h3>\n";
        $this->testSimpleInsert();
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

    private function checkTableStructure() {
        $tables = ['categories', 'menu_items'];
        
        foreach ($tables as $table) {
            echo "\n--- {$table} table ---\n";
            
            // Check if table exists
            if ($this->db->tableExists($table)) {
                echo "✅ Table exists\n";
                
                // Get table structure
                $structure = $this->db->getTableStructure($table);
                if ($structure) {
                    echo "Table structure:\n";
                    foreach ($structure as $column) {
                        echo "  - {$column['column_name']}: {$column['data_type']}";
                        if (isset($column['is_nullable']) && $column['is_nullable'] === 'NO') {
                            echo " (NOT NULL)";
                        }
                        if (isset($column['column_default'])) {
                            echo " (default: {$column['column_default']})";
                        }
                        echo "\n";
                    }
                }
                
                // Count rows
                $result = $this->db->query("SELECT COUNT(*) as count FROM {$table}");
                if ($result && $row = $result->fetch()) {
                    echo "Current rows: {$row['count']}\n";
                }
            } else {
                echo "❌ Table does not exist\n";
            }
        }
    }

    private function checkExistingData() {
        echo "\n--- Categories ---\n";
        $result = $this->db->query("SELECT id, name, description FROM categories ORDER BY id");
        if ($result) {
            while ($row = $result->fetch()) {
                echo "ID: {$row['id']}, Name: {$row['name']}\n";
            }
        } else {
            echo "No categories found or error\n";
        }

        echo "\n--- Menu Items ---\n";
        $result = $this->db->query("SELECT id, name, price, category_id FROM menu_items ORDER BY id LIMIT 5");
        if ($result) {
            while ($row = $result->fetch()) {
                echo "ID: {$row['id']}, Name: {$row['name']}, Price: €{$row['price']}, Category: {$row['category_id']}\n";
            }
        } else {
            echo "No menu items found or error\n";
        }
    }

    private function testSimpleInsert() {
        echo "\n--- Testing Simple Insert ---\n";
        
        // Test category insert
        $sql = "INSERT INTO categories (name, description, sort_order, is_active) VALUES (?, ?, ?, ?) ON CONFLICT (name) DO NOTHING RETURNING id";
        $result = $this->db->query($sql, ['Test Category', 'Test Description', 999, true]);
        
        if ($result) {
            $row = $result->fetch();
            if ($row) {
                echo "✅ Test category inserted with ID: {$row['id']}\n";
                
                // Test menu item insert
                $sql = "INSERT INTO menu_items (name, description, price, category_id, is_vegetarian, is_vegan, is_gluten_free, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
                $result = $this->db->query($sql, ['Test Item', 'Test Description', 9.99, $row['id'], true, false, false, true]);
                
                if ($result) {
                    $itemRow = $result->fetch();
                    if ($itemRow) {
                        echo "✅ Test menu item inserted with ID: {$itemRow['id']}\n";
                        
                        // Clean up test data
                        $this->db->query("DELETE FROM menu_items WHERE id = ?", [$itemRow['id']]);
                        $this->db->query("DELETE FROM categories WHERE id = ?", [$row['id']]);
                        echo "🧹 Test data cleaned up\n";
                    } else {
                        echo "❌ Test menu item insert failed\n";
                    }
                } else {
                    echo "❌ Test menu item insert failed\n";
                }
            } else {
                echo "❌ Test category insert failed\n";
            }
        } else {
            echo "❌ Test category insert failed\n";
        }
    }
}

// Always run the debugger when this file is loaded
$debugger = new DatabaseDebugger();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DasHouse Database Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>DasHouse Database Debug</h3>
                <p class="mb-0">Database diagnostic information will be displayed below.</p>
            </div>
            <div class="card-body">
                <h5>Debug Results:</h5>
                <pre class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;"><?php
                    $debugger->debugDatabase();
                ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
