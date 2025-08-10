<?php
// Supabase configuration for DasHouse project
// Replace these values with your actual Supabase project details

define('SUPABASE_URL', 'https://lvatvujwtyqwdsbqxjvm.supabase.co'); // project URL
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imx2YXR2dWp3dHlxd2RzYnF4anZtIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTQ4MTI5MjcsImV4cCI6MjA3MDM4ODkyN30.mg5GMCY8LGGUfVNLCWUCnPX2Q5LDAbKgoAJczFPm6QI'); // Replace with your anon key
define('SUPABASE_SERVICE_ROLE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imx2YXR2dWp3dHlxd2RzYnF4anZtIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1NDgxMjkyNywiZXhwIjoyMDcwMzg4OTI3fQ.lB-b2BVLIdWIspLhFTNSjlaHWR95XuKs5HtAfoBpD5Y'); // Replace with your service role key

// Database connection details
define('SUPABASE_DB_HOST', 'db.lvatvujwtyqwdsbqxjvm.supabase.co'); // Replace with your database host
define('SUPABASE_DB_NAME', 'postgres'); // Default database name
define('SUPABASE_DB_USER', 'postgres'); // Default database user
define('SUPABASE_DB_PASSWORD', 'slhAvH0Qyx1R4ifB'); // Your database password

// Optional: Environment detection
define('IS_DEVELOPMENT', true); // Set to false in production

// Supabase API endpoints
define('SUPABASE_AUTH_ENDPOINT', SUPABASE_URL . '/auth/v1');
define('SUPABASE_REST_ENDPOINT', SUPABASE_URL . '/rest/v1');
define('SUPABASE_STORAGE_ENDPOINT', SUPABASE_URL . '/storage/v1');

// Database tables (define your table names here)
define('TABLE_USERS', 'users');
define('TABLE_MENU_ITEMS', 'menu_items');
define('TABLE_CATEGORIES', 'categories');
define('TABLE_ORDERS', 'orders');
define('TABLE_RESERVATIONS', 'reservations');
define('TABLE_PHOTOS', 'photos');
define('TABLE_SETTINGS', 'settings');

// Error reporting
if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
