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
                    if (!isset($_POST['customer_id'], $_POST['driver_id'], $_POST['pickup_location'], $_POST['destination'], $_POST['fare'], $_POST['booking_date'], $_POST['pickup_time'], $_POST['dropoff_time'], $_POST['status'])) {
                        throw new Exception("All fields are required");
                    }

                    $pickup_datetime = new DateTime($_POST['booking_date'] . ' ' . $_POST['pickup_time']);
                    $dropoff_datetime = new DateTime($_POST['booking_date'] . ' ' . $_POST['dropoff_time']);
                    if ($dropoff_datetime <= $pickup_datetime) {
                        throw new Exception("Drop-off time must be after pick-up time");
                    }

                    $fare = floatval($_POST['fare']);
                    if ($fare <= 0) {
                        throw new Exception("Fare must be a positive number");
                    }

                    $stmt = $conn->prepare("INSERT INTO RideBookings (customer_id, driver_id, pickup_location, destination, fare, booking_date, pickup_time, dropoff_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisssdsss", $_POST['customer_id'], $_POST['driver_id'], $_POST['pickup_location'], $_POST['destination'], $fare, $_POST['booking_date'], $_POST['pickup_time'], $_POST['dropoff_time'], $_POST['status']);
                    
                    if ($stmt->execute()) {
                        $message = "Ride booking added successfully!";
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
                    if (!isset($_POST['ride_id'], $_POST['customer_id'], $_POST['driver_id'], $_POST['pickup_location'], $_POST['destination'], $_POST['fare'], $_POST['booking_date'], $_POST['pickup_time'], $_POST['dropoff_time'], $_POST['status'])) {
                        throw new Exception("All fields are required");
                    }

                    $pickup_datetime = new DateTime($_POST['booking_date'] . ' ' . $_POST['pickup_time']);
                    $dropoff_datetime = new DateTime($_POST['booking_date'] . ' ' . $_POST['dropoff_time']);
                    if ($dropoff_datetime <= $pickup_datetime) {
                        throw new Exception("Drop-off time must be after pick-up time");
                    }

                    $fare = floatval($_POST['fare']);
                    if ($fare <= 0) {
                        throw new Exception("Fare must be a positive number");
                    }

                    $stmt = $conn->prepare("UPDATE RideBookings SET customer_id = ?, driver_id = ?, pickup_location = ?, destination = ?, fare = ?, booking_date = ?, pickup_time = ?, dropoff_time = ?, status = ? WHERE ride_id = ?");
                    $stmt->bind_param("iisssdsssi", $_POST['customer_id'], $_POST['driver_id'], $_POST['pickup_location'], $_POST['destination'], $fare, $_POST['booking_date'], $_POST['pickup_time'], $_POST['dropoff_time'], $_POST['status'], $_POST['ride_id']);
                    
                    if ($stmt->execute()) {
                        $message = "Ride booking updated successfully!";
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
                    if (!isset($_POST['ride_id'])) {
                        throw new Exception("Ride ID is required");
                    }

                    $stmt = $conn->prepare("DELETE FROM RideBookings WHERE ride_id = ?");
                    $stmt->bind_param("i", $_POST['ride_id']);
                    
                    if ($stmt->execute()) {
                        $message = "Ride booking deleted successfully!";
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

// Fetch all ride bookings with customer and driver details
$bookings_query = "SELECT rb.ride_id, rb.customer_id, rb.driver_id, rb.pickup_location, rb.destination, rb.fare, rb.booking_date, rb.pickup_time, rb.dropoff_time, rb.status,
                   CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                   COALESCE(d.full_name, 'Unassigned') AS driver_name
                   FROM RideBookings rb
                   JOIN Customers c ON rb.customer_id = c.customer_id
                   LEFT JOIN Drivers d ON rb.driver_id = d.driver_id
                   ORDER BY rb.created_at DESC
                   LIMIT 10";
$bookings_result = $conn->query($bookings_query);

if (!$bookings_result) {
    die("Error fetching ride bookings: " . $conn->error);
}

// Fetch all customers for dropdown
$customers_query = "SELECT customer_id, first_name, last_name, email FROM Customers ORDER BY first_name";
$customers_result = $conn->query($customers_query);

if (!$customers_result) {
    die("Error fetching customers: " . $conn->error);
}

// Fetch all available drivers for dropdown
$drivers_query = "SELECT driver_id, full_name FROM Drivers WHERE status = 'available' ORDER BY full_name";
$drivers_result = $conn->query($drivers_query);

if (!$drivers_result) {
    die("Error fetching drivers: " . $conn->error);
}

// Get ride booking statistics
$stats_query = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_bookings,
                    SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_bookings,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_bookings
                FROM RideBookings";
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
    <title>Ride Booking Dashboard - Resident Villa</title>

    <!-- Modern CSS Framework -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            padding: var(--bg-primary);
            padding: 2rem;
            min-height: calc(100vh - 73px);
        }

        /* Breadcrumb */
        .breadcrumb-container {
            display: flex;
            gap: 0.5rem;
            color: var(--text-primary);
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }

        .breadcrumb {
            background: var(--bg-tertiary);
            border: none;
            color: white;
            padding: flex;
            gap: 0.5rem;
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
            font-size: calc(2rem);
            font-weight: bold;
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            color: var(--text-primary);
        }

        /* Buttons */
        .btn {
            background-color: var(--bg-card);
            border: 8px solid var(--border-color);
            color: var(--text-primary);
            padding: .5rem 1rem;
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
            background-color: var(--hover-bg);
            border-color: var(--accent-blue);
        }

        .btn-primary {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            border-color: #2563eb;
        }

        .btn-warning {
            background-color: var(--accent-yellow);
            border-color: var(--accent-yellow);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
        }

        .btn-danger {
            background-color: var(--accent-red);
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
            box-shadow: var(--shadow);
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
        .stat-purple .stat-icon { background: rgba(139, 92, 246, 0.1); color: var(--accent-purple); }
        .stat-purple .stat-value { color: var(--accent-purple); }

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

        /* Booking Cards */
        .bookings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .booking-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .booking-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-blue);
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, var(--accent-blue), var(--accent-purple));
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .booking-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .booking-details {
            display: grid;
            gap: 0.75rem;
        }

        .booking-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .booking-detail i {
            color: var(--text-secondary);
            width: 20px;
            text-align: center;
        }

        .booking-detail span {
            color: var(--text-primary);
        }

        .booking-detail .detail-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .booking-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
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
        .badge-primary { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }

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

            .bookings-grid {
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
            <span>Ride Booking Management</span>
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
            <a class="nav-link active" href="RideBookingDashBoard.php">
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
                <span>Ride Bookings</span>
            </nav>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-car"></i> Ride Booking Dashboard</h1>
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
            <div class="stat-card stat-purple">
                <div class="stat-header">
                    <div class="stat-title">Total Ride Bookings</div>
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['total_bookings']) ? number_format($stats['total_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Total rides booked</div>
                </div>
            </div>
            <div class="stat-card stat-blue">
                <div class="stat-header">
                    <div class="stat-title">Scheduled Rides</div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['scheduled_bookings']) ? number_format($stats['scheduled_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Upcoming rides</div>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-header">
                    <div class="stat-title">Completed Rides</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['completed_bookings']) ? number_format($stats['completed_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Finished rides</div>
                </div>
            </div>
            <div class="stat-card stat-yellow">
                <div class="stat-header">
                    <div class="stat-title">Canceled Rides</div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo isset($stats['canceled_bookings']) ? number_format($stats['canceled_bookings']) : 0; ?></div>
                    <div class="stat-subtitle">Canceled rides</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Add New Booking Form -->
            <div class="col-md-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-plus-circle"></i>
                        Add New Ride Booking
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
                                <label for="driver_id" class="form-label">Driver</label>
                                <select class="form-control" name="driver_id" id="driver_id" required>
                                    <option value="" disabled selected>Select Driver</option>
                                    <?php
                                    $drivers_result->data_seek(0);
                                    while ($driver = $drivers_result->fetch_assoc()): ?>
                                        <option value="<?php echo $driver['driver_id']; ?>">
                                            <?php echo htmlspecialchars($driver['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pickup_location" class="form-label">Pickup Location</label>
                                <input type="text" class="form-control" name="pickup_location" id="pickup_location" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="destination" class="form-label">Destination</label>
                                <input type="text" class="form-control" name="destination" id="destination" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fare" class="form-label">Fare ($)</label>
                                <input type="number" step="0.01" class="form-control" name="fare" id="fare" required min="0.01">
                            </div>
                            
                            <div class="mb-3">
                                <label for="booking_date" class="form-label">Booking Date</label>
                                <input type="date" class="form-control" name="booking_date" id="booking_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pickup_time" class="form-label">Pickup Time</label>
                                <input type="time" class="form-control" name="pickup_time" id="pickup_time" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dropoff_time" class="form-label">Drop-off Time</label>
                                <input type="time" class="form-control" name="dropoff_time" id="dropoff_time" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" name="status" id="status" required>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in-progress">In-Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="canceled">Canceled</option>
                                </select>
                            
                            </div>
                            
                            <button type="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> Add Booking
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Ride Bookings -->
            <div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-car"></i>
                        Recent Ride Bookings
                    </div>
                    <div class="card-body">
                        <div class="bookings-grid">
                            <?php
                            $bookings_result->data_seek(0);
                            if ($bookings_result->num_rows > 0):
                                while ($row = $bookings_result->fetch_assoc()):
                                    $status_class = $row['status'] == 'completed' ? 'success' : ($row['status'] == 'canceled' ? 'danger' : ($row['status'] == 'scheduled' ? 'primary' : 'warning'));
                                    // Prepare booking data for JavaScript
                                    $booking_data = json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT);
                                    $delete_data = json_encode([
                                        'id' => $row['ride_id'],
                                        'customer' => $row['customer_name'],
                                        'pickup' => $row['pickup_location'],
                                        'destination' => $row['destination'],
                                        'booking_date' => date('Y-m-d', strtotime($row['booking_date']))
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT);
                            ?>
                                    <div class="booking-card fade-in">
                                        <div class="booking-header">
                                            <div class="booking-title">Ride #<?php echo htmlspecialchars($row['ride_id']); ?></div>
                                            <span class="booking-status badge badge-<?php echo $status_class; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </div>
                                        <div class="booking-details">
                                            <div class="booking-detail">
                                                <i class="fas fa-user"></i>
                                                <span class="detail-label">Customer:</span>
                                                <span><?php echo htmlspecialchars($row['customer_name']); ?></span>
                                            </div>
                                            <div class="booking-detail">
                                                <i class="fas fa-id-card"></i>
                                                <span class="detail-label">Driver:</span>
                                                <span><?php echo htmlspecialchars($row['driver_name']); ?></span>
                                            </div>
                                            <div class="booking-detail">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span class="detail-label">Pickup:</span>
                                                <span title="<?php echo htmlspecialchars($row['pickup_location']); ?>">
                                                    <?php echo htmlspecialchars(substr($row['pickup_location'], 0, 20)) . (strlen($row['pickup_location']) > 20 ? '...' : ''); ?>
                                                </span>
                                            </div>
                                            <div class="booking-detail">
                                                <i class="fas fa-flag-checkered"></i>
                                                <span class="detail-label">Destination:</span>
                                                <span title="<?php echo htmlspecialchars($row['destination']); ?>">
                                                    <?php echo htmlspecialchars(substr($row['destination'], 0, 20)) . (strlen($row['destination']) > 20 ? '...' : ''); ?>
                                                </span>
                                            </div>
                                            <div class="booking-detail">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span class="detail-label">Date:</span>
                                                <span><?php echo date('M d, Y', strtotime($row['booking_date'])); ?></span>
                                            </div>
                                            <div class="booking-detail">
                                                <i class="fas fa-clock"></i>
                                                <span class="detail-label">Time:</span>
                                                <span><?php echo htmlspecialchars($row['pickup_time']); ?> - <?php echo htmlspecialchars($row['dropoff_time']); ?></span>
                                            </div>
                                            <div class="booking-detail">
                                                <i class="fas fa-dollar-sign"></i>
                                                <span class="detail-label">Fare:</span>
                                                <span>$<?php echo number_format($row['fare'], 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="booking-actions">
                                            <button class="btn btn-sm btn-warning" onclick="editBooking(<?php echo $booking_data; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteBooking(<?php echo $delete_data; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-car fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No ride bookings found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="#" class="btn mt-3">View All</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Booking Modal -->
    <div class="modal fade" id="editBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Ride Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="editBookingForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_booking">
                        <input type="hidden" name="ride_id" id="edit_ride_id">
                        
                        <div class="row">
                            <div class="col-md-6">
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
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_driver_id" class="form-label">Driver</label>
                                    <select class="form-control" name="driver_id" id="edit_driver_id" required>
                                        <?php
                                        $drivers_result->data_seek(0);
                                        while ($driver = $drivers_result->fetch_assoc()): ?>
                                            <option value="<?php echo $driver['driver_id']; ?>">
                                                <?php echo htmlspecialchars($driver['full_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_pickup_location" class="form-label">Pickup Location</label>
                                    <input type="text" class="form-control" name="pickup_location" id="edit_pickup_location" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_destination" class="form-label">Destination</label>
                                    <input type="text" class="form-control" name="destination" id="edit_destination" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_fare" class="form-label">Fare ($)</label>
                                    <input type="number" step="0.01" class="form-control" name="fare" id="edit_fare" required min="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_booking_date" class="form-label">Booking Date</label>
                                    <input type="date" class="form-control" name="booking_date" id="edit_booking_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_pickup_time" class="form-label">Pickup Time</label>
                                    <input type="time" class="form-control" name="pickup_time" id="edit_pickup_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_dropoff_time" class="form-label">Drop-off Time</label>
                                    <input type="time" class="form-control" name="dropoff_time" id="edit_dropoff_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="in-progress">In-Progress</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
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
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this ride booking?</p>
                    <div class="alert alert-warning">
                        <strong>Booking Details:</strong><br>
                        <span id="delete_booking_details"></span>
                    </div>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" id="delete_form">
                        <input type="hidden" name="action" value="delete_booking">
                        <input type="hidden" name="ride_id" id="delete_ride_id">
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
        setTimeout(() => {
            location.reload();
        }, 300000);

        function editBooking(booking) {
            try {
                // Log booking for debugging
                console.log('Edit booking data:', booking);

                // Populate modal fields
                document.getElementById('edit_ride_id').value = booking.ride_id || '';
                document.getElementById('edit_customer_id').value = booking.customer_id || '';
                document.getElementById('edit_driver_id').value = booking.driver_id || '';
                document.getElementById('edit_pickup_location').value = booking.pickup_location || '';
                document.getElementById('edit_destination').value = booking.destination || '';
                document.getElementById('edit_fare').value = parseFloat(booking.fare).toFixed(2) || '';
                document.getElementById('edit_booking_date').value = booking.booking_date || '';
                document.getElementById('edit_pickup_time').value = booking.time || '';
                document.getElementById('edit_dropoff_time').value = booking.dropoff_time || '';
                document.getElementById('edit_status').value = booking.status || '';

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editBookingModal'));
                modal.show();
            } catch (error) {
                console.error('Error in editBooking:', error);
                alert('Failed to load booking data for editing. Please try again.');
            }
        }

        function deleteBooking(booking) {
            try {
                // Log booking for debugging
                console.log('Delete booking data:', booking);

                // Populate delete modal
                document.getElementById('delete_ride_id').value = booking.id || '';
                document.getElementById('delete_booking_details').innerHTML = `
                    Ride ID: ${booking.id || 'N/A'}<br>
                    Customer: ${booking.customer || 'N/A'}<br>
                    Pickup: ${booking.pickup || 'N/A'}<br>
                    Destination: ${booking.destination || 'N/A'}<br>
                    Booking Date: ${booking.booking_date || 'N/A'}
                `;

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('deleteBookingModal'));
                modal.show();
            } catch (error) {
                console.error('Error in deleteBooking:', error);
                alert('Failed to load booking data for deletion. Please try again.');
            }
        }

        function exportReport() {
            const exportBtn = document.querySelector('.btn i.fa-download').parentElement;
            if (exportBtn) {
                const originalContent = exportBtn.innerHTML;
                exportBtn.innerHTML = '<div class="spinner"></div> Exporting...';
                exportBtn.disabled = true;

                // Create CSV content
                let csv = 'Ride ID,Customer,Driver,Pickup Location,Destination,Booking Date,Fare,Pickup Time,Dropoff Time,Status\n';
                <?php 
                $bookings_result->data_seek(0);
                while ($row = $bookings_result->fetch_assoc()): 
                ?>
                csv += `"${row['ride_id']}","${row['customer_name'].replace(/"/g, '')}","${row['driver_name'].replace(/"/g, '')}","${row['pickup_location'].replace(/"/g, '')}","${row['destination'].replace(/"/g, '')}","${row['booking_date']}","${row['fare']}","${row['pickup_time']}","${row['dropoff_time']}","${row['status']}"\n`;
                <?php endwhile; ?>

                // Create download link
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'ride_bookings_' + new Date().toISOString().slice(0,10) + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);

                exportBtn.innerHTML = '<i class="fas fa-check"></i> Exported!';
                setTimeout(() => {
                    exportBtn.innerHTML = originalContent;
                    exportBtn.disabled = false;
                }, 2000);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Set minimum date to today for date inputs
            const today = new Date().toISOString().slice(0, 10);
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.setAttribute('min', today);
            });

            // Form validation for times
            function validateTimes(pickupId, dropoffId, dateId, formId) {
                const pickupInput = document.getElementById(pickupId);
                const dropoffInput = document.getElementById(dropoffId);
                const dateInput = document.getElementById(dateId);
                const form = document.getElementById(formId);
                
                if (pickupInput && dropoffInput && dateInput && form) {
                    form.addEventListener('submit', (e) => {
                        if (pickupInput.value && dropoffInput.value && dateInput.value) {
                            const pickupDateTime = new Date(`${dateInput.value}T${pickupInput.value}`);
                            const dropoffDateTime = new Date(`${dateInput.value}T${dropoffInput.value}`);
                            if (dropoffDateTime <= pickupDateTime) {
                                e.preventDefault();
                                alert('Drop-off time must be after pick-up time');
                            }
                        }
                    });
                }
            }

            validateTimes('pickup_time', 'dropoff_time', 'booking_date', 'addBookingForm');
            validateTimes('edit_pickup_time', 'edit_dropoff_time', 'edit_booking_date', 'editBookingForm');

            // Form validation for fare
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', () => {
                    if (parseFloat(input.value) <= 0) {
                        input.value = '0.01';
                    }
                });
            });

            // Stagger animation for stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });

            // Stagger animation for booking cards
            const bookingCards = document.querySelectorAll('.booking-card');
            bookingCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Hover effects for nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('mouseenter', () => {
                    link.style.transform = 'translateX(8px)';
                });
                
                link.addEventListener('mouseleave', () => {
                    if (!link.classList.contains('active')) {
                        link.style.transform = 'translateX(0)';
                    }
                });
            });

            // Mobile menu toggle
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
                    display: none;
                    @media (max-width: 768px) { display: block; }
                `;
                
                toggleBtn.addEventListener('click', () => {
                    sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' 
                        ? 'translateX(-100%)' 
                        : 'translateX(0px)';
                });
                
                header.prepend(toggleBtn);
            };
            
            if (window.innerWidth <= 768) {
                createMobileToggle();
            }

            // Real-time clock update
            const updateClock = () => {
                const now = new Date();
                const timeString = now.toLocaleString('en-US', {
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

            // Intersection Observer for fade-in animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -20px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.card, .alert, .booking-card').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });

            // Close alert
            const alertCloseBtn = document.querySelector('.alert .btn-close');
            if (alertCloseBtn) {
                alertCloseBtn.addEventListener('click', () => {
                    this.parentElement.remove();
                });
            }

            // Tooltip for truncated text
            document.querySelectorAll('[title]').forEach(el => {
                el.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.textContent = this.getAttribute('title');
                    tooltip.style.cssText = `
                        position: absolute;
                        background: var(--bg-tertiary);
                        color: var(--text-primary);
                        padding: 0.5rem;
                        border-radius: 4px;
                        font-size: 0.875rem;
                        border: 1px solid var(--border-color);
                        z-index: 1000;
                        pointer-events: none;
                        opacity: 0;
                        transition: opacity 0.2s ease;
                    `;
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
                    tooltip.style.left = '0px ${rect.left + window.scrollX};`
                    
                    setTimeout(() => {
                        tooltip.style.opacity = '1';
                    }, 10);
                    
                    this.addEventListener('mouseleave', () => {
                        tooltip.remove();
                    }, { once: true });
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$db->closeConnection();
?>