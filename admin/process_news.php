<?php

/**
 * Supercar Spectacles Admin Panel
 * Backend logic for managing news articles (CRUD).
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

// Helper function to generate a URL-friendly slug
function create_slug($text)
{
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  return empty($text) ? 'n-a' : $text;
}

// Determine the action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- Handle AJAX Request for Full Article Data (GET) ---
if ($action === 'fetch_full_article' && isset($_GET['id'])) {
  $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
  if ($id === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    exit();
  }

  try {
    $stmt = $conn->prepare("SELECT id, title, slug, excerpt, content, featured_image, category, status, featured, published_at FROM news_articles WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($article) {
      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'article' => $article]);
    } else {
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Article not found.']);
    }
  } catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
  }
  exit();
}

// Only proceed with POST/Redirect actions if there's an action defined
if (!in_array($action, ['add', 'edit', 'delete'])) {
  $_SESSION['error_message'] = "Invalid action specified.";
  header('Location: news.php');
  exit();
}

// --- DELETE Action (GET/URL Parameter) ---
if ($action === 'delete' && isset($_GET['id'])) {
  $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
  if ($id === false) {
    $_SESSION['error_message'] = "Invalid article ID for deletion.";
    header('Location: news.php');
    exit();
  }

  try {
    $stmt = $conn->prepare("DELETE FROM news_articles WHERE id = :id");
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
      $_SESSION['success_message'] = "Article ID {$id} successfully deleted.";
    } else {
      $_SESSION['error_message'] = "Failed to delete article ID {$id}.";
    }
  } catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error during deletion: " . $e->getMessage();
  }

  header('Location: news.php');
  exit();
}

// --- ADD and EDIT Actions (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $excerpt = trim($_POST['excerpt'] ?? null);
  $content = trim($_POST['content'] ?? '');
  $featured_image = trim($_POST['featured_image'] ?? null);
  $category = trim($_POST['category'] ?? 'general');
  $status = trim($_POST['status'] ?? 'draft');
  $published_at_raw = trim($_POST['published_at'] ?? null);
  $featured = isset($_POST['featured']) ? 1 : 0;

  // Sanitize published_at
  $published_at = null;
  if (!empty($published_at_raw)) {
    // Convert the HTML datetime-local format (YYYY-MM-DDT...) to MySQL DATETIME format
    $published_at = date('Y-m-d H:i:s', strtotime($published_at_raw));
  }

  // Use provided slug or generate one
  $slug = trim($_POST['slug'] ?? '');
  if (empty($slug) && !empty($title)) {
    $slug = create_slug($title);
  } elseif (empty($slug)) {
    $slug = create_slug(substr($content, 0, 50)); // Fallback slug
  }

  // Basic validation
  if (empty($title) || empty($content) || empty($category) || empty($status)) {
    $_SESSION['error_message'] = "Title, Content, Category, and Status are required fields.";
    header('Location: news.php');
    exit();
  }

  if ($action === 'add') {
    try {
      $stmt = $conn->prepare("INSERT INTO news_articles 
                (title, slug, excerpt, content, featured_image, category, status, featured, published_at, created_at, updated_at) 
                VALUES (:title, :slug, :excerpt, :content, :featured_image, :category, :status, :featured, :published_at, NOW(), NOW())");

      $stmt->bindParam(':title', $title);
      $stmt->bindParam(':slug', $slug);
      $stmt->bindParam(':excerpt', $excerpt);
      $stmt->bindParam(':content', $content);
      $stmt->bindParam(':featured_image', $featured_image);
      $stmt->bindParam(':category', $category);
      $stmt->bindParam(':status', $status);
      $stmt->bindParam(':featured', $featured);
      $stmt->bindParam(':published_at', $published_at);

      if ($stmt->execute()) {
        $_SESSION['success_message'] = "New article '{$title}' successfully added.";
      } else {
        $_SESSION['error_message'] = "Failed to add article '{$title}'.";
      }
    } catch (PDOException $e) {
      $_SESSION['error_message'] = "Database error during addition: " . $e->getMessage();
    }
  } elseif ($action === 'edit') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === null) {
      $_SESSION['error_message'] = "Invalid article ID for update.";
      header('Location: news.php');
      exit();
    }

    try {
      $stmt = $conn->prepare("UPDATE news_articles SET 
                title = :title, 
                slug = :slug, 
                excerpt = :excerpt, 
                content = :content, 
                featured_image = :featured_image, 
                category = :category, 
                status = :status, 
                featured = :featured,
                published_at = :published_at,
                updated_at = NOW() 
                WHERE id = :id");

      $stmt->bindParam(':title', $title);
      $stmt->bindParam(':slug', $slug);
      $stmt->bindParam(':excerpt', $excerpt);
      $stmt->bindParam(':content', $content);
      $stmt->bindParam(':featured_image', $featured_image);
      $stmt->bindParam(':category', $category);
      $stmt->bindParam(':status', $status);
      $stmt->bindParam(':featured', $featured);
      $stmt->bindParam(':published_at', $published_at);
      $stmt->bindParam(':id', $id);

      if ($stmt->execute()) {
        $_SESSION['success_message'] = "Article ID {$id} ('{$title}') successfully updated.";
      } else {
        $_SESSION['error_message'] = "Failed to update article ID {$id}.";
      }
    } catch (PDOException $e) {
      $_SESSION['error_message'] = "Database error during update: " . $e->getMessage();
    }
  }

  header('Location: news.php');
  exit();
}
