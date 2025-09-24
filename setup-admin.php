<?php
/**
 * Quick Admin Setup Script
 * This script helps you set up the admin dashboard quickly
 */

echo "<h1>🚗 Supercar Spectacles - Admin Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .step { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
    .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .btn:hover { background: #0056b3; }
    .btn-success { background: #28a745; }
    .btn-success:hover { background: #1e7e34; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<div class='container'>";

// Step 1: Check Database Connection
echo "<div class='step'>";
echo "<h2>Step 1: Database Connection</h2>";

try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "<div class='success'>✅ Database connection successful!</div>";
        
        // Check if tables exist
        $tables = ['users', 'showcase_registrations', 'news_articles', 'car_listings', 'gallery_images', 'newsletter_subscriptions'];
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            echo "<div class='success'>✅ All required tables exist!</div>";
        } else {
            echo "<div class='error'>❌ Missing tables: " . implode(', ', $missing_tables) . "</div>";
            echo "<div class='warning'>⚠️ Please import the database schema first:</div>";
            echo "<pre>mysql -u your_username -p supercar_spectacles < database/schema.sql</pre>";
        }
        
    } else {
        echo "<div class='error'>❌ Database connection failed!</div>";
        echo "<div class='warning'>⚠️ Please check your database configuration in config/database.php</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Step 2: Check Admin User
echo "<div class='step'>";
echo "<h2>Step 2: Admin User</h2>";

try {
    if (isset($conn)) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $admin_count = $stmt->fetch()['count'];
        
        if ($admin_count > 0) {
            echo "<div class='success'>✅ Admin user exists!</div>";
            
            // Get admin details
            $stmt = $conn->query("SELECT username, email, first_name, last_name FROM users WHERE role = 'admin' LIMIT 1");
            $admin = $stmt->fetch();
            echo "<div class='info'>📧 Admin: " . $admin['first_name'] . " " . $admin['last_name'] . " (" . $admin['username'] . ")</div>";
        } else {
            echo "<div class='error'>❌ No admin user found!</div>";
            echo "<div class='warning'>⚠️ Creating default admin user...</div>";
            
            // Create default admin user
            $password_hash = password_hash('password', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified) 
                VALUES ('admin', 'admin@supercarspectacles.com', ?, 'Admin', 'User', 'admin', 'active', 1)
            ");
            
            if ($stmt->execute([$password_hash])) {
                echo "<div class='success'>✅ Default admin user created!</div>";
                echo "<div class='info'>📧 Username: admin | Password: password</div>";
            } else {
                echo "<div class='error'>❌ Failed to create admin user!</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking admin user: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Step 3: Check File Permissions
echo "<div class='step'>";
echo "<h2>Step 3: File Permissions</h2>";

$directories = [
    'uploads' => 'uploads/',
    'uploads/gallery' => 'uploads/gallery/',
    'admin' => 'admin/',
    'api' => 'api/'
];

foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<div class='success'>✅ $name directory is writable</div>";
        } else {
            echo "<div class='error'>❌ $name directory is not writable</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ $name directory does not exist</div>";
        // Try to create it
        if (mkdir($path, 0755, true)) {
            echo "<div class='success'>✅ Created $name directory</div>";
        } else {
            echo "<div class='error'>❌ Failed to create $name directory</div>";
        }
    }
}
echo "</div>";

// Step 4: Admin Panel Access
echo "<div class='step'>";
echo "<h2>Step 4: Admin Panel Access</h2>";

$admin_files = [
    'Login Page' => 'admin/login.php',
    'Dashboard' => 'admin/index.php',
    'Logout' => 'admin/logout.php'
];

$all_files_exist = true;
foreach ($admin_files as $name => $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $name exists</div>";
    } else {
        echo "<div class='error'>❌ $name is missing</div>";
        $all_files_exist = false;
    }
}

if ($all_files_exist) {
    echo "<div class='success'>✅ All admin files are present!</div>";
    echo "<div class='info'>🎉 Your admin panel is ready to use!</div>";
} else {
    echo "<div class='error'>❌ Some admin files are missing. Please check your file structure.</div>";
}
echo "</div>";

// Step 5: Quick Actions
echo "<div class='step'>";
echo "<h2>Step 5: Quick Actions</h2>";

echo "<div class='info'>🚀 Ready to access your admin panel?</div>";
echo "<a href='admin/login.php' class='btn btn-success' target='_blank'>🔐 Open Admin Login</a>";
echo "<a href='test/test-backend.php' class='btn' target='_blank'>🧪 Run Backend Tests</a>";
echo "<a href='test/test-apis.html' class='btn' target='_blank'>🔌 Test APIs</a>";

echo "<div style='margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 5px;'>";
echo "<h3>📋 Default Admin Credentials:</h3>";
echo "<pre>Username: admin
Password: password</pre>";
echo "<div class='warning'>⚠️ Please change the default password after first login!</div>";
echo "</div>";
echo "</div>";

// Summary
echo "<div class='step info'>";
echo "<h2>📊 Setup Summary</h2>";
echo "<p>This setup script has checked your Supercar Spectacles backend configuration.</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all checks passed, click 'Open Admin Login' to access your dashboard</li>";
echo "<li>Login with the default credentials (admin/password)</li>";
echo "<li>Change the default password immediately</li>";
echo "<li>Start managing your Supercar Spectacles content!</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
?>
