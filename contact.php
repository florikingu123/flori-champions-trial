<?php
// Database connection
$servername = "localhost";  // Update with your server info
$username = "root";         // Update with your DB username
$password = "";             // Update with your DB password
$dbname = "famify";         // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('Your message has been sent. Thank you!');</script>";
    } else {
        echo "<script>alert('Error: Could not send message.');</script>";
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
