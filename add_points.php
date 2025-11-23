<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Ensure game stats columns exist (MySQL doesn't support IF NOT EXISTS in ALTER TABLE, so we check first)
$check_columns = $conn->query("SHOW COLUMNS FROM family LIKE 'games_played'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE family ADD COLUMN games_played INT DEFAULT 0");
}
$check_columns = $conn->query("SHOW COLUMNS FROM family LIKE 'games_won'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE family ADD COLUMN games_won INT DEFAULT 0");
}
$check_columns = $conn->query("SHOW COLUMNS FROM family LIKE 'game_points'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE family ADD COLUMN game_points INT DEFAULT 0");
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$points = isset($data['points']) ? intval($data['points']) : 0;
$game_won = isset($data['game_won']) ? (bool)$data['game_won'] : false;
$increment_games = isset($data['increment_games']) ? (bool)$data['increment_games'] : true;

if ($points < 0) {
    echo json_encode(['error' => 'Invalid points value']);
    exit();
}

$email = $_SESSION['email'];

// Update points and game stats in the database
if ($increment_games) {
    if ($game_won) {
        $sql = "UPDATE family SET 
                points = points + ?, 
                game_points = game_points + ?,
                games_played = games_played + 1,
                games_won = games_won + 1
                WHERE member_email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("iis", $points, $points, $email);
    } else {
        $sql = "UPDATE family SET 
                points = points + ?, 
                game_points = game_points + ?,
                games_played = games_played + 1
                WHERE member_email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("iis", $points, $points, $email);
    }
} else {
    // Just update points without incrementing games (for manual point additions)
    $stmt = $conn->prepare("UPDATE family SET points = points + ?, game_points = game_points + ? WHERE member_email = ?");
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iis", $points, $points, $email);
}

if ($stmt->execute()) {
    // Get updated stats
    $stats_stmt = $conn->prepare("SELECT points, COALESCE(games_played, 0) as games_played, COALESCE(games_won, 0) as games_won, COALESCE(game_points, 0) as game_points FROM family WHERE member_email = ?");
    $stats_stmt->bind_param("s", $email);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();
    
    $win_rate = $stats['games_played'] > 0 ? round(($stats['games_won'] / $stats['games_played']) * 100) : 0;
    
    echo json_encode([
        'success' => true, 
        'points_added' => $points,
        'total_points' => $stats['points'],
        'game_points' => $stats['game_points'],
        'games_played' => $stats['games_played'],
        'games_won' => $stats['games_won'],
        'win_rate' => $win_rate
    ]);
} else {
    echo json_encode(['error' => 'Failed to add points: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 