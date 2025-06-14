<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Villa - Join Us</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --shadow-soft: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 15px 35px rgba(0, 0, 0, 0.2);
            --shadow-strong: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated background elements */
        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .shape-1 {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Main container */
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .registration-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--shadow-strong);
            padding: 40px;
            width: 100%;
            max-width: 480px;
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.8s ease-out forwards;
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: var(--accent-gradient);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: var(--shadow-medium);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo i {
            font-size: 24px;
            color: white;
        }

        h1 {
            color: var(--text-primary);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 400;
        }

        /* Social login section */
        .social-section {
            margin: 32px 0;
        }

        .social-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .social-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            background: var(--glass-bg);
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
            color: var(--text-primary);
            text-decoration: none;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }

        .divider span {
            padding: 0 16px;
        }

        /* Form styles */
        .form-grid {
            display: grid;
            gap: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .input-group {
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px;
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            background: var(--glass-bg);
            color: var(--text-primary);
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .input-field:focus {
            outline: none;
            border-color: rgba(79, 172, 254, 0.6);
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .input-field::placeholder {
            color: var(--text-secondary);
        }

        /* Submit button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: var(--accent-gradient);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 24px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Loading state */
        .loading {
            position: relative;
            color: transparent;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Sign-in link */
        .signin-link {
            text-align: center;
            margin-top: 24px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .signin-link a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .signin-link a:hover {
            color: #4facfe;
        }

        /* Success/Error messages */
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        .message.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .message.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Email loading modal */
        .email-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .email-modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            color: var(--text-primary);
            max-width: 400px;
            margin: 20px;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            background: var(--accent-gradient);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            animation: spin 2s linear infinite;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .registration-card {
                margin: 0;
                padding: 32px 24px;
                border-radius: 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 24px;
            }

            .social-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 16px;
            }

            .registration-card {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background -->
    <div class="background-animation">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
    </div>

    <div class="main-container">
        <div class="registration-card">
            <!-- Logo section -->
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-home"></i>
                </div>
                <h1>Join Resident Villa</h1>
                <p class="subtitle">Create your account and discover your perfect home</p>
            </div>

            <!-- Social login -->
            <div class="social-section">
                <div class="social-buttons">
                    <a href="#" class="social-btn">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-btn">
                        <i class="fab fa-apple"></i>
                    </a>
                </div>
                
                <div class="divider">
                    <span>or continue with email</span>
                </div>
            </div>

            <!-- Registration form -->
            <form id="registrationForm" method="post">
                <div class="form-grid">
                    <div class="form-row">
                        <div class="input-group">
                            <input type="text" name="fname" class="input-field" placeholder="First Name" required>
                        </div>
                        <div class="input-group">
                            <input type="text" name="lname" class="input-field" placeholder="Last Name" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <input type="email" name="email" class="input-field" placeholder="Email Address" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <input type="tel" name="phone" class="input-field" placeholder="Phone Number" required>
                        </div>
                        <div class="input-group">
                            <input type="text" name="country" class="input-field" placeholder="Country" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <input type="password" name="password" class="input-field" placeholder="Create Password" required>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="signin-link">
                Already have an account? <a href="signIn.php">Sign in here</a>
            </div>
        </div>
    </div>

    <!-- Email loading modal -->
    <div class="email-modal" id="emailModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Sending Welcome Email</h3>
            <p>Please wait while we send your welcome email...</p>
        </div>
    </div>

    <!-- EmailJS Script -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
        // Initialize EmailJS
        emailjs.init("IMBYADrcMPyNYpISy");

        // Form handling
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const formData = new FormData(this);
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Simulate form submission (replace with actual PHP handling)
            setTimeout(() => {
                // Simulate successful registration
                showMessage('Registration successful! Redirecting to sign in...', 'success');
                
                // Send welcome email for customers
                const email = formData.get('email');
                const fname = formData.get('fname');
                const lname = formData.get('lname');
                
                if (!email.startsWith('admin@')) {
                    sendWelcomeEmail(email, fname, lname);
                }
                
                // Reset form
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = 'signIn.php';
                }, 3000);
                
            }, 2000);
        });

        function sendWelcomeEmail(email, firstName, lastName) {
            const modal = document.getElementById('emailModal');
            modal.classList.add('active');
            
            const templateParams = {
                to_email: email,
                to_name: firstName + ' ' + lastName,
                first_name: firstName,
                last_name: lastName
            };

            emailjs.send("service_e0nxrqb", "template_sgxfkcd", templateParams)
                .then(function(response) {
                    console.log('Welcome email sent successfully');
                    modal.classList.remove('active');
                })
                .catch(function(error) {
                    console.error('Email sending failed:', error);
                    modal.classList.remove('active');
                });
        }

        function showMessage(text, type) {
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.textContent = text;
            
            const form = document.getElementById('registrationForm');
            form.parentNode.insertBefore(message, form);
            
            setTimeout(() => {
                message.remove();
            }, 5000);
        }

        // Enhanced input interactions
        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Smooth page transitions
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>

    <?php
    // Keep the original PHP logic here
    require_once __DIR__ . '/db/DatabaseConnection.php';

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $db = new DatabaseConnection();
    $conn = $db->conn;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST['email']) && !empty($_POST['fname']) && !empty($_POST['lname']) && !empty($_POST['country']) && !empty($_POST['phone']) && !empty($_POST['password'])) {
            
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $fname = filter_var($_POST['fname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $lname = filter_var($_POST['lname'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $country = filter_var($_POST['country'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $phone = filter_var($_POST['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'];

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $role = (strpos($email, 'admin@') === 0) ? 'admin' : 'customer';

                $sql = "INSERT INTO UserAccounts (first_name, last_name, email, password_hash, user_role) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("sssss", $fname, $lname, $email, $hashedPassword, $role);

                    if ($stmt->execute()) {
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showMessage('Registration successful! Redirecting to sign in...', 'success');
                                
                                if ('$role' === 'customer') {
                                    sendWelcomeEmail('$email', '$fname', '$lname');
                                }
                                
                                setTimeout(function() {
                                    window.location.href = 'signIn.php';
                                }, 3000);
                            });
                        </script>";
                    } else {
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showMessage('Error: " . addslashes($stmt->error) . "', 'error');
                            });
                        </script>";
                    }

                    $stmt->close();
                } else {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showMessage('Database error occurred', 'error');
                        });
                    </script>";
                }
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showMessage('Invalid email format', 'error');
                    });
                </script>";
            }
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showMessage('Please fill all required fields', 'error');
                });
            </script>";
        }
    }

    $db->closeConnection();
    ?>
</body>
</html>