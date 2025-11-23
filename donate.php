<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate and sanitize form data
    if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['amount']) || 
        !isset($_POST['card_number']) || !isset($_POST['expiry']) || !isset($_POST['cvc'])) {
        echo "<script>alert('Error: All fields are required!');</script>";
        exit();
    }
    
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Error: Invalid email address!');</script>";
        exit();
    }
    
    // Validate amount
    if ($amount <= 0) {
        echo "<script>alert('Error: Invalid donation amount!');</script>";
        exit();
    }
    
    // Encrypt sensitive payment information
    $card_number = password_hash($_POST['card_number'], PASSWORD_DEFAULT);
    $expiry = password_hash($_POST['expiry'], PASSWORD_DEFAULT);
    $cvc = password_hash($_POST['cvc'], PASSWORD_DEFAULT);
    
    // Insert into database
    $sql = "INSERT INTO donations (name, email, amount, card_number, expiry, cvc, donation_date) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssdsss", $name, $email, $amount, $card_number, $expiry, $cvc);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Thank you for your donation!');</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Donate - VolunteerHub</title>
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
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    .header {
      --background-color: #FFDDC1 !important;
      background-color: #FFDDC1 !important;
    }
    .navmenu a {
      text-decoration: none !important;
    }
    .navmenu a:hover {
      text-decoration: none !important;
    }
    .header .logo h1.sitename {
      border-bottom: none !important;
      text-decoration: none !important;
      border: none !important;
      box-shadow: none !important;
    }
    .header .logo {
      border-bottom: none !important;
      border: none !important;
      text-decoration: none !important;
    }
    .header .logo a {
      border-bottom: none !important;
      text-decoration: none !important;
    }
    a.logo {
      border-bottom: none !important;
      text-decoration: none !important;
    }
    .donation-card {
      transition: transform 0.3s ease;
    }
    .donation-card:hover {
      transform: translateY(-5px);
    }
    body {
      font-family: 'Mulish', sans-serif;
      background-color: #f9f7f2;
    }
    .impact-item {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .impact-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    .story-card {
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .story-card:hover {
      background-color: #e4dccc;
      transform: translateY(-4px);
    }
    .section-title {
      color: #5c4d3c;
    }
    .icon-color {
      color: #5c4d3c;
    }
    .highlight {
      color: #4a3f2f;
    }
  </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>

<body class="index-page">


<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">VolunteerHub</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
      <li><a href="famify.php">Organization Center</a></li>      
      <li><a href="addfam.php" id="openModal">Add a Volunteer</a></li>
      <li><a href="account.php">Your Account</a></li>
      <li><a href="connect.html">Connect</a></li>
        <li><a href="points_shop.php">Achievements</a></li>
        <li><a href="family_calendar.php">Events Calendar</a></li>
        <li><a href="donate.php">Donate</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>
<div class="page-title dark-background">
    <div class="container position-relative">
      <h1>Donate</h1>
      <p>A way to support volunteer organizations all over the world</p>
      <nav class="breadcrumbs">
        <ol>
        </ol>
      </nav>
    </div>
  </div>
  
  <div class="container py-5" style="max-width: 800px; margin: 0 auto;">
    <!-- Financial Donation -->
    <div class="bg-white rounded-lg shadow-lg p-4 donation-card mb-4">
      <h2 class="h3 fw-bold mb-3" style="color: #5c4d3c;">Financial Support</h2>
      <p class="mb-4" style="color: #4a3f2f;">Your financial contribution helps volunteer organizations provide community support, resources, and services to those in need.</p>
      <form id="donation-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
          <label class="form-label fw-medium" style="color: #4a3f2f;">Donation Amount</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" id="amount" name="amount" class="form-control" placeholder="Enter amount" min="1" step="0.01" required>
          </div>
        </div>
        <div class="mb-3">
          <input type="text" id="name" name="name" placeholder="Full Name" class="form-control" required>
        </div>
        <div class="mb-3">
          <input type="email" id="email" name="email" placeholder="Email Address" class="form-control" required>
        </div>
        <div class="bg-light p-3 rounded mb-3">
          <label class="form-label fw-medium mb-2" style="color: #4a3f2f;">Payment Information</label>
          <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" class="form-control mb-3" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(.{4})/g, '$1 ').trim().slice(0, 19)" required>
          <div class="row">
            <div class="col-md-6 mb-3">
              <input type="text" id="expiry" name="expiry" placeholder="MM/YY" maxlength="5" class="form-control" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\d{2})(\d{1,2})/, '$1/$2').slice(0, 5)" required>
            </div>
            <div class="col-md-6 mb-3">
              <input type="text" id="cvc" name="cvc" placeholder="123" maxlength="3" class="form-control" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3)" required>
            </div>
          </div>
        </div>
        <button type="submit" class="btn w-100" style="background-color: #d8c5a0; color: #4a3f2f; font-weight: 500;">Donate Now</button>
      </form>
    </div>

  </div>

  <div class="container py-5" style="max-width: 1200px;">
    <!-- Your Impact Section -->
    <div class="bg-white rounded-4 shadow p-4 mb-5">
      <h2 class="fs-3 fw-bold section-title mb-4">Your Impact</h2>
      <div class="row text-center">
        <div class="col-md-4 mb-4 impact-item">
          <div class="fs-1 icon-color mb-3">
            <i class="fa-solid fa-house"></i>
          </div>
          <h3 class="fs-5 fw-bold section-title">Housing Support</h3>
          <p class="highlight">Helped 42 organizations secure resources this month</p>
        </div>
        <div class="col-md-4 mb-4 impact-item">
          <div class="fs-1 icon-color mb-3">
            <i class="fa-solid fa-clipboard"></i>
          </div>
          <h3 class="fs-5 fw-bold section-title">Food Assistance</h3>
          <p class="highlight">Provided 1,200 meals to communities in need</p>
        </div>
        <div class="col-md-4 mb-4 impact-item">
          <div class="fs-1 icon-color mb-3">
            <i class="fa-solid fa-heart"></i>
          </div>
          <h3 class="fs-5 fw-bold section-title">Medical Support</h3>
          <p class="highlight">Covered medical expenses for 18 children</p>
        </div>
      </div>
    </div>

    <!-- Stories of Hope Section -->
    <div class="bg-white rounded-4 shadow p-4">
      <h2 class="fs-3 fw-bold section-title mb-4">Stories of Hope</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="story-card bg-[#f5f1e8] p-4 rounded-3">
            <p class="fst-italic highlight mb-3">"The support we received helped us get back on our feet after losing our home in a fire. We couldn't have made it through without this amazing community."</p>
            <p class="fw-bold section-title mb-0">- Community Volunteers</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="story-card bg-[#f5f1e8] p-4 rounded-3">
            <p class="fst-italic highlight mb-3">"When my son needed specialized medical treatment, I didn't know how we would afford it. The donations we received made it possible for him to get the care he needed."</p>
            <p class="fw-bold section-title mb-0">- Maria G.</p>
          </div>
        </div>
      </div>
    </div>
