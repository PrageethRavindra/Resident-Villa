<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Book Ride</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="font/flaticon.css">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <link rel="stylesheet" href="css/BookingRide.css">
</head>
<body>
    <div class="container">
        <div class="book">
            <div class="description">
                <h1><strong>Book</strong> your Ride</h1>
                <p>Resident Cab Service</p>
                <div class="quote"></div>
                <ul>
                    <li>Super reliable service</li>
                    <li>24/7 customer service</li>
                    <li>GPS tracking and help</li>
                    <li>Wide range vehicle</li>
                </ul>
            </div>
            <div class="form">
                <form action="#" method="POST">
                    <div class="inpbox full">
                        <span class="flaticon-taxi"></span>
                        <select id="VehicleType" name="VehicleType">
                            <option value="">Select Vehicle</option>
                            <option value="Prius">Prius</option>
                            <option value="Axio">Axio</option>
                            <option value="Vezel">Vezel</option>
                            <option value="Audi">Audi</option>
                        </select>
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-globe"></span>
                        <input name="pickup" type="text" placeholder="Pickup Location">
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-calendar"></span>
                        <input name="PickupDate" type="date" placeholder="Pickup Date">
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-location"></span>
                        <input name="drop" type="text" placeholder="Drop Location">
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-calendar"></span>
                        <input name="dropDate" type="date" placeholder="Drop Date">
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-user"></span>
                        <input name="fname" type="text" placeholder="Full Name">
                    </div>
                    <div class="inpbox">
                        <span class="flaticon-email"></span>
                        <input name="email" type="email" placeholder="Email">
                    </div>
                    <div class="inpbox full">
                        <div class="inrbox">
                            <span class="flaticon-taxi"> Regular</span>
                            <input value="regular" type="radio" name="plan">
                            <p><b>LKR.5000</b></p>
                            <span>1 Passenger</span>
                        </div>
                        <div class="inrbox">
                            <span class="flaticon-taxi"> Pro</span>
                            <input value="pro" type="radio" name="plan">
                            <p><b>LKR.10000</b></p>
                            <span>2 Passenger</span>
                        </div>
                        <div class="inrbox">
                            <span class="flaticon-taxi"> Advance</span>
                            <input value="advance" type="radio" name="plan">
                            <p><b>LKR.15000</b></p>
                            <span>4 Passenger</span>
                        </div>
                    </div>
                    <button class="subt" type="submit">Submit</button>
                    <button class="rst" type="reset">Reset</button>
                </form>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script type="text/javascript">
    (function() {
        // Initialize EmailJS
        emailjs.init({
            publicKey: "5w7dn8PaLxbtvDb_O",
        });
    })();
</script>

<script>
    function sendEmail(to_email, to_name) {
      emailjs.send("service_e0nxrqb","template_pjejhnt",{
to_name: to_name,
to_email: to_email,
})
        .then(function(response) {
            console.log('Email sent successfully:', response);
            alert('Email sent successfully!');
        }, function(error) {
            console.error('Email sending failed:', error);
            alert('Email sending failed. Please try again later.');
        });
    }
</script>
</body>
</html>






<?php
$servername = "localhost:3305";
$username = "prageeth";
$password = "123@Admin";
$dbname = "resident_villa";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!empty($_POST['fname']) && !empty($_POST['email'])) {
    $vehicleType = $conn->real_escape_string($_POST['VehicleType']);
    $pickup = $conn->real_escape_string($_POST['pickup']);
    $pickupDate = $conn->real_escape_string($_POST['PickupDate']);
    $drop = $conn->real_escape_string($_POST['drop']);
    $dropDate = $conn->real_escape_string($_POST['dropDate']);
    $fname = $conn->real_escape_string($_POST['fname']);
    $email = $conn->real_escape_string($_POST['email']);
    $packageType = isset($_POST['plan']) ? $conn->real_escape_string($_POST['plan']) : '';

    $stmt = $conn->prepare("INSERT INTO ridetb (vehicleType, pickUp, `drop`, packageType, PickupDate, DropDate, Fname, Email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $vehicleType, $pickup, $drop, $packageType, $pickupDate, $dropDate, $fname, $email);

    if ($stmt->execute()) {
        echo "<script>alert('New record created successfully');</script>";
        echo "<script>sendEmail('".$email."','".$fname."');</script>"; // Pass $email variable instead of 'email'
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();
?>