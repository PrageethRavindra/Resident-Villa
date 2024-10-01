<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomBooking Dashboard</title>

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
                <!-- BreadCrumbs -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Room Booking</li>
                    </ol>
                </nav>
                <!-- End BreadCrumbs -->

                <h1 class="h2">Dashboard</h1>

                <!-- First Row -->
                <div class="row my-4">
                    <div class="col-12 col-md-6 col-lg-3 mb-4 mb-lg-0">
                        <div class="card">
                            <h5 class="card-header">Customers</h5>
                            <div class="card-body">
                                <?php
                                    $servername = "localhost:3305";
                                    $username = "root";
                                    $password = "123@prageeth";
                                    $dbname = "resident_villa";

                                    $conn = new mysqli($servername, $username, $password, $dbname);
                                    // Check connection
                                    if ($conn->connect_error) {
                                        die("Connection failed: " . $conn->connect_error);
                                    }

                                    // SQL query to count rows
                                    $sql = "SELECT COUNT(*) AS total FROM room_bookings";
                                    $result = $conn->query($sql);

                                    // Check if the query was successful
                                    if ($result) {
                                        $row = $result->fetch_assoc();
                                        $totalRows = isset($row['total']) ? $row['total'] : 0;
                                    } else {
                                        $totalRows = 0; // Default value if query fails
                                    }

                                    $conn->close();

                                    echo "<p class='card-text'>".$totalRows."</p>";
                                ?>
                                <p class="card-text"></p>
                                <p id=json class="card-text text-success"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of First Row -->

                <!-- Second Row -->
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-lg-0">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">RoomNo</th>
                                                <th scope="col">Type</th>
                                                <th scope="col">Check-in Date</th>
                                                <th scope="col">Check-out Date</th>
                                                <th scope="col">Email</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                $conn = new mysqli($servername, $username, $password, $dbname);

                                                // Check connection
                                                if ($conn->connect_error) {
                                                    die("Connection failed: " . $conn->connect_error);
                                                }

                                                $sql = "SELECT * FROM room_bookings";
                                                $result = $conn->query($sql);

                                                // Check if the query was successful
                                                if ($result) {
                                                    $num = $result->num_rows;

                                                    if ($num > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            // Ensure keys exist to avoid "Undefined index" notices
                                                            $roomNo = isset($row['RoomNo']) ? htmlspecialchars($row['RoomNo']) : 'N/A';
                                                            $type = isset($row['Type']) ? htmlspecialchars($row['Type']) : 'N/A';
                                                            $checkInDate = isset($row['CheckInDate']) ? htmlspecialchars($row['CheckInDate']) : 'N/A';
                                                            $checkOutDate = isset($row['CheckOutDate']) ? htmlspecialchars($row['CheckOutDate']) : 'N/A';
                                                            $email = isset($row['Email']) ? htmlspecialchars($row['Email']) : 'N/A';

                                                            echo "<tr>
                                                                    <td>$roomNo</td>
                                                                    <td>$type</td>
                                                                    <td>$checkInDate</td>
                                                                    <td>$checkOutDate</td>
                                                                    <td>$email</td>
                                                                  </tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='5'>No room bookings found</td></tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5'>Error retrieving data: " . $conn->error . "</td></tr>";
                                                }

                                                $conn->close(); // Close the connection
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="#" class="btn btn-block btn-light">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Second Row -->
            </main>
        </div>
    </div>
</body>

</html>
