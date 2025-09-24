<?php
/**
 * Backend Testing Suite
 * Comprehensive tests to verify all backend functionality
 */

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚗 Supercar Spectacles Backend Test Suite</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
    .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    .test-item { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";

try {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "<div class='test-item success'>✅ Database connection successful!</div>";
        
        // Test if tables exist
        $tables = ['users', 'showcase_registrations', 'news_articles', 'car_listings', 'gallery_images', 'newsletter_subscriptions'];
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            echo "<div class='test-item success'>✅ All required tables exist!</div>";
        } else {
            echo "<div class='test-item error'>❌ Missing tables: " . implode(', ', $missing_tables) . "</div>";
        }
        
    } else {
        echo "<div class='test-item error'>❌ Database connection failed!</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Database error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: API Endpoints
echo "<div class='test-section'>";
echo "<h2>2. API Endpoints Test</h2>";

$api_endpoints = [
    'Showcase API' => '../api/showcase.php?action=stats',
    'News API' => '../api/news.php?action=list',
    'Cars API' => '../api/cars.php?action=stats',
    'Gallery API' => '../api/gallery.php?action=list',
    'Newsletter API' => '../api/newsletter.php?action=stats',
    'Contact API' => '../api/contact.php?action=list'
];

foreach ($api_endpoints as $name => $endpoint) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($endpoint, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "<div class='test-item success'>✅ $name is working</div>";
        } else {
            echo "<div class='test-item warning'>⚠️ $name responded but with unexpected format</div>";
        }
    } else {
        echo "<div class='test-item error'>❌ $name is not accessible</div>";
    }
}
echo "</div>";

// Test 3: File Permissions
echo "<div class='test-section'>";
echo "<h2>3. File Permissions Test</h2>";

$directories = [
    'uploads' => '../uploads/',
    'uploads/gallery' => '../uploads/gallery/',
    'admin' => '../admin/',
    'api' => '../api/'
];

foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<div class='test-item success'>✅ $name directory is writable</div>";
        } else {
            echo "<div class='test-item error'>❌ $name directory is not writable</div>";
        }
    } else {
        echo "<div class='test-item warning'>⚠️ $name directory does not exist</div>";
        // Try to create it
        if (mkdir($path, 0755, true)) {
            echo "<div class='test-item success'>✅ Created $name directory</div>";
        } else {
            echo "<div class='test-item error'>❌ Failed to create $name directory</div>";
        }
    }
}
echo "</div>";

// Test 4: PHP Configuration
echo "<div class='test-section'>";
echo "<h2>4. PHP Configuration Test</h2>";

$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='test-item success'>✅ $ext extension is loaded</div>";
    } else {
        echo "<div class='test-item error'>❌ $ext extension is missing</div>";
    }
}

// Check PHP version
$php_version = phpversion();
if (version_compare($php_version, '7.4.0', '>=')) {
    echo "<div class='test-item success'>✅ PHP version $php_version is supported</div>";
} else {
    echo "<div class='test-item error'>❌ PHP version $php_version is too old (requires 7.4+)</div>";
}

// Check file upload settings
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
echo "<div class='test-item info'>ℹ️ Upload max filesize: $upload_max</div>";
echo "<div class='test-item info'>ℹ️ Post max size: $post_max</div>";
echo "</div>";

// Test 5: Admin Panel Access
echo "<div class='test-section'>";
echo "<h2>5. Admin Panel Test</h2>";

$admin_files = [
    'Login Page' => '../admin/login.php',
    'Dashboard' => '../admin/index.php',
    'Logout' => '../admin/logout.php'
];

foreach ($admin_files as $name => $file) {
    if (file_exists($file)) {
        echo "<div class='test-item success'>✅ $name exists</div>";
    } else {
        echo "<div class='test-item error'>❌ $name is missing</div>";
    }
}
echo "</div>";

// Test 6: Sample Data Test
echo "<div class='test-section'>";
echo "<h2>6. Sample Data Test</h2>";

try {
    // Check if admin user exists
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetch()['count'];
    
    if ($admin_count > 0) {
        echo "<div class='test-item success'>✅ Admin user exists</div>";
    } else {
        echo "<div class='test-item warning'>⚠️ No admin user found</div>";
    }
    
    // Check event info
    $stmt = $conn->query("SELECT COUNT(*) as count FROM event_info");
    $event_count = $stmt->fetch()['count'];
    
    if ($event_count > 0) {
        echo "<div class='test-item success'>✅ Event information exists</div>";
    } else {
        echo "<div class='test-item warning'>⚠️ No event information found</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Sample data test failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 7: API Functionality Test
echo "<div class='test-section'>";
echo "<h2>7. API Functionality Test</h2>";

// Test newsletter subscription
$test_data = json_encode([
    'email' => 'test@example.com',
    'name' => 'Test User'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $test_data,
        'timeout' => 10
    ]
]);

$response = @file_get_contents('../api/newsletter.php?action=subscribe', false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "<div class='test-item success'>✅ Newsletter subscription API is working</div>";
    } else {
        echo "<div class='test-item warning'>⚠️ Newsletter API responded with: " . htmlspecialchars($response) . "</div>";
    }
} else {
    echo "<div class='test-item error'>❌ Newsletter subscription API test failed</div>";
}
echo "</div>";

// Summary
echo "<div class='test-section info'>";
echo "<h2>📊 Test Summary</h2>";
echo "<p>This test suite checks the basic functionality of your Supercar Spectacles backend.</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all tests pass, your backend is ready to use!</li>";
echo "<li>If any tests fail, check the error messages and fix the issues</li>";
echo "<li>Test the admin panel by visiting: <a href='../admin/login.php' target='_blank'>Admin Login</a></li>";
echo "<li>Test the APIs using the provided test files</li>";
echo "</ul>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔗 Quick Links</h2>";
echo "<p><a href='../admin/login.php' target='_blank'>Admin Panel Login</a></p>";
echo "<p><a href='test-apis.html' target='_blank'>API Testing Interface</a></p>";
echo "<p><a href='../README.md' target='_blank'>Documentation</a></p>";
echo "</div>";
?>
