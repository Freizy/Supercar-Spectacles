<?php


// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub'])) {
  try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "INSERT INTO `showcase_registrations` (`owner_name`, `car_make`, `car_model`, `contact_number`, `plate_number`, `description`, `status`) VALUES (:name, :make, :model, :contact, :plate, :description, :approval)";
    $stmt = $conn->prepare($sql);

    // Bind parameters to the placeholders
    $stmt->bindParam(':name', $_POST['owner_name']);
    $stmt->bindParam(':make', $_POST['car_make']);
    $stmt->bindParam(':model', $_POST['car_model']);
    $stmt->bindParam(':contact', $_POST['contact_number']);
    $stmt->bindParam(':plate', $_POST['plate_number']);
    $stmt->bindParam(':description', $_POST['description']);
    $approval = "pending";
    $stmt->bindParam(':approval', $approval);

    // Execute the statement and check for errors
    if (!$stmt->execute()) {
      echo "❌ **Query Execution Failed!**<br>";
      print_r($stmt->errorInfo()); // This will show a detailed error
    } else {
      echo "✅ **Registration successful!** The data should now be in your database.<br>";
    }
  } catch (PDOException $e) {
    echo "❌ **Database Connection/Query Error:** " . $e->getMessage();
  }
} else {
  echo "This page can only be accessed via the registration form.";
}

// Keep the code from redirecting so you can see the output
// header('Location: index.php?registration=success');
// exit();
