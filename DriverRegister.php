<?php
// Enable error reporting for debugging (disable display_errors in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display to prevent HTML output in AJAX
ini_set('log_errors', 1); // Log errors to file
ini_set('error_log', __DIR__ . '/error.log'); // Specify error log file

// Start session for user authentication and message storage
session_start();

// Include the DatabaseConnection class
require_once __DIR__ . '/db/DatabaseConnection.php';

// Create database connection
try {
    $db = new DatabaseConnection();
    $conn = $db->conn;
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Initialize message variables
$message = '';
$messageType = '';

// Check for session messages from redirect
if (isset($_SESSION['message']) && isset($_SESSION['messageType'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    try {
        switch ($_POST['action']) {
            case 'add_driver':
                if (!empty($_POST['full_name']) && !empty($_POST['license_number']) && !empty($_POST['vehicle_number']) && !empty($_POST['phone'])) {
                    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
                    $license_number = filter_var($_POST['license_number'], FILTER_SANITIZE_STRING);
                    $vehicle_number = filter_var($_POST['vehicle_number'], FILTER_SANITIZE_STRING);
                    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
                    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
                    $contact_email = !empty($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
                    $admin_password = !empty($_POST['password']) ? filter_var($_POST['password'], FILTER_SANITIZE_STRING) : '';

                    if ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
                        $message = "Invalid email address.";
                        $messageType = "danger";
                        $email_data = null;
                    } else {
                        $conn->begin_transaction();

                        $stmt = $conn->prepare("INSERT INTO Drivers (full_name, license_number, vehicle_number, phone, status) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $full_name, $license_number, $vehicle_number, $phone, $status);

                        if ($stmt->execute()) {
                            $driver_id = $conn->insert_id;

                            $name_parts = explode(' ', trim($full_name));
                            $first_name = $name_parts[0];
                            $last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';

                            $generated_email = "driver_$driver_id@residentvilla.com";
                            // Use admin-entered password if provided, otherwise generate a random one
                            $raw_password = $admin_password ?: bin2hex(random_bytes(8));
                            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

                            $user_stmt = $conn->prepare("INSERT INTO UserAccounts (first_name, last_name, email, password_hash, user_role) VALUES (?, ?, ?, ?, ?)");
                            $user_role = 'customer';
                            $user_stmt->bind_param("sssss", $first_name, $last_name, $generated_email, $password_hash, $user_role);

                            if ($user_stmt->execute()) {
                                $conn->commit();
                                $message = "Driver registered successfully!";
                                $messageType = "success";
                                // Prepare email content using the provided template
                                $email_template = "Dear {{to_name}},\n\nYour driver account has been successfully created with Resident Villa. Below are your login credentials:\n\nEmail: {{to_email}}\nPassword: {{password}}\n\nPlease log in at https://residentvilla.com/login and change your password upon first login.\n\nFor support, contact support@residentvilla.com.\n\nBest regards,\nThe Resident Villa Team";
                                $email_content = str_replace(
                                    ['{{to_name}}', '{{to_email}}', '{{password}}'],
                                    [$full_name, $generated_email, $raw_password],
                                    $email_template
                                );
                                // Debug email content and password
                                error_log("Generated password: $raw_password");
                                error_log("Email content to be sent: $email_content");
                                if (strpos($email_content, $raw_password) === false) {
                                    error_log("Warning: Password not found in email content");
                                }
                                $email_data = [
                                    'to_email' => $contact_email ?: $generated_email,
                                    'to_name' => $full_name,
                                    'password' => $raw_password,
                                    'message' => $email_content
                                ];
                            } else {
                                $conn->rollback();
                                $message = "Error adding driver to user accounts: " . $user_stmt->error;
                                $messageType = "danger";
                                $email_data = null;
                            }
                            $user_stmt->close();
                        } else {
                            $conn->rollback();
                            $message = "Error registering driver: " . $stmt->error;
                            $messageType = "danger";
                            $email_data = null;
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Please fill all required fields.";
                    $messageType = "danger";
                    $email_data = null;
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => $messageType === 'success',
                        'message' => $message,
                        'email_data' => $email_data
                    ]);
                    exit;
                } else {
                    $_SESSION['message'] = $message;
                    $_SESSION['messageType'] = $messageType;
                    if ($email_data) {
                        $_SESSION['email_data'] = $email_data;
                    }
                    header('Location: DriverRegister.php');
                    exit;
                }
                break;

            case 'update_driver':
                if (!empty($_POST['full_name']) && !empty($_POST['license_number']) && !empty($_POST['vehicle_number']) && !empty($_POST['phone']) && !empty($_POST['driver_id'])) {
                    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
                    $license_number = filter_var($_POST['license_number'], FILTER_SANITIZE_STRING);
                    $vehicle_number = filter_var($_POST['vehicle_number'], FILTER_SANITIZE_STRING);
                    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
                    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
                    $driver_id = filter_var($_POST['driver_id'], FILTER_SANITIZE_NUMBER_INT);

                    $stmt = $conn->prepare("UPDATE Drivers SET full_name = ?, license_number = ?, vehicle_number = ?, phone = ?, status = ? WHERE driver_id = ?");
                    $stmt->bind_param("sssssi", $full_name, $license_number, $vehicle_number, $phone, $status, $driver_id);

                    if ($stmt->execute()) {
                        $message = "Driver updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating driver: " . $stmt->error;
                        $messageType = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill all required fields.";
                    $messageType = "danger";
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => $messageType === 'success', 'message' => $message]);
                    exit;
                } else {
                    $_SESSION['message'] = $message;
                    $_SESSION['messageType'] = $messageType;
                    header('Location: DriverRegister.php');
                    exit;
                }
                break;

            case 'delete_driver':
                if (!empty($_POST['driver_id'])) {
                    $driver_id = filter_var($_POST['driver_id'], FILTER_SANITIZE_NUMBER_INT);

                    $stmt = $conn->prepare("DELETE FROM Drivers WHERE driver_id = ?");
                    $stmt->bind_param("i", $driver_id);

                    if ($stmt->execute()) {
                        $message = "Driver deleted successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error deleting driver: " . $stmt->error;
                        $messageType = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Driver ID is required.";
                    $messageType = "danger";
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => $messageType === 'success', 'message' => $message]);
                    exit;
                } else {
                    $_SESSION['message'] = $message;
                    $_SESSION['messageType'] = $messageType;
                    header('Location: DriverRegister.php');
                    exit;
                }
                break;

            default:
                $message = "Invalid action.";
                $messageType = "danger";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $message]);
                    exit;
                } else {
                    $_SESSION['message'] = $message;
                    $_SESSION['messageType'] = $messageType;
                    header('Location: DriverRegister.php');
                    exit;
                }
        }
    } catch (Exception $e) {
        error_log("Action error: " . $e->getMessage());
        $message = "Server error: " . $e->getMessage();
        $messageType = "danger";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        } else {
            $_SESSION['message'] = $message;
            $_SESSION['messageType'] = $messageType;
            header('Location: DriverRegister.php');
            exit;
        }
    }
}

