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

$manager_email = $_SESSION['email'];

// Debugging: Ensure session email is set correctly
if (empty($manager_email)) {
    die("Error: No manager email found in session.");
}

// Calculate level based on volunteer hours (for display)
function calculateLevel($hours) {
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

// Get manager's stats
$manager_stats = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total_hours, COUNT(*) as total_volunteers FROM family WHERE managers_email = ?");
$manager_stats->bind_param("s", $manager_email);
$manager_stats->execute();
$stats_result = $manager_stats->get_result();
$org_stats = $stats_result->fetch_assoc();
$manager_stats->close();

$total_org_hours = $org_stats['total_hours'] ?? 0;
$total_volunteers = $org_stats['total_volunteers'] ?? 0;

// First check if user exists in family table as a manager
$check_stmt = $conn->prepare("SELECT * FROM family WHERE managers_email = ?");
$check_stmt->bind_param("s", $manager_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$family_data = $check_result->fetch_assoc();
$check_stmt->close();

if (!$family_data) {
    die("Error: You are not registered as an organization administrator. Please contact support.");
}

// Get family ID
$family_id = $family_data['id'];

// Fetch all family members
$members = [];
$stmt = $conn->prepare("
    SELECT f.*, u.name, u.email 
    FROM family f 
    JOIN users u ON f.member_email = u.email 
    WHERE f.managers_email = ?
");
$stmt->bind_param("s", $manager_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

// Fetch all chores for the family
$chores = [];
$stmt = $conn->prepare("
    SELECT c.*, u.name as member_name 
    FROM chores c 
    JOIN users u ON c.member_email = u.email 
    WHERE c.family_id = ?
");
$stmt->bind_param("i", $family_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $chores[] = $row;
}
$stmt->close();

// Fetch pending verifications
$pending_verifications = [];
$stmt = $conn->prepare("
    SELECT cv.*, c.chore_name, c.points, u.name as member_name 
    FROM chore_verifications cv 
    JOIN chores c ON cv.chore_id = c.id 
    JOIN users u ON c.member_email = u.email 
    WHERE c.family_id = ? AND cv.status = 'pending'
    ORDER BY cv.created_at DESC
");
$stmt->bind_param("i", $family_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_verifications[] = $row;
}
$stmt->close();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_chore'])) {
        $verification_id = $_POST['verification_id'];
        $status = $_POST['status'];
        $chore_id = $_POST['chore_id'];
        
        // Update verification status
        $stmt = $conn->prepare("UPDATE chore_verifications SET status = ?, verified_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $verification_id);
        $stmt->execute();
        $stmt->close();
        
        // If approved, update points
        if ($status === 'approved') {
            $stmt = $conn->prepare("
                UPDATE family f 
                JOIN chores c ON f.member_email = c.member_email 
                SET f.points = f.points + c.points 
                WHERE c.id = ?
            ");
            $stmt->bind_param("i", $chore_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Delete the chore
        $stmt = $conn->prepare("DELETE FROM chores WHERE id = ?");
        $stmt->bind_param("i", $chore_id);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Chore verification completed!'); window.location.href='manager_dashboard.php';</script>";
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Manager Dashboard - VolunteerHub</title>
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
        
        .section {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            border: 1px solid #FFD3B5;
        }
        
        .member-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #FFD3B5;
        }
        
        .verification-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #FFD3B5;
        }
        
        .verification-image {
            max-width: 200px;
            border-radius: 10px;
            margin: 10px 0;
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
                    <li><a href="manager_dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="add_my_company.php">Add Company</a></li>
                    <li><a href="add_my_volunteer.php">Add Volunteer</a></li>
                    <li><a href="assign_chores.php">Assign Missions</a></li>
                    <li><a href="points_shop.php">Achievements</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container dashboard-container">
            <!-- Organization Stats Card -->
            <div class="section" style="background: linear-gradient(135deg, #FFD3B5, #FFAAA5); color: white; margin-bottom: 30px;">
                <h2 style="color: white; margin-bottom: 20px;">Organization Overview</h2>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?= number_format($total_org_hours) ?></div>
                        <div style="font-size: 1.1rem; opacity: 0.9;">Total Volunteer Hours</div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?= $total_volunteers ?></div>
                        <div style="font-size: 1.1rem; opacity: 0.9;">Total Volunteers</div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;"><?= $total_volunteers > 0 ? number_format($total_org_hours / $total_volunteers, 1) : 0 ?></div>
                        <div style="font-size: 1.1rem; opacity: 0.9;">Avg Hours/Volunteer</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="section" style="margin-bottom: 30px;">
                <h2>Quick Actions</h2>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="add_my_volunteer.php" class="btn btn-primary w-100" style="background: #FFD3B5; border: none; color: #333; padding: 15px;">
                            <i class="bi bi-person-plus"></i><br>Add Volunteer
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="assign_chores.php" class="btn btn-primary w-100" style="background: #FFD3B5; border: none; color: #333; padding: 15px;">
                            <i class="bi bi-list-check"></i><br>Assign Mission
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value"><?= count($members) ?></div>
                    <div class="stat-label">Volunteers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div class="stat-value"><?= count($chores) ?></div>
                    <div class="stat-label">Active Missions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-value"><?= count($pending_verifications) ?></div>
                    <div class="stat-label">Pending Verifications</div>
                </div>
            </div>

            <!-- Volunteers Section -->
            <div class="section">
                <h2>Volunteers</h2>
                <div class="row">
                    <?php foreach ($members as $member): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="member-card">
                                <h4><?= htmlspecialchars($member['name']) ?></h4>
                                <p>Email: <?= htmlspecialchars($member['email']) ?></p>
                                <p>Volunteer Hours: <?= htmlspecialchars($member['points']) ?></p>
                                <p>Level: <?= calculateLevel($member['points']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pending Verifications Section -->
            <div class="section">
                <h2>Pending Verifications</h2>
                <?php if (empty($pending_verifications)): ?>
                    <p class="text-center">No pending verifications.</p>
                <?php else: ?>
                    <?php foreach ($pending_verifications as $verification): ?>
                        <div class="verification-card">
                            <h4><?= htmlspecialchars($verification['chore_name']) ?></h4>
                            <p>Volunteer: <?= htmlspecialchars($verification['member_name']) ?></p>
                            <p>Volunteer Hours: <?= htmlspecialchars($verification['points']) ?></p>
                            <img src="<?= htmlspecialchars($verification['photo_path']) ?>" alt="Verification photo" class="verification-image">
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="verification_id" value="<?= $verification['id'] ?>">
                                <input type="hidden" name="chore_id" value="<?= $verification['chore_id'] ?>">
                                <button type="submit" name="verify_chore" value="approved" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button type="submit" name="verify_chore" value="rejected" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Active Missions Section -->
            <div class="section">
                <h2>Active Missions</h2>
                <p class="text-muted mb-3">View and manage all active volunteer missions</p>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mission</th>
                                <th>Volunteer</th>
                                <th>Volunteer Hours</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($chores)): ?>
                                <?php foreach ($chores as $chore): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($chore['chore_name']) ?></td>
                                        <td><?= htmlspecialchars($chore['member_name']) ?></td>
                                        <td><?= htmlspecialchars($chore['points']) ?></td>
                                        <td><?= date('M d, Y', strtotime($chore['due_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No active chores.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html> 
</html> 
</html> 
</html> 