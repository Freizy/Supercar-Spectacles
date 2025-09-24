<?php
/**
 * News Management API
 * Handles news articles CRUD operations and public access
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class NewsAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get published news articles (public)
     */
    public function getPublishedNews() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $category = $_GET['category'] ?? 'all';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 6);
            $offset = ($page - 1) * $limit;

            $where_clause = "WHERE status = 'published'";
            $params = [];

            if ($category !== 'all') {
                $where_clause .= " AND category = ?";
                $params[] = $category;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM news_articles $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get articles
            $sql = "
                SELECT n.id, n.title, n.slug, n.excerpt, n.featured_image, n.category, 
                       n.featured, n.views, n.published_at,
                       u.first_name, u.last_name
                FROM news_articles n
                LEFT JOIN users u ON n.author_id = u.id
                $where_clause 
                ORDER BY n.featured DESC, n.published_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $articles = $stmt->fetchAll();

            // Format articles
            foreach ($articles as &$article) {
                $article['author'] = trim($article['first_name'] . ' ' . $article['last_name']);
                unset($article['first_name'], $article['last_name']);
                $article['published_at'] = date('M j, Y', strtotime($article['published_at']));
            }

            return $this->sendResponse([
                'success' => true,
                'data' => $articles,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get single article by slug
     */
    public function getArticleBySlug() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $slug = $_GET['slug'] ?? '';
            if (empty($slug)) {
                return $this->sendResponse(['error' => 'Slug is required'], 400);
            }

            $stmt = $this->conn->prepare("
                SELECT n.*, u.first_name, u.last_name
                FROM news_articles n
                LEFT JOIN users u ON n.author_id = u.id
                WHERE n.slug = ? AND n.status = 'published'
            ");
            $stmt->execute([$slug]);
            $article = $stmt->fetch();

            if (!$article) {
                return $this->sendResponse(['error' => 'Article not found'], 404);
            }

            // Increment view count
            $update_stmt = $this->conn->prepare("UPDATE news_articles SET views = views + 1 WHERE id = ?");
            $update_stmt->execute([$article['id']]);

            // Format article
            $article['author'] = trim($article['first_name'] . ' ' . $article['last_name']);
            unset($article['first_name'], $article['last_name']);
            $article['published_at'] = date('M j, Y', strtotime($article['published_at']));

            return $this->sendResponse([
                'success' => true,
                'data' => $article
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get featured articles
     */
    public function getFeaturedArticles() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $limit = (int)($_GET['limit'] ?? 3);

            $stmt = $this->conn->prepare("
                SELECT n.id, n.title, n.slug, n.excerpt, n.featured_image, n.category, 
                       n.published_at, u.first_name, u.last_name
                FROM news_articles n
                LEFT JOIN users u ON n.author_id = u.id
                WHERE n.status = 'published' AND n.featured = 1
                ORDER BY n.published_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $articles = $stmt->fetchAll();

            // Format articles
            foreach ($articles as &$article) {
                $article['author'] = trim($article['first_name'] . ' ' . $article['last_name']);
                unset($article['first_name'], $article['last_name']);
                $article['published_at'] = date('M j, Y', strtotime($article['published_at']));
            }

            return $this->sendResponse([
                'success' => true,
                'data' => $articles
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create new article (admin only)
     */
    public function createArticle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['title', 'content'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return $this->sendResponse(['error' => "Field '$field' is required"], 400);
                }
            }

            // Generate slug
            $slug = $this->generateSlug($data['title']);
            
            // Check if slug exists
            $stmt = $this->conn->prepare("SELECT id FROM news_articles WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time();
            }

            $stmt = $this->conn->prepare("
                INSERT INTO news_articles 
                (title, slug, excerpt, content, featured_image, category, author_id, status, featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['title'],
                $slug,
                $data['excerpt'] ?? '',
                $data['content'],
                $data['featured_image'] ?? '',
                $data['category'] ?? 'general',
                1, // Default author ID (implement proper auth)
                $data['status'] ?? 'draft',
                $data['featured'] ?? false
            ]);

            if ($result) {
                $article_id = $this->conn->lastInsertId();
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Article created successfully',
                    'article_id' => $article_id,
                    'slug' => $slug
                ], 201);
            } else {
                return $this->sendResponse(['error' => 'Failed to create article'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update article (admin only)
     */
    public function updateArticle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)$data['id'];

            if (empty($id)) {
                return $this->sendResponse(['error' => 'Article ID is required'], 400);
            }

            // Check if article exists
            $stmt = $this->conn->prepare("SELECT id FROM news_articles WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                return $this->sendResponse(['error' => 'Article not found'], 404);
            }

            // Update article
            $update_fields = [];
            $params = [];

            $allowed_fields = ['title', 'excerpt', 'content', 'featured_image', 'category', 'status', 'featured'];
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $update_fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($update_fields)) {
                return $this->sendResponse(['error' => 'No fields to update'], 400);
            }

            $params[] = $id;
            $sql = "UPDATE news_articles SET " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Article updated successfully'
                ]);
            } else {
                return $this->sendResponse(['error' => 'Failed to update article'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete article (admin only)
     */
    public function deleteArticle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $id = (int)($_GET['id'] ?? 0);
            if (empty($id)) {
                return $this->sendResponse(['error' => 'Article ID is required'], 400);
            }

            $stmt = $this->conn->prepare("DELETE FROM news_articles WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Article deleted successfully'
                ]);
            } else {
                return $this->sendResponse(['error' => 'Failed to delete article'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate URL-friendly slug
     */
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Send JSON response
     */
    private function sendResponse($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data);
        exit();
    }
}

// Handle the request
$api = new NewsAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $api->getPublishedNews();
        break;
    case 'article':
        $api->getArticleBySlug();
        break;
    case 'featured':
        $api->getFeaturedArticles();
        break;
    case 'create':
        $api->createArticle();
        break;
    case 'update':
        $api->updateArticle();
        break;
    case 'delete':
        $api->deleteArticle();
        break;
    default:
        $api->sendResponse(['error' => 'Invalid action'], 400);
        break;
}
?>
