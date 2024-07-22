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
    <!-- Cabecera -->
    <nav class="navbar navbar-light bg-light p-3">
        <div class="d-flex col-12 col-md-3 col-lg-2 mb-2 mb-lg-0 flex-wrap flex-md-nowrap justify-content-between">
            <a class="navbar-brand" href="#">
                Admin Dashboard
            </a>
        </div>
    </nav>
    <!-- Fin Cabecera -->
    <!-- Contenedor Principal -->
    <div class="container-fluid">
        <!-- Contendor Filas -->
        <div class="row">
            <!-- Barra Lateral -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
				<div class="position-sticky">
					<ul class="nav flex-column">
						<li class="nav-item">
							<a class="nav-link active" aria-current="page" href="admin.php">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-home">
									<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
									<polyline points="9 22 9 12 15 12 15 22"></polyline>
								</svg>
								<span class="ml-2">Overview</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="RoomBookingDashBoard.php">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file">
									<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
									<polyline points="13 2 13 9 20 9"></polyline>
								</svg>
								<span class="ml-2">Room Bookings</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="RideBookingDashBoard.php">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart">
									<circle cx="9" cy="21" r="1"></circle>
									<circle cx="20" cy="21" r="1"></circle>
									<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
								</svg>
								<span class="ml-2">Ride Bookings</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="CustomerDashBoard.php">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users">
									<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
									<circle cx="9" cy="7" r="4"></circle>
									<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
									<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
								</svg>
								<span  class="ml-2">Customers</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="DriverRegister.php">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users">
									<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
									<circle cx="9" cy="7" r="4"></circle>
									<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
									<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
								</svg>
								<span class="ml-2">Driver Register</span>
							</a>
						</li>
					</ul>
				</div>
			</nav>
            <!-- Fin de Barra Lateral -->

            <main class="col-md-9 ml-sm-auto col-lg-10 px-md-4 py-4">
                <!-- BreadCrumbs -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Customers</li>
                    </ol>
                </nav>
                <!--Fin de BreadCrumbs -->
                <!--Dashboard -->
                <h1 class="h2">Dashboard</h1>

                <!-- First Row -->
                <div class="row my-4">
                    <div class="col-12 col-md-6 col-lg-3 mb-4 mb-lg-0">
                        <div class="card">
                            <h5 class="card-header">Customers</h5>
                            <div class="card-body">
                                <h5 class="card-title"></h5>
								<?php
                                    $servername = "localhost:3305";
                                    $username = "prageeth";
                                    $password = "123@Admin";
                                    $dbname = "resident_villa";
                                    
                                    $conn = new mysqli($servername, $username, $password, $dbname);
                                    // Check connection
                                    if ($conn->connect_error) {
                                        die("Connection failed: " . $conn->connect_error);
                                    }

                                                                    // SQL query to count rows
                                    $sql = "SELECT COUNT(*) AS total FROM signup";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        // Fetch result
                                        $row = $result->fetch_assoc();
                                        $totalRows = $row["total"];
                                    } else {
                                        $totalRows = 0;
                                    }

                                    $conn->close();

                                    echo "<p class='card-text'>".$totalRows."</p>"

                                         ?>
                                <p class="card-text"></p>
                                <p id=json class="card-text text-success"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!--Fin de First Row -->
                <!-- Second Row -->
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-lg-0">
                        <div class="card">
                            
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Email</th>
                                                <th scope="col">First Name</th>
                                                <th scope="col">Last Name</th>
                                                <th scope="col">Country</th>
                                                <th scope="col">Phone No</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $servername = "localhost:3305";
                                            $username = "prageeth";
                                            $password = "123@Admin";
                                            $dbname = "resident_villa";

                                            $conn = new mysqli($servername, $username, $password, $dbname);

                                            // Check connection
                                            if (!$conn) {
                                                die("Connection failed: " . mysqli_connect_error());
                                            }

                                            $sql = mysqli_query($conn, "SELECT * FROM signup");
                                            $num = mysqli_num_rows($sql);

                                            if ($num > 0) {
                                                while ($row = mysqli_fetch_array($sql)) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo $row['Email']; ?></td>
                                                        <td><?php echo $row['FName']; ?></td>
                                                        <td><?php echo $row['LName']; ?></td>
                                                        <td><?php echo $row['Country']; ?></td>
                                                        <td><?php echo $row['Phone']; ?></td>
                                                    </tr>
                                            <?php
                                                }
                                            }
                                            mysqli_close($conn); // Close the connection
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="#" class="btn btn-block btn-light">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Fin de Second Row -->
                <!--Fin Cards -->
            </main>
        </div>
        <!-- Fin Contendor Filas -->
    </div>
    <!-- Contenedor Principal -->
</body>

</html>
