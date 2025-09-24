# 🚗 Backend Testing Guide

This guide will help you test if your Supercar Spectacles backend is running perfectly.

## 📋 Pre-Testing Checklist

Before running tests, ensure you have:

1. **✅ Database Setup**
   - MySQL server running
   - Database `supercar_spectacles` created
   - Schema imported from `database/schema.sql`

2. **✅ File Structure**
   - All backend files in correct locations
   - Proper file permissions (755 for directories, 644 for files)
   - Upload directories created and writable

3. **✅ Configuration**
   - Database credentials updated in `config/database.php`
   - Email settings configured (optional for basic testing)

## 🧪 Testing Methods

### Method 1: Automated Test Suite (Recommended)

1. **Run the Backend Test Suite**
   ```
   http://your-domain.com/test/test-backend.php
   ```

2. **What it tests:**
   - ✅ Database connection
   - ✅ All required tables exist
   - ✅ API endpoints are accessible
   - ✅ File permissions
   - ✅ PHP configuration
   - ✅ Admin panel files
   - ✅ Sample data

### Method 2: Interactive API Testing

1. **Open the API Testing Interface**
   ```
   http://your-domain.com/test/test-apis.html
   ```

2. **Test each API:**
   - Newsletter subscription
   - Showcase registration
   - News management
   - Car listings
   - Gallery management
   - Contact forms

### Method 3: Manual Testing

#### Test Database Connection
```php
<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
if ($conn) {
    echo "✅ Database connected successfully!";
} else {
    echo "❌ Database connection failed!";
}
?>
```

#### Test API Endpoints
```bash
# Test newsletter stats
curl -X GET "http://your-domain.com/api/newsletter.php?action=stats"

# Test showcase stats
curl -X GET "http://your-domain.com/api/showcase.php?action=stats"

# Test news list
curl -X GET "http://your-domain.com/api/news.php?action=list"
```

## 🔍 What to Look For

### ✅ Success Indicators

1. **Database Tests**
   - All tables exist
   - Admin user created
   - Event info populated
   - No connection errors

2. **API Tests**
   - All endpoints return JSON responses
   - No 404 or 500 errors
   - Proper success/error messages
   - Data returned in expected format

3. **File Tests**
   - Upload directories writable
   - Admin files accessible
   - No permission errors

### ❌ Common Issues & Solutions

#### Database Connection Failed
```
❌ Database connection failed!
```
**Solution:**
- Check MySQL server is running
- Verify database credentials in `config/database.php`
- Ensure database `supercar_spectacles` exists
- Import schema: `mysql -u username -p supercar_spectacles < database/schema.sql`

#### API Endpoints Not Accessible
```
❌ API endpoint is not accessible
```
**Solution:**
- Check file paths are correct
- Verify web server is running
- Check for PHP syntax errors
- Ensure proper file permissions

#### Missing Tables
```
❌ Missing tables: users, showcase_registrations
```
**Solution:**
- Import the database schema
- Check database name is correct
- Verify user has CREATE TABLE permissions

#### File Permission Errors
```
❌ Directory is not writable
```
**Solution:**
```bash
chmod 755 uploads/
chmod 755 uploads/gallery/
chown www-data:www-data uploads/ -R  # For Apache
```

## 🎯 Step-by-Step Testing Process

### Step 1: Basic Setup Test
1. Open `test/test-backend.php` in your browser
2. Check all green checkmarks ✅
3. Fix any red X marks ❌

### Step 2: API Functionality Test
1. Open `test/test-apis.html` in your browser
2. Click "Test All APIs" button
3. Verify all APIs return success responses
4. Test individual features

### Step 3: Admin Panel Test
1. Go to `admin/login.php`
2. Login with default credentials:
   - Username: `admin`
   - Password: `password`
3. Verify dashboard loads with statistics
4. Test navigation between admin sections

### Step 4: Frontend Integration Test
1. Test showcase registration form
2. Test newsletter subscription
3. Test contact form
4. Verify data appears in admin panel

## 📊 Expected Results

### Database Statistics
- **Users:** 1 (admin user)
- **Event Info:** 1 (Supercar Spectacle 2025)
- **Site Settings:** 12+ configuration entries

### API Response Format
```json
{
    "success": true,
    "data": [...],
    "message": "Operation completed successfully"
}
```

### Admin Panel Features
- Dashboard with statistics
- Showcase registrations management
- News article management
- Car listings management
- Gallery management
- Newsletter management

## 🚨 Troubleshooting

### If Tests Fail

1. **Check Error Logs**
   ```bash
   tail -f /var/log/apache2/error.log  # Apache
   tail -f /var/log/nginx/error.log    # Nginx
   ```

2. **Enable PHP Error Display**
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. **Verify File Permissions**
   ```bash
   ls -la config/
   ls -la api/
   ls -la admin/
   ```

4. **Test Database Manually**
   ```bash
   mysql -u username -p
   USE supercar_spectacles;
   SHOW TABLES;
   SELECT * FROM users;
   ```

## ✅ Success Criteria

Your backend is working perfectly when:

1. **All automated tests pass** ✅
2. **All API endpoints respond correctly** ✅
3. **Admin panel loads and functions** ✅
4. **Database contains expected data** ✅
5. **File uploads work** ✅
6. **Email notifications send** ✅ (optional)

## 🎉 Next Steps

Once all tests pass:

1. **Change default admin password**
2. **Configure email settings**
3. **Add sample data**
4. **Test frontend integration**
5. **Deploy to production**

## 📞 Support

If you encounter issues:

1. Check the error messages in test results
2. Review the troubleshooting section
3. Check server error logs
4. Verify all requirements are met

---

**Happy Testing! 🚗✨**
