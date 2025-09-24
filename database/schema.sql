-- Supercar Spectacles Database Schema
-- This file contains the complete database structure for the Supercar Spectacles website

-- Create database
CREATE DATABASE IF NOT EXISTS supercar_spectacles CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE supercar_spectacles;

-- Users table for admin and user management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Supercar showcase registrations
CREATE TABLE showcase_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(100) NOT NULL,
    car_make VARCHAR(50) NOT NULL,
    car_model VARCHAR(50) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    plate_number VARCHAR(20) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- News and blog posts
CREATE TABLE news_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255),
    category ENUM('event', 'supercars', 'technology', 'general') DEFAULT 'general',
    author_id INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Car sales listings
CREATE TABLE car_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    mileage INT,
    color VARCHAR(50),
    transmission ENUM('manual', 'automatic', 'semi-automatic') DEFAULT 'automatic',
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') DEFAULT 'petrol',
    power_hp INT,
    acceleration_0_60 DECIMAL(3,1),
    top_speed INT,
    description TEXT,
    main_image VARCHAR(255),
    status ENUM('available', 'sold', 'pending', 'withdrawn') DEFAULT 'available',
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Car images for listings
CREATE TABLE car_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES car_listings(id) ON DELETE CASCADE
);

-- Car inquiries
CREATE TABLE car_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT,
    status ENUM('new', 'contacted', 'responded', 'closed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES car_listings(id) ON DELETE CASCADE
);

-- Gallery images
CREATE TABLE gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    category VARCHAR(50),
    sort_order INT DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Newsletter subscriptions
CREATE TABLE newsletter_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100),
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Contact form submissions
CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'responded') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Event information
CREATE TABLE event_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    event_end_date DATE,
    event_time VARCHAR(100),
    location VARCHAR(255),
    description TEXT,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Site settings
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified) 
VALUES ('admin', 'admin@supercarspectacles.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active', TRUE);

-- Insert default event information
INSERT INTO event_info (event_name, event_date, event_end_date, event_time, location, description, status) 
VALUES ('Supercar Spectacle 2025', '2025-12-15', '2025-12-17', '10:00 AM - 8:00 PM', 'Borteyman Stadium, Accra, Ghana', 'West Africa\'s premier automotive lifestyle festival', 'upcoming');

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_title', 'Supercar Spectacles', 'text', 'Main site title'),
('site_description', 'Ghana\'s premier automotive showcase', 'text', 'Site description'),
('contact_email', 'supercarspectacle1@gmail.com', 'text', 'Main contact email'),
('contact_phone', '0558702163', 'text', 'Main contact phone'),
('social_facebook', '#', 'text', 'Facebook page URL'),
('social_twitter', '#', 'text', 'Twitter page URL'),
('social_instagram', '#', 'text', 'Instagram page URL'),
('social_youtube', '#', 'text', 'YouTube channel URL'),
('newsletter_enabled', 'true', 'boolean', 'Enable newsletter subscription'),
('gallery_items_per_page', '12', 'number', 'Number of gallery items per page'),
('news_items_per_page', '6', 'number', 'Number of news items per page'),
('car_listings_per_page', '9', 'number', 'Number of car listings per page');

-- Create indexes for better performance
CREATE INDEX idx_news_category ON news_articles(category);
CREATE INDEX idx_news_status ON news_articles(status);
CREATE INDEX idx_news_published ON news_articles(published_at);
CREATE INDEX idx_car_make ON car_listings(make);
CREATE INDEX idx_car_status ON car_listings(status);
CREATE INDEX idx_car_price ON car_listings(price);
CREATE INDEX idx_gallery_status ON gallery_images(status);
CREATE INDEX idx_gallery_featured ON gallery_images(featured);
CREATE INDEX idx_showcase_status ON showcase_registrations(status);
CREATE INDEX idx_inquiry_status ON car_inquiries(status);
CREATE INDEX idx_newsletter_status ON newsletter_subscriptions(status);
