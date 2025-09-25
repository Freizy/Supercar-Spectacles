<?php

/**
 * Supercar Spectacles Admin Panel
 * Comprehensive settings management interface
 */

session_start();
require_once '../config/database.php';

// Simple authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// --- Fetch all settings from the database ---
$settings = [];
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM site_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($db_settings as $setting) {
        $settings[$setting['setting_key']] = htmlspecialchars($setting['setting_value']);
    }

    // --- Fetch all users for management ---
    $users_stmt = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Supercar Spectacles</title>
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

        /* Common Button Styles */
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
            margin-top: 10px;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        /* Settings Page Specific Styles */
        .settings-container {
            display: grid;
            gap: 30px;
        }

        .settings-section {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .settings-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .settings-section form {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group select,
        .form-group input[type="password"] {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        /* User Table Styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th,
        .user-table td {
            padding: 12px;
            border: 1px solid #eee;
            text-align: left;
        }

        .user-actions a {
            margin-right: 5px;
            font-size: 1.2em;
            color: #667eea;
        }

        /* Modal (Pop-up) Styles */
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
            margin: 10% auto;
            /* 10% from the top and centered */
            padding: 25px;
            border: 1px solid #888;
            width: 80%;
            /* Could be more or less, depending on screen size */
            max-width: 500px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
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
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="showcase.php" class="nav-link">
                        <i class="fas fa-car"></i> Showcase Registrations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="news.php" class="nav-link">
                        <i class="fas fa-newspaper"></i> News Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cars.php" class="nav-link">
                        <i class="fas fa-car-side"></i> Car Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="gallery.php" class="nav-link">
                        <i class="fas fa-images"></i> Gallery Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="newsletter.php" class="nav-link">
                        <i class="fas fa-envelope"></i> Newsletter
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link active">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>

            <div class="logout-btn">
                <a href="logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-cog"></i> Site Settings</h1>
            </div>

            <?php if (isset($error_message)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <div class="settings-section">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <p>Manage user accounts, roles, and permissions.</p>

                    <button class="btn-submit" onclick="document.getElementById('addUserModal').style.display='block'">
                        <i class="fas fa-plus"></i> Add New User
                    </button>

                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <form action="update_user.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" onchange="this.form.submit()">
                                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                <option value="moderator" <?php echo ($user['role'] == 'moderator') ? 'selected' : ''; ?>>Moderator</option>
                                                <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="user-actions">
                                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');" title="Delete User">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="addUserModal" class="modal">
                    <div class="modal-content">
                        <span class="close-btn" onclick="document.getElementById('addUserModal').style.display='none'">&times;</span>
                        <h3>Add New User</h3>
                        <form action="add_user.php" method="POST">
                            <div class="form-group">
                                <label for="new_username">Username</label>
                                <input type="text" id="new_username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="new_email">Email</label>
                                <input type="email" id="new_email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">Password</label>
                                <input type="password" id="new_password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_role">Role</label>
                                <select id="new_role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="moderator">Moderator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-submit">Add User</button>
                        </form>
                    </div>
                </div>

                <div class="settings-section">
                    <h2><i class="fas fa-globe"></i> Site-Wide Settings</h2>
                    <form action="update_site_settings.php" method="POST">
                        <div class="form-group">
                            <label for="site_title">Site Title</label>
                            <input type="text" id="site_title" name="site_title" value="<?php echo $settings['site_title']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_email">Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" value="<?php echo $settings['contact_email']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="social_instagram">Instagram URL</label>
                            <input type="text" id="social_instagram" name="social_instagram" value="<?php echo $settings['social_instagram']; ?>">
                        </div>
                        <button type="submit" class="btn-submit">Save Site Settings</button>
                    </form>
                </div>

                <div class="settings-section">
                    <h2><i class="fas fa-cogs"></i> Feature-Specific Settings</h2>
                    <form action="update_feature_settings.php" method="POST">
                        <div class="form-group">
                            <label for="news_items_per_page">News Items Per Page</label>
                            <input type="number" id="news_items_per_page" name="news_items_per_page" value="<?php echo $settings['news_items_per_page']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="car_listings_per_page">Car Listings Per Page</label>
                            <input type="number" id="car_listings_per_page" name="car_listings_per_page" value="<?php echo $settings['car_listings_per_page']; ?>">
                        </div>
                        <button type="submit" class="btn-submit">Save Feature Settings</button>
                    </form>
                </div>

                <div class="settings-section">
                    <h2><i class="fas fa-tools"></i> System & Maintenance</h2>
                    <p>Execute system tasks and perform backups.</p>
                    <div class="maintenance-buttons">
                        <a href="backup_database.php" class="btn btn-submit" style="background-color: #f44336; padding: 10px 20px;">
                            <i class="fas fa-database"></i> Backup Database
                        </a>
                        <a href="clear_cache.php" class="btn btn-submit" style="background-color: #2196F3; padding: 10px 20px;">
                            <i class="fas fa-broom"></i> Clear Cache
                        </a>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>

</html>