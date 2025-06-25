<?php
// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();

require_once '../db/DatabaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('This script only accepts POST requests');
}

try {
    $db = new DatabaseConnection();
    $conn = $db->conn;

    if (!$conn) {
        die("Database connection failed");
    }

    // Validate and get form data
    $booking_id_raw = $_POST['booking_id'] ?? null;
    $item = $_POST['item_name'] ?? null;
    $unit_price = $_POST['unit_price'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $added_by = $_POST['added_by'] ?? null;

    // Parse booking_id from QR code format
    $booking_id = null;
    if ($booking_id_raw) {
        if (preg_match('/BOOKING_ID:(\d+)/', $booking_id_raw, $matches)) {
            $booking_id = (int)$matches[1];
        } elseif (is_numeric($booking_id_raw)) {
            $booking_id = (int)$booking_id_raw;
        } else {
            die("Invalid booking ID format");
        }
    }

    // Validate required fields
    $errors = [];
    if (empty($booking_id)) $errors[] = "Booking ID is required";
    if (empty($item)) $errors[] = "Item name is required";
    if (empty($unit_price) || !is_numeric($unit_price) || $unit_price <= 0) $errors[] = "Valid unit price is required";
    if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) $errors[] = "Valid quantity is required";
    if (empty($added_by)) $errors[] = "Source is required";

    if ($errors) {
        echo "<p style='color:red;'>Error:</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
        exit;
    }

    // Calculate total
    $total = $unit_price * $quantity;

    // Prepare and execute the insert statement
    $sql = "INSERT INTO Expenses (booking_id, item_name, unit_price, quantity, total, added_by) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("isddds", $booking_id, $item, $unit_price, $quantity, $total, $added_by);

    // Execute the statement
    if ($stmt->execute()) {
        $inserted_id = $stmt->insert_id;

        // Verify the inserted record
        $verify = $conn->prepare("SELECT * FROM Expenses WHERE expense_id = ?");
        if (!$verify) {
            die("Verify query preparation failed: " . $conn->error);
        }
        $verify->bind_param("i", $inserted_id);
        if (!$verify->execute()) {
            throw new Exception("Verify query execution failed: " . $verify->error);
        }
        $result = $verify->get_result();
        $record = $result->fetch_assoc();
        $verify->close();

        if (!$record) {
            throw new Exception("Failed to verify inserted record");
        }

        // Display success message and redirect
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        echo "<title>Expense Added - Resident-Villa</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "</head>";
        echo "<body class='bg-light p-3'>";
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-success text-center'>";
        echo "<h4>✅ Expense Added Successfully!</h4>";
        echo "<p><a href='cashier.php'>Click here to go now</a></p>";
        echo "</div>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
        echo "<script>";
        echo "setTimeout(function() {";
        echo "    window.location.href = 'cashier.php';";
        echo "}, 3000);";
        echo "</script>";

    } else {
        echo "<p style='color:red;'>Error: Failed to add expense: " . htmlspecialchars($stmt->error) . "</p>";
        echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
    }

    $stmt->close();
    $db->closeConnection();

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
}

// End output buffering
ob_end_flush();
?>