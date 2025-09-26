<?php

/**
 * Supercar Spectacles
 * Script to handle newsletter subscription requests.
 */

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
  $db = new Database();
  $conn = $db->getConnection();

  // Sanitize and validate the email address
  $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

  // Check for an optional 'name' field from the form
  $name = isset($_POST['name']) ? trim($_POST['name']) : null;

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['form_message'] = "Invalid email address.";
    $_SESSION['message_type'] = "error";
    header('Location: index.php');
    exit();
  }

  try {
    // Prepare the SQL statement with ON DUPLICATE KEY UPDATE
    // This handles new subscriptions and re-activates unsubscribed users
    $stmt = $conn->prepare("
            INSERT INTO newsletter_subscriptions (email, name) 
            VALUES (:email, :name)
            ON DUPLICATE KEY UPDATE status = 'active'
        ");

    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':name', $name);
    $stmt->execute();

    $_SESSION['form_message'] = "Thank you for subscribing!";
    $_SESSION['message_type'] = "success";
  } catch (PDOException $e) {
    $_SESSION['form_message'] = "Subscription failed. Please try again later.";
    $_SESSION['message_type'] = "error";
  }
} else {
  $_SESSION['form_message'] = "Invalid request.";
  $_SESSION['message_type'] = "error";
}

header('Location: ../index.php');
exit();
