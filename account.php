<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in, if not, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: signin1.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get the user_id from the session

// Fetch the user data from the database
$stmt = $conn->prepare("SELECT email, number FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: signup.php");
    exit;
}

$message = "";
$message_type = "";

// Handle form submission for updating account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $new_email = trim($_POST["email"]);
    $new_number = trim($_POST["number"]);
    $new_password = trim($_POST["password"]); // Optional password update

    // Validate input fields
    if (empty($new_email) || empty($new_number)) {
        $message = "Email and phone number are required.";
        $message_type = "error";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "error";
    } elseif (!preg_match("/^\d{6,20}$/", $new_number)) { // Phone number validation
        $message = "Invalid phone number format.";
        $message_type = "error";
    } else {
        if (!empty($new_password)) {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $new_password)) {
                $message = "Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
                $message_type = "error";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_sql = "UPDATE users SET email = ?, number = ?, password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssi", $new_email, $new_number, $hashed_password, $user_id);
            }
        } else {
            $update_sql = "UPDATE users SET email = ?, number = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $new_email, $new_number, $user_id);
        }

        // Execute the update statement
        if ($update_stmt->execute()) {
            $message = "Account updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating account: " . $conn->error;
            $message_type = "error";
        }
        $update_stmt->close();
    }
}

// Fetch the updated user data
$stmt = $conn->prepare("SELECT email, number FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .form-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .form {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .form:hover {
            transform: translateY(-5px);
        }

        .form-title {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .input-group {
            margin-bottom: 25px;
        }

        .input-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .inputForm {
            position: relative;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .inputForm:focus-within {
            border-color: #FFD3B5;
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 211, 181, 0.1);
        }

        .input {
            width: 100%;
            padding: 15px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #333;
        }

        .input:focus {
            outline: none;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            user-select: none;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .button-submit {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            color: #333;
        }

        .button-submit.primary {
            background: #FFD3B5;
        }

        .button-submit.primary:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }

        .button-submit.secondary {
            background: #f8f9fa;
            border: 2px solid #e1e1e1;
        }

        .button-submit.secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        @media (max-width: 480px) {
            .form {
                padding: 30px 20px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>
<body>
    <div class="form-container">
        <form class="form" action="" method="POST">
            <div class="form-title">Edit Your Account</div>

            <?php if ($message): ?>
                <div class="message <?= htmlspecialchars($message_type) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label>Email Address</label>
                <div class="inputForm">
                    <input class="input" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
                </div>
            </div>

            <div class="input-group">
                <label>Phone Number</label>
                <div class="inputForm">
                    <input class="input" type="text" name="number" value="<?= htmlspecialchars($user['number'] ?? '') ?>" required />
                </div>
            </div>

            <div class="input-group">
                <label>New Password (Optional)</label>
                <div class="inputForm">
                    <input id="password" class="input" type="password" name="password" placeholder="Enter new password" />
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            <div class="button-group">
                <button class="button-submit primary" type="submit" name="update">Update Account</button>
                <button class="button-submit secondary" type="button" onclick="window.location.href='famify.php'">Back to Home</button>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            var passwordInput = document.getElementById("password");
            passwordInput.type = passwordInput.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>



