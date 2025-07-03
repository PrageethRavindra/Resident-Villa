<?php
// Enable error reporting for debugging (disable display_errors in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start session
session_start();

// Check if user is logged in and is a driver
if (!isset($_SESSION['email']) || !isset($_SESSION['driver_id'])) {
    error_log("Unauthorized access attempt: email or driver_id not set");
    header('Location: SignIn.php');
    exit;
}

// Log session data for debugging
error_log("Session email: " . $_SESSION['email']);
error_log("Session driver_id: " . $_SESSION['driver_id']);

// Include DatabaseConnection
require_once __DIR__ . '/db/DatabaseConnection.php';

try {
    $db = new DatabaseConnection();
    $conn = $db->conn;
    if (!$conn) {
        throw new Exception("Database connection is null");
    }
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Initialize message variables
$message = '';
$messageType = '';
if (isset($_SESSION['message']) && isset($_SESSION['messageType'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $driver_id = $_SESSION['driver_id'];
    $email = $_SESSION['email'];

    try {
        switch ($_POST['action']) {
            case 'complete_ride':
                if (!empty($_POST['booking_id'])) {
                    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
                    $conn->begin_transaction();

                    $stmt = $conn->prepare("UPDATE RideBookings SET status = 'completed', dropoff_time = CURRENT_TIME WHERE booking_id = ? AND driver_id = ? AND status IN ('pending', 'confirmed')");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("ii", $booking_id, $driver_id);

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $driver_stmt = $conn->prepare("UPDATE Drivers SET status = 'available' WHERE driver_id = ?");
                        if (!$driver_stmt) {
                            throw new Exception("Driver prepare failed: " . $conn->error);
                        }
                        $driver_stmt->bind_param("i", $driver_id);
                        if ($driver_stmt->execute()) {
                            $conn->commit();
                            $message = "Ride completed successfully!";
                            $messageType = "success";
                        } else {
                            $conn->rollback();
                            throw new Exception("Error updating driver status: " . $driver_stmt->error);
                        }
                        $driver_stmt->close();
                    } else {
                        $conn->rollback();
                        $message = "Error completing ride or no pending/confirmed ride found.";
                        $messageType = "danger";
                    }
                    $stmt->close();
                } else {
                    $message = "Booking ID is required.";
                    $messageType = "danger";
                }
                break;

            case 'update_profile':
                if (!empty($_POST['full_name']) && !empty($_POST['phone'])) {
                    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
                    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);

                    $conn->begin_transaction();
                    $stmt = $conn->prepare("UPDATE Drivers SET full_name = ?, phone = ? WHERE driver_id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("ssi", $full_name, $phone, $driver_id);

                    if ($stmt->execute()) {
                        $name_parts = explode(' ', trim($full_name));
                        $first_name = $name_parts[0];
                        $last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';
                        $user_stmt = $conn->prepare("UPDATE UserAccounts SET first_name = ?, last_name = ? WHERE email = ?");
                        if (!$user_stmt) {
                            throw new Exception("User prepare failed: " . $conn->error);
                        }
                        $user_stmt->bind_param("sss", $first_name, $last_name, $email);

                        if ($user_stmt->execute()) {
                            $conn->commit();
                            $_SESSION['name'] = $full_name;
                            $message = "Profile updated successfully!";
                            $messageType = "success";
                        } else {
                            $conn->rollback();
                            throw new Exception("Error updating user account: " . $user_stmt->error);
                        }
                        $user_stmt->close();
                    } else {
                        $conn->rollback();
                        throw new Exception("Error updating profile: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill all required fields.";
                    $messageType = "danger";
                }
                break;

            case 'change_password':
                if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                    $current_password = $_POST['current_password'];
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];

                    if ($new_password !== $confirm_password) {
                        $message = "New password and confirmation do not match.";
                        $messageType = "danger";
                    } else if (strlen($new_password) < 8) {
                        $message = "New password must be at least 8 characters long.";
                        $messageType = "danger";
                    } else {
                        $stmt = $conn->prepare("SELECT password_hash FROM UserAccounts WHERE email = ?");
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();

                        if (password_verify($current_password, $user['password_hash'])) {
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt = $conn->prepare("UPDATE UserAccounts SET password_hash = ? WHERE email = ?");
                            if (!$update_stmt) {
                                throw new Exception("Update prepare failed: " . $conn->error);
                            }
                            $update_stmt->bind_param("ss", $new_password_hash, $email);

                            if ($update_stmt->execute()) {
                                $message = "Password changed successfully!";
                                $messageType = "success";
                            } else {
                                throw new Exception("Error changing password: " . $update_stmt->error);
                            }
                            $update_stmt->close();
                        } else {
                            $message = "Current password is incorrect.";
                            $messageType = "danger";
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Please fill all password fields.";
                    $messageType = "danger";
                }
                break;

            default:
                $message = "Invalid action.";
                $messageType = "danger";
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $messageType === 'success', 'message' => $message]);
            exit;
        } else {
            $_SESSION['message'] = $message;
            $_SESSION['messageType'] = $messageType;
            header('Location: DriverDashboard.php');
            exit;
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
            header('Location: DriverDashboard.php');
            exit;
        }
    }
}

// Fetch driver details
$driver_id = $_SESSION['driver_id'];
$driver_query = $conn->prepare("SELECT full_name, phone, license_number, vehicle_number, status FROM Drivers WHERE driver_id = ?");
if (!$driver_query) {
    error_log("Driver query prepare failed: " . $conn->error);
    $message = "Error fetching driver details.";
    $messageType = "danger";
    $driver = null;
} else {
    $driver_query->bind_param("i", $driver_id);
    $driver_query->execute();
    $driver_result = $driver_query->get_result();
    $driver = $driver_result->num_rows > 0 ? $driver_result->fetch_assoc() : null;
    $driver_query->close();
    if (!$driver) {
        error_log("No driver found for driver_id: $driver_id");
        $message = "Driver not found.";
        $messageType = "danger";
    }
}

// Fetch ride bookings
$bookings_query = $conn->prepare(
    "SELECT b.booking_id, b.customer_id, b.pickup_location, b.destination, b.fare, b.booking_date, b.pickup_time, b.dropoff_time, b.status, b.created_at, c.first_name, c.last_name 
     FROM RideBookings b 
     LEFT JOIN Customers c ON b.customer_id = c.customer_id 
     WHERE b.driver_id = ? 
     ORDER BY b.created_at DESC"
);
if (!$bookings_query) {
    error_log("Bookings query prepare failed: " . $conn->error);
    $message = "Error fetching bookings: " . $conn->error;
    $messageType = "danger";
    $bookings_result = null;
} else {
    $bookings_query->bind_param("i", $driver_id);
    $bookings_query->execute();
    $bookings_result = $bookings_query->get_result();
    $bookings_query->close();
}

// Close database connection
if (isset($conn)) {
    try {
        $conn->close();
    } catch (Exception $e) {
        error_log("Error closing database connection: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Resident Villa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        .nav-item { margin: 0.25rem 1rem; }

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

        .nav-icon { width: 20px; text-align: center; }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 73px);
        }

        .breadcrumb-container { margin-bottom: 2rem; }

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

        .btn-success {
            background: var(--accent-green);
            border-color: var(--accent-green);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            border-color: #059669;
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

        .card-body { padding: 1.5rem; }

        .table-container { overflow-x: auto; }

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

        .table td { color: var(--text-primary); }

        .table tr:hover { background: var(--hover-bg); }

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
        .badge-primary { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }

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

        .btn-close:hover { opacity: 1; }

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
            max-width: 600px;
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

        .modal-body { margin-bottom: 1rem; }

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

        .close:hover { color: var(--text-primary); }

        .form-group { margin-bottom: 1rem; }

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

        .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; }
        .col-md-8 { flex: 0 0 66.667%; max-width: 66.667%; }

        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .col-md-4, .col-md-8 { flex: 0 0 100%; max-width: 100%; }
            .modal-content { max-width: 95%; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 1rem; }
            .header { padding: 1rem; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in { animation: fadeIn 0.6s ease-out; }

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
            <span><?php echo htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'Driver'); ?> (Driver)</span>
            <span>|</span>
            <span><?php echo date('M d, Y H:i'); ?></span>
        </div>
    </header>

    <nav class="sidebar">
        <div class="nav-item">
            <a class="nav-link active" href="DriverDashboard.php">
                <i class="fas fa-car nav-icon"></i>
                <span>Ride Bookings</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#edit-profile" onclick="showSection('edit-profile')">
                <i class="fas fa-user-edit nav-icon"></i>
                <span>Edit Profile</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#change-password" onclick="showSection('change-password')">
                <i class="fas fa-key nav-icon"></i>
                <span>Change Password</span>
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
                <a href="DriverDashboard.php">Home</a>
                <span>/</span>
                <span>Driver Dashboard</span>
            </nav>
        </div>

        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-car"></i> Driver Dashboard</h1>
        </div>

        <div id="alert" class="alert <?php echo $message ? 'alert-' . $messageType : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close"></button>
        </div>

        <div id="ride-bookings" class="section">
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-list"></i>
                    Your Ride Bookings
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Pickup</th>
                                    <th>Destination</th>
                                    <th>Fare (LKR)</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                            <td><?php echo htmlspecialchars((isset($booking['first_name']) ? $booking['first_name'] : 'Unknown') . ' ' . (isset($booking['last_name']) ? $booking['last_name'] : '')); ?></td>
                                            <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['destination']); ?></td>
                                            <td><?php echo number_format($booking['fare'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($booking['booking_date'] . ' ' . $booking['pickup_time']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $booking['status'] == 'confirmed' ? 'warning' : ($booking['status'] == 'completed' ? 'success' : ($booking['status'] == 'canceled' ? 'danger' : 'primary')); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick='viewRide(<?php echo htmlspecialchars(json_encode($booking)); ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                                    <button class="btn btn-sm btn-success" onclick="completeRide(<?php echo $booking['booking_id']; ?>)">
                                                        <i class="fas fa-check"></i> Complete
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-car fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No bookings found</p>
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

        <div id="edit-profile" class="section" style="display: none;">
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-user-edit"></i>
                    Edit Profile
                </div>
                <div class="card-body">
                    <?php if ($driver): ?>
                        <form id="editProfileForm" method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" name="full_name" id="full_name" value="<?php echo htmlspecialchars($driver['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" id="phone" value="<?php echo htmlspecialchars($driver['phone']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="license_number">License Number (Read-only)</label>
                                <input type="text" class="form-control" id="license_number" value="<?php echo htmlspecialchars($driver['license_number']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="vehicle_number">Vehicle Number (Read-only)</label>
                                <input type="text" class="form-control" id="vehicle_number" value="<?php echo htmlspecialchars($driver['vehicle_number']); ?>" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-danger">Unable to load driver profile.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="change-password" class="section" style="display: none;">
            <div class="card fade-in">
                <div class="card-header">
                    <i class="fas fa-key"></i>
                    Change Password
                </div>
                <div class="card-body">
                    <form id="changePasswordForm" method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" name="current_password" id="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div id="rideModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Ride Details</h2>
                <span class="close" onclick="closeRideModal()">×</span>
            </div>
            <div class="modal-body">
                <p><strong>Booking ID:</strong> <span id="ride_booking_id"></span></p>
                <p><strong>Customer:</strong> <span id="ride_customer"></span></p>
                <p><strong>Pickup:</strong> <span id="ride_pickup"></span></p>
                <p><strong>Destination:</strong> <span id="ride_destination"></span></p>
                <p><strong>Fare (LKR):</strong> <span id="ride_fare"></span></p>
                <p><strong>Date & Time:</strong> <span id="ride_date_time"></span></p>
                <p><strong>Status:</strong> <span id="ride_status"></span></p>
                <p><strong>Created At:</strong> <span id="ride_created_at"></span></p>
                <div id="map" class="loading"><span class="spinner"></span> Loading map...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRideModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, pickupMarker, destinationMarker, routeLine;

        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${sectionId}`) {
                    link.classList.add('active');
                }
            });
        }

        async function geocodeAddress(address) {
            if (!address || address.trim() === '') {
                console.error('Geocoding error: Empty address');
                return { coords: null, error: 'Address is empty' };
            }

            // Append default city/country if address seems incomplete
            let query = address.trim();
            if (!query.includes(',')) {
                query += ', Colombo, Sri Lanka';
            } else if (!query.toLowerCase().includes('sri lanka')) {
                query += ', Sri Lanka';
            }

            try {
                // Add a small delay to respect Nominatim's rate limit (1 req/sec)
                await new Promise(resolve => setTimeout(resolve, 1000));
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`, {
                    headers: {
                        'User-Agent': 'ResidentVilla/1.0 (http://localhost/Resident-villa; contact@residentvilla.com)'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }

                const data = await response.json();
                if (data.length > 0) {
                    return { coords: [parseFloat(data[0].lat), parseFloat(data[0].lon)], error: null };
                } else {
                    console.error('Geocoding error: No results for address:', query);
                    return { coords: null, error: 'No results found for address' };
                }
            } catch (error) {
                console.error('Geocoding error for address:', query, error);
                return { coords: null, error: error.message };
            }
        }

        async function initializeMap(pickup, destination) {
            const mapDiv = document.getElementById('map');
            mapDiv.innerHTML = '<div class="loading"><span class="spinner"></span> Loading map...</div>';

            if (map) {
                map.remove();
            }

            map = L.map('map').setView([6.9271, 79.8612], 12); // Default to Colombo, Sri Lanka
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '©️ <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const pickupResult = await geocodeAddress(pickup);
            const destinationResult = await geocodeAddress(destination);

            let errorMessages = [];
            if (pickupResult.error) {
                errorMessages.push(`Pickup: ${pickupResult.error}`);
            }
            if (destinationResult.error) {
                errorMessages.push(`Destination: ${destinationResult.error}`);
            }

            if (pickupResult.coords) {
                pickupMarker = L.marker(pickupResult.coords, {
                    icon: L.divIcon({
                        className: 'custom-icon',
                        html: '<i class="fas fa-map-pin fa-2x" style="color: green;"></i>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    })
                }).addTo(map).bindPopup('Pickup: ' + pickup);
            }

            if (destinationResult.coords) {
                destinationMarker = L.marker(destinationResult.coords, {
                    icon: L.divIcon({
                        className: 'custom-icon',
                        html: '<i class="fas fa-flag-checkered fa-2x" style="color: red;"></i>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    })
                }).addTo(map).bindPopup('Destination: ' + destination);
            }

            if (pickupResult.coords && destinationResult.coords) {
                routeLine = L.polyline([pickupResult.coords, destinationResult.coords], {
                    color: 'blue',
                    weight: 3
                }).addTo(map);
                map.fitBounds([pickupResult.coords, destinationResult.coords], { padding: [50, 50] });
                mapDiv.innerHTML = ''; // Clear loading indicator
            } else if (pickupResult.coords) {
                map.setView(pickupResult.coords, 14);
                mapDiv.innerHTML = ''; // Clear loading indicator
                if (destinationResult.error) {
                    mapDiv.innerHTML = `<p class="text-danger">Destination error: ${destinationResult.error}</p>`;
                }
            } else if (destinationResult.coords) {
                map.setView(destinationResult.coords, 14);
                mapDiv.innerHTML = ''; // Clear loading indicator
                if (pickupResult.error) {
                    mapDiv.innerHTML = `<p class="text-danger">Pickup error: ${pickupResult.error}</p>`;
                }
            } else {
                mapDiv.innerHTML = `<p class="text-danger">${errorMessages.join('<br>')}</p>`;
            }
        }

        function viewRide(booking) {
            document.getElementById('ride_booking_id').textContent = booking.booking_id;
            document.getElementById('ride_customer').textContent = (booking.first_name || 'Unknown') + ' ' + (booking.last_name || '');
            document.getElementById('ride_pickup').textContent = booking.pickup_location;
            document.getElementById('ride_destination').textContent = booking.destination;
            document.getElementById('ride_fare').textContent = parseFloat(booking.fare).toFixed(2);
            document.getElementById('ride_date_time').textContent = booking.booking_date + ' ' + booking.pickup_time;
            document.getElementById('ride_status').textContent = booking.status.charAt(0).toUpperCase() + booking.status.slice(1);
            document.getElementById('ride_created_at').textContent = booking.created_at;
            document.getElementById('rideModal').style.display = 'block';

            // Initialize map with pickup and destination
            initializeMap(booking.pickup_location, booking.destination);
        }

        function closeRideModal() {
            document.getElementById('rideModal').style.display = 'none';
            if (map) {
                map.remove();
                map = null;
            }
            document.getElementById('map').innerHTML = '<div class="loading"><span class="spinner"></span> Loading map...</div>';
        }

        function completeRide(bookingId) {
            if (confirm('Are you sure you want to mark this ride as completed?')) {
                const formData = new FormData();
                formData.append('action', 'complete_ride');
                formData.append('booking_id', bookingId);

                fetch('DriverDashboard.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Complete ride error:', error);
                    showAlert('Error: ' + error.message, 'danger');
                });
            }
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = `alert alert-${type} fade-in`;
            alert.innerHTML = '';
            alert.appendChild(document.createTextNode(message));
            const closeBtn = document.createElement('button');
            closeBtn.setAttribute('type', 'button');
            closeBtn.className = 'btn-close';
            closeBtn.onclick = () => { alert.style.display = 'none'; };
            alert.appendChild(closeBtn);
            alert.style.display = 'block';
            setTimeout(() => { alert.style.display = 'none'; }, 5000);
        }

        document.getElementById('editProfileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Updating...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('DriverDashboard.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                if (data.success) {
                    showAlert(data.message, 'success');
                    document.querySelector('.header-info span:nth-child(2)').textContent = `<?php echo htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'Driver'); ?> (Driver)`;
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Changing...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('DriverDashboard.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Password change error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.onclick = function(event) {
                const rideModal = document.getElementById('rideModal');
                if (event.target === rideModal) {
                    closeRideModal();
                }
            };

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-close')) {
                    e.target.parentElement.style.display = 'none';
                }
            });

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
                    sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
                });
                header.insertBefore(toggleBtn, header.firstChild);
            };

            if (window.innerWidth <= 768) {
                createMobileToggle();
            }

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

            <?php if ($message): ?>
                showAlert('<?php echo htmlspecialchars($message); ?>', '<?php echo $messageType; ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>