<?php
session_start();

include 'config.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        // ✅ Fetch user details, ensuring the account is not deleted
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $hashed_password);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                // ✅ Double-check that the user still exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    // ✅ Store session correctly
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["email"] = $email;
                    
                    header("Location: account.php");
                    exit();
                } else {
                    echo "<script>alert('This account no longer exists.'); window.location.href='signin.php';</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Invalid email or password!');</script>";
            }
        } else {
            echo "<script>alert('User not found!');</script>";
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
  <title>Sign In to Your Account</title>
  
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
    <form class="form T55" action="" method="POST">
      <div class="form-title T56">Sign In to Your Account</div>

      <div class="flex-column T57">
        <label class="T58">Email</label>
      </div>
      <div class="inputForm T59">
        <input placeholder="Enter your Email" class="input" type="email" name="email" required />
      </div>

      <div class="flex-column T60">
        <label class="T61">Password</label>
      </div>
      <div class="inputForm T62">
        <input placeholder="Enter your Password" class="input" type="password" name="password" required />
      </div>

      <button class="button-submit T66" type="submit">Sign In</button>

      <p class="p T67">Don't have an account? <a href="signup.php" class="span T68">Sign Up</a></p>
    </form>
  </div>

</body>
</html>
