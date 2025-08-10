# Das House Admin Panel

A simple, user-friendly admin panel for managing your restaurant website's menu and photos. **No coding experience required!**

## 🚀 Quick Start

1. **Access the admin panel**: Go to `yourwebsite.com/admin/`
2. **Login credentials**:
   - Username: `admin`
   - Password: `dashouse2024`
3. **Start managing**: Use the simple forms to update your menu and photos

## 🔐 Security

**Important**: Change the default password immediately after first login!

- Default username: `admin`
- Default password: `dashouse2024`
- The password is stored in `admin/index.php` (line 7)

## 📱 What You Can Do

### 🍽️ Menu Manager
- **Add new menu items** with simple forms
- **Edit existing items** (name, description, price, category)
- **Delete items** you no longer serve
- **Toggle visibility** (hide/show items temporarily)
- **Add dietary labels** (Vegan, Gluten-Free, etc.)
- **Organize by categories** (Breakfast, Snacks, Beverages, etc.)

### 📸 Photo Manager
- **Upload new photos** (drag & drop or click to browse)
- **Organize photos** by category
- **Delete old photos** you don't need
- **Toggle visibility** (hide/show photos)
- **Add descriptions** to make photos easier to find

### ⚙️ Settings
- **Change your password**
- **View system information**
- **Quick access** to other sections

## 🎯 How to Use

### Adding a Menu Item
1. Go to **Menu Manager**
2. Click **"Add Menu Item"** button
3. Fill out the form:
   - **Item Name**: What you call it (e.g., "Wiener Frühstück")
   - **Price**: How much it costs (e.g., 6.90)
   - **Category**: Which section it belongs in
   - **Description**: What's in it (e.g., "Semmel, Butter, Marmelade")
   - **Dietary Options**: Check boxes for special diets
4. Click **"Add Item"**

### Uploading a Photo
1. Go to **Photo Manager**
2. Click **"Upload Photos"** button
3. **Drag & drop** your photo or click to browse
4. Select the **category** it belongs to
5. Add a **description** (optional but helpful)
6. Click **"Upload Photo"**

### Editing Existing Items
1. Find the item you want to change
2. Click the **pencil icon** (edit button)
3. Make your changes in the popup form
4. Click **"Update Item"**

### Hiding Items Temporarily
1. Find the item you want to hide
2. Click the **eye icon** (toggle button)
3. The item will be hidden from customers but not deleted
4. Click the same button to show it again

## 📁 File Structure

```
admin/
├── index.php          # Login page
├── dashboard.php      # Main dashboard
├── menu-manager.php   # Menu management
├── photo-manager.php  # Photo management
├── settings.php       # Settings & password change
├── README.md          # This file
└── data/              # Data storage (created automatically)
    ├── menu.json      # Menu items
    └── photos.json    # Photo information
```

## 🔧 Technical Details

- **Built with**: PHP, Bootstrap 5, JavaScript
- **Data storage**: JSON files (no database required)
- **File uploads**: Stored in `demos/burger/images/others/`
- **Security**: Session-based authentication
- **Mobile-friendly**: Works on phones and tablets

## 🚨 Important Notes

1. **Backup your data**: The JSON files contain all your menu and photo information
2. **File permissions**: Make sure the `data/` folder is writable by your web server
3. **Photo formats**: Supports JPG, PNG, GIF, WebP (max 5MB per file)
4. **Regular updates**: Check for updates to keep your admin panel secure

## 🆘 Troubleshooting

### Can't upload photos?
- Check that the `demos/burger/images/others/` folder exists and is writable
- Make sure your photo is under 5MB
- Try a different photo format (JPG usually works best)

### Menu changes not showing?
- Make sure you saved the changes (click the save/update button)
- Check that the item is set to "active"
- Refresh the page to see updates

### Can't log in?
- Check your username and password
- Make sure you're using the correct URL (`/admin/`)
- Contact your web developer if issues persist

## 📞 Support

If you need help or have questions:
1. Check this README first
2. Look for error messages on the screen
3. Make sure all files are uploaded correctly
4. Contact your web developer for technical issues

## 🔄 Updates

The admin panel automatically:
- Creates necessary folders
- Saves your changes immediately
- Organizes photos by category
- Keeps track of when items were added/updated

---

**Happy managing! 🎉**

Your Das House website will now be much easier to keep up-to-date with fresh menus and photos.
