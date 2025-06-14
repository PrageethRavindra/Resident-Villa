<?php
// Include the DatabaseConnection class
require_once __DIR__ . '/db/DatabaseConnection.php';

// Start session for user authentication
session_start();

// Check if user is logged in
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_booking':
                try {
                    // Validate inputs
                    if (!isset($_POST['customer_id'], $_POST['room_number'], $_POST['check_in_date'], $_POST['check_out_date'], $_POST['status'])) {
                        throw new Exception("All fields are required");
                    }

                    $check_in = new DateTime($_POST['check_in_date']);
                    $check_out = new DateTime($_POST['check_out_date']);
                    if ($check_out <= $check_in) {
                        throw new Exception("Check-out date must be after check-in date");
                    }

                    $stmt = $conn->prepare("INSERT INTO RoomBookings (customer_id, room_number, check_in_date, check_out_date, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisss", $_POST['customer_id'], $_POST['room_number'], $_POST['check_in_date'], $_POST['check_out_date'], $_POST['status']);
                    
                    if ($stmt->execute()) {
                        $message = "Room booking added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding booking: " . $stmt->error;
                        $messageType = "danger";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
                
            case 'update_booking':
                try {
                    // Validate inputs
                    if (!isset($_POST['booking_id'], $_POST['customer_id'], $_POST['room_number'], $_POST['check_in_date'], $_POST['check_out_date'], $_POST['status'])) {
                        throw new Exception("All fields are required");
                    }

                    $check_in = new DateTime($_POST['check_in_date']);
                    $check_out = new DateTime($_POST['check_out_date']);
                    if ($check_out <= $check_in) {
                        throw new Exception("Check-out date must be after check-in date");
                    }

                    $stmt = $conn->prepare("UPDATE RoomBookings SET customer_id = ?, room_number = ?, check_in_date = ?, check_out_date = ?, status = ? WHERE booking_id = ?");
                    $stmt->bind_param("iisssi", $_POST['customer_id'], $_POST['room_number'], $_POST['check_in_date'], $_POST['check_out_date'], $_POST['status'], $_POST['booking_id']);
                    
                    if ($stmt->execute()) {
                        $message = "Room booking updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating booking: " . $stmt->error;
                        $messageType = "danger";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
                
            case 'delete_booking':
                try {
                    if (!isset($_POST['booking_id'])) {
                        throw new Exception("Booking ID is required");
                    }

                    $stmt = $conn->prepare("DELETE FROM RoomBookings WHERE booking_id = ?");
                    $stmt->bind_param("i", $_POST['booking_id']);
                    
                    if ($stmt->execute()) {
                        $message = "Room booking deleted successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error deleting booking: " . $stmt->error;
                        $messageType = "danger";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
        }
    }
}

// Fetch all room bookings with customer details
$bookings_query = "SELECT rb.*, c.first_name, c.last_name, c.email, c.phone 
                   FROM RoomBookings rb 
                   JOIN Customers c ON rb.customer_id = c.customer_id 
                   ORDER BY rb.check_in_date DESC";
$bookings_result = $conn->query($bookings_query);

if (!$bookings_result) {
    die("Error fetching bookings: " . $conn->error);
}

// Fetch all customers for dropdown
$customers_query = "SELECT customer_id, first_name, last_name, email FROM Customers ORDER BY first_name";
$customers_result = $conn->query($customers_query);

if (!$customers_result) {
    die("Error fetching customers: " . $conn->error);
}

// Get booking statistics
$stats_query = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_bookings,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
                FROM RoomBookings";
$stats_result = $conn->query($stats_query);

if (!$stats_result) {
    die("Error fetching statistics: " . $conn->error);
}

$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking Dashboard - Resident Villa</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-blue);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple));
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .stat-blue .stat-icon { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }
        .stat-blue .stat-value { color: var(--accent-blue); }
        .stat-green .stat-icon { background: rgba(16, 185, 129, 0.1); color: var(--accent-green); }
        .stat-green .stat-value { color: var(--accent-green); }
        .stat-yellow .stat-icon { background: rgba(245, 158, 11, 0.1); color: var(--accent-yellow); }
        .stat-yellow .stat-value { color: var(--accent-yellow); }

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
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--accent-red); }
        .badge-info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }

        /* Form Elements */
        .form-control {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
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
        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
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

            .stats-grid {
                grid-template-columns: 1fr;
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
            <span>Room Booking Management</span>
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
            <a class="nav-link active" href="RoomBookingDashBoard.php">
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
            <a class="nav-link" href="DriverRegister.php">
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
                <span>Room Bookings</span>
            </nav>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-bed"></i> Room Booking Dashboard</h1>
            <button class="btn" onclick="exportReport()">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> fade-in" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid fade-in">
            <div class="stat-card stat-blue">
                <div class="stat-header">
                    <div class="stat-title">Total Bookings</div>
                    <div class="stat-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['total_bookings']) ? number_format($stats['total_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">All room bookings</div>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-header">
                    <div class="stat-title">Confirmed</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['confirmed_bookings']) ? number_format($stats['confirmed_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Active bookings</div>
                </div>
            </div>
            <div class="stat-card stat-yellow">
                <div class="stat-header">
                    <div class="stat-title">Completed</div>
                    <div class="stat-icon">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['completed_bookings']) ? number_format($stats['completed_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Finished bookings</div>
                </div>
            </div>
            <div class="stat-card stat-yellow">
                <div class="stat-header">
                    <div class="stat-title">Canceled</div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['canceled_bookings']) ? number_format($stats['canceled_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Canceled bookings</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Add New Booking Form -->
            <div class="col-md-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i>
                        Add New Room Booking
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="addBookingForm">
                            <input type="hidden" name="action" value="add_booking">
                            
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select class="form-control" name="customer_id" id="customer_id" required>
                                    <option value="" disabled selected>Select Customer</option>
                                    <?php
                                    $customers_result->data_seek(0);
                                    while ($customer = $customers_result->fetch_assoc()): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>">
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['email'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="room_number" class="form-label">Room Number</label>
                                <input type="number" class="form-control" name="room_number" id="room_number" required min="1">
                            </div>
                            
                            <div class="mb-3">
                                <label for="check_in_date" class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" name="check_in_date" id="check_in_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="check_out_date" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" name="check_out_date" id="check_out_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" name="status" id="status" required>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="canceled">Canceled</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Add Booking
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bookings List -->
            <div class="col-md-8">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-list"></i>
                        Room Bookings
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bookings_result->num_rows > 0): ?>
                                        <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $booking['status'] == 'confirmed' ? 'success' : ($booking['status'] == 'canceled' ? 'danger' : 'info'); ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick='editBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick='deleteBooking(<?php echo htmlspecialchars(json_encode([
                                                        'id' => $booking['booking_id'],
                                                        'customer' => $booking['first_name'] . ' ' . $booking['last_name'],
                                                        'room' => $booking['room_number'],
                                                        'check_in' => date('M d, Y', strtotime($booking['check_in_date'])),
                                                        'check_out' => date('M d, Y', strtotime($booking['check_out_date']))
                                                    ])); ?>)'>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="py-4">
                                                    <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No room bookings found</p>
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

    <!-- Edit Booking Modal -->
    <div class="modal fade" id="editBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Room Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editBookingForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_booking">
                        <input type="hidden" name="booking_id" id="edit_booking_id">
                        
                        <div class="mb-3">
                            <label for="edit_customer_id" class="form-label">Customer</label>
                            <select class="form-control" name="customer_id" id="edit_customer_id" required>
                                <?php
                                $customers_result->data_seek(0);
                                while ($customer = $customers_result->fetch_assoc()): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>">
                                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['email'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_room_number" class="form-label">Room Number</label>
                            <input type="number" class="form-control" name="room_number" id="edit_room_number" required min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_check_in_date" class="form-label">Check-in Date</label>
                            <input type="date" class="form-control" name="check_in_date" id="edit_check_in_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_check_out_date" class="form-label">Check-out Date</label>
                            <input type="date" class="form-control" name="check_out_date" id="edit_check_out_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="confirmed">Confirmed</option>
                                <option value="canceled">Canceled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this booking?</p>
                    <div class="alert alert-warning">
                        <strong>Booking Details:</strong><br>
                        <span id="delete_booking_details"></span>
                    </div>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="delete_booking">
                        <input type="hidden" name="booking_id" id="delete_booking_id">
                        <button type="submit" class="btn btn-danger">Delete Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function(){
            location.reload();
        }, 300000);

        function editBooking(booking) {
            document.getElementById('edit_booking_id').value = booking.booking_id;
            document.getElementById('edit_customer_id').value = booking.customer_id;
            document.getElementById('edit_room_number').value = booking.room_number;
            document.getElementById('edit_check_in_date').value = booking.check_in_date;
            document.getElementById('edit_check_out_date').value = booking.check_out_date;
            document.getElementById('edit_status').value = booking.status;
            
            new bootstrap.Modal(document.getElementById('editBookingModal')).show();
        }

        function deleteBooking(booking) {
            document.getElementById('delete_booking_id').value = booking.id;
            document.getElementById('delete_booking_details').innerHTML = `
                Customer: ${booking.customer}<br>
                Room Number: ${booking.room}<br>
                Check-in: ${booking.check_in}<br>
                Check-out: ${booking.check_out}
            `;
            new bootstrap.Modal(document.getElementById('deleteBookingModal')).show();
        }

        function exportReport() {
            const exportBtn = document.querySelector('.btn i.fa-download').parentElement;
            if (exportBtn) {
                const originalContent = exportBtn.innerHTML;
                exportBtn.innerHTML = '<div class="spinner"></div> Exporting...';
                exportBtn.disabled = true;

                // Create CSV content
                let csv = 'Booking ID,Customer,Room,Check-in,Check-out,Status\n';
                <?php 
                $bookings_result->data_seek(0);
                while ($booking = $bookings_result->fetch_assoc()): 
                ?>
                csv += `"${booking['booking_id']}","${booking['first_name'].replace(/"/g, '""')} ${booking['last_name'].replace(/"/g, '""')}","${booking['room_number']}","${booking['check_in_date']}","${booking['check_out_date']}","${booking['status']}"\n`;
                <?php endwhile; ?>

                // Create download link
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'room_bookings_' + new Date().toISOString().slice(0,10) + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);

                exportBtn.innerHTML = '<i class="fas fa-check"></i> Exported!';
                setTimeout(() => {
                    exportBtn.innerHTML = originalContent;
                    exportBtn.disabled = false;
                }, 2000);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to today for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.setAttribute('min', today);
            });

            // Form validation for dates
            function validateDates(checkinId, checkoutId, formId) {
                const checkinInput = document.getElementById(checkinId);
                const checkoutInput = document.getElementById(checkoutId);
                const form = document.getElementById(formId);
                
                if (checkinInput && checkoutInput) {
                    checkinInput.addEventListener('change', function() {
                        checkoutInput.setAttribute('min', this.value);
                        if (checkoutInput.value && checkoutInput.value <= this.value) {
                            checkoutInput.value = '';
                        }
                    });

                    if (form) {
                        form.addEventListener('submit', function(e) {
                            if (checkinInput.value && checkoutInput.value) {
                                const checkinDate = new Date(checkinInput.value);
                                const checkoutDate = new Date(checkoutInput.value);
                                if (checkoutDate <= checkinDate) {
                                    e.preventDefault();
                                    alert('Check-out date must be after check-in date');
                                }
                            }
                        });
                    }
                }
            }

            validateDates('check_in_date', 'check_out_date', 'addBookingForm');
            validateDates('edit_check_in_date', 'edit_check_out_date', 'editBookingForm');

            // Form validation for room number
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value < 1) {
                        this.value = 1;
                    }
                });
            });

            // Stagger animation for stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = '${index * 0.1}s';
                card.classList.add('fade-in');
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

            // Mobile menu toggle functionality
            const createMobileToggle = () => {
                const header = document.querySelector('.header');
                const sidebar = document.querySelector('.sidebar');
                
                const toggleBtn = document.createElement('button');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.style.cssText = `
                    background: none;
                    border: none;
                    color: var(--text-primary);
                    font-size: 1.25rem;
                    cursor: pointer;
                    padding: 0.5rem;
                    display: 0.5rem;
                    padding: none;
                    
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

            // Add intersection observer for fade-in animations
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

            // Close alert on button click
            const alertCloseBtn = document.querySelector('.alert .btn-close');
            if (alertCloseBtn) {
                alertCloseBtn.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
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