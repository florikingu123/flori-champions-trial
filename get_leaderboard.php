<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$member_email = $_SESSION['email'];

// Get filter type
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'organization';

// Get user's organization info
$user_stmt = $conn->prepare("SELECT managers_email FROM family WHERE member_email = ?");
if (!$user_stmt) {
    echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
    exit();
}
$user_stmt->bind_param("s", $member_email);
if (!$user_stmt->execute()) {
    echo json_encode(['error' => 'Failed to execute statement: ' . $user_stmt->error]);
    exit();
}
$user_result = $user_stmt->get_result();
if ($user_result->num_rows === 0) {
    echo json_encode(['error' => 'User not found']);
    exit();
}
$user_data = $user_result->fetch_assoc();
$manager_email = $user_data['managers_email'] ?? null;
$user_stmt->close();

if ($filter !== 'global' && empty($manager_email)) {
    echo json_encode(['error' => 'User has no organization']);
    exit();
}

// Level calculation function
function calculateLevel($hours) {
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

if ($filter === 'global') {
    // Get global leaderboard
    $stmt = $conn->prepare("
        SELECT f.member_name, f.member_email, f.points as hours,
               COUNT(DISTINCT cv.id) as completed_missions
        FROM family f
        LEFT JOIN chore_verifications cv ON f.member_email = cv.member_email AND cv.status = 'approved'
        GROUP BY f.id, f.member_name, f.member_email, f.points
        ORDER BY f.points DESC, completed_missions DESC
        LIMIT 50
    ");
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Failed to execute statement: ' . $stmt->error]);
        exit();
    }
    $result = $stmt->get_result();
    $leaderboard = [];
    $user_rank = null;
    $rank = 1;
    while ($row = $result->fetch_assoc()) {
        $row['level'] = calculateLevel($row['hours']);
        $row['rank'] = $rank;
        if ($row['member_email'] === $member_email) {
            $user_rank = $rank;
        }
        $leaderboard[] = $row;
        $rank++;
    }
    $stmt->close();
} else {
    // Get organization leaderboard
    $stmt = $conn->prepare("
        SELECT f.member_name, f.member_email, f.points as hours,
               COUNT(DISTINCT cv.id) as completed_missions
        FROM family f
        LEFT JOIN chore_verifications cv ON f.member_email = cv.member_email AND cv.status = 'approved'
        WHERE f.managers_email = ?
        GROUP BY f.id, f.member_name, f.member_email, f.points
        ORDER BY f.points DESC, completed_missions DESC
        LIMIT 10
    ");
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("s", $manager_email);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Failed to execute statement: ' . $stmt->error]);
        exit();
    }
    $result = $stmt->get_result();
    $leaderboard = [];
    $user_rank = null;
    $rank = 1;
    while ($row = $result->fetch_assoc()) {
        $row['level'] = calculateLevel($row['hours']);
        $row['rank'] = $rank;
        if ($row['member_email'] === $member_email) {
            $user_rank = $rank;
        }
        $leaderboard[] = $row;
        $rank++;
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'leaderboard' => $leaderboard,
    'user_rank' => $user_rank,
    'filter' => $filter
]);

$conn->close();
?>

