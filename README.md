# Supercar Spectacles - PHP Backend

A comprehensive PHP backend system for the Supercar Spectacles website, featuring showcase registrations, news management, car sales, gallery management, newsletter subscriptions, and a full admin panel.

## 🚀 Features

### Core Functionality
- **Showcase Registration System** - Handle supercar showcase registrations with approval workflow
- **News Management** - Create, edit, and manage news articles with categories
- **Car Sales System** - Manage car listings and handle customer inquiries
- **Gallery Management** - Upload, organize, and manage gallery images
- **Newsletter System** - Handle subscriptions and send newsletters
- **Contact Form** - Process contact form submissions
- **Admin Panel** - Comprehensive dashboard for managing all aspects

### Technical Features
- **RESTful API** - Clean API endpoints for all functionality
- **Database Integration** - MySQL database with proper relationships
- **Security** - Input validation, SQL injection prevention, XSS protection
- **File Upload** - Secure image upload with validation
- **Email Notifications** - Automated email notifications
- **Responsive Design** - Mobile-friendly admin interface
- **Activity Logging** - Track admin activities and user actions

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP extensions: PDO, PDO_MySQL, GD, OpenSSL

## 🛠️ Installation

### 1. Database Setup

1. Create a MySQL database named `supercar_spectacles`
2. Import the database schema:
   ```bash
   mysql -u your_username -p supercar_spectacles < database/schema.sql
   ```

### 2. Configuration

1. Update the database configuration in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'supercar_spectacles';
   private $username = 'your_db_username';
   private $password = 'your_db_password';
   ```

2. Update other configuration settings as needed:
   - Email settings (SMTP configuration)
   - File upload paths
   - Security keys
   - Application URLs

### 3. File Permissions

Set proper permissions for upload directories:
```bash
chmod 755 uploads/
chmod 755 uploads/gallery/
```

### 4. Admin Account

The default admin account is created automatically:
- **Username:** admin
- **Password:** password (change this immediately!)

## 📁 Project Structure

```
SS/
├── api/                    # API endpoints
│   ├── showcase.php       # Showcase registration API
│   ├── news.php          # News management API
│   ├── cars.php          # Car sales API
│   ├── gallery.php       # Gallery management API
│   ├── newsletter.php    # Newsletter API
│   └── contact.php       # Contact form API
├── admin/                 # Admin panel
│   ├── index.php         # Dashboard
│   ├── login.php         # Admin login
│   └── logout.php        # Admin logout
├── config/               # Configuration files
│   └── database.php      # Database configuration
├── database/             # Database files
│   └── schema.sql        # Database schema
├── includes/             # Utility classes
│   └── Utils.php         # Common utility functions
├── uploads/              # File uploads
│   └── gallery/          # Gallery images
└── README.md             # This file
```

## 🔌 API Endpoints

### Showcase Registration
- `POST /api/showcase.php?action=submit` - Submit registration
- `GET /api/showcase.php?action=list` - Get registrations (admin)
- `PUT /api/showcase.php?action=update` - Update status (admin)
- `GET /api/showcase.php?action=stats` - Get statistics (admin)

### News Management
- `GET /api/news.php?action=list` - Get published articles
- `GET /api/news.php?action=article&slug=article-slug` - Get single article
- `GET /api/news.php?action=featured` - Get featured articles
- `POST /api/news.php?action=create` - Create article (admin)
- `PUT /api/news.php?action=update` - Update article (admin)
- `DELETE /api/news.php?action=delete&id=123` - Delete article (admin)

### Car Sales
- `GET /api/cars.php?action=list` - Get car listings
- `GET /api/cars.php?action=get&id=123` - Get single car
- `POST /api/cars.php?action=inquiry` - Submit inquiry
- `GET /api/cars.php?action=stats` - Get statistics
- `GET /api/cars.php?action=inquiries` - Get inquiries (admin)

### Gallery Management
- `GET /api/gallery.php?action=list` - Get gallery images
- `GET /api/gallery.php?action=featured` - Get featured images
- `GET /api/gallery.php?action=categories` - Get categories
- `POST /api/gallery.php?action=upload` - Upload image (admin)
- `PUT /api/gallery.php?action=update` - Update image (admin)
- `DELETE /api/gallery.php?action=delete&id=123` - Delete image (admin)

### Newsletter
- `POST /api/newsletter.php?action=subscribe` - Subscribe to newsletter
- `POST /api/newsletter.php?action=unsubscribe` - Unsubscribe
- `GET /api/newsletter.php?action=subscribers` - Get subscribers (admin)
- `GET /api/newsletter.php?action=stats` - Get statistics (admin)
- `POST /api/newsletter.php?action=send` - Send newsletter (admin)

### Contact Form
- `POST /api/contact.php?action=submit` - Submit contact form
- `GET /api/contact.php?action=list` - Get submissions (admin)
- `PUT /api/contact.php?action=update` - Update status (admin)

## 🔐 Admin Panel

Access the admin panel at: `http://your-domain.com/admin/`

