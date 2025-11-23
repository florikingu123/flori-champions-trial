<?php
require_once 'config.php';

// Check if rewards exist
$check_query = "SELECT * FROM rewards";
$result = $conn->query($check_query);

if ($result->num_rows > 0) {
    echo "<h2>Existing Rewards:</h2>";
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - " . $row['name'] . " (" . $row['points_required'] . " points)<br>";
    }
} else {
    echo "<h2>No rewards found. Adding sample rewards...</h2>";
    
    // Create default image if it doesn't exist
    if (!file_exists('uploads/default_reward.png')) {
        require_once 'create_default_image.php';
    }
    
    // Sample rewards
    $rewards = [
        ['Movie Night', 100, 'Watch a movie of your choice'],
        ['Extra Screen Time', 50, '30 minutes extra screen time'],
        ['Special Dessert', 75, 'Choose your favorite dessert'],
        ['Game Time', 150, 'Play your favorite game for 1 hour']
    ];
    
    $stmt = $conn->prepare("INSERT INTO rewards (name, points_required, description, image) VALUES (?, ?, ?, ?)");
    
    foreach ($rewards as $reward) {
        $stmt->bind_param("siss", $reward[0], $reward[1], $reward[2], 'uploads/default_reward.png');
        if ($stmt->execute()) {
            echo "Added reward: " . $reward[0] . "<br>";
        } else {
            echo "Error adding reward " . $reward[0] . ": " . $stmt->error . "<br>";
        }
    }
    
    $stmt->close();
}

$conn->close();
?> 