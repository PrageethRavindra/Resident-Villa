<?php
require_once __DIR__ . '/db/DatabaseConnection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new DatabaseConnection();
$conn = $db->conn;

// AJAX endpoint to check for available drivers and book the ride
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'check_driver_availability') {
    $booking_data = json_decode($_POST['booking_data'], true);
    
    // Sanitize inputs from stored booking data
    $customer_id = filter_var($booking_data['customer_id'], FILTER_SANITIZE_NUMBER_INT);
    $pickup_location = filter_var($booking_data['pickup_location'], FILTER_SANITIZE_STRING);
    $destination = filter_var($booking_data['destination'], FILTER_SANITIZE_STRING);
    $fare = filter_var($booking_data['fare'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $booking_date = filter_var($booking_data['booking_date'], FILTER_SANITIZE_STRING);
    $pickup_time = filter_var($booking_data['pickup_time'], FILTER_SANITIZE_STRING);
    $dropoff_time = filter_var($booking_data['dropoff_time'], FILTER_SANITIZE_STRING);
    $email = filter_var($booking_data['email'], FILTER_SANITIZE_EMAIL);
    $fname = filter_var($booking_data['fname'], FILTER_SANITIZE_STRING);

    // Find available driver
    $stmt_driver = $conn->prepare("
        SELECT driver_id FROM Drivers
        WHERE status = 'available'
        AND driver_id NOT IN (
            SELECT driver_id FROM RideBookings
            WHERE booking_date = ?
            AND status IN ('pending', 'confirmed')
            AND (
                (? BETWEEN pickup_time AND dropoff_time) OR
                (? BETWEEN pickup_time AND dropoff_time) OR
                (pickup_time BETWEEN ? AND ?)
            )
        )
        LIMIT 1
    ");
    $stmt_driver->bind_param("sssss", $booking_date, $pickup_time, $dropoff_time, $pickup_time, $dropoff_time);
    $stmt_driver->execute();
    $stmt_driver->store_result();

    $response = ['available' => false, 'booking_id' => null];

    if ($stmt_driver->num_rows > 0) {
        $stmt_driver->bind_result($driver_id);
        $stmt_driver->fetch();

        // Insert booking with pending status
        $insert = $conn->prepare("
            INSERT INTO RideBookings (customer_id, driver_id, pickup_location, destination, fare, booking_date, pickup_time, dropoff_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $insert->bind_param("iissdsss", $customer_id, $driver_id, $pickup_location, $destination, $fare, $booking_date, $pickup_time, $dropoff_time);

        if ($insert->execute()) {
            $booking_id = $conn->insert_id;
            // Update driver status
            $update_driver = $conn->prepare("UPDATE Drivers SET status = 'booked' WHERE driver_id = ?");
            $update_driver->bind_param("i", $driver_id);
            $update_driver->execute();
            $update_driver->close();

            $response = [
                'available' => true,
                'booking_id' => $booking_id,
                'email' => $email,
                'fname' => $fname,
                'fare' => $fare
            ];
        } else {
            error_log("RideBookings insert failed: " . $insert->error);
            $response['error'] = "Error saving booking to database: " . $insert->error;
        }
        $insert->close();
    }
    $stmt_driver->close();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    if (!empty($_POST['fname']) && !empty($_POST['email']) && !empty($_POST['pickup']) && !empty($_POST['drop']) && !empty($_POST['VehicleType'])) {
        // Sanitize inputs
        $vehicleType = filter_var($_POST['VehicleType'], FILTER_SANITIZE_STRING);
        $pickup = filter_var($_POST['pickup'], FILTER_SANITIZE_STRING);
        $pickupLat = filter_var($_POST['pickup_lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $pickupLng = filter_var($_POST['pickup_lng'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $pickupDate = filter_var($_POST['PickupDate'], FILTER_SANITIZE_STRING);
        $pickupTime = filter_var($_POST['pickup_time'], FILTER_SANITIZE_STRING);
        $drop = filter_var($_POST['drop'], FILTER_SANITIZE_STRING);
        $dropLat = filter_var($_POST['drop_lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $dropLng = filter_var($_POST['drop_lng'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $fname = filter_var($_POST['fname'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $distanceKm = filter_var($_POST['distance_km'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Calculate fare based on vehicle type and distance
            $vehicleData = [
                'tuk' => ['base' => 200, 'rate' => 60],
                'car' => ['base' => 350, 'rate' => 85],
                'van' => ['base' => 600, 'rate' => 120]
            ];
            
            $baseFare = isset($vehicleData[$vehicleType]['base']) ? $vehicleData[$vehicleType]['base'] : 0;
            $rate = isset($vehicleData[$vehicleType]['rate']) ? $vehicleData[$vehicleType]['rate'] : 0;
            $distanceFare = $distanceKm * $rate;
            $fare = number_format($baseFare + $distanceFare, 2, '.', '');

            // Get customer_id
            $stmt_customer = $conn->prepare("SELECT customer_id FROM Customers WHERE email = ?");
            $stmt_customer->bind_param("s", $email);
            $stmt_customer->execute();
            $stmt_customer->store_result();

            if ($stmt_customer->num_rows > 0) {
                $stmt_customer->bind_result($customer_id);
                $stmt_customer->fetch();

                // Calculate dropoff time
                $estimatedMinutes = isset($_POST['estimated_time']) ? (int)$_POST['estimated_time'] : 60;
                $dropoffTime = date('H:i:s', strtotime($pickupTime . " + $estimatedMinutes minutes"));

                // Store location with coordinates
                $pickupLocationWithCoords = $pickup . " (Lat: $pickupLat, Lng: $pickupLng)";
                $destinationWithCoords = $drop . " (Lat: $dropLat, Lng: $dropLng)";

                // Prepare booking data for polling
                $booking_data = json_encode([
                    'customer_id' => $customer_id,
                    'pickup_location' => $pickupLocationWithCoords,
                    'destination' => $destinationWithCoords,
                    'fare' => $fare,
                    'booking_date' => $pickupDate,
                    'pickup_time' => $pickupTime,
                    'dropoff_time' => $dropoffTime,
                    'email' => $email,
                    'fname' => $fname
                ]);

                // Find available driver
                $stmt_driver = $conn->prepare("
                    SELECT driver_id FROM Drivers
                    WHERE status = 'available'
                    AND driver_id NOT IN (
                        SELECT driver_id FROM RideBookings
                        WHERE booking_date = ?
                        AND status IN ('pending', 'confirmed')
                        AND (
                            (? BETWEEN pickup_time AND dropoff_time) OR
                            (? BETWEEN pickup_time AND dropoff_time) OR
                            (pickup_time BETWEEN ? AND ?)
                        )
                    )
                    LIMIT 1
                ");
                $stmt_driver->bind_param("sssss", $pickupDate, $pickupTime, $dropoffTime, $pickupTime, $dropoffTime);
                $stmt_driver->execute();
                $stmt_driver->store_result();

                if ($stmt_driver->num_rows > 0) {
                    $stmt_driver->bind_result($driver_id);
                    $stmt_driver->fetch();

                    // Insert booking with pending status
                    $insert = $conn->prepare("
                        INSERT INTO RideBookings (customer_id, driver_id, pickup_location, destination, fare, booking_date, pickup_time, dropoff_time, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $insert->bind_param("iissdsss", $customer_id, $driver_id, $pickupLocationWithCoords, $destinationWithCoords, $fare, $pickupDate, $pickupTime, $dropoffTime);

                    if ($insert->execute()) {
                        $booking_id = $conn->insert_id;
                        // Update driver status
                        $update_driver = $conn->prepare("UPDATE Drivers SET status = 'booked' WHERE driver_id = ?");
                        $update_driver->bind_param("i", $driver_id);
                        $update_driver->execute();
                        $update_driver->close();

                        // Send confirmation email
                        echo "<script>
                            var email = '" . htmlspecialchars($email, ENT_QUOTES) . "';
                            var name = '" . htmlspecialchars($fname, ENT_QUOTES) . "';
                            var bookingId = '" . $booking_id . "';
                            window.onload = function() {
                                sendEmail(email, name, 'Booking Confirmation: Your ride has been scheduled. Estimated fare: LKR " . number_format($fare, 2) . "');
                                showBookingSuccess(bookingId);
                            };
                        </script>";
                    } else {
                        $error = $insert->error;
                        error_log("RideBookings insert failed: $error");
                        echo "<script>
                            console.log('SQL Error: " . addslashes($error) . "');
                            alert('Error saving booking to database: " . addslashes($error) . "');
                        </script>";
                    }
                    $insert->close();
                } else {
                    // No drivers available, show waiting state and start polling
                    echo "<script>
                        var bookingData = " . $booking_data . ";
                        window.onload = function() {
                            showWaitingState(bookingData);
                        };
                    </script>";
                }
                $stmt_driver->close();
            } else {
                echo "<script>alert('Customer email not found.');</script>";
            }
            $stmt_customer->close();
        } else {
            echo "<script>alert('Invalid email format.');</script>";
        }
    } else {
        echo "<script>alert('Please fill all required fields and select locations on the map.');</script>";
    }
}

$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Ride - Modern Cab Service</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            overflow: hidden;
        }

        .left-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }

        .left-panel::-webkit-scrollbar {
            width: 6px;
        }

        .left-panel::-webkit-scrollbar-track {
            background: transparent;
        }

        .left-panel::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .right-panel {
            position: relative;
            overflow: hidden;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .feature i {
            color: #4ade80;
        }

        .form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-group.full {
            grid-column: span 2;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            color: #6b7280;
            z-index: 2;
        }

        input, select {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .vehicle-selection {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .vehicle-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .vehicle-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .vehicle-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .vehicle-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        .vehicle-card.selected i {
            color: white;
        }

        .vehicle-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .vehicle-card p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        .vehicle-card .price {
            font-weight: 700;
            font-size: 0.9rem;
        }

        .map-container {
            height: 100%;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .map-controls {
            position: absolute;
            top: 1rem;
            left: 1rem;
            right: 1rem;
            z-index: 1000;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .map-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .map-btn:hover {
            background: white;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .map-btn.active {
            background: #667eea;
            color: white;
        }

        .route-info {
            position: absolute;
            bottom: 1rem;
            left: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            z-index: 1000;
            display: none;
        }

        .route-info.show {
            display: block;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .fare-estimate {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: none;
        }

        .fare-estimate.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fare-breakdown {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .fare-item {
            text-align: center;
        }

        .fare-item .label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }

        .fare-item .value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .total-fare {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .total-fare .value {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 1.2rem;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 0;
        }

        .map-placeholder i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .waiting-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            display: none;
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .waiting-container.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
            }
            
            .left-panel {
                max-height: 50vh;
                order: 2;
            }
            
            .right-panel {
                order: 1;
                min-height: 50vh;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .vehicle-selection {
                grid-template-columns: 1fr;
            }
            
            .fare-breakdown {
                grid-template-columns: 1fr;
            }
            
            .left-panel {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="header">
                <h1>Book Your Ride</h1>
                <p>Premium Cab Service</p>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Safe & Reliable</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Service</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>GPS Tracking</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-car"></i>
                        <span>Modern Fleet</span>
                    </div>
                </div>
            </div>

            <div class="form">
                <form id="bookingForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <!-- Hidden fields -->
                    <input type="hidden" name="distance_km" id="distanceKm">
                    <input type="hidden" name="estimated_time" id="estimatedTimeMinutes">
                    <input type="hidden" name="VehicleType" id="selectedVehicleType">

                    <!-- Vehicle Selection -->
                    <div class="form-group full">
                        <h3 style="margin-bottom: 1rem; color: #374151;">Choose Your Vehicle</h3>
                        <div class="vehicle-selection">
                            <div class="vehicle-card" data-vehicle="tuk" data-base="200" data-rate="60">
                                <i class="fas fa-motorcycle"></i>
                                <h3>Tuk Tuk</h3>
                                <p>1-2 Passengers</p>
                                <div class="price">Base: LKR 200 + 60/km</div>
                            </div>
                            <div class="vehicle-card" data-vehicle="car" data-base="350" data-rate="85">
                                <i class="fas fa-car"></i>
                                <h3>Car</h3>
                                <p>1-4 Passengers</p>
                                <div class="price">Base: LKR 350 + 85/km</div>
                            </div>
                            <div class="vehicle-card" data-vehicle="van" data-base="600" data-rate="120">
                                <i class="fas fa-bus"></i>
                                <h3>Van</h3>
                                <p>5-8 Passengers</p>
                                <div class="price">Base: LKR 600 + 120/km</div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input name="fname" type="text" placeholder="Full Name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input name="email" type="email" placeholder="Email Address" required>
                            </div>
                        </div>
                    </div>

                    <!-- Location Fields -->
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-map-marker-alt" style="color: #22c55e;"></i>
                                <input name="pickup" id="pickupInput" type="text" placeholder="Pickup Location" required readonly>
                            </div>
                            <input name="pickup_lat" id="pickupLat" type="hidden">
                            <input name="pickup_lng" id="pickupLng" type="hidden">
                        </div>
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i>
                                <input name="drop" id="dropInput" type="text" placeholder="Drop Location" required readonly>
                            </div>
                            <input name="drop_lat" id="dropLat" type="hidden">
                            <input name="drop_lng" id="dropLng" type="hidden">
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input name="PickupDate" type="date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-clock"></i>
                                <input name="pickup_time" type="time" required>
                            </div>
                        </div>
                    </div>

                    <!-- Fare Estimate -->
                    <div id="fareEstimate" class="fare-estimate">
                        <h3>Fare Estimate</h3>
                        <div class="fare-breakdown">
                            <div class="fare-item">
                                <div class="label">Distance</div>
                                <div class="value" id="fareDistance">0 km</div>
                            </div>
                            <div class="fare-item">
                                <div class="label">Duration</div>
                                <div class="value" id="fareDuration">0 min</div>
                            </div>
                            <div class="fare-item">
                                <div class="label">Base Fare</div>
                                <div class="value" id="fareBase">LKR 0</div>
                            </div>
                            <div class="fare-item">
                                <div class="label">Distance Fare</div>
                                <div class="value" id="fareDistanceAmount">LKR 0</div>
                            </div>
                        </div>
                        <div class="total-fare">
                            <div class="label">Total Estimated Fare</div>
                            <div class="value" id="fareTotal">LKR 0</div>
                        </div>
                    </div>

                    <!-- Waiting Indicator -->
                    <div id="waitingContainer" class="waiting-container">
                        <div class="spinner"></div>
                        <h3>Waiting for Driver</h3>
                        <p id="waitingMessage">All drivers are booked. Your ride is on the waiting list.</p>
                    </div>

                    <!-- Buttons -->
                    <div class="buttons">
                        <button type="button" class="btn btn-secondary" onclick="clearForm()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Book Ride
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="right-panel">
            <div class="map-controls">
                <button type="button" class="map-btn" id="selectPickupBtn">
                    <i class="fas fa-map-marker-alt" style="color: #22c55e;"></i>
                    Select Pickup
                </button>
                <button type="button" class="map-btn" id="selectDropBtn">
                    <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i>
                    Select Drop
                </button>
                <button type="button" class="map-btn" id="getCurrentLocationBtn">
                    <i class="fas fa-crosshairs"></i>
                    My Location
                </button>
                <button type="button" class="map-btn" id="clearMapBtn">
                    <i class="fas fa-trash"></i>
                    Clear
                </button>
            </div>
            
            <div id="map" class="map-container">
                <div class="map-placeholder">
                    <div>Interactive Map</div>
                    <div style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.7;">Click buttons above to select pickup and drop locations</div>
                </div>
            </div>
            
            <div id="routeInfo" class="route-info">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <h4 style="color: #374151;">Route Information</h4>
                    <i class="fas fa-route" style="color: #667eea;"></i>
                </div>
                <div id="routeDetails"></div>
            </div>
        </div>
    </div>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAWTbocqKAcq7FPVWIaGw64ZAfk2Fiqxmo&libraries=places&callback=initMap" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
        (function() {
            emailjs.init({
                publicKey: "IMBYADrcMPyNYpISy",
            });
        })();

        // Global variables
        let map;
        let pickupMarker;
        let dropMarker;
        let directionsService;
        let directionsRenderer;
        let geocoder;
        let isSelectingPickup = false;
        let isSelectingDrop = false;
        let currentDistance = 0;
        let currentDuration = 0;
        let selectedVehicle = null;
        let pollingInterval = null;

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: { lat: 6.9271, lng: 79.8612 },
                styles: [
                    {
                        featureType: "all",
                        elementType: "geometry.fill",
                        stylers: [{ weight: "2.00" }]
                    },
                    {
                        featureType: "all",
                        elementType: "geometry.stroke",
                        stylers: [{ color: "#9c9c9c" }]
                    },
                    {
                        featureType: "all",
                        elementType: "labels.text",
                        stylers: [{ visibility: "on" }]
                    }
                ],
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false
            });

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#667eea',
                    strokeWeight: 5,
                    strokeOpacity: 0.8
                }
            });
            directionsRenderer.setMap(map);
            geocoder = new google.maps.Geocoder();

            map.addListener('click', function(event) {
                if (isSelectingPickup) {
                    setPickupLocation(event.latLng);
                } else if (isSelectingDrop) {
                    setDropLocation(event.latLng);
                }
            });

            setupEventListeners();
        }

        function setupEventListeners() {
            document.getElementById('selectPickupBtn').addEventListener('click', function() {
                isSelectingPickup = true;
                isSelectingDrop = false;
                this.classList.add('active');
                document.getElementById('selectDropBtn').classList.remove('active');
                map.setOptions({ cursor: 'crosshair' });
            });

            document.getElementById('selectDropBtn').addEventListener('click', function() {
                isSelectingDrop = true;
                isSelectingPickup = false;
                this.classList.add('active');
                document.getElementById('selectPickupBtn').classList.remove('active');
                map.setOptions({ cursor: 'crosshair' });
            });

            document.getElementById('clearMapBtn').addEventListener('click', clearMap);
            document.getElementById('getCurrentLocationBtn').addEventListener('click', getCurrentLocation);

            document.querySelectorAll('.vehicle-card').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.vehicle-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    selectedVehicle = {
                        type: this.dataset.vehicle,
                        base: parseInt(this.dataset.base),
                        rate: parseFloat(this.dataset.rate)
                    };
                    
                    document.getElementById('selectedVehicleType').value = selectedVehicle.type;
                    updateFareEstimate();
                });
            });

            document.getElementById('bookingForm').addEventListener('submit', function(e) {
                if (!selectedVehicle) {
                    e.preventDefault();
                    alert('Please select a vehicle type.');
                    return;
                }
                
                const pickupLat = document.getElementById('pickupLat').value;
                const pickupLng = document.getElementById('pickupLng').value;
                const dropLat = document.getElementById('dropLat').value;
                const dropLng = document.getElementById('dropLng').value;

                if (!pickupLat || !pickupLng || !dropLat || !dropLng) {
                    e.preventDefault();
                    alert('Please select both pickup and drop locations on the map.');
                    return;
                }
            });
        }

        function showWaitingState(bookingData) {
            const waitingContainer = document.getElementById('waitingContainer');
            document.getElementById('waitingMessage').textContent = 'All drivers are booked. Your ride is on the waiting list.';
            waitingContainer.classList.add('show');
            document.getElementById('bookingForm').style.display = 'none';
            document.getElementById('fareEstimate').classList.remove('show');

            // Start polling for driver availability
            pollingInterval = setInterval(() => {
                checkDriverAvailability(bookingData);
            }, 5000); // Check every 5 seconds
        }

        function showBookingSuccess(bookingId, email, fname, fare) {
            const waitingContainer = document.getElementById('waitingContainer');
            waitingContainer.classList.remove('show');
            document.getElementById('bookingForm').style.display = 'block';
            clearForm();
            sendEmail(email, fname, `Booking Confirmation: Your ride has been scheduled. Estimated fare: LKR ${fare}`);
            alert('Booking confirmed! You will receive a confirmation email shortly.');
        }

        function checkDriverAvailability(bookingData) {
            fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=check_driver_availability&booking_data=${encodeURIComponent(JSON.stringify(bookingData))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    clearInterval(pollingInterval);
                    showBookingSuccess(data.booking_id, data.email, data.fname, data.fare);
                } else if (data.error) {
                    clearInterval(pollingInterval);
                    alert(data.error);
                    document.getElementById('waitingContainer').classList.remove('show');
                    document.getElementById('bookingForm').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error checking driver availability:', error);
                clearInterval(pollingInterval);
                alert('Error checking driver availability. Please try again.');
                document.getElementById('waitingContainer').classList.remove('show');
                document.getElementById('bookingForm').style.display = 'block';
            });
        }

        function setPickupLocation(latLng) {
            if (pickupMarker) {
                pickupMarker.setMap(null);
            }

            pickupMarker = new google.maps.Marker({
                position: latLng,
                map: map,
                title: 'Pickup Location',
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="12" fill="#22c55e" stroke="#ffffff" stroke-width="3"/>
                            <circle cx="16" cy="16" r="4" fill="#ffffff"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                }
            });

            geocoder.geocode({ location: latLng }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    document.getElementById('pickupInput').value = results[0].formatted_address;
                    document.getElementById('pickupLat').value = latLng.lat();
                    document.getElementById('pickupLng').value = latLng.lng();
                    calculateRoute();
                } else {
                    alert('Could not find address for this location.');
                }
            });

            isSelectingPickup = false;
            document.getElementById('selectPickupBtn').classList.remove('active');
            map.setOptions({ cursor: 'default' });
        }

        function setDropLocation(latLng) {
            if (dropMarker) {
                dropMarker.setMap(null);
            }

            dropMarker = new google.maps.Marker({
                position: latLng,
                map: map,
                title: 'Drop Location',
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="12" fill="#ef4444" stroke="#ffffff" stroke-width="3"/>
                            <circle cx="16" cy="16" r="4" fill="#ffffff"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                }
            });

            geocoder.geocode({ location: latLng }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    document.getElementById('dropInput').value = results[0].formatted_address;
                    document.getElementById('dropLat').value = latLng.lat();
                    document.getElementById('dropLng').value = latLng.lng();
                    calculateRoute();
                } else {
                    alert('Could not find address for this location.');
                }
            });

            isSelectingDrop = false;
            document.getElementById('selectDropBtn').classList.remove('active');
            map.setOptions({ cursor: 'default' });
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const latLng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    setPickupLocation(latLng);
                    map.setCenter(latLng);
                    map.setZoom(15);
                }, function(error) {
                    alert('Error: Geolocation failed - ' + error.message);
                });
            } else {
                alert('Error: Your browser does not support geolocation.');
            }
        }

        function clearMap() {
            if (pickupMarker) {
                pickupMarker.setMap(null);
                pickupMarker = null;
            }
            if (dropMarker) {
                dropMarker.setMap(null);
                dropMarker = null;
            }
            directionsRenderer.setDirections({ routes: [] });

            document.getElementById('pickupInput').value = '';
            document.getElementById('dropInput').value = '';
            document.getElementById('pickupLat').value = '';
            document.getElementById('pickupLng').value = '';
            document.getElementById('dropLat').value = '';
            document.getElementById('dropLng').value = '';

            document.getElementById('routeInfo').classList.remove('show');
            document.getElementById('fareEstimate').classList.remove('show');
            document.getElementById('waitingContainer').classList.remove('show');
            document.getElementById('bookingForm').style.display = 'block';

            currentDistance = 0;
            currentDuration = 0;
            document.getElementById('distanceKm').value = '';
            document.getElementById('estimatedTimeMinutes').value = '';

            isSelectingPickup = false;
            isSelectingDrop = false;
            document.getElementById('selectPickupBtn').classList.remove('active');
            document.getElementById('selectDropBtn').classList.remove('active');
            map.setOptions({ cursor: 'default' });

            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }

        function calculateRoute() {
            const pickupLat = document.getElementById('pickupLat').value;
            const pickupLng = document.getElementById('pickupLng').value;
            const dropLat = document.getElementById('dropLat').value;
            const dropLng = document.getElementById('dropLng').value;

            if (!pickupLat || !pickupLng || !dropLat || !dropLng) {
                return;
            }

            const request = {
                origin: new google.maps.LatLng(parseFloat(pickupLat), parseFloat(pickupLng)),
                destination: new google.maps.LatLng(parseFloat(dropLat), parseFloat(dropLng)),
                travelMode: google.maps.TravelMode.DRIVING
            };

            directionsService.route(request, function(result, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);

                    const route = result.routes[0].legs[0];
                    currentDistance = route.distance.value / 1000;
                    currentDuration = Math.round(route.duration.value / 60);

                    document.getElementById('distanceKm').value = currentDistance;
                    document.getElementById('estimatedTimeMinutes').value = currentDuration;

                    document.getElementById('routeDetails').innerHTML = `
                        <div style="font-size: 0.9rem; color: #374151;">
                            <div><strong>From:</strong> ${document.getElementById('pickupInput').value}</div>
                            <div><strong>To:</strong> ${document.getElementById('dropInput').value}</div>
                            <div><strong>Distance:</strong> ${currentDistance.toFixed(2)} km</div>
                            <div><strong>Estimated Time:</strong> ${currentDuration} minutes</div>
                        </div>
                    `;
                    document.getElementById('routeInfo').classList.add('show');

                    updateFareEstimate();
                } else {
                    alert('Error calculating route: ' + status);
                }
            });
        }

        function calculateFare(vehicle, distance) {
            if (!vehicle) return 0;
            return vehicle.base + (vehicle.rate * distance);
        }

        function updateFareEstimate() {
            if (!selectedVehicle || !currentDistance) {
                document.getElementById('fareEstimate').classList.remove('show');
                return;
            }

            const fare = calculateFare(selectedVehicle, currentDistance);
            document.getElementById('fareDistance').textContent = `${currentDistance.toFixed(2)} km`;
            document.getElementById('fareDuration').textContent = `${currentDuration} min`;
            document.getElementById('fareBase').textContent = `LKR ${selectedVehicle.base.toFixed(2)}`;
            document.getElementById('fareDistanceAmount').textContent = `LKR ${(selectedVehicle.rate * currentDistance).toFixed(2)}`;
            document.getElementById('fareTotal').textContent = `LKR ${fare.toFixed(2)}`;
            document.getElementById('fareEstimate').classList.add('show');

            const fareEstimate = document.getElementById('fareEstimate');
            const leftPanel = document.querySelector('.left-panel');
            if (fareEstimate && leftPanel) {
                fareEstimate.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function clearForm() {
            document.getElementById('bookingForm').reset();
            document.querySelectorAll('.vehicle-card').forEach(card => card.classList.remove('selected'));
            selectedVehicle = null;
            clearMap();
            document.getElementById('fareEstimate').classList.remove('show');
            document.getElementById('routeInfo').classList.remove('show');
            document.getElementById('waitingContainer').classList.remove('show');
            document.getElementById('bookingForm').style.display = 'block';
        }

        function sendEmail(to_email, to_name, message) {
            emailjs.send("service_e0nxrqb", "template_pjejhnt", {
                to_name: to_name,
                to_email: to_email,
                message: message
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