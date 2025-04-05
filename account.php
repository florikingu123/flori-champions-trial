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
    <style>
          body, html {
      height: 100%;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #f0f0f0;
    }

    .form-container {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
    }

    .form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: linear-gradient(45deg, #6C757D, #4A4A4A);
      padding: 30px;
      width: 450px;
      border-radius: 20px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
        Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
      transition: background 0.6s ease-in-out;
    }

    .form:hover {
      background: linear-gradient(45deg, #4A4A4A, #6C757D);
    }

    ::placeholder {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
        Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
      color: #6C757D;
    }

    .form button {
      align-self: center;
      background: #6C757D;
      color: white;
      border: 2px solid #4A4A4A;
    }

    .form button:hover {
      background: #4A4A4A;
      color: #fff;
      border: 2px solid #6C757D;
    }

    .form button:active {
      transform: scale(0.9);
    }

    .flex-column > label {
      color: white;
      font-weight: 600;
    }

    .inputForm {
      border: 1.5px solid #6C757D;
      border-radius: 10em;
      height: 50px;
      display: flex;
      align-items: center;
      padding-left: 10px;
      transition: border 0.3s ease-in-out;
      background-color: white;
    }

    .input {
      margin-left: 10px;
      border-radius: 10rem;
      border: none;
      width: 100%;
      height: 100%;
    }

    .input:focus {
      outline: none;
    }

    .inputForm:focus-within {
      border: 1.5px solid #4A4A4A;
    }

    .flex-row {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 10px;
      justify-content: space-between;
    }

    .flex-row > div > label {
      font-size: 14px;
      color: white;
      font-weight: 400;
    }

    .span {
      font-size: 14px;
      margin-left: 5px;
      color: white;
      font-weight: 500;
      cursor: pointer;
      text-decoration : none;
    }

    .button-submit {
      position: relative;
      display: inline-block;
      padding: 15px 10px 15px 10px;
      text-align: center;
      letter-spacing: 1px;
      text-decoration: none;
      background: transparent;
      transition: ease-out 0.5s;
      border: 2px solid;
      border-radius: 10em;
      box-shadow: inset 0 0 0 0 #6C757D;
      margin: 20px 0 0 0;
      color: white;
      font-size: 15px;
      font-weight: 500;
      height: 20px 20px 20px;
      width: 85%;
      cursor: pointer;
    }

    .button-submit:hover {
      color: white;
      box-shadow: inset 0 -100px 0 0 #4A4A4A;
    }

    .button-submit:active {
      transform: scale(0.9);
    }

    .p {
      text-align: center;
      color: white;
      font-size: 14px;
      margin: 10px 0 0 0;
    }

    .form-title {
      text-align: center;
      color: white;
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    </style>
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

        <div class="flex-column">
            <label>Email</label>
        </div>
        <div class="inputForm">
            <input class="input" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
        </div>

        <div class="flex-column">
            <label>Phone Number</label>
        </div>
        <div class="inputForm">
            <input class="input" type="text" name="number" value="<?= htmlspecialchars($user['number'] ?? '') ?>" required />
        </div>

        <div class="flex-column">
            <label>New Password (Optional)</label>
        </div>
        <div class="inputForm password-container">
            <input id="password" class="input" type="password" name="password" placeholder="Enter new password" />
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <button class="button-submit" type="submit" name="update">Update Account</button>

<button class="button-submit" type="button" onclick="window.location.href='famify.php'">Back to Home</button>



    </form>
</div>

<script>
function togglePassword() {
    var passwordInput = document.getElementById("password");
    passwordInput.type = passwordInput.type === "password" ? "text" : "password";
}

document.getElementById("goFamify").addEventListener("click", function() {
    window.location.href = "famify.php";
});
</script>

</body>
</html>
