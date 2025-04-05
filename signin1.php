<?php
session_start();

include 'config.php'; // Database connection

// Check if connection is valid
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $family_manager_email = trim($_POST["family_manager_email"]);

    if (empty($email) || empty($password) || empty($family_manager_email)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        // Check if the family manager exists in the users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            die("SQL Error (Family Manager Check): " . $conn->error);
        }
        $stmt->bind_param("s", $family_manager_email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($manager_id);
            $stmt->fetch();
            
            // Check user details in the family table
            $stmt_family = $conn->prepare("SELECT id, member_pass FROM family WHERE member_email = ? AND managers_email = ?");
            if (!$stmt_family) {
                die("SQL Error (Family Table Check): " . $conn->error);
            }
            $stmt_family->bind_param("ss", $email, $family_manager_email);
            $stmt_family->execute();
            $stmt_family->store_result();
            
            if ($stmt_family->num_rows > 0) {
                $stmt_family->bind_result($user_id, $hashed_password);
                $stmt_family->fetch();
                
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["email"] = $email;
                    $_SESSION["manager_email"] = $family_manager_email;
                    
                    header("Location: member.php");
                    exit();
                } else {
                    echo "<script>alert('Invalid email or password!');</script>";
                }
            } else {
                echo "<script>alert('No family member record found!');</script>";
            }
            $stmt_family->close();
        } else {
            echo "<script>alert('Family manager not found!');</script>";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In as a Member</title>
  <style>
    /* Center the form on the screen */
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

    .span {
      font-size: 14px;
      margin-left: 5px;
      color: white;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      transition: color 0.3s ease-in-out;
    }

    .span:hover {
      color: #cccccc;
    }

    .p {
      text-align: center;
      color: white;
      font-size: 14px;
      margin: 5px 0;
    }

    .button-submit {
      padding: 15px 30px;
      text-align: center;
      background: transparent;
      transition: ease-out 0.5s;
      border: 2px solid;
      border-radius: 10em;
      box-shadow: inset 0 0 0 0 #6C757D;
      margin: 20px 0 10px 0;
      color: white;
      font-size: 15px;
      font-weight: 500;
      height: 50px;
      width: 100%;
      cursor: pointer;
    }

    .button-submit:hover {
      color: white;
      box-shadow: inset 0 -100px 0 0 #4A4A4A;
    }
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
      align-self: flex-end;
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
      padding: 15px 30px;
      text-align: center;
      letter-spacing: 1px;
      text-decoration: none;
      background: transparent;
      transition: ease-out 0.5s;
      border: 2px solid;
      border-radius: 10em;
      box-shadow: inset 0 0 0 0 #6C757D;
      margin: 20px 0 10px 0;
      color: white;
      font-size: 15px;
      font-weight: 500;
      height: 50px;
      width: 100%;
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
      margin: 5px 0;
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
      <div class="form-title">Sign In as a Member</div>

      <div class="flex-column">
        <label>Email</label>
      </div>
      <div class="inputForm">
        <input placeholder="Enter your Email" class="input" type="email" name="email" required />
      </div>

      <div class="flex-column">
        <label>Password</label>
      </div>
      <div class="inputForm">
        <input placeholder="Enter your Password" class="input" type="password" name="password" required />
      </div>
      
      <div class="flex-column">
        <label>Family Manager Email</label>
      </div>
      <div class="inputForm">
        <input placeholder="Enter Family Manager's Email" class="input" type="email" name="family_manager_email" required />
      </div>

      <button class="button-submit" type="submit">Sign In</button>

      <p class="p">Don't have an account? Tell your family member to make you one.</p>
    </form>
  </div>
</body>
</html>
