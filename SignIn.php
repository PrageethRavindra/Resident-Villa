<?php
// Include the class file for the database connection
require_once __DIR__ . './db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create an instance of the DatabaseConnection class
$db = new DatabaseConnection();
$conn = $db->conn; // You can now access the connection via $db->conn

session_start(); // Start the session at the very beginning

// Backend validation for login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        // Sanitize inputs
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Prepare a SQL statement to check user credentials
            $sql = "SELECT email, password, fname, lname, role FROM signup WHERE email=?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind parameter to the prepared statement
                $stmt->bind_param("s", $email);
                // Execute the prepared statement
                $stmt->execute();
                // Store result
                $result = $stmt->get_result();

                // Check if there is a matching user
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();

                    // Verify the password
                    if (password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['email'] = $email;
                        $_SESSION['name'] = $user['fname'] . ' ' . $user['lname']; // Store the user's name
                        $_SESSION['role'] = $user['role']; // Store the user's role

                        // Redirect based on role
                        if ($user['role'] === 'admin') {
                            header("Location: admin.php"); // Redirect to admin dashboard
                        } else {
                            header("Location: index.php"); // Redirect to user index page
                        }
                        exit();
                    } else {
                        // Incorrect password
                        $error_message = "Login failed. Incorrect email or password.";
                    }
                } else {
                    // No matching user found
                    $error_message = "Login failed. Incorrect email or password.";
                }

                // Close statement
                $stmt->close();
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            // Invalid email format
            $error_message = "Invalid email format.";
        }
    } else {
        // Missing fields
        $error_message = "Please fill in all required fields.";
    }
}

// Close the connection when done
$db->closeConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Resident Villa</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
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
</head>

<body>
    <div class="container" id="container">
        <div class="form-container sign-in-container">
            <form action="signin.php" method="post" onsubmit="return validateLoginForm();">
                <h1>Sign in</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your account</span>

                <!-- Email input field with validation -->
                <input id="email" name="email" type="email" placeholder="Email" required />
                
                <!-- Password input field with validation -->
                <input id="password" name="password" type="password" placeholder="Password" required />
                
                <a href="#">Forgot your password?</a>
                <button type="submit">Sign In</button>
                

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger mt-3"><?php echo $error_message; ?></div>
                <?php endif; ?>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us, please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Customer!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="ghost" id="signUp" onclick="redirectToSignUpPage()">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>Created with <i class="fa fa-heart"></i> by <a target="_blank" href="https://florin-pop.com">Florin Pop</a></p>
    </footer>

    <!-- JavaScript for frontend validation -->
    <script>
        function validateLoginForm() {
            var email = document.getElementById("email").value;
            var password = document.getElementById("password").value;
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;  // Regular expression for email validation

            if (email == "" || password == "") {
                alert("Please fill in both email and password.");
                return false;
            }

            if (!emailPattern.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }

            return true;  // If all validation passes
        }

        function redirectToSignUpPage() {
            window.location.href = "Register.php";
        }
    </script>
</body>
</html>





