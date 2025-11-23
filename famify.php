<?php
session_start();
include 'config.php'; // Database connection

// Ensure only organization admins can access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$manager_email = $_SESSION['email'];

// Check if logged-in user is an organization admin
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $manager_email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die("<script>alert('Access denied: Only organization administrators can access this page.'); window.location.href='member.php';</script>");
}
$stmt->close();

// Handle announcement creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_announcement'])) {
    $announcement_title = trim($_POST['announcement_title']);
    $announcement_message = trim($_POST['announcement_message']);
    
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (!empty($announcement_title) && !empty($announcement_message)) {
        $stmt = $conn->prepare("INSERT INTO announcements (manager_email, title, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $manager_email, $announcement_title, $announcement_message);
            if ($stmt->execute()) {
                $announcement_sent = true;
                
                // If AJAX request, return JSON and exit
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Announcement sent successfully to all volunteers!']);
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
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
            exit();
        }
    }
}

// Handle mission assignments
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if this is an AJAX request (for mission assignments)
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'delete' && isset($_POST['chore_id'])) {
            // Delete mission
            $chore_id = intval($_POST['chore_id']);
            $stmt = $conn->prepare("DELETE FROM chores WHERE id = ? AND manager_email = ?");
            $stmt->bind_param("is", $chore_id, $manager_email);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('Mission deleted successfully!'); window.location.href='famify.php';</script>";
        } elseif ($_POST['action'] == 'edit' && isset($_POST['chore_id'])) {
            // Edit mission
            $chore_id = intval($_POST['chore_id']);
            $chore_name = trim($_POST['chore_name'] ?? '');
            $chore_points = intval($_POST['chore_points'] ?? 0);
            $member_email = trim($_POST['member_email'] ?? '');
            
            $stmt = $conn->prepare("UPDATE chores SET chore_name = ?, points = ?, member_email = ? WHERE id = ? AND manager_email = ?");
            $stmt->bind_param("sisss", $chore_name, $chore_points, $member_email, $chore_id, $manager_email);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('Mission updated successfully!'); window.location.href='famify.php';</script>";
        }
    } else if (isset($_POST['chore_name']) && isset($_POST['chore_points'])) {
        // Add new mission (supports bulk assignment with multiple volunteers)
        $chore_name = trim($_POST['chore_name']);
        $chore_points = intval($_POST['chore_points'] ?? 0);
        
        // Get selected volunteers (can be single email or array)
        $selected_volunteers = [];
        if (isset($_POST['member_emails']) && is_array($_POST['member_emails'])) {
            $selected_volunteers = $_POST['member_emails'];
        } elseif (isset($_POST['member_email']) && !empty($_POST['member_email'])) {
            // Backward compatibility - single email
            $selected_volunteers = [$_POST['member_email']];
        }

        if (!empty($chore_name) && !empty($selected_volunteers) && $chore_points > 0) {
            $status = 'pending';
            $redeemed = 0;
            $success_count = 0;
            $errors = [];
            
            // Insert mission for each selected volunteer
            foreach ($selected_volunteers as $member_email) {
                $member_email = trim($member_email);
                if (empty($member_email)) continue;
                
                $stmt = $conn->prepare("INSERT INTO chores (manager_email, member_email, chore_name, points, status, redeemed) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssisi", $manager_email, $member_email, $chore_name, $chore_points, $status, $redeemed);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $errors[] = $member_email . ': ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = $member_email . ': ' . $conn->error;
                }
            }
            
            // Return response
            if ($success_count > 0) {
                $message = $success_count > 1 
                    ? "Mission assigned successfully to {$success_count} volunteers!" 
                    : 'Mission assigned successfully!';
                
                if (!empty($errors)) {
                    $message .= ' Some errors: ' . implode(', ', $errors);
                }
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message, 'assigned_count' => $success_count]);
                    exit();
                } else {
                    echo "<script>alert('{$message}'); window.location.href='famify.php';</script>";
                }
            } else {
                $error_msg = !empty($errors) ? implode(', ', $errors) : 'Failed to assign mission.';
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error_msg]);
                    exit();
                } else {
                    echo "<script>alert('Error: " . addslashes($error_msg) . "');</script>";
                }
            }
        } else {
            // If AJAX request and validation failed, return error
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields correctly and select at least one volunteer.']);
                exit();
            } else {
                echo "<script>alert('Please fill in all fields correctly and select at least one volunteer.');</script>";
            }
        }
    }
}

// Fetch all volunteers for this organization (for bulk assignment)
$volunteers = [];
$stmt = $conn->prepare("SELECT member_name, member_email FROM family WHERE managers_email = ? ORDER BY member_name ASC");
if ($stmt) {
    $stmt->bind_param("s", $manager_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $volunteers[] = $row;
        }
    }
    $stmt->close();
}

// Fetch assigned missions
$chores = [];
$stmt = $conn->prepare("SELECT id, member_email, chore_name, points FROM chores WHERE manager_email = ?");
$stmt->bind_param("s", $manager_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $chores[] = $row;
}
$stmt->close();

