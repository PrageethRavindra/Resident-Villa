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

    // Prepare and execute query to update booking status
    $stmt = $conn->prepare('UPDATE Bookings SET booking_status = "completed" WHERE booking_id = ?');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $booking_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Update BookedRooms status
        $stmt_rooms = $conn->prepare('UPDATE BookedRooms SET room_status = "checked-out" WHERE booking_id = ?');
        if (!$stmt_rooms) {
            throw new Exception('Prepare failed for BookedRooms: ' . $conn->error);
        }
        $stmt_rooms->bind_param('i', $booking_id);
        $stmt_rooms->execute();
        $stmt_rooms->close();

        echo json_encode(['success' => true]);
    } else {
        throw new Exception('No booking found or already completed for booking ID ' . $booking_id);
    }

    // Close statement and connection
    $stmt->close();
    $db->closeConnection();
} catch (Exception $e) {
    // Log error
    error_log('Error in complete_checkout.php: ' . $e->getMessage());
    // Close connection if it exists
    if (isset($db)) {
        $db->closeConnection();
    }
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>