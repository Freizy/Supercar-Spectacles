<?php
/**
 * Supercar Spectacles Admin Panel
 * Main dashboard and management interface
 */

session_start();
require_once '../config/database.php';

// Simple authentication check (implement proper auth system)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get dashboard statistics
try {
    // Showcase registrations
    $stmt = $conn->query("SELECT COUNT(*) as total FROM showcase_registrations");
    $showcase_total = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as pending FROM showcase_registrations WHERE status = 'pending'");
    $showcase_pending = $stmt->fetch()['pending'];

    // News articles
    $stmt = $conn->query("SELECT COUNT(*) as total FROM news_articles");
    $news_total = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as published FROM news_articles WHERE status = 'published'");
    $news_published = $stmt->fetch()['published'];

    // Car listings
    $stmt = $conn->query("SELECT COUNT(*) as total FROM car_listings WHERE status = 'available'");
    $cars_total = $stmt->fetch()['total'];

    // Car inquiries
    $stmt = $conn->query("SELECT COUNT(*) as total FROM car_inquiries WHERE status = 'new'");
    $inquiries_new = $stmt->fetch()['total'];

    // Newsletter subscribers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM newsletter_subscriptions WHERE status = 'active'");
    $subscribers_total = $stmt->fetch()['total'];

    // Gallery images
    $stmt = $conn->query("SELECT COUNT(*) as total FROM gallery_images WHERE status = 'active'");
    $gallery_total = $stmt->fetch()['total'];

} catch (Exception $e) {
    $error_message = "Error loading dashboard data: " . $e->getMessage();
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
            border-bottom: 1px solid rgba(255,255,255,0.1);
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

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            border-right: 3px solid #fff;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        } 

        .header p {
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.showcase { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.news { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.cars { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.inquiries { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .stat-icon.newsletter { background: linear-gradient(135deg, #fa709a, #fee140); }
        .stat-icon.gallery { background: linear-gradient(135deg, #a8edea, #fed6e3); }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .stat-subtitle {
            color: #999;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .recent-activity h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 0.9rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            color: #999;
            font-size: 0.8rem;
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
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }

        .btn:hover {
            background: rgba(255,255,255,0.2);
        }

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
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-car"></i> Admin Panel</h2>
                <p>Supercar Spectacles</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="showcase.php" class="nav-link">
                        <i class="fas fa-car"></i>
                        Showcase Registrations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="news.php" class="nav-link">
                        <i class="fas fa-newspaper"></i>
                        News Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cars.php" class="nav-link">
                        <i class="fas fa-car-side"></i>
                        Car Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="gallery.php" class="nav-link">
                        <i class="fas fa-images"></i>
                        Gallery Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="newsletter.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        Newsletter
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>

            <div class="logout-btn">
                <a href="logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <p>Welcome to the Supercar Spectacles admin panel</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon showcase">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-number"><?php echo $showcase_total; ?></div>
                    </div>
                    <div class="stat-label">Showcase Registrations</div>
                    <div class="stat-subtitle"><?php echo $showcase_pending; ?> Active Registrations</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon news">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div class="stat-number"><?php echo $news_total; ?></div>
                    </div>
                    <div class="stat-label">News Articles</div>
                    <div class="stat-subtitle"><?php echo $news_published; ?> published</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon cars">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <div class="stat-number"><?php echo $cars_total; ?></div>
                    </div>
                    <div class="stat-label">Car Listings</div>
                    <div class="stat-subtitle">Available for sale</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon inquiries">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $inquiries_new; ?></div>
                    </div>
                    <div class="stat-label">New Inquiries</div>
                    <div class="stat-subtitle">Require attention</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon newsletter">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-number"><?php echo $subscribers_total; ?></div>
                    </div>
                    <div class="stat-label">Newsletter Subscribers</div>
                    <div class="stat-subtitle">Active subscribers</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon gallery">
                            <i class="fas fa-images"></i>
                        </div>
                        <div class="stat-number"><?php echo $gallery_total; ?></div>
                    </div>
                    <div class="stat-label">Gallery Images</div>
                    <div class="stat-subtitle">Active images</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <h3>Recent Activity</h3>
                
                <div class="activity-item">
                    <div class="activity-icon" style="background: #667eea;">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">New showcase registration</div>
                        <div class="activity-time">2 hours ago</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon" style="background: #f093fb;">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">News article published</div>
                        <div class="activity-time">5 hours ago</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon" style="background: #4facfe;">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">New car inquiry received</div>
                        <div class="activity-time">1 day ago</div>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon" style="background: #43e97b;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Newsletter sent to subscribers</div>
                        <div class="activity-time">2 days ago</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Simple mobile menu toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

        // Auto-refresh dashboard data every 5 minutes
        setInterval(function() {
            // You can implement AJAX refresh here
        }, 300000);
    </script>
</body>
</html>
