<?php
/**
 * Contact Form API
 * Handles contact form submissions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

class ContactAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Submit contact form
     */
    public function submitContact() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['name', 'email', 'message'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return $this->sendResponse(['error' => "Field '$field' is required"], 400);
                }
            }

            // Validate email
            $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return $this->sendResponse(['error' => 'Invalid email address'], 400);
            }

            // Sanitize input data
            $name = $this->sanitizeInput($data['name']);
            $phone = $this->sanitizeInput($data['phone'] ?? '');
            $subject = $this->sanitizeInput($data['subject'] ?? '');
            $message = $this->sanitizeInput($data['message']);

            // Insert contact submission
            $stmt = $this->conn->prepare("
                INSERT INTO contact_submissions 
                (name, email, phone, subject, message) 
                VALUES (?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([$name, $email, $phone, $subject, $message]);

            if ($result) {
                $submission_id = $this->conn->lastInsertId();
                
                // Send notification email
                $this->sendContactNotification($data, $submission_id);
                
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'submission_id' => $submission_id
                ], 201);
            } else {
                return $this->sendResponse(['error' => 'Failed to send message'], 500);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get contact submissions (admin only)
     */
    public function getSubmissions() {
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
                $where_clause = 'WHERE status = ?';
                $params[] = $status;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM contact_submissions $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get submissions
            $sql = "
                SELECT id, name, email, phone, subject, message, status, created_at, updated_at
                FROM contact_submissions 
                $where_clause 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $submissions = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $submissions,
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
     * Update submission status (admin only)
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

            if (!in_array($status, ['new', 'read', 'responded'])) {
                return $this->sendResponse(['error' => 'Invalid status'], 400);
            }

            $stmt = $this->conn->prepare("
                UPDATE contact_submissions 
                SET status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");

            $result = $stmt->execute([$status, $id]);

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
     * Send contact notification email
     */
    private function sendContactNotification($data, $submission_id) {
        $to = 'supercarspectacle1@gmail.com';
        $subject = 'New Contact Form Submission - Supercar Spectacles';
        $message = "
            New contact form submission received:
            
            Submission ID: #{$submission_id}
            
            Contact Details:
            Name: {$data['name']}
            Email: {$data['email']}
            Phone: " . ($data['phone'] ?? 'Not provided') . "
            Subject: " . ($data['subject'] ?? 'No subject') . "
            
            Message:
            {$data['message']}
            
            Submitted on: " . date('Y-m-d H:i:s') . "
        ";
        
        // For now, just log the email (implement actual email sending)
        error_log("Contact email to send: $to - $subject");
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
$api = new ContactAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit':
        $api->submitContact();
        break;
    case 'list':
        $api->getSubmissions();
        break;
    case 'update':
        $api->updateStatus();
        break;
    default:
        $api->sendResponse(['error' => 'Invalid action'], 400);
        break;
}
?>