// Fetch pending verifications
$stmt = $conn->prepare("
    SELECT cv.*, cv.chore_id, c.chore_name, c.points, f.member_email 
    FROM chore_verifications cv 
    JOIN chores c ON cv.chore_id = c.id 
    JOIN family f ON cv.member_email = f.member_email 
    WHERE cv.status = 'pending' AND c.manager_email = ?
");
$stmt->bind_param("s", $manager_email);
$stmt->execute();
$verifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate level function
function calculateLevel($hours) {
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

function getHoursForNextLevel($currentLevel) {
    $nextLevel = $currentLevel + 1;
    return pow($nextLevel - 1, 2) * 10;
}

// Get organization volunteers for leaderboard
$leaderboard_stmt = $conn->prepare("
    SELECT f.member_name, f.member_email, f.points as hours,
           COUNT(DISTINCT cv.id) as completed_missions
    FROM family f
    LEFT JOIN chore_verifications cv ON f.member_email = cv.member_email AND cv.status = 'approved'
    WHERE f.managers_email = ?
    GROUP BY f.id, f.member_name, f.member_email, f.points
    ORDER BY f.points DESC, completed_missions DESC
    LIMIT 10
");
$leaderboard_stmt->bind_param("s", $manager_email);
$leaderboard_stmt->execute();
$leaderboard_result = $leaderboard_stmt->get_result();
$leaderboard = [];
$rank = 1;
while ($row = $leaderboard_result->fetch_assoc()) {
    $row['level'] = calculateLevel($row['hours']);
    $row['rank'] = $rank;
    $leaderboard[] = $row;
    $rank++;
}
$leaderboard_stmt->close();

// Get organization stats
$org_stats_stmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) as total_hours, COUNT(*) as total_volunteers FROM family WHERE managers_email = ?");
$org_stats_stmt->bind_param("s", $manager_email);
$org_stats_stmt->execute();
$org_stats_result = $org_stats_stmt->get_result();
$org_stats = $org_stats_result->fetch_assoc();
$org_stats_stmt->close();

$total_org_hours = $org_stats['total_hours'] ?? 0;
$total_volunteers = $org_stats['total_volunteers'] ?? 0;

// Fetch resources
$resources_stmt = $conn->prepare("
    SELECT * FROM resources 
    WHERE manager_email = ? 
    ORDER BY created_at DESC
");
if ($resources_stmt) {
    $resources_stmt->bind_param("s", $manager_email);
    if ($resources_stmt->execute()) {
        $resources_result = $resources_stmt->get_result();
        $resources = [];
        while ($row = $resources_result->fetch_assoc()) {
            $resources[] = $row;
        }
    }
    $resources_stmt->close();
} else {
    $resources = [];
}

// Handle resource upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_resource'])) {
    $resource_title = trim($_POST['resource_title']);
    $resource_description = trim($_POST['resource_description']);
    $resource_category = trim($_POST['resource_category']);
    
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (!empty($resource_title)) {
        // For now, store as text resource (can be enhanced for file uploads)
        $stmt = $conn->prepare("INSERT INTO resources (manager_email, title, description, category, file_type) VALUES (?, ?, ?, ?, 'text')");
        if ($stmt) {
            $stmt->bind_param("ssss", $manager_email, $resource_title, $resource_description, $resource_category);
            if ($stmt->execute()) {
                $resource_added = true;
                $new_resource_id = $conn->insert_id;
                
                // If AJAX request, return JSON with new resource data and exit
                if ($is_ajax) {
                    // Fetch the newly added resource
                    $fetch_stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
                    $fetch_stmt->bind_param("i", $new_resource_id);
                    $fetch_stmt->execute();
                    $new_resource = $fetch_stmt->get_result()->fetch_assoc();
                    $fetch_stmt->close();
                    
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Resource added successfully!',
                        'resource' => $new_resource
                    ]);
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
            echo json_encode(['success' => false, 'message' => 'Please enter a resource title.']);
            exit();
        }
    }
}

// Fetch volunteer messages
$messages_stmt = $conn->prepare("
    SELECT vm.*, f.member_name 
    FROM volunteer_messages vm
    JOIN family f ON vm.volunteer_email = f.member_email
    WHERE vm.manager_email = ?
    ORDER BY vm.created_at DESC
    LIMIT 20
");
$messages_stmt->bind_param("s", $manager_email);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();
$volunteer_messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $volunteer_messages[] = $row;
}
$messages_stmt->close();

// Get volunteer mission history for AI context
$history_stmt = $conn->prepare("
    SELECT c.chore_name, c.points, cv.status
    FROM chores c
    LEFT JOIN chore_verifications cv ON c.id = cv.chore_id
    WHERE c.manager_email = ?
    ORDER BY cv.verified_at DESC
    LIMIT 10
");
$history_stmt->bind_param("s", $manager_email);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$mission_history = [];
while ($row = $history_result->fetch_assoc()) {
    $mission_history[] = $row;
}
$history_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Organization Admin - VolunteerHub</title>
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
/* Modern Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f8f9fa;
    color: #333;
    line-height: 1.6;
}

/* Container Styles */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

/* Card Styles */
.chore-assignment-card, .chores-list-card {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.section-title {
    color: #5c4d3c;
    font-size: 24px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

/* Form Styles */
.chore-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #5c4d3c;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #5c4d3c;
    box-shadow: 0 0 0 3px rgba(92, 77, 60, 0.1);
    outline: none;
}

.assign-button {
    background: #5c4d3c;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.assign-button:hover {
    background: #4a3d30;
    transform: translateY(-2px);
}

/* Table Styles */
.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table th {
    background: #f8f9fa;
    color: #5c4d3c;
    font-weight: 600;
    padding: 15px;
    text-align: left;
}

.table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    color: #333;
}

.points-badge {
    background: #5c4d3c;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.table-responsive {
    border-radius: 8px;
    overflow: hidden;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
}

.edit-button, .delete-button {
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.edit-button {
    color: #5c4d3c;
}

.delete-button {
    color: #dc3545;
}

.edit-button:hover {
    color: #4a3d30;
}

.delete-button:hover {
    color: #c82333;
}

/* Camera Modal Styles */
.camera-modal .modal-content {
    max-width: 800px;
    background: #fff;
    color: #333;
}

.camera-container {
    width: 100%;
    max-width: 640px;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
}

.camera-feed {
    width: 100%;
    height: auto;
    display: block;
}

.camera-controls {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    justify-content: center;
}

.camera-btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.camera-btn.primary {
    background: #5c4d3c;
    color: white;
}

.camera-btn.secondary {
    background: #dc3545;
    color: white;
}

.camera-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    .chore-assignment-card, .chores-list-card {
        padding: 20px;
    }
    
    .form-control {
        font-size: 14px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}

.stats-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: transform 0.3s ease;
    border: 1px solid #e0e0e0;
    margin-bottom: 20px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 2.5rem;
    color: #5c4d3c;
    margin-bottom: 15px;
}

.stat-info h3 {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    color: #5c4d3c;
    margin: 0;
}

/* Announcements Card Styles */
.announcements-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    height: 100%;
}

.announcement-item {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    transition: transform 0.3s ease;
}

.announcement-item:last-child {
    border-bottom: none;
}

.announcement-item:hover {
    transform: translateX(5px);
}

.announcement-icon {
    font-size: 1.5rem;
    color: #5c4d3c;
    margin-right: 20px;
    flex-shrink: 0;
}

.announcement-content h3 {
    font-size: 1.2rem;
    color: #5c4d3c;
    margin-bottom: 10px;
}

.announcement-content p {
    color: #666;
    margin-bottom: 10px;
    line-height: 1.6;
}

.announcement-date {
    font-size: 0.9rem;
    color: #999;
}

/* Quick Actions Card Styles */
.quick-actions-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    height: 100%;
}

.quick-actions-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.quick-action-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #5c4d3c;
    text-decoration: none;
    transition: all 0.3s ease;
}

