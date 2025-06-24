<?php
require_once '../db/DatabaseConnection.php'; // ✅ use your existing file

$db = new DatabaseConnection();
$conn = $db->conn;

// Get form data
$booking_id = $_POST['booking_id'];
$item = $_POST['item_name'];
$amount = $_POST['amount'];
$added_by = $_POST['added_by'];

// Insert into the Expenses table
$sql = "INSERT INTO Expenses (booking_id, item_name, amount, added_by) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issd", $booking_id, $item, $amount, $added_by);

if ($stmt->execute()) {
    echo "<script>alert('Expense added successfully.'); window.location.href='cashier.php';</script>";
} else {
    echo "Error: " . $conn->error;
}

$db->closeConnection();
?>
