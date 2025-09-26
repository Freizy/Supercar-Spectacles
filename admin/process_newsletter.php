<?php

/**
 * Supercar Spectacles Admin Panel
 * Script to handle sending a newsletter to all subscribers.
 */

session_start();
require_once '../config/database.php';
require '../vendor/autoload.php'; // Path to Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    // Sanitize input
    $subject = htmlspecialchars(trim($_POST['subject']));
    $body = $_POST['body']; // HTML content, no sanitization here

    if (empty($subject) || empty($body)) {
        $_SESSION['error_message'] = "Subject and body are required.";
        header('Location: newsletter.php');
        exit();
    }

    try {
        // --- Fetch all ACTIVE newsletter subscribers from the correct table ---
        // The table name is 'newsletter_subscriptions', and we only select 'active' statuses.
        $stmt = $conn->query("SELECT email FROM newsletter_subscriptions WHERE status = 'active'");
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($subscribers) === 0) {
            $_SESSION['error_message'] = "No active subscribers to send the newsletter to.";
            header('Location: newsletter.php');
            exit();
        }

        // --- Instantiate PHPMailer ---
        $mail = new PHPMailer(true); // `true` enables exceptions

        // --- Configure SMTP settings (Recommended) ---
        // You MUST replace these with your actual SMTP server details
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_email_password'; // Or App Password for services like Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Set email content and sender
        $mail->setFrom('no-reply@supercarspectacles.com', 'Supercar Spectacles Newsletter');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // Plain-text alternative

        $sent_count = 0;
        $error_emails = [];

        // --- Loop through each subscriber and send the email ---
        foreach ($subscribers as $subscriber) {
            $email = $subscriber['email'];
            $mail->clearAllRecipients(); // Clear previous recipients
            $mail->addAddress($email);

            try {
                $mail->send();
                $sent_count++;
            } catch (Exception $e) {
                $error_emails[] = $email;
                // Log the error for this specific email
                error_log("Failed to send newsletter to {$email}: {$e->getMessage()}");
            }
        }

        if (count($error_emails) > 0) {
            $_SESSION['error_message'] = "Newsletter sent to {$sent_count} subscribers. Failed to send to " . count($error_emails) . " recipients.";
        } else {
            $_SESSION['success_message'] = "Newsletter sent successfully to all {$sent_count} subscribers!";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Failed to send newsletter: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to the newsletter page
header('Location: newsletter.php');
exit();