// Fetch all drivers
try {
    $drivers_query = "SELECT * FROM Drivers ORDER BY driver_id DESC";
    $drivers_result = $conn->query($drivers_query);

    if (!$drivers_result) {
        $message = "Error fetching drivers: " . $conn->error;
        $messageType = "danger";
    }
} catch (Exception $e) {
    error_log("Driver query error: " . $e->getMessage());
    $message = "Error fetching drivers: " . $e->getMessage();
    $messageType = "danger";
}

// Check for email data to send after redirect
$email_data = isset($_SESSION['email_data']) ? $_SESSION['email_data'] : null;
if ($email_data) {
    unset($_SESSION['email_data']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - Resident Villa</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #111111;
            --bg-tertiary: #1a1a1a;
            --bg-card: #161616;
            --border-color: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --text-muted: #666666;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --accent-purple: #8b5cf6;
            --accent-red: #ef4444;
            --hover-bg: #1f1f1f;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 73px;
            width: 250px;
            height: calc(100vh - 73px);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 2rem 0;
            z-index: 90;
            transition: transform 0.3s ease;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: var(--hover-bg);
            color: var(--text-primary);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: white;
        }

        .nav-icon {
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 73px);
        }

        .breadcrumb-container {
            margin-bottom: 2rem;
        }

        .breadcrumb {
            display: flex;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .breadcrumb a {
            color: var(--accent-blue);
            text-decoration: none;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--hover-bg);
            border-color: var(--accent-blue);
        }

        .btn-primary {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .btn-warning {
            background: var(--accent-yellow);
            border-color: var(--accent-yellow);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
        }

        .btn-danger {
            background: var(--accent-red);
            border-color: var(--accent-red);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--hover-bg);
            border-color: var(--accent-blue);
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.875rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table td {
            color: var(--text-primary);
        }

        .table tr:hover {
            background: var(--hover-bg);
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--accent-green); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--accent-yellow); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--accent-red); }

        .form-control {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23a0a0a0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
        }

        .alert {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            position: relative;
            display: none;
        }

        .alert-success {
            border-color: var(--accent-green);
            background: rgba(16, 185, 129, 0.1);
        }

        .alert-danger {
            border-color: var(--accent-red);
            background: rgba(239, 68, 68, 0.1);
        }

        .btn-close {
            background: transparent;
            color: var(--text-secondary);
            opacity: 0.7;
            position: absolute;
            right: 1rem;
            top: 1rem;
            border: none;
            cursor: pointer;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--bg-card);
            margin: 5% auto;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            color: var(--text-primary);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-body {
            margin-bottom: 1rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .close {
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .close:hover {
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .col-md-4 {
            flex: 0 0 33.333%;
            max-width: 33.333%;
        }

        .col-md-8 {
            flex: 0 0 66.667%;
            max-width: 66.667%;
        }

        @media (max-width: 768px) {
            .col-md-4, .col-md-8 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .loading {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-building"></i> Resident Villa
        </div>
        <div class="header-info">
            <i class="fas fa-user-circle"></i>
            <span>Driver Management</span>
            <span>|</span>
            <span><?php echo date('M d, Y H:i'); ?></span>
        </div>
    </header>

    <nav class="sidebar">
        <div class="nav-item">
            <a class="nav-link" href="admin.php">
                <i class="fas fa-chart-line nav-icon"></i>
                <span>Overview</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="RoomBookingDashBoard.php">
                <i class="fas fa-bed nav-icon"></i>
                <span>Room Bookings</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="RideBookingDashBoard.php">
                <i class="fas fa-car nav-icon"></i>
                <span>Ride Bookings</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="CustomerDashBoard.php">
                <i class="fas fa-users nav-icon"></i>
                <span>Customers</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link active" href="DriverRegister.php">
                <i class="fas fa-id-card nav-icon"></i>
                <span>Driver Management</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="UserAccountsDashboard.php">
                <i class="fas fa-user-cog nav-icon"></i>
                <span>User Accounts</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="breadcrumb-container">
            <nav class="breadcrumb">
                <a href="admin.php">Home</a>
                <span>/</span>
                <span>Driver Management</span>
            </nav>
        </div>

        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-id-card"></i> Driver Management</h1>
            <button class="btn">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>

        <div id="alert" class="alert <?php echo $message ? 'alert-' . $messageType : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close"></button>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i>
                        Register New Driver
                    </div>
                    <div class="card-body">
                        <form id="addDriverForm" method="POST" action="">
                            <input type="hidden" name="action" value="add_driver">
                            
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" name="full_name" id="full_name" placeholder="Enter full name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="license_number">License Number</label>
                                <input type="text" class="form-control" name="license_number" id="license_number" placeholder="Enter license number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="vehicle_number">Vehicle Number</label>
                                <input type="text" class="form-control" name="vehicle_number" id="vehicle_number" placeholder="Enter vehicle number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" id="phone" placeholder="Enter phone number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Contact Email (Optional)</label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Enter contact email">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password (Optional)</label>
                                <input type="password" class="form-control" name="password" id="password" placeholder="Enter password or leave blank to auto-generate">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status" required>
                                    <option value="available">Available</option>
                                    <option value="booked">Booked</option>
                                    <option value="off-duty">Off-duty</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Register Driver
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-list"></i>
                        Registered Drivers
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>License</th>
                                        <th>Vehicle</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($drivers_result) && $drivers_result->num_rows > 0): ?>
                                        <?php while ($driver = $drivers_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($driver['driver_id']); ?></td>
                                                <td><?php echo htmlspecialchars($driver['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                                <td><?php echo htmlspecialchars($driver['vehicle_number']); ?></td>
                                                <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $driver['status'] == 'available' ? 'success' : ($driver['status'] == 'booked' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($driver['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick='editDriver(<?php echo htmlspecialchars(json_encode($driver)); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteDriver(<?php echo $driver['driver_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-id-card fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No drivers found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="editDriverModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Driver</h2>
                <span class="close" onclick="closeEditModal()">×</span>
            </div>
            <form id="editDriverForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_driver">
                    <input type="hidden" name="driver_id" id="edit_driver_id">
                    
                    <div class="form-group">
                        <label for="edit_full_name">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_license_number">License Number</label>
                        <input type="text" class="form-control" name="license_number" id="edit_license_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_vehicle_number">Vehicle Number</label>
                        <input type="text" class="form-control" name="vehicle_number" id="edit_vehicle_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" name="status" id="edit_status" required>
                            <option value="available">Available</option>
                            <option value="booked">Booked</option>
                            <option value="off-duty">Off-duty</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Driver
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
                <span class="close" onclick="closeDeleteModal()">×</span>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this driver?
            </div>
            <form id="deleteDriverForm" method="POST" action="">
                <input type="hidden" name="action" value="delete_driver">
                <input type="hidden" name="driver_id" id="delete_driver_id">
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script type="text/javascript">
        // Initialize EmailJS
        (function() {
            emailjs.init({
                publicKey: "N_fSwhcbsNOCtVYHC"
            });
        })();

        // Function to send email with driver credentials
        function sendEmail(to_email, to_name, password, message) {
            console.log('Sending email with:', { to_email, to_name, password, message }); // Debug log
            emailjs.send("service_ph6rrcl", "template_d7x63dh", {
                to_name: to_name,
                to_email: to_email,
                password: password,
                message: message
            })
            .then(function(response) {
                console.log('Email sent successfully:', response);
                showAlert('Driver credentials emailed successfully!', 'success');
            }, function(error) {
                console.error('Email sending failed:', error);
                showAlert('Failed to send email: ' + error.text, 'danger');
            });
        }

        // Modal functions
        function editDriver(driver) {
            document.getElementById('edit_driver_id').value = driver.driver_id;
            document.getElementById('edit_full_name').value = driver.full_name;
            document.getElementById('edit_license_number').value = driver.license_number;
            document.getElementById('edit_vehicle_number').value = driver.vehicle_number;
            document.getElementById('edit_phone').value = driver.phone;
            document.getElementById('edit_status').value = driver.status;
            document.getElementById('editDriverModal').style.display = 'block';
        }

        function deleteDriver(driverId) {
            document.getElementById('delete_driver_id').value = driverId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editDriverModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Show alert messages
        function showAlert(message, type) {
            console.log('Showing alert:', message, type);
            const alert = document.getElementById('alert');
            alert.className = `alert alert-${type} fade-in`;

            // Use safe innerHTML and escape message
            const safeMessage = document.createTextNode(message);
            alert.innerHTML = ''; // Clear previous content
            alert.appendChild(safeMessage);

            // Create close button
            const closeBtn = document.createElement('button');
            closeBtn.setAttribute('type', 'button');
            closeBtn.className = 'btn-close';
            closeBtn.onclick = () => {
                alert.style.display = 'none';
            };

            alert.appendChild(closeBtn);
            alert.style.display = 'block';

            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Form submission handlers
        document.getElementById('addDriverForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Registering...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('DriverRegister.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Fetch response:', data);
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                if (data.success) {
                    showAlert('Driver registered successfully!', 'success');
                    if (data.email_data) {
                        sendEmail(
                            data.email_data.to_email,
                            data.email_data.to_name,
                            data.email_data.password,
                            data.email_data.message
                        );
                    }
                    this.reset();
                    refreshDriverTable();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        document.getElementById('editDriverForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Updating...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('DriverRegister.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeEditModal();
                    refreshDriverTable();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        document.getElementById('deleteDriverForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Deleting...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('DriverRegister.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeDeleteModal();
                    refreshDriverTable();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        // Refresh driver table without page reload
        function refreshDriverTable() {
            fetch('DriverRegister.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.table-container table tbody');
                document.querySelector('.table-container table tbody').innerHTML = newTable.innerHTML;
            })
            .catch(error => {
                console.error('Table refresh error:', error);
                showAlert('Error refreshing table: ' + error.message, 'danger');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Handle email sending for non-AJAX requests
            <?php if ($email_data): ?>
                console.log('Non-AJAX email data:', <?php echo json_encode($email_data); ?>);
                sendEmail(
                    '<?php echo htmlspecialchars($email_data['to_email'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($email_data['to_name'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($email_data['password'], ENT_QUOTES); ?>',
                    '<?php echo htmlspecialchars($email_data['message'], ENT_QUOTES); ?>'
                );
            <?php endif; ?>

            // Close modals when clicking outside
            window.onclick = function(event) {
                const editModal = document.getElementById('editDriverModal');
                const deleteModal = document.getElementById('deleteModal');
                if (event.target === editModal) {
                    closeEditModal();
                }
                if (event.target === deleteModal) {
                    closeDeleteModal();
                }
            };

            // Close alert on button click
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-close')) {
                    e.target.parentElement.style.display = 'none';
                }
            });

            // Mobile menu toggle
            const createMobileToggle = () => {
                const header = document.querySelector('.header');
                const sidebar = document.querySelector('.sidebar');
                
                const toggleBtn = document.createElement('button');
                toggleBtn.innerHTML = '<i class="fas fa-arrow-right"></i>';
                toggleBtn.style.cssText = `
                    background: none;
                    border: none;
                    color: var(--text-primary);
                    font-size: 1.25rem;
                    cursor: pointer;
                    padding: 0.5rem;
                    display: none;
                `;
                toggleBtn.style.setProperty('@media (max-width: 768px)', 'display: block;');
                
                toggleBtn.addEventListener('click', () => {
                    sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' 
                        ? 'translateX(-100%)' 
                        : 'translateX(0px)';
                });
                
                header.insertBefore(toggleBtn, header.firstChild);
            };
            
            if (window.innerWidth <= 768) {
                createMobileToggle();
            }

            // Real-time clock update
            const updateClock = () => {
                const now = new Date();
                const timeString = now.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const clockElement = document.querySelector('.header-info span:last-child');
                if (clockElement) {
                    clockElement.textContent = timeString;
                }
            };
            setInterval(updateClock, 60000);
            updateClock();

            // Intersection observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.card, .alert').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });

            // Hover effects for nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(8px)';
                });
                link.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('active')) {
                        this.style.transform = 'translateX(0)';
                    }
                });
            });

            // Export button functionality
            const exportBtn = document.querySelector('.btn i.fa-download').parentElement;
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<div class="spinner"></div> Exporting...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-check"></i> Exported!';
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 2000);
                    }, 1500);
                });
            }

            // Show initial PHP message if any
            <?php if ($message): ?>
                showAlert('<?php echo htmlspecialchars($message); ?>', '<?php echo $messageType; ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>