<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Check if DatabaseConnection.php exists
if (!file_exists('../db/DatabaseConnection.php')) {
    echo json_encode(['error' => 'DatabaseConnection.php not found']);
    exit;
}

// Include the DatabaseConnection class
require_once '../db/DatabaseConnection.php';

try {
    // Create database connection
    $db = new DatabaseConnection();
    $conn = $db->conn;

    // Get booking ID from query parameter
    $booking_id = isset($_GET['booking_id']) ? trim($_GET['booking_id']) : '';

    if (empty($booking_id)) {
        throw new Exception('Booking ID is required');
    }

    // Prepare and execute query to fetch expenses summary and customer/booking details
    $stmt = $conn->prepare('
        SELECT 
            b.booking_id,
            CONCAT(c.first_name, " ", c.last_name) AS customer_name,
            c.email AS customer_email,
            (SELECT GROUP_CONCAT(room_id SEPARATOR ", ") FROM BookedRooms WHERE booking_id = b.booking_id) AS room_numbers,
            b.checkin_date AS check_in_date,
            b.checkout_date AS check_out_date,
            (SELECT COALESCE(SUM(room_price), 0) FROM BookedRooms WHERE booking_id = b.booking_id) AS total_room_price,
            (SELECT COALESCE(SUM(total), 0) FROM Expenses WHERE booking_id = b.booking_id AND added_by = "Bar") AS bar_expenses,
            (SELECT COALESCE(SUM(total), 0) FROM Expenses WHERE booking_id = b.booking_id AND added_by = "Restaurant") AS restaurant_expenses,
            (SELECT COALESCE(SUM(total), 0) FROM Expenses WHERE booking_id = b.booking_id AND added_by = "Room Service") AS room_service_expenses
        FROM Bookings b
        INNER JOIN Customers c ON b.customer_id = c.customer_id
        WHERE b.booking_id = ?
    ');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Close statement
    $stmt->close();

    // Close database connection
    $db->closeConnection();

    if ($data) {
        if (empty($data['customer_email']) || !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid or missing customer email for booking ID ' . $booking_id);
        }
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No booking or expenses found for this booking ID']);
    }
} catch (Exception $e) {
    // Log error
    error_log('Error in get_expenses.php: ' . $e->getMessage());
    // Close connection if it exists
    if (isset($db)) {
        $db->closeConnection();
    }
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>