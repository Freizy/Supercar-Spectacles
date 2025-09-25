<?php

/**
 * Supercar Spectacles Admin Panel
 * Script to handle updating a user's role.
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// Check if the form was submitted with a user ID and new role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
  $user_id = $_POST['user_id'];
  $role = $_POST['role'];

  $db = new Database();
  $conn = $db->getConnection();

  try {
    // Prevent an admin from demoting themselves
    if ($user_id == $_SESSION['user_id'] && $role != 'admin') {
      $_SESSION['error_message'] = "You cannot demote your own account role.";
      header('Location: settings.php');
      exit();
    }

    // Prepare the SQL statement to update the user's role
    $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :id");

    // Bind the parameters
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':id', $user_id);

    // Execute the statement
    if ($stmt->execute()) {
      $_SESSION['success_message'] = "User role updated successfully!";
    } else {
      $_SESSION['error_message'] = "Failed to update user role. Please try again.";
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
