<?php
/**
 * Gallery Management API
 * Handles gallery images CRUD operations and public access
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

class GalleryAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get gallery images (public)
     */
    public function getImages() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $category = $_GET['category'] ?? 'all';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 12);
            $offset = ($page - 1) * $limit;

            $where_clause = "WHERE status = 'active'";
            $params = [];

            if ($category !== 'all') {
                $where_clause .= " AND category = ?";
                $params[] = $category;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM gallery_images $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get images
            $sql = "
                SELECT id, title, description, image_path, alt_text, category, featured
                FROM gallery_images 
                $where_clause 
                ORDER BY featured DESC, sort_order ASC, created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $images = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $images,
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
     * Get featured images
     */
    public function getFeaturedImages() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $limit = (int)($_GET['limit'] ?? 6);

            $stmt = $this->conn->prepare("
                SELECT id, title, description, image_path, alt_text, category
                FROM gallery_images 
                WHERE status = 'active' AND featured = 1
                ORDER BY sort_order ASC, created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $images = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $images
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get image categories
     */
    public function getCategories() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $stmt = $this->conn->query("
                SELECT category, COUNT(*) as count 
                FROM gallery_images 
                WHERE status = 'active' AND category IS NOT NULL AND category != ''
                GROUP BY category 
                ORDER BY count DESC
            ");
            $categories = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $categories
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload new image (admin only)
     */
    public function uploadImage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                return $this->sendResponse(['error' => 'No image file uploaded'], 400);
            }

            $file = $_FILES['image'];
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $alt_text = $_POST['alt_text'] ?? '';

            // Validate file
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed_types)) {
                return $this->sendResponse(['error' => 'Invalid file type'], 400);
            }

            if ($file['size'] > MAX_FILE_SIZE) {
                return $this->sendResponse(['error' => 'File too large'], 400);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $upload_path = UPLOAD_PATH . 'gallery/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }

            $file_path = $upload_path . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                return $this->sendResponse(['error' => 'Failed to upload file'], 500);
            }

            // Get next sort order
            $stmt = $this->conn->query("SELECT MAX(sort_order) as max_order FROM gallery_images");
            $max_order = $stmt->fetch()['max_order'] ?? 0;
            $sort_order = $max_order + 1;

            // Insert into database
            $stmt = $this->conn->prepare("
                INSERT INTO gallery_images 
                (title, description, image_path, alt_text, category, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $title,
                $description,
                $file_path,
                $alt_text,
                $category,
                $sort_order
            ]);

            if ($result) {
                $image_id = $this->conn->lastInsertId();
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'image_id' => $image_id,
                    'image_path' => $file_path
                ], 201);
            } else {
                // Delete uploaded file if database insert failed
                unlink($file_path);
                return $this->sendResponse(['error' => 'Failed to save image data'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update image (admin only)
     */
    public function updateImage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)$data['id'];

            if (empty($id)) {
                return $this->sendResponse(['error' => 'Image ID is required'], 400);
            }

            // Check if image exists
            $stmt = $this->conn->prepare("SELECT id FROM gallery_images WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                return $this->sendResponse(['error' => 'Image not found'], 404);
            }

            // Update image
            $update_fields = [];
            $params = [];

            $allowed_fields = ['title', 'description', 'alt_text', 'category', 'sort_order', 'featured', 'status'];
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
            $sql = "UPDATE gallery_images SET " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Image updated successfully'
                ]);
            } else {
                return $this->sendResponse(['error' => 'Failed to update image'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete image (admin only)
     */
    public function deleteImage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $id = (int)($_GET['id'] ?? 0);
            if (empty($id)) {
                return $this->sendResponse(['error' => 'Image ID is required'], 400);
            }

            // Get image path before deletion
            $stmt = $this->conn->prepare("SELECT image_path FROM gallery_images WHERE id = ?");
            $stmt->execute([$id]);
            $image = $stmt->fetch();

            if (!$image) {
                return $this->sendResponse(['error' => 'Image not found'], 404);
            }

            // Delete from database
            $stmt = $this->conn->prepare("DELETE FROM gallery_images WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                // Delete physical file
                if (file_exists($image['image_path'])) {
                    unlink($image['image_path']);
                }

                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                return $this->sendResponse(['error' => 'Failed to delete image'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reorder images (admin only)
     */
    public function reorderImages() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $data = json_decode(file_get_contents('php://input'), true);
            $image_orders = $data['orders'] ?? [];

            if (empty($image_orders)) {
                return $this->sendResponse(['error' => 'No orders provided'], 400);
            }

            $this->conn->beginTransaction();

            try {
                foreach ($image_orders as $order) {
                    $stmt = $this->conn->prepare("UPDATE gallery_images SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$order['sort_order'], $order['id']]);
                }

                $this->conn->commit();
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Images reordered successfully'
                ]);

            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
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
$api = new GalleryAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $api->getImages();
        break;
    case 'featured':
        $api->getFeaturedImages();
        break;
    case 'categories':
        $api->getCategories();
        break;
    case 'upload':
        $api->uploadImage();
        break;
    case 'update':
        $api->updateImage();
        break;
    case 'delete':
        $api->deleteImage();
        break;
    case 'reorder':
        $api->reorderImages();
        break;
    default:
        $api->sendResponse(['error' => 'Invalid action'], 400);
        break;
}
?>
