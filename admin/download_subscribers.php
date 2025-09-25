<?php
/**
 * Supercar Spectacles Admin Panel
 * Script to download newsletter subscriber emails as a CSV file.
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

// --- Set CSV headers to force a download ---
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=newsletter_subscribers_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// --- Write the CSV header row ---
fputcsv($output, array('Email', 'Subscribed At'));

try {
    // --- Fetch all subscriber data from the database ---
    $stmt = $conn->query("SELECT email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");

    // --- Loop through the rows and write each to the CSV file ---
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
} catch (PDOException $e) {
    // Log the error and set a session message
    $_SESSION['error_message'] = "Failed to generate CSV: " . $e->getMessage();
    // Redirect back to the newsletter page
    header('Location: newsletter.php');
    exit();
} finally {
    // Close the file pointer
    fclose($output);
}

// Exit the script after the CSV has been sent to the browser
exit();
?>