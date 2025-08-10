<?php
require_once 'includes/SupabaseDB.php';

class DatabaseSeeder {
    private $db;

    public function __construct() {
        $this->db = new SupabaseDB();
    }

    public function seedAllData() {
        echo "<h2>Seeding DasHouse Database with Menu Items</h2>\n";
        
        // First, ensure categories exist
        $this->seedCategories();
        
        // Then seed menu items
        $this->seedMenuItems();
        
        echo "<h3>Database seeding completed!</h3>\n";
    }

    private function seedCategories() {
        echo "<h3>Setting up categories...</h3>\n";
        
        $categories = [
            ['name' => 'Breakfast & Waffles', 'description' => 'Fresh breakfast items and delicious waffles', 'sort_order' => 1],
            ['name' => 'Snacks & Meze', 'description' => 'Light snacks and Mediterranean meze', 'sort_order' => 2],
            ['name' => 'Beverages', 'description' => 'Hot and cold beverages', 'sort_order' => 3],
            ['name' => 'Cocktails & Spirits', 'description' => 'Craft cocktails and spirits', 'sort_order' => 4]
        ];
        
        foreach ($categories as $category) {
            // Check if category already exists
            $existing = $this->db->fetch("SELECT id FROM categories WHERE name = ?", [$category['name']]);
            
            if ($existing) {
                echo "✓ Category '{$category['name']}' already exists\n";
            } else {
                $sql = "INSERT INTO categories (name, description, sort_order, is_active) 
                        VALUES (?, ?, ?, true)";
                
                if ($this->db->query($sql, [$category['name'], $category['description'], $category['sort_order']])) {
                    echo "✓ Category '{$category['name']}' added\n";
                } else {
                    echo "✗ Error adding category '{$category['name']}'\n";
                }
            }
        }
    }

