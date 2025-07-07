<?php
require_once __DIR__ . '/db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

$db = new DatabaseConnection();
$conn = $db->conn;

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch available room types
$room_types = [];
$stmt = $conn->prepare("SELECT room_id, room_type, max_adults, max_children, price FROM HotelRoom");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $room_types[] = $row;
}
$stmt->close();

// Initialize variables
$successMessage = '';
$bookingDetails = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['check-in']) && !empty($_POST['check-out']) && !empty($_POST['rooms'])) {
        
        // Sanitize inputs
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $checkIn = htmlspecialchars(trim($_POST['check-in']));
        $checkOut = htmlspecialchars(trim($_POST['check-out']));
        $rooms = $_POST['rooms'];
        
        // Split full name
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
                // Start transaction
                $conn->begin_transaction();

                try {
                    // Check if customer exists
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
                        $stmtInsert->execute();
                        $customer_id = $stmtInsert->insert_id;
                        $stmtInsert->close();
                    }
                    $stmt->close();

                    // Validate and process room selections
                    $total_adults = 0;
                    $total_children = 0;
                    $available_rooms = [];
                    $selected_room_ids = [];
                    $is_entire_villa = false;

                    // Check if Entire Villa is selected
                    foreach ($rooms as $room) {
                        $room_id = (int)$room['room_id'];
                        $stmt = $conn->prepare("SELECT room_type FROM HotelRoom WHERE room_id = ?");
                        $stmt->bind_param("i", $room_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $room_data = $result->fetch_assoc();
                        $stmt->close();

                        if ($room_data['room_type'] === 'Entire Villa') {
                            $is_entire_villa = true;
                            break;
                        }
                    }

                    foreach ($rooms as $room) {
                        $room_id = (int)$room['room_id'];
                        $adults = (int)$room['adults'];
                        $children = (int)$room['children'];

                        // Check for duplicate room types
                        if (in_array($room_id, $selected_room_ids)) {
                            throw new Exception("Cannot select the same room type multiple times: Room ID $room_id");
                        }
                        $selected_room_ids[] = $room_id;

                        // Validate room exists and get capacity
                        $stmt = $conn->prepare("SELECT max_adults, max_children, price, room_type FROM HotelRoom WHERE room_id = ?");
                        $stmt->bind_param("i", $room_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows == 0) {
                            throw new Exception("Invalid room ID: $room_id");
                        }
                        $room_data = $result->fetch_assoc();
                        $stmt->close();

                        // Validate capacity
                        if ($adults > $room_data['max_adults'] || $children > $room_data['max_children']) {
                            throw new Exception("Guest count exceeds room capacity for room ID: $room_id");
                        }

                        // Check availability
                        if ($is_entire_villa && $room_data['room_type'] !== 'Entire Villa') {
                            throw new Exception("Cannot book other rooms when Entire Villa is selected");
                        }

                        if ($room_data['room_type'] === 'Entire Villa') {
                            // Check if any rooms are booked for the selected dates
                            $stmt = $conn->prepare("
                                SELECT br.room_id 
                                FROM BookedRooms br 
                                JOIN Bookings b ON br.booking_id = b.booking_id
                                WHERE (
                                    (b.checkin_date <= ? AND b.checkout_date > ?) 
                                    OR (b.checkin_date < ? AND b.checkout_date >= ?)
                                    OR (b.checkin_date >= ? AND b.checkout_date <= ?)
                                )
                            ");
                            $stmt->bind_param("ssssss", $checkOut, $checkIn, $checkOut, $checkIn, $checkIn, $checkOut);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                throw new Exception("Entire Villa cannot be booked as other rooms are already booked for selected dates");
                            }
                            $stmt->close();
                        } else {
                            // Check if Entire Villa is booked for the selected dates
                            $stmt = $conn->prepare("
                                SELECT br.room_id 
                                FROM BookedRooms br 
                                JOIN Bookings b ON br.booking_id = b.booking_id
                                JOIN HotelRoom hr ON br.room_id = hr.room_id
                                WHERE hr.room_type = 'Entire Villa'
                                AND (
                                    (b.checkin_date <= ? AND b.checkout_date > ?) 
                                    OR (b.checkin_date < ? AND b.checkout_date >= ?)
                                    OR (b.checkin_date >= ? AND b.checkout_date <= ?)
                                )
                            ");
                            $stmt->bind_param("ssssss", $checkOut, $checkIn, $checkOut, $checkIn, $checkIn, $checkOut);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                throw new Exception("Cannot book this room as Entire Villa is already booked for selected dates");
                            }
                            $stmt->close();

                            // Regular room availability check
                            $stmt = $conn->prepare("
                                SELECT br.room_id 
                                FROM BookedRooms br 
                                JOIN Bookings b ON br.booking_id = b.booking_id
                                WHERE br.room_id = ?
                                AND (
                                    (b.checkin_date <= ? AND b.checkout_date > ?) 
                                    OR (b.checkin_date < ? AND b.checkout_date >= ?)
                                    OR (b.checkin_date >= ? AND b.checkout_date <= ?)
                                )
                            ");
                            $stmt->bind_param("issssss", $room_id, $checkOut, $checkIn, $checkOut, $checkIn, $checkIn, $checkOut);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                throw new Exception("Room ID $room_id is not available for selected dates");
                            }
                            $stmt->close();
                        }

                        $total_adults += $adults;
                        $total_children += $children;
                        $available_rooms[] = [
                            'room_id' => $room_id,
                            'adults' => $adults,
                            'children' => $children,
                            'price' => $room_data['price']
                        ];
                    }

                    // Insert into Bookings
                    $stmt = $conn->prepare("
                        INSERT INTO Bookings (customer_id, checkin_date, checkout_date, num_adults, num_children, booking_status) 
                        VALUES (?, ?, ?, ?, ?, 'confirmed')
                    ");
                    $stmt->bind_param("issii", $customer_id, $checkIn, $checkOut, $total_adults, $total_children);
                    $stmt->execute();
                    $booking_id = $stmt->insert_id;
                    $stmt->close();

                    // Insert into BookedRooms
                    foreach ($available_rooms as $room) {
                        $stmt = $conn->prepare("
                            INSERT INTO BookedRooms (booking_id, room_id, assigned_adults, assigned_children, room_price, room_status) 
                            VALUES (?, ?, ?, ?, ?, 'booked')
                        ");
                        $stmt->bind_param("iiiid", $booking_id, $room['room_id'], $room['adults'], $room['children'], $room['price']);
                        $stmt->execute();
                        $stmt->close();
                    }

                    // Generate QR Code
                    $qrData = "BOOKING_ID:$booking_id,CUSTOMER:$name,CHECK_IN:$checkIn,CHECK_OUT:$checkOut";
                    $qrCodeUrl = generateQRCodeURL($qrData);

                    // Store booking details
                    session_start();
                    $_SESSION['booking_details'] = [
                        'booking_id' => $booking_id,
                        'rooms' => $available_rooms,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'name' => $name,
                        'email' => $email,
                        'qr_code_url' => $qrCodeUrl
                    ];

                    // Commit transaction
                    $conn->commit();

                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();

                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<script>alert('Error: " . htmlspecialchars($e->getMessage()) . "');</script>";
                }
            }
        } else {
            echo "<script>alert('Invalid email format.');</script>";
        }
    } else {
        echo "<script>alert('Please fill all required fields.');</script>";
    }
}

