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

// Note: Email typo fixes should be handled through admin panel, not in production code

// Fetch user's total volunteer hours and organization group info
$stmt = $conn->prepare("
    SELECT f.points, f.id as organization_id, f.managers_email 
    FROM family f 
    WHERE f.member_email = ?
");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $organization_info = $result->fetch_assoc();
    } else {
        $organization_info = null;
    }
    $stmt->close();
} else {
    error_log("Error preparing organization_info query: " . $conn->error);
    $organization_info = null;
}

if (!$organization_info) {
    // Show a page with directory access instead of just dying
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Organization - VolunteerHub</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .message-box {
                background: white;
                padding: 50px;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                max-width: 600px;
                text-align: center;
            }
            .message-box h1 {
                color: #333;
                font-size: 28px;
                margin-bottom: 20px;
            }
            .message-box p {
                color: #666;
                font-size: 16px;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .btn-directory {
                display: inline-block;
                padding: 15px 40px;
                background: #FFD3B5;
                color: #333;
                text-decoration: none;
                border-radius: 12px;
                font-weight: 500;
                font-size: 16px;
                transition: all 0.3s ease;
                margin: 10px;
            }
            .btn-directory:hover {
                background: #FFAAA5;
                transform: translateY(-2px);
            }
            .icon {
                font-size: 64px;
                color: #FFD3B5;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <div class="icon">👥</div>
            <h1>No Organization Assigned</h1>
            <p>You are not currently added to any organization. You can browse available volunteers and organizations to find one to join, or contact an organization administrator to add you.</p>
                    <a href="browse_directory.php" class="btn-directory">
                        <i class="bi bi-people"></i> Browse Volunteers & Organizations
                    </a>
            <a href="logout.php" class="btn-directory" style="background: #6c757d; color: white;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$user_points = $organization_info['points'] ?? 0;

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

$organization_id = $organization_info['organization_id'];
$manager_email = $organization_info['managers_email'];

// Get filter (organization or global)
$leaderboard_filter = isset($_GET['leaderboard']) ? $_GET['leaderboard'] : 'organization';

// Get organization leaderboard
$leaderboard_org = [];
$user_rank_org = null;
if (isset($manager_email) && !empty($manager_email)) {
    $leaderboard_stmt = $conn->prepare("
    SELECT f.member_name, f.member_email, f.points as hours,
           COUNT(DISTINCT CASE WHEN c.status = 'completed' OR c.status = 'redeemed' THEN c.id END) as completed_missions
    FROM family f
    LEFT JOIN chores c ON f.member_email = c.member_email
    WHERE f.managers_email = ?
    GROUP BY f.id, f.member_name, f.member_email, f.points
    ORDER BY f.points DESC, completed_missions DESC
    LIMIT 10
");
    if ($leaderboard_stmt) {
        $leaderboard_stmt->bind_param("s", $manager_email);
        if ($leaderboard_stmt->execute()) {
            $leaderboard_result = $leaderboard_stmt->get_result();
            $rank = 1;
            while ($row = $leaderboard_result->fetch_assoc()) {
                $row['level'] = calculateLevel($row['hours']);
                $row['rank'] = $rank;
                if ($row['member_email'] === $member_email) {
                    $user_rank_org = $rank;
                }
                $leaderboard_org[] = $row;
                $rank++;
            }
        }
        $leaderboard_stmt->close();
    } else {
        error_log("Error preparing leaderboard query: " . $conn->error);
    }
}

// Get global leaderboard
$leaderboard_global = [];
$user_rank_global = null;
    $global_stmt = $conn->prepare("
    SELECT f.member_name, f.member_email, f.points as hours,
           COUNT(DISTINCT CASE WHEN c.status = 'completed' OR c.status = 'redeemed' THEN c.id END) as completed_missions
    FROM family f
    LEFT JOIN chores c ON f.member_email = c.member_email
    GROUP BY f.id, f.member_name, f.member_email, f.points
    ORDER BY f.points DESC, completed_missions DESC
    LIMIT 50
");
if ($global_stmt) {
    if ($global_stmt->execute()) {
        $global_result = $global_stmt->get_result();
        $rank = 1;
        while ($row = $global_result->fetch_assoc()) {
            $row['level'] = calculateLevel($row['hours']);
            $row['rank'] = $rank;
            if ($row['member_email'] === $member_email) {
                $user_rank_global = $rank;
            }
            $leaderboard_global[] = $row;
            $rank++;
        }
    }
    $global_stmt->close();
} else {
    error_log("Error preparing global_leaderboard query: " . $conn->error);
}

// Set active leaderboard
$leaderboard = $leaderboard_filter === 'global' ? $leaderboard_global : $leaderboard_org;
$user_rank = $leaderboard_filter === 'global' ? $user_rank_global : $user_rank_org;

// Get organization stats
$org_stats = ['total_hours' => 0, 'total_volunteers' => 0];
if (isset($manager_email) && !empty($manager_email)) {
    $org_stats_stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total_hours, COUNT(*) as total_volunteers FROM family WHERE managers_email = ?");
    if ($org_stats_stmt) {
        $org_stats_stmt->bind_param("s", $manager_email);
        if ($org_stats_stmt->execute()) {
            $org_stats_result = $org_stats_stmt->get_result();
            $org_stats = $org_stats_result->fetch_assoc() ?? $org_stats;
        }
        $org_stats_stmt->close();
    } else {
        error_log("Error preparing org_stats query: " . $conn->error);
    }
}

$total_org_hours = $org_stats['total_hours'] ?? 0;
$total_volunteers = $org_stats['total_volunteers'] ?? 0;

// Get mission history for AI
$history_stmt = $conn->prepare("
    SELECT c.chore_name, c.points, cv.status
    FROM chores c
    LEFT JOIN chore_verifications cv ON c.id = cv.chore_id
    WHERE c.member_email = ?
    ORDER BY cv.verified_at DESC
    LIMIT 10
");
if ($history_stmt) {
    $history_stmt->bind_param("s", $member_email);
    if ($history_stmt->execute()) {
        $history_result = $history_stmt->get_result();
        $mission_history = [];
        while ($row = $history_result->fetch_assoc()) {
            $mission_history[] = $row;
        }
    } else {
        $mission_history = [];
    }
    $history_stmt->close();
} else {
    error_log("Error preparing history_stmt query: " . $conn->error);
    $mission_history = [];
}

// Fetch ALL missions (pending and completed) for Mission Discovery Map
// Get pending missions from chores table
$pending_missions = [];
$stmt = $conn->prepare("
    SELECT c.id, c.chore_name, c.points, 'pending' as mission_status
    FROM chores c 
    WHERE c.member_email = ?
    AND NOT EXISTS (SELECT 1 FROM chore_verifications cv WHERE cv.chore_id = c.id AND cv.status = 'approved' AND cv.member_email = ?)
    ORDER BY c.id DESC
");
if ($stmt) {
    $stmt->bind_param("ss", $member_email, $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pending_missions[] = $row;
        }
    }
    $stmt->close();
} else {
    error_log("Error preparing pending_missions query: " . $conn->error);
    $pending_missions = [];
}

// Get completed missions from chores table using status
$completed_missions_for_map = [];
$stmt = $conn->prepare("
    SELECT c.id, 
           c.chore_name, 
           c.points, 
           'completed' as mission_status
    FROM chores c
    WHERE c.member_email = ? AND (c.status = 'completed' OR c.status = 'redeemed')
    ORDER BY c.created_at DESC
");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $completed_missions_for_map[] = $row;
        }
    }
    $stmt->close();
} else {
    error_log("Error preparing completed_missions_for_map query: " . $conn->error);
    $completed_missions_for_map = [];
}

// Combine both
$all_missions = array_merge($pending_missions, $completed_missions_for_map);

// Fetch assigned missions (only pending ones) for the chores list
$chores = [];
foreach ($all_missions as $mission) {
    if ($mission['mission_status'] === 'pending') {
        $chores[] = $mission;
    }
}

// Fetch completed missions - use status field from chores table
// Show both redeemed and non-redeemed completed missions
$completed_chores = [];
$stmt = $conn->prepare("
    SELECT c.id as chore_id,
           c.chore_name, 
           c.points,
           c.created_at,
           c.status,
           c.redeemed,
           cv.id as verification_id,
           cv.photo_path,
           cv.verified_at,
           cv.status as verification_status
    FROM chores c
    LEFT JOIN chore_verifications cv ON c.id = cv.chore_id AND cv.member_email = c.member_email AND cv.status = 'approved'
    WHERE c.member_email = ? AND (c.status = 'completed' OR c.status = 'redeemed')
    ORDER BY c.redeemed ASC, cv.verified_at DESC, c.created_at DESC
");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $completed_chores[] = $row;
        }
    }
    $stmt->close();
} else {
    // If prepare failed, log error but continue
    error_log("Error preparing completed_chores query: " . $conn->error);
    $completed_chores = [];
}

// Fetch achievements assigned to the user
$rewards = [];
$stmt = $conn->prepare("
    SELECT r.name, r.points_required, r.image 
    FROM rewards r 
    JOIN assigned_rewards ar ON r.id = ar.reward_id
    WHERE ar.member_email = ? AND ar.status = 'pending'
");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rewards[] = $row;
        }
    }
    $stmt->close();
} else {
    error_log("Error preparing rewards query: " . $conn->error);
    $rewards = [];
}

