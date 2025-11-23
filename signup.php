<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable error reporting

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $number = filter_var(trim($_POST["number"]), FILTER_SANITIZE_STRING);
    $is_admin = isset($_POST["is_admin"]) ? 1 : 0;

    // Form data validation (removed debug output for production)

    // Validate required fields
    if (empty($email) || empty($password) || empty($number)) {
        die("All fields are required.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Validate password strength
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        die("Password does not meet security requirements.");
    }

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$check_stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        die("Email already exists.");
    }
    $check_stmt->close();

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert user into database
    $sql = "INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssi", $email, $hashed_password, $number, $is_admin);

    if ($stmt->execute()) {
        echo "<script>alert('User registered successfully!'); window.location.href='famify.php';</script>";
        exit();
    } else {
        die("Execution failed: " . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up for Your Organization - VolunteerHub</title>
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

        .password-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .button-submit {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            color: #333;
            background: #FFD3B5;
            margin-top: 10px;
        }

        .button-submit:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }

        .p {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }

        .span {
            color: #FFD3B5;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .span:hover {
            color: #FFAAA5;
        }

        @media (max-width: 480px) {
            .form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form class="form" action="signup.php" method="POST" onsubmit="validateForm(event)">
            <div class="form-title">Sign Up for Your Organization</div>

            <div class="input-group">
                <label>Email Address</label>
                <div class="inputForm">
                    <input placeholder="Enter your Email" class="input" type="email" name="email" required />
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <div class="inputForm">
                    <input id="password" placeholder="Enter your Password" class="input" type="password" name="password" required />
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>
                <div class="password-error" id="password-error">
                    Password must be at least 8 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.
                </div>
            </div>

            <div class="input-group">
                <label>Phone Number</label>
                <div class="inputForm">
                    <input placeholder="Enter your Number" class="input" type="text" name="number" required />
                </div>
            </div>

            <button type="submit" class="button-submit">Sign Up</button>

            <p class="p">Already have an account? <a href="signin.php" class="span">Sign In</a></p>
        </form>
    </div>

    <script>
        function validatePassword() {
            const password = document.getElementById("password").value;
            const passwordError = document.getElementById("password-error");
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            if (!regex.test(password)) {
                passwordError.style.display = "block";
                return false;
            } else {
                passwordError.style.display = "none";
                return true;
            }
        }

        function togglePassword() {
            const passwordInput = document.getElementById("password");
            passwordInput.type = passwordInput.type === "password" ? "text" : "password";
        }

        function validateForm(event) {
            if (!validatePassword()) {
                event.preventDefault();
                alert("Password does not meet security requirements.");
            }
        }
    </script>
</body>
</html>
