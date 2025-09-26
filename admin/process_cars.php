<?php

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Send a JSON error response for AJAX requests, or redirect for direct access.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    } else {
        header('Location: login.php');
    }
    exit();
}

$action = $_REQUEST['action'] ?? '';
$db = new Database();
$conn = $db->getConnection();

try {
    switch ($action) {
        case 'add':
            // Check for required fields
            if (empty($_POST['make']) || empty($_POST['model']) || empty($_POST['price'])) {
                $_SESSION['error_message'] = "Make, model, and price are required fields.";
                header('Location: cars.php');
                exit();
            }

            // Insert new car listing
            $stmt = $conn->prepare("INSERT INTO car_listings (make, model, year, price, mileage, color, transmission, fuel_type, power_hp, acceleration_0_60, top_speed, description, main_image, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $featured = isset($_POST['featured']) ? 1 : 0;

            $stmt->execute([
                $_POST['make'],
                $_POST['model'],
                $_POST['year'] ?: null,
                $_POST['price'],
                $_POST['mileage'] ?: null,
                $_POST['color'] ?: null,
                $_POST['transmission'] ?: 'automatic',
                $_POST['fuel_type'] ?: 'petrol',
                $_POST['power_hp'] ?: null,
                $_POST['acceleration_0_60'] ?: null,
                $_POST['top_speed'] ?: null,
                $_POST['description'] ?: null,
                $_POST['main_image'] ?: null,
                $_POST['status'] ?: 'available',
                $featured
            ]);

            $_SESSION['success_message'] = "New car listing for " . htmlspecialchars($_POST['make']) . " " . htmlspecialchars($_POST['model']) . " added successfully!";
            break;

        case 'edit':
            // Check for required fields
            if (empty($_POST['id']) || empty($_POST['make']) || empty($_POST['model']) || empty($_POST['price'])) {
                $_SESSION['error_message'] = "ID, make, model, and price are required for editing.";
                header('Location: cars.php');
                exit();
            }

            // Update existing car listing
            $stmt = $conn->prepare("UPDATE car_listings SET make=?, model=?, year=?, price=?, mileage=?, color=?, transmission=?, fuel_type=?, power_hp=?, acceleration_0_60=?, top_speed=?, description=?, main_image=?, status=?, featured=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $featured = isset($_POST['featured']) ? 1 : 0;

            $stmt->execute([
                $_POST['make'],
                $_POST['model'],
                $_POST['year'] ?: null,
                $_POST['price'],
                $_POST['mileage'] ?: null,
                $_POST['color'] ?: null,
                $_POST['transmission'] ?: 'automatic',
                $_POST['fuel_type'] ?: 'petrol',
                $_POST['power_hp'] ?: null,
                $_POST['acceleration_0_60'] ?: null,
                $_POST['top_speed'] ?: null,
                $_POST['description'] ?: null,
                $_POST['main_image'] ?: null,
                $_POST['status'] ?: 'available',
                $featured,
                $_POST['id']
            ]);

            $_SESSION['success_message'] = "Car listing updated successfully!";
            break;

        case 'delete':
            // Check for required fields
            if (empty($_GET['id'])) {
                $_SESSION['error_message'] = "Car ID is missing for deletion.";
                header('Location: cars.php');
                exit();
            }

            // Delete a car listing
            $stmt = $conn->prepare("DELETE FROM car_listings WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            $_SESSION['success_message'] = "Car listing deleted successfully!";
            break;

        case 'fetch_full_car':
            header('Content-Type: application/json');
            if (empty($_GET['id'])) {
                echo json_encode(['success' => false, 'message' => 'Car ID is missing.']);
                exit();
            }

            $stmt = $conn->prepare("SELECT * FROM car_listings WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($car) {
                echo json_encode(['success' => true, 'car' => $car]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Car not found.']);
            }
            exit();

        default:
            $_SESSION['error_message'] = "Invalid action.";
            break;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to the cars page after processing
if ($action !== 'fetch_full_car') {
    header('Location: cars.php');
    exit();
}

?>