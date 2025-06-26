<?php
// Include the class file for the database connection
require_once __DIR__ . '/db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Create an instance of the DatabaseConnection class
try {
    $db = new DatabaseConnection();
    $conn = $db->conn;
    if (!$conn) {
        throw new Exception("Database connection is null");
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    $error_message = "Database connection failed. Please try again later.";
}

// Start session
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Hardcoded Admin Logic
            $hardcoded_admin_email = 'admin@villa.com';
            $hardcoded_admin_password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // "password"

            if (strtolower($email) === strtolower($hardcoded_admin_email)) {
                if (password_verify($password, $hardcoded_admin_password_hash)) {
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = 'Admin User';
                    $_SESSION['role'] = 'admin';
                    header("Location: admin.php");
                    exit();
                } else {
                    $error_message = "Login failed. Incorrect email or password.";
                }
            } else {
                // Check database for regular users
                try {
                    $sql = "SELECT email, password_hash, first_name, last_name, user_role FROM UserAccounts WHERE email=?";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();

                        if (password_verify($password, $user['password_hash'])) {
                            $_SESSION['email'] = $email;
                            $_SESSION['name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                            $_SESSION['role'] = $user['user_role'];

                            // Check if user is a driver
                            $driver_sql = "SELECT driver_id FROM Drivers WHERE email=?";
                            $driver_stmt = $conn->prepare($driver_sql);
                            if (!$driver_stmt) {
                                // Fallback: Try matching driver_id with email prefix (e.g., driver_1@residentvilla.com)
                                $email_prefix = explode('@', $email)[0];
                                $driver_sql = "SELECT driver_id FROM Drivers WHERE driver_id = ?";
                                $driver_stmt = $conn->prepare($driver_sql);
                                if (!$driver_stmt) {
                                    throw new Exception("Driver prepare failed: " . $conn->error);
                                }
                                $driver_id = (int)str_replace('driver_', '', $email_prefix);
                                $driver_stmt->bind_param("i", $driver_id);
                            } else {
                                $driver_stmt->bind_param("s", $email);
                            }
                            $driver_stmt->execute();
                            $driver_result = $driver_stmt->get_result();

                            if ($driver_result->num_rows > 0) {
                                // User is a driver, redirect to DriverDashboard
                                $_SESSION['driver_id'] = $driver_result->fetch_assoc()['driver_id'];
                                if (!file_exists(__DIR__ . '/DriverDashboard.php')) {
                                    throw new Exception("DriverDashboard.php not found");
                                }
                                header("Location: DriverDashboard.php");
                            } else if ($user['user_role'] === 'admin') {
                                header("Location: admin.php");
                            } else {
                                header("Location: index.php");
                            }
                            $driver_stmt->close();
                            exit();
                        } else {
                            $error_message = "Login failed. Incorrect email or password.";
                        }
                    } else {
                        $error_message = "Login failed. Incorrect email or password.";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error_message = "Server error occurred. Please try again.";
                }
            }
        } else {
            $error_message = "Invalid email format.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Close database connection
if (isset($db)) {
    try {
        $db->closeConnection();
    } catch (Exception $e) {
        error_log("Error closing database connection: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Resident Villa - Sign In</title>
    <meta name="description" content="Resident Villa Login Portal">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #666;
            font-size: 0.9rem;
            font-weight: 400;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.1rem;
            z-index: 2;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            outline: none;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #667eea;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input::placeholder {
            color: #999;
            font-weight: 400;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 1.5rem;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
            color: #999;
            font-size: 0.9rem;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #ddd, transparent);
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }

        .social-login {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            border: 2px solid #e1e8f0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .social-btn.facebook { color: #1877f2; }
        .social-btn.facebook:hover { background: #1877f2; color: white; border-color: #1877f2; }
        .social-btn.google { color: #ea4335; }
        .social-btn.google:hover { background: #ea4335; color: white; border-color: #ea4335; }
        .social-btn.linkedin { color: #0077b5; }
        .social-btn.linkedin:hover { background: #0077b5; color: white; border-color: #0077b5; }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .signup-link p {
            color: #666;
            font-size: 0.9rem;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #764ba2;
        }

        .alert {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert i {
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
                max-width: none;
            }

            h1 { font-size: 1.75rem; }
            .logo { width: 70px; height: 70px; font-size: 1.75rem; }
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        .shape:nth-child(1) { width: 80px; height: 80px; top: 20%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; top: 60%; right: 10%; animation-delay: 5s; }
        .shape:nth-child(3) { width: 40px; height: 40px; top: 80%; left: 20%; animation-delay: 10s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(30px) rotate(240deg); }
        }
    </style>
</head>

<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-home"></i>
            </div>
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to your Resident Villa account</p>
        </div>

        <form action="SignIn.php" method="post" onsubmit="return validateLoginForm();">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input id="email" name="email" type="email" placeholder="Enter your email address" required />
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input id="password" name="password" type="password" placeholder="Enter your password" required />
            </div>

            <div class="forgot-password">
                <a href="#">Forgot your password?</a>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                Sign In
            </button>

            <?php if (isset($error_message)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="divider">
            <span>or continue with</span>
        </div>

        <div class="social-login">
            <a href="#" class="social-btn facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-btn google"><i class="fab fa-google"></i></a>
            <a href="#" class="social-btn linkedin"><i class="fab fa-linkedin-in"></i></a>
        </div>

        <div class="signup-link">
            <p>Don't have an account? <a href="#" onclick="redirectToSignUpPage()">Create one here</a></p>
        </div>
    </div>

    <script>
        function validateLoginForm() {
            var email = document.getElementById("email").value;
            var password = document.getElementById("password").value;
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email == "" || password == "") {
                alert("Please fill in both email and password.");
                return false;
            }

            if (!emailPattern.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }

            return true;
        }

        function redirectToSignUpPage() {
            window.location.href = "Register.php";
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.querySelector('.login-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Signing In...';
            btn.disabled = true;
        });

        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>