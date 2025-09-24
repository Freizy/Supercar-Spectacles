<?php
/**
 * Supercar Showcase Registration API
 * Handles showcase registration submissions and management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class ShowcaseAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Submit a new showcase registration
     */
    public function submitRegistration() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['owner_name', 'car_make', 'car_model', 'contact_number', 'plate_number', 'description'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return $this->sendResponse(['error' => "Field '$field' is required"], 400);
                }
            }

            // Sanitize input data
            $owner_name = $this->sanitizeInput($data['owner_name']);
            $car_make = $this->sanitizeInput($data['car_make']);
            $car_model = $this->sanitizeInput($data['car_model']);
            $contact_number = $this->sanitizeInput($data['contact_number']);
            $plate_number = $this->sanitizeInput($data['plate_number']);
            $description = $this->sanitizeInput($data['description']);

            // Check if plate number already exists
            $stmt = $this->conn->prepare("SELECT id FROM showcase_registrations WHERE plate_number = ?");
            $stmt->execute([$plate_number]);
            if ($stmt->fetch()) {
                return $this->sendResponse(['error' => 'A car with this plate number is already registered'], 409);
            }

            // Insert new registration
            $stmt = $this->conn->prepare("
                INSERT INTO showcase_registrations 
                (owner_name, car_make, car_model, contact_number, plate_number, description) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $owner_name, $car_make, $car_model, $contact_number, $plate_number, $description
            ]);

            if ($result) {
                $registration_id = $this->conn->lastInsertId();
                
                // Send confirmation email (optional)
                $this->sendConfirmationEmail($data, $registration_id);
                
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Registration submitted successfully',
                    'registration_id' => $registration_id
                ], 201);
            } else {
                return $this->sendResponse(['error' => 'Failed to submit registration'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all showcase registrations (admin only)
     */
    public function getRegistrations() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication (implement your auth logic here)
            // $this->checkAdminAuth();

            $status = $_GET['status'] ?? 'all';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;

            $where_clause = '';
            $params = [];

            if ($status !== 'all') {
                $where_clause = 'WHERE status = ?';
                $params[] = $status;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM showcase_registrations $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get registrations
            $sql = "
                SELECT id, owner_name, car_make, car_model, contact_number, plate_number, 
                       description, status, admin_notes, created_at, updated_at
                FROM showcase_registrations 
                $where_clause 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $registrations = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $registrations,
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
     * Update registration status (admin only)
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)$data['id'];
            $status = $data['status'];
            $admin_notes = $data['admin_notes'] ?? '';

            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                return $this->sendResponse(['error' => 'Invalid status'], 400);
            }

            $stmt = $this->conn->prepare("
                UPDATE showcase_registrations 
                SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");

            $result = $stmt->execute([$status, $admin_notes, $id]);

            if ($result) {
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Status updated successfully'
                ]);
            } else {
                return $this->sendResponse(['error' => 'Failed to update status'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get registration statistics
     */
    public function getStatistics() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $stats = [];

            // Total registrations
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM showcase_registrations");
            $stats['total'] = $stmt->fetch()['total'];

            // Registrations by status
            $stmt = $this->conn->query("
                SELECT status, COUNT(*) as count 
                FROM showcase_registrations 
                GROUP BY status
            ");
            $status_counts = $stmt->fetchAll();
            $stats['by_status'] = array_column($status_counts, 'count', 'status');

            // Recent registrations (last 7 days)
            $stmt = $this->conn->query("
                SELECT COUNT(*) as recent 
                FROM showcase_registrations 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stats['recent'] = $stmt->fetch()['recent'];

            // Popular car makes
            $stmt = $this->conn->query("
                SELECT car_make, COUNT(*) as count 
                FROM showcase_registrations 
                GROUP BY car_make 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $stats['popular_makes'] = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail($data, $registration_id) {
        // Implement email sending logic here
        // You can use PHPMailer or similar library
        $to = $data['email'] ?? 'supercarspectacle1@gmail.com';
        $subject = 'Showcase Registration Confirmation - Supercar Spectacles';
        $message = "
            Dear {$data['owner_name']},
            
            Thank you for registering your {$data['car_make']} {$data['car_model']} for Supercar Spectacle 2025!
            
            Registration ID: #{$registration_id}
            Plate Number: {$data['plate_number']}
            
            We will review your registration and contact you within 48 hours with further details.
            
            Best regards,
            Supercar Spectacles Team
        ";
        
        // For now, just log the email (implement actual email sending)
        error_log("Email to send: $to - $subject");
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
$api = new ShowcaseAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit':
        $api->submitRegistration();
        break;
    case 'list':
        $api->getRegistrations();
        break;
    case 'update':
        $api->updateStatus();
        break;
    case 'stats':
        $api->getStatistics();
        break;
    default:
        $api->sendResponse(['error' => 'Invalid action'], 400);
        break;
}
?>
