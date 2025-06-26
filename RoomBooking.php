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
                $booking_id = $stmtBooking->insert_id;

                // Generate QR Code using online API (alternative to phpqrcode)
                $qrData = "BOOKING_ID:$booking_id,CUSTOMER:$name,ROOM:$roomNumber,CHECK_IN:$checkIn,CHECK_OUT:$checkOut";
                $qrCodeUrl = generateQRCodeURL($qrData);

                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        alert('Booking created successfully! Booking ID: $booking_id');
                        sendEmail('$email', '$name', '$qrCodeUrl', '$booking_id', '$roomType', '$checkIn', '$checkOut');
                    });
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

// Function to generate QR code URL using online service
function generateQRCodeURL($data) {
    $encodedData = urlencode($data);
    $size = "200x200";
    return "https://api.qrserver.com/v1/create-qr-code/?size=$size&data=$encodedData";
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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            box-sizing: border-box;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        select {
            cursor: pointer;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 24px;
            }
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🏨 Room Booking Form</h1>
    <form action="#" method="POST">
        <div class="form-group">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label for="room-type">Room Type:</label>
            <select id="room-type" name="room-type" required>
                <option value="">Select a room type</option>
                <option value="single">🛏️ Single Room</option>
                <option value="double">🛏️🛏️ Double Room</option>
                <option value="suite">✨ Suite</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="check-in">Check-in Date:</label>
                <input type="date" id="check-in" name="check-in" required>
            </div>

            <div class="form-group">
                <label for="check-out">Check-out Date:</label>
                <input type="date" id="check-out" name="check-out" required>
            </div>
        </div>

        <button type="submit" class="submit-btn">Book Room</button>
    </form>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script type="text/javascript">
    (function() {
        emailjs.init({
            publicKey: "YhHXgvR7VQvQoE4VR", // Replace with your EmailJS public key
        });
    })();

    function sendEmail(to_email, to_name, qr_code_url, booking_id, room_type, check_in, check_out) {
        const templateParams = {
            to_name: to_name,
            to_email: to_email,
            booking_id: booking_id,
            room_type: room_type,
            check_in: check_in,
            check_out: check_out,
            qr_code_url: qr_code_url
        };

        emailjs.send("service_71f1uuo", "template_7wux2ll", templateParams)
        .then(function(response) {
            console.log('Email sent successfully:', response);
            alert('Booking confirmation email sent successfully!');
        }, function(error) {
            console.error('Email sending failed:', error);
            alert('Booking created but email sending failed. Please contact support.');
        });
    }

    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('check-in').setAttribute('min', today);
        document.getElementById('check-out').setAttribute('min', today);
        
        // Update check-out minimum when check-in changes
        document.getElementById('check-in').addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            checkInDate.setDate(checkInDate.getDate() + 1);
            const minCheckOut = checkInDate.toISOString().split('T')[0];
            document.getElementById('check-out').setAttribute('min', minCheckOut);
        });
    });
</script>

</body>
</html>