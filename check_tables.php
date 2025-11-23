<?php
require_once 'config.php';

// Check if rewards table exists
$check_rewards = $conn->query("SHOW TABLES LIKE 'rewards'");
if ($check_rewards->num_rows == 0) {
    // Create rewards table
    $create_rewards = "CREATE TABLE rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL,
        points_required INT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if (!$conn->query($create_rewards)) {
        die("Error creating rewards table: " . $conn->error);
    }
    echo "Created rewards table<br>";
} else {
    // Check if description column exists
    $check_column = $conn->query("SHOW COLUMNS FROM rewards LIKE 'description'");
    if ($check_column->num_rows == 0) {
        // Add description column
        $alter_rewards = "ALTER TABLE rewards ADD COLUMN description TEXT AFTER points_required";
        if (!$conn->query($alter_rewards)) {
            die("Error adding description column: " . $conn->error);
        }
        echo "Added description column to rewards table<br>";
    }
}

// Check if assigned_rewards table exists
$check_assigned = $conn->query("SHOW TABLES LIKE 'assigned_rewards'");
if ($check_assigned->num_rows == 0) {
    // Create assigned_rewards table
    $create_assigned = "CREATE TABLE assigned_rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_email VARCHAR(255) NOT NULL,
        reward_id INT NOT NULL,
        status ENUM('pending', 'redeemed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (member_email, reward_id)
    )";
    if (!$conn->query($create_assigned)) {
        die("Error creating assigned_rewards table: " . $conn->error);
    }
    echo "Created assigned_rewards table<br>";
}

// Check if family table exists
$check_family = $conn->query("SHOW TABLES LIKE 'family'");
if ($check_family->num_rows == 0) {
    // Create family table
    $create_family = "CREATE TABLE family (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_email VARCHAR(255) NOT NULL UNIQUE,
        managers_email VARCHAR(255) NOT NULL,
        points INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if (!$conn->query($create_family)) {
        die("Error creating family table: " . $conn->error);
    }
    echo "Created family table<br>";
}

echo "All necessary tables are set up!";
?> 