// Get performance data for dashboard (last 30 days activity)
// Query from chores table using status and created_at
$activity_stmt = $conn->prepare("
    SELECT DATE(c.created_at) as activity_date, COUNT(*) as missions_completed
    FROM chores c
    WHERE c.member_email = ? 
    AND (c.status = 'completed' OR c.status = 'redeemed')
    AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(c.created_at)
    ORDER BY activity_date DESC
");
if ($activity_stmt) {
    $activity_stmt->bind_param("s", $member_email);
    if ($activity_stmt->execute()) {
        $activity_result = $activity_stmt->get_result();
        $activity_data = [];
        while ($row = $activity_result->fetch_assoc()) {
            $activity_data[] = $row;
        }
    }
    $activity_stmt->close();
} else {
    $activity_data = [];
}

// Get count of missions completed in last 30 days
$recent_activity_stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM chores c
    WHERE c.member_email = ? 
    AND (c.status = 'completed' OR c.status = 'redeemed')
    AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
if ($recent_activity_stmt) {
    $recent_activity_stmt->bind_param("s", $member_email);
    if ($recent_activity_stmt->execute()) {
        $recent_result = $recent_activity_stmt->get_result();
        $recent_row = $recent_result->fetch_assoc();
        $recent_activity = (int)($recent_row['count'] ?? 0);
    } else {
        $recent_activity = 0;
    }
    $recent_activity_stmt->close();
} else {
    $recent_activity = 0;
}

// Get engagement score (based on recent activity)
$total_missions = count($completed_chores);
$engagement_score = min(100, ($recent_activity * 10) + ($total_missions * 2));

// Get GLOBAL activity heatmap data (all volunteers in organization, not just this user)
// Query from chores table using status
$heatmap_stmt = $conn->prepare("
    SELECT DAYNAME(c.created_at) as day_name, HOUR(c.created_at) as hour, COUNT(*) as activity_count
    FROM chores c
    JOIN family f ON c.member_email = f.member_email
    WHERE f.managers_email = ? 
    AND (c.status = 'completed' OR c.status = 'redeemed')
    AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DAYNAME(c.created_at), HOUR(c.created_at)
    ORDER BY day_name, hour
");
if ($heatmap_stmt) {
    $heatmap_stmt->bind_param("s", $manager_email);
    if ($heatmap_stmt->execute()) {
        $heatmap_result = $heatmap_stmt->get_result();
        $heatmap_data = [];
        while ($row = $heatmap_result->fetch_assoc()) {
            $heatmap_data[] = $row;
        }
    }
    $heatmap_stmt->close();
} else {
    $heatmap_data = [];
}

// Fetch announcements from organization owner
$announcements_stmt = $conn->prepare("
    SELECT a.* 
    FROM announcements a
    JOIN family f ON a.manager_email = f.managers_email
    WHERE f.member_email = ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
if ($announcements_stmt) {
    $announcements_stmt->bind_param("s", $member_email);
    if ($announcements_stmt->execute()) {
        $announcements_result = $announcements_stmt->get_result();
        $announcements = [];
        while ($row = $announcements_result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
    $announcements_stmt->close();
} else {
    $announcements = [];
}

// Fetch resources from organization owner
$resources_stmt = $conn->prepare("
    SELECT r.* 
    FROM resources r
    JOIN family f ON r.manager_email = f.managers_email
    WHERE f.member_email = ?
    ORDER BY r.created_at DESC
");
if ($resources_stmt) {
    $resources_stmt->bind_param("s", $member_email);
    if ($resources_stmt->execute()) {
        $resources_result = $resources_stmt->get_result();
        $available_resources = [];
        while ($row = $resources_result->fetch_assoc()) {
            $available_resources[] = $row;
        }
    }
    $resources_stmt->close();
} else {
    $available_resources = [];
}

// Handle sending message to organization owner
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text']);
    
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (!empty($message_text) && !empty($manager_email)) {
        $stmt = $conn->prepare("INSERT INTO volunteer_messages (volunteer_email, manager_email, message, status) VALUES (?, ?, ?, 'unread')");
        if ($stmt) {
            $stmt->bind_param("sss", $member_email, $manager_email, $message_text);
            if ($stmt->execute()) {
                $message_sent = true;
                
                // If AJAX request, return JSON and exit
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
                    $stmt->close();
                    exit();
                }
            }
            $stmt->close();
        }
    } else {
        // If AJAX request and validation failed, return error
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please enter a message.']);
            exit();
        }
    }
}

// Handle claiming volunteer hours
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_points'])) {
    // Calculate total hours from completed but not yet redeemed missions
    $stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) FROM chores WHERE member_email = ? AND status = 'completed' AND redeemed = 0");
    if ($stmt) {
        $stmt->bind_param("s", $member_email);
        if ($stmt->execute()) {
            $stmt->bind_result($total_points);
            $stmt->fetch();
        } else {
            $total_points = 0;
        }
        $stmt->close();
    } else {
        error_log("Error preparing total_points query: " . $conn->error);
        $total_points = 0;
    }

    if ($total_points > 0) {
        // Update volunteer hours in organization table
        $stmt = $conn->prepare("UPDATE family SET points = points + ? WHERE member_email = ?");
        if ($stmt) {
            $stmt->bind_param("is", $total_points, $member_email);
            $stmt->execute();
            $stmt->close();
        }

        // Mark completed chores as redeemed (don't delete them)
        $stmt = $conn->prepare("UPDATE chores SET redeemed = 1, status = 'redeemed' WHERE member_email = ? AND status = 'completed' AND redeemed = 0");
        if ($stmt) {
            $stmt->bind_param("s", $member_email);
            $stmt->execute();
            $stmt->close();
        }

        echo "<script>alert('Volunteer hours claimed successfully!'); window.location.href='member.php';</script>";
        exit();
    } else {
        echo "<script>alert('No missions to claim.');</script>";
    }
}

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Volunteer Dashboard - VolunteerHub</title>
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
}

.modal.show {
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
#volunteerForm {
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

.member-dashboard {
    padding: 60px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.points-card {
    background: linear-gradient(135deg, #FFD3B5, #FFAAA5);
    color: white;
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(255, 163, 165, 0.2);
    transition: transform 0.3s ease;
}

.points-card:hover {
    transform: translateY(-5px);
}

.points-display {
    font-size: 3.5rem;
    font-weight: bold;
    margin: 20px 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
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
    transition: all 0.3s ease;
    border: 1px solid #FFD3B5;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(255, 163, 165, 0.15);
}

.stat-icon {
    font-size: 2.5rem;
    color: #FFD3B5;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
    margin: 10px 0;
}

.stat-label {
    color: #666;
    font-size: 1.1rem;
    font-weight: 500;
}

.chores-section {
    background: white;
    padding: 35px;
    border-radius: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-bottom: 40px;
    border: 1px solid #FFD3B5;
}

.chores-section h2 {
    color: #333;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #FFD3B5;
    font-weight: 600;
    font-size: 1.8rem;
}

.table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.table th {
    background: #FFD3B5;
    color: white;
    padding: 15px 20px;
    font-weight: 500;
    border: none;
}

.table td {
    padding: 15px 20px;
    vertical-align: middle;
    background: #f8f9fa;
    border: none;
}

.table tr {
    transition: transform 0.3s ease;
}

.table tr:hover {
    transform: translateX(5px);
}

.btn-verify {
    background: #FFD3B5;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(255, 163, 165, 0.2);
}

.btn-verify:hover {
    background: #FFAAA5;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 163, 165, 0.3);
}

.completed-chore-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
    border: 1px solid #FFD3B5;
}

.completed-chore-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(255, 163, 165, 0.15);
}

.completed-chore-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-bottom: 2px solid #FFD3B5;
}

.completed-chore-details {
    padding: 25px;
    background: white;
}

.completed-chore-details h4 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.3rem;
    font-weight: 600;
}

.completed-chore-details p {
    color: #666;
    margin-bottom: 12px;
    font-size: 1rem;
    line-height: 1.5;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    margin-top: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.status-approved {
    background-color: #28a745;
    color: white;
}

.status-rejected {
    background-color: #dc3545;
    color: white;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.btn-verify:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.7;
}

.btn-verify:disabled:hover {
    transform: none;
    box-shadow: none;
}

.modal-content {
    background: white;
    padding: 35px;
    border-radius: 20px;
    max-width: 800px;
    width: 95%;
}

.camera-container {
    margin: 25px auto;
}

#cameraFeed, #photoPreview {
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.camera-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
}

