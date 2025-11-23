<?php
session_start();
include 'config.php';

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    die("Not logged in");
}

$user_email = $_SESSION['email'];
$family_id = $_GET['family_id'] ?? 0;

if (!$family_id) {
    die("No organization ID provided");
}

// Verify user belongs to this organization
$check_stmt = $conn->prepare("
    SELECT * FROM family 
    WHERE id = ? AND (member_email = ? OR managers_email = ?)
");
$check_stmt->bind_param("iss", $family_id, $user_email, $user_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if (!$check_result->fetch_assoc()) {
    die("Not authorized");
}
$check_stmt->close();

// Fetch recent messages
$messages = [];
$stmt = $conn->prepare("
    SELECT fc.*, u.name as sender_name 
    FROM family_chat fc 
    JOIN users u ON fc.sender_email = u.email 
    WHERE fc.family_id = ? 
    ORDER BY fc.created_at DESC 
    LIMIT 50
");
$stmt->bind_param("i", $family_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

$conn->close();

// Output messages as HTML
foreach (array_reverse($messages) as $message): ?>
    <div class="message <?= $message['sender_email'] === $user_email ? 'sent' : 'received' ?>">
        <div class="message-header">
            <strong><?= htmlspecialchars($message['sender_name']) ?></strong>
            <span class="text-muted ms-2">
                <?= date('M d, Y H:i', strtotime($message['created_at'])) ?>
            </span>
        </div>
        <div class="message-content">
            <?= htmlspecialchars($message['message']) ?>
        </div>
    </div>
<?php endforeach; ?> 