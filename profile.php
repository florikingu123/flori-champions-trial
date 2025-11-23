<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$member_email = $_SESSION['email'];

// Calculate level function
function calculateLevel($hours) {
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

$user_points = 0;
$user_level = 1;

// Fetch all volunteer data
$volunteer_data = [];
$stmt = $conn->prepare("
    SELECT f.id, f.member_name, f.member_email, f.points, f.managers_email,
           COALESCE(f.games_played, 0) as games_played,
           COALESCE(f.games_won, 0) as games_won,
           COALESCE(f.game_points, 0) as game_points
    FROM family f 
    WHERE f.member_email = ?
");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $volunteer_data = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$volunteer_data) {
    header("Location: signin.php");
    exit();
}

// Calculate level
$volunteer_data['level'] = calculateLevel($volunteer_data['points']);
$user_points = $volunteer_data['points'];
$user_level = $volunteer_data['level'];

// Get organization name
$org_name = 'Unknown Organization';
if (!empty($volunteer_data['managers_email'])) {
    $org_stmt = $conn->prepare("SELECT company_name FROM users WHERE email = ?");
    if ($org_stmt) {
        $org_stmt->bind_param("s", $volunteer_data['managers_email']);
        if ($org_stmt->execute()) {
            $org_result = $org_stmt->get_result();
            $org_row = $org_result->fetch_assoc();
            if ($org_row) {
                $org_name = $org_row['company_name'] ?? $volunteer_data['managers_email'];
            }
        }
        $org_stmt->close();
    }
}

// Get mission statistics
$mission_stats = [
    'total' => 0,
    'completed' => 0,
    'pending' => 0,
    'rejected' => 0,
    'redeemed' => 0
];

$mission_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' OR status = 'redeemed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) as redeemed
    FROM chores 
    WHERE member_email = ?
");
if ($mission_stmt) {
    $mission_stmt->bind_param("s", $member_email);
    if ($mission_stmt->execute()) {
        $mission_result = $mission_stmt->get_result();
        $mission_stats = $mission_result->fetch_assoc() ?? $mission_stats;
    }
    $mission_stmt->close();
}

// Get rewards statistics
$rewards_stats = [
    'total_redeemed' => 0,
    'total_pending' => 0
];

$rewards_stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status = 'redeemed' THEN 1 ELSE 0 END) as total_redeemed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending
    FROM assigned_rewards 
    WHERE member_email = ?
");
if ($rewards_stmt) {
    $rewards_stmt->bind_param("s", $member_email);
    if ($rewards_stmt->execute()) {
        $rewards_result = $rewards_stmt->get_result();
        $rewards_stats = $rewards_result->fetch_assoc() ?? $rewards_stats;
    }
    $rewards_stmt->close();
}

// Get recent activity (last 30 days)
$recent_activity = 0;
$activity_stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM chores 
    WHERE member_email = ? 
    AND (status = 'completed' OR status = 'redeemed')
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
if ($activity_stmt) {
    $activity_stmt->bind_param("s", $member_email);
    if ($activity_stmt->execute()) {
        $activity_result = $activity_stmt->get_result();
        $activity_row = $activity_result->fetch_assoc();
        $recent_activity = (int)($activity_row['count'] ?? 0);
    }
    $activity_stmt->close();
}

// Get win rate
$win_rate = 0;
if ($volunteer_data['games_played'] > 0) {
    $win_rate = round(($volunteer_data['games_won'] / $volunteer_data['games_played']) * 100, 1);
}

