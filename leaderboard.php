<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$user_email = $_SESSION['email'];

// Function to calculate level based on volunteer hours
function calculateLevel($hours) {
    // Level formula: Level = floor(sqrt(hours / 10)) + 1
    // This means:
    // Level 1: 0-9 hours
    // Level 2: 10-39 hours
    // Level 3: 40-89 hours
    // Level 4: 90-159 hours
    // Level 5: 160-249 hours
    // And so on...
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

// Function to get hours needed for next level
function getHoursForNextLevel($currentLevel) {
    // Reverse the formula: hours = (level - 1)^2 * 10
    $nextLevel = $currentLevel + 1;
    return pow($nextLevel - 1, 2) * 10;
}

// Get filter (all, organization, global)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'organization';
$manager_email = null;

// Check if user is a manager or member
$check_stmt = $conn->prepare("SELECT managers_email FROM family WHERE member_email = ?");
$check_stmt->bind_param("s", $user_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$user_data = $check_result->fetch_assoc();
$check_stmt->close();

if ($user_data) {
    $manager_email = $user_data['managers_email'];
}

// Fetch leaderboard data
if ($filter === 'organization' && $manager_email) {
    // Organization leaderboard
    $stmt = $conn->prepare("
        SELECT f.member_name, f.member_email, f.points as hours, f.managers_email,
               COUNT(DISTINCT cv.id) as completed_missions
        FROM family f
        LEFT JOIN chore_verifications cv ON f.member_email = cv.member_email AND cv.status = 'approved'
        WHERE f.managers_email = ?
        GROUP BY f.id, f.member_name, f.member_email, f.points, f.managers_email
        ORDER BY f.points DESC, completed_missions DESC
    ");
    $stmt->bind_param("s", $manager_email);
} else {
    // Global leaderboard
    $stmt = $conn->prepare("
        SELECT f.member_name, f.member_email, f.points as hours, f.managers_email,
               COUNT(DISTINCT cv.id) as completed_missions
        FROM family f
        LEFT JOIN chore_verifications cv ON f.member_email = cv.member_email AND cv.status = 'approved'
        GROUP BY f.id, f.member_name, f.member_email, f.points, f.managers_email
        ORDER BY f.points DESC, completed_missions DESC
        LIMIT 100
    ");
}

$stmt->execute();
$result = $stmt->get_result();
$leaderboard = [];
$user_rank = null;
$rank = 1;

while ($row = $result->fetch_assoc()) {
    $row['level'] = calculateLevel($row['hours']);
    $row['hours_for_next'] = getHoursForNextLevel($row['level']);
    $row['rank'] = $rank;
    
    if ($row['member_email'] === $user_email) {
        $user_rank = $row;
        $user_rank['rank'] = $rank;
    }
    
    $leaderboard[] = $row;
    $rank++;
}
$stmt->close();

// Get user's current stats
$user_stmt = $conn->prepare("SELECT points as hours FROM family WHERE member_email = ?");
$user_stmt->bind_param("s", $user_email);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_stats = $user_result->fetch_assoc();
$user_stmt->close();

$user_hours = $user_stats['hours'] ?? 0;
$user_level = calculateLevel($user_hours);
$hours_for_next = getHoursForNextLevel($user_level);
$hours_progress = $user_hours - (pow($user_level - 1, 2) * 10);
$hours_needed = $hours_for_next - $user_hours;
$progress_percent = $hours_for_next > 0 ? min(100, ($hours_progress / ($hours_for_next - (pow($user_level - 1, 2) * 10))) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Volunteer Leaderboard - VolunteerHub</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .header {
            background-color: #FFD3B5;
        }
        
        .leaderboard-container {
            padding: 100px 0 60px;
        }
        
        .user-stats-card {
            background: linear-gradient(135deg, #FFD3B5, #FFAAA5);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(255, 163, 165, 0.2);
        }
        
        .level-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .progress-container {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .progress-bar-custom {
            background: white;
            height: 25px;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #4CAF50, #45a049);
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 12px 24px;
            border: 2px solid #FFD3B5;
            background: white;
            color: #333;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #FFD3B5;
            color: white;
            transform: translateY(-2px);
        }
        
        .leaderboard-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .leaderboard-header {
            background: #FFD3B5;
            padding: 20px;
            text-align: center;
        }
        
        .leaderboard-header h2 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .leaderboard-item {
            display: grid;
            grid-template-columns: 80px 1fr 150px 150px 120px;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .leaderboard-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .leaderboard-item.user-item {
            background: #fff3cd;
            border-left: 4px solid #FFD3B5;
        }
        
        .rank-badge {
            font-size: 1.5rem;
            font-weight: bold;
            color: #FFD3B5;
            text-align: center;
        }
        
        .rank-badge.gold {
            color: #FFD700;
        }
        
        .rank-badge.silver {
            color: #C0C0C0;
        }
        
        .rank-badge.bronze {
            color: #CD7F32;
        }
        
        .volunteer-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .volunteer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #FFD3B5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
        }
        
        .volunteer-details h4 {
            margin: 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .volunteer-details p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .hours-display {
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
        }
        
        .level-display {
            text-align: center;
        }
        
        .level-badge-small {
            display: inline-block;
            background: #FFD3B5;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .missions-display {
            text-align: center;
            color: #666;
        }
        
        .medal-icon {
            font-size: 2rem;
        }
        
        @media (max-width: 768px) {
            .leaderboard-item {
                grid-template-columns: 60px 1fr;
                gap: 10px;
            }
            
            .hours-display, .level-display, .missions-display {
                grid-column: 2;
                text-align: left;
            }
        }
    </style>
    <?php include 'includes/theme_includes.php'; ?>
</head>
<body>
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.html" class="logo d-flex align-items-center">
                <h1 class="sitename">VolunteerHub</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="member.php">Volunteer Dashboard</a></li>
                    <li><a href="leaderboard.php" class="active">Leaderboard</a></li>
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container leaderboard-container">
            <!-- User Stats Card -->
            <div class="user-stats-card">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2>Your Volunteer Profile</h2>
                        <div class="level-badge">Level <?= $user_level ?></div>
                        <p style="margin-top: 15px; font-size: 1.1rem;">
                            <strong><?= number_format($user_hours) ?></strong> Volunteer Hours
                        </p>
                        <?php if ($user_rank): ?>
                            <p style="margin-top: 10px;">
                                <i class="bi bi-trophy"></i> Rank #<?= $user_rank['rank'] ?> 
                                <?= $filter === 'organization' ? 'in your organization' : 'globally' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="progress-container">
                            <p style="margin-bottom: 10px; font-weight: 500;">
                                Progress to Level <?= $user_level + 1 ?>
                            </p>
                            <div class="progress-bar-custom">
                                <div class="progress-fill" style="width: <?= $progress_percent ?>%">
                                    <?= number_format($hours_progress) ?> / <?= number_format($hours_for_next - (pow($user_level - 1, 2) * 10)) ?> hours
                                </div>
                            </div>
                            <p style="margin-top: 10px; font-size: 0.9rem;">
                                <?= number_format($hours_needed) ?> more hours to level up!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="?filter=organization" class="filter-btn <?= $filter === 'organization' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> My Organization
                </a>
                <a href="?filter=global" class="filter-btn <?= $filter === 'global' ? 'active' : '' ?>">
                    <i class="bi bi-globe"></i> Global Leaderboard
                </a>
            </div>

            <!-- Leaderboard Table -->
            <div class="leaderboard-table">
                <div class="leaderboard-header">
                    <h2>
                        <i class="bi bi-trophy"></i> 
                        <?= $filter === 'organization' ? 'Organization' : 'Global' ?> Volunteer Leaderboard
                    </h2>
                </div>
                
                <?php if (empty($leaderboard)): ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <p>No volunteers found yet. Be the first to complete missions!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($leaderboard as $volunteer): ?>
                        <div class="leaderboard-item <?= $volunteer['member_email'] === $user_email ? 'user-item' : '' ?>">
                            <div class="rank-badge <?= $volunteer['rank'] == 1 ? 'gold' : ($volunteer['rank'] == 2 ? 'silver' : ($volunteer['rank'] == 3 ? 'bronze' : '')) ?>">
                                <?php if ($volunteer['rank'] <= 3): ?>
                                    <i class="bi bi-trophy-fill medal-icon"></i>
                                <?php else: ?>
                                    #<?= $volunteer['rank'] ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="volunteer-info">
                                <div class="volunteer-avatar">
                                    <?= strtoupper(substr($volunteer['member_name'], 0, 1)) ?>
                                </div>
                                <div class="volunteer-details">
                                    <h4><?= htmlspecialchars($volunteer['member_name']) ?></h4>
                                    <p><?= htmlspecialchars($volunteer['member_email']) ?></p>
                                </div>
                            </div>
                            
                            <div class="hours-display">
                                <i class="bi bi-clock-history"></i> <?= number_format($volunteer['hours']) ?> hrs
                            </div>
                            
                            <div class="level-display">
                                <span class="level-badge-small">Level <?= $volunteer['level'] ?></span>
                            </div>
                            
                            <div class="missions-display">
                                <i class="bi bi-check-circle"></i> <?= $volunteer['completed_missions'] ?> missions
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer id="footer" class="footer dark-background">
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
            </div>
        </div>
        <div class="container copyright text-center mt-4">
            <p>© <span>Copyright</span> <strong class="px-1 sitename">VolunteerHub</strong> <span>All Rights Reserved</span></p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

