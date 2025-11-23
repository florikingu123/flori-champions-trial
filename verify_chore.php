<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

// Create mission_verifications table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS chore_verifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    chore_id INT(11) NOT NULL,
    member_email VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    chore_name VARCHAR(255),
    points INT(11),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (chore_id) REFERENCES chores(id) ON DELETE CASCADE
)";
$conn->query($create_table_sql);

// Modify existing foreign key constraint if table exists
$modify_constraint_sql = "ALTER TABLE chore_verifications DROP FOREIGN KEY IF EXISTS chore_verifications_ibfk_1";
$conn->query($modify_constraint_sql);

$add_constraint_sql = "ALTER TABLE chore_verifications ADD CONSTRAINT chore_verifications_ibfk_1 FOREIGN KEY (chore_id) REFERENCES chores(id) ON DELETE CASCADE";
$conn->query($add_constraint_sql);

// Create organization table if it doesn't exist
$create_family_sql = "CREATE TABLE IF NOT EXISTS family (
    id INT(11) NOT NULL AUTO_INCREMENT,
    member_email VARCHAR(255) NOT NULL,
    points INT(11) DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY member_email (member_email)
)";
$conn->query($create_family_sql);

// Handle photo submission
if (isset($_POST['photo_data'])) {
    if (!isset($_POST['chore_id']) || !isset($_POST['member_email']) || !isset($_POST['photo_data'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: Missing required data'
        ]);
        exit();
    }

    $chore_id = intval($_POST['chore_id']);
    $member_email = $_POST['member_email'];
    $photo_data = $_POST['photo_data'];

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/verifications/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid('verification_') . '.jpg';
    $filepath = $upload_dir . $filename;

    // Decode and save the base64 image
    $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
    $photo_data = base64_decode($photo_data);
    if (file_put_contents($filepath, $photo_data)) {
        // Insert verification record into database
        $stmt = $conn->prepare("INSERT INTO chore_verifications (chore_id, member_email, photo_path, status) VALUES (?, ?, ?, 'pending')");
        if ($stmt === false) {
            unlink($filepath);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error preparing database query: ' . $conn->error
            ]);
            exit();
        }
        
        $stmt->bind_param("iss", $chore_id, $member_email, $filepath);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Photo submitted successfully! Waiting for organization administrator verification.',
                'chore_id' => $chore_id
            ]);
        } else {
            unlink($filepath); // Delete the uploaded file if database insertion fails
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error saving verification: ' . $stmt->error
            ]);
        }
        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error saving photo. Please try again.'
        ]);
    }
    exit();
}

// Handle approval/rejection
if (isset($_POST['action']) && isset($_POST['verification_id'])) {
    if (!isset($_POST['chore_id']) || !isset($_POST['member_email']) || !isset($_POST['points'])) {
        die("Error: Missing required data");
    }

    $verification_id = intval($_POST['verification_id']);
    $chore_id = intval($_POST['chore_id']);
    $member_email = $_POST['member_email'];
    $points = intval($_POST['points']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Start transaction
        $conn->begin_transaction();

        try {
            // First get chore details
            $stmt = $conn->prepare("SELECT chore_name, points FROM chores WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Error preparing chore query: " . $conn->error);
            }
            $stmt->bind_param("i", $chore_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $chore = $result->fetch_assoc();
            $stmt->close();

            if (!$chore) {
                throw new Exception("Chore not found");
            }

            // First update the verification status
            $stmt = $conn->prepare("UPDATE chore_verifications SET status = 'approved', verified_at = NOW() WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Error preparing verification update query: " . $conn->error);
            }
            $stmt->bind_param("i", $verification_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update verification status: " . $stmt->error);
            }
            $stmt->close();

            // Add points to member
            $stmt = $conn->prepare("UPDATE family SET points = points + ? WHERE member_email = ?");
            if ($stmt === false) {
                throw new Exception("Error preparing points update query: " . $conn->error);
            }
            $stmt->bind_param("is", $points, $member_email);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update points: " . $stmt->error);
            }
            $stmt->close();

            // Update chore status to 'completed'
            $stmt = $conn->prepare("UPDATE chores SET status = 'completed' WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Error preparing chore status update query: " . $conn->error);
            }
            $stmt->bind_param("i", $chore_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update chore status: " . $stmt->error);
            }
            $stmt->close();

            // DO NOT DELETE THE CHORE - Keep it for historical records
            // The chore will remain but marked as completed via the status field
            // This allows us to track completed missions properly

            // Commit transaction
            $conn->commit();
            
            // Return JSON response
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode([
                'success' => true,
                'message' => 'Chore approved and points awarded!',
                'chore_id' => $chore_id
            ]);
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode([
                'success' => false,
                'message' => 'Error processing approval: ' . $e->getMessage()
            ]);
            exit();
        }
    } elseif ($action === 'reject') {
        // Update verification status to rejected
        $stmt = $conn->prepare("UPDATE chore_verifications SET status = 'rejected', verified_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $verification_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update chore status to 'rejected'
            $stmt = $conn->prepare("UPDATE chores SET status = 'rejected' WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $chore_id);
                $stmt->execute();
                $stmt->close();
            }
            
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode([
                'success' => true,
                'message' => 'Chore verification rejected.',
                'verification_id' => $verification_id
            ]);
        } else {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode([
                'success' => false,
                'message' => 'Error rejecting verification: ' . $stmt->error
            ]);
        }
        $stmt->close();
        exit();
    }

    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit();
}

$conn->close();
?> 