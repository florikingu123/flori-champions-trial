<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reward_id']) && isset($_POST['member_email'])) {
    $reward_id = intval($_POST['reward_id']);
    $member_email = trim($_POST['member_email']);
    
    // Update the reward status to redeemed
    $sql = "UPDATE assigned_rewards SET status = 'redeemed' WHERE reward_id = ? AND member_email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("is", $reward_id, $member_email);
        if ($stmt->execute()) {
            echo "<script>alert('Reward marked as redeemed successfully!'); window.location.href='points_shop.php';</script>";
        } else {
            echo "<script>alert('Error updating reward status: " . $stmt->error . "'); window.location.href='points_shop.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error preparing statement: " . $conn->error . "'); window.location.href='points_shop.php';</script>";
    }
} else {
    header("Location: points_shop.php");
    exit();
}
?> 