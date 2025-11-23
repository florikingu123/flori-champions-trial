<?php
include 'config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("Location: contact.html?error=missing_fields");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.html?error=invalid_email");
        exit();
    }

    // Sanitize for database
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    // Check if contact table exists, if not create it
    $table_check = $conn->query("SHOW TABLES LIKE 'contact'");
    if ($table_check->num_rows == 0) {
        $create_table = "CREATE TABLE IF NOT EXISTS contact (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($create_table);
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        // Execute the statement
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?success=1");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?error=db_error");
            exit();
        }
    } else {
        $conn->close();
        header("Location: contact.html?error=db_error");
        exit();
    }
} else {
    // If not POST, redirect to contact page
    header("Location: contact.html");
    exit();
}
?>


    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        // Execute the statement
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?success=1");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?error=db_error");
            exit();
        }
    } else {
        $conn->close();
        header("Location: contact.html?error=db_error");
        exit();
    }
} else {
    // If not POST, redirect to contact page
    header("Location: contact.html");
    exit();
}
?>


    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        // Execute the statement
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?success=1");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?error=db_error");
            exit();
        }
    } else {
        $conn->close();
        header("Location: contact.html?error=db_error");
        exit();
    }
} else {
    // If not POST, redirect to contact page
    header("Location: contact.html");
    exit();
}
?>


    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        // Execute the statement
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?success=1");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: contact.html?error=db_error");
            exit();
        }
    } else {
        $conn->close();
        header("Location: contact.html?error=db_error");
        exit();
    }
} else {
    // If not POST, redirect to contact page
    header("Location: contact.html");
    exit();
}
?>
