<?php
require_once 'includes/SupabaseDB.php';

class DatabaseSetup {
    private $db;
    
    public function __construct() {
        $this->db = new SupabaseDB();
    }
    
    /**
     * Create all tables for DasHouse
     */
    public function setupAllTables() {
        $this->createUsersTable();
        $this->createCategoriesTable();
        $this->createMenuItemsTable();
        $this->createOrdersTable();
        $this->createReservationsTable();
        $this->createPhotosTable();
        $this->createSettingsTable();
        
        echo "Database setup completed!\n";
    }
    
    /**
     * Create users table
     */
    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            phone VARCHAR(20),
            role VARCHAR(20) DEFAULT 'customer',
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Users table created successfully\n";
        } else {
            echo "Error creating users table\n";
        }
    }
    
    /**
     * Create categories table
     */
    private function createCategoriesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Categories table created successfully\n";
            $this->insertDefaultCategories();
        } else {
            echo "Error creating categories table\n";
        }
    }
    
    /**
     * Create menu items table
     */
    private function createMenuItemsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS menu_items (
            id SERIAL PRIMARY KEY,
            category_id INTEGER REFERENCES categories(id),
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(500),
            is_vegetarian BOOLEAN DEFAULT false,
            is_vegan BOOLEAN DEFAULT false,
            is_gluten_free BOOLEAN DEFAULT false,
            is_available BOOLEAN DEFAULT true,
            allergens TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Menu items table created successfully\n";
        } else {
            echo "Error creating menu items table\n";
        }
    }
    
    /**
     * Create orders table
     */
    private function createOrdersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS orders (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id),
            order_number VARCHAR(50) UNIQUE NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            delivery_address TEXT,
            delivery_phone VARCHAR(20),
            delivery_notes TEXT,
            payment_method VARCHAR(50),
            payment_status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Orders table created successfully\n";
        } else {
            echo "Error creating orders table\n";
        }
    }
    
    /**
     * Create order items table
     */
    private function createOrderItemsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS order_items (
            id SERIAL PRIMARY KEY,
            order_id INTEGER REFERENCES orders(id),
            menu_item_id INTEGER REFERENCES menu_items(id),
            quantity INTEGER NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            special_instructions TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Order items table created successfully\n";
        } else {
            echo "Error creating order items table\n";
        }
    }
    
    /**
     * Create reservations table
     */
    private function createReservationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS reservations (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id),
            name VARCHAR(200) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            date DATE NOT NULL,
            time TIME NOT NULL,
            guests INTEGER NOT NULL,
            special_requests TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Reservations table created successfully\n";
        } else {
            echo "Error creating reservations table\n";
        }
    }
    
    /**
     * Create photos table
     */
    private function createPhotosTable() {
        $sql = "CREATE TABLE IF NOT EXISTS photos (
            id SERIAL PRIMARY KEY,
            title VARCHAR(200),
            description TEXT,
            image_url VARCHAR(500) NOT NULL,
            thumbnail_url VARCHAR(500),
            category VARCHAR(100),
            tags TEXT[],
            is_active BOOLEAN DEFAULT true,
            sort_order INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Photos table created successfully\n";
        } else {
            echo "Error creating photos table\n";
        }
    }
    
    /**
     * Create settings table
     */
    private function createSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id SERIAL PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->db->query($sql)) {
            echo "Settings table created successfully\n";
            $this->insertDefaultSettings();
        } else {
            echo "Error creating settings table\n";
        }
    }
    
    /**
     * Insert default categories
     */
    private function insertDefaultCategories() {
        $categories = [
            ['name' => 'Breakfast & Waffles', 'description' => 'Morning delights and sweet treats', 'sort_order' => 1],
            ['name' => 'Snacks & Meze', 'description' => 'Light bites and Mediterranean flavors', 'sort_order' => 2],
            ['name' => 'Beverages', 'description' => 'Coffee, tea, and refreshing drinks', 'sort_order' => 3],
            ['name' => 'Cocktails & Spirits', 'description' => 'Evening drinks and signature cocktails', 'sort_order' => 4]
        ];
        
        foreach ($categories as $category) {
            $this->db->insert('categories', $category);
        }
        echo "Default categories inserted\n";
    }
    
    /**
     * Insert default settings
     */
    private function insertDefaultSettings() {
        $settings = [
            ['setting_key' => 'restaurant_name', 'setting_value' => 'Das House', 'setting_type' => 'text', 'description' => 'Restaurant name'],
            ['setting_key' => 'restaurant_address', 'setting_value' => 'Gumpendorfer strasse 51, Vienna, Austria', 'setting_type' => 'text', 'description' => 'Restaurant address'],
            ['setting_key' => 'restaurant_phone', 'setting_value' => '+43 677 634 238 81', 'setting_type' => 'text', 'description' => 'Restaurant phone number'],
            ['setting_key' => 'restaurant_email', 'setting_value' => 'info@dashouse.at', 'setting_type' => 'text', 'description' => 'Restaurant email'],
            ['setting_key' => 'opening_hours', 'setting_value' => 'Tue-Thur 10:00am - 23:30pm, Fri-Sat 10:00am - 01:00pm, Sunday 10:00am - 19:00pm, Monday Closed', 'setting_type' => 'text', 'description' => 'Opening hours'],
            ['setting_key' => 'delivery_enabled', 'setting_value' => 'false', 'setting_type' => 'boolean', 'description' => 'Enable delivery service'],
            ['setting_key' => 'reservations_enabled', 'setting_value' => 'true', 'setting_type' => 'boolean', 'description' => 'Enable table reservations']
        ];
        
        foreach ($settings as $setting) {
            $this->db->insert('settings', $setting);
        }
        echo "Default settings inserted\n";
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        if ($this->db->query("SELECT 1")) {
            echo "Database connection successful!\n";
            return true;
        } else {
            echo "Database connection failed!\n";
            return false;
        }
    }
}

// Run the setup if this file is executed directly
if (php_sapi_name() === 'cli' || isset($_GET['setup'])) {
    $setup = new DatabaseSetup();
    
    if ($setup->testConnection()) {
        $setup->setupAllTables();
    } else {
        echo "Cannot proceed without database connection\n";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DasHouse Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>DasHouse Database Setup</h3>
                    </div>
                    <div class="card-body">
                        <p>Click the button below to set up your Supabase database tables:</p>
                        <a href="?setup=1" class="btn btn-primary">Setup Database</a>
                        
                        <?php if (isset($_GET['setup'])): ?>
                            <div class="mt-3">
                                <h5>Setup Results:</h5>
                                <pre class="bg-dark text-light p-3 rounded"><?php
                                    $setup = new DatabaseSetup();
                                    if ($setup->testConnection()) {
                                        $setup->setupAllTables();
                                    }
                                ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
