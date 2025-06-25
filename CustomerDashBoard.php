<?php
// Include the DatabaseConnection class
require_once __DIR__ . '/db/DatabaseConnection.php';

// Handle search functionality
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Resident Villa</title>

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

        .btn-secondary {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
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

        /* Color variants */
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

        .input-group-text {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 8px 0 0 8px;
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

        .btn-close {
            background: transparent;
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .btn-close:hover {
            opacity: 1;
        }

        /* Pagination */
        .pagination {
            gap: 0.5rem;
        }

        .page-item .page-link {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .page-item.active .page-link {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        .page-item .page-link:hover {
            background: var(--hover-bg);
            border-color: var(--accent-blue);
            color: var(--text-primary);
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
            <span>Customer Management</span>
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
            <a class="nav-link active" href="CustomerDashBoard.php">
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
                <span>Customer Management</span>
            </nav>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-users"></i> Customer Dashboard</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-plus"></i> Add Customer
                </button>
                <button class="btn" onclick="exportCustomers()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid fade-in">
            <!-- Total Customers -->
            <div class="stat-card stat-blue">
                <div class="stat-header">
                    <div class="stat-title">Total Customers</div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS total FROM Customers";
                        $result = $db->conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            $totalRows = $result->fetch_assoc()['total'];
                            echo "<div class='stat-value'>" . number_format($totalRows) . "</div>";
                            echo "<div class='stat-subtitle'>Registered customers</div>";
                        } else {
                            echo "<div class='stat-value'>0</div>";
                            echo "<div class='stat-subtitle'>No customers</div>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Active Users -->
            <div class="stat-card stat-green">
                <div class="stat-header">
                    <div class="stat-title">Active Users</div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS total FROM UserAccounts WHERE user_role = 'customer'";
                        $result = $db->conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            $totalRows = $result->fetch_assoc()['total'];
                            echo "<div class='stat-value'>" . number_format($totalRows) . "</div>";
                            echo "<div class='stat-subtitle'>User accounts</div>";
                        } else {
                            echo "<div class='stat-value'>0</div>";
                            echo "<div class='stat-subtitle'>No user accounts</div>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Room Bookings -->
            <div class="stat-card stat-yellow">
                <div class="stat-header">
                    <div class="stat-title">Room Bookings</div>
                    <div class="stat-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS total FROM RoomBookings WHERE status = 'confirmed'";
                        $result = $db->conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            $totalRows = $result->fetch_assoc()['total'];
                            echo "<div class='stat-value'>" . number_format($totalRows) . "</div>";
                            echo "<div class='stat-subtitle'>Active bookings</div>";
                        } else {
                            echo "<div class='stat-value'>0</div>";
                            echo "<div class='stat-subtitle'>No bookings</div>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Ride Bookings -->
            <div class="stat-card stat-purple">
                <div class="stat-header">
                    <div class="stat-title">Ride Bookings</div>
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS total FROM RideBookings WHERE status IN ('scheduled', 'in-progress')";
                        $result = $db->conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            $totalRows = $result->fetch_assoc()['total'];
                            echo "<div class='stat-value'>" . number_format($totalRows) . "</div>";
                            echo "<div class='stat-subtitle'>Active rides</div>";
                        } else {
                            echo "<div class='stat-value'>0</div>";
                            echo "<div class='stat-subtitle'>No active rides</div>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card fade-in mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search by name, email, or phone..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group w-100" role="group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="CustomerDashBoard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customer List -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-list"></i>
                Customer List
                <small class="text-muted ms-auto">
                    <?php
                    if (!empty($searchTerm)) {
                        echo "Search results for: <strong>" . htmlspecialchars($searchTerm) . "</strong>";
                    }
                    ?>
                </small>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Joined</th>
                                <th>Bookings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $db = new DatabaseConnection();
                                
                                // Build search query
                                $whereClause = "";
                                if (!empty($searchTerm)) {
                                    $searchTerm = $db->conn->real_escape_string($searchTerm);
                                    $whereClause = "WHERE (first_name LIKE '%$searchTerm%' OR last_name LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%' OR phone LIKE '%$searchTerm%')";
                                }
                                
                                // Get total count for pagination
                                $countSql = "SELECT COUNT(*) AS total FROM Customers $whereClause";
                                $countResult = $db->conn->query($countSql);
                                $totalRecords = $countResult ? $countResult->fetch_assoc()['total'] : 0;
                                $totalPages = ceil($totalRecords / $recordsPerPage);
                                
                                // Main query with pagination
                                $sql = "SELECT c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.address, c.created_at,
                                        (SELECT COUNT(*) FROM RoomBookings rb WHERE rb.customer_id = c.customer_id) AS room_bookings,
                                        (SELECT COUNT(*) FROM RideBookings ride WHERE ride.customer_id = c.customer_id) AS ride_bookings
                                        FROM Customers c 
                                        $whereClause 
                                        ORDER BY c.created_at DESC 
                                        LIMIT $recordsPerPage OFFSET $offset";
                                
                                $result = $db->conn->query($sql);
                                
                                if ($result && $result->num_rows > 0) {
                                    $counter = $offset + 1;
                                    while ($row = $result->fetch_assoc()) {
                                        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                                        $totalBookings = $row['room_bookings'] + $row['ride_bookings'];
                                        
                                        echo "<tr>
                                                <td>{$counter}</td>
                                                <td>
                                                    <strong>{$fullName}</strong>
                                                    <br><small class='text-muted'>ID: {$row['customer_id']}</small>
                                                </td>
                                                <td>" . htmlspecialchars($row['email']) . "</td>
                                                <td>" . htmlspecialchars($row['phone'] ?: 'N/A') . "</td>
                                                <td title='" . htmlspecialchars($row['address'] ?: 'N/A') . "'>" . htmlspecialchars(substr($row['address'] ?: 'N/A', 0, 30)) . (strlen($row['address']) > 30 ? '...' : '') . "</td>
                                                <td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>
                                                <td>
                                                    <span class='badge badge-primary'>{$totalBookings} total</span>
                                                    <br><small class='text-muted'>{$row['room_bookings']} rooms, {$row['ride_bookings']} rides</small>
                                                </td>
                                                <td>
                                                    <div class='btn-group btn-group-sm' role='group'>
                                                        <button type='button' class='btn btn-primary' onclick='viewCustomer({$row['customer_id']})' title='View Details'>
                                                            <i class='fas fa-eye'></i>
                                                        </button>
                                                        <button type='button' class='btn btn-secondary' onclick='editCustomer({$row['customer_id']})' title='Edit'>
                                                            <i class='fas fa-edit'></i>
                                                        </button>
                                                        <button type='button' class='btn btn-danger' onclick='deleteCustomer({$row['customer_id']})' title='Delete'>
                                                            <i class='fas fa-trash'></i>
                                                        </button>
                                                    </div>
                                                </td>
                                              </tr>";
                                        $counter++;
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>
                                            <div class='py-4'>
                                                <i class='fas fa-users fa-3x text-muted mb-3'></i>
                                                <p class='text-muted'>" . (!empty($searchTerm) ? 'No customers found matching your search.' : 'No customers found.') . "</p>
                                            </div>
                                          </td></tr>";
                                }
                                
                                $db->closeConnection();
                            } catch (Exception $e) {
                                echo "<tr><td colspan='8' class='text-danger text-center'>Error loading customer data</td></tr>";
                                error_log("Database error: " . $e->getMessage());
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Customer pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($searchTerm) ? '&search='.urlencode($searchTerm) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchTerm) ? '&search='.urlencode($searchTerm) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($searchTerm) ? '&search='.urlencode($searchTerm) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $recordsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> customers
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCustomerForm">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomer()">Save Customer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function(){
            location.reload();
        }, 300000);

        document.addEventListener('DOMContentLoaded', function() {
            // Stagger animation for stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = ${index * 0.1}s;
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

            document.querySelectorAll('.card, .stat-card').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });

            // Add tooltip functionality for truncated text
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
                    
                    setTimeout(() => {
                        tooltip.style.opacity = '1';
                    }, 100);
                    
                    this.addEventListener('mouseleave', () => {
                        tooltip.remove();
                    }, { once: true });
                });
            });

            // Add export functionality
            const exportBtn = document.querySelector('button[onclick="exportCustomers()"]');
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

        function viewCustomer(customerId) {
            alert('View customer details for ID: ' + customerId);
        }
        
        function editCustomer(customerId) {
            alert('Edit customer with ID: ' + customerId);
        }
        
        function deleteCustomer(customerId) {
            if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
                alert('Delete customer with ID: ' + customerId);
            }
        }
        
        function saveCustomer() {
            const form = document.getElementById('addCustomerForm');
            const formData = new FormData(form);
            
            alert('Save customer functionality to be implemented');
        }

        // Add CSS for mobile toggle button
        const style = document.createElement('style');
        style.textContent = `
            @media (max-width: 768px) {
                .header button {
                    display: block !important;
                }
                
                .sidebar {
                    z-index: 1000;
                    box-shadow: var(--shadow-lg);
                }
                
                .main-content {
                    transition: margin-left 0.3s ease;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>