### Features:
- **Dashboard** - Overview of all system statistics
- **Showcase Management** - Review and approve registrations
- **News Management** - Create and manage articles
- **Car Sales** - Manage listings and inquiries
- **Gallery Management** - Upload and organize images
- **Newsletter** - Manage subscribers and send newsletters
- **Settings** - Configure system settings

## 🔧 Frontend Integration

### JavaScript Examples

#### Submit Showcase Registration
```javascript
fetch('/api/showcase.php?action=submit', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        owner_name: 'John Doe',
        car_make: 'Ferrari',
        car_model: '488 Pista',
        contact_number: '+233123456789',
        plate_number: 'GT-1234-AB',
        description: 'Beautiful Ferrari with custom modifications'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Registration submitted successfully!');
    } else {
        alert('Error: ' + data.error);
    }
});
```

#### Subscribe to Newsletter
```javascript
fetch('/api/newsletter.php?action=subscribe', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        email: 'user@example.com',
        name: 'John Doe'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Successfully subscribed!');
    } else {
        alert('Error: ' + data.error);
    }
});
```

#### Get News Articles
```javascript
fetch('/api/news.php?action=list&category=event&page=1')
.then(response => response.json())
.then(data => {
    if (data.success) {
        data.data.forEach(article => {
            console.log(article.title);
        });
    }
});
```

## 🛡️ Security Features

- **Input Validation** - All inputs are validated and sanitized
- **SQL Injection Prevention** - Using prepared statements
- **XSS Protection** - HTML entities encoding
- **CSRF Protection** - Token-based protection (implement as needed)
- **File Upload Security** - Type and size validation
- **Admin Authentication** - Secure login system
- **Activity Logging** - Track all admin activities

## 📧 Email Configuration

Update email settings in `config/database.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'your-email@gmail.com');
define('FROM_NAME', 'Supercar Spectacles');
```

## 🔄 Database Schema

The database includes the following main tables:
- `users` - Admin and user accounts
- `showcase_registrations` - Car showcase registrations
- `news_articles` - News and blog articles
- `car_listings` - Car sales listings
- `car_images` - Car listing images
- `car_inquiries` - Car inquiry submissions
- `gallery_images` - Gallery images
- `newsletter_subscriptions` - Newsletter subscribers
- `contact_submissions` - Contact form submissions
- `event_info` - Event information
- `site_settings` - Site configuration

## 🚀 Deployment

### Production Checklist:
1. Update database credentials
2. Set secure file permissions
3. Configure email settings
4. Change default admin password
5. Enable HTTPS
6. Set up regular database backups
7. Configure error logging
8. Update security keys

### Environment Variables:
Consider using environment variables for sensitive configuration:
```php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'supercar_spectacles';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';
```

## 📞 Support

For support and questions:
- Email: supercarspectacle1@gmail.com
- Phone: 0558702163

## 📄 License

This project is proprietary software for Supercar Spectacles.

---

**Built with ❤️ for Supercar Spectacles 2025**
