<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$manager_email = $_SESSION['email'];

// Handle adding an achievement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reward'])) {
    $name = trim($_POST['reward_name']);
    $points = intval($_POST['reward_points']);
    $description = trim($_POST['reward_description']);
    
    // Handle file upload
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $image = time() . '_' . basename($_FILES['reward_image']['name']);
    $targetFile = $targetDir . $image;
    
    if (move_uploaded_file($_FILES['reward_image']['tmp_name'], $targetFile)) {
        // Insert reward - using the correct table structure
        $sql = "INSERT INTO rewards (name, image, points_required, assigned_to) VALUES (?, ?, ?, '')";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
        } else {
            $stmt->bind_param("ssi", $name, $image, $points);
            
            if ($stmt->execute()) {
                echo "<script>alert('Achievement added successfully!'); window.location.href='points_shop.php';</script>";
            } else {
                echo "<script>alert('Error adding reward: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>alert('Error uploading file.');</script>";
    }
}

// Handle assigning an achievement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_reward'])) {
    $member_email = trim($_POST['member_email']);
    $reward_id = intval($_POST['reward_id']);
    
    // First check if the member exists in the family table
    $check_member = $conn->prepare("SELECT member_email FROM family WHERE member_email = ?");
    $check_member->bind_param("s", $member_email);
    $check_member->execute();
    $result = $check_member->get_result();
    
    if ($result->num_rows > 0) {
        // Check if reward is already assigned
        $check_assignment = $conn->prepare("SELECT id FROM assigned_rewards WHERE reward_id = ? AND member_email = ?");
        $check_assignment->bind_param("is", $reward_id, $member_email);
        $check_assignment->execute();
        $assignment_result = $check_assignment->get_result();
        
        if ($assignment_result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO assigned_rewards (member_email, reward_id, status) VALUES (?, ?, 'pending')");
    if ($stmt) {
        $stmt->bind_param("si", $member_email, $reward_id);
        if ($stmt->execute()) {
                    echo "<script>alert('Achievement assigned successfully!');</script>";
                } else {
                    echo "<script>alert('Error assigning reward: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Database error: " . $conn->error . "');</script>";
            }
        } else {
            echo "<script>alert('This achievement is already assigned to this volunteer.');</script>";
        }
        $check_assignment->close();
    } else {
        echo "<script>alert('Volunteer not found. Please enter a valid volunteer email.');</script>";
    }
    $check_member->close();
}

// Get all rewards
$rewards = $conn->query("SELECT * FROM rewards ORDER BY points_required ASC");
if (!$rewards) {
    echo "<script>alert('Error fetching rewards: " . $conn->error . "');</script>";
} else {
    // Debug: Show number of rewards found
    $num_rewards = $rewards->num_rows;
    echo "<script>console.log('Number of rewards found: " . $num_rewards . "');</script>";
    
    // Debug: Show first reward if any
    if ($num_rewards > 0) {
        $first_reward = $rewards->fetch_assoc();
        echo "<script>console.log('First reward: " . json_encode($first_reward) . "');</script>";
        // Reset the pointer back to the beginning
        $rewards->data_seek(0);
    }
}

// Get family members for the current manager
$family_members = $conn->prepare("SELECT member_email FROM family WHERE managers_email = ?");
$family_members->bind_param("s", $manager_email);
$family_members->execute();
$members_result = $family_members->get_result();

// Get assigned rewards
$assigned_rewards_query = "SELECT r.*, ar.status, ar.member_email 
                         FROM rewards r 
                         INNER JOIN assigned_rewards ar ON r.id = ar.reward_id 
                         WHERE ar.member_email IN (
                             SELECT member_email 
                             FROM family 
                             WHERE managers_email = ?
                         )
                         ORDER BY ar.created_at DESC";

