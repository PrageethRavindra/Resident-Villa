

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
    </style>
</head>

<body>
<div class="container" id="container">
    <div class="form-container sign-up-container">
        <form action="#" method="post">
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
            <a href="SignIn.php"><button type="button" class="ghost" id="signUp">Sign Up</button></a>
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
            publicKey: "5w7dn8PaLxbtvDb_O",
        });
    })();
</script>

<script>
    function sendEmail(to_email, to_name) {
      emailjs.send("service_e0nxrqb","template_sgxfkcd",{
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

<?php
// Establish connection to MySQL database
$servername = "localhost:3305"; // Change as per your configuration
$username = "prageeth"; // Change as per your configuration
$password = "123@Admin"; // Change as per your configuration
$dbname = "resident_villa"; // Change as per your configuration

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (isset($_POST['email']) && isset($_POST['country'])) {
    // Retrieve email and password from the form
    $email = $_POST['email'];
    $password = $_POST['country'];

// Retrieve values from POST data
$email = $_POST['email'];
$fname = $_POST['fname'];
$lname = $_POST['lname'];
$country = $_POST['country'];
$phone = $_POST['phone'];
$password = $_POST['password'];

// SQL query to insert data into the database
$sql = "INSERT INTO signup (email, fname, lname, country, phone, password) VALUES ('$email', '$fname', '$lname', '$country', '$phone', '$password')";


if ($conn->query($sql) === TRUE) {
    // If record inserted successfully
    echo "<script>alert('New record created successfully');</script>";
    echo "<script>sendEmail('".$email."','".$fname."');</script>";
} else {
    // If an error occurred
    echo "<script>alert('Error: " . $sql . "\\n" . $conn->error . "');</script>";
}
}
$conn->close();
?>