.quick-action-item:hover {
    background: #5c4d3c;
    color: white;
    transform: translateX(5px);
}

.quick-action-item i {
    font-size: 1.2rem;
    margin-right: 15px;
}

.quick-action-item span {
    font-weight: 500;
}

@media (max-width: 768px) {
    .announcements-card, .quick-actions-card {
        margin-bottom: 30px;
    }
    
    .announcement-item {
        padding: 15px;
    }
    
    .quick-action-item {
        padding: 12px;
    }
}

.verification-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    height: 100%;
}

.verification-card:hover {
    transform: translateY(-5px);
}

.verification-image-container {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.verification-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-bottom: 2px solid #f0f0f0;
}

.verification-details {
    padding: 20px;
}

.verification-title {
    color: #5c4d3c;
    font-size: 1.2rem;
    margin-bottom: 15px;
    font-weight: 600;
}

.verification-info {
    margin-bottom: 20px;
}

.verification-info p {
    color: #666;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.verification-info i {
    color: #5c4d3c;
    font-size: 1.1rem;
}

.verification-actions {
    display: flex;
    gap: 10px;
}

.btn-approve, .btn-reject {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-approve {
    background: #28a745;
    color: white;
}

.btn-reject {
    background: #dc3545;
    color: white;
}

.btn-approve:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-reject:hover {
    background: #c82333;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .verification-image-container {
        height: 180px;
    }
    
    .verification-details {
        padding: 15px;
    }
    
    .verification-title {
        font-size: 1.1rem;
    }
    
    .verification-actions {
        flex-direction: column;
    }
}

/* AI Suggestions Styles */
.suggestion-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    border-left: 4px solid #5c4d3c;
    transition: all 0.3s ease;
}

.suggestion-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.suggestion-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.suggestion-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #5c4d3c;
    margin: 0;
}

.suggestion-category {
    background: #5c4d3c;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.suggestion-description {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
}

.suggestion-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggestion-hours {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #5c4d3c;
    font-weight: 500;
}

