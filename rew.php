<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$member_email = $_SESSION['email'];

// Get user's points
$stmt = $conn->prepare("SELECT points FROM family WHERE member_email = ?");
$stmt->bind_param("s", $member_email);
$stmt->execute();
$stmt->bind_result($user_points);
$stmt->fetch();
$stmt->close();

// Get available rewards
$rewards_query = "SELECT r.id, r.name, r.image, r.points_required 
                 FROM rewards r 
                 INNER JOIN assigned_rewards ar ON r.id = ar.reward_id 
                 WHERE ar.member_email = ? 
                 AND ar.status = 'pending'
                 ORDER BY r.points_required ASC";
$rewards_stmt = $conn->prepare($rewards_query);
if (!$rewards_stmt) {
    die("Error preparing rewards query: " . $conn->error);
}
$rewards_stmt->bind_param("s", $member_email);
$rewards_stmt->execute();
$rewards = $rewards_stmt->get_result();

// Get redeemed rewards history
$redeemed_query = "SELECT r.name, r.points_required, ar.id as redemption_id
                  FROM assigned_rewards ar
                  JOIN rewards r ON ar.reward_id = r.id
                  WHERE ar.member_email = ? 
                  AND ar.status = 'redeemed'
                  ORDER BY ar.id DESC
                  LIMIT 5";
$redeemed_stmt = $conn->prepare($redeemed_query);
if (!$redeemed_stmt) {
    die("Error preparing redeemed rewards query: " . $conn->error);
}
$redeemed_stmt->bind_param("s", $member_email);
$redeemed_stmt->execute();
$redeemed_result = $redeemed_stmt->get_result();

// Handle reward redemption
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['redeem'])) {
    $reward_id = intval($_POST['reward_id']);
    $reward_points = intval($_POST['reward_points']);

    if ($user_points >= $reward_points) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Update user's points
            $stmt = $conn->prepare("UPDATE family SET points = points - ? WHERE member_email = ?");
            if (!$stmt) {
                throw new Exception("Error preparing points update statement");
            }
            $stmt->bind_param("is", $reward_points, $member_email);
            if (!$stmt->execute()) {
                throw new Exception("Error updating points");
            }
            $stmt->close();

            // Update the reward status to redeemed
            $stmt = $conn->prepare("UPDATE assigned_rewards SET status = 'redeemed' WHERE reward_id = ? AND member_email = ?");
            if (!$stmt) {
                throw new Exception("Error preparing reward status update statement");
            }
            $stmt->bind_param("is", $reward_id, $member_email);
            if (!$stmt->execute()) {
                throw new Exception("Error updating reward status");
            }
            $stmt->close();

            // If we got here, commit the transaction
            $conn->commit();
            
            // Refresh the page to show updated rewards
            echo "<script>alert('Achievement redeemed successfully!'); window.location.href='rew.php';</script>";
            exit();

        } catch (Exception $e) {
            // If there was an error, rollback the transaction
            $conn->rollback();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Not enough volunteer hours!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Achievements - VolunteerHub</title>
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
    .header {
      --background-color: #FFDDC1 !important;
      background-color: #FFDDC1 !important;
    }
    .rewards-dashboard {
      padding: 40px 0;
      background: #f8f9fa;
    }

    .points-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      text-align: center;
      margin-bottom: 30px;
    }

    .points-display {
      font-size: 3rem;
      color: #5c4d3c;
      font-weight: bold;
      margin: 20px 0;
    }

    .rewards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .reward-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }

    .reward-card:hover {
      transform: translateY(-5px);
    }

    .reward-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 15px;
    }

    .reward-name {
      font-size: 1.2rem;
      color: #5c4d3c;
      margin-bottom: 10px;
    }

    .reward-points {
      color: #666;
      margin-bottom: 15px;
    }

    .reward-description {
      color: #666;
      margin-bottom: 20px;
      font-size: 0.9rem;
    }

    .redeem-button {
      background: #5c4d3c;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
      width: 100%;
    }

    .redeem-button:hover {
      background: #4a3d30;
    }

    .redeem-button:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .history-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .history-item {
      padding: 15px;
      border-bottom: 1px solid #eee;
    }

    .history-item:last-child {
      border-bottom: none;
    }

    .history-date {
      color: #999;
      font-size: 0.9rem;
    }

    .section-title {
      color: #5c4d3c;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
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
      <li><a href="#">Your Hours: <?= htmlspecialchars($user_points ?? 0) ?></a></li>

      <li><a href="member.php">Organization Center</a></li>      
      <li><a href="games.php">Engagement Zone</a></li>
        <li><a href="profile.php">My Profile</a></li>
        <li><a href="rew.php" class="active">Achievements</a></li>
        <li><a href="view_calendar.php">Events Calendar</a></li>
        <li><a href="ai.php">AI</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>
<div class="page-title dark-background">
    <div class="container position-relative">
      <h1>Achievements Center</h1>
      <p>Redeem your volunteer hours for achievements and recognition</p>
      <nav class="breadcrumbs">
        <ol>
        </ol>
      </nav>
    </div>
  </div>
  <h2>Your Available Hours: <?= $user_points ?></h2>

<section class="rewards-dashboard">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="points-card">
                    <h3>Your Volunteer Hours</h3>
                    <div class="points-display">
                        <?= number_format($user_points) ?>
                    </div>
                    <p>Keep completing missions to earn more hours!</p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="history-card">
                    <h2 class="section-title">Recent Redemptions</h2>
                    <?php if ($redeemed_result && $redeemed_result->num_rows > 0): ?>
                        <?php while ($reward = $redeemed_result->fetch_assoc()): ?>
                            <div class="history-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4><?= htmlspecialchars($reward['name']) ?></h4>
                                    </div>
                                    <span class="points-badge"><?= number_format($reward['points_required']) ?> pts</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No rewards redeemed yet. Start earning points to redeem rewards!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="rewards-section mt-5">
            <h2 class="section-title">Available Rewards</h2>
            <div class="rewards-grid">
                <?php if ($rewards && $rewards->num_rows > 0): ?>
                    <?php while ($row = $rewards->fetch_assoc()): ?>
                        <div class="reward-card">
                            <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="reward-image">
                            <h3 class="reward-name"><?= htmlspecialchars($row['name']) ?></h3>
                            <p class="reward-points"><?= number_format($row['points_required']) ?> points</p>
                            <form method="POST">
                                <input type="hidden" name="reward_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="reward_points" value="<?= $row['points_required'] ?>">
                                <button type="submit" name="redeem" class="redeem-button" <?= $user_points < $row['points_required'] ? 'disabled' : '' ?>>
                                    <?= $user_points >= $row['points_required'] ? 'Redeem Now' : 'Not Enough Points' ?>
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No rewards available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
    

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
        <li><i class="bi bi-chevron-right"></i> <a href="#">Keeping Your Family Organized</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">How VolunteerHub Keeps Your Organization on Track</a></li>
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


</body>

</html>
</html>
</html>
</html>