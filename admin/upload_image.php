<?php

/**
 * Supercar Spectacles Admin Panel
 * Script to handle image uploads for the gallery.
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_file'])) {
  $db = new Database();
  $conn = $db->getConnection();

  // Sanitize and get new data from the form
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $category = trim($_POST['category']);
  $alt_text = trim($_POST['alt_text']);

  // Define upload directory
  $upload_dir = realpath(__DIR__ . '/../uploads/');

  // Check if the uploads directory exists and is writable
  if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
    $_SESSION['error_message'] = "Uploads directory does not exist or is not writable.";
    header('Location: gallery.php');
    exit();
  }

  $file_info = $_FILES['image_file'];
  $file_name = $file_info['name'];
  $file_tmp = $file_info['tmp_name'];
  $file_size = $file_info['size'];
  $file_error = $file_info['error'];
  $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

  // Allowed file types
  $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

  // Validation
  if ($file_error !== 0) {
    $_SESSION['error_message'] = "There was an error uploading your file.";
  } elseif (!in_array($file_ext, $allowed_ext)) {
    $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
  } elseif ($file_size > 5000000) { // 5MB limit
    $_SESSION['error_message'] = "File size is too large. Max size is 5MB.";
  } else {
    // Generate a unique filename to prevent overwriting existing files
    $new_filename = uniqid('img_', true) . '.' . $file_ext;
    $destination = $upload_dir . '/' . $new_filename;

    // Move the uploaded file
    if (move_uploaded_file($file_tmp, $destination)) {
      try {
        // Insert file info into the database
        $stmt = $conn->prepare("INSERT INTO gallery_images (title, description, image_path, alt_text, category) VALUES (:title, :description, :image_path, :alt_text, :category)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image_path', $new_filename); // Changed from filename to image_path
        $stmt->bindParam(':alt_text', $alt_text);
        $stmt->bindParam(':category', $category);
        $stmt->execute();

        $_SESSION['success_message'] = "Image uploaded and added to the gallery successfully!";
      } catch (PDOException $e) {
        // If database insertion fails, delete the uploaded file
        if (file_exists($destination)) {
          unlink($destination);
        }
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
      }
    } else {
      $_SESSION['error_message'] = "Failed to move the uploaded file.";
    }
  }
} else {
  $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the gallery page
header('Location: gallery.php');
exit();
