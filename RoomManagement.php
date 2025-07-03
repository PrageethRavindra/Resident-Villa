<?php
// Enable error reporting for debugging (disable display_errors in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['email'])) {
    error_log("Unauthorized access attempt: email not set");
    header('Location: SignIn.php');
    exit;
}

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

    try {
        switch ($_POST['action']) {
            case 'add_room':
                $room_type = filter_var($_POST['room_type'], FILTER_SANITIZE_STRING);
                $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $max_adults = filter_var($_POST['max_adults'], FILTER_SANITIZE_NUMBER_INT);
                $max_children = filter_var($_POST['max_children'], FILTER_SANITIZE_NUMBER_INT);

                $stmt = $conn->prepare("INSERT INTO HotelRoom (room_type, price, max_adults, max_children) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sdii", $room_type, $price, $max_adults, $max_children);

                if ($stmt->execute()) {
                    $message = "Room added successfully!";
                    $messageType = "success";
                } else {
                    throw new Exception("Error adding room: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'update_room':
                $room_id = filter_var($_POST['room_id'], FILTER_SANITIZE_NUMBER_INT);
                $room_type = filter_var($_POST['room_type'], FILTER_SANITIZE_STRING);
                $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $max_adults = filter_var($_POST['max_adults'], FILTER_SANITIZE_NUMBER_INT);
                $max_children = filter_var($_POST['max_children'], FILTER_SANITIZE_NUMBER_INT);

                $stmt = $conn->prepare("UPDATE HotelRoom SET room_type = ?, price = ?, max_adults = ?, max_children = ? WHERE room_id = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("sdiii", $room_type, $price, $max_adults, $max_children, $room_id);

                if ($stmt->execute()) {
                    $message = "Room updated successfully!";
                    $messageType = "success";
                } else {
                    throw new Exception("Error updating room: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'delete_room':
                $room_id = filter_var($_POST['room_id'], FILTER_SANITIZE_NUMBER_INT);

                // First check if the room has any bookings
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM RoomBookings WHERE room_id = ?");
                $check_stmt->bind_param("i", $room_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $booking_count = $check_result->fetch_row()[0];
                $check_stmt->close();

                if ($booking_count > 0) {
                    $message = "Cannot delete room with active bookings.";
                    $messageType = "danger";
                } else {
                    $stmt = $conn->prepare("DELETE FROM HotelRoom WHERE room_id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("i", $room_id);

                    if ($stmt->execute()) {
                        $message = "Room deleted successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Error deleting room: " . $stmt->error);
                    }
                    $stmt->close();
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
            header('Location: RoomManagement.php');
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
            header('Location: RoomManagement.php');
            exit;
        }
    }
}

// Fetch all rooms
$rooms_query = $conn->prepare("SELECT * FROM HotelRoom ORDER BY room_type");
if (!$rooms_query) {
    error_log("Rooms query prepare failed: " . $conn->error);
    $message = "Error fetching rooms: " . $conn->error;
    $messageType = "danger";
    $rooms_result = null;
} else {
    $rooms_query->execute();
    $rooms_result = $rooms_query->get_result();
    $rooms_query->close();
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
    <title>Room Management - Resident Villa</title>
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

        .btn-danger {
            background: var(--accent-red);
            border-color: var(--accent-red);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
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
            <span><?php echo htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin'); ?> (Admin)</span>
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
        <a class="nav-link active" href="RoomManagement.php">
                <i class="fas fa-hotel nav-icon"></i>
                <span>Room management</span>
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
            <a class="nav-link" href="staff/cashier.php">
            <i class="fas fa-cash-register nav-icon"></i>
                <span>Cashier</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="staff/checkout.php">
            <i class="fas fa-credit-card nav-icon"></i>
                <span>Checkout</span>
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
                <a href="AdminDashboard.php">Home</a>
                <span>/</span>
                <span>Room Management</span>
            </nav>
        </div>

        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-bed"></i> Room Management</h1>
            <button class="btn btn-primary" onclick="showAddRoomModal()">
                <i class="fas fa-plus"></i> Add New Room
            </button>
        </div>

        <div id="alert" class="alert <?php echo $message ? 'alert-' . $messageType : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close"></button>
        </div>

        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-list"></i>
                Hotel Rooms
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Room ID</th>
                                <th>Room Type</th>
                                <th>Price (LKR)</th>
                                <th>Max Adults</th>
                                <th>Max Children</th>
                                <th>Total Capacity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rooms_result && $rooms_result->num_rows > 0): ?>
                                <?php while ($room = $rooms_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['room_id']); ?></td>
                                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td><?php echo number_format($room['price'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($room['max_adults']); ?></td>
                                        <td><?php echo htmlspecialchars($room['max_children']); ?></td>
                                        <td><?php echo htmlspecialchars($room['total_capacity']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="showEditRoomModal(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDeleteRoom(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_type']); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="py-4">
                                            <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No rooms found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-plus"></i> Add New Room</h2>
                <span class="close" onclick="closeAddRoomModal()">×</span>
            </div>
            <form id="addRoomForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_room">
                    <div class="form-group">
                        <label for="add_room_type">Room Type</label>
                        <input type="text" class="form-control" name="room_type" id="add_room_type" required>
                    </div>
                    <div class="form-group">
                        <label for="add_price">Price (LKR)</label>
                        <input type="number" class="form-control" name="price" id="add_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="add_max_adults">Max Adults</label>
                        <input type="number" class="form-control" name="max_adults" id="add_max_adults" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="add_max_children">Max Children</label>
                        <input type="number" class="form-control" name="max_children" id="add_max_children" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddRoomModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Room
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-edit"></i> Edit Room</h2>
                <span class="close" onclick="closeEditRoomModal()">×</span>
            </div>
            <form id="editRoomForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_room">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    <div class="form-group">
                        <label for="edit_room_type">Room Type</label>
                        <input type="text" class="form-control" name="room_type" id="edit_room_type" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">Price (LKR)</label>
                        <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_max_adults">Max Adults</label>
                        <input type="number" class="form-control" name="max_adults" id="edit_max_adults" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_max_children">Max Children</label>
                        <input type="number" class="form-control" name="max_children" id="edit_max_children" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditRoomModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Room
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
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

        function showAddRoomModal() {
            document.getElementById('addRoomModal').style.display = 'block';
            document.getElementById('addRoomForm').reset();
        }

        function closeAddRoomModal() {
            document.getElementById('addRoomModal').style.display = 'none';
        }

        function showEditRoomModal(room) {
            document.getElementById('editRoomModal').style.display = 'block';
            document.getElementById('edit_room_id').value = room.room_id;
            document.getElementById('edit_room_type').value = room.room_type;
            document.getElementById('edit_price').value = room.price;
            document.getElementById('edit_max_adults').value = room.max_adults;
            document.getElementById('edit_max_children').value = room.max_children;
        }

        function closeEditRoomModal() {
            document.getElementById('editRoomModal').style.display = 'none';
        }

        function confirmDeleteRoom(roomId, roomType) {
            if (confirm(`Are you sure you want to delete the room "${roomType}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete_room');
                formData.append('room_id', roomId);

                fetch('RoomManagement.php', {
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
                    console.error('Delete room error:', error);
                    showAlert('Error: ' + error.message, 'danger');
                });
            }
        }

        document.getElementById('addRoomForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Saving...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('RoomManagement.php', {
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
                    closeAddRoomModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Add room error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        document.getElementById('editRoomForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Updating...';
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('RoomManagement.php', {
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
                    closeEditRoomModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Update room error:', error);
                showAlert('Error: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.onclick = function(event) {
                const addModal = document.getElementById('addRoomModal');
                const editModal = document.getElementById('editRoomModal');
                
                if (event.target === addModal) {
                    closeAddRoomModal();
                }
                if (event.target === editModal) {
                    closeEditRoomModal();
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