# Business Information Management System

This system allows you to manage your business contact information, address, and operating hours through the admin panel.

## Features

- **Business Information Management**: Update business name, email, phone, and website
- **Address Management**: Store and update street address, city, state, ZIP code, and country
- **Operating Hours**: Set opening and closing times for each day of the week
- **Business Description**: Add a custom description for your business
- **API Access**: RESTful API endpoints for frontend integration

## Setup Instructions

### 1. Database Setup

First, you need to create the `business_info` table in your database. You can either:

**Option A: Use the SQL Script**
- Download `create-business-info-table.sql`
- Run it in your database management tool (pgAdmin, Supabase SQL editor, etc.)

**Option B: Use the Setup Page**
- Navigate to `setup-business-info.php` in your admin panel
- Click "Initialize Business Info Table" to automatically set up the system

### 2. Access the Business Info Manager

- Log into your admin panel
- Navigate to "Business Info" in the sidebar
- Or use the quick action card on the dashboard

## Usage

### Managing Business Information

1. **Basic Information**
   - Business name (required)
   - Email address
   - Phone number
   - Website URL
   - Business description

2. **Address Information**
   - Street address
   - City
   - State/Province
   - ZIP/Postal code
   - Country

3. **Operating Hours**
   - Set opening and closing times for each day
   - Mark days as closed by leaving times empty
   - Use the "Closed" checkbox for quick day management

### Saving Changes

- Click "Save Business Information" to update all fields
- The system will validate required fields
- Success/error messages will be displayed

## API Endpoints

### GET /admin/api/business-info.php
Retrieves current business information.

**Response:**
```json
{
  "success": true,
  "data": {
    "business_name": "Das House",
    "email": "info@dashouse.com",
    "phone": "(555) 123-4567",
    "address": "123 Main Street",
    "city": "Anytown",
    "state": "CA",
    "zip_code": "90210",
    "country": "United States",
    "description": "Welcome to Das House...",
    "website": "https://dashouse.com",
    "monday_open": "09:00",
    "monday_close": "17:00",
    // ... other days
  }
}
```

### POST/PUT/PATCH /admin/api/business-info.php
Updates business information.

**Request Body:**
```json
{
  "business_name": "Updated Business Name",
  "email": "newemail@example.com",
  "phone": "(555) 987-6543"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Business information updated successfully"
}
```

## Database Schema

The `business_info` table contains the following fields:

| Field | Type | Description |
|-------|------|-------------|
| id | SERIAL | Primary key |
| business_name | VARCHAR(255) | Business name (required) |
| email | VARCHAR(255) | Contact email |
| phone | VARCHAR(50) | Phone number |
| address | TEXT | Street address |
| city | VARCHAR(100) | City |
| state | VARCHAR(100) | State/Province |
| zip_code | VARCHAR(20) | ZIP/Postal code |
| country | VARCHAR(100) | Country |
| description | TEXT | Business description |
| website | VARCHAR(255) | Website URL |
| monday_open | TIME | Monday opening time |
| monday_close | TIME | Monday closing time |
| tuesday_open | TIME | Tuesday opening time |
| tuesday_close | TIME | Tuesday closing time |
| wednesday_open | TIME | Wednesday opening time |
| wednesday_close | TIME | Wednesday closing time |
| thursday_open | TIME | Thursday opening time |
| thursday_close | TIME | Thursday closing time |
| friday_open | TIME | Friday opening time |
| friday_close | TIME | Friday closing time |
| saturday_open | TIME | Saturday opening time |
| saturday_close | TIME | Saturday closing time |
| sunday_open | TIME | Sunday opening time |
| sunday_close | TIME | Sunday closing time |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

## Frontend Integration

The customer-facing website now automatically uses the new backend API! Here's what's been implemented:

### 1. **Automatic Business Info Loading**
- The frontend automatically fetches business information when pages load
- No manual updates needed - changes in admin panel appear on website immediately
- Fallback to default values if API is unavailable

### 2. **JavaScript Integration** (`js/business-info-loader.js`)
```javascript
// The system automatically:
// - Loads business info on page load
// - Updates contact section with current data
// - Updates page title and navigation
// - Handles phone and email links
// - Formats and displays operating hours
```

### 3. **Updated Pages**
- **Main page** (`index.html`): Contact section now uses dynamic data
- **Gift shop page** (`gift-shop.html`): Phone number updates automatically
- Both pages include the business info loader script

### 4. **Manual API Usage** (if needed)
```javascript
fetch('/admin/api/business-info.php')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      document.getElementById('business-name').textContent = data.data.business_name;
      document.getElementById('business-phone').textContent = data.data.phone;
      // ... populate other fields
    }
  });
```

### 5. **Display Operating Hours**
```javascript
function formatHours(open, close) {
  if (!open || !close) return 'Closed';
  return `${open} - ${close}`;
}

// Example usage for Monday
const mondayHours = formatHours(data.data.monday_open, data.data.monday_close);
```

## Troubleshooting

### Common Issues

1. **"Table doesn't exist" error**
   - Run the SQL script to create the table
   - Or use the setup page to initialize the system

2. **"Failed to update" error**
   - Check that all required fields are filled
   - Verify database connection and permissions
   - Check server error logs

3. **Time fields not saving**
   - Ensure time format is HH:MM (24-hour format)
   - Check that the database supports TIME data type

### Support

If you encounter issues:
1. Check the admin panel error messages
2. Verify database connectivity
3. Review server error logs
4. Ensure proper file permissions

## Security Notes

- The business info manager requires admin authentication
- API endpoints are publicly accessible (for frontend use)
- Input validation is performed on all form submissions
- SQL injection protection is implemented through prepared statements

## Future Enhancements

Potential improvements for future versions:
- Social media links management
- Holiday hours and special schedules
- Multiple location support
- Business hours widget generation
- Integration with Google My Business
- Email notifications for updates
