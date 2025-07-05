<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Resident Villa</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link rel="manifest" href="site.webmanifest"> -->
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.png">
    <!-- Place favicon.ico in the root directory -->

    <!-- CSS here -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/gijgo.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/slicknav.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- <link rel="stylesheet" href="css/responsive.css"> -->
    <style>
    /* Custom styles for compact yet comfortable glass buttons */
    .header-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .book_btn {
        margin: 0;
        height: 40px; /* Increased from 32px */
    }
    
    .book_btn a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 14px;
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border-radius: 20px; /* Adjusted to half of 40px height */
        font-weight: 500;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.3px;
        transition: all 0.15s ease;
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        height: 40px;
        line-height: 1;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        white-space: nowrap;
    }
    
    .book_btn a:hover {
        background: rgba(255, 255, 255, 0.22);
        transform: translateY(-0.5px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        margin-left: 12px;
        color: white;
        font-weight: 500;
        font-size: 13px;
        white-space: nowrap; /* Ensures text stays in one line */
    }
    
    .user-profile i {
        margin-right: 5px;
        font-size: 15px;
    }
    
    @media (max-width: 1199px) {
        .header-buttons {
            gap: 6px;
        }
        .book_btn a {
            padding: 0 12px;
            font-size: 12px;
        }
    }
    </style>
</head>

<body>
    <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->

    <!-- header-start -->
    <header>
        <div class="header-area ">
            <div id="sticky-header" class="main-header-area">
                <div class="container-fluid p-0">
                    <div class="row align-items-center no-gutters">
                        <div class="col-xl-5 col-lg-6">
                            <div class="main-menu  d-none d-lg-block">
                                <nav>
                                    <ul id="navigation">
                                        <li><a class="active" href="index.php">home</a></li>
                                        <?php
                                        session_start();

                                        // Check if the email session variable is set
                                        if(!isset($_SESSION['email'])) {
                                            // Display the "LogIn" link if the email session variable is not set
                                            echo '<li><a class="SignIn" href="SignIn.php">LogIn</a></li>';
                                        } else {
                                            // Display the "Logout" button if the email session variable is set
                                            echo '<li><a class="Logout" href="logout.php">Logout</a></li>';
                                        }
                                        ?>
                                        <li><a href="rooms.php">rooms</a></li>
                                        <li><a href="about.html">About</a></li>
                                        <li><a href="contact.html">Contact</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-2">
                            <div class="logo-img">
                                <a href="index.php">
                                    <img src="img/logo5.png" alt="Logo" width="80" height="80">
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-5 col-lg-4 d-none d-lg-block">
                            <div class="book_room">
                                
                                <div class="header-buttons">
                                    <?php
                                    // Check if the email session variable is set
                                    if(isset($_SESSION['email'])) {
                                        // Display the "Book A Room" link
                                        echo '<div class="book_btn">';
                                        echo '<a class="BookingRoom" href="RoomBooking.php">Book A Room</a>';
                                        echo '</div>';

                                        // Display the "Book A Ride" link
                                        echo '<div class="book_btn">';
                                        echo '<a class="BookingRide" href="BookingRide.php">Book A Ride</a>';
                                        echo '</div>';
                                        
                                        // Display user profile
                                        echo '<div class="user-profile">';
                                        echo '<i class="fa fa-user-circle"></i>';
                                        echo 'Hi, ' . (isset($_SESSION['name']) ? $_SESSION['name'] : 'User');
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mobile_menu d-block d-lg-none"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- header-end -->

    <!-- slider_area_start -->
    <div class="slider_area">
        <div class="slider_active owl-carousel">
            <div class="single_slider d-flex align-items-center justify-content-center slider_bg_1">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="slider_text text-center">
                                <h3>Resident Villa</h3>
                                <p>Elegance Unveiled, Luxury Redefined</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="single_slider  d-flex align-items-center justify-content-center slider_bg_2">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="slider_text text-center">
                                <h3>Life is Beautiful</h3>
                                <p>Elegance Unveiled, Luxury Redefined</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="single_slider d-flex align-items-center justify-content-center slider_bg_1">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="slider_text text-center">
                                <h3>Resident Villa</h3>
                                <p>Elegance Unveiled, Luxury Redefined</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="single_slider  d-flex align-items-center justify-content-center slider_bg_2">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="slider_text text-center">
                                <h3>Life is Beautiful</h3>
                                <p>Elegance Unveiled, Luxury Redefined</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- slider_area_end -->


<?php
require_once __DIR__ . '/db/DatabaseConnection.php';
$db = new DatabaseConnection();
$conn = $db->conn;

// Fetch latest feedbacks with user info
$feedbacks = [];
$sql = "SELECT f.rating, f.comment, f.created_at, u.first_name, u.last_name 
        FROM Feedback f 
        JOIN UserAccounts u ON f.user_id = u.user_id 
        ORDER BY f.created_at DESC LIMIT 8";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}
?>
<!-- Feedbacks Section Start -->
<style>
.feedbacks-section {
    background: linear-gradient(135deg, #f8fafc 0%, #e9eafc 100%);
    padding: 56px 0 36px 0;
}
.feedbacks-title {
    text-align: center;
    font-size: 2.1rem;
    font-weight: 700;
    margin-bottom: 18px;
    color: #2d2d2d;
    letter-spacing: 0.5px;
}
.feedbacks-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    justify-content: center;
}
.feedback-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 18px rgba(60,80,180,0.07);
    padding: 28px 26px 20px 26px;
    max-width: 340px;
    min-width: 260px;
    flex: 1 1 260px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    position: relative;
    transition: box-shadow 0.18s;
}
.feedback-card:hover {
    box-shadow: 0 8px 32px rgba(60,80,180,0.13);
}
.feedback-user {
    font-weight: 600;
    color: #2d8cff;
    font-size: 1.08rem;
    margin-bottom: 4px;
}
.feedback-date {
    font-size: 0.92rem;
    color: #888;
    margin-bottom: 10px;
}
.feedback-stars {
    margin-bottom: 10px;
}
.feedback-stars .fa-star {
    color: #f7b731;
    font-size: 1.1rem;
    margin-right: 2px;
}
.feedback-stars .fa-star-o {
    color: #e0e0e0;
    font-size: 1.1rem;
    margin-right: 2px;
}
.feedback-comment {
    font-size: 1.04rem;
    color: #333;
    margin-bottom: 0;
    line-height: 1.5;
    min-height: 48px;
}
@media (max-width: 900px) {
    .feedbacks-grid { flex-direction: column; align-items: center; }
    .feedback-card { max-width: 98vw; }
}
</style>
<section class="feedbacks-section">
    <div class="container">
        <div class="feedbacks-title">
            <i class="fa fa-comments-o" style="color:#2d8cff"></i> What Our Guests Say
        </div>
        <div class="feedbacks-grid">
            <?php if (count($feedbacks) === 0): ?>
                <div style="color:#888; font-size:1.1rem; text-align:center;">No feedbacks yet. Be the first to share your experience!</div>
            <?php else: ?>
                <?php foreach ($feedbacks as $fb): ?>
                    <div class="feedback-card">
                        <div class="feedback-user">
                            <i class="fa fa-user-circle"></i>
                            <?php echo htmlspecialchars($fb['first_name'] . ' ' . $fb['last_name']); ?>
                        </div>
                        <div class="feedback-date">
                            <i class="fa fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($fb['created_at'])); ?>
                        </div>
                        <div class="feedback-stars">
                            <?php
                            $rating = (int)$fb['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fa fa-star"></i>';
                                } else {
                                    echo '<i class="fa fa-star-o"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="feedback-comment">
                            <i class="fa fa-quote-left" style="color:#2d8cff"></i>
                            <?php echo nl2br(htmlspecialchars($fb['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Feedbacks Section End -->
    
    <!-- about_area_start -->
    <div class="about_area">
        <div class="container">
            <div class="row">
                <div class="col-xl-5 col-lg-5">
                    <div class="about_info">
                        <div class="section_title mb-20px">
                            <span>About Us</span>
                            <h3>A Luxuries Hotel <br>
                                with Nature</h3>
                        </div>
                        <p>Suscipit libero pretium nullam potenti. Interdum, blandit phasellus consectetuer dolor ornare
                            dapibus enim ut tincidunt rhoncus tellus sollicitudin pede nam maecenas, dolor sem. Neque
                            sollicitudin enim. Dapibus lorem feugiat facilisi faucibus et. Rhoncus.</p>
                        <a href="#" class="line-button">Learn More</a>
                    </div>
                </div>
                <div class="col-xl-7 col-lg-7">
                    <div class="about_thumb d-flex">
                        <div class="img_1">
                            <img src="img/about/about_1.png" alt="">
                        </div>
                        <div class="img_2">
                            <img src="img/about/about_2.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- about_area_end -->

    <!-- offers_area_start -->
    <div class="offers_area">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="section_title text-center mb-100">
                        <span>Our Offers</span>
                        <h3>Ongoing Offers</h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-4 col-md-4">
                    <div class="single_offers">
                        <div class="about_thumb">
                            <img src="img/offers/1.png" alt="">
                        </div>
                        <h3>Up to 35% savings on Club <br>
                            rooms and Suites</h3>
                        <ul>
                            <li>Luxaries condition</li>
                            <li>3 Adults & 2 Children size</li>
                            <li>Sea view side</li>
                        </ul>
                        <a href="RoomBooking.php" class="book_now">book now</a>
                    </div>
                </div>
                <div class="col-xl-4 col-md-4">
                    <div class="single_offers">
                        <div class="about_thumb">
                            <img src="img/offers/2.png" alt="">
                        </div>
                        <h3>Up to 35% savings on Club <br>
                            rooms and Suites</h3>
                        <ul>
                            <li>Luxaries condition</li>
                            <li>3 Adults & 2 Children size</li>
                            <li>Sea view side</li>
                        </ul>
                        <a href="RoomBooking.php" class="book_now">book now</a>
                    </div>
                </div>
                <div class="col-xl-4 col-md-4">
                    <div class="single_offers">
                        <div class="about_thumb">
                            <img src="img/offers/3.png" alt="">
                        </div>
                        <h3>Up to 35% savings on Club <br>
                            rooms and Suites</h3>
                        <ul>
                            <li>Luxaries condition</li>
                            <li>3 Adults & 2 Children size</li>
                            <li>Sea view side</li>
                        </ul>
                        <a href="RoomBooking.php" class="book_now">book now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- offers_area_end -->

    <!-- video_area_start -->
    <div class="video_area video_bg overlay">
        <div class="video_area_inner text-center">
            <span>Resident Sea View</span>
            <h3>Relax and Enjoy your <br>
                Vacation </h3>
            <a href="https://www.youtube.com/watch?v=vLnPwxZdW4Y" class="video_btn popup-video">
                <i class="fa fa-play"></i>
            </a>
        </div>
    </div>
    <!-- video_area_end -->

    <!-- about_area_start -->
    <div class="about_area">
        <div class="container">
            <div class="row">
                <div class="col-xl-7 col-lg-7">
                    <div class="about_thumb2 d-flex">
                        <div class="img_1">
                            <img src="img/about/1.png" alt="">
                        </div>
                        <div class="img_2">
                            <img src="img/about/2.png" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 col-lg-5">
                    <div class="about_info">
                        <div class="section_title mb-20px">
                            <span>Delicious Food</span>
                            <h3>We Serve Fresh and <br>
                                Delicious Food</h3>
                        </div>
                        <p>Welcome to ResidentVilla, an exquisite haven seamlessly blending opulence with the serenity of nature. 
                            Nestled in Hikkaduwa, our luxury hotel is designed to provide a resplendent retreat for those seeking
                             an immersive experience surrounded by natural beauty.</p>
                        <a href="#" class="line-button">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- about_area_end -->

    <!-- features_room_startt -->
    <div class="features_room">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="section_title text-center mb-100">
                        <span>Featured Rooms</span>
                        <h3>Choose a Better Room</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="rooms_here">
            <div class="single_rooms">
                <div class="room_thumb">
                    <img src="img/rooms/1.png" alt="">
                    <div class="room_heading d-flex justify-content-between align-items-center">
                        <div class="room_heading_inner">
                            
                            <h3>Superior Room</h3>
                        </div>
                        <a href="RoomBooking.php" class="line-button">book now</a>
                    </div>
                </div>
            </div>
            <div class="single_rooms">
                <div class="room_thumb">
                    <img src="img/rooms/2.png" alt="">
                    <div class="room_heading d-flex justify-content-between align-items-center">
                        <div class="room_heading_inner">
                            
                            <h3>Deluxe Room</h3>
                        </div>
                        <a href="RoomBooking.php" class="line-button">book now</a>
                    </div>
                </div>
            </div>
            <div class="single_rooms">
                <div class="room_thumb">
                    <img src="img/rooms/3.png" alt="">
                    <div class="room_heading d-flex justify-content-between align-items-center">
                        <div class="room_heading_inner">
                            
                            <h3>Signature Room</h3>
                        </div>
                        <a href="RoomBooking.php" class="line-button">book now</a>
                    </div>
                </div>
            </div>
            <div class="single_rooms">
                <div class="room_thumb">
                    <img src="img/rooms/4.png" alt="">
                    <div class="room_heading d-flex justify-content-between align-items-center">
                        <div class="room_heading_inner">
                            
                            <h3>Couple Room</h3>
                        </div>
                        <a href="RoomBooking.php" class="line-button">book now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- features_room_end -->

    <!-- forQuery_start -->
    <div class="forQuery">
        <div class="container">
            <div class="row">
                <div class="col-xl-10 offset-xl-1 col-md-12">
                    <div class="Query_border">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-xl-6 col-md-6">
                                <div class="Query_text">
                                    <p>For Reservation 0r Query?</p>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <div class="phone_num">
                                    <a href="#" class="mobile_no">+94 91222333</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- forQuery_end-->

    <!-- instragram_area_start -->
    <div class="instragram_area">
        <div class="single_instagram">
            <img src="img/instragram/1.png" alt="">
            <div class="ovrelay">
                <a href="#">
                    <i class="fa fa-instagram"></i>
                </a>
            </div>
        </div>
        <div class="single_instagram">
            <img src="img/instragram/2.png" alt="">
            <div class="ovrelay">
                <a href="#">
                    <i class="fa fa-instagram"></i>
                </a>
            </div>
        </div>
        <div class="single_instagram">
            <img src="img/instragram/3.png" alt="">
            <div class="ovrelay">
                <a href="#">
                    <i class="fa fa-instagram"></i>
                </a>
            </div>
        </div>
        <div class="single_instagram">
            <img src="img/instragram/4.png" alt="">
            <div class="ovrelay">
                <a href="#">
                    <i class="fa fa-instagram"></i>
                </a>
            </div>
        </div>
        <div class="single_instagram">
            <img src="img/instragram/5.png" alt="">
            <div class="ovrelay">
                <a href="#">
                    <i class="fa fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>
    <!-- instragram_area_end -->

    <!-- footer -->
    <footer class="footer">
        <div class="footer_top">
            <div class="container">
                <div class="row">
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="footer_widget">
                            <h3 class="footer_title">
                                address
                            </h3>
                            <p class="footer_text"> No.232 GalleRoad,<br>
                                Hikkaduwa,Sri Lanka</p>
                            <a href="#" class="line-button">Get Direction</a>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-lg-3">
                        <div class="footer_widget">
                            <h3 class="footer_title">
                                Reservation
                            </h3>
                            <p class="footer_text">+94 911222333 <br>
                                ResidentVilla@galle.com</p>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6 col-lg-2">
                        <div class="footer_widget">
                            <h3 class="footer_title">
                                Navigation
                            </h3>
                            <ul>
                                <li><a href="#">Home</a></li>
                                <li><a href="#">Rooms</a></li>
                                <li><a href="#">About</a></li>
                                <li><a href="#">News</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-lg-4">
                        <div class="footer_widget">
                            <h3 class="footer_title">
                                Newsletter
                            </h3>
                            <form action="#" class="newsletter_form">
                                <input type="text" placeholder="Enter your mail">
                                <button type="submit">Sign Up</button>
                            </form>
                            <p class="newsletter_text">Subscribe newsletter to get updates</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copy-right_text">
            <div class="container">
                <div class="footer_border"></div>
                <div class="row">
                    <div class="col-xl-8 col-md-7 col-lg-9">
                        <p class="copy_right">
                            <i class="fa fa-heart-o" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">ResidentVilla</a>
                        </p>
                    </div>
                    <div class="col-xl-4 col-md-5 col-lg-3">
                        <div class="socail_links">
                            <ul>
                                <li>
                                    <a href="#">
                                        <i class="fa fa-facebook-square"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa fa-twitter"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i class="fa fa-instagram"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- form itself end-->
    <form id="test-form" class="white-popup-block mfp-hide">
        <div class="popup_box ">
            <div class="popup_inner">
                <h3>Check Availability</h3>
                <form action="#">
                    <div class="row">
                        <div class="col-xl-6">
                            <input id="datepicker" placeholder="Check in date">
                        </div>
                        <div class="col-xl-6">
                            <input id="datepicker2" placeholder="Check out date">
                        </div>
                        <div class="col-xl-6">
                            <select class="form-select wide" id="default-select" class="">
                                <option data-display="Adult">1</option>
                                <option value="1">2</option>
                                <option value="2">3</option>
                                <option value="3">4</option>
                            </select>
                        </div>
                        <div class="col-xl-6">
                            <select class="form-select wide" id="default-select" class="">
                                <option data-display="Children">1</option>
                                <option value="1">2</option>
                                <option value="2">3</option>
                                <option value="3">4</option>
                            </select>
                        </div>
                        <div class="col-xl-12">
                            <select class="form-select wide" id="default-select" class="">
                                <option data-display="Room type">Room type</option>
                                <option value="1">Laxaries Rooms</option>
                                <option value="2">Deluxe Room</option>
                                <option value="3">Signature Room</option>
                                <option value="4">Couple Room</option>
                            </select>
                        </div>
                        <div class="col-xl-12">
                            <button type="submit" class="boxed-btn3">Check Availability</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </form>
    <!-- form itself end -->

    <!-- JS here -->
    <script src="js/vendor/modernizr-3.5.0.min.js"></script>
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/isotope.pkgd.min.js"></script>
    <script src="js/ajax-form.js"></script>
    <script src="js/waypoints.min.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/imagesloaded.pkgd.min.js"></script>
    <script src="js/scrollIt.js"></script>
    <script src="js/jquery.scrollUp.min.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/nice-select.min.js"></script>
    <script src="js/jquery.slicknav.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/gijgo.min.js"></script>

    <!--contact js-->
    <script src="js/contact.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/mail-script.js"></script>

    <script src="js/main.js"></script>
    <script>
        $('#datepicker').datepicker({
            iconsLibrary: 'fontawesome',
            icons: {
                rightIcon: '<span class="fa fa-caret-down"></span>'
            }
        });
        $('#datepicker2').datepicker({
            iconsLibrary: 'fontawesome',
            icons: {
                rightIcon: '<span class="fa fa-caret-down"></span>'
            }
        });
    </script>

    <?php if (isset($_SESSION['email'])): ?>
    <!-- Floating Feedback Widget -->
    <style>
    #feedback-widget {
        position: fixed;
        bottom: 32px;
        right: 32px;
        z-index: 9999;
        background: rgba(255,255,255,0.97);
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.13);
        padding: 20px 22px 16px 22px;
        width: 320px;
        max-width: 90vw;
        font-family: inherit;
        display: none;
        animation: fadeIn 0.4s;
    }
    #feedback-widget.open { display: block; }
    #feedback-widget h4 { margin: 0 0 10px 0; font-size: 18px; }
    #feedback-widget .stars {
        display: flex;
        gap: 4px;
        margin-bottom: 10px;
    }
    #feedback-widget .star {
        font-size: 26px;
        color: #ccc;
        cursor: pointer;
        transition: color 0.15s;
    }
    #feedback-widget .star.selected,
    #feedback-widget .star:hover,
    #feedback-widget .star:hover ~ .star { color: #f7b731; }
    #feedback-widget textarea {
        width: 100%;
        min-height: 60px;
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 7px 10px;
        margin-bottom: 10px;
        resize: vertical;
        font-size: 14px;
    }
    #feedback-widget .actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
    #feedback-widget .btn {
        background: #2d8cff;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 16px;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.15s;
    }
    #feedback-widget .btn.cancel { background: #aaa; }
    #feedback-widget .close-btn {
        position: absolute;
        top: 7px;
        right: 12px;
        font-size: 18px;
        color: #888;
        cursor: pointer;
    }
    #feedback-fab {
        position: fixed;
        bottom: 32px;
        right: 32px;
        z-index: 9998;
        background: #2d8cff;
        color: #fff;
        border-radius: 50%;
        width: 54px;
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.13);
        cursor: pointer;
        transition: background 0.15s;
    }
    #feedback-fab:hover { background: #1b6edc; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px);} to { opacity: 1; transform: none; } }
    </style>

    <div id="feedback-fab" title="Give Feedback">
        <i class="fa fa-commenting"></i>
    </div>
    <div id="feedback-widget">
        <span class="close-btn" title="Close">&times;</span>
        <h4>Your Feedback</h4>
        <div class="stars" id="feedback-stars">
            <span class="star" data-value="1">&#9733;</span>
            <span class="star" data-value="2">&#9733;</span>
            <span class="star" data-value="3">&#9733;</span>
            <span class="star" data-value="4">&#9733;</span>
            <span class="star" data-value="5">&#9733;</span>
        </div>
        <textarea id="feedback-text" placeholder="Share your experience..."></textarea>
        <div class="actions">
            <button class="btn cancel" type="button">Cancel</button>
            <button class="btn submit" type="button">Submit</button>
        </div>
        <div id="feedback-success" style="display:none; color:green; margin-top:8px;">Thank you for your feedback!</div>
    </div>
    <script>
    (function(){
        // Show/hide widget
        const fab = document.getElementById('feedback-fab');
        const widget = document.getElementById('feedback-widget');
        const closeBtn = widget.querySelector('.close-btn');
        const cancelBtn = widget.querySelector('.btn.cancel');
        const submitBtn = widget.querySelector('.btn.submit');
        const stars = widget.querySelectorAll('.star');
        const textarea = document.getElementById('feedback-text');
        const successMsg = document.getElementById('feedback-success');
        let selected = 0;

        fab.onclick = () => { widget.classList.add('open'); fab.style.display='none'; }
        closeBtn.onclick = cancelBtn.onclick = () => { widget.classList.remove('open'); fab.style.display='flex'; successMsg.style.display='none'; textarea.value=''; setStars(0);}
        function setStars(val) {
            selected = val;
            stars.forEach((s,i)=>{ s.classList.toggle('selected', i<val); });
        }
        stars.forEach((star,i)=>{
            star.onmouseover = ()=> setStars(i+1);
            star.onmouseout = ()=> setStars(selected);
            star.onclick = ()=> setStars(i+1);
        });
        submitBtn.onclick = () => {
            if(selected===0) { alert('Please select a rating.'); return; }
            if(textarea.value.trim().length<3) { alert('Please enter your feedback.'); return; }
            fetch('save_feedback.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({rating: selected, comment: textarea.value.trim()})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    successMsg.style.display='block';
                    setTimeout(()=>{ widget.classList.remove('open'); fab.style.display='flex'; successMsg.style.display='none'; textarea.value=''; setStars(0); }, 1500);
                } else {
                    alert(data.message || 'Error saving feedback.');
                }
            })
            .catch(()=>alert('Network error.'));
        };
        // Show FAB on load
        fab.style.display = 'flex';
    })();
    </script>
    <?php endif; ?>

</body>
</html>