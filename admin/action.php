<?php

session_start();
require_once '../config/database.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Ensure both ID and action are present in the URL
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: showcase.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$registration_id = $_GET['id'];
$action = $_GET['action'];

// Validate the action to prevent SQL injection or unwanted updates
if ($action === 'approve' || $action === 'reject') {
    try {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $sql = "UPDATE showcase_registrations SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $registration_id);
        
        $stmt->execute();
    } catch (PDOException $e) {
        // Log the error but don't show it to the user for security reasons
        // You could redirect with an error message here
        error_log("Database error in actions.php: " . $e->getMessage());
    }
}

// Redirect back to the showcase registrations page
header('Location: showcase.php');
exit();

?>