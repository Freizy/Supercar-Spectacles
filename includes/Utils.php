<?php
/**
 * Utility Functions
 * Common helper functions for the Supercar Spectacles application
 */

class Utils {
    
    /**
     * Generate a secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generate URL-friendly slug
     */
    public static function generateSlug($text) {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = 'USD') {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'GHS' => '₵'
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }

    /**
     * Format date
     */
    public static function formatDate($date, $format = 'M j, Y') {
        return date($format, strtotime($date));
    }

    /**
     * Time ago function
     */
    public static function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        
        return floor($time/31536000) . ' years ago';
    }

    /**
     * Resize image
     */
    public static function resizeImage($source, $destination, $max_width, $max_height, $quality = 80) {
        $image_info = getimagesize($source);
        if (!$image_info) return false;

        $width = $image_info[0];
        $height = $image_info[1];
        $mime = $image_info['mime'];

        // Calculate new dimensions
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);

        // Create image resource
        switch ($mime) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($source);
                break;
            default:
                return false;
        }

        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency for PNG and GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }

        // Resize image
        imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Save image
        $result = false;
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($new_image, $destination, $quality);
                break;
            case 'image/png':
                $result = imagepng($new_image, $destination, 9);
                break;
            case 'image/gif':
                $result = imagegif($new_image, $destination);
                break;
        }

        // Clean up
        imagedestroy($source_image);
        imagedestroy($new_image);

        return $result;
    }

    /**
     * Generate thumbnail
     */
    public static function generateThumbnail($source, $destination, $size = 300) {
        return self::resizeImage($source, $destination, $size, $size);
    }

    /**
     * Send email
     */
    public static function sendEmail($to, $subject, $message, $from_email = null, $from_name = null) {
        $from_email = $from_email ?: FROM_EMAIL;
        $from_name = $from_name ?: FROM_NAME;

        $headers = [
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/html; charset=UTF-8'
        ];

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    /**
     * Log activity
     */
    public static function logActivity($user_id, $action, $details = '', $ip_address = null, $user_agent = null) {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([
                $user_id,
                $action,
                $details,
                $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? ''),
                $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '')
            ]);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true && 
               in_array($_SESSION['admin_role'] ?? '', ['admin', 'moderator']);
    }

    /**
     * Require admin authentication
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit();
        }
    }

    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Generate pagination data
     */
    public static function getPagination($page, $limit, $total) {
        $pages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $pages,
            'offset' => $offset,
            'has_prev' => $page > 1,
            'has_next' => $page < $pages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $pages ? $page + 1 : null
        ];
    }

    /**
     * Clean filename for upload
     */
    public static function cleanFilename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }

    /**
     * Get file extension
     */
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is image
     */
    public static function isImage($filename) {
        $extension = self::getFileExtension($filename);
        return in_array($extension, ALLOWED_IMAGE_TYPES);
    }

    /**
     * Generate unique filename
     */
    public static function generateUniqueFilename($original_filename) {
        $extension = self::getFileExtension($original_filename);
        return uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Create directory if it doesn't exist
     */
    public static function createDirectory($path, $permissions = 0755) {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, true);
        }
        return true;
    }

    /**
     * Delete file safely
     */
    public static function deleteFile($filepath) {
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Get file size in human readable format
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>
