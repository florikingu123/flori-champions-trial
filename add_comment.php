<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$chore_id = isset($data['chore_id']) ? intval($data['chore_id']) : 0;
$comment_text = isset($data['comment_text']) ? trim($data['comment_text']) : '';
$member_email = $_SESSION['email'];

if ($chore_id <= 0 || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Invalid chore ID or empty comment']);
    exit();
}

// Verify the mission belongs to the user or is in their organization
$stmt = $conn->prepare("SELECT c.id FROM chores c JOIN family f ON c.member_email = f.member_email WHERE c.id = ? AND (c.member_email = ? OR f.managers_email = (SELECT managers_email FROM family WHERE member_email = ?))");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing query: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iss", $chore_id, $member_email, $member_email);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Mission not found or access denied']);
    exit();
}

// Insert comment
$stmt = $conn->prepare("INSERT INTO mission_comments (chore_id, member_email, comment_text) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing insert: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iss", $chore_id, $member_email, $comment_text);
if ($stmt->execute()) {
    $comment_id = $stmt->insert_id;
    
    // Fetch the comment with user info
    $fetch_stmt = $conn->prepare("SELECT mc.*, f.member_name FROM mission_comments mc JOIN family f ON mc.member_email = f.member_email WHERE mc.id = ?");
    if ($fetch_stmt) {
        $fetch_stmt->bind_param("i", $comment_id);
        $fetch_stmt->execute();
        $comment_result = $fetch_stmt->get_result();
        $comment = $comment_result->fetch_assoc();
        $fetch_stmt->close();
        
        echo json_encode([
            'success' => true,
            'comment' => $comment
        ]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding comment: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>

