<?php
require_once 'includes/SupabaseDB.php';

class DatabaseCleanup {
    private $db;

    public function __construct() {
        $this->db = new SupabaseDB();
    }

    public function cleanupDatabase() {
        echo "<h2>DasHouse Database Cleanup</h2>\n";
        
        if (!$this->testConnection()) {
            echo "❌ Cannot connect to database\n";
            return;
        }

        echo "<h3>1. Removing Duplicate Categories</h3>\n";
        $this->removeDuplicateCategories();

        echo "<h3>2. Clearing Menu Items</h3>\n";
        $this->clearMenuItems();

        echo "<h3>3. Resetting Category IDs</h3>\n";
        $this->resetCategoryIds();

        echo "<h3>4. Verifying Clean State</h3>\n";
        $this->verifyCleanState();

        echo "<h3>✅ Database cleanup completed!</h3>\n";
        echo "<p>You can now run the seeding script again.</p>\n";
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

    private function removeDuplicateCategories() {
        // Keep only the first occurrence of each category name
        $sql = "DELETE FROM categories WHERE id NOT IN (
            SELECT DISTINCT ON (name) id FROM categories ORDER BY name, id
        )";
        
        if ($this->db->query($sql)) {
            echo "✅ Duplicate categories removed\n";
        } else {
            echo "❌ Failed to remove duplicates\n";
        }
    }

    private function clearMenuItems() {
        $sql = "DELETE FROM menu_items";
        if ($this->db->query($sql)) {
            echo "✅ All menu items cleared\n";
        } else {
            echo "❌ Failed to clear menu items\n";
        }
    }

    private function resetCategoryIds() {
        // Reset the sequence to start from 1
        $sql = "SELECT setval('categories_id_seq', (SELECT MAX(id) FROM categories))";
        if ($this->db->query($sql)) {
            echo "✅ Category ID sequence reset\n";
        } else {
            echo "❌ Failed to reset sequence\n";
        }
    }

    private function verifyCleanState() {
        // Check categories
        $result = $this->db->query("SELECT COUNT(*) as count FROM categories");
        if ($result && $row = $result->fetch()) {
            echo "Categories remaining: {$row['count']}\n";
        }

        // Check menu items
        $result = $this->db->query("SELECT COUNT(*) as count FROM menu_items");
        if ($result && $row = $result->fetch()) {
            echo "Menu items remaining: {$row['count']}\n";
        }

        // Show remaining categories
        $result = $this->db->query("SELECT id, name FROM categories ORDER BY id");
        if ($result) {
            echo "Remaining categories:\n";
            while ($row = $result->fetch()) {
                echo "  ID: {$row['id']}, Name: {$row['name']}\n";
            }
        }
    }
}

// Always run cleanup when this file is loaded
$cleanup = new DatabaseCleanup();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DasHouse Database Cleanup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>DasHouse Database Cleanup</h3>
                <p class="mb-0">This will clean up duplicate categories and prepare for fresh seeding.</p>
            </div>
            <div class="card-body">
                <h5>Cleanup Results:</h5>
                <pre class="bg-dark text-light p-3 rounded" style="max-height: 600px; overflow-y: auto;"><?php
                    $cleanup->cleanupDatabase();
                ?></pre>
                
                <div class="mt-4">
                    <h6>Next Steps:</h6>
                    <ol>
                        <li>After cleanup completes, go to: <a href="seed-database.php" class="btn btn-success btn-sm">Seed Database</a></li>
                        <li>This will add fresh categories and menu items</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
