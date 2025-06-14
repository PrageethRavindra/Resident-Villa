<?php
// Include the DatabaseConnection class
require_once __DIR__ . './db/DatabaseConnection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for user authentication
session_start();

// // Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }

// Create database connection
try {
    $db = new DatabaseConnection();
    $conn = $db->conn;
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_driver':
                // Validate required fields
                if (!empty($_POST['full_name']) && !empty($_POST['license_number']) && !empty($_POST['vehicle_number']) && !empty($_POST['phone'])) {
                    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
                    $license_number = filter_var($_POST['license_number'], FILTER_SANITIZE_STRING);
                    $vehicle_number = filter_var($_POST['vehicle_number'], FILTER_SANITIZE_STRING);
                    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
                    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

                    try {
                        $stmt = $conn->prepare("INSERT INTO Drivers (full_name, license_number, vehicle_number, phone, status) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $full_name, $license_number, $vehicle_number, $phone, $status);

                        if ($stmt->execute()) {
                            $message = "Driver registered successfully!";
                            $messageType = "success";
                            // Email sending will be handled client-side
                        } else {
                            $message = "Error registering driver: " . $stmt->error;
                            $messageType = "danger";
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        $messageType = "danger";
                    }
                } else {
                    $message = "Please fill all required fields.";
                    $messageType = "danger";
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

                    try {
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
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        $messageType = "danger";
                    }
                } else {
                    $message = "Please fill all required fields.";
                    $messageType = "danger";
                }
                break;

            case 'delete_driver':
                if (!empty($_POST['driver_id'])) {
                    $driver_id = filter_var($_POST['driver_id'], FILTER_SANITIZE_NUMBER_INT);

                    try {
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
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                        $messageType = "danger";
                    }
                }
                break;
        }
    }
}

// Fetch all drivers
$drivers_query = "SELECT * FROM Drivers ORDER BY driver_id DESC";
$drivers_result = $conn->query($drivers_query);

if (!$drivers_result) {
    $message = "Error fetching drivers: " . $conn->error;
    $messageType = "danger";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Management - Resident Villa</title>

    <!-- Modern CSS Framework -->
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

        /* Header */
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

        /* Sidebar */
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

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 73px);
        }

        /* Breadcrumb */
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

        /* Page Header */
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

        /* Buttons */
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

        /* Card */
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

        /* Table */
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

        /* Status Badges */
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

        /* Form Elements */
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

        /* Alert */
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

        /* Modal */
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

        /* Layout */
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

        /* Responsive Design */
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

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* Loading States */
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
    <!-- Header -->
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

    <!-- Sidebar -->
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav class="breadcrumb">
                <a href="admin.php">Home</a>
                <span>/</span>
                <span>Driver Management</span>
            </nav>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-id-card"></i> Driver Management</h1>
            <button class="btn">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>

        <!-- Alert Messages -->
        <div id="alert" class="alert <?php echo $message ? 'alert-' . $messageType : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close"></button>
        </div>

        <div class="row">
            <!-- Add Driver Form -->
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

            <!-- Drivers Table -->
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
                                    <?php if ($drivers_result->num_rows > 0): ?>
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

    <!-- Edit Driver Modal -->
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
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Driver
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
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
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@5/dist/email.min.js"></script>

    <script>
        // Initialize EmailJS
        (function() {
            emailjs.init({
                publicKey: 'YhHXAJvR7QvQoE4VR',
            });
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
            document.getElementById('deleteDriverModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editDriverModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteDriverModal').style.display = 'none';
        // Send confirmation email on successful registration
        function sendEmail(to_email, to_name) {
            emailjs.send("service_71f1uuo", "template_lwdo2vk", {
                to_name: to_name,
                to_email: to_email
            })
            .then(response => {
                console.log('Email sent successfully:', response);
                showAlert('Confirmation email sent successfully!', 'success');
            }, error => {
                console.error('Email sending failed:', error);
                showAlert('Failed to send confirmation email.', 'danger');
            });
        }

        // Form submission handlers
        document.getElementById('addDriverForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Registering...';
            submitBtn.disabled = true;

            const formData = new FormData(this);
            const fullName = formData.get('full_name');

            fetch('DriverRegister.php', {
                method: 'POST',
                body: formData)
            .then(response => response.text())
            .then(data => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                // Extract email from formData for EmailJS
                const email = formData.get('email');
                if (data.includes('success')) {
                    sendEmail(email, fullName);
                }
                window.location.reload(true);
            })
            .catch(error => {
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
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                closeEditModal();
                window.location.reload(true);
            })
            .catch(error => {
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
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                closeDeleteModal();
                window.location.reload(true);
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        // Show alert messages
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = alert alert-${type} fade-in;
            alert.innerHTML = ${message}<button type="button" class="btn-close"></button>;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Close modals when clicking outside
            window.onclick = function(event) {
                const editModal = document.getElementById('editDriverModal');
                const deleteModal = document.getElementById('deleteDriverModal');
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
                    @media (max-width: 768px) {
                        display: block;
                    }
                `;
                
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
        });
    </script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>