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

// First check if user exists in family table
$check_stmt = $conn->prepare("SELECT * FROM family WHERE member_email = ?");
$check_stmt->bind_param("s", $member_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$family_data = $check_result->fetch_assoc();
$check_stmt->close();

if (!$family_data) {
    // Redirect to directory instead of showing error
            header("Location: browse_directory.php?message=no_org");
    exit();
}

// Get member's points and family info
$user_points = $family_data['points'] ?? 0;
$manager_email = $family_data['managers_email'];

// Calculate level based on volunteer hours
function calculateLevel($hours) {
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

function getHoursForNextLevel($currentLevel) {
    $nextLevel = $currentLevel + 1;
    return pow($nextLevel - 1, 2) * 10;
}

$user_level = calculateLevel($user_points);
$hours_for_next = getHoursForNextLevel($user_level);
$hours_progress = $user_points - (pow($user_level - 1, 2) * 10);
$hours_needed = $hours_for_next - $user_points;
$progress_percent = $hours_for_next > 0 ? min(100, ($hours_progress / ($hours_for_next - (pow($user_level - 1, 2) * 10))) * 100) : 0;

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

// Fetch completed chores with verifications
$completed_chores = [];
$stmt = $conn->prepare("
    SELECT cv.*, c.chore_name, c.points, cv.status, cv.verified_at
    FROM chore_verifications cv 
    JOIN chores c ON cv.chore_id = c.id
    WHERE c.member_email = ? AND cv.status IN ('approved', 'rejected')
    ORDER BY cv.verified_at DESC
");
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}
$stmt->bind_param("s", $member_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $completed_chores[] = $row;
}
$stmt->close();

// Fetch rewards assigned to the user
$rewards = [];
$stmt = $conn->prepare("
    SELECT r.name, r.points_required, r.image 
    FROM rewards r 
    JOIN assigned_rewards ar ON r.id = ar.reward_id
    WHERE ar.member_email = ? AND ar.status = 'pending'
");
$stmt->bind_param("s", $member_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rewards[] = $row;
}
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

        echo "<script>alert('Points claimed successfully!'); window.location.href='member_dashboard.php';</script>";
        exit();
    } else {
        echo "<script>alert('No chores to claim.');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Member Dashboard - VolunteerHub</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .header {
            background-color: #FFD3B5;
        }
        
        .dashboard-container {
            padding: 80px 0;
        }
        
        .points-card {
            background: linear-gradient(135deg, #FFD3B5, #FFAAA5);
            color: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 163, 165, 0.2);
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid #FFD3B5;
        }
        
        .chores-section {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            border: 1px solid #FFD3B5;
        }
        
        .points-display {
            font-size: 4rem;
            font-weight: bold;
            margin: 20px 0;
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
                    <li><a href="#">Level <?= $user_level ?> | <?= htmlspecialchars($user_points) ?> hrs</a></li>
                    <li><a href="member_dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="games.php">Games</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="rew.php">Achievements</a></li>
                    <li><a href="view_calendar.php">Calendar</a></li>
                    <li><a href="ai.php">Chore AI</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container dashboard-container">
            <!-- Points Card -->
            <div class="points-card">
                <h2>Your Volunteer Profile</h2>
                <div style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; margin-top: 20px;">
                    <div>
                        <div style="font-size: 3rem; font-weight: bold; margin-bottom: 10px;">
                            <?= number_format($user_points) ?>
                        </div>
                        <div style="font-size: 1.1rem; opacity: 0.9;">Volunteer Hours</div>
                    </div>
                    <div style="border-left: 2px solid rgba(255,255,255,0.3); padding-left: 30px; margin-left: 30px;">
                        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">
                            Level <?= $user_level ?>
                        </div>
                        <div style="font-size: 1.1rem; opacity: 0.9;">Current Level</div>
                    </div>
                </div>
                <div style="margin-top: 25px; background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem;">
                        <span>Progress to Level <?= $user_level + 1 ?></span>
                        <span><?= number_format($hours_needed) ?> hours needed</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.3); height: 20px; border-radius: 10px; overflow: hidden;">
                        <div style="background: white; height: 100%; width: <?= $progress_percent ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center; color: #333; font-weight: bold; font-size: 0.85rem;">
                            <?= number_format($progress_percent, 1) ?>%
                        </div>
                    </div>
                </div>
                <p style="margin-top: 20px; font-size: 1rem;">Keep completing missions to earn more volunteer hours and level up!</p>
            </div>

            <!-- Stats Section -->
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value"><?= count($chores) ?></div>
                    <div class="stat-label">Pending Missions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div class="stat-value"><?= count($completed_chores) ?></div>
                    <div class="stat-label">Completed Missions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="stat-value"><?= count($rewards) ?></div>
                    <div class="stat-label">Available Achievements</div>
                </div>
            </div>

            <!-- Missions Section -->
            <div class="chores-section">
                <h2>Your Assigned Missions</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mission</th>
                                <th>Volunteer Hours</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($chores)): ?>
                                <?php foreach ($chores as $chore): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($chore['chore_name']) ?></td>
                                        <td><?= htmlspecialchars($chore['points']) ?> hours</td>
                                        <td>
                                            <button type="button" class="btn btn-verify" onclick="openCamera(<?= $chore['id'] ?>)">
                                                <i class="bi bi-camera"></i> Verify Completion
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No assigned missions.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Completed Missions Section -->
            <div class="chores-section">
                <h2>Completed Missions</h2>
                <div class="row">
                    <?php if (empty($completed_chores)): ?>
                        <div class="col-12 text-center">
                            <p>No completed missions yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($completed_chores as $chore): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="completed-chore-card">
                                    <img src="<?= htmlspecialchars($chore['photo_path']) ?>" alt="Completed mission" class="completed-chore-image">
                                    <div class="completed-chore-details">
                                        <h4><?= htmlspecialchars($chore['chore_name']) ?></h4>
                                        <p>Volunteer Hours Earned: <?= htmlspecialchars($chore['points']) ?></p>
                                        <p>Completed: <?= date('M d, Y H:i', strtotime($chore['verified_at'])) ?></p>
                                        <p class="status-badge <?= $chore['status'] === 'approved' ? 'status-approved' : 'status-rejected' ?>">
                                            <?= ucfirst($chore['status']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 
    <script src="assets/js/main.js"></script>
</body>
</html> 
    <script src="assets/js/main.js"></script>
</body>
</html> 
    <script src="assets/js/main.js"></script>
</body>
</html> 