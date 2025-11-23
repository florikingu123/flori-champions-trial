<?php
session_start();
include 'config.php';

// Allow access without login - but check if logged in for admin status
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['email']);
$user_email = $is_logged_in ? $_SESSION['email'] : null;
$message = "";
$message_type = "";

// Check if user is an organization admin (only if logged in)
$is_admin = false;
if ($is_logged_in) {
    $check_admin = $conn->prepare("SELECT is_admin FROM users WHERE email = ?");
    $check_admin->bind_param("s", $user_email);
    $check_admin->execute();
    $admin_result = $check_admin->get_result();
    $admin_data = $admin_result->fetch_assoc();
    $check_admin->close();
    $is_admin = $admin_data && $admin_data['is_admin'] == 1;
}

// Handle adding volunteer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_volunteer'])) {
    $volunteer_name = htmlspecialchars(trim($_POST['volunteer_name']));
    $volunteer_email = filter_var(trim($_POST['volunteer_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_phone = trim($_POST['volunteer_phone']);
    $volunteer_password = trim($_POST['volunteer_password']);
    $organization_email = $is_admin ? $user_email : filter_var(trim($_POST['organization_email']), FILTER_SANITIZE_EMAIL);
    
    if (!empty($volunteer_name) && filter_var($volunteer_email, FILTER_VALIDATE_EMAIL) && !empty($volunteer_password) && strlen($volunteer_password) >= 8 && !empty($organization_email)) {
        // Check if volunteer already exists
        $check = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
        $check->bind_param("s", $volunteer_email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            // Create user account
            $hashed_password = password_hash($volunteer_password, PASSWORD_BCRYPT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())");
            $user_stmt->bind_param("sss", $volunteer_email, $hashed_password, $volunteer_phone);
            $user_stmt->execute();
            $user_stmt->close();
            
            // Add to family table
            $hashed_pass = password_hash($volunteer_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass, points) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("ssss", $organization_email, $volunteer_name, $volunteer_email, $hashed_pass);
            
            if ($stmt->execute()) {
                $message = "Volunteer added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding volunteer: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Volunteer with this email already exists!";
            $message_type = "error";
        }
        $check->close();
    } else {
        if (empty($volunteer_name)) {
            $message = "Volunteer name is required.";
        } elseif (!filter_var($volunteer_email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif (empty($volunteer_password)) {
            $message = "Password is required.";
        } elseif (strlen($volunteer_password) < 8) {
            $message = "Password must be at least 8 characters long.";
        } elseif (empty($organization_email)) {
            $message = "Please select an organization.";
        } else {
            $message = "Please fill in all required fields correctly.";
        }
        $message_type = "error";
    }
}

// Get all organizations for dropdown (if not admin)
$organizations = [];
if (!$is_admin) {
    $orgs_stmt = $conn->prepare("SELECT email FROM users WHERE is_admin = 1 ORDER BY email");
    $orgs_stmt->execute();
    $orgs_result = $orgs_stmt->get_result();
    while ($row = $orgs_result->fetch_assoc()) {
        $organizations[] = $row['email'];
    }
    $orgs_stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Volunteer - VolunteerHub</title>
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

        .input, .select {
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

        .select {
            cursor: pointer;
        }

        .input:last-child, .select:last-child {
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
                        <li><a href="add_my_company.php">Add Company</a></li>
                        <li><a href="add_my_volunteer.php" class="active">Add Volunteer</a></li>
                        <li><a href="account.php">Your Account</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="browse_directory.php">Browse Directory</a></li>
                        <li><a href="add_my_company.php">Add Company</a></li>
                        <li><a href="add_my_volunteer.php" class="active">Add Volunteer</a></li>
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
                    <div class="title">Add Volunteer</div>
                    <div class="subtitle">Create a new volunteer account</div>

                    <?php if ($message): ?>
                        <div class="message <?= $message_type ?>" style="text-align: left; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <input type="text" name="volunteer_name" class="input" placeholder="Volunteer Name *" required>
                    </div>

                    <div class="form-container">
                        <input type="email" name="volunteer_email" class="input" placeholder="Volunteer Email *" required>
                    </div>

                    <div class="form-container">
                        <input type="text" name="volunteer_phone" class="input" placeholder="Volunteer Phone">
                    </div>

                    <div class="form-container">
                        <input type="password" name="volunteer_password" class="input" placeholder="Password (min 8 characters) *" required minlength="8">
                    </div>

                    <?php if (!$is_logged_in || !$is_admin): ?>
                        <div class="form-container">
                            <select name="organization_email" class="select" required>
                                <option value="">Select Organization *</option>
                                <?php if (empty($organizations)): ?>
                                    <option value="" disabled>No organizations available</option>
                                <?php else: ?>
                                    <?php foreach ($organizations as $org): ?>
                                        <option value="<?= htmlspecialchars($org) ?>"><?= htmlspecialchars($org) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php if (empty($organizations)): ?>
                            <div class="form-section">
                                <small style="color: #dc3545;">⚠ No organizations found. Please create an organization first.</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <input type="hidden" name="organization_email" value="<?= htmlspecialchars($user_email) ?>">
                        <div class="form-container">
                            <input type="text" class="input" value="<?= htmlspecialchars($user_email) ?>" disabled style="background: #f5f5f5;">
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="add_volunteer">Add Volunteer</button>

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
