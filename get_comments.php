<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$chore_id = isset($_GET['chore_id']) ? intval($_GET['chore_id']) : 0;

if ($chore_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid chore ID']);
    exit();
}

// Fetch comments for this mission
$stmt = $conn->prepare("
    SELECT mc.*, f.member_name 
    FROM mission_comments mc 
    JOIN family f ON mc.member_email = f.member_email 
    WHERE mc.chore_id = ? 
    ORDER BY mc.created_at ASC
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error preparing query: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $chore_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'comments' => $comments]);
?>