.camera-buttons button {
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.notification {
    position: fixed;
    top: 25px;
    right: 25px;
    padding: 15px 30px;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
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
        <li><a href="member.php" class="active">Organization Center</a></li>
        <li><a href="games.php">Engagement Zone</a></li>
        <li><a href="profile.php">My Profile</a></li>
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


    <section id="hero" class="hero section dark-background">

  

      <div id="hero-carousel" class="carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">

        <div class="container position-relative">

          <div class="carousel-item active">
            <div class="carousel-container">
              <h2>Welcome to VolunteerHub</h2>
              <p>Discover a powerful platform to manage volunteer missions, track hours, and recognize achievements. Organize missions, track volunteer hours, and grow community impact. Build stronger communities through organized volunteer work. VolunteerHub empowers organizations and volunteers.</p>
              <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                <a href="#about" class="btn-get-started">Read More</a>
                <a href="browse_directory.php" class="btn-get-started" style="background: rgba(255,255,255,0.2); border: 2px solid white;">
                  <i class="bi bi-people"></i> Browse Directory
                </a>
              </div>
            </div>
          </div>

          <div class="carousel-item">
            <div class="carousel-container">
              <h2>Our Mission</h2>
              <p>Empower organizations to effectively manage volunteers and missions. Track volunteer hours, assign missions, and recognize achievements. Build a thriving volunteer community that makes a real impact. Start organizing today with VolunteerHub!</p>
              <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                <a href="#about" class="btn-get-started">Read More</a>
                <a href="browse_directory.php" class="btn-get-started" style="background: rgba(255,255,255,0.2); border: 2px solid white;">
                  <i class="bi bi-people"></i> Browse Directory
                </a>
              </div>
            </div>
          </div>

          <div class="carousel-item">
            <div class="carousel-container">
              <h2>Our vision</h2>
              <p>At VolunteerHub, we aim to bring volunteer organizations together through seamless management and shared achievements. By simplifying missions and fostering teamwork, we empower organizations to thrive and create lasting community impact together.
              </p>
              <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                <a href="#about" class="btn-get-started">Read More</a>
                <a href="browse_directory.php" class="btn-get-started" style="background: rgba(255,255,255,0.2); border: 2px solid white;">
                  <i class="bi bi-people"></i> Browse Directory
                </a>
              </div>
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
        <div class="section-title text-center mb-5">
          <h2>Organization Features</h2>
          <p>Discover how VolunteerHub makes volunteer management effective and rewarding</p>
        </div>
    
        <div class="row gy-4">
          <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-people-fill"></i>
              </div>
              <div class="feature-content">
                <h3>Organization Hub</h3>
                <p>Create your volunteer organization, add volunteers, and start your journey together. Our intuitive interface makes volunteer management a breeze.</p>
                <div class="feature-stats">
                  <div class="stat">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Connected</span>
                  </div>
                  <div class="stat">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Organized</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
    
          <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-check2-square"></i>
              </div>
              <div class="feature-content">
                <h3>Smart Tasks</h3>
                <p>Assign and track missions with our smart system. Watch as your volunteers grow more engaged and your organization makes greater impact every day.</p>
                <div class="feature-stats">
                  <div class="stat">
                    <span class="stat-number">Easy</span>
                    <span class="stat-label">Assignment</span>
                  </div>
                  <div class="stat">
                    <span class="stat-number">Fun</span>
                    <span class="stat-label">Completion</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
    
          <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-graph-up-arrow"></i>
              </div>
              <div class="feature-content">
                <h3>Progress Dashboard</h3>
                <p>Track everyone's progress with beautiful charts and insights. Celebrate achievements and encourage growth together.</p>
                <div class="feature-stats">
                  <div class="stat">
                    <span class="stat-number">Real</span>
                    <span class="stat-label">Time Updates</span>
                  </div>
                  <div class="stat">
                    <span class="stat-number">Smart</span>
                    <span class="stat-label">Analytics</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
    
          <div class="col-lg-6 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="bi bi-gift-fill"></i>
              </div>
              <div class="feature-content">
                <h3>Reward Center</h3>
                <p>Turn missions into achievements! Our volunteer hours system makes earning recognition and badges more rewarding than ever.</p>
                <div class="feature-stats">
                  <div class="stat">
                    <span class="stat-number">Instant</span>
                    <span class="stat-label">Achievements</span>
                  </div>
                  <div class="stat">
                    <span class="stat-number">Fun</span>
                    <span class="stat-label">Redemption</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
            </div>
          </div>
    
      <style>
        .featured-services {
          padding: 60px 0;
          background: #f8f9fa;
        }

        .section-title {
          margin-bottom: 50px;
        }

        .section-title h2 {
          color: #333;
          font-size: 2.5rem;
          font-weight: 700;
          margin-bottom: 15px;
        }

        .section-title p {
          color: #666;
          font-size: 1.1rem;
        }

        .feature-card {
          background: white;
          border-radius: 20px;
          padding: 30px;
          height: 100%;
          transition: all 0.3s ease;
          position: relative;
          overflow: hidden;
          box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .feature-card:hover {
          transform: translateY(-10px);
          box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .feature-card::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 5px;
          background: linear-gradient(90deg, #FFD3B5, #FFAAA5);
        }

        .feature-icon {
          font-size: 2.5rem;
          color: #FFD3B5;
          margin-bottom: 20px;
        }

        .feature-content h3 {
          color: #333;
          font-size: 1.5rem;
          font-weight: 600;
          margin-bottom: 15px;
        }

        .feature-content p {
          color: #666;
          font-size: 1rem;
          line-height: 1.6;
          margin-bottom: 20px;
        }

        .feature-stats {
          display: flex;
          gap: 20px;
          margin-top: 20px;
          padding-top: 20px;
          border-top: 1px solid #eee;
        }

        .stat {
          text-align: center;
          flex: 1;
        }

        .stat-number {
          display: block;
          font-size: 1.2rem;
          font-weight: 700;
          color: #FFD3B5;
          margin-bottom: 5px;
        }

        .stat-label {
          font-size: 0.9rem;
          color: #666;
        }

        @media (max-width: 768px) {
          .feature-card {
            margin-bottom: 30px;
          }
          
          .section-title h2 {
            font-size: 2rem;
          }
        }
      </style>
    </section>
    
    <section class="member-dashboard">
        <div class="container">
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
                    <div class="stat-label">Approved Missions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="stat-value"><?= $user_points ?></div>
                    <div class="stat-label">Total Points</div>
                </div>
            </div>
    
            <!-- Completed Missions Section -->
            <div class="chores-section">
                <h2>Completed Missions (<?= count($completed_chores) ?>)</h2>
                <div class="row">
                    <?php if (empty($completed_chores)): ?>
                        <div class="col-12 text-center">
                            <p>No completed missions yet. Complete a mission and have it approved by your organization administrator to see it here!</p>
                            <p style="color: #999; font-size: 0.9rem; margin-top: 10px;">Make sure you submit a photo for verification and wait for approval.</p>
                        </div>
                    <?php else: 
                        foreach ($completed_chores as $chore): 
                            $chore_name = !empty($chore['chore_name']) ? $chore['chore_name'] : 'Completed Mission';
                            $points = !empty($chore['points']) ? $chore['points'] : 0;
                            $verified_date = !empty($chore['verified_at']) ? $chore['verified_at'] : '';
                            $is_redeemed = !empty($chore['redeemed']) || $chore['status'] == 'redeemed';
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="completed-chore-card" style="border-left: <?= $is_redeemed ? '4px solid #28a745' : '4px solid #FFD3B5' ?>;">
                                    <?php if (!empty($chore['photo_path'])): ?>
                                        <img src="<?= htmlspecialchars($chore['photo_path']) ?>" alt="Completed mission" class="completed-chore-image">
                                    <?php endif; ?>
                                    <div class="completed-chore-details">
                                        <h4><?= htmlspecialchars($chore_name) ?></h4>
                                        <p>Hours Earned: <?= htmlspecialchars($points) ?></p>
                                        <?php if ($verified_date): ?>
                                            <p>Completed: <?= date('M d, Y H:i', strtotime($verified_date)) ?></p>
                                        <?php endif; ?>
                                        <p class="status-badge <?= $is_redeemed ? 'status-approved' : 'status-pending' ?>" style="background: <?= $is_redeemed ? '#28a745' : '#FFD3B5' ?>; color: <?= $is_redeemed ? 'white' : '#333' ?>;">
                                            <?= $is_redeemed ? '✓ Redeemed' : 'Pending Redemption' ?>
                                        </p>
                                        <button type="button" class="btn btn-sm" style="background: #5c4d3c; color: white; margin-top: 10px; width: 100%;" onclick="toggleComments(<?= $chore['chore_id'] ?>)">
                                            <i class="bi bi-chat"></i> View Comments
                                        </button>
                                    </div>
                                </div>
                                <div id="comments-container-completed-<?= $chore['chore_id'] ?>" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                                    <h6 style="color: #5c4d3c; margin-bottom: 10px;"><i class="bi bi-chat-dots"></i> Comments</h6>
                                    <div id="comments-list-completed-<?= $chore['chore_id'] ?>" style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
                                        <p style="color: #666; text-align: center; font-size: 0.9rem;">Loading comments...</p>
                                    </div>
                                    <textarea id="comment-input-completed-<?= $chore['chore_id'] ?>" placeholder="Write a comment..." style="width: 100%; padding: 8px; border: 2px solid #e0e0e0; border-radius: 5px; resize: vertical; min-height: 60px; margin-bottom: 8px; font-size: 0.9rem;"></textarea>
                                    <button type="button" class="btn btn-sm" style="background: #5c4d3c; color: white; width: 100%;" onclick="submitComment(<?= $chore['chore_id'] ?>, 'completed')">
                                        <i class="bi bi-send"></i> Post Comment
                                    </button>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>

            <div class="chores-section">
                <h2>Your Assigned Missions</h2>
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
                            <tr data-chore-id="<?= $chore['id'] ?>">
                                <td><?= htmlspecialchars($chore['chore_name']) ?></td>
                                <td><?= htmlspecialchars($chore['points']) ?> hours</td>
                                <td>
                                    <button type="button" class="btn btn-verify" onclick="openCamera(<?= $chore['id'] ?>)">
                                        <i class="bi bi-camera"></i> Verify Completion
                                    </button>
                                    <button type="button" class="btn btn-sm" style="background: #5c4d3c; color: white; margin-left: 5px;" onclick="toggleComments(<?= $chore['id'] ?>)">
                                        <i class="bi bi-chat"></i> Comments
                                    </button>
                                </td>
                            </tr>
                            <tr id="comments-row-<?= $chore['id'] ?>" style="display: none;">
                                <td colspan="3">
                                    <div id="comments-container-<?= $chore['id'] ?>" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 10px;">
                                        <h5 style="color: #5c4d3c; margin-bottom: 15px;"><i class="bi bi-chat-dots"></i> Mission Comments</h5>
                                        <div id="comments-list-<?= $chore['id'] ?>" style="max-height: 300px; overflow-y: auto; margin-bottom: 15px;">
                                            <p style="color: #666; text-align: center;">Loading comments...</p>
                                        </div>
                                        <div style="border-top: 1px solid #e0e0e0; padding-top: 15px;">
                                            <textarea id="comment-input-<?= $chore['id'] ?>" placeholder="Write a comment..." style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; resize: vertical; min-height: 80px; margin-bottom: 10px;"></textarea>
                                            <button type="button" class="btn" style="background: #5c4d3c; color: white;" onclick="submitComment(<?= $chore['id'] ?>)">
                                                <i class="bi bi-send"></i> Post Comment
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No pending missions.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Leaderboard Section -->
            <div class="chores-section" id="leaderboard-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                    <h2 style="color: #333; margin: 0;"><i class="bi bi-trophy"></i> Volunteer Leaderboard</h2>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" id="btn-org-leaderboard" onclick="loadLeaderboard('organization')" style="padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0; font-weight: 500; transition: all 0.3s ease; cursor: pointer; background: #5c4d3c; color: white;">
                            <i class="bi bi-people"></i> Organization
                        </button>
                        <button type="button" id="btn-global-leaderboard" onclick="loadLeaderboard('global')" style="padding: 8px 20px; border-radius: 20px; border: 2px solid #e0e0e0; font-weight: 500; transition: all 0.3s ease; cursor: pointer; background: #f8f9fa; color: #333;">
                            <i class="bi bi-globe"></i> Global
                        </button>
                    </div>
                </div>
                <p id="leaderboard-description" style="color: #666; margin-bottom: 20px;">
                    See how you rank among your organization's volunteers
                </p>
                <div id="leaderboard-rank-info" style="display: none; background: #FFD3B5; padding: 15px; border-radius: 10px; margin-bottom: 20px; color: #333;">
                    <i class="bi bi-star-fill"></i> <strong>Your Rank:</strong> <span id="user-rank-text"></span>
                </div>
                <div id="leaderboard-content">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="width: 80px; color: #333;">Rank</th>
                                    <th style="color: #333;">Volunteer</th>
                                    <th style="color: #333;">Hours</th>
                                    <th style="color: #333;">Level</th>
                                    <th style="color: #333;">Missions</th>
                                </tr>
                            </thead>
                            <tbody id="leaderboard-tbody">
                                <?php foreach ($leaderboard as $volunteer): ?>
                                    <tr style="<?= $volunteer['member_email'] === $member_email ? 'background: #fff3cd;' : '' ?>">
                                        <td>
                                            <?php if ($volunteer['rank'] <= 3): ?>
                                                <span style="font-size: 1.5rem;">
                                                    <?php if ($volunteer['rank'] == 1): ?>🥇
                                                    <?php elseif ($volunteer['rank'] == 2): ?>🥈
                                                    <?php else: ?>🥉<?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <strong style="color: #333;">#<?= $volunteer['rank'] ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: #333;"><?= htmlspecialchars($volunteer['member_name'] ?? $volunteer['member_email']) ?></strong>
                                            <?php if ($volunteer['member_email'] === $member_email): ?>
                                                <span style="background: #FFD3B5; color: #333; padding: 3px 8px; border-radius: 10px; font-size: 0.85rem; margin-left: 8px;">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span style="background: #FFD3B5; color: #333; padding: 5px 12px; border-radius: 15px; font-weight: 500;"><?= number_format($volunteer['hours']) ?> hrs</span></td>
                                        <td><span style="background: #5c4d3c; color: white; padding: 5px 12px; border-radius: 15px; font-weight: 500;">Level <?= $volunteer['level'] ?></span></td>
                                        <td style="color: #666;"><?= $volunteer['completed_missions'] ?> completed</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Level Progress Section -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-graph-up"></i> Level Progress</h2>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 style="color: #5c4d3c; margin-bottom: 20px; font-size: 1.8rem;">Level <?= $user_level ?></h3>
                        <p style="color: #666; margin-bottom: 15px; font-size: 1.1rem;">
                            <strong style="color: #333;"><?= number_format($user_points) ?></strong> volunteer hours earned
                        </p>
                        <div style="background: #f0f0f0; border-radius: 10px; padding: 3px; margin-bottom: 15px;">
                            <div style="background: linear-gradient(90deg, #FFD3B5, #FFAAA5); height: 30px; border-radius: 8px; width: <?= $progress_percent ?>%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem; transition: width 0.5s ease; min-width: 50px;">
                                <?= number_format($progress_percent, 1) ?>%
                            </div>
                        </div>
                        <p style="color: #666;">
                            <i class="bi bi-arrow-up-circle" style="color: #FFD3B5;"></i> 
                            <strong style="color: #333;"><?= number_format($hours_needed) ?></strong> more hours to reach Level <?= $user_level + 1 ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-center">
                        <div style="font-size: 6rem; color: #FFD3B5; font-weight: bold; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
                            <?= $user_level ?>
                        </div>
                        <p style="color: #666; font-size: 1.2rem; font-weight: 500;">Current Level</p>
                    </div>
                </div>
            </div>

            <!-- AI Mission Suggestions Section -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-lightbulb"></i> AI Mission Suggestions</h2>
                <p style="color: #666; margin-bottom: 20px;">Get personalized mission suggestions based on your volunteer history</p>
                <form id="ai-suggestion-form">
                    <div class="mb-3">
                        <label for="ai_context" class="form-label" style="color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">What kind of missions interest you?</label>
                        <textarea id="ai_context" name="context" class="form-control" rows="3" 
                                  placeholder="e.g., I'm interested in environmental work, or suggest missions based on my history..." 
                                  style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px; color: #333; font-family: 'Poppins', sans-serif;"></textarea>
                    </div>
                    <button type="button" onclick="getAISuggestions()" class="btn" style="background: #5c4d3c; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="bi bi-magic"></i> Get AI Suggestions
                    </button>
                </form>
                <div id="ai-suggestions-container" class="mt-4" style="display: none;">
                    <h4 style="color: #5c4d3c; margin-bottom: 20px; font-weight: 600;">Suggested Missions</h4>
                    <div id="ai-suggestions-list"></div>
                </div>
            </div>

            <!-- Send Message to Organization Owner Section -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-envelope"></i> Contact Organization Administrator</h2>
                <p style="color: #666; margin-bottom: 20px;">Send a message to your organization administrator. They will see it in their dashboard.</p>
                <div id="message-alert" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px;"></div>
                <form id="message-form" onsubmit="return sendMessage(event)">
                    <div class="mb-3">
                        <label for="message_text" class="form-label" style="color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Your Message:</label>
                        <textarea id="message_text" name="message_text" class="form-control" rows="4" 
                                  placeholder="Type your message here..." required
                                  style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 12px; color: #333; font-family: 'Poppins', sans-serif; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" id="send-message-btn" class="btn" style="background: #5c4d3c; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="bi bi-send"></i> Send Message
                    </button>
                </form>
            </div>

            <!-- Announcements Section -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-megaphone"></i> Announcements</h2>
                <p style="color: #666; margin-bottom: 20px;">Important messages from your organization administrator</p>
                
                <div id="announcements-list" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <?php if (empty($announcements)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">No announcements yet.</p>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #5c4d3c; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <h4 style="color: #5c4d3c; margin-bottom: 10px; font-size: 1.2rem;">
                                    <?= htmlspecialchars($announcement['title']) ?>
                                </h4>
                                <p style="color: #666; margin-bottom: 10px; line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($announcement['message'])) ?>
                                </p>
                                <div style="color: #999; font-size: 0.85rem;">
                                    <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($announcement['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feature 1: Volunteer Performance Dashboard -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-graph-up-arrow"></i> Your Performance Dashboard</h2>
                <p style="color: #666; margin-bottom: 20px;">Track your volunteer performance, trends, and engagement scores</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #5c4d3c, #7a6652); color: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 10px;">Engagement Score</div>
                        <div style="font-size: 3rem; font-weight: bold; margin-bottom: 10px;"><?= $engagement_score ?></div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">out of 100</div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border-left: 4px solid #5c4d3c;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Missions (Last 30 Days)</div>
                        <div style="font-size: 2.5rem; font-weight: bold; color: #5c4d3c; margin-bottom: 10px;"><?= $recent_activity ?></div>
                        <div style="color: #999; font-size: 0.8rem;">recent completions</div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border-left: 4px solid #FFD3B5;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Missions</div>
                        <div style="font-size: 2.5rem; font-weight: bold; color: #5c4d3c; margin-bottom: 10px;"><?= $total_missions ?></div>
                        <div style="color: #999; font-size: 0.8rem;">all time</div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; border-left: 4px solid #28a745;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Average per Week</div>
                        <div style="font-size: 2.5rem; font-weight: bold; color: #5c4d3c; margin-bottom: 10px;"><?= $recent_activity > 0 ? round($recent_activity / 4.3, 1) : 0 ?></div>
                        <div style="color: #999; font-size: 0.8rem;">missions/week</div>
                    </div>
                </div>
                
                <!-- Activity Trend Chart -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                    <h4 style="color: #5c4d3c; margin-bottom: 15px;">Activity Trend (Last 30 Days)</h4>
                    <div id="activity-chart" style="height: 200px; background: white; border-radius: 8px; padding: 15px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Feature 8: Global Activity Heatmap -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-calendar-heatmap"></i> Global Activity Heatmap</h2>
                <p style="color: #666; margin-bottom: 20px;">Organization-wide volunteer activity patterns by day and time (Last 30 Days)</p>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <div id="heatmap-container" style="display: grid; grid-template-columns: auto repeat(24, 1fr); gap: 3px; margin-bottom: 20px; font-size: 0.7rem;">
                        <?php
                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        $hours = range(0, 23);
                        $heatmap_matrix = [];
                        
                        // Initialize matrix
                        foreach ($days as $day) {
                            foreach ($hours as $hour) {
                                $heatmap_matrix[$day][$hour] = 0;
                            }
                        }
                        
                        // Populate matrix from database
                        foreach ($heatmap_data as $data) {
                            $day = $data['day_name'];
                            $hour = (int)$data['hour'];
                            if (isset($heatmap_matrix[$day][$hour])) {
                                $heatmap_matrix[$day][$hour] += (int)$data['activity_count'];
                            }
                        }
                        
                        // Find max activity for intensity calculation
                        $max_activity = 0;
                        foreach ($heatmap_matrix as $day_data) {
                            foreach ($day_data as $count) {
                                if ($count > $max_activity) $max_activity = $count;
                            }
                        }
                        ?>
                        <!-- Empty corner cell -->
                        <div></div>
                        <!-- Hour headers -->
                        <?php foreach ($hours as $hour): ?>
                            <div style="text-align: center; font-size: 0.65rem; color: #666; padding: 3px; font-weight: 500; writing-mode: vertical-rl; text-orientation: mixed;">
                                <?= $hour ?>h
                            </div>
                        <?php endforeach; ?>
                        <!-- Day rows with hour cells -->
                        <?php foreach ($days as $day): ?>
                            <div style="font-size: 0.75rem; color: #666; padding: 8px 5px; font-weight: 500; text-align: right; display: flex; align-items: center;">
                                <?= substr($day, 0, 3) ?>
                            </div>
                            <?php foreach ($hours as $hour): 
                                $count = $heatmap_matrix[$day][$hour] ?? 0;
                                $intensity = $max_activity > 0 ? ($count / $max_activity) : 0;
                                $opacity = max(0.2, min(1, $intensity));
                                $bg_color = $count > 0 ? "rgba(92, 77, 60, $opacity)" : "#f0f0f0";
                                $text_color = $count > 0 ? 'white' : '#999';
                            ?>
                                <div style="background: <?= $bg_color ?>; border-radius: 2px; padding: 6px 3px; text-align: center; font-size: 0.65rem; color: <?= $text_color ?>; cursor: pointer; min-height: 25px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;" 
                                     title="<?= $day ?> <?= $hour ?>:00 - <?= $count ?> mission<?= $count != 1 ? 's' : '' ?> completed">
                                    <?= $count > 0 ? $count : '' ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                        <span style="font-size: 0.8rem; color: #666; font-weight: 500;">Less Activity</span>
                        <div style="flex: 1; display: flex; gap: 2px;">
                            <?php for ($i = 0; $i <= 10; $i++): 
                                $opacity = max(0.2, $i / 10);
                                $bg = "rgba(92, 77, 60, $opacity)";
                            ?>
                                <div style="background: <?= $bg ?>; height: 15px; flex: 1; border-radius: 2px;"></div>
                            <?php endfor; ?>
                        </div>
                        <span style="font-size: 0.8rem; color: #666; font-weight: 500;">More Activity</span>
                        <?php if ($max_activity > 0): ?>
                            <span style="font-size: 0.75rem; color: #999; margin-left: 10px;">
                                Peak: <?= $max_activity ?> missions
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Feature 5: Custom Report Builder -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-file-earmark-text"></i> Custom Report Builder</h2>
                <p style="color: #666; margin-bottom: 20px;">Create and export custom reports of your volunteer activity</p>
                
                <form id="report-builder-form" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Date Range</label>
                            <select id="report-date-range" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                                <option value="all">All time</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Include</label>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" checked name="include[]" value="missions"> Missions
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" checked name="include[]" value="hours"> Hours
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" checked name="include[]" value="rewards"> Rewards
                                </label>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Export Format</label>
                            <select id="report-format" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="html">HTML</option>
                                <option value="csv">CSV</option>
                                <option value="pdf">PDF (Print)</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" onclick="generateReport()" class="btn" style="background: #5c4d3c; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer;">
                        <i class="bi bi-download"></i> Generate & Export Report
                    </button>
                </form>
                
                <div id="report-preview" style="display: none; background: white; padding: 20px; border-radius: 10px; margin-top: 20px; border: 2px solid #e0e0e0;">
                    <h4 style="color: #5c4d3c; margin-bottom: 15px;">Report Preview</h4>
                    <div id="report-content"></div>
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button onclick="exportReport('html')" class="btn" style="background: #5c4d3c; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
                            <i class="bi bi-file-earmark"></i> Export HTML
                        </button>
                        <button onclick="exportReport('csv')" class="btn" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
                            <i class="bi bi-filetype-csv"></i> Export CSV
                        </button>
                        <button onclick="window.print()" class="btn" style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
                            <i class="bi bi-printer"></i> Print PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feature 7: Real-time Activity Feed -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-activity"></i> Recent Activity Feed</h2>
                <p style="color: #666; margin-bottom: 20px;">Live updates on your volunteer actions and mission completions</p>
                
                <div id="activity-feed" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <?php 
                    $recent_activities = array_slice($completed_chores, 0, 10);
                    if (empty($recent_activities)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">No recent activity to display.</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid #5c4d3c; display: flex; align-items: center; gap: 15px;">
                                <div style="font-size: 1.5rem; color: #5c4d3c;">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                        Mission Completed: <?= htmlspecialchars($activity['chore_name']) ?>
                                    </div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        <i class="bi bi-clock"></i> <?= isset($activity['verified_at']) ? date('M d, Y H:i', strtotime($activity['verified_at'])) : 'Recently' ?>
                                        <span style="margin-left: 15px;">
                                            <i class="bi bi-star"></i> +<?= htmlspecialchars($activity['points']) ?> hours
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feature 57: Resource Library (for volunteers) -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-folder"></i> Resource Library</h2>
                <p style="color: #666; margin-bottom: 20px;">Access documents, guides, and training materials from your organization</p>
                
                <?php if (empty($available_resources)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                        <i class="bi bi-folder-x" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                        <p style="color: #666;">No resources available yet. Your organization administrator will add resources here.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($available_resources as $resource): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #5c4d3c; height: 100%; transition: transform 0.3s ease;">
                                    <div style="font-size: 2rem; color: #5c4d3c; margin-bottom: 10px;">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <h4 style="color: #333; margin-bottom: 10px; font-size: 1.1rem;">
                                        <?= htmlspecialchars($resource['title']) ?>
                                    </h4>
                                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px; min-height: 40px;">
                                        <?= htmlspecialchars($resource['description'] ?? 'No description available') ?>
                                    </p>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                                        <span style="background: #f8f9fa; color: #5c4d3c; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 500;">
                                            <?= htmlspecialchars($resource['category']) ?>
                                        </span>
                                        <span style="color: #999; font-size: 0.8rem;">
                                            <i class="bi bi-calendar"></i> <?= date('M d, Y', strtotime($resource['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mission Discovery Map Section -->
            <div class="chores-section">
                <h2 style="color: #333;"><i class="bi bi-map"></i> Mission Discovery Map</h2>
                <p style="color: #666; margin-bottom: 20px;">Explore available missions in your organization. Click on mission markers to see details.</p>
                
                <div style="background: #f8f9fa; border-radius: 15px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                        <button onclick="filterMissions('all')" class="map-filter-btn active" data-filter="all" style="padding: 8px 16px; border-radius: 20px; border: 2px solid #5c4d3c; background: #5c4d3c; color: white; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                            <i class="bi bi-list-ul"></i> All Missions
                        </button>
                        <button onclick="filterMissions('pending')" class="map-filter-btn" data-filter="pending" style="padding: 8px 16px; border-radius: 20px; border: 2px solid #e0e0e0; background: #f8f9fa; color: #333; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                            <i class="bi bi-clock"></i> Pending
                        </button>
                        <button onclick="filterMissions('completed')" class="map-filter-btn" data-filter="completed" style="padding: 8px 16px; border-radius: 20px; border: 2px solid #e0e0e0; background: #f8f9fa; color: #333; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
                            <i class="bi bi-check-circle"></i> Completed
                        </button>
                    </div>
                    
                    <div id="mission-map-container" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 30px; min-height: 400px; position: relative; overflow: hidden;">
                        <div id="mission-map" style="position: relative; width: 100%; height: 400px; background: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 20px; padding: 20px;">
                            <!-- Mission markers will be dynamically inserted here -->
                        </div>
                        <div id="mission-map-legend" style="position: absolute; bottom: 15px; left: 15px; background: rgba(255,255,255,0.95); padding: 12px 18px; border-radius: 8px; font-size: 0.85rem; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 12px; height: 12px; background: #FFD3B5; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                                    <span>Pending</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 12px; height: 12px; background: #28a745; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                                    <span>Completed</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 12px; height: 12px; background: #5c4d3c; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                                    <span>Your Mission</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="mission-details-panel" style="display: none; background: white; border: 2px solid #5c4d3c; border-radius: 12px; padding: 20px; margin-top: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <h3 id="mission-details-name" style="color: #5c4d3c; margin: 0; font-size: 1.5rem;"></h3>
                        <button onclick="closeMissionDetails()" style="background: none; border: none; font-size: 1.5rem; color: #666; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                    </div>
                    <div id="mission-details-content"></div>
                </div>
            </div>

            <!-- Camera Modal -->
            <div id="cameraModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Verify Mission Completion</h2>
                    <p class="text-muted">Take a photo to submit your mission completion. The organization administrator will review and approve it.</p>
                    <div class="camera-container">
                        <video id="cameraFeed" autoplay playsinline></video>
                        <canvas id="photoCanvas" style="display: none;"></canvas>
                        <img id="photoPreview" style="display: none; max-width: 100%; margin-top: 10px;">
                    </div>
                    <form id="verificationForm" method="POST">
                        <input type="hidden" name="chore_id" id="choreId">
                        <input type="hidden" name="member_email" value="<?= htmlspecialchars($member_email) ?>">
                        <input type="hidden" name="photo_data" id="photoData">
                        <div class="camera-buttons">
                            <button type="button" id="takePhotoBtn" class="btn btn-primary">
                                <i class="bi bi-camera"></i> Take Photo
                            </button>
                            <button type="submit" id="uploadBtn" class="btn btn-success" style="display: none;">
                                <i class="bi bi-cloud-upload"></i> Upload
                            </button>
                            <button type="button" id="retakeBtn" class="btn btn-secondary" style="display: none;">
                                <i class="bi bi-arrow-counterclockwise"></i> Retake
                            </button>
                        </div>
    </form>
                </div>
            </div>

            <style>
            .camera-container {
                width: 100%;
                max-width: 640px;
                margin: 20px auto;
                text-align: center;
            }

            #cameraFeed {
                width: 100%;
                border-radius: 8px;
                background: #000;
            }

            .camera-buttons {
                display: flex;
                gap: 10px;
                justify-content: center;
                margin-top: 20px;
            }

            .modal-content {
                max-width: 700px;
                width: 90%;
            }

            #photoPreview {
                border-radius: 8px;
                border: 2px solid #ddd;
            }
            </style>

            <script>
            let stream = null;
            const modal = document.getElementById('cameraModal');
            const cameraCloseBtn = document.querySelector('.close');
            const cameraFeed = document.getElementById('cameraFeed');
            const photoCanvas = document.getElementById('photoCanvas');
            const photoPreview = document.getElementById('photoPreview');
            const takePhotoBtn = document.getElementById('takePhotoBtn');
            const uploadBtn = document.getElementById('uploadBtn');
            const retakeBtn = document.getElementById('retakeBtn');
            const verificationForm = document.getElementById('verificationForm');

            // Function to remove chore from UI
            function removeChoreFromList(choreId) {
                const choreRow = document.querySelector(`tr[data-chore-id="${choreId}"]`);
                if (choreRow) {
                    choreRow.remove();
                }
                // Update stats
                updateStats();
            }

            // Function to update stats
            function updateStats() {
                const pendingChores = document.querySelectorAll('tr[data-chore-id]').length;
                document.querySelector('.stat-value:nth-child(1)').textContent = pendingChores;
            }

            // Function to show notification
            function showNotification(message, isSuccess = true) {
                const notification = document.createElement('div');
                notification.className = `notification ${isSuccess ? 'success' : 'error'}`;
                notification.textContent = message;
                document.body.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            // Function to open camera
            window.openCamera = async function(choreId) {
                document.getElementById('choreId').value = choreId;
                const modal = document.getElementById('cameraModal');
                modal.classList.add('show');
                
                try {
                    const constraints = {
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        }
                    };

                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    cameraFeed.srcObject = stream;
                    
                    // Reset UI state
                    takePhotoBtn.style.display = 'block';
                    uploadBtn.style.display = 'none';
                    retakeBtn.style.display = 'none';
                    photoPreview.style.display = 'none';
                    cameraFeed.style.display = 'block';

                    await new Promise((resolve) => {
                        cameraFeed.onloadedmetadata = () => {
                            resolve();
                        };
                    });

                } catch (err) {
                    console.error('Camera error:', err);
                    showNotification('Error accessing camera. Please make sure you have granted camera permissions and try again.', false);
                    closeCamera();
                }
            }

            // Close modal and stop camera
            function closeCamera() {
                const modal = document.getElementById('cameraModal');
                modal.classList.remove('show');
                if (stream) {
                    stream.getTracks().forEach(track => {
                        track.stop();
                    });
                    stream = null;
                }
                // Reset UI
                cameraFeed.srcObject = null;
                photoPreview.src = '';
                photoPreview.style.display = 'none';
                cameraFeed.style.display = 'block';
                takePhotoBtn.style.display = 'block';
                uploadBtn.style.display = 'none';
                retakeBtn.style.display = 'none';
            }

            // Event listeners
            if (cameraCloseBtn) {
                cameraCloseBtn.onclick = closeCamera;
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    closeCamera();
                }
            }

            takePhotoBtn.onclick = function() {
                try {
                    photoCanvas.width = cameraFeed.videoWidth;
                    photoCanvas.height = cameraFeed.videoHeight;
                    
                    const context = photoCanvas.getContext('2d');
                    context.drawImage(cameraFeed, 0, 0);
                    
                    const photoData = photoCanvas.toDataURL('image/jpeg', 0.8);
                    document.getElementById('photoData').value = photoData;
                    
                    photoPreview.src = photoData;
                    photoPreview.style.display = 'block';
                    cameraFeed.style.display = 'none';
                    
                    takePhotoBtn.style.display = 'none';
                    uploadBtn.style.display = 'block';
                    retakeBtn.style.display = 'block';
                } catch (err) {
                    console.error('Photo capture error:', err);
                    showNotification('Error capturing photo. Please try again.', false);
                }
            }

            retakeBtn.onclick = function() {
                photoPreview.style.display = 'none';
                cameraFeed.style.display = 'block';
                takePhotoBtn.style.display = 'block';
                uploadBtn.style.display = 'none';
                retakeBtn.style.display = 'none';
            }

            verificationForm.onsubmit = async function(e) {
                e.preventDefault();
                const photoData = document.getElementById('photoData').value;
                if (!photoData) {
                    showNotification('Please take a photo first!', false);
                    return;
                }

                // Show loading state
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';

                try {
                    const formData = new FormData(this);
                    const response = await fetch('verify_chore.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification(result.message);
                        closeCamera();
                        if (result.chore_id) {
                            removeChoreFromList(result.chore_id);
                        }
                    } else {
                        showNotification(result.message, false);
                    }
                } catch (err) {
                    console.error('Upload error:', err);
                    showNotification('Error uploading photo. Please try again.', false);
                } finally {
                    // Reset button state
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload';
                }
            }

            // Handle page unload
            window.onbeforeunload = function() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            }
            </script>

            <!-- Achievements Preview Section -->
            <div class="rewards-preview">
                <h2 class="section-title">Your Achievements</h2>
                <div class="rewards-grid">
                    <?php if (!empty($rewards)): ?>
                        <?php foreach ($rewards as $reward): ?>
                            <div class="reward-card">
                                <div class="reward-icon">
                                    <i class="bi bi-gift"></i>
                                </div>
                                <div class="reward-content">
                                    <h3 class="reward-name"><?= htmlspecialchars($reward['name']) ?></h3>
                                    <div class="reward-points">
                                        <i class="bi bi-star-fill"></i>
                                        <?= $reward['points_required'] ?> points
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-rewards">No achievements redeemed yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <style>
            .rewards-preview {
                background: white;
                padding: 35px;
                border-radius: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                margin-bottom: 40px;
                border: 1px solid #FFD3B5;
            }

            .rewards-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 25px;
                margin-top: 30px;
            }

            .reward-card {
                background: #f8f9fa;
                border-radius: 15px;
                padding: 25px;
                display: flex;
                align-items: center;
                gap: 20px;
                transition: all 0.3s ease;
                border: 1px solid #FFD3B5;
            }

            .reward-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(255, 163, 165, 0.15);
            }

            .reward-icon {
                font-size: 2.5rem;
                color: #FFD3B5;
                background: white;
                width: 70px;
                height: 70px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                box-shadow: 0 4px 15px rgba(255, 163, 165, 0.2);
            }

            .reward-content {
                flex: 1;
            }

            .reward-name {
                color: #333;
                font-size: 1.2rem;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .reward-points {
                color: #666;
                font-size: 1rem;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .reward-points i {
                color: #FFD3B5;
            }

            .no-rewards {
                text-align: center;
                color: #666;
                font-size: 1.1rem;
                padding: 30px;
                background: #f8f9fa;
                border-radius: 12px;
                border: 1px dashed #FFD3B5;
            }

            @media (max-width: 768px) {
                .rewards-grid {
                    grid-template-columns: 1fr;
                }
            }
            </style>
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
// Send Message Function (AJAX)
async function sendMessage(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const messageText = document.getElementById('message_text').value.trim();
    const sendBtn = document.getElementById('send-message-btn');
    const alertDiv = document.getElementById('message-alert');
    
    if (!messageText) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = '#fee';
        alertDiv.style.color = '#c33';
        alertDiv.style.border = '1px solid #fcc';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Please enter a message.';
        return false;
    }
    
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
    
    try {
        const formData = new FormData();
        formData.append('send_message', '1');
        formData.append('message_text', messageText);
        
        const response = await fetch('member.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alertDiv.style.display = 'block';
            alertDiv.style.background = '#d4edda';
            alertDiv.style.color = '#155724';
            alertDiv.style.border = '1px solid #c3e6cb';
            alertDiv.innerHTML = '<i class="bi bi-check-circle"></i> ' + result.message;
            document.getElementById('message_text').value = '';
            
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 3000);
        } else {
            alertDiv.style.display = 'block';
            alertDiv.style.background = '#fee';
            alertDiv.style.color = '#c33';
            alertDiv.style.border = '1px solid #fcc';
            alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + (result.message || 'Error sending message. Please try again.');
        }
    } catch (error) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = '#fee';
        alertDiv.style.color = '#c33';
        alertDiv.style.border = '1px solid #fcc';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Error sending message. Please try again.';
    } finally {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="bi bi-send"></i> Send Message';
    }
    
    return false;
}

// Report Builder Functions
function generateReport() {
    const dateRange = document.getElementById('report-date-range').value;
    const format = document.getElementById('report-format').value;
    const includes = Array.from(document.querySelectorAll('input[name="include[]"]:checked')).map(cb => cb.value);
    
    const reportContent = document.getElementById('report-content');
    const preview = document.getElementById('report-preview');
    
    const completedMissions = <?= json_encode($completed_chores) ?>;
    
    let html = '<h3 style="color: #5c4d3c; margin-bottom: 20px;">Volunteer Activity Report</h3>';
    html += '<p style="color: #666; margin-bottom: 15px;"><strong>Date Range:</strong> ' + (dateRange === 'all' ? 'All Time' : 'Last ' + dateRange + ' days') + '</p>';
    html += '<p style="color: #666; margin-bottom: 20px;"><strong>Generated:</strong> ' + new Date().toLocaleString() + '</p>';
    
    if (includes.includes('missions')) {
        html += '<div style="margin-bottom: 20px;"><h4 style="color: #333;">Missions Completed</h4>';
        html += '<p><strong>Total:</strong> <?= $total_missions ?> missions</p>';
        if (completedMissions.length > 0) {
            html += '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
            html += '<thead><tr style="background: #f8f9fa;"><th style="padding: 10px; text-align: left; border-bottom: 2px solid #e0e0e0;">Mission Name</th><th style="padding: 10px; text-align: left; border-bottom: 2px solid #e0e0e0;">Hours</th><th style="padding: 10px; text-align: left; border-bottom: 2px solid #e0e0e0;">Completed Date</th></tr></thead>';
            html += '<tbody>';
            completedMissions.slice(0, 20).forEach(mission => {
                const date = mission.verified_at ? new Date(mission.verified_at).toLocaleDateString() : 'N/A';
                html += `<tr style="border-bottom: 1px solid #f0f0f0;"><td style="padding: 8px;">${mission.chore_name || 'N/A'}</td><td style="padding: 8px;">${mission.points || 0}</td><td style="padding: 8px;">${date}</td></tr>`;
            });
            html += '</tbody></table>';
        }
        html += '</div>';
    }
    if (includes.includes('hours')) {
        html += '<div style="margin-bottom: 20px;"><h4 style="color: #333;">Volunteer Hours</h4><p><strong>Total:</strong> <?= number_format($user_points) ?> hours</p></div>';
    }
    if (includes.includes('rewards')) {
        html += '<div style="margin-bottom: 20px;"><h4 style="color: #333;">Rewards</h4><p><strong>Total Rewards Redeemed:</strong> <?= count($rewards) ?></p></div>';
    }
    
    reportContent.innerHTML = html;
    preview.style.display = 'block';
}

function exportReport(format) {
    if (format === 'csv') {
        const csv = 'Date Range,Total Missions,Total Hours,Engagement Score\n' +
                   '<?= date("Y-m-d") ?>,' + <?= $total_missions ?> + ',' + <?= $user_points ?> + ',' + <?= $engagement_score ?>;
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'volunteer-report-' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
    } else if (format === 'html') {
        const content = document.getElementById('report-content').innerHTML;
        const html = '<!DOCTYPE html><html><head><title>Volunteer Report</title></head><body>' + content + '</body></html>';
        const blob = new Blob([html], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'volunteer-report-' + new Date().toISOString().split('T')[0] + '.html';
        a.click();
    }
}

// Activity Chart (Simple bar chart using CSS)
window.addEventListener('DOMContentLoaded', function() {
    const activityData = <?= json_encode($activity_data ?? []) ?>;
    const chartContainer = document.getElementById('activity-chart');
    if (chartContainer) {
        if (activityData && Array.isArray(activityData) && activityData.length > 0) {
            const missionsArray = activityData.map(d => parseInt(d.missions_completed) || 0);
            const maxMissions = Math.max(...missionsArray);
            if (maxMissions > 0) {
                chartContainer.innerHTML = '<div style="display: flex; align-items: flex-end; justify-content: space-around; height: 150px; gap: 5px; padding: 10px; overflow-x: auto;">' +
                    activityData.map(d => {
                        const missions = parseInt(d.missions_completed) || 0;
                        const height = maxMissions > 0 ? (missions / maxMissions) * 100 : 0;
                        const dateStr = d.activity_date || '';
                        let dateLabel = '';
                        try {
                            if (dateStr) {
                                const date = new Date(dateStr);
                                dateLabel = date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
                            }
                        } catch(e) {
                            dateLabel = dateStr;
                        }
                        return '<div style="flex: 1; display: flex; flex-direction: column; align-items: center; min-width: 40px;"><div style="background: #5c4d3c; width: 100%; height: ' + height + '%; border-radius: 4px 4px 0 0; min-height: 5px; max-height: 100%; transition: all 0.3s ease;"></div><div style="font-size: 0.7rem; color: #666; margin-top: 5px; font-weight: 500;">' + missions + '</div><div style="font-size: 0.65rem; color: #999; margin-top: 2px; text-align: center;">' + dateLabel + '</div></div>';
                    }).join('') + '</div>';
            } else {
                chartContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No activity data available for the selected period.</p>';
            }
        } else {
            chartContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No activity data available for the selected period. Complete missions to see your activity trend!</p>';
        }
    }
});

// AI Suggestions Function
async function getAISuggestions() {
    const context = document.getElementById('ai_context').value.trim();
    const container = document.getElementById('ai-suggestions-container');
    const list = document.getElementById('ai-suggestions-list');
    const button = event.target;
    
    if (!context) {
        alert('Please enter what kind of missions you\'re looking for');
        return;
    }
    
    // Show loading
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Getting suggestions...';
    list.innerHTML = '<p class="text-center" style="color: #666;">Loading suggestions...</p>';
    container.style.display = 'block';
    
    try {
        const GEMINI_KEY = 'AIzaSyAVD5YiIAilUzdm8x_CGKCMYI1Vmamd6TI';
        const GEMINI_MODEL = 'gemini-2.5-flash';
        const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_KEY}`;
        
        const totalOrgHours = <?= isset($total_org_hours) ? $total_org_hours : 0 ?>;
        const totalVolunteers = <?= isset($total_volunteers) ? $total_volunteers : 0 ?>;
        const userHours = <?= $user_points ?>;
        const aiContext = `You are a volunteer mission suggestion assistant. The user has ${userHours} volunteer hours. The organization has ${totalOrgHours} total hours and ${totalVolunteers} volunteers. User request: ${context}. Please suggest 3-5 specific volunteer missions. For each mission, provide: 1) Mission name, 2) Brief description, 3) Estimated hours (1-10), 4) Category. Format as JSON array with fields: name, description, hours, category.`;
        
        const response = await fetch(GEMINI_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                contents: [{
                    parts: [{ text: aiContext }]
                }],
                generationConfig: {
                    temperature: 0.7,
                    topK: 40,
                    topP: 0.95,
                    maxOutputTokens: 2000
                }
            })
        });
        
        if (!response.ok) {
            throw new Error('API request failed');
        }
        
        const data = await response.json();
        let suggestions = [];
        
        if (data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts) {
            const text = data.candidates[0].content.parts[0].text;
            
            // Try to extract JSON
            const jsonMatch = text.match(/\[[\s\S]*\]/);
            if (jsonMatch) {
                try {
                    suggestions = JSON.parse(jsonMatch[0]);
                } catch(e) {
                    console.error('JSON parse error:', e);
                }
            }
            
            // If no JSON, parse text manually
            if (suggestions.length === 0) {
                const lines = text.split('\n').filter(l => l.trim());
                let currentSuggestion = {};
                lines.forEach(line => {
                    if (line.match(/mission|volunteer|community|help|assist/i)) {
                        if (currentSuggestion.name) {
                            suggestions.push(currentSuggestion);
                            currentSuggestion = {};
                        }
                        currentSuggestion.name = line.substring(0, 50).trim();
                        currentSuggestion.description = line.trim();
                        const hourMatch = line.match(/\d+/);
                        currentSuggestion.hours = hourMatch ? parseInt(hourMatch[0]) : 2;
                        currentSuggestion.category = 'Community Service';
                    }
                });
                if (currentSuggestion.name) suggestions.push(currentSuggestion);
            }
        }
        
        // Default suggestions if none found
        if (suggestions.length === 0) {
            suggestions = [
                { name: 'Community Garden Maintenance', description: 'Help maintain and beautify local community gardens', hours: 3, category: 'Environmental' },
                { name: 'Food Bank Assistance', description: 'Sort and organize donations at the local food bank', hours: 4, category: 'Community Service' },
                { name: 'Tutoring Students', description: 'Provide academic support to students in need', hours: 2, category: 'Education' }
            ];
        }
        
        // Display suggestions
        list.innerHTML = '';
        suggestions.slice(0, 5).forEach((suggestion) => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.style.cssText = 'background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 15px; border-left: 4px solid #5c4d3c; transition: all 0.3s ease;';
            const safeName = (suggestion.name || 'Mission').replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeDesc = (suggestion.description || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
            item.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                    <h4 style="color: #5c4d3c; margin: 0; font-size: 1.2rem; font-weight: 600;">${suggestion.name || 'Mission'}</h4>
                    <span style="background: #5c4d3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem;">${suggestion.category || 'Community Service'}</span>
                </div>
                <p style="color: #666; margin-bottom: 15px; line-height: 1.6;">${suggestion.description || 'A great volunteer opportunity'}</p>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #5c4d3c; font-weight: 500;"><i class="bi bi-clock"></i> ${suggestion.hours || 2} hours</span>
                    <button onclick="alert('Share this suggestion with your organization administrator to get it assigned!')" style="background: #5c4d3c; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease;">
                        <i class="bi bi-info-circle"></i> Learn More
                    </button>
                </div>
            `;
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            });
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
            list.appendChild(item);
        });
        
    } catch (error) {
        console.error('Error:', error);
        list.innerHTML = '<p class="text-center" style="color: #dc3545;">Error getting suggestions. Please try again.</p>';
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-magic"></i> Get AI Suggestions';
    }
}

