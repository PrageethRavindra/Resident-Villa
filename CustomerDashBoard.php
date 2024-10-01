<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>

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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Customers</li>
                    </ol>
                </nav>

                <h1 class="h2">Dashboard</h1>

                <!-- First Row -->
                <div class="row my-4">
                    <div class="col-12 col-md-6 col-lg-3 mb-4 mb-lg-0">
                        <div class="card">
                            <h5 class="card-header">Customers</h5>
                            <div class="card-body">
                                <?php
                                $servername = "localhost:3305";
                                $username = "root"; // Adjust if needed
                                $password = "123@prageeth"; // Adjust if needed
                                $dbname = "resident_villa";

                                // Create connection
                                $conn = new mysqli($servername, $username, $password, $dbname);

                                // Check connection
                                if ($conn->connect_error) {
                                    die("Connection failed: " . $conn->connect_error);
                                }

                                // SQL query to count rows
                                $sql = "SELECT COUNT(*) AS total FROM signup";
                                $result = $conn->query($sql);

                                $totalRows = ($result && $result->num_rows > 0) ? $result->fetch_assoc()["total"] : 0;

                                echo "<p class='card-text'>" . $totalRows . "</p>";

                                $conn->close();
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of First Row -->

                <!-- Second Row -->
                <div class="row">
                    <div class="col-12"> <!-- Adjusted to full width -->
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 600px;"> <!-- Increase max height for larger view -->
                                    <table class="table table-striped table-hover"> <!-- Add table styles for better display -->
                                        <thead>
                                            <tr>
                                                <th scope="col">Email</th>
                                                <th scope="col">First Name</th>
                                                <th scope="col">Last Name</th>
                                                <th scope="col">Country</th>
                                                <th scope="col">Phone No</th>
                                                <th scope="col">Role</th> <!-- Added Role Column -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Reconnect to the database
                                            $conn = new mysqli($servername, $username, $password, $dbname);

                                            // Check connection
                                            if ($conn->connect_error) {
                                                die("Connection failed: " . $conn->connect_error);
                                            }

                                            // SQL query to fetch all rows including role
                                            $sql = "SELECT Email, FName, LName, Country, Phone, Role FROM signup"; // Adjust if needed
                                            $result = $conn->query($sql);

                                            if ($result && $result->num_rows > 0) {
                                                // Fetch and display each row
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<tr>
                                                            <td>" . (!empty($row['Email']) ? htmlspecialchars($row['Email']) : 'N/A') . "</td>
                                                            <td>" . (!empty($row['FName']) ? htmlspecialchars($row['FName']) : 'N/A') . "</td>
                                                            <td>" . (!empty($row['LName']) ? htmlspecialchars($row['LName']) : 'N/A') . "</td>
                                                            <td>" . (!empty($row['Country']) ? htmlspecialchars($row['Country']) : 'N/A') . "</td>
                                                            <td>" . (!empty($row['Phone']) ? htmlspecialchars($row['Phone']) : 'N/A') . "</td>
                                                            <td>" . (!empty($row['Role']) ? htmlspecialchars($row['Role']) : 'N/A') . "</td> <!-- Display Role -->
                                                          </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6'>No customers found</td></tr>";
                                            }

                                            // Close the connection
                                            $conn->close();
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