    private function seedMenuItems() {
        echo "<h3>Adding menu items...</h3>\n";
        
        // Get category IDs for reference
        $breakfastCategory = $this->getCategoryId('Breakfast & Waffles');
        $snacksCategory = $this->getCategoryId('Snacks & Meze');
        $beveragesCategory = $this->getCategoryId('Beverages');
        $cocktailsCategory = $this->getCategoryId('Cocktails & Spirits');

        // Breakfast & Waffles
        $breakfastItems = [
            [
                'name' => 'Wiener Frühstück | Viennese Breakfast',
                'description' => 'Semmel, Butter, Marmelade / Breadroll, Butter, Jam',
                'price' => 6.90,
                'category_id' => $breakfastCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Französisches Frühstück | French Breakfast',
                'description' => 'Croissant, Butter, Marmelade / Croissant, Butter, Jam',
                'price' => 7.90,
                'category_id' => $breakfastCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Orientalisches Frühstück | Oriental Breakfast',
                'description' => 'Gebäck, Hummus, Oliven, Tomaten, Gurken, Käse, Putenschinken',
                'price' => 12.90,
                'category_id' => $breakfastCategory,
                'is_vegetarian' => false,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Veganes Frühstück | Vegan Breakfast',
                'description' => 'Gebäck, Hummus, vegane Wurstspezialität, Oliven, Tomaten, Gurken, vegane Käsealternative',
                'price' => 12.90,
                'category_id' => $breakfastCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Apfelmus Waffel | Applesauce Waffle',
                'description' => 'Apfelmus, Zimt, Schlagobers, Zucker / Applesauce, Cinnamon, whipped Cream and Sugar',
                'price' => 11.90,
                'category_id' => $breakfastCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Schokolade Waffel | Chocolate Waffle',
                'description' => 'Frische Früchte, Schokolade, Schlagobers / fresh Fruit, chocolate and whipped Cream',
                'price' => 11.90,
                'category_id' => $breakfastCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ]
        ];

        // Snacks & Meze
        $snackItems = [
            [
                'name' => 'Flammkuchen Mediterran',
                'description' => 'Hirtenkäse, Paprika, Zucchini / Feta Cheese, Peppers, Zucchini',
                'price' => 12.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Flammkuchen Elsässer Art',
                'description' => 'Speck, Zwiebel / Bacon, Onion',
                'price' => 13.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => false,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Veganer Flammkuchen mit Gemüse',
                'description' => 'Vegane Creme, 7 Gemüsesorten / vegan cream, 7 types of vegetables',
                'price' => 14.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Pesto Bread',
                'description' => 'Getoastetes Sauerteigbrot, Pesto, vegane Wurstspezialität, und Käsealternative, Salat, Knoblauch Mayonnaise',
                'price' => 10.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Kimchi Bread',
                'description' => 'Getoastetes Sauerteigbrot, Hummus, vegane Käsealternative, Kimchi, Salat, Knoblauch Mayonnaise',
                'price' => 10.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_available' => true
            ],
            [
                'name' => 'Kisir – Couscous Salat',
                'description' => 'Traditional Turkish bulgur salad with fresh vegetables',
                'price' => 8.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Hummus-Teller mit Gebäck',
                'description' => 'Hummus plate with fresh bread',
                'price' => 8.90,
                'category_id' => $snacksCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_available' => true
            ]
        ];

        // Beverages
        $beverageItems = [
            [
                'name' => 'Espresso',
                'description' => 'Single shot of premium coffee',
                'price' => 3.30,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Espresso Doppio',
                'description' => 'Double shot of premium coffee',
                'price' => 4.90,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Cappuccino',
                'description' => 'Espresso with steamed milk and foam',
                'price' => 4.80,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Café Latte',
                'description' => 'Espresso with steamed milk',
                'price' => 5.20,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Türkischer Kaffee | Turkish coffee',
                'description' => 'aus der Familien-Rösterei „Tok" in Adana | from the family roastery "Tok" in Adana',
                'price' => 4.50,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Heiße Schokolade | Hot Chocolate',
                'description' => 'Rich hot chocolate with whipped cream',
                'price' => 5.50,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Hausgemachte Limonaden | Homemade lemonades',
                'description' => 'Granatapfel-Basilikum, Holunder-Minze, Ingwer-Rosmarin / basil-pomegranate, elderflower-mint, ginger-rosemary',
                'price' => 6.50,
                'category_id' => $beveragesCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ]
        ];

        // Cocktails & Spirits
        $cocktailItems = [
            [
                'name' => 'Aperol Spritz',
                'description' => 'Aperol, Prosecco, Sodawasser',
                'price' => 7.90,
                'category_id' => $cocktailsCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Hugo',
                'description' => 'Holundersirup, Limettensaft, Prosecco, Sodawasser, Minze',
                'price' => 7.90,
                'category_id' => $cocktailsCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Das House Mule',
                'description' => 'Raki, Limettensaft, Ginger Beer',
                'price' => 11.70,
                'category_id' => $cocktailsCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'New York Sour',
                'description' => 'Rye Whiskey, Zitronensaft, Zuckersirup, Rotwein',
                'price' => 15.90,
                'category_id' => $cocktailsCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Mojito',
                'description' => 'leichter Rum, Limette, brauner Zucker, Minze, Angostura Bitters, Sodawasser',
                'price' => 13.70,
                'category_id' => $cocktailsCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => true,
                'is_available' => true
            ],
            [
                'name' => 'Fassbier | Draught beer',
                'description' => 'Murauer 0,3l / 0,5l €5.90',
                'price' => 4.90,
                'category_id' => $cocktailsCategory,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_available' => true
            ]
        ];

        // Insert all menu items
        $allItems = array_merge($breakfastItems, $snackItems, $beverageItems, $cocktailItems);
        
        foreach ($allItems as $item) {
            // Check if menu item already exists
            $existing = $this->db->fetch("SELECT id FROM menu_items WHERE name = ?", [$item['name']]);
            
            if ($existing) {
                echo "✓ Menu item '{$item['name']}' already exists\n";
            } else {
                $sql = "INSERT INTO menu_items (name, description, price, category_id, is_vegetarian, is_vegan, is_gluten_free, is_available, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                if ($this->db->query($sql, [
                    $item['name'], 
                    $item['description'], 
                    $item['price'], 
                    $item['category_id'], 
                    $item['is_vegetarian'], 
                    $item['is_vegan'], 
                    $item['is_gluten_free'], 
                    $item['is_available']
                ])) {
                    echo "✓ Added: {$item['name']} - €{$item['price']}\n";
                } else {
                    echo "✗ Error adding: {$item['name']}\n";
                }
            }
        }
    }

    private function getCategoryId($categoryName) {
        $sql = "SELECT id FROM categories WHERE name = ?";
        $result = $this->db->query($sql, [$categoryName]);
        if ($result && $row = $result->fetch()) {
            return $row['id'];
        }
        return null;
    }

    public function testConnection() {
        try {
            $result = $this->db->query("SELECT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Run the seeder if this file is executed directly
if (php_sapi_name() === 'cli' || isset($_GET['seed'])) {
    $seeder = new DatabaseSeeder();
    if ($seeder->testConnection()) {
        $seeder->seedAllData();
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
    <title>DasHouse Database Seeding</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>DasHouse Database Seeding</h3>
                <p class="mb-0">This will populate your database with all current menu items from your website.</p>
            </div>
            <div class="card-body">
                <p>Click the button below to seed your database with menu items:</p>
                <a href="?seed=1" class="btn btn-success">Seed Database with Menu Items</a>
                
                <?php if (isset($_GET['seed'])): ?>
                    <div class="mt-4">
                        <h5>Seeding Results:</h5>
                        <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><?php
                            $seeder = new DatabaseSeeder();
                            if ($seeder->testConnection()) {
                                $seeder->seedAllData();
                            } else {
                                echo "Cannot proceed without database connection\n";
                            }
                        ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
