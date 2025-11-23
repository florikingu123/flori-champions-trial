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
    $organization_admin_email = trim($_POST["family_manager_email"]);

    if (empty($email) || empty($password) || empty($organization_admin_email)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        // Check if the organization admin exists in the users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            die("SQL Error (Organization Admin Check): " . $conn->error);
        }
        $stmt->bind_param("s", $organization_admin_email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($manager_id);
            $stmt->fetch();
            
            // Check user details in the organization table
            $stmt_family = $conn->prepare("SELECT id, member_pass FROM family WHERE member_email = ? AND managers_email = ?");
            if (!$stmt_family) {
                die("SQL Error (Organization Table Check): " . $conn->error);
            }
            $stmt_family->bind_param("ss", $email, $organization_admin_email);
            $stmt_family->execute();
            $stmt_family->store_result();
            
            if ($stmt_family->num_rows > 0) {
                $stmt_family->bind_result($user_id, $hashed_password);
                $stmt_family->fetch();
                
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["email"] = $email;
                    $_SESSION["manager_email"] = $organization_admin_email;
                    
                    // Check if there's a redirect URL
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header("Location: " . $redirect);
                    } else {
                        header("Location: member.php");
                    }
                    exit();
                } else {
                    echo "<script>alert('Invalid email or password!');</script>";
                }
            } else {
                echo "<script>alert('No volunteer record found!');</script>";
            }
            $stmt_family->close();
        } else {
            echo "<script>alert('Organization administrator not found!');</script>";
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
    <title>Sign In as Volunteer - VolunteerHub</title>
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

        @media (max-width: 480px) {
            .form {
                padding: 30px 20px;
            }
        }
    </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>
<body>
    <div class="form-container">
        <form class="form" action="" method="POST">
            <div class="form-title">Sign In as Volunteer</div>

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
            
            <div class="input-group">
                <label>Organization Administrator Email</label>
                <div class="inputForm">
                    <input placeholder="Enter Organization Admin's Email" class="input" type="email" name="family_manager_email" required />
                </div>
            </div>

            <button class="button-submit" type="submit">Sign In</button>

            <p class="p">Don't have an account? Contact your organization administrator to add you.</p>
            
            <div style="text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Need to find an organization?</p>
                <p style="margin: 0 0 10px 0; color: #999; font-size: 12px;">Browse organizations and request an account - organization admins will create your account</p>
                <a href="browse_directory.php" style="display: inline-block; padding: 12px 30px; background: #FFD3B5; color: #333; text-decoration: none; font-weight: 500; font-size: 14px; border-radius: 10px; transition: all 0.3s ease;">Browse Directory →</a>
            </div>
        </form>
    </div>
</body>
</html>
