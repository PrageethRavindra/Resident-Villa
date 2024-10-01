<?php
// Include the class file for the database connection
require_once __DIR__ . './db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create an instance of the DatabaseConnection class
$db = new DatabaseConnection();
$conn = $db->conn; // You can now access the connection via $db->conn

// Backend validation for the driver registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure all required fields are filled
    if (!empty($_POST['email']) && !empty($_POST['fname']) && !empty($_POST['lname']) && !empty($_POST['vehicle_type']) && !empty($_POST['phone']) && !empty($_POST['VehicleNo'])) {
        
        // Sanitize and validate inputs
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $fname = filter_var($_POST['fname'], FILTER_SANITIZE_STRING);
        $lname = filter_var($_POST['lname'], FILTER_SANITIZE_STRING);
        $vehicleType = filter_var($_POST['vehicle_type'], FILTER_SANITIZE_STRING);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $vehicleNo = filter_var($_POST['VehicleNo'], FILTER_SANITIZE_STRING);

        // Validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Prepare the SQL query for insertion
            $sql = "INSERT INTO drivertb (email, fname, lname, vehicle_type, phone, vehicle_no) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind the parameters to the prepared statement
                $stmt->bind_param("ssssss", $email, $fname, $lname, $vehicleType, $phone, $vehicleNo);

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
                // Handle preparation errors
                echo "Error: " . $conn->error;
            }
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
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Resident Villa</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS here -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/Register.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Add your custom styles here */
        .overlay-container {
            flex: 1;
            background-color: #0064fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            background: #0064fa;
            background: -webkit-linear-gradient(to right, #0064fa, #0064fa);
            background: linear-gradient(to right, #0064fa, #0064fa);
            color: #ffffff;
            width: 100%;
            max-width: 400px; /* Adjust as needed */
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25), 0 10px 10px rgba(0, 0, 0, 0.22);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-panel {
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .overlay-container {
                width: 100%;
            }
            .overlay {
                width: 80%;
            }
        }

        /* Style for the combo box */
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        select[name="vehicle_type"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        /* Keep the previous style for the button */
        button#signUp {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>
<div class="container" id="container">
    <div class="form-container sign-up-container">
        <form action="DriverRegister.php" method="post">
            <h1>Driver Registration</h1>
            <div class="social-container">
                <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
            </div>
            <span>or use your email for registration</span>
            <input type="text" name="fname" placeholder="First Name" required/>
            <input type="text" name="lname" placeholder="Last Name" required/>
            <input type="number" name="phone" placeholder="Phone No" required/>
           
            <select name="vehicle_type" required>
                <option value="" disabled selected>Select Vehicle Type</option>
                <option value="car">Car</option>
                <option value="tuck">Tuk</option>
                <option value="van">Van</option>
            </select>
        
            <input type="email" name="email" placeholder="Email" required/>
            <input type="text" name="VehicleNo" placeholder="VehicleNo" required/>
            <button type="submit" class="ghost" id="register">Register</button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/vendor/Register.js"></script>

    <script>
        // You can add JavaScript code here if needed
    </script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="text/javascript">
        (function() {
            // Initialize EmailJS
            emailjs.init({
                publicKey: "YhHXgvR7VQvQoE4VR",
            });
        })();
    </script>

    <script>
        function sendEmail(to_email, to_name) {
            emailjs.send("service_71f1uuo", "template_lwdo2vk", {
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

