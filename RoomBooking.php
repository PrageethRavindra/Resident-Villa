<?php
require_once __DIR__ . '/db/DatabaseConnection.php';

// Enable error display for debugging (remove after fixing)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Ensure this path is writable

$db = new DatabaseConnection();
$conn = $db->conn;

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['room-type']) && !empty($_POST['check-in']) && !empty($_POST['check-out'])) {
        
        // Sanitize inputs
        $roomType = htmlspecialchars(trim($_POST['room-type']));
        $checkIn = htmlspecialchars(trim($_POST['check-in']));
        $checkOut = htmlspecialchars(trim($_POST['check-out']));
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        
        // Split full name into first and last name
        $nameParts = explode(" ", $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Validate dates
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
            $today = new DateTime();

            if ($checkInDate < $today->setTime(0,0,0)) {
                echo "<script>alert('Check-in date cannot be in the past.');</script>";
            } elseif ($checkOutDate <= $checkInDate) {
                echo "<script>alert('Check-out date must be after check-in date.');</script>";
            } else {
                // 1. Check if customer already exists
                $stmt = $conn->prepare("SELECT customer_id FROM Customers WHERE email = ?");
                if (!$stmt) {
                    error_log("Prepare failed: " . $conn->error);
                    die("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($customer_id);
                    $stmt->fetch();
                } else {
                    // Insert new customer
                    $stmtInsert = $conn->prepare("INSERT INTO Customers (first_name, last_name, email) VALUES (?, ?, ?)");
                    if (!$stmtInsert) {
                        error_log("Prepare failed: " . $conn->error);
                        die("Prepare failed: " . $conn->error);
                    }
                    $stmtInsert->bind_param("sss", $firstName, $lastName, $email);
                    if ($stmtInsert->execute()) {
                        $customer_id = $stmtInsert->insert_id;
                    } else {
                        error_log("Error inserting customer: " . $stmtInsert->error);
                        echo "<script>alert('Error creating customer. Please try again.');</script>";
                    }
                    $stmtInsert->close();
                }
                $stmt->close();

                // 2. Define available rooms
                $roomConfig = [
                    'superior' => ['count' => 2, 'base_number' => 101],
                    'deluxe' => ['count' => 4, 'base_number' => 201],
                    'signature' => ['count' => 1, 'base_number' => 301],
                    'couple' => ['count' => 6, 'base_number' => 401]
                ];

                if (!array_key_exists($roomType, $roomConfig)) {
                    echo "<script>alert('Invalid room type selected.');</script>";
                } else {
                    // 3. Check room availability
                    $stmtCheck = $conn->prepare("
                        SELECT room_number 
                        FROM RoomBookings 
                        WHERE room_number BETWEEN ? AND ? 
                        AND (
                            (check_in_date <= ? AND check_out_date > ?) 
                            OR (check_in_date < ? AND check_out_date >= ?)
                            OR (check_in_date >= ? AND check_out_date <= ?)
                        )
                    ");
                    if (!$stmtCheck) {
                        error_log("Prepare failed: " . $conn->error);
                        die("Prepare failed: " . $conn->error);
                    }
                    $baseNumber = $roomConfig[$roomType]['base_number'];
                    $maxNumber = $baseNumber + $roomConfig[$roomType]['count'] - 1;
                    $stmtCheck->bind_param("iissssss", 
                        $baseNumber, 
                        $maxNumber, 
                        $checkOut, 
                        $checkIn, 
                        $checkOut, 
                        $checkIn, 
                        $checkIn, 
                        $checkOut
                    );
                    $stmtCheck->execute();
                    $result = $stmtCheck->get_result();
                    
                    $bookedRooms = [];
                    while ($row = $result->fetch_assoc()) {
                        $bookedRooms[] = $row['room_number'];
                    }
                    $stmtCheck->close();

                    // Find available room
                    $availableRoom = null;
                    for ($i = $baseNumber; $i <= $maxNumber; $i++) {
                        if (!in_array($i, $bookedRooms)) {
                            $availableRoom = $i;
                            break;
                        }
                    }

                    if ($availableRoom === null) {
                        echo "<script>alert('Sorry, no $roomType rooms available for the selected dates.');</script>";
                    } else {
                        // 4. Insert room booking
                        $stmtBooking = $conn->prepare("INSERT INTO RoomBookings (customer_id, room_number, check_in_date, check_out_date) VALUES (?, ?, ?, ?)");
                        if (!$stmtBooking) {
                            error_log("Prepare failed: " . $conn->error);
                            die("Prepare failed: " . $conn->error);
                        }
                        $stmtBooking->bind_param("iiss", $customer_id, $availableRoom, $checkIn, $checkOut);
                        
                        if ($stmtBooking->execute()) {
                            $booking_id = $stmtBooking->insert_id;

                            // Generate QR Code
                            $qrData = "BOOKING_ID:$booking_id,CUSTOMER:$name,ROOM:$availableRoom,CHECK_IN:$checkIn,CHECK_OUT:$checkOut";
                            $qrCodeUrl = generateQRCodeURL($qrData);

                            // Set success message
                            $successMessage = "Booking created successfully! Booking ID: $booking_id, Room Number: $availableRoom";

                            echo "<script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    sendEmail('$email', '$name', '$qrCodeUrl', '$booking_id', '$roomType', '$checkIn', '$checkOut');
                                });
                            </script>";

                        } else {
                            error_log("SQL Error: " . $stmtBooking->error);
                            echo "<script>alert('Error: " . htmlspecialchars($stmtBooking->error) . "');</script>";
                        }
                        $stmtBooking->close();
                    }
                }
            }
        } else {
            echo "<script>alert('Invalid email format.');</script>";
        }
    } else {
        echo "<script>alert('Please fill all required fields.');</script>";
    }
}

// Function to generate QR code URL
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
            backdrop-filter: blur baffle(10px);
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
            color

: white;
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
            text-align: center;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🏨 Room Booking Form</h1>
    <?php if ($successMessage): ?>
        <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

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
                <option value="superior">🏠 Superior Room (2 available)</option>
                <option value="deluxe">🏡 Deluxe Room (4 available)</option>
                <option value="signature">🏰 Signature Room (1 available)</option>
                <option value="couple">💞 Couple Room (6 available)</option>
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
            publicKey: "YhHXgvR7VQvQoE4VR",
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