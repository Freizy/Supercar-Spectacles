<?php

/**
 * Database Configuration for Supercar Spectacles
 * * This file contains the database connection configuration
 * and initialization for the Supercar Spectacles website.
 * * NOTE: The Database class now uses the constants defined below
 * for consistency and ease of maintenance.
 */

// Database configuration constants (Define these first so the class can use them)
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


class Database
{
    private $conn;

    // We no longer need to define $host, $db_name, etc., as private properties
    // because we are using the constants defined above.

    public function __construct()
    {
        // Removed: $this->config = require 'db_config.php';
        // Use the defined constants for configuration
    }

    /**
     * Get database connection
     * @return PDO|null The PDO connection object or null on failure.
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $this->conn = new PDO(
                $dsn,
                DB_USER,
                DB_PASS
            );
            
            // Set attributes for proper error handling and default fetch mode
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            // In a production environment, log the error and hide the sensitive details.
            error_log("Database Connection Error: " . $exception->getMessage());
            die("A database connection error occurred. Please try again later.");
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection()
    {
        // Setting the connection to null closes it.
        $this->conn = null;
    }
}
