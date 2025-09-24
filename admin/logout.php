<?php
/**
 * Admin Logout
 * Handles admin logout and session cleanup
 */

session_start();
require_once '../config/database.php';

// Log logout activity if user is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        try {
            $log_stmt = $conn->prepare("
                INSERT INTO admin_logs (user_id, action, ip_address, user_agent) 
                VALUES (?, 'logout', ?, ?)
            ");
            $log_stmt->execute([
                $_SESSION['admin_id'] ?? 0,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // Log table doesn't exist yet, that's okay
        }
    } catch (Exception $e) {
        // Log error but don't prevent logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit();
?>
