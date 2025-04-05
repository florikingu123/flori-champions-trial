
<?php
session_start();
include 'config.php';

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$member_email = $_SESSION['email'];

// Debugging: Ensure session email is set correctly
if (empty($member_email)) {
    die("Error: No member email found in session.");
}

// Fix potential email typos in database
$stmt = $conn->prepare("UPDATE chores SET member_email = 'flori@gmail.com' WHERE member_email = 'flori@gamil.com'");
$stmt->execute();
$stmt->close();

// Fetch assigned chores
$chores = [];
$stmt = $conn->prepare("SELECT id, chore_name, points FROM chores WHERE member_email = ?");
$stmt->bind_param("s", $member_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $chores[] = $row;
}
$stmt->close();

// Fetch user's total points
$stmt = $conn->prepare("SELECT points FROM family WHERE member_email = ?");
$stmt->bind_param("s", $member_email);
$stmt->execute();
$stmt->bind_result($user_points);
$stmt->fetch();
$stmt->close();

// Handle claiming points
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_points'])) {
    // Calculate total points from chores
    $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) FROM chores WHERE member_email = ?");
    $stmt->bind_param("s", $member_email);
    $stmt->execute();
    $stmt->bind_result($total_points);
    $stmt->fetch();
    $stmt->close();

    if ($total_points > 0) {
        // Update points in family table
        $stmt = $conn->prepare("UPDATE family SET points = points + ? WHERE member_email = ?");
        $stmt->bind_param("is", $total_points, $member_email);
        $stmt->execute();
        $stmt->close();

        // Delete claimed chores
        $stmt = $conn->prepare("DELETE FROM chores WHERE member_email = ?");
        $stmt->bind_param("s", $member_email);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Points claimed successfully!'); window.location.href='member.php';</script>";
        exit();
    } else {
        echo "<script>alert('No chores to claim.');</script>";
    }
}

// Fetch rewards assigned to the user
$rewards = [];
$stmt = $conn->prepare("SELECT name, points_required, image FROM rewards WHERE assigned_to = ?");
$stmt->bind_param("s", $member_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rewards[] = $row;
}
$stmt->close();

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Family member - Famify</title>
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
/* Modal Background */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Modal Content */
.modal-content {
    color: white;
    padding: 30px;
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0px 8px 20px rgba(255, 255, 255, 0.1);
    position: relative;
}

/* Close Button */
.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 26px;
    cursor: pointer;
    color: white;
}

.close:hover {
    color: #bbb;
}

/* Form Layout */
#familyMemberForm {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}

/* Input Fields */
input[type="text"], input[type="number"] {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #555;
    background: #222;
    color: white;
    outline: none;
    font-size: 16px;
}

input[type="text"]:hover, input[type="number"]:hover {
    border-color: #888;
}

/* File Upload Button */
input[type="file"] {
    display: none;
}

.custom-file-upload {
    display: inline-block;
    padding: 12px 24px;
    background: #444;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}

.custom-file-upload:hover {
    background: #666;
}

/* Image Preview */
#imagePreview {
    display: none;
    max-width: 120px;
    border-radius: 10px;
    margin-top: 15px;
    border: 3px solid white;
    padding: 6px;
}

/* Submit Button */
button {
    background: transparent;
    color: #FFD3B5; /* Warm peach text */
    padding: 12px 24px;
    border: 2px solid #FFD3B5; /* Warm peach border */
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: transform 0.3s ease-in-out, background 0.3s ease-in-out;
    margin-left: 50px;
}