// Leaderboard AJAX functionality
async function loadLeaderboard(filter) {
    const tbody = document.getElementById('leaderboard-tbody');
    const rankInfo = document.getElementById('leaderboard-rank-info');
    const rankText = document.getElementById('user-rank-text');
    const description = document.getElementById('leaderboard-description');
    const btnOrg = document.getElementById('btn-org-leaderboard');
    const btnGlobal = document.getElementById('btn-global-leaderboard');
    
    // Update button styles
    if (filter === 'organization') {
        btnOrg.style.background = '#5c4d3c';
        btnOrg.style.color = 'white';
        btnGlobal.style.background = '#f8f9fa';
        btnGlobal.style.color = '#333';
        description.textContent = 'See how you rank among your organization\'s volunteers';
    } else {
        btnGlobal.style.background = '#5c4d3c';
        btnGlobal.style.color = 'white';
        btnOrg.style.background = '#f8f9fa';
        btnOrg.style.color = '#333';
        description.textContent = 'See how you rank among all volunteers worldwide';
    }
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 40px; color: #666;"><i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Loading...</td></tr>';
    
    try {
        const response = await fetch(`get_leaderboard.php?filter=${filter}`);
        const data = await response.json();
        
        if (data.success) {
            // Update rank info
            if (data.user_rank) {
                rankText.textContent = `#${data.user_rank} ${filter === 'global' ? 'globally' : 'in your organization'}`;
                rankInfo.style.display = 'block';
            } else {
                rankInfo.style.display = 'none';
            }
            
            // Update table
            if (data.leaderboard.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 40px; color: #666;">No volunteers found yet.</td></tr>';
            } else {
                tbody.innerHTML = '';
                data.leaderboard.forEach((volunteer) => {
                    const row = document.createElement('tr');
                    const isCurrentUser = volunteer.member_email === '<?= $member_email ?>';
                    if (isCurrentUser) {
                        row.style.background = '#fff3cd';
                    }
                    
                    let rankCell = '';
                    if (volunteer.rank <= 3) {
                        const medals = ['🥇', '🥈', '🥉'];
                        rankCell = `<span style="font-size: 1.5rem;">${medals[volunteer.rank - 1]}</span>`;
                    } else {
                        rankCell = `<strong style="color: #333;">#${volunteer.rank}</strong>`;
                    }
                    
                    const name = volunteer.member_name || volunteer.member_email;
                    const youBadge = isCurrentUser ? '<span style="background: #FFD3B5; color: #333; padding: 3px 8px; border-radius: 10px; font-size: 0.85rem; margin-left: 8px;">You</span>' : '';
                    
                    row.innerHTML = `
                        <td>${rankCell}</td>
                        <td>
                            <strong style="color: #333;">${name}</strong>
                            ${youBadge}
                        </td>
                        <td><span style="background: #FFD3B5; color: #333; padding: 5px 12px; border-radius: 15px; font-weight: 500;">${parseInt(volunteer.hours).toLocaleString()} hrs</span></td>
                        <td><span style="background: #5c4d3c; color: white; padding: 5px 12px; border-radius: 15px; font-weight: 500;">Level ${volunteer.level}</span></td>
                        <td style="color: #666;">${volunteer.completed_missions} completed</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 40px; color: #dc3545;">Error loading leaderboard. Please try again.</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 40px; color: #dc3545;">Error loading leaderboard. Please try again.</td></tr>';
    }
}

// Add spin animation for loading
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .mission-marker {
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
        animation: pulse 2s infinite;
    }
    .mission-marker:hover {
        transform: scale(1.15);
        z-index: 10;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    .mission-marker.hidden {
        display: none;
    }
`;
document.head.appendChild(style);

// Mission Discovery Map functionality
const allMissions = <?= json_encode($all_missions) ?>;
let currentFilter = 'all';



function closeMissionDetails() {
    document.getElementById('mission-details-panel').style.display = 'none';
}

// Initialize map on page load
document.addEventListener('DOMContentLoaded', function() {
    if (allMissions && allMissions.length > 0) {
        filterMissions('all');
    } else {
        const mapContainer = document.getElementById('mission-map');
        mapContainer.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.7;"></i><p style="font-size: 1.1rem;">No missions found</p></div>';
    }
});
function filterMissions(filter) {
    const mapContainer = document.getElementById('mission-map');
    mapContainer.innerHTML = '';
    
    // Update button styles
    document.querySelectorAll('.map-filter-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '#f8f9fa';
        btn.style.border = '2px solid #e0e0e0';
        btn.style.color = '#333';
        if (btn.dataset.filter === filter || btn.onclick.toString().includes(filter)) {
            btn.classList.add('active');
            btn.style.background = '#5c4d3c';
            btn.style.border = '2px solid #5c4d3c';
            btn.style.color = 'white';
        }
    });
    
    let filteredMissions = allMissions || [];
    if (filter === 'pending') {
        filteredMissions = allMissions.filter(m => m.mission_status === 'pending');
    } else if (filter === 'completed') {
        filteredMissions = allMissions.filter(m => m.mission_status === 'completed');
    }
    
    if (filteredMissions.length === 0) {
        mapContainer.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.7;"></i><p style="font-size: 1.1rem;">No missions found</p></div>';
        return;
    }
    
    filteredMissions.forEach((mission, index) => {
        const marker = document.createElement('div');
        marker.className = 'mission-marker';
        marker.dataset.missionId = mission.id;
        marker.dataset.status = mission.mission_status;
        
        const bgColor = mission.mission_status === 'completed' ? '#28a745' : '#FFD3B5';
        const borderColor = mission.mission_status === 'completed' ? '#1e7e34' : '#ffb366';
        
        marker.style.cssText = `
            background: ${bgColor};
            border: 3px solid ${borderColor};
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
            animation: pulse 2s infinite;
        `;
        
        marker.innerHTML = `<i class="bi bi-${mission.mission_status === 'completed' ? 'check-circle' : 'clock'}" style="font-size: 1.5rem; color: ${mission.mission_status === 'completed' ? 'white' : '#333'};"></i>`;
        
        marker.addEventListener('click', () => showMissionDetails(mission));
        mapContainer.appendChild(marker);
    });
}

function showMissionDetails(mission) {
    const panel = document.getElementById('mission-details-panel');
    const nameEl = document.getElementById('mission-details-name');
    const contentEl = document.getElementById('mission-details-content');
    
    if (!panel || !nameEl || !contentEl) {
        console.error('Mission details panel elements not found');
        return;
    }
    
    const isPending = !mission.status || mission.status === 'pending';
    const statusBadge = isPending 
        ? '<span style="background: #FFD3B5; color: #333; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;"><i class="bi bi-clock"></i> Pending</span>'
        : '<span style="background: #28a745; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;"><i class="bi bi-check-circle"></i> Completed</span>';
    
    nameEl.textContent = mission.chore_name || mission.mission_name || 'Mission';
    
    let detailsHtml = `
        <div style="margin-bottom: 15px;">
            ${statusBadge}
            <span style="background: #5c4d3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; margin-left: 10px;">
                <i class="bi bi-clock"></i> ${mission.points || 0} hours
            </span>
        </div>
        <p style="color: #666; margin-bottom: 15px; line-height: 1.6;">
            ${mission.description || 'Complete this mission to earn volunteer hours and contribute to your organization'}
        </p>
    `;
    
    if (mission.verified_at) {
        var verifiedDate = mission.verified_at;
        var dateStr = 'N/A';
        if (verifiedDate) {
            var dateObj = new Date(verifiedDate);
            var timeValue = dateObj.getTime();
            var isValid = !isNaN(timeValue);
            if (dateObj && isValid) {
                var monthNum = dateObj.getMonth();
                var month = monthNum + 1;
                var day = dateObj.getDate();
                var year = dateObj.getFullYear();
                dateStr = month + '/' + day + '/' + year;
            }
        }
        var completedDiv = '<div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-top: 15px;"><strong style="color: #333;">Completed:</strong> <span style="color: #666;">' + dateStr + '</span></div>';
        detailsHtml = detailsHtml + completedDiv;
    }
    
    if (isPending && mission.id) {
        var missionId = mission.id;
        var buttonHtml = '<button onclick="openCamera(' + missionId + ')" style="background: #5c4d3c; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; margin-top: 15px; transition: all 0.3s ease;"><i class="bi bi-camera"></i> Verify Completion</button>';
        detailsHtml = detailsHtml + buttonHtml;
    }
    
    contentEl.innerHTML = detailsHtml;
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeMissionDetails() {
    document.getElementById('mission-details-panel').style.display = 'none';
}

// Initialize map on page load
document.addEventListener('DOMContentLoaded', function() {
    if (allMissions && allMissions.length > 0) {
        filterMissions('all');
    } else {
        const mapContainer = document.getElementById('mission-map');
        if (mapContainer) {
            mapContainer.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.7;"></i><p style="font-size: 1.1rem;">No missions found</p></div>';
        }
    }
});
</script>
<script>
// Fix null reference errors
const openModalBtn = document.getElementById("openModal");
if (openModalBtn) {
    openModalBtn.addEventListener("click", function() {
        const modal = document.getElementById("addMemberModal");
        if (modal) modal.style.display = "block";
    });
}

const addMemberCloseBtn = document.querySelector(".close");
if (addMemberCloseBtn) {
    addMemberCloseBtn.addEventListener("click", function() {
        const modal = document.getElementById("addMemberModal");
        if (modal) modal.style.display = "none";
    });
}

// Image Preview Functionality
const memberImage = document.getElementById("memberImage");
if (memberImage) {
    memberImage.addEventListener("change", function(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const imagePreview = document.getElementById("imagePreview");
            if (imagePreview) {
                imagePreview.src = reader.result;
                imagePreview.style.display = "block";
            }
        };
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    });
}

// Mission Comments Functions
function toggleComments(choreId) {
    const commentsRow = document.getElementById('comments-row-' + choreId);
    const completedContainer = document.getElementById('comments-container-completed-' + choreId);
    
    if (commentsRow) {
        if (commentsRow.style.display === 'none') {
            commentsRow.style.display = '';
            loadComments(choreId);
        } else {
            commentsRow.style.display = 'none';
        }
    }
    
    if (completedContainer) {
        if (completedContainer.style.display === 'none') {
            completedContainer.style.display = 'block';
            loadComments(choreId, 'completed');
        } else {
            completedContainer.style.display = 'none';
        }
    }
}

function loadComments(choreId, type = 'pending') {
    const commentsListId = type === 'completed' ? 'comments-list-completed-' + choreId : 'comments-list-' + choreId;
    const commentsList = document.getElementById(commentsListId);
    
    if (!commentsList) return;
    
    fetch('get_comments.php?chore_id=' + choreId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.comments) {
                if (data.comments.length === 0) {
                    commentsList.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">No comments yet. Be the first to comment!</p>';
                } else {
                    let html = '';
                    data.comments.forEach(comment => {
                        const date = new Date(comment.created_at).toLocaleString();
                        html += `
                            <div style="background: white; padding: 12px; border-radius: 5px; margin-bottom: 10px; border-left: 3px solid #5c4d3c;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <strong style="color: #5c4d3c;">${escapeHtml(comment.member_name || 'Volunteer')}</strong>
                                    <small style="color: #999;">${date}</small>
                                </div>
                                <p style="color: #333; margin: 0;">${escapeHtml(comment.comment_text)}</p>
                            </div>
                        `;
                    });
                    commentsList.innerHTML = html;
                }
            } else {
                commentsList.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 20px;">Error loading comments.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading comments:', error);
            commentsList.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 20px;">Error loading comments.</p>';
        });
}

function submitComment(choreId, type = 'pending') {
    const inputId = type === 'completed' ? 'comment-input-completed-' + choreId : 'comment-input-' + choreId;
    const commentInput = document.getElementById(inputId);
    const commentText = commentInput ? commentInput.value.trim() : '';
    
    if (!commentText) {
        alert('Please enter a comment.');
        return;
    }
    
    fetch('add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            chore_id: choreId,
            comment_text: commentText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            commentInput.value = '';
            loadComments(choreId, type);
        } else {
            alert('Error: ' + (data.message || 'Failed to post comment'));
        }
    })
    .catch(error => {
        console.error('Error submitting comment:', error);
        alert('Error posting comment. Please try again.');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<!-- JavaScript to handle modal display -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("addMemberModal");
        const modalCloseBtn = document.querySelector(".close");
        const signUpButton = document.querySelector(".button-submit");

        if (signUpButton) {
            signUpButton.addEventListener("click", function (event) {
                event.preventDefault();
                if (modal) modal.style.display = "flex";
            });
        }

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener("click", function () {
                if (modal) modal.style.display = "none";
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
