<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script type="text/javascript" src="Funciones.js"></script>
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-light bg-light p-3">
        <div class="d-flex col-12 col-md-3 col-lg-2 mb-2 mb-lg-0 flex-wrap flex-md-nowrap justify-content-between">
            <a class="navbar-brand" href="#">
                Admin Dashboard
            </a>
        </div>
    </nav>
    <!-- End Header -->

    <!-- Main Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="admin.php">
                                Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="RoomBookingDashBoard.php">
                                Room Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="RideBookingDashBoard.php">
                                Ride Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="CustomerDashBoard.php">
                                Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="DriverRegister.php">
                                Driver Register
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- End Sidebar -->

            <main class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Breadcrumbs -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Overview</li>
                    </ol>
                </nav>
                <!-- End Breadcrumbs -->

                <h1 class="h2">Dashboard</h1>
                <p>This is the homepage of a simple admin interface.</p>

                <!-- Cards -->
                <div class="row my-4">
    <!-- Count Customers -->
    <div class="col-12 col-md-6 col-lg-3 mb-4 mb-lg-0">
        <div class="card">
            <h5 class="card-header">Customers</h5>
            <div class="card-body">
                <?php
                    $servername = "localhost:3305";
                    $username = "root";
                    $password = "123@prageeth";
                    $dbname = "resident_villa";

                    // Create connection
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Check connection
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // SQL query to count customers
                    $sql = "SELECT COUNT(*) AS total FROM signup";
                    $result = $conn->query($sql);

                    if ($result) {
                        $totalRows = $result->fetch_assoc()['total'];
                        echo "<p class='card-text'>".$totalRows."</p>";
                    } else {
                        echo "<p class='card-text'>0</p>"; // If query fails or returns 0
                    }

                    $conn->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Count Drivers -->
    <div class="col-12 col-md-6 mb-4 mb-lg-0 col-lg-3">
        <div class="card">
            <h5 class="card-header">Drivers</h5>
            <div class="card-body">
                <?php
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // SQL query to count drivers
                    $sql = "SELECT COUNT(*) AS total FROM drivertb";
                    $result = $conn->query($sql);

                    if ($result) {
                        $totalRows = $result->fetch_assoc()['total'];
                        echo "<p class='card-text'>".$totalRows."</p>";
                    } else {
                        echo "<p class='card-text'>0</p>";
                    }

                    $conn->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Count Room Bookings -->
    <div class="col-12 col-md-6 mb-4 mb-lg-0 col-lg-3">
        <div class="card">
            <h5 class="card-header">Room Bookings</h5>
            <div class="card-body">
                <?php
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // SQL query to count room bookings
                    $sql = "SELECT COUNT(*) AS total FROM room_bookings";
                    $result = $conn->query($sql);

                    if ($result) {
                        $totalRows = $result->fetch_assoc()['total'];
                        echo "<p class='card-text'>".$totalRows."</p>";
                    } else {
                        echo "<p class='card-text'>0</p>";
                    }

                    $conn->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Count Ride Bookings -->
    <div class="col-12 col-md-6 mb-4 mb-lg-0 col-lg-3">
        <div class="card">
            <h5 class="card-header">Ride Bookings</h5>
            <div class="card-body">
                <?php
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // SQL query to count ride bookings
                    $sql = "SELECT COUNT(*) AS total FROM ridetb";
                    $result = $conn->query($sql);

                    if ($result) {
                        $totalRows = $result->fetch_assoc()['total'];
                        echo "<p class='card-text'>".$totalRows."</p>";
                    } else {
                        echo "<p class='card-text'>0</p>";
                    }

                    $conn->close();
                ?>
            </div>
        </div>
    </div>
</div>

            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>
