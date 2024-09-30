<?php
// Include the class file for the database connection
require_once __DIR__ . './db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create an instance of the DatabaseConnection class
$db = new DatabaseConnection();
$conn = $db->conn; // You can now access the connection via $db->conn

// Backend validation for the ride registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure required fields are filled
    if (!empty($_POST['fname']) && !empty($_POST['email'])) {
        
        // Sanitize and validate inputs
        $vehicleType = filter_var($_POST['VehicleType'], FILTER_SANITIZE_STRING);
        $pickup = filter_var($_POST['pickup'], FILTER_SANITIZE_STRING);
        $pickupDate = filter_var($_POST['PickupDate'], FILTER_SANITIZE_STRING);
        $drop = filter_var($_POST['drop'], FILTER_SANITIZE_STRING);
        $dropDate = filter_var($_POST['dropDate'], FILTER_SANITIZE_STRING);
        $fname = filter_var($_POST['fname'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $packageType = isset($_POST['plan']) ? filter_var($_POST['plan'], FILTER_SANITIZE_STRING) : '';

        // Validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Prepare the SQL query for insertion
            $stmt = $conn->prepare("INSERT INTO ridetb (vehicleType, pickUp, dropOff, packageType, PickupDate, DropDate, Fname, Email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            // Check if the statement was prepared successfully
            if ($stmt === false) {
                die("Error preparing statement: " . $conn->error);
            }

            $stmt->bind_param("ssssssss", $vehicleType, $pickup, $drop, $packageType, $pickupDate, $dropDate, $fname, $email);

            // Execute the query
            if ($stmt->execute()) {
                // Notify the user of success
                echo "<script>alert('New record created successfully.');</script>";
                
                // Pass the email and name to a JavaScript variable and call sendEmail later
                echo "<script>
                    var email = '".$email."';
                    var name = '".$fname."';
                    window.onload = function() {
                        sendEmail(email, name);
                    };
                </script>";
            } else {
                // Handle any errors that occur during insertion
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }

            // Close the prepared statement
            $stmt->close();
        } else {
            // If the email format is invalid
            echo "<script>alert('Invalid email format.');</script>";
        }
    } else {
        // If any required fields are missing
        echo "<script>alert('Please fill all required fields.');</script>";
    }
}

// Close the connection when done
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
