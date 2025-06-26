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
            bes.booking_id,
            bes.total_expenses,
            bes.bar_expenses,
            bes.restaurant_expenses,
            bes.room_service_expenses,
            CONCAT(c.first_name, " ", c.last_name) AS customer_name,
            c.email AS customer_email,
            rb.room_number,
            rb.check_in_date,
            rb.check_out_date
        FROM BookingExpensesSummary bes
        INNER JOIN RoomBookings rb ON bes.booking_id = rb.booking_id
        INNER JOIN Customers c ON rb.customer_id = c.customer_id
        WHERE bes.booking_id = ?
    ');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $booking_id);
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
        echo json_encode(['error' => 'No expenses or booking found for this booking ID']);
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