.use-suggestion-btn {
    background: #5c4d3c;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.use-suggestion-btn:hover {
    background: #4a3d30;
    transform: translateY(-2px);
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(92, 77, 60, 0.3);
    border-radius: 50%;
    border-top-color: #5c4d3c;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
        <li><a href="famify.php" class="active">Organization Center</a></li>
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


  <main class="main">


    <section id="hero" class="hero section dark-background">

  

      <div id="hero-carousel" class="carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">

        <div class="container position-relative">

          <div class="carousel-item active">
            <div class="carousel-container">
              <h2>Welcome to VolunteerHub</h2>
              <p>Discover a powerful platform to manage volunteer missions, track hours, and recognize achievements. Organize missions, track volunteer hours, and grow community impact. Build stronger communities through organized volunteer work. VolunteerHub empowers organizations and volunteers.</p>
              <a href="#about" class="btn-get-started">Read More</a>
            </div>
          </div>

          <div class="carousel-item">
            <div class="carousel-container">
              <h2>Our Mission</h2>
              <p>Empower organizations to effectively manage volunteers and missions. Track volunteer hours, assign missions, and recognize achievements. Build a thriving volunteer community that makes a real impact. Start organizing today with VolunteerHub!</p>
              <a href="#about" class="btn-get-started">Read More</a>
            </div>
          </div>

          <div class="carousel-item">
            <div class="carousel-container">
              <h2>Our Vision</h2>
              <p>At VolunteerHub, we aim to strengthen communities through seamless volunteer management and shared achievements. By organizing missions and fostering collaboration, we empower organizations and volunteers to create lasting positive impact together.
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
                <h3>Organization Management</h3>
              </a>
              <p>Create and manage your volunteer organization with ease. Add volunteers, assign roles, and start organizing missions in minutes to keep everyone connected and productive.</p>
            </div>
          </div>
    
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item item-orange position-relative">
              <div class="icon">
                <i class="bi bi-card-checklist"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>Mission Assignments</h3>
              </a>
              <p>Assign volunteer missions with ease. Keep track of completed missions and recognize progress to motivate everyone in your organization.</p>
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
              <p>Monitor your organization's progress through detailed charts and tables. See which volunteers are excelling and who might need a little extra encouragement.</p>
            </div>
          </div>
    
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item item-red position-relative">
              <div class="icon">
                <i class="bi bi-gift"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>Achievement System</h3>
              </a>
              <p>Set up an achievement system where completed missions earn volunteer hours that can be converted into badges, certificates, or recognition.</p>
            </div>
          </div>
    
        </div>
    
      </div>
    
    </section>
    <div class="container mt-5">
      <div class="row">
        <div class="col-lg-6">
          <div class="chore-assignment-card">
            <h2 class="section-title">Assign a New Mission</h2>
            <div id="chore-alert" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px;"></div>
            <form id="add-chore-form" onsubmit="return addChore(event)" class="chore-form">
              <div class="form-group">
                <label for="chore_name">Mission Name</label>
                <input type="text" id="chore_name" name="chore_name" placeholder="e.g., Community cleanup event" required class="form-control">
              </div>
              
              <div class="form-group">
                <label for="chore_points">Volunteer Hours</label>
                <input type="number" id="chore_points" name="chore_points" placeholder="Enter hours (1-100)" min="1" max="100" required class="form-control">
              </div>
              
              <div class="form-group">
                <label for="member_emails">Select Volunteers (Hold Ctrl/Cmd to select multiple)</label>
                <select id="member_emails" name="member_emails[]" multiple required class="form-control" style="min-height: 150px;">
                    <?php if (empty($volunteers)): ?>
                        <option disabled>No volunteers added yet. Add volunteers first.</option>
                    <?php else: ?>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <option value="<?= htmlspecialchars($volunteer['member_email']) ?>">
                                <?= htmlspecialchars($volunteer['member_name'] ?? $volunteer['member_email']) ?> (<?= htmlspecialchars($volunteer['member_email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted" style="display: block; margin-top: 5px;">Select one or more volunteers to assign this mission to</small>
              </div>
              
              <button type="submit" id="add-chore-btn" class="assign-button">
                <i class="bi bi-plus-circle"></i> Assign Chore
              </button>
    </form>
          </div>
        </div>
    
        <div class="col-lg-6">
          <div class="chores-list-card">
            <h2 class="section-title">Assigned Chores</h2>
            <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
                    <th>Member</th>
          <th>Chore</th>
          <th>Points</th>
                    <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($chores as $chore): ?>
        <tr>
          <td><?= htmlspecialchars($chore['member_email']) ?></td>
          <td><?= htmlspecialchars($chore['chore_name']) ?></td>
                    <td><span class="points-badge"><?= htmlspecialchars($chore['points']) ?></span></td>
                    <td>
                      <div class="action-buttons">
                        <button class="edit-button" onclick="editChore(<?= htmlspecialchars(json_encode($chore)) ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this chore?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="chore_id" value="<?= $chore['id'] ?>">
                          <button type="submit" class="delete-button">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
            </div>
          </div>
        </div>
      </div>
  </div>

    <!-- Pending Verifications Section -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-12">
          <div class="chores-list-card">
            <h2 class="section-title">Pending Verifications</h2>
            <div class="row">
              <?php if (empty($verifications)): ?>
                <div class="col-12 text-center">
                  <p>No pending verifications.</p>
                </div>
              <?php else: 
                foreach ($verifications as $verification): ?>
                  <div class="col-md-6 col-lg-4 mb-4">
                    <div class="verification-card">
                      <div class="verification-image-container">
                        <img src="<?= htmlspecialchars($verification['photo_path']) ?>" alt="Chore verification" class="verification-image">
                      </div>
                      <div class="verification-details">
                        <h4 class="verification-title"><?= htmlspecialchars($verification['chore_name']) ?></h4>
                        <div class="verification-info">
                          <p><i class="bi bi-person"></i> <?= htmlspecialchars($verification['member_email']) ?></p>
                          <p><i class="bi bi-star"></i> <?= htmlspecialchars($verification['points']) ?> points</p>
                          <p><i class="bi bi-clock"></i> <?= isset($verification['submitted_at']) ? date('M d, Y H:i', strtotime($verification['submitted_at'])) : (isset($verification['created_at']) ? date('M d, Y H:i', strtotime($verification['created_at'])) : 'N/A') ?></p>
                        </div>
                        <div class="verification-actions">
                          <button type="button" onclick="handleVerification(<?= $verification['id'] ?>, <?= isset($verification['chore_id']) ? $verification['chore_id'] : 0 ?>, '<?= htmlspecialchars($verification['member_email'], ENT_QUOTES) ?>', <?= isset($verification['points']) ? $verification['points'] : 0 ?>, 'approve')" class="btn-approve">
                            <i class="bi bi-check-circle"></i> Approve
                          </button>
                          <button type="button" onclick="handleVerification(<?= $verification['id'] ?>, <?= isset($verification['chore_id']) ? $verification['chore_id'] : 0 ?>, '<?= htmlspecialchars($verification['member_email'], ENT_QUOTES) ?>', <?= isset($verification['points']) ? $verification['points'] : 0 ?>, 'reject')" class="btn-reject">
                            <i class="bi bi-x-circle"></i> Reject
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach;
              endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Announcement Center Section -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-12">
          <div class="chore-assignment-card">
            <h2 class="section-title"><i class="bi bi-megaphone"></i> Send Announcement to Volunteers</h2>
            <?php if (isset($announcement_sent) && $announcement_sent): ?>
              <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <i class="bi bi-check-circle"></i> Announcement sent successfully to all volunteers!
              </div>
            <?php endif; ?>
            <div id="announcement-alert" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px;"></div>
            <form id="announcement-form" onsubmit="return sendAnnouncement(event)" class="chore-form">
              <div class="form-group">
                <label for="announcement_title">Announcement Title</label>
                <input type="text" id="announcement_title" name="announcement_title" placeholder="e.g., Upcoming Event, Important Notice" required class="form-control">
              </div>
              <div class="form-group">
                <label for="announcement_message">Message</label>
                <textarea id="announcement_message" name="announcement_message" rows="5" placeholder="Type your announcement message here..." required class="form-control" style="resize: vertical;"></textarea>
              </div>
              <button type="submit" id="send-announcement-btn" class="assign-button">
                <i class="bi bi-send"></i> Send Announcement
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Volunteer Messages Section -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-12">
          <div class="chores-list-card">
            <h2 class="section-title"><i class="bi bi-envelope"></i> Messages from Volunteers</h2>
            <?php if (empty($volunteer_messages)): ?>
              <div class="text-center" style="padding: 40px;">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                <p style="color: #666;">No messages from volunteers yet.</p>
              </div>
            <?php else: ?>
              <div class="row">
                <?php foreach ($volunteer_messages as $msg): ?>
                  <div class="col-md-6 mb-3">
                    <div class="verification-card" style="background: <?= $msg['status'] == 'unread' ? '#fff3cd' : '#fff' ?>; border-left: 4px solid <?= $msg['status'] == 'unread' ? '#ffc107' : '#5c4d3c' ?>;">
                      <div class="verification-details">
                        <h4 style="color: #5c4d3c; margin-bottom: 10px;">
                          <?= htmlspecialchars($msg['member_name'] ?? $msg['volunteer_email']) ?>
                          <?php if ($msg['status'] == 'unread'): ?>
                            <span style="background: #ffc107; color: #333; padding: 3px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: 10px;">NEW</span>
                          <?php endif; ?>
                        </h4>
                        <p style="color: #666; margin-bottom: 10px; white-space: pre-wrap;"><?= htmlspecialchars($msg['message']) ?></p>
                        <p style="color: #999; font-size: 0.9rem;">
                          <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($msg['created_at'])) ?>
                        </p>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Chore Modal -->
    <div id="editChoreModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Chore</h2>
        <form method="POST" class="chore-form">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="chore_id" id="edit_chore_id">
          
          <div class="form-group">
            <label for="edit_chore_name">Chore Name</label>
            <input type="text" id="edit_chore_name" name="chore_name" required class="form-control">
          </div>
          
          <div class="form-group">
            <label for="edit_chore_points">Points Value</label>
            <input type="number" id="edit_chore_points" name="chore_points" min="1" max="100" required class="form-control">
          </div>
          
          <div class="form-group">
            <label for="edit_member_email">Family Member</label>
            <input type="email" id="edit_member_email" name="member_email" required class="form-control">
          </div>
          
          <button type="submit" class="assign-button">
            <i class="bi bi-save"></i> Save Changes
          </button>
        </form>
      </div>
    </div>

    <!-- Feature 57: Resource Library -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-12">
          <div class="chore-assignment-card">
            <h2 class="section-title"><i class="bi bi-folder"></i> Resource Library</h2>
            <p style="color: #666; margin-bottom: 20px;">Manage documents, guides, and training materials for your volunteers</p>
            
            <?php if (isset($resource_added) && $resource_added): ?>
              <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <i class="bi bi-check-circle"></i> Resource added successfully!
              </div>
            <?php endif; ?>
            
            <!-- Add Resource Form -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
              <h3 style="color: #5c4d3c; margin-bottom: 15px; font-size: 1.2rem;">Add New Resource</h3>
              <div id="resource-alert" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px;"></div>
              <form id="add-resource-form" onsubmit="return addResource(event)" class="chore-form">
                <div class="form-group">
                  <label for="resource_title">Resource Title</label>
                  <input type="text" id="resource_title" name="resource_title" placeholder="e.g., Volunteer Training Guide, Mission Checklist" required class="form-control">
                </div>
                <div class="form-group">
                  <label for="resource_category">Category</label>
                  <select id="resource_category" name="resource_category" class="form-control">
                    <option value="Training">Training</option>
                    <option value="Guides">Guides</option>
                    <option value="Documents">Documents</option>
                    <option value="Templates">Templates</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="resource_description">Description</label>
                  <textarea id="resource_description" name="resource_description" rows="3" placeholder="Describe the resource..." class="form-control" style="resize: vertical;"></textarea>
                </div>
                <button type="submit" id="add-resource-btn" name="add_resource" class="assign-button">
                  <i class="bi bi-plus-circle"></i> Add Resource
                </button>
              </form>
            </div>
            
            <!-- Resources List -->
            <h3 style="color: #5c4d3c; margin-bottom: 20px; font-size: 1.2rem;">Available Resources</h3>
            <?php if (empty($resources)): ?>
              <div class="text-center" style="padding: 40px; background: #f8f9fa; border-radius: 10px;">
                <i class="bi bi-folder-x" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                <p style="color: #666;">No resources added yet. Add your first resource above.</p>
              </div>
            <?php else: ?>
              <div class="row">
                <?php foreach ($resources as $resource): ?>
                  <div class="col-md-6 col-lg-4 mb-3">
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #5c4d3c; height: 100%;">
                      <div style="font-size: 2rem; color: #5c4d3c; margin-bottom: 10px;">
                        <i class="bi bi-file-earmark-text"></i>
                      </div>
                      <h4 style="color: #333; margin-bottom: 10px; font-size: 1.1rem;">
                        <?= htmlspecialchars($resource['title']) ?>
                      </h4>
                      <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">
                        <?= htmlspecialchars($resource['description'] ?? 'No description') ?>
                      </p>
                      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                        <span style="background: #f8f9fa; color: #5c4d3c; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                          <?= htmlspecialchars($resource['category']) ?>
                        </span>
                        <span style="color: #999; font-size: 0.8rem;">
                          <?= date('M d, Y', strtotime($resource['created_at'])) ?>
                        </span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Organization Statistics Dashboard -->
    <section class="family-stats-section">
      <div class="container">
        <div class="row">
          <div class="col-12">
            <div class="stats-card">
              <h2 class="section-title">Organization Dashboard</h2>
              <div class="row stats-cards">
                <div class="col-md-3 col-sm-6">
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-info">
                      <h3>Total Missions</h3>
                      <p class="stat-number"><?= count($chores) ?></p>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-sm-6">
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-info">
                      <h3>Total Hours</h3>
                      <p class="stat-number"><?= number_format($total_org_hours) ?></p>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-sm-6">
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-info">
                      <h3>Volunteers</h3>
                      <p class="stat-number"><?= $total_volunteers ?></p>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-sm-6">
                  <div class="stat-card">
                    <div class="stat-icon">
                      <i class="bi bi-trophy"></i>
                    </div>
                    <div class="stat-info">
                      <h3>Avg Hours</h3>
                      <p class="stat-number"><?= $total_volunteers > 0 ? number_format($total_org_hours / $total_volunteers, 1) : 0 ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Leaderboard Section -->
    <div id="leaderboard-section" class="container mt-5">
      <div class="row">
        <div class="col-12">
          <div class="chores-list-card">
            <h2 class="section-title"><i class="bi bi-trophy"></i> Organization Leaderboard</h2>
            <p class="text-muted mb-4">Top volunteers ranked by volunteer hours</p>
            <?php if (empty($leaderboard)): ?>
              <div class="text-center py-4">
                <p>No volunteers yet. Add volunteers to see the leaderboard!</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th style="width: 80px;">Rank</th>
                      <th>Volunteer</th>
                      <th>Hours</th>
                      <th>Level</th>
                      <th>Missions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($leaderboard as $volunteer): ?>
                      <tr>
                        <td>
                          <?php if ($volunteer['rank'] <= 3): ?>
                            <span style="font-size: 1.5rem;">
                              <?php if ($volunteer['rank'] == 1): ?>🥇
                              <?php elseif ($volunteer['rank'] == 2): ?>🥈
                              <?php else: ?>🥉<?php endif; ?>
                            </span>
                          <?php else: ?>
                            <strong>#<?= $volunteer['rank'] ?></strong>
                          <?php endif; ?>
                        </td>
                        <td>
                          <strong><?= htmlspecialchars($volunteer['member_name'] ?? $volunteer['member_email']) ?></strong><br>
                          <small class="text-muted"><?= htmlspecialchars($volunteer['member_email']) ?></small>
                        </td>
                        <td><span class="points-badge"><?= number_format($volunteer['hours']) ?> hrs</span></td>
                        <td><span class="points-badge" style="background: #5c4d3c;">Level <?= $volunteer['level'] ?></span></td>
                        <td><?= $volunteer['completed_missions'] ?> completed</td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- AI Mission Suggestions Section -->
    <div id="ai-suggestions-section" class="container mt-5">
      <div class="row">
        <div class="col-12">
          <div class="chore-assignment-card">
            <h2 class="section-title"><i class="bi bi-lightbulb"></i> AI Mission Suggestions</h2>
            <p class="text-muted mb-4">Get AI-powered mission suggestions based on your organization's activity</p>
            <form id="ai-suggestion-form" class="chore-form">
              <div class="form-group">
                <label for="ai_context">What kind of missions are you looking for?</label>
                <textarea id="ai_context" name="context" class="form-control" rows="3" 
                          placeholder="e.g., I need environmental missions, or suggest missions for education programs, or missions based on our history..."></textarea>
              </div>
              <button type="button" onclick="getAISuggestions()" class="assign-button">
                <i class="bi bi-magic"></i> Get AI Suggestions
              </button>
            </form>
            <div id="ai-suggestions-container" class="mt-4" style="display: none;">
              <h3 class="mb-3">Suggested Missions</h3>
              <div id="ai-suggestions-list"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Family Announcements and Quick Actions -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-lg-8">
          <div class="announcements-card">
            <h2 class="section-title">Family Announcements</h2>
            <div class="announcements-list">
              <div class="announcement-item">
                <div class="announcement-icon">
                  <i class="bi bi-megaphone"></i>
                </div>
                <div class="announcement-content">
                  <h3>Weekly Family Meeting</h3>
                  <p>Join us this Sunday at 6 PM for our weekly family meeting to discuss chores and rewards.</p>
                  <span class="announcement-date">Posted: <?= date('M d, Y') ?></span>
                </div>
              </div>
              <div class="announcement-item">
                <div class="announcement-icon">
                  <i class="bi bi-gift"></i>
                </div>
                <div class="announcement-content">
                  <h3>New Rewards Available</h3>
                  <p>Check out the new rewards in the shop! Save your points for exciting new prizes.</p>
                  <span class="announcement-date">Posted: <?= date('M d, Y', strtotime('-1 day')) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="quick-actions-card">
            <h2 class="section-title">Quick Actions</h2>
            <div class="quick-actions-list">
              <a href="#" class="quick-action-item">
                <i class="bi bi-calendar-plus"></i>
                <span>Schedule Recurring Chores</span>
              </a>
              <a href="points_shop.php" class="quick-action-item">
                <i class="bi bi-gift"></i>
                <span>View Achievements</span>
              </a>
              <a href="#leaderboard-section" class="quick-action-item">
                <i class="bi bi-trophy"></i>
                <span>View Leaderboard</span>
              </a>
              <a href="#ai-suggestions-section" class="quick-action-item">
                <i class="bi bi-lightbulb"></i>
                <span>AI Mission Suggestions</span>
              </a>
              <a href="#" class="quick-action-item">
                <i class="bi bi-graph-up"></i>
                <span>View Progress Report</span>
              </a>
              <a href="#" class="quick-action-item">
                <i class="bi bi-bell"></i>
                <span>Set Reminders</span>
              </a>
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
// Add Chore Function (AJAX)
async function addChore(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const choreName = document.getElementById('chore_name').value.trim();
    const chorePoints = document.getElementById('chore_points').value;
    const memberEmailsSelect = document.getElementById('member_emails');
    const selectedEmails = Array.from(memberEmailsSelect.selectedOptions).map(option => option.value);
    const addBtn = document.getElementById('add-chore-btn');
    const alertDiv = document.getElementById('chore-alert');
    
    if (!choreName || !chorePoints || selectedEmails.length === 0) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = '#fee';
        alertDiv.style.color = '#c33';
        alertDiv.style.border = '1px solid #fcc';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Please fill in all fields and select at least one volunteer.';
        return false;
    }
    
    addBtn.disabled = true;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Assigning...';
    
    try {
        const formData = new FormData();
        formData.append('chore_name', choreName);
        formData.append('chore_points', chorePoints);
        selectedEmails.forEach(email => {
            formData.append('member_emails[]', email);
        });
        
        const response = await fetch('famify.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        // Check if response is OK
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error. Response text:', text);
            alertDiv.style.display = 'block';
            alertDiv.style.background = '#fee';
            alertDiv.style.color = '#c33';
            alertDiv.style.border = '1px solid #fcc';
            alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Server error. Mission may have been added - please refresh the page.';
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Assign Chore';
            return false;
        }
        
        if (result.success) {
            alertDiv.style.display = 'block';
            alertDiv.style.background = '#d4edda';
            alertDiv.style.color = '#155724';
            alertDiv.style.border = '1px solid #c3e6cb';
            alertDiv.innerHTML = '<i class="bi bi-check-circle"></i> ' + result.message;
            document.getElementById('chore_name').value = '';
            document.getElementById('chore_points').value = '';
            // Clear selections but keep the select element
            Array.from(memberEmailsSelect.options).forEach(option => option.selected = false);
            
            // Reload the page after 1 second to show the new mission
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alertDiv.style.display = 'block';
            alertDiv.style.background = '#fee';
            alertDiv.style.color = '#c33';
            alertDiv.style.border = '1px solid #fcc';
            alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + (result.message || 'Error assigning mission. Please try again.');
        }
    } catch (error) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = '#fee';
        alertDiv.style.color = '#c33';
        alertDiv.style.border = '1px solid #fcc';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Error assigning mission. Please try again.';
    } finally {
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Assign Chore';
    }
    
    return false;
}

// Add Resource Function (AJAX)
async function addResource(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const title = document.getElementById('resource_title').value.trim();
    const description = document.getElementById('resource_description').value.trim();
    const category = document.getElementById('resource_category').value;
    const addBtn = document.getElementById('add-resource-btn');
    const alertDiv = document.getElementById('resource-alert');
    
    if (!title) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = '#fee';
        alertDiv.style.color = '#c33';
        alertDiv.style.border = '1px solid #fcc';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Please enter a resource title.';
        return false;
    }
    
    addBtn.disabled = true;
    addBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
    
    try {
        const formData = new FormData();
        formData.append('add_resource', '1');
        formData.append('resource_title', title);
        formData.append('resource_description', description);
        formData.append('resource_category', category);
        
        const response = await fetch('famify.php', {
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
            document.getElementById('resource_title').value = '';
            document.getElementById('resource_description').value = '';
            
            // Add the new resource to the list dynamically
            if (result.resource) {
                const resourcesContainer = document.querySelector('.row');
                if (resourcesContainer) {
                    const resourceHtml = `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #5c4d3c; height: 100%;">
                                <div style="font-size: 2rem; color: #5c4d3c; margin-bottom: 10px;">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <h4 style="color: #333; margin-bottom: 10px; font-size: 1.1rem;">
                                    ${result.resource.title}
                                </h4>
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">
                                    ${result.resource.description || 'No description'}
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                                    <span style="background: #f8f9fa; color: #5c4d3c; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">
                                        ${result.resource.category}
                                    </span>
                                    <span style="color: #999; font-size: 0.8rem;">
                                        ${new Date(result.resource.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                    resourcesContainer.insertAdjacentHTML('beforeend', resourceHtml);
                } else {
                    // If container doesn't exist, reload page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                // Reload page to show new resource
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            alertDiv.style.display = 'block';
            alertDiv.style.background = '#fee';
            alertDiv.style.color = '#c33';
            alertDiv.style.border = '1px solid #fcc';
            alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> ' + (result.message || 'Error adding resource. Please try again.');
        }
    } catch (error) {
        alertDiv.style.display = 'block';
        alertDiv.style.background = '#fee';
        alertDiv.style.color = '#c33';
        alertDiv.style.border = '1px solid #fcc';
        alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Error adding resource. Please try again.';
    } finally {
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Add Resource';
    }
    
    return false;
}
// Add refresh handling
if (window.location.search.includes('refresh=')) {
    // Remove the refresh parameter from the URL
    window.history.replaceState({}, document.title, window.location.pathname);
    // Force reload the page without cache
    window.location.reload(true);
}

const openModalBtn = document.getElementById("openModal");
if (openModalBtn) {
    openModalBtn.addEventListener("click", function() {
        const modal = document.getElementById("addMemberModal");
        if (modal) modal.style.display = "block";
    });
}

const closeBtn = document.querySelector(".close");
if (closeBtn) {
    closeBtn.addEventListener("click", function() {
        const modal = document.getElementById("addMemberModal");
        if (modal) modal.style.display = "none";
    });
}

// Image Preview Functionality
const memberImageInput = document.getElementById("memberImage");
if (memberImageInput) {
    memberImageInput.addEventListener("change", function(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const imagePreview = document.getElementById("imagePreview");
            if (imagePreview) {
                imagePreview.src = reader.result;
                imagePreview.style.display = "block";
            }
        };
        reader.readAsDataURL(event.target.files[0]);
    });
}
</script>
<!-- JavaScript to handle modal display -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("addMemberModal");
        const closeBtn = document.querySelector(".close");
        const signUpButton = document.querySelector(".button-submit");

        if (signUpButton && modal) {
            signUpButton.addEventListener("click", function (event) {
                event.preventDefault();
                modal.style.display = "flex";
            });
        }

        if (closeBtn && modal) {
            closeBtn.addEventListener("click", function () {
                modal.style.display = "none";
            });
        }

        if (modal) {
            window.addEventListener("click", function (event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        }
    });
</script>

<script>
  // Get the modal
  const editModal = document.getElementById("editChoreModal");
  if (editModal) {
    const span = document.getElementsByClassName("close");
    if (span && span.length > 0) {
      span[0].onclick = function() {
        editModal.style.display = "none";
      };
    }

    // Function to edit chore
    window.editChore = function(chore) {
      const editId = document.getElementById("edit_chore_id");
      const editName = document.getElementById("edit_chore_name");
      const editPoints = document.getElementById("edit_chore_points");
      const editEmail = document.getElementById("edit_member_email");
      
      if (editId) editId.value = chore.id || '';
      if (editName) editName.value = chore.chore_name || '';
      if (editPoints) editPoints.value = chore.points || '';
      if (editEmail) editEmail.value = chore.member_email || '';
      editModal.style.display = "block";
    };

    // Close modal when clicking (x)
    if (span && span.length > 0) {
      span[0].onclick = function() {
        editModal.style.display = "none";
      };
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target == editModal) {
        editModal.style.display = "none";
      }
    });
  }
</script>

<?php
function getTopPerformer($chores) {
    $memberPoints = [];
    foreach ($chores as $chore) {
        $email = $chore['member_email'];
        if (!isset($memberPoints[$email])) {
            $memberPoints[$email] = 0;
        }
        $memberPoints[$email] += $chore['points'];
    }
    
    if (empty($memberPoints)) {
        return "No data";
    }
    
    arsort($memberPoints);
    $topMember = array_key_first($memberPoints);
    return explode('@', $topMember)[0]; // Return just the username part
}
?>

<script>
// Add this new function to handle verifications
async function handleVerification(verificationId, choreId, memberEmail, points, action) {
    try {
        const formData = new FormData();
        formData.append('verification_id', verificationId);
        formData.append('chore_id', choreId);
        formData.append('member_email', memberEmail);
        formData.append('points', points);
        formData.append('action', action);

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
            // Show success notification
            alert(result.message);
            // Remove the verification card from the UI
            const verificationCard = document.querySelector(`[data-verification-id="${verificationId}"]`);
            if (verificationCard) {
                verificationCard.remove();
            }
            // Reload the page to update stats
            window.location.reload();
        } else {
            // Show error notification
            alert(result.message || 'An error occurred');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while processing the verification');
    }
}

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
    button.innerHTML = '<span class="loading-spinner"></span> Getting suggestions...';
    list.innerHTML = '<p class="text-center">Loading suggestions...</p>';
    container.style.display = 'block';
    
    try {
        const GEMINI_KEY = 'AIzaSyAVD5YiIAilUzdm8x_CGKCMYI1Vmamd6TI';
        const GEMINI_MODEL = 'gemini-2.5-flash';
        const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_KEY}`;
        
        const totalOrgHours = <?= $total_org_hours ?>;
        const totalVolunteers = <?= $total_volunteers ?>;
        const aiContext = `You are a volunteer mission suggestion assistant. The organization has ${totalOrgHours} total volunteer hours and ${totalVolunteers} volunteers. User request: ${context}. Please suggest 3-5 specific volunteer missions. For each mission, provide: 1) Mission name, 2) Brief description, 3) Estimated hours (1-10), 4) Category. Format as JSON array with fields: name, description, hours, category.`;
        
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
                suggestions = JSON.parse(jsonMatch[0]);
            } else {
                // Parse text manually
                const lines = text.split('\n').filter(l => l.trim());
                let currentSuggestion = {};
                lines.forEach(line => {
                    if (line.match(/mission|volunteer|community|help/i)) {
                        if (currentSuggestion.name) {
                            suggestions.push(currentSuggestion);
                            currentSuggestion = {};
                        }
                        currentSuggestion.name = line.substring(0, 50);
                        currentSuggestion.description = line;
                        currentSuggestion.hours = parseInt(line.match(/\d+/)?.[0]) || 2;
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
            const safeName = (suggestion.name || 'Mission').replace(/'/g, "\\'");
            const safeDesc = (suggestion.description || '').replace(/'/g, "\\'");
            item.innerHTML = `
                <div class="suggestion-header">
                    <h4 class="suggestion-name">${suggestion.name || 'Mission'}</h4>
                    <span class="suggestion-category">${suggestion.category || 'Community Service'}</span>
                </div>
                <p class="suggestion-description">${suggestion.description || 'A great volunteer opportunity'}</p>
                <div class="suggestion-footer">
                    <span class="suggestion-hours"><i class="bi bi-clock"></i> ${suggestion.hours || 2} hours</span>
                    <button class="use-suggestion-btn" onclick="useSuggestion('${safeName}', ${suggestion.hours || 2}, '${safeDesc}')">
                        <i class="bi bi-plus-circle"></i> Use This Mission
                    </button>
                </div>
            `;
            list.appendChild(item);
        });
        
    } catch (error) {
        console.error('Error:', error);
        list.innerHTML = '<p class="text-center text-danger">Error getting suggestions. Please try again.</p>';
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-magic"></i> Get AI Suggestions';
    }
}

function useSuggestion(name, hours, description) {
    document.getElementById('chore_name').value = name;
    document.getElementById('chore_points').value = hours;
    document.getElementById('chore_name').scrollIntoView({ behavior: 'smooth', block: 'center' });
    alert('Mission details filled in! Please select a volunteer and submit.');
}
</script>
</body>

</html>
</html>
</html>
</html>
</html>