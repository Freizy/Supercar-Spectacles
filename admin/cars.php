<?php

/**
 * Supercar Spectacles Admin Panel
 * Newsletter subscriber management interface.
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

$db = new Database();
$conn = $db->getConnection();




?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Gallery</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    /* All existing CSS from the previous file */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f5f5f5;
      color: #333;
    }

    .admin-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 250px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px 0;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      z-index: 10;
    }

    .sidebar-header {
      padding: 0 20px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 20px;
    }

    .sidebar-header h2 {
      font-size: 1.5rem;
      margin-bottom: 5px;
    }

    .sidebar-header p {
      opacity: 0.8;
      font-size: 0.9rem;
    }

    .nav-menu {
      list-style: none;
    }

    .nav-item {
      margin-bottom: 5px;
    }

    .nav-link {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .nav-link:hover,
    .nav-link.active {
      background-color: rgba(255, 255, 255, 0.1);
      border-right: 3px solid #fff;
    }

    .nav-link i {
      margin-right: 10px;
      width: 20px;
    }

    /* Main Content Styles */
    .main-content {
      flex: 1;
      margin-left: 250px;
      padding: 20px;
    }

    .header {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }

    .header h1 {
      color: #333;
      margin-bottom: 10px;
    }

    .header p {
      color: #666;
    }

    /* Content Section */
    .content-section {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
    }

    .content-section h2 {
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: #333;
      padding-bottom: 10px;
      border-bottom: 2px solid #eee;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #555;
    }

    .form-group input[type="file"],
    .form-group input[type="text"],
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    .btn-submit {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1rem;
      width: fit-content;
    }

    .btn-submit:hover {
      opacity: 0.9;
    }

    /* Gallery Grid */
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .gallery-item {
      position: relative;
      overflow: hidden;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .gallery-item img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      display: block;
    }

    .image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .gallery-item:hover .image-overlay {
      opacity: 1;
    }

    .image-overlay p {
      margin-bottom: 10px;
      text-align: center;
    }

    .btn-delete {
      color: white;
      text-decoration: none;
      font-size: 1.5rem;
    }

    /* Common Button Styles */
    .btn {
      display: inline-block;
      padding: 10px 20px;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      text-align: center;
    }

    .logout-btn .btn {
      background: rgba(255, 255, 255, 0.1);
      width: 100%;
    }

    .logout-btn .btn:hover {
      background: rgba(255, 255, 255, 0.2);
      width: 100%;
    }

    /* Table Action Button Styles */
    .actions {
      white-space: nowrap;
      display: flex;
      gap: 5px;
    }

    .btn-success,
    .btn-danger {
      color: white;
      border: none;
      padding: 8px 10px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s ease;
    }

    .btn-success {
      background-color: #4CAF50;
    }

    .btn-success:hover {
      background-color: #45a049;
    }

    .btn-danger {
      background-color: #f44336;
    }

    .btn-danger:hover {
      background-color: #da190b;
    }

    .btn-print {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      margin-left: 10px;
      white-space: nowrap;
    }

    @media (max-width: 480px) {
      .actions {
        flex-direction: column;
        gap: 8px;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }
    }

    .logout-btn {
      position: absolute;
      bottom: 20px;
      left: 20px;
      right: 20px;
    }
  </style>
</head>

<body>
  <div class="admin-container">
    <nav class="sidebar">
      <div class="sidebar-header">
        <h2><i class="fas fa-car"></i> Admin Panel</h2>
        <p>Supercar Spectacles</p>
      </div>
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="index.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a href="showcase.php" class="nav-link">
            <i class="fas fa-car"></i>
            Showcase Registrations
          </a>
        </li>
        <li class="nav-item">
          <a href="news.php" class="nav-link">
            <i class="fas fa-newspaper"></i>
            News Management
          </a>
        </li>
        <li class="nav-item">
          <a href="cars.php" class="nav-link active">
            <i class="fas fa-car-side"></i>
            Car Sales
          </a>
        </li>
        <li class="nav-item">
          <a href="gallery.php" class="nav-link">
            <i class="fas fa-images"></i>
            Gallery Management
          </a>
        </li>
        <li class="nav-item">
          <a href="newsletter.php" class="nav-link">
            <i class="fas fa-envelope"></i>
            Newsletter
          </a>
        </li>
        <li class="nav-item">
          <a href="settings.php" class="nav-link">
            <i class="fas fa-cog"></i>
            Settings
          </a>
        </li>
      </ul>
      <div class="logout-btn">
        <a href="logout.php" class="btn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </nav>