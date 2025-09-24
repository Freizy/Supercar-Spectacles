<?php
/**
 * Car Sales API
 * Handles car listings, inquiries, and management
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

class CarsAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get car listings (public)
     */
    public function getListings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $brand = $_GET['brand'] ?? '';
            $price_min = $_GET['price_min'] ?? '';
            $price_max = $_GET['price_max'] ?? '';
            $year = $_GET['year'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 9);
            $offset = ($page - 1) * $limit;

            $where_clause = "WHERE status = 'available'";
            $params = [];

            if (!empty($brand)) {
                $where_clause .= " AND LOWER(make) = ?";
                $params[] = strtolower($brand);
            }

            if (!empty($price_min)) {
                $where_clause .= " AND price >= ?";
                $params[] = (float)$price_min;
            }

            if (!empty($price_max)) {
                $where_clause .= " AND price <= ?";
                $params[] = (float)$price_max;
            }

            if (!empty($year)) {
                $where_clause .= " AND year = ?";
                $params[] = (int)$year;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM car_listings $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get listings
            $sql = "
                SELECT id, make, model, year, price, mileage, color, transmission, fuel_type,
                       power_hp, acceleration_0_60, top_speed, description, main_image, featured
                FROM car_listings 
                $where_clause 
                ORDER BY featured DESC, created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $listings = $stmt->fetchAll();

            // Get images for each listing
            foreach ($listings as &$listing) {
                $listing['images'] = $this->getCarImages($listing['id']);
                $listing['price_formatted'] = '$' . number_format($listing['price']);
            }

            return $this->sendResponse([
                'success' => true,
                'data' => $listings,
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
     * Get single car listing
     */
    public function getListing() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $id = (int)($_GET['id'] ?? 0);
            if (empty($id)) {
                return $this->sendResponse(['error' => 'Car ID is required'], 400);
            }

            $stmt = $this->conn->prepare("
                SELECT * FROM car_listings WHERE id = ? AND status = 'available'
            ");
            $stmt->execute([$id]);
            $listing = $stmt->fetch();

            if (!$listing) {
                return $this->sendResponse(['error' => 'Car not found'], 404);
            }

            // Get images
            $listing['images'] = $this->getCarImages($id);
            $listing['price_formatted'] = '$' . number_format($listing['price']);

            return $this->sendResponse([
                'success' => true,
                'data' => $listing
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Submit car inquiry
     */
    public function submitInquiry() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['car_id', 'name', 'email', 'phone'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return $this->sendResponse(['error' => "Field '$field' is required"], 400);
                }
            }

            // Check if car exists
            $stmt = $this->conn->prepare("SELECT id, make, model FROM car_listings WHERE id = ? AND status = 'available'");
            $stmt->execute([$data['car_id']]);
            $car = $stmt->fetch();

            if (!$car) {
                return $this->sendResponse(['error' => 'Car not found'], 404);
            }

            // Insert inquiry
            $stmt = $this->conn->prepare("
                INSERT INTO car_inquiries 
                (car_id, name, email, phone, message) 
                VALUES (?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['car_id'],
                $this->sanitizeInput($data['name']),
                $this->sanitizeInput($data['email']),
                $this->sanitizeInput($data['phone']),
                $this->sanitizeInput($data['message'] ?? '')
            ]);

            if ($result) {
                $inquiry_id = $this->conn->lastInsertId();
                
                // Send notification email
                $this->sendInquiryNotification($data, $car, $inquiry_id);
                
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Inquiry submitted successfully',
                    'inquiry_id' => $inquiry_id
                ], 201);
            } else {
                return $this->sendResponse(['error' => 'Failed to submit inquiry'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get car images
     */
    private function getCarImages($car_id) {
        $stmt = $this->conn->prepare("
            SELECT image_path, alt_text 
            FROM car_images 
            WHERE car_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$car_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get car statistics
     */
    public function getStatistics() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $stats = [];

            // Total cars
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM car_listings WHERE status = 'available'");
            $stats['total_cars'] = $stmt->fetch()['total'];

            // Cars by make
            $stmt = $this->conn->query("
                SELECT make, COUNT(*) as count 
                FROM car_listings 
                WHERE status = 'available'
                GROUP BY make 
                ORDER BY count DESC
            ");
            $stats['by_make'] = $stmt->fetchAll();

            // Price range
            $stmt = $this->conn->query("
                SELECT MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price
                FROM car_listings 
                WHERE status = 'available'
            ");
            $price_stats = $stmt->fetch();
            $stats['price_range'] = [
                'min' => (float)$price_stats['min_price'],
                'max' => (float)$price_stats['max_price'],
                'average' => (float)$price_stats['avg_price']
            ];

            // Recent inquiries
            $stmt = $this->conn->query("
                SELECT COUNT(*) as recent_inquiries 
                FROM car_inquiries 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stats['recent_inquiries'] = $stmt->fetch()['recent_inquiries'];

            return $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get inquiries (admin only)
     */
    public function getInquiries() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $status = $_GET['status'] ?? 'all';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;

            $where_clause = '';
            $params = [];

            if ($status !== 'all') {
                $where_clause = 'WHERE ci.status = ?';
                $params[] = $status;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM car_inquiries ci $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get inquiries
            $sql = "
                SELECT ci.*, cl.make, cl.model, cl.year, cl.price
                FROM car_inquiries ci
                JOIN car_listings cl ON ci.car_id = cl.id
                $where_clause 
                ORDER BY ci.created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $inquiries = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $inquiries,
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
     * Send inquiry notification email
     */
    private function sendInquiryNotification($data, $car, $inquiry_id) {
        // Implement email sending logic here
        $to = 'supercarspectacle1@gmail.com';
        $subject = 'New Car Inquiry - ' . $car['make'] . ' ' . $car['model'];
        $message = "
            New car inquiry received:
            
            Car: {$car['make']} {$car['model']}
            Inquiry ID: #{$inquiry_id}
            
            Customer Details:
            Name: {$data['name']}
            Email: {$data['email']}
            Phone: {$data['phone']}
            
            Message:
            {$data['message']}
        ";
        
        // For now, just log the email
        error_log("Inquiry email to send: $to - $subject");
    }

    /**
     * Sanitize input data
     */
    private function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
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
$api = new CarsAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $api->getListings();
        break;
    case 'get':
        $api->getListing();
        break;
    case 'inquiry':
        $api->submitInquiry();
        break;
    case 'stats':
        $api->getStatistics();
        break;
    case 'inquiries':
        $api->getInquiries();
        break;
    default:
        $api->sendResponse(['error' => 'Invalid action'], 400);
        break;
}
?>
