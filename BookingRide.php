<?php
require_once __DIR__ . './db/DatabaseConnection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new DatabaseConnection();
$conn = $db->conn;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['fname']) && !empty($_POST['email'])) {

        // Sanitize inputs
        $vehicleType = filter_var($_POST['VehicleType'], FILTER_SANITIZE_STRING);
        $pickup = filter_var($_POST['pickup'], FILTER_SANITIZE_STRING);
        $pickupDate = filter_var($_POST['PickupDate'], FILTER_SANITIZE_STRING);
        $drop = filter_var($_POST['drop'], FILTER_SANITIZE_STRING);
        $dropDate = filter_var($_POST['dropDate'], FILTER_SANITIZE_STRING);
        $fname = filter_var($_POST['fname'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $packageType = isset($_POST['plan']) ? filter_var($_POST['plan'], FILTER_SANITIZE_STRING) : '';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            // Calculate fare
            $fare = 0;
            switch($packageType) {
                case 'regular': $fare = 5000; break;
                case 'pro': $fare = 10000; break;
                case 'advance': $fare = 15000; break;
                default: $fare = 0;
            }

            // Get customer_id from Customers table
            $stmt_customer = $conn->prepare("SELECT customer_id FROM Customers WHERE email = ?");
            $stmt_customer->bind_param("s", $email);
            $stmt_customer->execute();
            $stmt_customer->store_result();

            if ($stmt_customer->num_rows > 0) {
                $stmt_customer->bind_result($customer_id);
                $stmt_customer->fetch();

                // Find available driver
                $stmt_driver = $conn->prepare("SELECT driver_id FROM Drivers WHERE status = 'available' LIMIT 1");
                $stmt_driver->execute();
                $stmt_driver->store_result();

                if ($stmt_driver->num_rows > 0) {
                    $stmt_driver->bind_result($driver_id);
                    $stmt_driver->fetch();

                    // Insert into RideBookings table
                    $bookingDate = $pickupDate;
                    $pickupTime = '09:00:00'; // you can dynamically take this from user later
                    $dropoffTime = '10:00:00'; // default drop off for demo

                    $insert = $conn->prepare("
                        INSERT INTO RideBookings (customer_id, driver_id, pickup_location, destination, fare, booking_date, pickup_time, dropoff_time, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
                    ");
                    $insert->bind_param("iissdsss", $customer_id, $driver_id, $pickup, $drop, $fare, $bookingDate, $pickupTime, $dropoffTime);

                    if ($insert->execute()) {
                        echo "<script>alert('Booking created successfully.');</script>";

                        // Update driver status to booked
                        $conn->query("UPDATE Drivers SET status = 'booked' WHERE driver_id = $driver_id");

                        echo "<script>
                            var email = '".$email."';
                            var name = '".$fname."';
                            window.onload = function() {
                                sendEmail(email, name);
                            };
                        </script>";
                    } else {
                        echo "<script>alert('Error inserting booking: " . $insert->error . "');</script>";
                    }
                    $insert->close();

                } else {
                    echo "<script>alert('No available drivers.');</script>";
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
        echo "<script>alert('Please fill all required fields.');</script>";
    }
}
$db->closeConnection();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Book Ride</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="font/flaticon.css">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <link rel="stylesheet" href="css/BookingRide.css">
</head>
<body>
    <div class="container">
        <div class="book">
            <div class="description">
                <h1><strong>Book</strong> your Ride</h1>
                <p>Resident Cab Service</p>
                <div class="quote"></div>
                <ul>
                    <li>Super reliable service</li>
                    <li>24/7 customer service</li>
                    <li>GPS tracking and help</li>
                    <li>Wide range vehicle</li>
                </ul>
            </div>
            <div class="form">
                <form action="#" method="POST">
                    <div class="inpbox full">
                        <span class="flaticon-taxi"></span>
                        <select id="VehicleType" name="VehicleType" required>
                            <option value="">Select Vehicle</option>
                            <option value="Prius">Prius</option>
                            <option value="Axio">Axio</option>
                            <option value="Vezel">Vezel</option>
                            <option value="Audi">Audi</option>
                        </select>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-globe"></span>
                        <input name="pickup" type="text" placeholder="Pickup Location" required>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-calendar"></span>
                        <input name="PickupDate" type="date" placeholder="Pickup Date" required>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-location"></span>
                        <input name="drop" type="text" placeholder="Drop Location" required>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-calendar"></span>
                        <input name="dropDate" type="date" placeholder="Drop Date" required>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-user"></span>
                        <input name="fname" type="text" placeholder="Full Name" required>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-email"></span>
                        <input name="email" type="email" placeholder="Email" required>
                    </div>
                    <div class="inpbox full">
                        <div class="inrbox">
                            <span class="flaticon-taxi"> Regular</span>
                            <input value="regular" type="radio" name="plan" required>
                            <p><b>LKR.5000</b></p>
                            <span>1 Passenger</span>
                        </div>
                        <div class="inrbox">
                            <span class="flaticon-taxi"> Pro</span>
                            <input value="pro" type="radio" name="plan" required>
                            <p><b>LKR.10000</b></p>
                            <span>2 Passenger</span>
                        </div>
                        <div class="inrbox">
                            <span class="flaticon-taxi"> Advance</span>
                            <input value="advance" type="radio" name="plan" required>
                            <p><b>LKR.15000</b></p>
                            <span>4 Passenger</span>
                        </div>
                    </div>
                    <button class="subt" type="submit">Submit</button>
                    <button class="rst" type="reset">Reset</button>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="text/javascript">
        (function() {
            // Initialize EmailJS
            emailjs.init({
                publicKey: "IMBYADrcMPyNYpISy",
            });
        })();
    </script>

    <script>
        function sendEmail(to_email, to_name) {
            emailjs.send("service_e0nxrqb", "template_pjejhnt", {
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
