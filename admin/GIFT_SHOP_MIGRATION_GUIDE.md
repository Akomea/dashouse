# Gift Shop Migration Guide

## Overview
The gift shop was previously using JSON files for data storage. This guide helps you migrate to Supabase database tables for better performance and management.

## Step 1: Create the Database Table

1. **Go to your Supabase Dashboard**
   - Navigate to [supabase.com](https://supabase.com)
   - Go to your project: `lvatvujwtyqwdsbqxjvm`

2. **Open SQL Editor**
   - Click on "SQL Editor" in the left sidebar
   - Click "New Query"

3. **Run the Table Creation Script**
   - Copy the entire contents of `admin/create-gift-shop-table.sql`
   - Paste it into the SQL editor
   - Click "Run" to execute the script

4. **Verify Table Creation**
   - Go to "Table Editor" in the sidebar
   - You should see a new table called `gift_shop_items`

## Step 2: Migrate Your Data

1. **Upload Files to Your Server**
   - Upload `admin/migrate-gift-shop-data.php` to your cPanel hosting
   - Ensure `data/gift-shop.json` exists with your current data

2. **Run the Migration Script**
   - Visit: `yoursite.com/admin/migrate-gift-shop-data.php`
   - Follow the on-screen instructions
   - The script will automatically transfer your JSON data to Supabase

## Step 3: Update Your Code

Your API files have already been updated to use the Supabase table:
- `admin/api/gift-shop.php` - Now uses Supabase REST API
- `js/das-house-gift-shop.js` - Will automatically load from new API

## Step 4: Test Everything

1. **Run API Test**
   - Visit: `yoursite.com/admin/test-api.php`
   - Check that "Gift Shop Items Table" shows SUCCESS

2. **Check Your Gift Shop Page**
   - Visit your gift shop page
   - Items should load from the database
   - Images should display correctly

## Troubleshooting

### Table Creation Issues
- **Error: "permission denied"** - Make sure you're using your project owner account
- **Error: "already exists"** - The table might already exist, check Table Editor

### Migration Issues
- **Error: "Table not found"** - Run Step 1 first to create the table
- **Error: "Permission denied"** - Check that your service role key is correct in `config/supabase.php`

### API Issues
- **404 errors** - Table doesn't exist or RLS policies are too restrictive
- **403 errors** - Authentication/permission issues

### Row Level Security (RLS)
If you have issues with permissions, you might need to adjust the RLS policies:

```sql
-- Allow anonymous users to read active items
CREATE POLICY "Allow anonymous read" ON gift_shop_items
    FOR SELECT USING (active = true);

-- Allow service role full access
CREATE POLICY "Allow service role" ON gift_shop_items
    FOR ALL USING (auth.role() = 'service_role');
```

## Benefits After Migration

1. **Better Performance** - Database queries are faster than file operations
2. **Real-time Updates** - Changes reflect immediately across all pages
3. **Better Admin Tools** - Use Supabase dashboard to manage data
4. **Automatic Backups** - Supabase handles data backups
5. **Scalability** - Can handle many more items efficiently

## Next Steps

After successful migration:
1. Backup your `data/gift-shop.json` file
2. Consider removing the old JSON file once everything works
3. Use the admin panel to add new gift shop items
4. Monitor the Supabase dashboard for usage statistics
