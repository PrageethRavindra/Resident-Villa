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
        
        // Split full name into first and last name
        $nameParts = explode(" ", $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            // 1. Check if customer already exists
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
                if ($stmtInsert->execute()) {
                    $customer_id = $stmtInsert->insert_id;
                } else {
                    die("Error inserting customer: " . $stmtInsert->error);
                }
                $stmtInsert->close();
            }
            $stmt->close();

            // 2. Assign room_number based on room type
            $roomNumbers = [
                "single" => 101,
                "double" => 102,
                "suite"  => 201
            ];

            if (!array_key_exists($roomType, $roomNumbers)) {
                die("Invalid room type selected.");
            }

            $roomNumber = $roomNumbers[$roomType];

            // 3. Insert room booking
            $stmtBooking = $conn->prepare("INSERT INTO RoomBookings (customer_id, room_number, check_in_date, check_out_date) VALUES (?, ?, ?, ?)");
            $stmtBooking->bind_param("iiss", $customer_id, $roomNumber, $checkIn, $checkOut);
            
            if ($stmtBooking->execute()) {
                echo "<script>alert('Booking created successfully.');</script>";
                echo "<script>
                    var email = '".$email."';
                    var name = '".$name."';
                    window.onload = function() {
                        sendEmail(email, name);
                    };
                </script>";
            } else {
                error_log("SQL Error: " . $stmtBooking->error);
                echo "<script>alert('Error: " . htmlspecialchars($stmtBooking->error) . "');</script>";
            }

            $stmtBooking->close();

        } else {
            echo "<script>alert('Invalid email format.');</script>";
        }
    } else {
        echo "<script>alert('Please fill all required fields.');</script>";
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
            background-color: #e3f2fd; /* Light blue background */
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #ffffff; /* White background for form */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            color: #1976d2; /* Blue header */
        }
        .inpbox {
            margin-bottom: 15px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #bbb; /* Light gray border */
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background-color: #1976d2; /* Blue button */
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #1565c0; /* Darker blue on hover */
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Room Booking Form</h1>
    <form action="#" method="POST">
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
                <option value="single">Single</option>
                <option value="double">Double</option>
                <option value="suite">Suite</option>
            </select>
        </div>

        <div class="inpbox">
            <label for="check-in">Check-in Date:</label>
            <input type="date" id="check-in" name="check-in" required>
        </div>

        <div class="inpbox">
            <label for="check-out">Check-out Date:</label>
            <input type="date" id="check-out" name="check-out" required>
        </div>

        <button type="submit">Book Room</button>
    </form>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script type="text/javascript">
    (function() {
        emailjs.init({
            publicKey: "YhHXgvR7VQvQoE4VR", // Replace with your EmailJS public key
        });
    })();

    function sendEmail(to_email, to_name) {
        emailjs.send("service_71f1uuo", "template_7wux2ll", {
            to_name: to_name,
            to_email: to_email,
        })
        .then(function(response) {
            console.log('Email sent successfully:', response);
            alert('Email sent successfully!');
        }, function(error) {
            console.error('Email sending failed:', error);
            alert('Email sending failed. Please try again later.');
        });
    }
</script>

</body>
</html>
