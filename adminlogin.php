<?php
// Establish connection to MySQL database
$servername = "localhost:3305"; // Change as per your configuration
$username = "prageeth"; // Change as per your configuration
$password = "123@Admin"; // Change as per your configuration
$dbname = "resident_villa"; // Change as per your configuration

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (isset($_POST['username']) && isset($_POST['password'])) {
    // Retrieve email and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];
// Retrieve email and password from the form
$username = $_POST['username'];
$password = $_POST['password'];

// Prepare a SQL statement
$sql = "SELECT username, password FROM adminlogin WHERE username=?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind parameter to the prepared statement
    $stmt->bind_param("s", $username);

    // Execute the prepared statement
    $stmt->execute();

    // Store result
    $result = $stmt->get_result();

    // Check if there is a matching user
    if ($result->num_rows > 0) {
        // Fetch the user's details
        $user = $result->fetch_assoc();

        // Verify password
        if ($password == $user['password']) {
            // Password is correct, login successful
            header("Location: admin.php");
        exit(); // Ensure that script execution stops after redirect
        } else {
            // Password is incorrect
            echo "Login failed. Please check your email and password.";
        }
    } else {
        // No matching user found
        echo "Login failed. Please check your email and password.";
    }

    // Close statement
    $stmt->close();
} else {
    // Error in preparing the statement
    echo "Error: " . $conn->error;
}
}
// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Resident Villa</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS here -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/gijgo.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/slicknav.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/AdminLogin.css">
</head>

<body>
<div class="container" id="container">
    
    <div class="form-container sign-in-container">
        <form action="#" method="post">
            <h1><b>Sign in</b></h1>
            <div class="social-container">
                <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
            </div>
            <span>or use your account</span>
            <input name="username" type="Username" placeholder="Username" />
            <input name="password" type="password" placeholder="Password" />
            <a href="admin.php"><button>Sign In</button></a>
        </form>
    </div>
    <div class="overlay-container">
        <div class="overlay">
            <div class="overlay-panel overlay-left">
                <h1>Welcome Back!</h1>
                <p>To keep connected with us please login with your personal info</p>
                <button class="ghost" id="signIn">Sign In</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h1>Hello Admin</h1>
                <p>Resident Villa</p>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>
        Created with <i class="fa fa-heart"></i> by
        <a target="_blank" href="https://florin-pop.com">Florin Pop</a>
        - Read how I created this and how you can join the challenge
        <a target="_blank" href="https://www.florin-pop.com/blog/2019/03/double-slider-sign-in-up-form/">here</a>.
    </p>
</footer>

</body>
</html>
