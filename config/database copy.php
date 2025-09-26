<?php

/**
 * Database Configuration for Supercar Spectacles
 * 
 * This file contains the database connection configuration
 * and initialization for the Supercar Spectacles website.
 */

class Database
{
    private $host = 'localhost';
    private $db_name = 'supercar_spectacles';
    private $username = 'root';
    private $password = '';
    private $conn;
    private $config;

    public function __construct()
    {
        // You can initialize any required properties here if needed
        $this->config = require 'db_config.php';
    }

    /**
     * Get database connection
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection()
    {
        $this->conn = null;
    }
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'supercar_spectacles');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Supercar Spectacles');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/projects/SS');
define('ADMIN_EMAIL', 'admin@supercarspectacles.com');

// Security configuration
define('JWT_SECRET', 'your_jwt_secret_key_here');
define('ENCRYPTION_KEY', 'your_encryption_key_here');

// File upload configuration
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'supercarspectacle1@gmail.com');
define('SMTP_PASSWORD', 'your_app_password_here');
define('FROM_EMAIL', 'supercarspectacle1@gmail.com');
define('FROM_NAME', 'Supercar Spectacles');

// Payment configuration (for future integration)
define('PAYMENT_GATEWAY', 'paystack'); // or 'flutterwave', 'stripe'
define('PAYSTACK_PUBLIC_KEY', 'your_paystack_public_key');
define('PAYSTACK_SECRET_KEY', 'your_paystack_secret_key');

// Event configuration
define('EVENT_DATE', '2025-12-15');
define('EVENT_END_DATE', '2025-12-17');
define('EVENT_LOCATION', 'Borteyman Stadium, Accra, Ghana');
define('EVENT_TIME', '10:00 AM - 8:00 PM');

// Ticket pricing
define('GENERAL_TICKET_PRICE', 500);
define('VIP_TICKET_PRICE', 1500);
define('PREMIUM_TICKET_PRICE', 2500);
