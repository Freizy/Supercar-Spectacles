<?php

/**
 * Supercar Spectacles Admin Panel
 * Script to handle updating general site settings.
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $db = new Database();
  $conn = $db->getConnection();

  try {
    // Sanitize and validate input
    $site_title = trim($_POST['site_title']);
    $contact_email = filter_var(trim($_POST['contact_email']), FILTER_SANITIZE_EMAIL);
    $social_instagram = trim($_POST['social_instagram']);

    // Define an array of settings to update
    $settings_to_update = [
      'site_title' => $site_title,
      'contact_email' => $contact_email,
      'social_instagram' => $social_instagram
    ];

    // Begin a transaction to ensure all updates succeed or fail together
    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE site_settings SET setting_value = :setting_value WHERE setting_key = :setting_key");

    foreach ($settings_to_update as $key => $value) {
      $stmt->bindParam(':setting_value', $value);
      $stmt->bindParam(':setting_key', $key);
      $stmt->execute();
    }

    // Commit the transaction
    $conn->commit();
    $_SESSION['success_message'] = "Site settings updated successfully!";
  } catch (PDOException $e) {
    // Rollback the transaction on error
    if ($conn->inTransaction()) {
      $conn->rollBack();
    }
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
  }
} else {
  // If the request method is not POST, redirect back
  $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the settings page
header('Location: settings.php');
exit();
