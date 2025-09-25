<?php
/**
 * Supercar Spectacles Admin Panel
 * Script to handle the deletion of a gallery image.
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if an ID was passed in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No image ID specified.";
    header('Location: gallery.php');
    exit();
}

$image_id = intval($_GET['id']);
$db = new Database();
$conn = $db->getConnection();

try {
    // Begin a transaction to ensure both database and file actions succeed or fail together
    $conn->beginTransaction();

    // 1. Fetch the image_path from the database before deletion
    $stmt = $conn->prepare("SELECT image_path FROM gallery_images WHERE id = :id");
    $stmt->bindParam(':id', $image_id);
    $stmt->execute();
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        $image_path_from_db = $image['image_path'];
        $file_path = realpath(__DIR__ . '/../uploads/' . $image_path_from_db);

        // 2. Delete the record from the database
        $stmt_delete = $conn->prepare("DELETE FROM gallery_images WHERE id = :id");
        $stmt_delete->bindParam(':id', $image_id);
        $stmt_delete->execute();

        // 3. Delete the physical file from the server
        if (file_exists($file_path) && !is_dir($file_path)) {
            unlink($file_path);
        }

        // Commit the transaction
        $conn->commit();
        $_SESSION['success_message'] = "Image deleted successfully!";

    } else {
        $_SESSION['error_message'] = "Image not found.";
        $conn->rollBack(); // Rollback if image not found
    }
} catch (PDOException $e) {
    // Rollback the transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to the gallery page
header('Location: gallery.php');
exit();
?>