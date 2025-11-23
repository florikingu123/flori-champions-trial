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
        <form class="form" action="" method="POST">
            <div class="form-title">Sign In to Your Account</div>

            <div class="input-group">
                <label>Email Address</label>
                <div class="inputForm">
                    <input placeholder="Enter your Email" class="input" type="email" name="email" required />
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <div class="inputForm">
                    <input placeholder="Enter your Password" class="input" type="password" name="password" required />
                </div>
            </div>

            <button class="button-submit" type="submit">Sign In</button>

            <p class="p">Don't have an account? <a href="signup.php" class="span">Sign Up</a></p>
        </form>
    </div>
</body>
</html>
