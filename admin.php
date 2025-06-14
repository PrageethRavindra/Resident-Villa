<?php
// Include the DatabaseConnection class
require_once __DIR__ . './db/DatabaseConnection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Resident Villa</title>
    
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
            justify-content: between;
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

        .export-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-btn:hover {
            background: var(--hover-bg);
            border-color: var(--accent-blue);
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

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Cards */
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
        .badge-primary { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }

        /* System Status Grid */
        .system-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .status-item {
            text-align: center;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .status-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .status-label {
            font-size: 0.875rem;
            color: var(--text-muted);
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

            .content-grid {
                grid-template-columns: 1fr;
            }

            .system-status {
                grid-template-columns: repeat(2, 1fr);
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
            <span>Welcome, Admin</span>
            <span>|</span>
            <span><?php echo date('M d, Y H:i'); ?></span>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="nav-item">
            <a class="nav-link active" href="admin.php">
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
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav class="breadcrumb">
                <a href="#">Home</a>
                <span>/</span>
                <span>Dashboard Overview</span>
            </nav>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Dashboard Overview</h1>
            <button class="export-btn">
                <i class="fas fa-download"></i>
                Export Report
            </button>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid fade-in">
            <!-- Customers Card -->
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
                        
                        if ($result) {
                            $totalRows = $result->fetch_assoc()['total'];
                            echo "<div class='stat-value'>" . number_format($totalRows) . "</div>";
                            echo "<div class='stat-subtitle'>Registered customers</div>";
                        } else {
                            echo "<div class='stat-value'>0</div>";
                            echo "<div class='stat-subtitle'>No customers found</div>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Drivers Card -->
            <div class="stat-card stat-green">
                <div class="stat-header">
                    <div class="stat-title">Active Drivers</div>
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS total FROM Drivers";
                        $result = $db->conn->query($sql);
                        $totalDrivers = $result ? $result->fetch_assoc()['total'] : 0;
                        
                        $sql = "SELECT COUNT(*) AS available FROM Drivers WHERE status = 'available'";
                        $result = $db->conn->query($sql);
                        $availableDrivers = $result ? $result->fetch_assoc()['available'] : 0;
                        
                        echo "<div class='stat-value'>" . number_format($totalDrivers) . "</div>";
                        echo "<div class='stat-subtitle'>" . $availableDrivers . " available now</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Room Bookings Card -->
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
                        $sql = "SELECT COUNT(*) AS total FROM RoomBookings";
                        $result = $db->conn->query($sql);
                        $totalBookings = $result ? $result->fetch_assoc()['total'] : 0;
                        
                        $sql = "SELECT COUNT(*) AS active FROM RoomBookings WHERE status = 'confirmed' AND check_in_date <= CURDATE() AND check_out_date >= CURDATE()";
                        $result = $db->conn->query($sql);
                        $activeBookings = $result ? $result->fetch_assoc()['active'] : 0;
                        
                        echo "<div class='stat-value'>" . number_format($totalBookings) . "</div>";
                        echo "<div class='stat-subtitle'>" . $activeBookings . " active today</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Ride Bookings Card -->
            <div class="stat-card stat-purple">
                <div class="stat-header">
                    <div class="stat-title">Ride Bookings</div>
                    <div class="stat-icon">
                        <i class="fas fa-route"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS total FROM RideBookings";
                        $result = $db->conn->query($sql);
                        $totalRides = $result ? $result->fetch_assoc()['total'] : 0;
                        
                        $sql = "SELECT COUNT(*) AS today FROM RideBookings WHERE booking_date = CURDATE()";
                        $result = $db->conn->query($sql);
                        $todayRides = $result ? $result->fetch_assoc()['today'] : 0;
                        
                        echo "<div class='stat-value'>" . number_format($totalRides) . "</div>";
                        echo "<div class='stat-subtitle'>" . $todayRides . " scheduled today</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='stat-value loading'><div class='spinner'></div>Error</div>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid fade-in">
            <!-- Recent Room Bookings -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bed"></i>
                    Recent Room Bookings
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT rb.booking_id, rb.room_number, rb.check_in_date, rb.check_out_date, rb.status,
                                CONCAT(c.first_name, ' ', c.last_name) AS customer_name
                                FROM RoomBookings rb
                                JOIN Customers c ON rb.customer_id = c.customer_id
                                ORDER BY rb.created_at DESC
                                LIMIT 5";
                        
                        $result = $db->conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            echo "<div class='table-container'>";
                            echo "<table class='table'>";
                            echo "<thead><tr><th>Room</th><th>Customer</th><th>Check-in</th><th>Status</th></tr></thead>";
                            echo "<tbody>";
                            
                            while ($row = $result->fetch_assoc()) {
                                $statusClass = $row['status'] == 'confirmed' ? 'success' : ($row['status'] == 'canceled' ? 'danger' : 'warning');
                                echo "<tr>";
                                echo "<td><strong>" . $row['room_number'] . "</strong></td>";
                                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['check_in_date'])) . "</td>";
                                echo "<td><span class='badge badge-" . $statusClass . "'>" . ucfirst($row['status']) . "</span></td>";
                                echo "</tr>";
                            }
                            
                            echo "</tbody></table>";
                            echo "</div>";
                        } else {
                            echo "<p style='color: var(--text-muted); text-align: center; padding: 2rem;'>No recent room bookings found.</p>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<p style='color: var(--accent-red); text-align: center; padding: 2rem;'>Error loading recent bookings</p>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>

            <!-- Recent Ride Bookings -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-car"></i>
                    Recent Ride Bookings
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT ride.ride_id, ride.pickup_location, ride.destination, ride.booking_date, ride.status,
                                CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                                d.full_name AS driver_name
                                FROM RideBookings ride
                                JOIN Customers c ON ride.customer_id = c.customer_id
                                JOIN Drivers d ON ride.driver_id = d.driver_id
                                ORDER BY ride.created_at DESC
                                LIMIT 5";
                        
                        $result = $db->conn->query($sql);
                        
                        if ($result && $result->num_rows > 0) {
                            echo "<div class='table-container'>";
                            echo "<table class='table'>";
                            echo "<thead><tr><th>Route</th><th>Customer</th><th>Date</th><th>Status</th></tr></thead>";
                            echo "<tbody>";
                            
                            while ($row = $result->fetch_assoc()) {
                                $statusClass = $row['status'] == 'completed' ? 'success' : ($row['status'] == 'canceled' ? 'danger' : 'primary');
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars(substr($row['pickup_location'], 0, 20)) . "...</strong></td>";
                                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['booking_date'])) . "</td>";
                                echo "<td><span class='badge badge-" . $statusClass . "'>" . ucfirst($row['status']) . "</span></td>";
                                echo "</tr>";
                            }
                            
                            echo "</tbody></table>";
                            echo "</div>";
                        } else {
                            echo "<p style='color: var(--text-muted); text-align: center; padding: 2rem;'>No recent ride bookings found.</p>";
                        }
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<p style='color: var(--accent-red); text-align: center; padding: 2rem;'>Error loading recent rides</p>";
                        error_log("Database error: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i>
                System Status
            </div>
            <div class="system-status">
                <div class="status-item">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS checkins FROM RoomBookings WHERE check_in_date = CURDATE() AND status = 'confirmed'";
                        $result = $db->conn->query($sql);
                        $checkins = $result ? $result->fetch_assoc()['checkins'] : 0;
                        
                        echo "<div class='status-value' style='color: var(--accent-blue);'>" . $checkins . "</div>";
                        echo "<div class='status-label'>Check-ins Today</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='status-value' style='color: var(--accent-red);'>-</div>";
                        echo "<div class='status-label'>Error</div>";
                    }
                    ?>
                </div>

                <div class="status-item">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS users FROM UserAccounts";
                        $result = $db->conn->query($sql);
                        $users = $result ? $result->fetch_assoc()['users'] : 0;
                        
                        echo "<div class='status-value' style='color: var(--accent-purple);'>" . $users . "</div>";
                        echo "<div class='status-label'>User Accounts</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='status-value' style='color: var(--accent-red);'>-</div>";
                        echo "<div class='status-label'>Error</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function(){
            location.reload();
        }, 300000);
        
        // Add smooth animations on load
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

            // Add loading animation to data that might be slow
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(value => {
                if (value.textContent.includes('Error')) {
                    value.style.color = 'var(--accent-red)';
                }
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
            
            // Add mobile toggle for smaller screens
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
            
            // Update clock every minute
            setInterval(updateClock, 60000);

            // Add smooth scrolling for any anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

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

            // Observe all cards and sections
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

            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case '1':
                            e.preventDefault();
                            window.location.href = 'admin.php';
                            break;
                        case '2':
                            e.preventDefault();
                            window.location.href = 'RoomBookingDashBoard.php';
                            break;
                        case '3':
                            e.preventDefault();
                            window.location.href = 'RideBookingDashBoard.php';
                            break;
                        case '4':
                            e.preventDefault();
                            window.location.href = 'CustomerDashBoard.php';
                            break;
                    }
                }
            });

            // Performance monitoring
            if ('performance' in window) {
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        const perfData = performance.timing;
                        const loadTime = perfData.loadEventEnd - perfData.navigationStart;
                        
                        if (loadTime > 3000) {
                            console.warn('Dashboard loaded slowly:', loadTime + 'ms');
                        }
                    }, 0);
                });
            }

            // Add export functionality
            const exportBtn = document.querySelector('.export-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    // Add loading state
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<div class="spinner"></div> Exporting...';
                    this.disabled = true;
                    
                    // Simulate export process
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
                </div>

                <div class="status-item">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COUNT(*) AS checkouts FROM RoomBookings WHERE check_out_date = CURDATE() AND status = 'confirmed'";
                        $result = $db->conn->query($sql);
                        $checkouts = $result ? $result->fetch_assoc()['checkouts'] : 0;
                        
                        echo "<div class='status-value' style='color: var(--accent-yellow);'>" . $checkouts . "</div>";
                        echo "<div class='status-label'>Check-outs Today</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='status-value' style='color: var(--accent-red);'>-</div>";
                        echo "<div class='status-label'>Error</div>";
                    }
                    ?>
                </div>

                <div class="status-item">
                    <?php
                    try {
                        $db = new DatabaseConnection();
                        $sql = "SELECT COALESCE(SUM(fare), 0) AS revenue FROM RideBookings WHERE booking_date = CURDATE() AND status != 'canceled'";
                        $result = $db->conn->query($sql);
                        $revenue = $result ? $result->fetch_assoc()['revenue'] : 0;
                        
                        echo "<div class='status-value' style='color: var(--accent-green);'>$" . number_format($revenue, 0) . "</div>";
                        echo "<div class='status-label'>Today's Revenue</div>";
                        
                        $db->closeConnection();
                    } catch (Exception $e) {
                        echo "<div class='status-value' style='color: var(--accent-red);'>-</div>";
                        echo "<div class='status-label'>Error</div>";
                    }
                    ?>