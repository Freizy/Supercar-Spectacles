<?php
/**
 * Newsletter Subscription API
 * Handles newsletter subscriptions and management
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

class NewsletterAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Subscribe to newsletter
     */
    public function subscribe() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate email
            if (empty($data['email'])) {
                return $this->sendResponse(['error' => 'Email is required'], 400);
            }

            $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return $this->sendResponse(['error' => 'Invalid email address'], 400);
            }

            $name = $data['name'] ?? '';

            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT id, status FROM newsletter_subscriptions WHERE email = ?");
            $stmt->execute([$email]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($existing['status'] === 'active') {
                    return $this->sendResponse(['error' => 'Email is already subscribed'], 409);
                } else {
                    // Reactivate subscription
                    $stmt = $this->conn->prepare("
                        UPDATE newsletter_subscriptions 
                        SET status = 'active', name = ?, subscribed_at = CURRENT_TIMESTAMP, unsubscribed_at = NULL 
                        WHERE email = ?
                    ");
                    $result = $stmt->execute([$name, $email]);
                    
                    if ($result) {
                        $this->sendWelcomeEmail($email, $name);
                        return $this->sendResponse([
                            'success' => true,
                            'message' => 'Subscription reactivated successfully'
                        ]);
                    }
                }
            } else {
                // Create new subscription
                $stmt = $this->conn->prepare("
                    INSERT INTO newsletter_subscriptions (email, name, status) 
                    VALUES (?, ?, 'active')
                ");
                $result = $stmt->execute([$email, $name]);

                if ($result) {
                    $subscription_id = $this->conn->lastInsertId();
                    $this->sendWelcomeEmail($email, $name);
                    
                    return $this->sendResponse([
                        'success' => true,
                        'message' => 'Successfully subscribed to newsletter',
                        'subscription_id' => $subscription_id
                    ], 201);
                }
            }

            return $this->sendResponse(['error' => 'Failed to subscribe'], 500);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['email'])) {
                return $this->sendResponse(['error' => 'Email is required'], 400);
            }

            $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return $this->sendResponse(['error' => 'Invalid email address'], 400);
            }

            $stmt = $this->conn->prepare("
                UPDATE newsletter_subscriptions 
                SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP 
                WHERE email = ?
            ");
            $result = $stmt->execute([$email]);

            if ($result && $stmt->rowCount() > 0) {
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Successfully unsubscribed from newsletter'
                ]);
            } else {
                return $this->sendResponse(['error' => 'Email not found in subscription list'], 404);
            }

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get subscribers (admin only)
     */
    public function getSubscribers() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $status = $_GET['status'] ?? 'active';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;

            $where_clause = '';
            $params = [];

            if ($status !== 'all') {
                $where_clause = 'WHERE status = ?';
                $params[] = $status;
            }

            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM newsletter_subscriptions $where_clause";
            $count_stmt = $this->conn->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get subscribers
            $sql = "
                SELECT id, email, name, status, subscribed_at, unsubscribed_at
                FROM newsletter_subscriptions 
                $where_clause 
                ORDER BY subscribed_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $subscribers = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $subscribers,
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
     * Get subscription statistics
     */
    public function getStatistics() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            $stats = [];

            // Total subscribers
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM newsletter_subscriptions WHERE status = 'active'");
            $stats['total_subscribers'] = $stmt->fetch()['total'];

            // Subscribers by status
            $stmt = $this->conn->query("
                SELECT status, COUNT(*) as count 
                FROM newsletter_subscriptions 
                GROUP BY status
            ");
            $status_counts = $stmt->fetchAll();
            $stats['by_status'] = array_column($status_counts, 'count', 'status');

            // Recent subscriptions (last 30 days)
            $stmt = $this->conn->query("
                SELECT COUNT(*) as recent 
                FROM newsletter_subscriptions 
                WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'active'
            ");
            $stats['recent_subscriptions'] = $stmt->fetch()['recent'];

            // Monthly subscription trend (last 12 months)
            $stmt = $this->conn->query("
                SELECT 
                    DATE_FORMAT(subscribed_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM newsletter_subscriptions 
                WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status = 'active'
                GROUP BY DATE_FORMAT(subscribed_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stats['monthly_trend'] = $stmt->fetchAll();

            return $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send newsletter (admin only)
     */
    public function sendNewsletter() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->sendResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Check for admin authentication
            // $this->checkAdminAuth();

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['subject']) || empty($data['content'])) {
                return $this->sendResponse(['error' => 'Subject and content are required'], 400);
            }

            // Get active subscribers
            $stmt = $this->conn->query("SELECT email, name FROM newsletter_subscriptions WHERE status = 'active'");
            $subscribers = $stmt->fetchAll();

            if (empty($subscribers)) {
                return $this->sendResponse(['error' => 'No active subscribers found'], 400);
            }

            $sent_count = 0;
            $failed_count = 0;

            foreach ($subscribers as $subscriber) {
                if ($this->sendNewsletterEmail($subscriber['email'], $subscriber['name'], $data['subject'], $data['content'])) {
                    $sent_count++;
                } else {
                    $failed_count++;
                }
            }

            return $this->sendResponse([
                'success' => true,
                'message' => 'Newsletter sent successfully',
                'sent_count' => $sent_count,
                'failed_count' => $failed_count,
                'total_subscribers' => count($subscribers)
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send welcome email
     */
    private function sendWelcomeEmail($email, $name) {
        $subject = 'Welcome to Supercar Spectacles Newsletter!';
        $message = "
            Dear " . ($name ?: 'Subscriber') . ",
            
            Thank you for subscribing to the Supercar Spectacles newsletter!
            
            You'll now receive the latest updates about:
            - Event announcements and updates
            - Exclusive supercar news
            - Behind-the-scenes content
            - Special offers and promotions
            
            Stay tuned for exciting updates about Supercar Spectacle 2025!
            
            Best regards,
            The Supercar Spectacles Team
        ";
        
        // For now, just log the email (implement actual email sending)
        error_log("Welcome email to send: $email - $subject");
    }

    /**
     * Send newsletter email
     */
    private function sendNewsletterEmail($email, $name, $subject, $content) {
        $message = "
            Dear " . ($name ?: 'Subscriber') . ",
            
            $content
            
            Best regards,
            The Supercar Spectacles Team
            
            ---
            To unsubscribe, click here: " . APP_URL . "/unsubscribe?email=$email
        ";
        
        // For now, just log the email (implement actual email sending)
        error_log("Newsletter email to send: $email - $subject");
        return true; // Simulate successful send
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
$api = new NewsletterAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'subscribe':
        $api->subscribe();
        break;
    case 'unsubscribe':
        $api->unsubscribe();
        break;
    case 'subscribers':
        $api->getSubscribers();
        break;
    case 'stats':
        $api->getStatistics();
        break;
    case 'send':
        $api->sendNewsletter();
        break;
    default:
        $api->sendResponse(['error' => 'Invalid action'], 400);
        break;
}
?>
