<?php
require_once 'config.php';

// First, check if the rewards table exists
$check_table = $conn->query("SHOW TABLES LIKE 'rewards'");
if ($check_table->num_rows == 0) {
    // Create the rewards table if it doesn't exist
    $create_table = "CREATE TABLE rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL,
        points_required INT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        echo "Rewards table created successfully<br>";
    } else {
        die("Error creating rewards table: " . $conn->error);
    }
} else {
    echo "Rewards table exists<br>";
}

// Try to insert a test reward
$test_name = "Test Reward";
$test_image = "test.jpg";
$test_points = 100;
$test_description = "This is a test reward";

$sql = "INSERT INTO rewards (name, image, points_required, description) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("ssis", $test_name, $test_image, $test_points, $test_description);

if ($stmt->execute()) {
    echo "Test reward inserted successfully<br>";
} else {
    echo "Error inserting test reward: " . $stmt->error . "<br>";
}

$stmt->close();

// Show all rewards in the table
$result = $conn->query("SELECT * FROM rewards");
if ($result) {
    echo "<br>Current rewards in database:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . ", Points: " . $row['points_required'] . "<br>";
    }
} else {
    echo "Error fetching rewards: " . $conn->error;
}
?> 