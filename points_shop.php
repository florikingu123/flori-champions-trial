<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// Handle adding a reward
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reward'])) {
    $name = $_POST['reward_name'];
    $points = $_POST['reward_points'];
    $image = $_FILES['reward_image']['name'];
    $targetDir = __DIR__ . "/uploads/";
    $targetFile = $targetDir . basename($image);
    
    // Check if file is uploaded
    if (!empty($image) && move_uploaded_file($_FILES['reward_image']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("INSERT INTO rewards (name, image, points_required) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssi", $name, $image, $points);
            if ($stmt->execute()) {
                echo "Reward added successfully!";
            } else {
                echo "Error adding reward: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Database error: " . $conn->error;
        }
    } else {
        echo "Error uploading file.";
    }
}

// Handle assigning a reward
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_reward'])) {
    $member_email = $_POST['member_email'];
    $reward_id = $_POST['reward_id'];
    
    $stmt = $conn->prepare("INSERT INTO assigned_rewards (member_email, reward_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("si", $member_email, $reward_id);
        if ($stmt->execute()) {
            echo "Reward assigned successfully!";
        } else {
            echo "Error assigning reward: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Database error: " . $conn->error;
    }
}

$rewards = $conn->query("SELECT * FROM rewards");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Family manager - Famify</title>
  <meta name="description" content="">
  <meta name="keywords" content="">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
<style>
    /* Style the default file input */
input[type="file"] {
    position: relative;
    display: inline-block;
    padding: 12px;
    background: transparent;
    color: #FFD3B5; /* Warm peach text */
    border: 2px solid #FFD3B5; /* Warm peach border */
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    width: 100%;
    max-width: 250px;
    transition: transform 0.3s ease-in-out, background 0.3s ease-in-out;
}

/* Remove default browser styles */
input[type="file"]::-webkit-file-upload-button {
    visibility: hidden;
}

/* Custom file button inside the input */
input[type="file"]::before {
    content: "Choose File";
    display: inline-block;
    background: transparent;
    color: #FFD3B5; /* Warm peach text */
    border: 2px solid #FFD3B5; /* Warm peach border */
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    text-align: center;
}

/* Hover effect */
input[type="file"]:hover::before {
    background: #FFD3B5; /* Warm peach background */
    color: #333; /* Dark text for contrast */
    transform: scale(1.05); /* Slight bounce */
}
/* Style the entire form */
form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: rgba(255, 211, 181, 0.1); /* Light warm peach background */
    border: 2px solid #B87350; /* Darker warm peach border */
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    margin: 20px auto;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
}

/* Input Fields */
input, select {
    width: 100%;
    padding: 12px;
    border: 2px solid #B87350; /* Darker warm peach border */
    border-radius: 8px;
    background: transparent;
    color: white;
    outline: none;
    font-size: 16px;
    transition: border-color 0.3s ease-in-out;
}

input::placeholder, select {
    color: #FFD3B5; /* Warm peach placeholder text */
}

input:focus, select:focus {
    border-color: #8A4F32; /* Even darker warm peach when focused */
}

/* Custom File Upload Styling */
input[type="file"] {
    display: none;
}

/* Custom File Upload Button */
.custom-file-upload {
    display: inline-block;
    padding: 12px 24px;
    background: transparent;
    color: #FFD3B5; /* Warm peach text */
    border: 2px solid #B87350; /* Darker warm peach border */
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    transition: transform 0.3s ease-in-out, background 0.3s ease-in-out;
}

/* Hover Effect for File Upload */
.custom-file-upload:hover {
    background: #B87350; /* Darker warm peach background */
    color: #FFF5E1; /* Light text */
    transform: scale(1.05);
}

/* Button Styling */
button {
    background: transparent;
    color: #FFD3B5; /* Warm peach text */
    padding: 12px 24px;
    border: 2px solid #B87350; /* Darker warm peach border */
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: transform 0.3s ease-in-out, background 0.3s ease-in-out;
    width: 100%;
    max-width: 200px;
}

