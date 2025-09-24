<?php
require_once './config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($conn) {
        echo "✅ Connection to the database was successful!";
    } else {
        echo "❌ Failed to get a database connection.";
    }
} catch (PDOException $e) {
    echo "❌ Connection error: " . $e->getMessage();
}
?>