</div>





    

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
        <span class="sitename">VolunteerHub</span>
      </a>
      <div class="footer-contact pt-3">
        <p>1234 Elm Street</p>
        <p>Los Angeles, CA 90001</p>
        <p class="mt-3"><strong>Phone:</strong> <span>+1 2345 6789 01</span></p>
        <p><strong>Email:</strong> <span>volunteerhub@info.com</span></p>
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
        <li><i class="bi bi-chevron-right"></i> <a href="#">Managing Your Volunteer Organization</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">How VolunteerHub Keeps Your Organization on Track</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Manage Missions and Achievements</a></li>
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
  <p>© <span>Copyright</span> <strong class="px-1 sitename">VolunteerHub</strong> <span>All Rights Reserved</span></p>
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
  document.addEventListener("DOMContentLoaded", () => {
    const amountInput = document.getElementById('amount');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const cardInput = document.getElementById('card-number');
    const expiryInput = document.getElementById('expiry');
    const cvcInput = document.getElementById('cvc');

    function launchCelebration() {
      const canvas = document.createElement('canvas');
      canvas.style.position = 'fixed';
      canvas.style.top = 0;
      canvas.style.left = 0;
      canvas.style.width = '100%';
      canvas.style.height = '100%';
      canvas.style.zIndex = 9999;
      canvas.id = 'confetti-canvas';

      document.body.appendChild(canvas);
      const confetti = window.confetti.create(canvas, { resize: true, useWorker: true });
      confetti({ particleCount: 300, spread: 180, origin: { y: 0.6 } });

      setTimeout(() => {
        document.body.removeChild(canvas);
      }, 3000);
    }

    // Handle form submission
    const donationForm = document.getElementById('donation-form');
    if (donationForm) {
      donationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const name = nameInput.value || 'Friend';
        const amount = amountInput.value || '0';
        
        // Submit the form
        this.submit();
        
        // Show celebration after a short delay
        setTimeout(() => {
          launchCelebration();
        }, 100);
      });
    }
  });
</script>

</body>

</html>

</html>