/* Button Hover Effect */
button:hover {
    background: #B87350; /* Darker warm peach background */
    color: #FFF5E1; /* Lighter text */
    transform: scale(1.1); /* Bouncy effect */
}

/* Headings */
h2 {
    text-align: center;
    color: #FFD3B5;
    font-size: 24px;
    margin-top: 30px;
}

</style>
</head>

<body class="index-page">


<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">Famify</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
      <li><a href="famify.php">Family center</a></li>      
      <li><a href="addfam.php" id="openModal">Add a Family Member</a></li>
        <li><a href="account.php">Your Account</a></li>
        <li><a href="connect.html">Connect</a></li>
        <li><a href="points_shop.php">Rewards</a></li>
        
        
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>


<div class="page-title dark-background">
    <div class="container position-relative">
      <h1>Reward System</h1>
      <p>A way u can add rewards to family member with the points they've earned</p>
      <nav class="breadcrumbs">
      </nav>
    </div>
  </div>
  
  <h2>Add Reward</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="reward_name" placeholder="Reward Name" required>
    <input type="number" name="reward_points" placeholder="Points Required" required>

    <label for="reward_image" class="custom-file-upload">Choose File</label>
    <input type="file" id="reward_image" name="reward_image" required>

    <button type="submit" name="add_reward">Add Reward</button>
</form>

    </form>

    <h2>Assign Reward</h2>
    <form method="POST">
        <select name="reward_id">
            <?php while ($row = $rewards->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= $row['name'] ?> (<?= $row['points_required'] ?> pts)</option>
            <?php endwhile; ?>
        </select>
        <input type="email" name="member_email" placeholder="Member Email" required>
        <button type="submit" name="assign_reward">Assign Reward</button>
    </form>
    

    <footer id="footer" class="footer dark-background">

<div class="footer-newsletter">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-6">
        <h4>Join Our Newsletter</h4>
        <p>Subscribe to our newsletter and receive the latest news about our products and services!</p>
    
          <div class="newsletter-form" action="newsletter.php" method="post" class="php-email-form">
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="submit" value="Subscribe">
          </div>
          <div class="loading">Loading</div>
          <div class="sent-message" style="display:none;">Your subscription request has been sent. Thank you!</div>
        
      </div>
    </div>
  </div>
</div>

<div class="container footer-top">
  <div class="row gy-4">
    <div class="col-lg-4 col-md-6 footer-about">
      <a href="index.html" class="d-flex align-items-center">
        <span class="sitename">Famify</span>
      </a>
      <div class="footer-contact pt-3">
        <p>1234 Elm Street</p>
        <p>Los Angeles, CA 90001</p>
        <p class="mt-3"><strong>Phone:</strong> <span>+1 2345 6789 01</span></p>
        <p><strong>Email:</strong> <span>famify@info.com</span></p>
      </div>
    </div>

    <div class="col-lg-2 col-md-3 footer-links">
      <h4>Useful Links</h4>
      <ul>
        <li><i class="bi bi-chevron-right"></i> <a href="index.html">Home</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="about.html" class="active">About</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="services.html">Services</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="portfolio.html">Portfolio</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="team.html">Team</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="blog.html">Blog</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="contact.html">Contact</a></li>
      </ul>
    </div>

    <div class="col-lg-2 col-md-3 footer-links">
      <h4>Our Services</h4>
      <ul>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Keeping Your Family Organized</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">How Famify Keeps Your Family on Track</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Manage Chores and Rewards</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Effortless Communication</a></li>
      </ul>
    </div>

    <div class="col-lg-4 col-md-12">
      <h4>Follow Us</h4>
      <div class="social-links d-flex">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
    </div>

  </div>
</div>

<div class="container copyright text-center mt-4">
  <p>© <span>Copyright</span> <strong class="px-1 sitename">Famify</strong> <span>All Rights Reserved</span></p>
</div>

</footer>


  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>


</body>

</html>