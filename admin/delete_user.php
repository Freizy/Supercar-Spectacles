<?php

/**
 * Supercar Spectacles Admin Panel
 * Script to handle deleting a user.
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// Check if a user ID was passed in the URL
if (isset($_GET['id'])) {
  $user_id = $_GET['id'];

  // Prevent an admin from deleting their own account
  if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own account.";
    header('Location: settings.php');
    exit();
  }

  $db = new Database();
  $conn = $db->getConnection();

  try {
    // Prepare the SQL statement to delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");

    // Bind the parameter
    $stmt->bindParam(':id', $user_id);

    // Execute the statement
    if ($stmt->execute()) {
      $_SESSION['success_message'] = "User deleted successfully!";
    } else {
      $_SESSION['error_message'] = "Failed to delete user. Please try again.";
    }
  } catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the settings page
header('Location: settings.php');
exit();
