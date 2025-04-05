<?php
include 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit();
    }

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT email FROM newsletter_subscribers WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Email already subscribed
        echo json_encode(["status" => "error", "message" => "This email is already subscribed."]);
    } else {
        // Proceed with insertion if email does not exist
        $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Subscription successful!"]);
        } else {
            // Database error during insertion
            echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
        }

        $stmt->close();
    }

    $checkStmt->close();
}

$conn->close();
?>