button:hover {
    background: #FFD3B5; /* Warm peach background */
    color: white; /* Dark text for contrast */
    transform: scale(1.1); /* Slight bounce effect */
    border: 2px solid white;
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
      <li><a href="#">Your Points: <?= htmlspecialchars($user_points ?? 0) ?></a></li>

      <li><a href="member.php">Family center</a></li>      
      <li><a href="games.html">Games</a></li>
        <li><a href="account.php">Your Account</a></li>
        <li><a href="rew.php">Rewards</a></li>
        
        <li><a href="ai.php">Chore ai</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>


  <main class="main">


    <section id="hero" class="hero section dark-background">

  

      <div id="hero-carousel" class="carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">

        <div class="container position-relative">

          <div class="carousel-item active">
            <div class="carousel-container">
              <h2>Welcome to Famify</h2>
              <p>Discover a new way to manage your family’s tasks and rewards with ease. Organize chores, track points, and redeem exciting rewards. Build stronger connections while creating productive routines together. Famify simplifies family life.</p>
              <a href="#about" class="btn-get-started">Read More</a>
            </div>
          </div>

          <div class="carousel-item">
            <div class="carousel-container">
              <h2>Famify mission</h2>
              <p>Choose the plan that fits your family’s needs. The Default Plan offers essential features, while the Plus Plan provides enhanced options for customization and rewards. Start simplifying today with Famify!</p>
              <a href="#about" class="btn-get-started">Read More</a>
            </div>
          </div>

          <div class="carousel-item">
            <div class="carousel-container">
              <h2>Our vision</h2>
              <p>At Famify, we aim to bring families closer through seamless organization and shared achievements. By simplifying tasks and fostering teamwork, we empower families to thrive and create lasting memories together.
              </p>
              <a href="#about" class="btn-get-started">Read More</a>
            </div>
          </div>

          <a class="carousel-control-prev" href="#hero-carousel" role="button" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
          </a>

          <a class="carousel-control-next" href="#hero-carousel" role="button" data-bs-slide="next">
            <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
          </a>

          <ol class="carousel-indicators"></ol>

        </div>

      </div>

    </section>

    <section id="featured-services" class="featured-services section">

      <div class="container">
    
        <div class="row gy-4">
    
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item item-cyan position-relative">
              <div class="icon">
                <i class="bi bi-people"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>Family Management</h3>
              </a>
              <p>Create and manage your family group with ease. Add members, assign roles, and start organizing tasks in minutes to keep everyone connected and productive.</p>
            </div>
          </div>
    
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item item-orange position-relative">
              <div class="icon">
                <i class="bi bi-card-checklist"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>Chore Assignments</h3>
              </a>
              <p>Assign chores to family members with ease. Keep track of completed tasks and reward progress to motivate everyone in the household.</p>
            </div>
          </div>
    
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item item-teal position-relative">
              <div class="icon">
                <i class="bi bi-graph-up"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>Progress Tracking</h3>
              </a>
              <p>Monitor your family’s progress through detailed charts and tables. See who’s excelling and who might need a little extra encouragement.</p>
            </div>
          </div>
    
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item item-red position-relative">
              <div class="icon">
                <i class="bi bi-gift"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>Rewards System</h3>
              </a>
              <p>Set up a rewards system where completed chores earn points that can be redeemed for exciting products or family privileges.</p>
            </div>
          </div>
    
        </div>
    
      </div>
    
    </section>
    
    <main>
    <h2>Assigned Chores</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Chore</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($chores)): ?>
            <?php foreach ($chores as $chore): ?>
            <tr>
                <td><?= htmlspecialchars($chore['chore_name']) ?></td>
                <td><?= htmlspecialchars($chore['points']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2">No assigned chores.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <form method="POST">
        <button type="submit" name="claim_points">Claim Points</button>
    </form>

   
</main>


    <footer id="footer" class="footer dark-background">

<div class="footer-newsletter">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-6">
        <h4>Join Our Newsletter</h4>
        <p>Subscribe to our newsletter and receive the latest news about our products and services!</p>
        <form action="newsletter.php" method="post" class="php-email-form">
          <div class="newsletter-form">
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="submit" value="Subscribe">
          </div>
          <div class="loading">Loading</div>
          <div class="sent-message" style="display:none;">Your subscription request has been sent. Thank you!</div>
        </form>
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
<script>
document.getElementById("openModal").addEventListener("click", function() {
    document.getElementById("addMemberModal").style.display = "block";
});

document.querySelector(".close").addEventListener("click", function() {
    document.getElementById("addMemberModal").style.display = "none";
});

// Image Preview Functionality
document.getElementById("memberImage").addEventListener("change", function(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const imagePreview = document.getElementById("imagePreview");
        imagePreview.src = reader.result;
        imagePreview.style.display = "block";
    };
    reader.readAsDataURL(event.target.files[0]);
});
</script>
<!-- JavaScript to handle modal display -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("addMemberModal");
        const closeBtn = document.querySelector(".close");
        const signUpButton = document.querySelector(".button-submit");

        signUpButton.addEventListener("click", function (event) {
            event.preventDefault();
            modal.style.display = "flex";
        });

        closeBtn.addEventListener("click", function () {
            modal.style.display = "none";
        });

        window.addEventListener("click", function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    });
</script>
</body>

</html>