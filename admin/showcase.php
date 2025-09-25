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

  $stmt = $conn->query("SELECT COUNT(*) as approved FROM showcase_registrations WHERE status = 'approved'");
  $showcase_approved = $stmt->fetch()['approved'];

  $stmt = $conn->query("SELECT COUNT(*) as rejected FROM showcase_registrations WHERE status = 'rejected'");
  $showcase_rejected = $stmt->fetch()['rejected'];
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

    /* Statistics Grid Styles */
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

    .stat-icon.showcase {
      background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .stat-icon.news {
      background: linear-gradient(135deg, #f093fb, #f5576c);
    }

    .stat-icon.cars {
      background: linear-gradient(135deg, #4facfe, #00f2fe);
    }

    .stat-icon.inquiries {
      background: linear-gradient(135deg, #43e97b, #38f9d7);
    }

    .stat-icon.newsletter {
      background: linear-gradient(135deg, #fa709a, #fee140);
    }

    .stat-icon.gallery {
      background: linear-gradient(135deg, #a8edea, #fed6e3);
    }

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

    /* Recent Activity & Table Styles */
    .recent-activity {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }

    .recent-activity h3 {
      margin-bottom: 20px;
      color: #333;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      /* Ensures rounded corners apply to content */
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .table th,
    .table td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }

    .table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .table th {
      font-weight: 600;
    }

    .table tbody tr:last-child td {
      border-bottom: none;
    }

    .table tbody tr:hover {
      background-color: #f7f7f7;
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
      width: 100%;
    }

    /* Table Action Button Styles */
    .actions {
      white-space: nowrap;
      display: flex;
      /* Use flexbox for button alignment */
      gap: 5px;
      /* Add a small gap between buttons */
    }

    .btn-success,
    .btn-danger {
      /* Common styles for both buttons */
      color: white;
      border: none;
      padding: 8px 10px;
      /* Adjust padding to make them more compact */
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      display: inline-flex;
      /* Use inline-flex to center the icon */
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s ease;
    }

    .btn-success {
      background-color: #4CAF50;
      /* A fresh green */
    }

    .btn-success:hover {
      background-color: #45a049;
    }

    .btn-danger {
      background-color: #f44336;
      /* A clean red */
    }

    .btn-danger:hover {
      background-color: #da190b;
    }

    .btn-print {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      margin-left: 10px;
      white-space: nowrap;
    }

    /* Optional: Media query for very small screens to stack buttons vertically */
    @media (max-width: 480px) {
      .actions {
        flex-direction: column;
        /* Stack buttons vertically on small phones */
        gap: 8px;
        /* Increase gap for better spacing */
      }
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
    <!-- Sidebar -->
    <nav class="sidebar">
      <div class="sidebar-header">
        <h2><i class="fas fa-car"></i> Admin Panel</h2>
        <p>Supercar Spectacles</p>
      </div>

      <ul class="nav-menu">
        <li class="nav-item">
          <a href="index.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a href="showcase.php" class="nav-link active">
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
        <h1>Showcase Registrations</h1>
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
          <div class="stat-label">Total Showcase Registrations</div>
          <div class="stat-subtitle"><?php echo $showcase_total; ?> Car owners have registered.</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon showcase">
              <i class="fas fa-car"></i>
            </div>
            <div class="stat-number"><?php echo $showcase_pending; ?></div>
          </div>
          <div class="stat-label">Pending Showcase Registrations</div>
          <div class="stat-subtitle"><?php echo $showcase_pending; ?> Pending Registrations</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon showcase">
              <i class="fas fa-car"></i>
            </div>
            <div class="stat-number"><?php echo $showcase_approved; ?></div>
          </div>
          <div class="stat-label">Approved Showcase Registrations</div>
          <div class="stat-subtitle"><?php echo $showcase_approved; ?> Approved Registrations</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon showcase">
              <i class="fas fa-car"></i>
            </div>
            <div class="stat-number"><?php echo $showcase_rejected; ?></div>
          </div>
          <div class="stat-label">Rejected Showcase Registrations</div>
          <div class="stat-subtitle"><?php echo $showcase_rejected; ?> Rejected Registrations</div>
        </div>
      </div>

      <!-- Pending Registrations -->
      <div class="recent-activity">
        <h3>Pending showcase Registrations</h3>


        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Owner</th>
                <th>Make</th>
                <th>Model</th>
                <th>Contact</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                $stmt = $conn->query("SELECT * FROM showcase_registrations WHERE status = 'pending' ORDER BY id DESC");
                $pending_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($pending_registrations) {
                  $row_number = 1;
                  foreach ($pending_registrations as $row) {
              ?>
                    <tr>
                      <td><?php echo $row_number++; ?></td>
                      <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['car_make']); ?></td>
                      <td><?php echo htmlspecialchars($row['car_model']); ?></td>
                      <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                      <td><?php echo htmlspecialchars($row['description']); ?></td>
                      <td class="actions">
                        <a href="actions.php?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-success" title="Approve">
                          <i class="fas fa-check"></i>
                        </a>
                        <a href="actions.php?id=<?php echo $row['id']; ?>&action=reject" class="btn btn-danger" title="Reject">
                          <i class="fas fa-trash-alt"></i> </a>
                      </td>
                    </tr>
              <?php
                  }
                } else {
                  // Updated colspan to 7 to account for the new "Actions" column
                  echo '<tr><td colspan="7" style="text-align: center;">No pending registrations found.</td></tr>';
                }
              } catch (PDOException $e) {
                echo '<tr><td colspan="7" style="text-align: center;">Error fetching data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>


      <!-- Approved Registrations -->
      <div class="recent-activity">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h3>Approved showcase Registrations</h3>
          <button class="btn btn-print" onclick="printApproved()">
            <i class="fas fa-print"></i> Print Approved List
          </button>
        </div>
        <div class="table-responsive">
          <table id="approvedTable" class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Owner</th>
                <th>Make</th>
                <th>Model</th>
                <th>Contact</th>
                <th>Description</th>
                <th>Plate</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                $stmt = $conn->query("SELECT * FROM showcase_registrations WHERE status = 'approved' ORDER BY id DESC");
                $approved_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($approved_registrations) {
                  $row_number = 1;
                  foreach ($approved_registrations as $row) {
              ?>
                    <tr>
                      <td><?php echo $row_number++; ?></td>
                      <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['car_make']); ?></td>
                      <td><?php echo htmlspecialchars($row['car_model']); ?></td>
                      <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                      <td><?php echo htmlspecialchars($row['description']); ?></td>
                      <td><?php echo htmlspecialchars($row['plate_number']); ?></td>
                    </tr>
              <?php
                  }
                } else {
                  // The colspan is now 7 to match the number of columns
                  echo '<tr><td colspan="7" style="text-align: center;">No approved registrations found.</td></tr>';
                }
              } catch (PDOException $e) {
                echo '<tr><td colspan="7" style="text-align: center;">Error fetching data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>


      <!-- Rejected Registrations -->
      <div class="recent-activity">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h3>Rejected Showcase Registrations</h3>
          <button class="btn btn-print" onclick="printRejected()">
            <i class="fas fa-print"></i> Print Rejected List
          </button>
        </div>
        <div class="table-responsive">
          <table id="rejectedTable" class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Owner</th>
                <th>Make</th>
                <th>Model</th>
                <th>Contact</th>
                <th>Description</th>
                <th>Plate</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                $stmt = $conn->query("SELECT * FROM showcase_registrations WHERE status = 'rejected' ORDER BY id DESC");
                $rejected_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($rejected_registrations) {
                  $row_number = 1;
                  foreach ($rejected_registrations as $row) {
              ?>
                    <tr>
                      <td><?php echo $row_number++; ?></td>
                      <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['car_make']); ?></td>
                      <td><?php echo htmlspecialchars($row['car_model']); ?></td>
                      <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                      <td><?php echo htmlspecialchars($row['description']); ?></td>
                      <td><?php echo htmlspecialchars($row['plate_number']); ?></td>
                    </tr>
              <?php
                  }
                } else {
                  // The colspan is now 6 to match the number of columns
                  echo '<tr><td colspan="6" style="text-align: center;">No rejected registrations found.</td></tr>';
                }
              } catch (PDOException $e) {
                echo '<tr><td colspan="6" style="text-align: center;">Error fetching data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>



      </div>
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

    function printApproved() {
      const printContent = document.getElementById('approvedTable').outerHTML;
      const originalContent = document.body.innerHTML;

      // Temporarily replace the body content with the table for printing
      document.body.innerHTML = `
        <style>
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
        ${printContent}
    `;

      window.print();

      // Restore the original content after printing
      document.body.innerHTML = originalContent;
      window.location.reload(); // Reloads the page to ensure all functionality is restored
    }

    function printRejected() {
      const printContent = document.getElementById('rejectedTable').outerHTML;
      const originalContent = document.body.innerHTML;

      // Temporarily replace the body content with the table for printing
      document.body.innerHTML = `
        <style>
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
        ${printContent}
    `;

      window.print();

      // Restore the original content after printing
      document.body.innerHTML = originalContent;
      window.location.reload(); // Reloads the page to ensure all functionality is restored
    }
  </script>
</body>

</html>