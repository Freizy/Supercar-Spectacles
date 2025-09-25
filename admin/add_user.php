<?php
/**
 * Supercar Spectacles Admin Panel
 * Script to handle adding a new user from the settings page.
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
    // Sanitize and validate input
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error_message'] = "All fields are required to add a new user.";
        header('Location: settings.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        header('Location: settings.php');
        exit();
    }

    // Hash the password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $db = new Database();
    $conn = $db->getConnection();

    try {
        // Prepare the SQL statement to insert the new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)");

        // Bind the parameters
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':role', $role);

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User '{$username}' added successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to add user. Please try again.";
        }
    } catch (PDOException $e) {
        // Check for duplicate entry error
        if ($e->getCode() == 23000) {
            $_SESSION['error_message'] = "A user with that username or email already exists.";
        } else {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }
} else {
    // If the request method is not POST, redirect back
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the settings page
header('Location: settings.php');
exit();
?>