<?php
session_start();

// Check if admin is logged in
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: login.php");
//     exit();
// }

// Database connection
$servername = "localhost:3306";
$username = "root";
$password = "prageeth";
$dbname = "resident_villa";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'delete_user':
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserAccounts WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                if ($stmt->fetchColumn() == 0) {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM UserAccounts WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
            }
            exit;
            
        case 'update_user':
            try {
                // Validate email
                if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                    exit;
                }
                
                // Check if email already exists (excluding current user)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserAccounts WHERE email = ? AND user_id != ?");
                $stmt->execute([$_POST['email'], $_POST['user_id']]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE UserAccounts SET first_name = ?, last_name = ?, email = ?, user_role = ? WHERE user_id = ?");
                $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['user_role'], $_POST['user_id']]);
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
            }
            exit;
            
        case 'add_user':
            try {
                // Validate inputs
                if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                    exit;
                }
                
                if (strlen($_POST['password']) < 8) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
                    exit;
                }
                
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM UserAccounts WHERE email = ?");
                $stmt->execute([$_POST['email']]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
                
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO UserAccounts (first_name, last_name, email, password_hash, user_role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['email'], $password_hash, $_POST['user_role']]);
                echo json_encode(['success' => true, 'message' => 'User added successfully']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error adding user: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Fetch user accounts with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $where_conditions[] = "user_role = ?";
    $params[] = $role_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_sql = "SELECT COUNT(*) FROM UserAccounts $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch users
$sql = "SELECT * FROM UserAccounts $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts Dashboard - Resident Villa</title>

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

        /* Controls */
        .controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 2.5rem 0.5rem 0.75rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .search-box input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .search-box i {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .filter-select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23a0a0a0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
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

        /* Role Badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-admin { background: rgba(239, 68, 68, 0.1); color: var(--accent-red); }
        .badge-customer { background: rgba(16, 185, 129, 0.1); color: var(--accent-green); }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            color: var(--accent-blue);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background: var(--accent-blue);
            color: white;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        /* Confirmation Dialog */
        .confirm-dialog {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .confirm-dialog-content {
            background: var(--bg-card);
            margin: 15% auto;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            color: var(--text-primary);
            text-align: center;
        }

        .confirm-dialog-buttons {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        /* Alert */
        .alert {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: none;
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

        .alert-close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .alert-close:hover {
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

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: auto;
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
            <span>User Accounts Management</span>
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
            <a class="nav-link" href="DriverRegister.php">
                <i class="fas fa-id-card nav-icon"></i>
                <span>Driver Management</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link active" href="UserAccountsDashboard.php">
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
                <span>User Accounts</span>
            </nav>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-user-cog"></i> User Accounts Management</h1>
            <button class="btn">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>

        <!-- Alert -->
        <div id="alert" class="alert">
            <span id="alertMessage"></span>
            <span class="alert-close" onclick="hideAlert()">×</span>
        </div>

        <!-- Controls -->
        <div class="controls fade-in">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                <i class="fas fa-search"></i>
            </div>
            <select id="roleFilter" class="filter-select">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
            </select>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New User
            </button>
        </div>

        <!-- User Table -->
        <div class="card fade-in">
            <div class="card-header">
                <i class="fas fa-users"></i>
                User Accounts
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['user_role']; ?>">
                                        <?php echo ucfirst($user['user_role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="openConfirmDelete(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No users found</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                               class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New User</h2>
                <span class="close" onclick="closeModal()">×</span>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="formAction" name="action">
                
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="first_name" required pattern="[A-Za-z\s]+" title="First name should only contain letters and spaces">
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="last_name" required pattern="[A-Za-z\s]+" title="Last name should only contain letters and spaces">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="userRole">Role</label>
                    <select id="userRole" name="user_role" required>
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save User
                </button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div id="confirmDeleteModal" class="confirm-dialog">
        <div class="confirm-dialog-content">
            <h2 class="modal-title">Confirm Delete</h2>
            <p id="confirmDeleteMessage"></p>
            <div class="confirm-dialog-buttons">
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button class="btn" onclick="closeConfirmDelete()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', debounce(function() {
            filterTable();
        }, 300));

        document.getElementById('roleFilter').addEventListener('change', function() {
            filterTable();
        });

        function filterTable() {
            const search = document.getElementById('searchInput').value.trim();
            const role = document.getElementById('roleFilter').value;
            const url = new URL(window.location);
            url.searchParams.set('search', search);
            url.searchParams.set('role', role);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('formAction').value = 'add_user';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('userModal').style.display = 'block';
        }

        function openEditModal(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'update_user';
            document.getElementById('userId').value = user.user_id;
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name;
            document.getElementById('email').value = user.email;
            document.getElementById('userRole').value = user.user_role;
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('password').required = false;
            document.getElementById('userModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Delete confirmation
        let currentUserId = null;
        function openConfirmDelete(userId, userName) {
            currentUserId = userId;
            document.getElementById('confirmDeleteMessage').textContent = 
                `Are you sure you want to delete ${userName}? This action cannot be undone.`;
            document.getElementById('confirmDeleteModal').style.display = 'block';
        }

        function closeConfirmDelete() {
            document.getElementById('confirmDeleteModal').style.display = 'none';
            currentUserId = null;
        }

        // Form submission
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="spinner"></div> Saving...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch('UserAccountsDashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                if (data.success) {
                    closeModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                showAlert('An error occurred: ' + error.message, 'danger');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            });
        });

        // Delete user
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (currentUserId) {
                const deleteBtn = this;
                const originalContent = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<div class="spinner"></div> Deleting...';
                deleteBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', currentUserId);
                
                fetch('UserAccountsDashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showAlert(data.message, data.success ? 'success' : 'danger');
                    deleteBtn.innerHTML = originalContent;
                    deleteBtn.disabled = false;
                    if (data.success) {
                        closeConfirmDelete();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    showAlert('An error occurred: ' + error.message, 'danger');
                    deleteBtn.innerHTML = originalContent;
                    deleteBtn.disabled = false;
                });
            }
        });

        // Show alert messages
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            const alertMessage = document.getElementById('alertMessage');
            alert.className = `alert alert-${type} fade-in`;
            alertMessage.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function hideAlert() {
            document.getElementById('alert').style.display = 'none';
        }

        // Debounce function for search input
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Document ready
        document.addEventListener('DOMContentLoaded', function() {
            // Close modals when clicking outside
            window.onclick = function(event) {
                const userModal = document.getElementById('userModal');
                const confirmModal = document.getElementById('confirmDeleteModal');
                if (event.target === userModal) {
                    closeModal();
                }
                if (event.target === confirmModal) {
                    closeConfirmDelete();
                }
            };

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
            updateClock();

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

            document.querySelectorAll('.card, .controls').forEach(el => {
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

            // Export functionality
            const exportBtn = document.querySelector('.btn i.fa-download').parentElement;
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<div class="spinner"></div> Exporting...';
                    this.disabled = true;
                    
                    // Simulate export process
                    setTimeout(() => {
                        const csvContent = [
                            'ID,First Name,Last Name,Email,Role,Created At',
                            ...<?php echo json_encode(array_map(function($user) {
                                return [
                                    $user['user_id'],
                                    $user['first_name'],
                                    $user['last_name'],
                                    $user['email'],
                                    $user['user_role'],
                                    date('M j, Y g:i A', strtotime($user['created_at']))
                                ];
                            }, $users)); ?>.map(row => row.join(','))
                        ].join('\n');
                        
                        const blob = new Blob([csvContent], { type: 'text/csv' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'user_accounts_export.csv';
                        a.click();
                        window.URL.revokeObjectURL(url);
                        
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