try {
    $assigned_stmt = $conn->prepare($assigned_rewards_query);
    if (!$assigned_stmt) {
        throw new Exception("Error preparing assigned rewards query: " . $conn->error);
    }

    $assigned_stmt->bind_param("s", $manager_email);
    if (!$assigned_stmt->execute()) {
        throw new Exception("Error executing assigned rewards query: " . $assigned_stmt->error);
    }

    $assigned_rewards = $assigned_stmt->get_result();
    if (!$assigned_rewards) {
        throw new Exception("Error getting assigned rewards result: " . $assigned_stmt->error);
    }
} catch (Exception $e) {
    echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    $assigned_rewards = false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Achievements Shop - VolunteerHub</title>
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
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .rewards-section {
        padding: 60px 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .rewards-grid {
        background: #fff;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-top: 30px;
    }

    .rewards-grid h2 {
        text-align: center;
        font-size: 36px;
        margin-bottom: 50px;
        font-weight: 600;
        position: relative;
        padding-bottom: 15px;
        color: #333;
    }

    .rewards-grid h2:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: #FFD3B5;
    }

    .rewards-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 30px;
    }

    .reward-card {
        background: #fff;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #FFD3B5;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    position: relative;
        overflow: hidden;
    }

    .reward-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .reward-image {
    width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: 12px;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
}

    .reward-card:hover .reward-image {
        transform: scale(1.02);
    }

    .reward-name {
        font-size: 22px;
        margin: 15px 0;
        font-weight: 600;
        line-height: 1.3;
        color: #333;
    }

    .reward-details {
        margin: 20px 0;
    display: flex;
    flex-direction: column;
        gap: 12px;
    }

    .reward-details p {
        margin: 0;
        font-size: 15px;
        line-height: 1.5;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .reward-details strong {
        font-weight: 600;
        min-width: 120px;
        color: #555;
    }

    .points-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #FFD3B5;
        color: #333;
    }

    .status-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .status-badge.success {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.warning {
        background: #fff3cd;
        color: #856404;
    }

    .btn-success {
        margin-top: auto;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
    align-items: center;
        justify-content: center;
        gap: 8px;
        background: #FFD3B5;
        color: #333;
        border: none;
    }

    .btn-success:hover {
        background: #FFAAA5;
        transform: translateY(-2px);
    }

    .no-rewards {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 16px;
        border: 2px dashed #FFD3B5;
    }

    .no-rewards p {
        font-size: 18px;
        line-height: 1.6;
        max-width: 500px;
        margin: 0 auto;
        color: #666;
    }

    .form-section {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-bottom: 40px;
    }

    .reward-form-card {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .reward-form-card:hover {
        transform: translateY(-5px);
    }

    .form-title {
        color: #333;
        font-size: 28px;
        font-weight: 600;
        text-align: center;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        color: #555;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .inputForm {
        position: relative;
        border: 2px solid #e1e1e1;
    border-radius: 12px;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .inputForm:focus-within {
        border-color: #FFD3B5;
        background: white;
        box-shadow: 0 0 0 4px rgba(255, 211, 181, 0.1);
}

    .form-control {
    width: 100%;
        padding: 15px;
        border: none;
    background: transparent;
        font-size: 15px;
        color: #333;
    }

    .form-control:focus {
    outline: none;
    }

    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    /* Custom File Upload */
    .file-upload-wrapper {
        position: relative;
        margin-bottom: 20px;
    }

    .file-upload-input {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px;
        background: #f8f9fa;
        border: 2px solid #e1e1e1;
        border-radius: 12px;
        color: #555;
        font-size: 15px;
        font-weight: 500;
    cursor: pointer;
        transition: all 0.3s ease;
    }

    .file-upload-label:hover {
        border-color: #FFD3B5;
        background: white;
    }

    .file-upload-label i {
        margin-right: 10px;
        font-size: 20px;
    }

    .file-name {
        margin-top: 8px;
        font-size: 14px;
        color: #666;
    }

    .form-text {
        margin-top: 8px;
        font-size: 14px;
        color: #666;
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .submit-button {
        flex: 1;
        padding: 15px;
        border: none;
        border-radius: 12px;
    font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    text-align: center;
        color: #333;
        background: #FFD3B5;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .submit-button:hover {
        background: #FFAAA5;
        transform: translateY(-2px);
}

    .submit-button i {
        font-size: 20px;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .rewards-container {
            gap: 25px;
        }
    }

    @media (max-width: 992px) {
        .rewards-container {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .rewards-grid {
            padding: 30px;
        }
    }

    @media (max-width: 768px) {
        .rewards-container {
            grid-template-columns: 1fr;
        }
        
        .rewards-grid {
            padding: 25px;
        }
        
        .rewards-grid h2 {
            font-size: 28px;
            margin-bottom: 35px;
}
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
      <li><a href="addfam.php" id="openModal">Add a Family Member</a></li>
        <li><a href="account.php">Your Account</a></li>
        <li><a href="connect.html">Connect</a></li>
        <li><a href="points_shop.php">Rewards</a></li>
        <li><a href="family_calendar.php">Calendar</a></li>
        <li><a href="donate.php">Donate</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>


<div class="page-title">
    <div class="container position-relative">
        <h1>Rewards Management</h1>
        <p>Create and assign rewards like certificates, recognition badges, and achievements that volunteers can earn with their volunteer hours</p>
    </div>
  </div>
  
<section class="rewards-section">
    <div class="container">
        <div class="form-section">
            <div class="reward-form-card">
                <h2 class="form-title">Add New Reward</h2>
                <form method="POST" enctype="multipart/form-data" action="">
                    <div class="form-group">
                        <label for="reward_name">Reward Name</label>
                        <div class="inputForm">
                            <input type="text" id="reward_name" name="reward_name" class="form-control" 
                                   placeholder="Enter reward name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reward_description">Description</label>
                        <div class="inputForm">
                            <textarea id="reward_description" name="reward_description" class="form-control" 
                                      placeholder="Enter reward description" required rows="4"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reward_points">Volunteer Hours Required</label>
                        <div class="inputForm">
                            <input type="number" id="reward_points" name="reward_points" class="form-control" 
                                   placeholder="Enter volunteer hours required" min="1" required>
                        </div>
                        <small class="form-text">Volunteers need this many hours to redeem this reward (certificates, badges, etc.)</small>
                    </div>

                    <div class="form-group">
                        <label>Reward Image</label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="reward_image" name="reward_image" class="file-upload-input" 
                                   accept="image/*" required>
                            <label for="reward_image" class="file-upload-label">
                                <i class="bi bi-cloud-upload"></i>
                                Choose an image
                            </label>
                            <div class="file-name"></div>
                        </div>
                        <small class="form-text">Upload an image for the reward (JPG, PNG, GIF)</small>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="add_reward" class="submit-button">
                            <i class="bi bi-plus-circle"></i>
                            Add Reward
                        </button>
                    </div>
    </form>
            </div>

            <div class="reward-form-card">
                <h2 class="form-title">Assign Reward</h2>
    <form method="POST">
                    <div class="form-group">
                        <label for="reward_id">Select Reward</label>
                        <div class="inputForm">
                            <select id="reward_id" name="reward_id" class="form-control" required>
                                <option value="">Choose a reward...</option>
                                <?php 
                                $rewards->data_seek(0);
                                while ($row = $rewards->fetch_assoc()): 
                                ?>
                                    <option value="<?= $row['id'] ?>">
                                        <?= htmlspecialchars($row['name']) ?> (<?= $row['points_required'] ?> hours)
                                    </option>
            <?php endwhile; ?>
        </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="member_email">Volunteer Email</label>
                        <div class="inputForm">
                            <input type="email" id="member_email" name="member_email" class="form-control" 
                                   placeholder="Enter volunteer's email" required>
                        </div>
                        <small class="form-text">Enter the email address of the volunteer you want to assign this reward to.</small>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="assign_reward" class="submit-button">
                            <i class="bi bi-gift"></i>
                            Assign Reward
                        </button>
                    </div>
    </form>
            </div>
        </div>

        <div class="rewards-grid">
            <h2>Rewards Dashboard</h2>
            <div class="rewards-container">
                <?php
                // Query to get all assigned rewards with their status
                $dashboard_query = "SELECT r.*, ar.status, ar.member_email, f.member_name 
                                  FROM rewards r 
                                  INNER JOIN assigned_rewards ar ON r.id = ar.reward_id 
                                  LEFT JOIN family f ON ar.member_email = f.member_email 
                                  WHERE ar.member_email IN (
                                      SELECT member_email 
                                      FROM family 
                                      WHERE managers_email = ?
                                  )
                                  ORDER BY ar.id DESC";

                $dashboard_stmt = $conn->prepare($dashboard_query);
                if ($dashboard_stmt) {
                    $dashboard_stmt->bind_param("s", $manager_email);
                    $dashboard_stmt->execute();
                    $dashboard_result = $dashboard_stmt->get_result();

                    if ($dashboard_result && $dashboard_result->num_rows > 0): 
                        while ($row = $dashboard_result->fetch_assoc()): 
                            $status_class = $row['status'] === 'redeemed' ? 'success' : 'warning';
                            $status_text = ucfirst($row['status']);
                ?>
                            <div class="reward-card">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($row['image']) ?>" 
                                         alt="<?= htmlspecialchars($row['name']) ?>" 
                                         class="reward-image">
                                <?php endif; ?>
                                <h3 class="reward-name"><?= htmlspecialchars($row['name']) ?></h3>
                                <div class="reward-details">
                                    <p>
                                        <strong>Assigned to:</strong>
                                        <span><?= htmlspecialchars($row['member_name'] ?? $row['member_email']) ?></span>
                                    </p>
                                    <p>
                                        <strong>Points Required:</strong>
                                        <span class="points-badge">
                                            <?= number_format($row['points_required']) ?> points
                                        </span>
                                    </p>
                                    <p>
                                        <strong>Status:</strong>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </p>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" action="redeem_reward.php">
                                            <input type="hidden" name="reward_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="member_email" value="<?= htmlspecialchars($row['member_email']) ?>">
                                            <button type="submit" class="btn-success">
                                                <i class="bi bi-check-circle"></i>
                                                Mark as Redeemed
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                <?php 
                        endwhile;
                    else: 
                ?>
                        <div class="no-rewards">
                            <p>No rewards have been assigned yet. Start by creating rewards like certificates, recognition badges, or achievements that volunteers can earn with their volunteer hours!</p>
                        </div>
                <?php 
                    endif;
                    $dashboard_stmt->close();
                } else {
                    echo "<div class='alert alert-danger'>Error preparing dashboard query: " . $conn->error . "</div>";
                }
                ?>
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

  <script>
    // File upload preview
    document.getElementById('reward_image').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const fileLabel = document.querySelector('.file-name');
        if (fileName) {
            fileLabel.textContent = fileName;
        } else {
            fileLabel.textContent = '';
        }
    });

    // Add success/error message handling
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
  </script>

</body>

</html>
</html>
</html>
</html>