// Get leaderboard rank (organization)
$org_rank = null;
if (!empty($volunteer_data['managers_email'])) {
    $rank_stmt = $conn->prepare("
        SELECT COUNT(*) + 1 as rank
        FROM family f
        WHERE f.managers_email = ? 
        AND (f.points > ? OR (f.points = ? AND f.member_email < ?))
    ");
    if ($rank_stmt) {
        $rank_stmt->bind_param("siis", 
            $volunteer_data['managers_email'],
            $volunteer_data['points'],
            $volunteer_data['points'],
            $member_email
        );
        if ($rank_stmt->execute()) {
            $rank_result = $rank_stmt->get_result();
            $rank_row = $rank_result->fetch_assoc();
            $org_rank = (int)($rank_row['rank'] ?? null);
        }
        $rank_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>My Profile - VolunteerHub</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
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
  </style>
    <style>
        :root {
            --primary-color: #5c4d3c;
            --accent-color: #FFD3B5;
            --heading-color: #333;
            --default-color: #666;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #4a3d30 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            margin: 0 auto 20px;
            border: 5px solid white;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .section-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: background 0.2s;
        }
        
        .section-toggle:hover {
            background: #e9ecef;
        }
        
        .section-toggle h3 {
            margin: 0;
            color: var(--heading-color);
            font-size: 1.3rem;
        }
        
        .toggle-switch {
            width: 50px;
            height: 26px;
            background: #ccc;
            border-radius: 13px;
            position: relative;
            transition: background 0.3s;
        }
        
        .toggle-switch.active {
            background: var(--primary-color);
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            top: 3px;
            left: 3px;
            transition: left 0.3s;
        }
        
        .toggle-switch.active::after {
            left: 27px;
        }
        
        .section-content {
            display: none;
        }
        
        .section-content.show {
            display: block;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--default-color);
            font-weight: 500;
        }
        
        .info-value {
            color: var(--heading-color);
            font-weight: 600;
        }
        
        .stat-badge {
            display: inline-block;
            padding: 8px 16px;
            background: var(--accent-color);
            color: var(--primary-color);
            border-radius: 20px;
            font-weight: 600;
            margin: 5px;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.2s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        body {
            padding-top: 80px;
        }
    </style>
</head>
<body class="index-page">
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">VolunteerHub</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="#">Level <?= $user_level ?? 1 ?> | <?= htmlspecialchars($user_points ?? 0) ?> hrs</a></li>
        <li><a href="member.php">Organization Center</a></li>
        <li><a href="games.php">Engagement Zone</a></li>
        <li><a href="profile.php" class="active">My Profile</a></li>
        <li><a href="rew.php">Achievements</a></li>
        <li><a href="view_calendar.php">Events Calendar</a></li>
        <li><a href="ai.php">AI</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>

<main class="main">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container text-center">
            <div class="profile-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h1 style="margin: 0; font-size: 2.5rem;"><?= htmlspecialchars($volunteer_data['member_name']) ?></h1>
            <p style="margin: 10px 0 0; opacity: 0.9; font-size: 1.1rem;">Level <?= $volunteer_data['level'] ?> Volunteer</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <!-- Basic Information Section -->
                <div class="profile-card">
                    <div class="section-toggle" onclick="toggleSection('basic')">
                        <h3><i class="bi bi-person"></i> Basic Information</h3>
                        <div class="toggle-switch active" id="toggle-basic"></div>
                    </div>
                    <div class="section-content show" id="section-basic">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?= htmlspecialchars($volunteer_data['member_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?= htmlspecialchars($volunteer_data['member_email']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Organization</span>
                            <span class="info-value"><?= htmlspecialchars($org_name) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Volunteer ID</span>
                            <span class="info-value">#<?= $volunteer_data['id'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Points & Level Section -->
                <div class="profile-card">
                    <div class="section-toggle" onclick="toggleSection('points')">
                        <h3><i class="bi bi-trophy"></i> Points & Level</h3>
                        <div class="toggle-switch active" id="toggle-points"></div>
                    </div>
                    <div class="section-content show" id="section-points">
                        <div class="info-item">
                            <span class="info-label">Total Points</span>
                            <span class="info-value"><?= number_format($volunteer_data['points']) ?> pts</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Current Level</span>
                            <span class="info-value">Level <?= $volunteer_data['level'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Points to Next Level</span>
                            <span class="info-value"><?= number_format((pow($volunteer_data['level'], 2) * 10) - $volunteer_data['points']) ?> pts</span>
                        </div>
                        <?php if ($org_rank): ?>
                        <div class="info-item">
                            <span class="info-label">Organization Rank</span>
                            <span class="info-value">#<?= $org_rank ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mission Statistics Section -->
                <div class="profile-card">
                    <div class="section-toggle" onclick="toggleSection('missions')">
                        <h3><i class="bi bi-clipboard-check"></i> Mission Statistics</h3>
                        <div class="toggle-switch active" id="toggle-missions"></div>
                    </div>
                    <div class="section-content show" id="section-missions">
                        <div class="info-item">
                            <span class="info-label">Total Missions</span>
                            <span class="info-value"><?= $mission_stats['total'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Completed Missions</span>
                            <span class="info-value" style="color: #28a745;"><?= $mission_stats['completed'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pending Missions</span>
                            <span class="info-value" style="color: #ffc107;"><?= $mission_stats['pending'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Redeemed Missions</span>
                            <span class="info-value" style="color: #17a2b8;"><?= $mission_stats['redeemed'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Rejected Missions</span>
                            <span class="info-value" style="color: #dc3545;"><?= $mission_stats['rejected'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Missions (Last 30 Days)</span>
                            <span class="info-value"><?= $recent_activity ?></span>
                        </div>
                    </div>
                </div>

                <!-- Game Statistics Section -->
                <div class="profile-card">
                    <div class="section-toggle" onclick="toggleSection('games')">
                        <h3><i class="bi bi-controller"></i> Game Statistics</h3>
                        <div class="toggle-switch active" id="toggle-games"></div>
                    </div>
                    <div class="section-content show" id="section-games">
                        <div class="info-item">
                            <span class="info-label">Games Played</span>
                            <span class="info-value"><?= $volunteer_data['games_played'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Games Won</span>
                            <span class="info-value" style="color: #28a745;"><?= $volunteer_data['games_won'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Win Rate</span>
                            <span class="info-value"><?= $win_rate ?>%</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Game Points Earned</span>
                            <span class="info-value"><?= number_format($volunteer_data['game_points']) ?> pts</span>
                        </div>
                    </div>
                </div>

                <!-- Rewards Section -->
                <div class="profile-card">
                    <div class="section-toggle" onclick="toggleSection('rewards')">
                        <h3><i class="bi bi-gift"></i> Rewards & Achievements</h3>
                        <div class="toggle-switch active" id="toggle-rewards"></div>
                    </div>
                    <div class="section-content show" id="section-rewards">
                        <div class="info-item">
                            <span class="info-label">Rewards Redeemed</span>
                            <span class="info-value" style="color: #28a745;"><?= $rewards_stats['total_redeemed'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pending Rewards</span>
                            <span class="info-value" style="color: #ffc107;"><?= $rewards_stats['total_pending'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary Section -->
                <div class="profile-card">
                    <div class="section-toggle" onclick="toggleSection('activity')">
                        <h3><i class="bi bi-activity"></i> Activity Summary</h3>
                        <div class="toggle-switch active" id="toggle-activity"></div>
                    </div>
                    <div class="section-content show" id="section-activity">
                        <div class="info-item">
                            <span class="info-label">Total Volunteer Hours</span>
                            <span class="info-value"><?= number_format($volunteer_data['points']) ?> hours</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Missions Completed (30 Days)</span>
                            <span class="info-value"><?= $recent_activity ?> missions</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Completion Rate</span>
                            <span class="info-value">
                                <?php 
                                $completion_rate = $mission_stats['total'] > 0 
                                    ? round(($mission_stats['completed'] / $mission_stats['total']) * 100, 1) 
                                    : 0; 
                                echo $completion_rate . '%';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Load saved preferences from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const sections = ['basic', 'points', 'missions', 'games', 'rewards', 'activity'];
            sections.forEach(section => {
                const saved = localStorage.getItem('profile_section_' + section);
                if (saved === 'false') {
                    toggleSection(section, false);
                }
            });
        });

        function toggleSection(sectionId, animate = true) {
            const section = document.getElementById('section-' + sectionId);
            const toggle = document.getElementById('toggle-' + sectionId);
            
            if (section && toggle) {
                const isActive = toggle.classList.contains('active');
                
                if (animate) {
                    if (isActive) {
                        section.style.display = 'none';
                        toggle.classList.remove('active');
                        localStorage.setItem('profile_section_' + sectionId, 'false');
                    } else {
                        section.style.display = 'block';
                        toggle.classList.add('active');
                        localStorage.setItem('profile_section_' + sectionId, 'true');
                    }
                } else {
                    if (!isActive) {
                        section.style.display = 'none';
                        toggle.classList.remove('active');
                    }
                }
            }
        }
    </script>
</body>
</html>

