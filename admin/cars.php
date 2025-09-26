<?php
/**
 * Supercar Spectacles Admin Panel
 * Car sales management interface.
 */

session_start();
// NOTE: Assuming this path is correct and defines a Database class.
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// --- Fetch all cars from the database ---
$cars_for_sale = [];
$error_message = '';
try {
    // UPDATED QUERY: Selecting necessary fields for the main car table view
    $stmt = $conn->query("SELECT id, make, model, year, price, status, featured FROM car_listings ORDER BY created_at DESC");
    $cars_for_sale = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Fetch Dashboard Stats for the cars page ---
    $total_inventory = $conn->query("SELECT COUNT(*) FROM car_listings")->fetchColumn();
    $total_available = $conn->query("SELECT COUNT(*) FROM car_listings WHERE status = 'available'")->fetchColumn();
    $total_sold = $conn->query("SELECT COUNT(*) FROM car_listings WHERE status = 'sold'")->fetchColumn();
    $total_pending = $conn->query("SELECT COUNT(*) FROM car_listings WHERE status IN ('pending', 'withdrawn')")->fetchColumn();
    $total_value_query = $conn->query("SELECT SUM(price) FROM car_listings WHERE status = 'available'");
    $total_inventory_value = $total_value_query->fetchColumn() ?? 0;

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Check for success or error messages from the processing script (like process_cars.php)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$session_error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$error_message = $error_message ?: $session_error_message; // Prioritize DB error if present

unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Function to safely check and display the featured status
function is_featured($value)
{
    return $value == 1 ? '<span class="status-featured"><i class="fas fa-star"></i> Featured</span>' : 'No';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Car Sales</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Base Styles */
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

        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }

        .logout-btn .btn {
            background: rgba(255, 255, 255, 0.1);
            width: 100%;
        }

        .logout-btn .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            width: 100%;
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

        /* Stats Grid (New) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        
        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.available { background: linear-gradient(135deg, #4caf50, #8bc34a); }
        .stat-icon.sold { background: linear-gradient(135deg, #ff9800, #ff5722); }
        .stat-icon.value { background: linear-gradient(135deg, #00bfff, #0080ff); }
        
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

        /* Content Section */
        .content-section {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .content-section h2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        /* Table Styles */
        .cars-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .cars-table thead tr {
            background-color: #f8f8f8;
            border-bottom: 2px solid #ddd;
        }

        .cars-table th,
        .cars-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .cars-table th {
            font-weight: 600;
            color: #555;
        }

        .cars-table tr:hover {
            background-color: #f9f9f9;
        }

        .cars-table td:last-child {
            width: 100px;
        }

        /* Action column width */

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
            display: inline-block;
        }

        .status-available {
            background-color: #4CAF50;
            color: white;
        }

        .status-sold {
            background-color: #f44336;
            color: white;
        }

        .status-pending {
            background-color: #ff9800;
            color: white;
        }
        
        .status-withdrawn {
            background-color: #9e9e9e;
            color: white;
        }

        .status-featured {
            background-color: #FFD700;
            color: #333;
        }


        /* Action Buttons */
        .action-buttons .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: opacity 0.3s ease;
        }

        .action-buttons .btn-primary:hover {
            opacity: 0.9;
        }

        .table-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-actions button,
        .table-actions a {
            padding: 8px;
            border-radius: 5px;
            font-size: 1rem;
            line-height: 1;
            transition: all 0.2s ease;
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

        .edit-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            cursor: pointer;
        }

        .edit-btn:hover {
            background-color: #0b7dda;
        }

        .text-danger {
            background-color: #f44336;
            color: white;
            padding: 8px;
            text-decoration: none;
        }

        .text-danger:hover {
            background-color: #da190b;
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
        .form-group textarea,
        .form-group select,
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            padding-top: 50px;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 800px;
            position: relative;
            animation-name: animatetop;
            animation-duration: 0.4s
        }

        /* Add Animation */
        @keyframes animatetop {
            from {
                top: -300px;
                opacity: 0
            }

            to {
                top: 0;
                opacity: 1
            }
        }

        .modal-content h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 15px;
            right: 25px;
            cursor: pointer;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
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

            .cars-table,
            .cars-table tbody,
            .cars-table tr,
            .cars-table td {
                display: block;
                width: 100%;
            }

            .cars-table thead {
                display: none;
            }

            .cars-table tr {
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            }

            .cars-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .cars-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 50%;
                padding-left: 15px;
                font-weight: bold;
                text-align: left;
                color: #555;
            }
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
                    <a href="cars.php" class="nav-link active">
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
                    <a href="settings.php" class="nav-link">
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
                <h1><i class="fas fa-car-side"></i> Car Sales Management</h1>
                <p>Manage and publish car listings for sale.</p>
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

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon total">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_inventory); ?></div>
                    </div>
                    <div class="stat-label">Total Inventory</div>
                    <div class="stat-subtitle">All car listings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon available">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_available); ?></div>
                    </div>
                    <div class="stat-label">Available Cars</div>
                    <div class="stat-subtitle">Ready for sale</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon sold">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($total_sold); ?></div>
                    </div>
                    <div class="stat-label">Cars Sold</div>
                    <div class="stat-subtitle">Recent sales</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon value">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-number">$<?php echo number_format($total_inventory_value, 2, '.', ','); ?></div>
                    </div>
                    <div class="stat-label">Inventory Value</div>
                    <div class="stat-subtitle">Based on available cars</div>
                </div>
            </div>

            <div class="content-section">
                <h2>
                    Car Listings
                    <div class="action-buttons">
                        <button id="addCarBtn" class="btn-primary">
                            <i class="fas fa-plus"></i> Add New Car
                        </button>
                    </div>
                </h2>

                <?php if (count($cars_for_sale) > 0): ?>
                    <table class="cars-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Make</th>
                                <th>Model</th>
                                <th>Year</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cars_for_sale as $car): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($car['id']); ?></td>
                                    <td data-label="Make"><?php echo htmlspecialchars($car['make']); ?></td>
                                    <td data-label="Model"><?php echo htmlspecialchars($car['model']); ?></td>
                                    <td data-label="Year"><?php echo htmlspecialchars($car['year']); ?></td>
                                    <td data-label="Price">$<?php echo number_format(htmlspecialchars($car['price']), 0, '.', ','); ?></td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?php echo htmlspecialchars($car['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($car['status'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="Featured Status"><?php echo is_featured($car['featured']); ?></td>
                                    <td class="table-actions" data-label="Actions">
                                        <button class="edit-btn" data-id="<?php echo htmlspecialchars($car['id']); ?>" title="Edit Car">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="process_cars.php?action=delete&id=<?php echo htmlspecialchars($car['id']); ?>" class="text-danger" title="Delete Car">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No cars found. Add a new car listing to get started.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="addCarModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" title="Close">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add New Car</h2>
            <form id="addCarForm" action="process_cars.php" method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-row">
                    <div class="form-group">
                        <label for="add-make">Make <span style="color:red">*</span></label>
                        <input type="text" id="add-make" name="make" required>
                    </div>
                    <div class="form-group">
                        <label for="add-model">Model <span style="color:red">*</span></label>
                        <input type="text" id="add-model" name="model" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="add-year">Year</label>
                        <input type="number" id="add-year" name="year" min="1900" max="2100">
                    </div>
                    <div class="form-group">
                        <label for="add-price">Price ($) <span style="color:red">*</span></label>
                        <input type="number" id="add-price" name="price" required step="1">
                    </div>
                    <div class="form-group">
                        <label for="add-mileage">Mileage (miles)</label>
                        <input type="number" id="add-mileage" name="mileage" step="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="add-color">Color</label>
                        <input type="text" id="add-color" name="color">
                    </div>
                    <div class="form-group">
                        <label for="add-transmission">Transmission</label>
                        <select id="add-transmission" name="transmission">
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual</option>
                            <option value="semi-automatic">Semi-Automatic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add-fuel-type">Fuel Type</label>
                        <select id="add-fuel-type" name="fuel_type">
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="add-power">Power (hp)</label>
                        <input type="number" id="add-power" name="power_hp" step="1">
                    </div>
                    <div class="form-group">
                        <label for="add-acceleration">0-60 mph (sec)</label>
                        <input type="number" id="add-acceleration" name="acceleration_0_60" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="add-top-speed">Top Speed (mph)</label>
                        <input type="number" id="add-top-speed" name="top_speed" step="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="add-description">Description</label>
                    <textarea id="add-description" name="description" rows="10" placeholder="Enter a detailed description of the car..."></textarea>
                </div>
                <div class="form-group">
                    <label for="add-main-image">Main Image URL</label>
                    <input type="text" id="add-main-image" name="main_image" placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="add-status">Status <span style="color:red">*</span></label>
                        <select id="add-status" name="status" required>
                            <option value="available">Available</option>
                            <option value="pending">Pending</option>
                            <option value="sold">Sold</option>
                            <option value="withdrawn">Withdrawn</option>
                        </select>
                    </div>
                    <div class="form-group checkbox-group" style="align-self: flex-end;">
                        <input type="checkbox" id="add-featured" name="featured" value="1">
                        <label for="add-featured" style="margin-bottom: 0;">Mark as Featured Car</label>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Add Car
                </button>
            </form>
        </div>
    </div>

    <div id="editCarModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" title="Close">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Car</h2>
            <form id="editCarForm" action="process_cars.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-make">Make <span style="color:red">*</span></label>
                        <input type="text" id="edit-make" name="make" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-model">Model <span style="color:red">*</span></label>
                        <input type="text" id="edit-model" name="model" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-year">Year</label>
                        <input type="number" id="edit-year" name="year" min="1900" max="2100">
                    </div>
                    <div class="form-group">
                        <label for="edit-price">Price ($) <span style="color:red">*</span></label>
                        <input type="number" id="edit-price" name="price" required step="1">
                    </div>
                    <div class="form-group">
                        <label for="edit-mileage">Mileage (miles)</label>
                        <input type="number" id="edit-mileage" name="mileage" step="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-color">Color</label>
                        <input type="text" id="edit-color" name="color">
                    </div>
                    <div class="form-group">
                        <label for="edit-transmission">Transmission</label>
                        <select id="edit-transmission" name="transmission">
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual</option>
                            <option value="semi-automatic">Semi-Automatic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-fuel-type">Fuel Type</label>
                        <select id="edit-fuel-type" name="fuel_type">
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-power">Power (hp)</label>
                        <input type="number" id="edit-power" name="power_hp" step="1">
                    </div>
                    <div class="form-group">
                        <label for="edit-acceleration">0-60 mph (sec)</label>
                        <input type="number" id="edit-acceleration" name="acceleration_0_60" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="edit-top-speed">Top Speed (mph)</label>
                        <input type="number" id="edit-top-speed" name="top_speed" step="1">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit-description">Description</label>
                    <textarea id="edit-description" name="description" rows="10"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit-main-image">Main Image URL</label>
                    <input type="text" id="edit-main-image" name="main_image">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-status">Status <span style="color:red">*</span></label>
                        <select id="edit-status" name="status" required>
                            <option value="available">Available</option>
                            <option value="pending">Pending</option>
                            <option value="sold">Sold</option>
                            <option value="withdrawn">Withdrawn</option>
                        </select>
                    </div>
                    <div class="form-group checkbox-group" style="align-self: flex-end;">
                        <input type="checkbox" id="edit-featured" name="featured" value="1">
                        <label for="edit-featured" style="margin-bottom: 0;">Mark as Featured Car</label>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        // Get the modals and buttons
        var addModal = document.getElementById("addCarModal");
        var editModal = document.getElementById("editCarModal");
        var addBtn = document.getElementById("addCarBtn");
        var closeBtns = document.querySelectorAll(".close-btn");
        var editBtns = document.querySelectorAll(".edit-btn");

        // Function to open a modal (updated to use flex for better centering)
        function openModal(modal) {
            modal.style.display = "flex";
        }

        // Function to close a modal
        function closeModal(modal) {
            modal.style.display = "none";
        }

        // Open add modal
        addBtn.onclick = function() {
            openModal(addModal);
        }

        // Close modals with the close button
        closeBtns.forEach(function(btn) {
            btn.onclick = function() {
                closeModal(addModal);
                closeModal(editModal);
            }
        });

        // Close modals when clicking outside of them
        window.onclick = function(event) {
            if (event.target == addModal) {
                closeModal(addModal);
            }
            if (event.target == editModal) {
                closeModal(editModal);
            }
        }

        // Handle Edit button clicks to open the edit modal and fetch content
        editBtns.forEach(function(btn) {
            btn.onclick = function() {
                var id = this.getAttribute('data-id');

                // Clear the form fields first
                document.getElementById('editCarForm').reset();

                // Set the ID
                document.getElementById('edit-id').value = id;

                // Make an AJAX call to fetch the full car data
                // The process_cars.php will handle the 'fetch_full_car' action
                fetch(`process_cars.php?action=fetch_full_car&id=${id}`)
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            throw new Error("Received non-JSON response from server.");
                        }
                    })
                    .then(data => {
                        if (data.success && data.car) {
                            const car = data.car;

                            // Populate all form fields
                            document.getElementById('edit-make').value = car.make;
                            document.getElementById('edit-model').value = car.model;
                            document.getElementById('edit-year').value = car.year || '';
                            document.getElementById('edit-price').value = car.price;
                            document.getElementById('edit-mileage').value = car.mileage || '';
                            document.getElementById('edit-color').value = car.color || '';
                            document.getElementById('edit-transmission').value = car.transmission;
                            document.getElementById('edit-fuel-type').value = car.fuel_type;
                            document.getElementById('edit-power').value = car.power_hp || '';
                            document.getElementById('edit-acceleration').value = car.acceleration_0_60 || '';
                            document.getElementById('edit-top-speed').value = car.top_speed || '';
                            document.getElementById('edit-description').value = car.description || '';
                            document.getElementById('edit-main-image').value = car.main_image || '';
                            document.getElementById('edit-status').value = car.status;

                            // Set the featured checkbox
                            document.getElementById('edit-featured').checked = car.featured === 1;

                            openModal(editModal);
                        } else {
                            console.error("Error fetching car data:", data.message || 'Unknown error.');
                            alert("Error: Could not load car data. Check console for details.");
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        alert("An error occurred while communicating with the server.");
                    });
            }
        });
    </script>
</body>

</html>