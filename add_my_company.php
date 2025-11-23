<?php
session_start();
include 'config.php';

// Allow access without login - anyone can add an organization
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['email']);

$user_email = $is_logged_in ? $_SESSION['email'] : null;
$message = "";
$message_type = "";

// Handle adding company/organization
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_company'])) {
    $company_name = htmlspecialchars(trim($_POST['company_name']));
    $company_email = filter_var(trim($_POST['company_email']), FILTER_SANITIZE_EMAIL);
    $company_phone = trim($_POST['company_phone']);
    $company_password = trim($_POST['company_password']);
    $company_description = htmlspecialchars(trim($_POST['company_description']));
    
    if (!empty($company_name) && filter_var($company_email, FILTER_VALIDATE_EMAIL) && !empty($company_password) && strlen($company_password) >= 8) {
        // Check if company already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $company_email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $hashed_password = password_hash($company_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $company_email, $hashed_password, $company_phone);
            
            if ($stmt->execute()) {
                $message = "Company/Organization added successfully! You can now log in as an organization administrator.";
                $message_type = "success";
            } else {
                $message = "Error adding company: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Company with this email already exists!";
            $message_type = "error";
        }
        $check->close();
    } else {
        if (empty($company_name)) {
            $message = "Company name is required.";
        } elseif (!filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif (empty($company_password)) {
            $message = "Password is required.";
        } elseif (strlen($company_password) < 8) {
            $message = "Password must be at least 8 characters long.";
        } else {
            $message = "Please fill in all required fields correctly.";
        }
        $message_type = "error";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Your Company - VolunteerHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header .sitename {
            color: #333 !important;
        }

        .navmenu a {
            color: #333 !important;
        }

        .navmenu a:hover,
        .navmenu a.active {
            color: #FFD3B5 !important;
        }

        .form-wrapper {
            max-width: 500px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .form-box {
            max-width: 100%;
            background: #f1f7fe;
            overflow: hidden;
            border-radius: 16px;
            color: #010101;
        }

        .form {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 32px 24px 24px;
            gap: 16px;
            text-align: center;
        }

        .title {
            font-weight: bold;
            font-size: 1.6rem;
            color: #333;
        }

        .subtitle {
            font-size: 1rem;
            color: #666;
        }

        .form-container {
            overflow: hidden;
            border-radius: 8px;
            background-color: #fff;
            margin: 0;
            width: 100%;
        }

        .input, .textarea {
            background: none;
            border: 0;
            outline: 0;
            height: 40px;
            width: 100%;
            border-bottom: 1px solid #eee;
            font-size: .9rem;
            padding: 8px 15px;
            font-family: 'Poppins', sans-serif;
        }

        .textarea {
            height: auto;
            min-height: 80px;
            resize: vertical;
        }

        .input:last-child, .textarea:last-child {
            border-bottom: 0;
        }

        .form button {
            background-color: #FFD3B5;
            color: #333;
            border: 0;
            border-radius: 24px;
            padding: 10px 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .3s ease;
        }

        .form button:hover {
            background-color: #FFAAA5;
        }

        .form-section {
            padding: 16px;
            font-size: .85rem;
            background-color: #e0ecfb;
            box-shadow: rgb(0 0 0 / 8%) 0 -1px;
        }

        .form-section a {
            font-weight: bold;
            color: #0066ff;
            transition: color .3s ease;
        }

        .form-section a:hover {
            color: #005ce6;
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #FFD3B5;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            color: #FFAAA5;
        }
    </style>
</head>
<body>
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.html" class="logo d-flex align-items-center">
                <h1 class="sitename">VolunteerHub</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <?php if ($is_logged_in): ?>
                        <li><a href="member.php">Volunteer Dashboard</a></li>
                        <li><a href="browse_directory.php">Browse Directory</a></li>
                        <li><a href="add_my_company.php" class="active">Add Company</a></li>
                        <li><a href="add_my_volunteer.php">Add Volunteer</a></li>
                        <li><a href="account.php">Your Account</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="browse_directory.php">Browse Directory</a></li>
                        <li><a href="add_my_company.php" class="active">Add Company</a></li>
                        <li><a href="signin1.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="form-wrapper">
            <a href="browse_directory.php" class="back-link">← Back to Directory</a>

            <div class="form-box">
                <form method="POST" action="" class="form">
                    <div class="title">Add Your Company/Organization</div>
                    <div class="subtitle">Create your organization account</div>

                    <?php if ($message): ?>
                        <div class="message <?= $message_type ?>" style="text-align: left; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <input type="text" name="company_name" class="input" placeholder="Company/Organization Name *" required>
                    </div>

                    <div class="form-container">
                        <input type="email" name="company_email" class="input" placeholder="Company Email *" required>
                    </div>

                    <div class="form-container">
                        <input type="text" name="company_phone" class="input" placeholder="Company Phone">
                    </div>

                    <div class="form-container">
                        <input type="password" name="company_password" class="input" placeholder="Password (min 8 characters) *" required minlength="8">
                    </div>

                    <div class="form-container">
                        <textarea name="company_description" class="textarea" placeholder="Company Description"></textarea>
                    </div>

                    <button type="submit" name="add_company">Add Company</button>

                    <div class="form-section">
                        <small style="color: #666;">Password must be at least 8 characters long</small>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
