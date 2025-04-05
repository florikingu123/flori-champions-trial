<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "famify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$status = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $managers_email = filter_var(trim($_POST['managers_email']), FILTER_VALIDATE_EMAIL);
    $member_name = htmlspecialchars(trim($_POST['member_name']));
    $member_email = filter_var(trim($_POST['member_email']), FILTER_VALIDATE_EMAIL);
    $member_pass = trim($_POST['member_pass']);

    if (!$managers_email || !$member_email || empty($member_name) || empty($member_pass)) {
        $status = "error";
        $message = "Invalid input. Please check your details.";
    } else {
        $hashed_pass = password_hash($member_pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $managers_email, $member_name, $member_email, $hashed_pass);
            if ($stmt->execute()) {
                $status = "success";
                $message = "Family member added successfully!";
            } else {
                $status = "error";
                $message = "Error: Could not add family member.";
            }
            $stmt->close();
        } else {
            $status = "error";
            $message = "Database error. Please try again.";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Family Member</title>
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
            font-family: "Segoe UI", Roboto, sans-serif;
            transition: background 0.6s ease-in-out;
        }
        .form:hover {
            background: linear-gradient(45deg, #4A4A4A, #6C757D);
        }
        .form-title {
            text-align: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .message {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: gray;
            margin-bottom: 10px;
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
            background-color: white;
        }
        .input {
            margin-left: 10px;
            border: none;
            width: 100%;
            height: 100%;
            border-radius: 10rem;
        }
        .input:focus {
            outline: none;
        }
        .button-submit {
            padding: 15px;
            background: transparent;
            border: 2px solid;
            border-radius: 10em;
            color: white;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: inset 0 0 0 0 #6C757D;
            transition: ease-out 0.5s;
        }
        .button-submit:hover {
            box-shadow: inset 0 -100px 0 0 #4A4A4A;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form class="form" action="" method="POST">
            <div class="form-title">Add Family Member</div>
            
            <?php if (!empty($message) && $status === 'success'): ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            
            <div class="flex-column">
                <label>Email (Manager)</label>
            </div>
            <div class="inputForm">
                <input placeholder="Enter Manager's Email" class="input" type="email" name="managers_email" required />
            </div>
            
            <div class="flex-column">
                <label>Member Name</label>
            </div>
            <div class="inputForm">
                <input placeholder="Enter Member's Name" class="input" type="text" name="member_name" required />
            </div>
            
            <div class="flex-column">
                <label>Member Email</label>
            </div>
            <div class="inputForm">
                <input placeholder="Enter Member's Email" class="input" type="email" name="member_email" required />
            </div>
            
            <div class="flex-column">
                <label>Password</label>
            </div>
            <div class="inputForm">
                <input placeholder="Enter Password" class="input" type="password" name="member_pass" required />
            </div>
            
            <button class="button-submit" type="submit">Add Family Member</button>
                    <button class="button-submit" type="button" onclick="window.location.href='famify.php'">Go Back</button>
        </form>
    </div>
</body>
</html>
