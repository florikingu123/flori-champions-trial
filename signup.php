

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

    // Debugging: Check if form data is received
    var_dump($_POST);

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
  <title>Sign Up for Your Family</title>
  
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
</head>
<body>

<div class="form-container">
    <form class="form" action="signup.php" method="POST" onsubmit="validateForm(event)">
      <div class="form-title">Sign Up for Your Family</div>

      <div class="flex-column">
        <label>Email</label>
      </div>
      <div class="inputForm">
        <input placeholder="Enter your Email" class="input" type="email" name="email" required />
      </div>

      <div class="flex-column">
        <label>Password</label>
      </div>
      <div class="inputForm password-container">
        <input id="password" placeholder="Enter your Password" class="input" type="password" name="password" required />
        <span class="toggle-password" onclick="togglePassword()">👁️</span>
      </div>
      <div class="password-error" id="password-error" style="color: red; font-size: 12px; display: none;">
        Password must be at least 8 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.
      </div>

      <div class="flex-column">
        <label>Number</label>
      </div>
      <div class="inputForm">
        <input placeholder="Enter your Number" class="input" type="text" name="number" required />
      </div>

      <button type="submit" class="button-submit">Sign Up</button>

      <p class="p">Already have an account? <a href="signin.php" class="span">Sign In</a></p>
    </form>
</div>

</body>
</html>
