<?php

/**
 * Supercar Spectacles Admin Panel
 * News article management interface.
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

// --- Fetch all news articles from the database ---
$news_articles = [];
$error_message = '';
try {
  // UPDATED QUERY: Selecting necessary fields for the main table view
  $stmt = $conn->query("SELECT id, title, slug, category, status, published_at, created_at, featured FROM news_articles ORDER BY created_at DESC");
  $news_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error_message = "Database error: " . $e->getMessage();
}

// Check for success or error messages from other scripts (like process_news.php)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$session_error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$error_message = $error_message ?: $session_error_message; // Prioritize DB error if present, otherwise use session error

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
  <title>Admin Panel - News Management</title>
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

    /* Table Styles (Added) */
    .news-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .news-table thead tr {
      background-color: #f8f8f8;
      border-bottom: 2px solid #ddd;
    }

    .news-table th,
    .news-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
    }

    .news-table th {
      font-weight: 600;
      color: #555;
    }

    .news-table tr:hover {
      background-color: #f9f9f9;
    }

    .news-table td:last-child {
      width: 100px;
    }

    /* Action column width */

    /* Status Badges (Added) */
    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-weight: 600;
      font-size: 0.75rem;
      display: inline-block;
    }

    .status-published {
      background-color: #4CAF50;
      color: white;
    }

    .status-draft {
      background-color: #ff9800;
      color: white;
    }

    .status-archived {
      background-color: #9e9e9e;
      color: white;
    }

    .status-featured {
      background-color: #FFD700;
      color: #333;
    }


    /* Action Buttons (Updated) */
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
    .form-group input[type="datetime-local"] {
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

    /* Alert Messages (Added) */
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

    /* Modal Styles (Added) */
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

      .news-table,
      .news-table tbody,
      .news-table tr,
      .news-table td {
        display: block;
        width: 100%;
      }

      .news-table thead {
        display: none;
      }

      .news-table tr {
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
      }

      .news-table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
      }

      .news-table td::before {
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
    <!-- Sidebar Navigation -->
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
          <a href="news.php" class="nav-link active">
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

    <!-- Main Content Area -->
    <main class="main-content">
      <div class="header">
        <h1><i class="fas fa-newspaper"></i> News Management</h1>
        <p>Manage and publish news articles for your website using the new, robust content structure.</p>
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
          News Articles
          <div class="action-buttons">
            <button id="addNewsBtn" class="btn-primary">
              <i class="fas fa-plus"></i> Add New Article
            </button>
          </div>
        </h2>

        <?php if (count($news_articles) > 0): ?>
          <table class="news-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th style="width: 100px;">Category</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 150px;">Published Date</th>
                <th style="width: 80px;">Featured</th>
                <th style="width: 100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($news_articles as $article): ?>
                <tr>
                  <td data-label="ID"><?php echo htmlspecialchars($article['id']); ?></td>
                  <td data-label="Title"><?php echo htmlspecialchars($article['title']); ?></td>
                  <td data-label="Category"><?php echo htmlspecialchars(ucfirst($article['category'])); ?></td>
                  <td data-label="Status">
                    <span class="status-badge status-<?php echo htmlspecialchars($article['status']); ?>">
                      <?php echo htmlspecialchars(ucfirst($article['status'])); ?>
                    </span>
                  </td>
                  <td data-label="Published Date"><?php echo $article['published_at'] ? date('Y-m-d H:i', strtotime($article['published_at'])) : 'Draft/Scheduled'; ?></td>
                  <td data-label="Featured Status"><?php echo is_featured($article['featured']); ?></td>
                  <td class="table-actions" data-label="Actions">
                    <button class="edit-btn" data-id="<?php echo htmlspecialchars($article['id']); ?>" title="Edit Article">
                      <i class="fas fa-edit"></i>
                    </button>
                    <!-- Removed the confirm() call as per canvas guidelines -->
                    <a href="process_news.php?action=delete&id=<?php echo htmlspecialchars($article['id']); ?>" class="text-danger" title="Delete Article">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p>No news articles found. Add a new article to get started.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Add News Modal -->
  <div id="addNewsModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" title="Close">&times;</span>
      <h2><i class="fas fa-plus-circle"></i> Add New Article</h2>
      <form id="addNewsForm" action="process_news.php" method="POST">
        <input type="hidden" name="action" value="add">

        <div class="form-row">
          <div class="form-group">
            <label for="add-title">Title <span style="color:red">*</span></label>
            <input type="text" id="add-title" name="title" required>
          </div>
          <div class="form-group">
            <label for="add-slug">Slug (URL-friendly text)</label>
            <input type="text" id="add-slug" name="slug" placeholder="e.g., new-ferrari-launch">
          </div>
        </div>

        <div class="form-group">
          <label for="add-excerpt">Excerpt (Short Summary)</label>
          <textarea id="add-excerpt" name="excerpt" rows="3" placeholder="A brief summary for listings..."></textarea>
        </div>

        <div class="form-group">
          <label for="add-content">Content (HTML allowed) <span style="color:red">*</span></label>
          <textarea id="add-content" name="content" rows="15" required placeholder="Write your full article content here..."></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="add-featured-image">Featured Image URL</label>
            <input type="text" id="add-featured-image" name="featured_image" placeholder="https://example.com/image.jpg">
          </div>
          <div class="form-group">
            <label for="add-category">Category <span style="color:red">*</span></label>
            <select id="add-category" name="category" required>
              <option value="general">General</option>
              <option value="event">Event</option>
              <option value="supercars">Supercars</option>
              <option value="technology">Technology</option>
              <option value="lifestyle">Lifestyle</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="add-status">Status <span style="color:red">*</span></label>
            <select id="add-status" name="status" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div class="form-group">
            <label for="add-published-at">Publish Date (Optional for scheduling)</label>
            <input type="datetime-local" id="add-published-at" name="published_at">
          </div>
        </div>

        <div class="form-group checkbox-group">
          <input type="checkbox" id="add-featured" name="featured" value="1">
          <label for="add-featured" style="margin-bottom: 0;">Mark as Featured Article (Will appear on homepage/top spots)</label>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 20px;">
          <i class="fas fa-plus"></i> Add Article
        </button>
      </form>
    </div>
  </div>

  <!-- Edit News Modal -->
  <div id="editNewsModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" title="Close">&times;</span>
      <h2><i class="fas fa-edit"></i> Edit Article</h2>
      <form id="editNewsForm" action="process_news.php" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">

        <div class="form-row">
          <div class="form-group">
            <label for="edit-title">Title <span style="color:red">*</span></label>
            <input type="text" id="edit-title" name="title" required>
          </div>
          <div class="form-group">
            <label for="edit-slug">Slug (URL-friendly text)</label>
            <input type="text" id="edit-slug" name="slug">
          </div>
        </div>

        <div class="form-group">
          <label for="edit-excerpt">Excerpt (Short Summary)</label>
          <textarea id="edit-excerpt" name="excerpt" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label for="edit-content">Content (HTML allowed) <span style="color:red">*</span></label>
          <textarea id="edit-content" name="content" rows="15" required></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit-featured-image">Featured Image URL</label>
            <input type="text" id="edit-featured-image" name="featured_image">
          </div>
          <div class="form-group">
            <label for="edit-category">Category <span style="color:red">*</span></label>
            <select id="edit-category" name="category" required>
              <option value="general">General</option>
              <option value="event">Event</option>
              <option value="supercars">Supercars</option>
              <option value="technology">Technology</option>
              <option value="lifestyle">Lifestyle</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="edit-status">Status <span style="color:red">*</span></label>
            <select id="edit-status" name="status" required>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div class="form-group">
            <label for="edit-published-at">Publish Date (Optional for scheduling)</label>
            <input type="datetime-local" id="edit-published-at" name="published_at">
          </div>
        </div>

        <div class="form-group checkbox-group">
          <input type="checkbox" id="edit-featured" name="featured" value="1">
          <label for="edit-featured" style="margin-bottom: 0;">Mark as Featured Article (Will appear on homepage/top spots)</label>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 20px;">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>
  </div>

  <script>
    // Get the modals and buttons
    var addModal = document.getElementById("addNewsModal");
    var editModal = document.getElementById("editNewsModal");
    var addBtn = document.getElementById("addNewsBtn");
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

    // Utility function to convert MySQL DATETIME to HTML datetime-local format
    function toDateTimeLocal(mysqlDateTime) {
      if (!mysqlDateTime) return '';
      // Handle both T-separated and space-separated formats from MySQL
      let cleanDateTime = mysqlDateTime.replace(' ', 'T');
      // Remove seconds part if present for datetime-local input
      if (cleanDateTime.lastIndexOf(':') > cleanDateTime.lastIndexOf('T')) {
        cleanDateTime = cleanDateTime.substring(0, cleanDateTime.lastIndexOf(':'));
      }
      return cleanDateTime;
    }

    // Handle Edit button clicks to open the edit modal and fetch content
    editBtns.forEach(function(btn) {
      btn.onclick = function() {
        var id = this.getAttribute('data-id');

        // Clear the form fields first
        document.getElementById('editNewsForm').reset();

        // Set the ID
        document.getElementById('edit-id').value = id;

        // Make an AJAX call to fetch the full article data
        // The process_news.php will handle the 'fetch_full_article' action
        fetch(`process_news.php?action=fetch_full_article&id=${id}`)
          .then(response => {
            // Check if the response is actually JSON before parsing
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
              return response.json();
            } else {
              throw new Error("Received non-JSON response from server.");
            }
          })
          .then(data => {
            if (data.success && data.article) {
              const article = data.article;

              // Populate all form fields
              document.getElementById('edit-title').value = article.title;
              document.getElementById('edit-slug').value = article.slug;
              document.getElementById('edit-excerpt').value = article.excerpt || ''; // handle null
              document.getElementById('edit-content').value = article.content;
              document.getElementById('edit-featured-image').value = article.featured_image || ''; // handle null
              document.getElementById('edit-category').value = article.category;
              document.getElementById('edit-status').value = article.status;

              // Convert and set the published_at date
              document.getElementById('edit-published-at').value = toDateTimeLocal(article.published_at);

              // Set the featured checkbox
              document.getElementById('edit-featured').checked = article.featured === 1;

              openModal(editModal);
            } else {
              // Using a custom alert since browser alerts are disabled
              console.error("Error fetching article data:", data.message || 'Unknown error.');
              alert("Error: Could not load article data. Check console for details.");
            }
          })
          .catch(error => {
            console.error('Fetch Error:', error);
            // Using a custom alert since browser alerts are disabled
            alert("An error occurred while communicating with the server.");
          });
      }
    });
  </script>
</body>

</html>