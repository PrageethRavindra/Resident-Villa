<?php
// Include the class file for the database connection
require_once __DIR__ . './db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create an instance of the DatabaseConnection class
$db = new DatabaseConnection();
$conn = $db->conn; // You can now access the connection via $db->conn

// Backend validation for the registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure all required fields are filled
    if (!empty($_POST['email']) && !empty($_POST['fname']) && !empty($_POST['lname']) && !empty($_POST['country']) && !empty($_POST['phone']) && !empty($_POST['password'])) {
        
        // Sanitize and validate inputs
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $fname = filter_var($_POST['fname'], FILTER_SANITIZE_STRING);
        $lname = filter_var($_POST['lname'], FILTER_SANITIZE_STRING);
        $country = filter_var($_POST['country'], FILTER_SANITIZE_STRING);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $password = $_POST['password'];

        // Validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Hash the password for security before inserting it into the database
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Prepare the SQL query for insertion
            $sql = "INSERT INTO signup (email, fname, lname, country, phone, password) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind the parameters to the prepared statement
                $stmt->bind_param("ssssss", $email, $fname, $lname, $country, $phone, $hashedPassword);

                // Execute the query
                if ($stmt->execute()) {
                    // Notify the user of success
                    echo "<script>alert('Registration successful.');</script>";
                    
                    // Pass the email to a JavaScript variable and call sendEmail later
                    echo "<script>
                        var email = '$email';
                        window.onload = function() {
                            sendEmail(email);
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
    </style>
</head>

<body>
<div class="container" id="container">
    <div class="form-container sign-up-container">
        <form action="" method="post"> <!-- Keep action as current file -->
            <h1>Create Account</h1>
            <div class="social-container">
                <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
            </div>
            <span>or use your email for registration</span>
            <input type="text" name="fname" placeholder="First Name" required/>
            <input type="text" name="lname" placeholder="Last Name" required/>
            <input type="number" name="phone" placeholder="Phone No" required/>
            <input type="text" name="country" placeholder="Country" required/>
            <input type="email" name="email" placeholder="Email" required/>
            <input type="password" name="password" placeholder="Password" required/>
            <button type="submit" class="ghost" id="signUp">Sign Up</button>
        </form>
    </div>
</div>

<!-- Load the EmailJS script and initialize it -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script type="text/javascript">
    (function() {
        // Initialize EmailJS with your public key
        emailjs.init("IMBYADrcMPyNYpISy"); 
    })();
</script>

<!-- Define the sendEmail function -->
<script>
    function sendEmail(to_email) {
        console.log("Sending email to:", to_email);
        emailjs.send("service_e0nxrqb", "template_sgxfkcd", {
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
