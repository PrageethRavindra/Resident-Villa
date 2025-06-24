<?php
require_once __DIR__ . '/db/DatabaseConnection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new DatabaseConnection();
$conn = $db->conn;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['room-type']) && !empty($_POST['check-in']) && !empty($_POST['check-out'])) {
        
        $roomType = filter_var($_POST['room-type'], FILTER_SANITIZE_STRING);
        $checkIn = filter_var($_POST['check-in'], FILTER_SANITIZE_STRING);
        $checkOut = filter_var($_POST['check-out'], FILTER_SANITIZE_STRING);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Split full name
        $nameParts = explode(" ", $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        // Define room types and their room numbers
        $roomConfig = [
            'superior' => ['numbers' => [101, 102], 'limit' => 2],
            'deluxe' => ['numbers' => [201, 202], 'limit' => 2],
            'signature' => ['numbers' => [301], 'limit' => 1],
            'couple' => ['numbers' => [401, 402, 403, 404], 'limit' => 4]
        ];

        // Validate room type
        if (!array_key_exists($roomType, $roomConfig)) {
            echo "<div class='error'>Invalid room type selected.</div>";
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Server-side date validation
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
            $today = new DateTime();
            $today->setTime(0, 0, 0, 0);

            if ($checkInDate < $today) {
                echo "<div class='error'>Check-in date cannot be in the past.</div>";
            } elseif ($checkOutDate <= $checkInDate) {
                echo "<div class='error'>Check-out date must be after check-in date.</div>";
            } else {
                // Start transaction
                $conn->begin_transaction();

                try {
                    // 1. Check if customer exists
                    $stmt = $conn->prepare("SELECT customer_id FROM Customers WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($customer_id);
                        $stmt->fetch();
                    } else {
                        // Insert new customer
                        $stmtInsert = $conn->prepare("INSERT INTO Customers (first_name, last_name, email) VALUES (?, ?, ?)");
                        $stmtInsert->bind_param("sss", $firstName, $lastName, $email);
                        if (!$stmtInsert->execute()) {
                            if ($stmtInsert->errno == 1062) {
                                throw new Exception("Email already exists in Customers table.");
                            }
                            throw new Exception("Error inserting customer: " . $stmtInsert->error);
                        }
                        $customer_id = $stmtInsert->insert_id;
                        $stmtInsert->close();
                    }
                    $stmt->close();

                    // 2. Check room availability
                    $roomNumbers = $roomConfig[$roomType]['numbers'];
                    $roomLimit = $roomConfig[$roomType]['limit'];
                    $placeholders = implode(',', array_fill(0, count($roomNumbers), '?'));
                    $types = str_repeat('i', count($roomNumbers)) . 'ssss';
                    $params = array_merge($roomNumbers, [$checkIn, $checkOut, $checkIn, $checkOut]);

                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as booked_count
                        FROM RoomBookings
                        WHERE room_number IN ($placeholders)
                        AND status = 'confirmed'
                        AND (
                            (? BETWEEN check_in_date AND check_out_date)
                            OR (? BETWEEN check_in_date AND check_out_date)
                            OR (check_in_date BETWEEN ? AND ?)
                        )
                    ");
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $stmt->bind_result($bookedCount);
                    $stmt->fetch();
                    $stmt->close();

                    if ($bookedCount >= $roomLimit) {
                        throw new Exception("All $roomType rooms are booked for the selected dates.");
                    }

                    // 3. Find an available room
                    $stmt = $conn->prepare("
                        SELECT room_number
                        FROM (SELECT ? as room_number UNION SELECT ? UNION SELECT ? UNION SELECT ?) rooms
                        WHERE room_number NOT IN (
                            SELECT room_number
                            FROM RoomBookings
                            WHERE status = 'confirmed'
                            AND (
                                (? BETWEEN check_in_date AND check_out_date)
                                OR (? BETWEEN check_in_date AND check_out_date)
                                OR (check_in_date BETWEEN ? AND ?)
                            )
                        )
                        LIMIT 1
                    ");
                    $stmt->bind_param("iiiissss", ...[$roomNumbers[0], $roomNumbers[1] ?? $roomNumbers[0], $roomNumbers[2] ?? $roomNumbers[0], $roomNumbers[3] ?? $roomNumbers[0], $checkIn, $checkOut, $checkIn, $checkOut]);
                    $stmt->execute();
                    $stmt->bind_result($roomNumber);
                    if (!$stmt->fetch()) {
                        throw new Exception("No available $roomType rooms found for the selected dates.");
                    }
                    $stmt->close();

                    // 4. Insert room booking
                    $stmtBooking = $conn->prepare("INSERT INTO RoomBookings (customer_id, room_number, check_in_date, check_out_date) VALUES (?, ?, ?, ?)");
                    $stmtBooking->bind_param("iiss", $customer_id, $roomNumber, $checkIn, $checkOut);
                    
                    if (!$stmtBooking->execute()) {
                        throw new Exception("Error creating booking: " . $stmtBooking->error);
                    }
                    $stmtBooking->close();

                    // Commit transaction
                    $conn->commit();

                    // Trigger email
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            sendEmail('$email', '$name', '$roomType', '$checkIn', '$checkOut');
                            setTimeout(function() {
                                window.location.href = 'booking_confirmation.php';
                            }, 2000);
                        });
                    </script>";
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        } else {
            echo "<div class='error'>Invalid email format.</div>";
        }
    } else {
        echo "<div class='error'>Please fill all required fields.</div>";
    }
}

$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking Form</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <link rel="stylesheet" href="css/BookingRoom.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e3f2fd;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            color: #1976d2;
        }
        .inpbox {
            margin-bottom: 15px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background-color: #1976d2;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #1565c0;
        }
        .error {
            color: #d32f2f;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Room Booking Form</h1>
    <form id="bookingForm" action="" method="POST">
        <div class="inpbox">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="inpbox">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="inpbox">
            <label for="room-type">Room Type:</label>
            <select id="room-type" name="room-type" required>
                <option value="">Select a room type</option>
                <option value="superior">Superior Room</option>
                <option value="deluxe">Deluxe Room</option>
                <option value="signature">Signature Room</option>
                <option value="couple">Couple Room</option>
            </select>
        </div>

        <div class="inpbox" style="position: relative;">
            <label for="check-in">Check-in Date:</label>
            <input type="date" id="check-in" name="check-in" required>
            <span class="error" id="check-in-error" style="display: none; font-size: 12px;"></span>
        </div>

        <div class="inpbox" style="position: relative;">
            <label for="check-out">Check-out Date:</label>
            <input type="date" id="check-out" name="check-out" required>
            <span class="error" id="check-out-error" style="display: none; font-size: 12px;"></span>
        </div>

        <button type="submit">Book Room</button>
    </form>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script type="text/javascript">
    (function() {
        emailjs.init({
            publicKey: "YhHXgvR7VQvQoE4VR",
        });
    })();

    function sendEmail(to_email, to_name, room_type, check_in, check_out) {
        emailjs.send("service_71f1uuo", "template_7wux2ll", {
            to_name: to_name,
            to_email: to_email,
            room_type: room_type.charAt(0).toUpperCase() + room_type.slice(1),
            check_in: check_in,
            check_out: check_out
        })
        .then(function(response) {
            console.log('Email sent successfully:', response);
            alert('Booking confirmation email sent successfully!');
        }, function(error) {
            console.error('Email sending failed:', error);
            alert('Email sending failed. Please try again later.');
        });
    }

    // Client-side date validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const checkIn = new Date(document.getElementById('check-in').value);
        const checkOut = new Date(document.getElementById('check-out').value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const checkInError = document.getElementById('check-in-error');
        const checkOutError = document.getElementById('check-out-error');
        checkInError.style.display = 'none';
        checkOutError.style.display = 'none';

        let valid = true;

        if (checkIn < today) {
            checkInError.textContent = 'Check-in date cannot be in the past.';
            checkInError.style.display = 'block';
            valid = false;
        }

        if (checkOut <= checkIn) {
            checkOutError.textContent = 'Check-out date must be after check-in date.';
            checkOutError.style.display = 'block';
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        }
    });
</script>
</body>
</html>