// Check for successful booking
session_start();
if (isset($_SESSION['booking_details'])) {
    $booking = $_SESSION['booking_details'];
    $room_list = implode(', ', array_map(function($room) use ($room_types) {
        $room_type = array_filter($room_types, function($rt) use ($room) { return $rt['room_id'] == $room['room_id']; });
        $room_type = reset($room_type);
        return $room_type['room_type'] . " (Adults: {$room['adults']}, Children: {$room['children']})";
    }, $booking['rooms']));
    $successMessage = "Booking created successfully! Booking ID: {$booking['booking_id']}, Rooms: $room_list";
    
    // Send email
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            sendEmail('{$booking['email']}', '{$booking['name']}', '{$booking['qr_code_url']}', '{$booking['booking_id']}', 
                     '$room_list', '{$booking['check_in']}', '{$booking['check_out']}');
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 2000);
        });
    </script>";
    
    unset($_SESSION['booking_details']);
}

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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking - Hotel Reservation</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('img/banner/banner2.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #6c757d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(0, 123, 255, 0.3);
            border-radius: 8px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.85);
            color: #2c3e50;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            background: #fff;
        }
        
        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .room-selection {
            border: 1px solid #e9ecef;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            position: relative;
        }
        
        .room-selection .remove-room {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #dc3545;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.6), rgba(0, 86, 179, 0.6));
            color: #ffffff;
            border: 1px solid rgba(0, 123, 255, 0.4);
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 16px;
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.8), rgba(0, 86, 179, 0.8));
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4);
            transform: translateY(-2px);
        }
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .error-message {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .add-room-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 16px;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                padding: 30px 24px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Room Booking</h1>
        <p>Reserve your perfect accommodation</p>
    </div>
    
    <?php if ($successMessage): ?>
        <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
        <div class="redirect-message">You will be redirected to the homepage in 5 seconds...</div>
        <form id="bookingForm" style="display: none;"></form>
    <?php else: ?>
        <form action="#" method="POST" id="bookingForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="check-in">Check-in Date</label>
                    <input type="date" id="check-in" name="check-in" required>
                </div>

                <div class="form-group">
                    <label for="check-out">Check-out Date</label>
                    <input type="date" id="check-out" name="check-out" required>
                </div>
            </div>

            <div id="room-selections">
                <div class="room-selection">
                    <span class="remove-room" onclick="removeRoom(this)">×</span>
                    <div class="form-group">
                        <label>Room Type</label>
                        <select name="rooms[0][room_id]" class="room-type-select" required onchange="updateRoomOptions(this)">
                            <option value="">Select room type</option>
                            <?php foreach ($room_types as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>" 
                                        data-max-adults="<?php echo $room['max_adults']; ?>" 
                                        data-max-children="<?php echo $room['max_children']; ?>"
                                        data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>">
                                    <?php echo htmlspecialchars($room['room_type']) . " (LKR " . $room['price'] . "/night)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Adults (Max: <span class="max-adults">10</span>)</label>
                            <input type="number" name="rooms[0][adults]" min="0" max="10" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Children (Max: <span class="max-children">10</span>)</label>
                            <input type="number" name="rooms[0][children]" min="0" max="10" value="0" required>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="add-room-btn" onclick="addRoom()">Add Another Room</button>
            <button type="submit" class="submit-btn" id="submitBtn">Reserve Rooms</button>
        </form>
    <?php endif; ?>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script type="text/javascript">
    (function() {
        emailjs.init({
            publicKey: "YhHXgvR7VQvQoE4VR",
        });
    })();

    function sendEmail(to_email, to_name, qr_code_url, booking_id, room_list, check_in, check_out) {
        const templateParams = {
            to_name: to_name,
            to_email: to_email,
            booking_id: booking_id,
            room_list: room_list,
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

    let roomCount = 1;

    function addRoom() {
        const roomSelections = document.getElementById('room-selections');
        const newRoom = document.createElement('div');
        newRoom.className = 'room-selection';
        newRoom.innerHTML = `
            <span class="remove-room" onclick="removeRoom(this)">×</span>
            <div class="form-group">
                <label>Room Type</label>
                <select name="rooms[${roomCount}][room_id]" class="room-type-select" required onchange="updateRoomOptions(this)">
                    <option value="">Select room type</option>
                    <?php foreach ($room_types as $room): ?>
                        <option value="<?php echo $room['room_id']; ?>" 
                                data-max-adults="<?php echo $room['max_adults']; ?>" 
                                data-max-children="<?php echo $room['max_children']; ?>"
                                data-room-type="<?php echo htmlspecialchars($room['room_type']); ?>">
                            <?php echo htmlspecialchars($room['room_type']) . " (LKR " . $room['price'] . "/night)"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Adults (Max: <span class="max-adults">10</span>)</label>
                    <input type="number" name="rooms[${roomCount}][adults]" min="0" max="10" value="1" required>
                </div>
                <div class="form-group">
                    <label>Children (Max: <span class="max-children">10</span>)</label>
                    <input type="number" name="rooms[${roomCount}][children]" min="0" max="10" value="0" required>
                </div>
            </div>
        `;
        roomSelections.appendChild(newRoom);
        roomCount++;
        updateRoomOptions();
    }

    function removeRoom(element) {
        if (document.querySelectorAll('.room-selection').length > 1) {
            element.parentElement.remove();
            updateRoomOptions();
        }
    }

    function updateRoomOptions(selectElement) {
        const selects = document.querySelectorAll('.room-type-select');
        const selectedValues = Array.from(selects)
            .map(select => select.value)
            .filter(value => value !== '');
        const selectedRoomTypes = Array.from(selects)
            .map(select => select.selectedOptions[0]?.dataset.roomType)
            .filter(type => type !== undefined);

        const isEntireVillaSelected = selectedRoomTypes.includes('Entire Villa');

        selects.forEach(select => {
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                if (option.value !== '') {
                    const isEntireVillaOption = option.dataset.roomType === 'Entire Villa';
                    if (isEntireVillaSelected && !isEntireVillaOption) {
                        // Disable all other rooms if Entire Villa is selected
                        option.disabled = true;
                        option.style.display = 'none';
                    } else if (!isEntireVillaSelected && selectedRoomTypes.length > 0 && isEntireVillaOption) {
                        // Disable Entire Villa if any other room is selected
                        option.disabled = true;
                        option.style.display = 'none';
                    } else if (selectedValues.includes(option.value) && option.value !== select.value) {
                        // Disable duplicate selections
                        option.disabled = true;
                        option.style.display = 'none';
                    } else {
                        option.disabled = false;
                        option.style.display = '';
                    }
                }
            });

            // Update max adults and children display
            if (select === selectElement && select.value !== '') {
                const roomSelection = select.closest('.room-selection');
                const maxAdultsSpan = roomSelection.querySelector('.max-adults');
                const maxChildrenSpan = roomSelection.querySelector('.max-children');
                const selectedOption = select.selectedOptions[0];
                const maxAdults = selectedOption.dataset.maxAdults || 10;
                const maxChildren = selectedOption.dataset.maxChildren || 10;
                
                maxAdultsSpan.textContent = maxAdults;
                maxChildrenSpan.textContent = maxChildren;
                
                const adultsInput = roomSelection.querySelector('input[name*="[adults]"]');
                const childrenInput = roomSelection.querySelector('input[name*="[children]"]');
                adultsInput.max = maxAdults;
                childrenInput.max = maxChildren;
                
                if (parseInt(adultsInput.value) > maxAdults) adultsInput.value = maxAdults;
                if (parseInt(childrenInput.value) > maxChildren) childrenInput.value = maxChildren;
            }
        });

        // Disable add room button if Entire Villa is selected
        const addRoomBtn = document.querySelector('.add-room-btn');
        addRoomBtn.disabled = isEntireVillaSelected;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const checkInInput = document.getElementById('check-in');
        const checkOutInput = document.getElementById('check-out');
        const form = document.getElementById('bookingForm');
        const submitBtn = document.getElementById('submitBtn');

        if (checkInInput) {
            checkInInput.setAttribute('min', today);
            checkOutInput.setAttribute('min', today);

            checkInInput.addEventListener('change', function() {
                const checkInDate = new Date(this.value);
                checkInDate.setDate(checkInDate.getDate() + 1);
                const minCheckOut = checkInDate.toISOString().split('T')[0];
                checkOutInput.setAttribute('min', minCheckOut);
                
                if (checkOutInput.value && checkOutInput.value <= this.value) {
                    checkOutInput.value = '';
                }
            });

            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
                form.classList.add('form-loading');
            });

            // Dynamic input validation
            document.getElementById('room-selections').addEventListener('change', function(e) {
                if (e.target.tagName === 'SELECT' && e.target.name.includes('[room_id]')) {
                    updateRoomOptions(e.target);
                }
            });
        }
    });
</script>

</body>
</html>