<?php

/**
 * Supercar Spectacles Admin Panel
 * Newsletter subscriber management interface.
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

// --- Fetch all newsletter subscribers from the database ---
$subscribers = [];
$error_message = '';
try {
    $stmt = $conn->query("SELECT id, email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Check for success or error messages from other scripts (e.g., download script)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Newsletter</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 10;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-right: 3px solid #fff;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
        }

        /* Table and Button Styles */
        .content-section {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .content-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-download,
        .btn-send {
            background-color: #2196F3;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-send {
            background-color: #4CAF50;
        }

        .btn-download:hover {
            background-color: #1a7bb9;
        }

        .btn-send:hover {
            background-color: #45a049;
        }

        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }

        .logout-btn .btn {
            background: rgba(255, 255, 255, 0.1);
            width: 100%;
        }

        .logout-btn .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            width: 100%;
        }


        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Modal Styles */
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 100;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            /* 5% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            /* Could be more or less, depending on screen size */
            max-width: 800px;
            border-radius: 10px;
            position: relative;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: fit-content;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        .subscriber-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .subscriber-table th,
        .subscriber-table td {
            padding: 12px;
            border: 1px solid #eee;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-car"></i> Admin Panel</h2>
                <p>Supercar Spectacles</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="showcase.php" class="nav-link"><i class="fas fa-car"></i> Showcase Registrations</a></li>
                <li class="nav-item"><a href="news.php" class="nav-link"><i class="fas fa-newspaper"></i> News Management</a></li>
                <li class="nav-item"><a href="cars.php" class="nav-link"><i class="fas fa-car-side"></i> Car Sales</a></li>
                <li class="nav-item"><a href="gallery.php" class="nav-link"><i class="fas fa-images"></i> Gallery Management</a></li>
                <li class="nav-item"><a href="newsletter.php" class="nav-link active"><i class="fas fa-envelope"></i> Newsletter</a></li>
                <li class="nav-item"><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
            <div class="logout-btn">
                <a href="logout.php" class="btn nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-envelope"></i> Newsletter Subscribers</h1>
                <p>Manage your subscriber list for marketing and updates.</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="content-section">
                <h2>
                    Subscriber List
                    <div class="action-buttons">
                        <button id="sendNewsletterBtn" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Send Newsletter
                        </button>
                        <a href="download_subscribers.php" class="btn-download">
                            <i class="fas fa-download"></i> Download CSV
                        </a>
                    </div>
                </h2>

                <?php if (count($subscribers) > 0): ?>
                    <table class="subscriber-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Subscribed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscribers as $subscriber): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subscriber['id']); ?></td>
                                    <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                    <td><?php echo htmlspecialchars($subscriber['subscribed_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No subscribers found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="newsletterModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Compose Newsletter</h2>
            <form action="process_newsletter.php" method="POST">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="body">Body (HTML allowed)</label>
                    <textarea id="body" name="body" rows="15" required></textarea>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Newsletter
                </button>
            </form>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("newsletterModal");

        // Get the button that opens the modal
        var btn = document.getElementById("sendNewsletterBtn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close-btn")[0];

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>