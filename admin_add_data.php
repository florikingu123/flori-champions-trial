<?php
session_start();
include 'config.php';

// Check if user is logged in as organization admin
if (!isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$admin_email = $_SESSION['email'];

// Check if user is an organization admin
$check_admin = $conn->prepare("SELECT is_admin FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$admin_result = $check_admin->get_result();
$admin_data = $admin_result->fetch_assoc();
$check_admin->close();

if (!$admin_data || $admin_data['is_admin'] != 1) {
    die("Access denied: Only organization administrators can access this page.");
}

$message = "";
$message_type = "";

// Handle adding new volunteer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_volunteer'])) {
    $volunteer_name = htmlspecialchars(trim($_POST['volunteer_name']));
    $volunteer_email = filter_var(trim($_POST['volunteer_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_phone = trim($_POST['volunteer_phone']);
    $volunteer_password = trim($_POST['volunteer_password']);
    $organization_email = trim($_POST['organization_email']);
    
    if (!empty($volunteer_name) && filter_var($volunteer_email, FILTER_VALIDATE_EMAIL) && !empty($volunteer_password)) {
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
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Handle adding new organization
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_organization'])) {
    $org_email = filter_var(trim($_POST['org_email']), FILTER_SANITIZE_EMAIL);
    $org_phone = trim($_POST['org_phone']);
    $org_password = trim($_POST['org_password']);
    
    if (filter_var($org_email, FILTER_VALIDATE_EMAIL) && !empty($org_password)) {
        // Check if organization already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $org_email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $hashed_password = password_hash($org_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $org_email, $hashed_password, $org_phone);
            
            if ($stmt->execute()) {
                $message = "Organization added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding organization: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Organization with this email already exists!";
            $message_type = "error";
        }
        $check->close();
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Get all organizations for dropdown
$orgs_stmt = $conn->prepare("SELECT email FROM users WHERE is_admin = 1 ORDER BY email");
$orgs_stmt->execute();
$orgs_result = $orgs_stmt->get_result();
$organizations = [];
while ($row = $orgs_result->fetch_assoc()) {
    $organizations[] = $row['email'];
}
$orgs_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Volunteers & Organizations - VolunteerHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 100px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-header h1 {
            color: #333;
            font-size: 32px;
            font-weight: 600;
        }

        .form-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFD3B5;
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

        .input, .select {
            width: 100%;
            padding: 15px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        .input:focus, .select:focus {
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
            background: #FFD3B5;
            color: #333;
        }

        .button-submit:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
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
                    <li><a href="manager_dashboard.php">Dashboard</a></li>
                    <li><a href="admin_add_data.php" class="active">Add Data</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Panel - Add Volunteers & Organizations</h1>
                <p>Add new volunteers and organizations to the platform</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <a href="manager_dashboard.php" class="back-link">← Back to Dashboard</a>

            <!-- Add Volunteer Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Volunteer</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Volunteer Name *</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_name" class="input" placeholder="Enter volunteer name" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Email *</label>
                        <div class="inputForm">
                            <input type="email" name="volunteer_email" class="input" placeholder="Enter volunteer email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Phone</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="volunteer_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Assign to Organization *</label>
                        <div class="inputForm">
                            <select name="organization_email" class="select" required>
                                <option value="">Select Organization</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?= htmlspecialchars($org) ?>"><?= htmlspecialchars($org) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_volunteer" class="button-submit">Add Volunteer</button>
                </form>
            </div>

            <!-- Add Organization Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Organization</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Organization Email *</label>
                        <div class="inputForm">
                            <input type="email" name="org_email" class="input" placeholder="Enter organization email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Organization Phone</label>
                        <div class="inputForm">
                            <input type="text" name="org_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="org_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <button type="submit" name="add_organization" class="button-submit">Add Organization</button>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

session_start();
include 'config.php';

// Check if user is logged in as organization admin
if (!isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$admin_email = $_SESSION['email'];

// Check if user is an organization admin
$check_admin = $conn->prepare("SELECT is_admin FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$admin_result = $check_admin->get_result();
$admin_data = $admin_result->fetch_assoc();
$check_admin->close();

if (!$admin_data || $admin_data['is_admin'] != 1) {
    die("Access denied: Only organization administrators can access this page.");
}

$message = "";
$message_type = "";

// Handle adding new volunteer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_volunteer'])) {
    $volunteer_name = htmlspecialchars(trim($_POST['volunteer_name']));
    $volunteer_email = filter_var(trim($_POST['volunteer_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_phone = trim($_POST['volunteer_phone']);
    $volunteer_password = trim($_POST['volunteer_password']);
    $organization_email = trim($_POST['organization_email']);
    
    if (!empty($volunteer_name) && filter_var($volunteer_email, FILTER_VALIDATE_EMAIL) && !empty($volunteer_password)) {
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
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Handle adding new organization
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_organization'])) {
    $org_email = filter_var(trim($_POST['org_email']), FILTER_SANITIZE_EMAIL);
    $org_phone = trim($_POST['org_phone']);
    $org_password = trim($_POST['org_password']);
    
    if (filter_var($org_email, FILTER_VALIDATE_EMAIL) && !empty($org_password)) {
        // Check if organization already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $org_email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $hashed_password = password_hash($org_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $org_email, $hashed_password, $org_phone);
            
            if ($stmt->execute()) {
                $message = "Organization added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding organization: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Organization with this email already exists!";
            $message_type = "error";
        }
        $check->close();
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Get all organizations for dropdown
$orgs_stmt = $conn->prepare("SELECT email FROM users WHERE is_admin = 1 ORDER BY email");
$orgs_stmt->execute();
$orgs_result = $orgs_stmt->get_result();
$organizations = [];
while ($row = $orgs_result->fetch_assoc()) {
    $organizations[] = $row['email'];
}
$orgs_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Volunteers & Organizations - VolunteerHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 100px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-header h1 {
            color: #333;
            font-size: 32px;
            font-weight: 600;
        }

        .form-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFD3B5;
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

        .input, .select {
            width: 100%;
            padding: 15px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        .input:focus, .select:focus {
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
            background: #FFD3B5;
            color: #333;
        }

        .button-submit:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
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
                    <li><a href="manager_dashboard.php">Dashboard</a></li>
                    <li><a href="admin_add_data.php" class="active">Add Data</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Panel - Add Volunteers & Organizations</h1>
                <p>Add new volunteers and organizations to the platform</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <a href="manager_dashboard.php" class="back-link">← Back to Dashboard</a>

            <!-- Add Volunteer Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Volunteer</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Volunteer Name *</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_name" class="input" placeholder="Enter volunteer name" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Email *</label>
                        <div class="inputForm">
                            <input type="email" name="volunteer_email" class="input" placeholder="Enter volunteer email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Phone</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="volunteer_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Assign to Organization *</label>
                        <div class="inputForm">
                            <select name="organization_email" class="select" required>
                                <option value="">Select Organization</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?= htmlspecialchars($org) ?>"><?= htmlspecialchars($org) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_volunteer" class="button-submit">Add Volunteer</button>
                </form>
            </div>

            <!-- Add Organization Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Organization</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Organization Email *</label>
                        <div class="inputForm">
                            <input type="email" name="org_email" class="input" placeholder="Enter organization email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Organization Phone</label>
                        <div class="inputForm">
                            <input type="text" name="org_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="org_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <button type="submit" name="add_organization" class="button-submit">Add Organization</button>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

session_start();
include 'config.php';

// Check if user is logged in as organization admin
if (!isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$admin_email = $_SESSION['email'];

// Check if user is an organization admin
$check_admin = $conn->prepare("SELECT is_admin FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$admin_result = $check_admin->get_result();
$admin_data = $admin_result->fetch_assoc();
$check_admin->close();

if (!$admin_data || $admin_data['is_admin'] != 1) {
    die("Access denied: Only organization administrators can access this page.");
}

$message = "";
$message_type = "";

// Handle adding new volunteer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_volunteer'])) {
    $volunteer_name = htmlspecialchars(trim($_POST['volunteer_name']));
    $volunteer_email = filter_var(trim($_POST['volunteer_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_phone = trim($_POST['volunteer_phone']);
    $volunteer_password = trim($_POST['volunteer_password']);
    $organization_email = trim($_POST['organization_email']);
    
    if (!empty($volunteer_name) && filter_var($volunteer_email, FILTER_VALIDATE_EMAIL) && !empty($volunteer_password)) {
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
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Handle adding new organization
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_organization'])) {
    $org_email = filter_var(trim($_POST['org_email']), FILTER_SANITIZE_EMAIL);
    $org_phone = trim($_POST['org_phone']);
    $org_password = trim($_POST['org_password']);
    
    if (filter_var($org_email, FILTER_VALIDATE_EMAIL) && !empty($org_password)) {
        // Check if organization already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $org_email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $hashed_password = password_hash($org_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $org_email, $hashed_password, $org_phone);
            
            if ($stmt->execute()) {
                $message = "Organization added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding organization: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Organization with this email already exists!";
            $message_type = "error";
        }
        $check->close();
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Get all organizations for dropdown
$orgs_stmt = $conn->prepare("SELECT email FROM users WHERE is_admin = 1 ORDER BY email");
$orgs_stmt->execute();
$orgs_result = $orgs_stmt->get_result();
$organizations = [];
while ($row = $orgs_result->fetch_assoc()) {
    $organizations[] = $row['email'];
}
$orgs_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Volunteers & Organizations - VolunteerHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 100px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-header h1 {
            color: #333;
            font-size: 32px;
            font-weight: 600;
        }

        .form-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFD3B5;
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

        .input, .select {
            width: 100%;
            padding: 15px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        .input:focus, .select:focus {
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
            background: #FFD3B5;
            color: #333;
        }

        .button-submit:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
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
                    <li><a href="manager_dashboard.php">Dashboard</a></li>
                    <li><a href="admin_add_data.php" class="active">Add Data</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Panel - Add Volunteers & Organizations</h1>
                <p>Add new volunteers and organizations to the platform</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <a href="manager_dashboard.php" class="back-link">← Back to Dashboard</a>

            <!-- Add Volunteer Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Volunteer</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Volunteer Name *</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_name" class="input" placeholder="Enter volunteer name" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Email *</label>
                        <div class="inputForm">
                            <input type="email" name="volunteer_email" class="input" placeholder="Enter volunteer email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Phone</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="volunteer_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Assign to Organization *</label>
                        <div class="inputForm">
                            <select name="organization_email" class="select" required>
                                <option value="">Select Organization</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?= htmlspecialchars($org) ?>"><?= htmlspecialchars($org) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_volunteer" class="button-submit">Add Volunteer</button>
                </form>
            </div>

            <!-- Add Organization Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Organization</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Organization Email *</label>
                        <div class="inputForm">
                            <input type="email" name="org_email" class="input" placeholder="Enter organization email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Organization Phone</label>
                        <div class="inputForm">
                            <input type="text" name="org_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="org_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <button type="submit" name="add_organization" class="button-submit">Add Organization</button>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

session_start();
include 'config.php';

// Check if user is logged in as organization admin
if (!isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$admin_email = $_SESSION['email'];

// Check if user is an organization admin
$check_admin = $conn->prepare("SELECT is_admin FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$admin_result = $check_admin->get_result();
$admin_data = $admin_result->fetch_assoc();
$check_admin->close();

if (!$admin_data || $admin_data['is_admin'] != 1) {
    die("Access denied: Only organization administrators can access this page.");
}

$message = "";
$message_type = "";

// Handle adding new volunteer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_volunteer'])) {
    $volunteer_name = htmlspecialchars(trim($_POST['volunteer_name']));
    $volunteer_email = filter_var(trim($_POST['volunteer_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_phone = trim($_POST['volunteer_phone']);
    $volunteer_password = trim($_POST['volunteer_password']);
    $organization_email = trim($_POST['organization_email']);
    
    if (!empty($volunteer_name) && filter_var($volunteer_email, FILTER_VALIDATE_EMAIL) && !empty($volunteer_password)) {
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
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Handle adding new organization
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_organization'])) {
    $org_email = filter_var(trim($_POST['org_email']), FILTER_SANITIZE_EMAIL);
    $org_phone = trim($_POST['org_phone']);
    $org_password = trim($_POST['org_password']);
    
    if (filter_var($org_email, FILTER_VALIDATE_EMAIL) && !empty($org_password)) {
        // Check if organization already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $org_email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $hashed_password = password_hash($org_password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $org_email, $hashed_password, $org_phone);
            
            if ($stmt->execute()) {
                $message = "Organization added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding organization: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Organization with this email already exists!";
            $message_type = "error";
        }
        $check->close();
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = "error";
    }
}

// Get all organizations for dropdown
$orgs_stmt = $conn->prepare("SELECT email FROM users WHERE is_admin = 1 ORDER BY email");
$orgs_stmt->execute();
$orgs_result = $orgs_stmt->get_result();
$organizations = [];
while ($row = $orgs_result->fetch_assoc()) {
    $organizations[] = $row['email'];
}
$orgs_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Volunteers & Organizations - VolunteerHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            padding-top: 100px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-header h1 {
            color: #333;
            font-size: 32px;
            font-weight: 600;
        }

        .form-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFD3B5;
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

        .input, .select {
            width: 100%;
            padding: 15px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        .input:focus, .select:focus {
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
            background: #FFD3B5;
            color: #333;
        }

        .button-submit:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
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
                    <li><a href="manager_dashboard.php">Dashboard</a></li>
                    <li><a href="admin_add_data.php" class="active">Add Data</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Admin Panel - Add Volunteers & Organizations</h1>
                <p>Add new volunteers and organizations to the platform</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <a href="manager_dashboard.php" class="back-link">← Back to Dashboard</a>

            <!-- Add Volunteer Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Volunteer</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Volunteer Name *</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_name" class="input" placeholder="Enter volunteer name" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Email *</label>
                        <div class="inputForm">
                            <input type="email" name="volunteer_email" class="input" placeholder="Enter volunteer email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Volunteer Phone</label>
                        <div class="inputForm">
                            <input type="text" name="volunteer_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="volunteer_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Assign to Organization *</label>
                        <div class="inputForm">
                            <select name="organization_email" class="select" required>
                                <option value="">Select Organization</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?= htmlspecialchars($org) ?>"><?= htmlspecialchars($org) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_volunteer" class="button-submit">Add Volunteer</button>
                </form>
            </div>

            <!-- Add Organization Form -->
            <div class="form-section">
                <h2 class="section-title">Add New Organization</h2>
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Organization Email *</label>
                        <div class="inputForm">
                            <input type="email" name="org_email" class="input" placeholder="Enter organization email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Organization Phone</label>
                        <div class="inputForm">
                            <input type="text" name="org_phone" class="input" placeholder="Enter phone number">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password *</label>
                        <div class="inputForm">
                            <input type="password" name="org_password" class="input" placeholder="Enter password" required>
                        </div>
                    </div>

                    <button type="submit" name="add_organization" class="button-submit">Add Organization</button>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>





