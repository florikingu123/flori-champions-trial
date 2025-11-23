<?php
session_start();
include 'config.php';

// Allow access without login - volunteers need to see organizations before they can log in
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['email']);
$current_user_email = $is_logged_in ? $_SESSION['email'] : null;
$message = "";
$message_type = "";

// Handle adding volunteer request (works for both logged in and not logged in users)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_to_join'])) {
    $org_email = filter_var(trim($_POST['org_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_name = htmlspecialchars(trim($_POST['volunteer_name']));
    $volunteer_email = filter_var(trim($_POST['volunteer_email']), FILTER_SANITIZE_EMAIL);
    $volunteer_phone = trim($_POST['volunteer_phone']);
    $message_text = htmlspecialchars(trim($_POST['message']));
    
    if (filter_var($org_email, FILTER_VALIDATE_EMAIL) && filter_var($volunteer_email, FILTER_VALIDATE_EMAIL)) {
        // Create a join/account request
        $subject = $is_logged_in ? "Volunteer Join Request from " . $volunteer_name : "Volunteer Account Request from " . $volunteer_name;
        $body = $is_logged_in 
            ? "A volunteer wants to join your organization:\n\n"
            : "A new volunteer is requesting an account to join your organization:\n\n";
        $body .= "Name: " . $volunteer_name . "\n";
        $body .= "Email: " . $volunteer_email . "\n";
        $body .= "Phone: " . $volunteer_phone . "\n";
        $body .= "Message: " . $message_text . "\n\n";
        $body .= $is_logged_in 
            ? "Please add this volunteer to your organization."
            : "Please create an account for this volunteer and add them to your organization.";
        
        // For now, we'll just show a success message
        // In production, you'd send an email or save to database
        $message = $is_logged_in 
            ? "Join request sent successfully! The organization will contact you soon."
            : "Account request sent successfully! The organization administrator will create your account and contact you soon.";
        $message_type = "success";
    } else {
        $message = "Please enter valid email addresses.";
        $message_type = "error";
    }
}

// Fetch all volunteers (excluding current user if logged in)
$volunteers = [];
if ($is_logged_in) {
    $stmt = $conn->prepare("
        SELECT DISTINCT f.member_email, f.member_name, f.managers_email, u.number as phone
        FROM family f
        LEFT JOIN users u ON f.member_email = u.email
        WHERE f.member_email != ? AND f.member_email IS NOT NULL
        ORDER BY f.member_name ASC
    ");
    $stmt->bind_param("s", $current_user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $volunteers[] = $row;
    }
    $stmt->close();
} else {
    // Show all volunteers if not logged in
    $stmt = $conn->prepare("
        SELECT DISTINCT f.member_email, f.member_name, f.managers_email, u.number as phone
        FROM family f
        LEFT JOIN users u ON f.member_email = u.email
        WHERE f.member_email IS NOT NULL
        ORDER BY f.member_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $volunteers[] = $row;
    }
    $stmt->close();
}

// Fetch all organizations with full details (admin email is the organization email)
$organizations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT u.email as admin_email, u.email as organization_email, u.number as phone, COUNT(DISTINCT f.id) as volunteer_count
    FROM users u
    LEFT JOIN family f ON u.email = f.managers_email
    WHERE u.is_admin = 1
    GROUP BY u.email, u.number
    ORDER BY u.email ASC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $organizations[] = $row;
}
$stmt->close();

// Check if user has organization (only if logged in)
$has_org = false;
if ($is_logged_in) {
    $check_org = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
    $check_org->bind_param("s", $current_user_email);
    $check_org->execute();
    $org_result = $check_org->get_result();
    $has_org = $org_result->num_rows > 0;
    $check_org->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Volunteers & Organizations - VolunteerHub</title>
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

        .directory-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            color: #333;
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .directory-section {
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

        .search-box {
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #FFD3B5;
            box-shadow: 0 0 0 4px rgba(255, 211, 181, 0.1);
        }

        .directory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .directory-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e1e1e1;
            transition: all 0.3s ease;
        }

        .directory-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #FFD3B5;
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: #FFD3B5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: #333;
        }

        .card-title {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .card-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .card-info i {
            color: #FFD3B5;
            margin-right: 8px;
        }

        .contact-btn {
            width: 100%;
            padding: 12px;
            background: #FFD3B5;
            border: none;
            border-radius: 10px;
            color: #333;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .contact-btn:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            background: #FFD3B5;
            color: #333;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 10px;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 25px;
        }

        .modal-header h2 {
            color: #333;
            font-size: 24px;
            margin: 0;
        }

        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #333;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .inputForm {
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .inputForm:focus-within {
            border-color: #FFD3B5;
            background: white;
        }

        .input, .textarea {
            width: 100%;
            padding: 12px;
            border: none;
            background: transparent;
            font-size: 15px;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        .textarea {
            resize: vertical;
            min-height: 100px;
        }

        .input:focus, .textarea:focus {
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
            background: #FFD3B5;
            color: #333;
            transition: all 0.3s ease;
        }

        .button-submit:hover {
            background: #FFAAA5;
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
                        <li><a href="browse_directory.php" class="active">Browse Directory</a></li>
                        <li><a href="add_my_company.php">Add Company</a></li>
                        <li><a href="add_my_volunteer.php">Add Volunteer</a></li>
                        <li><a href="games.php">Engagement Zone</a></li>
                        <li><a href="account.php">Your Account</a></li>
                        <li><a href="rew.php">Achievements</a></li>
                        <li><a href="view_calendar.php">Events Calendar</a></li>
                        <li><a href="ai.php">AI</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="browse_directory.php" class="active">Browse Directory</a></li>
                        <li><a href="add_my_company.php">Add Company</a></li>
                        <li><a href="add_my_volunteer.php">Add Volunteer</a></li>
                        <li><a href="signin1.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="directory-container">
            <div class="page-header">
                <h1>Browse Volunteers & Organizations</h1>
                <p>Connect with other volunteers and organizations to coordinate, join events, or collaborate</p>
                <?php if (!$is_logged_in): ?>
                    <div style="background: #d1ecf1; border: 1px solid #0c5460; padding: 20px; border-radius: 10px; margin-top: 20px; color: #0c5460;">
                        <strong>ℹ️ Getting Started:</strong> Browse the organizations below and contact them to request an account. Organization administrators will create your volunteer account for you. Once you have an account, you can log in and access all features.
                    </div>
                <?php elseif (!$has_org): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 10px; margin-top: 20px; color: #856404;">
                        <strong>⚠ Notice:</strong> You are not currently assigned to any organization. Browse the organizations below and contact them to join, or ask your organization administrator to add you.
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Organizations Section -->
            <div class="directory-section">
                <h2 class="section-title">
                    <i class="bi bi-building"></i> Organizations
                </h2>
                <div class="search-box">
                    <input type="text" id="organizationSearch" class="search-input" placeholder="Search organizations by admin email or organization email...">
                </div>
                <div class="directory-grid" id="organizationsGrid">
                    <?php if (empty($organizations)): ?>
                        <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 30px; border-radius: 15px; max-width: 600px; margin: 0 auto;">
                                <h3 style="color: #856404; margin-bottom: 15px;">No Organizations Found</h3>
                                <p style="color: #856404; margin-bottom: 20px;">There are no organizations in the directory yet. To get started:</p>
                                <div style="text-align: left; margin-bottom: 20px;">
                                    <p style="color: #856404; margin: 10px 0;"><strong>Option 1:</strong> Run the setup script to create starter organizations and volunteers</p>
                                    <a href="setup_starter_data.php" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; margin-top: 10px; font-weight: 500;">Run Setup Script →</a>
                                </div>
                                <div style="text-align: left; margin-bottom: 20px;">
                                    <p style="color: #856404; margin: 10px 0;"><strong>Option 2:</strong> Add an organization manually (requires login)</p>
                                    <a href="add_my_company.php" style="display: inline-block; padding: 10px 20px; background: #FFD3B5; color: #333; text-decoration: none; border-radius: 8px; margin-top: 10px; font-weight: 500;">Add Organization →</a>
                                </div>
                                <div style="text-align: left;">
                                    <p style="color: #856404; margin: 10px 0;"><strong>Note:</strong> Organizations are created by users with admin privileges. Organization administrators can add their organization through the "Add Company" page.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($organizations as $org): ?>
                            <div class="directory-card" data-email="<?= htmlspecialchars(strtolower($org['organization_email'])) ?>">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <h3 class="card-title"><?= htmlspecialchars($org['organization_email']) ?></h3>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-person-badge"></i> <strong>Admin Email:</strong> <a href="mailto:<?= htmlspecialchars($org['admin_email']) ?>" style="color: #FFD3B5; text-decoration: none;"><?= htmlspecialchars($org['admin_email']) ?></a>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-envelope"></i> <strong>Organization Email:</strong> <a href="mailto:<?= htmlspecialchars($org['organization_email']) ?>" style="color: #FFD3B5; text-decoration: none;"><?= htmlspecialchars($org['organization_email']) ?></a>
                                </div>
                                <?php if (!empty($org['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($org['phone']) ?>" style="color: #FFD3B5; text-decoration: none;"><?= htmlspecialchars($org['phone']) ?></a>
                                    </div>
                                <?php endif; ?>
                                <div class="badge">
                                    <?= htmlspecialchars($org['volunteer_count']) ?> Volunteer(s)
                                </div>
                                <div style="margin-top: 10px; padding: 10px; background: #e9ecef; border-radius: 8px; font-size: 13px; color: #666;">
                                    <strong>Contact Info:</strong><br>
                                    <strong>Main Admin:</strong> <?= htmlspecialchars($org['admin_email']) ?><br>
                                    <strong>Organization Email:</strong> <?= htmlspecialchars($org['organization_email']) ?><br>
                                    <?php if (!empty($org['phone'])): ?>
                                        <strong>Phone:</strong> <?= htmlspecialchars($org['phone']) ?>
                                    <?php endif; ?>
                                </div>
                                <button class="contact-btn" onclick="openContactModal('<?= htmlspecialchars($org['admin_email']) ?>', 'organization')">
                                    <i class="bi bi-envelope"></i> Contact Organization
                                </button>
                                <?php if (!$is_logged_in || !$has_org): ?>
                                    <button class="contact-btn" onclick="openJoinModal('<?= htmlspecialchars($org['admin_email']) ?>')" style="background: #28a745; color: white; margin-top: 10px;">
                                        <i class="bi bi-person-plus"></i> <?= $is_logged_in ? 'Request to Join' : 'Request Account' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Volunteers Section -->
            <div class="directory-section">
                <h2 class="section-title">
                    <i class="bi bi-people"></i> Volunteers
                </h2>
                <div class="search-box">
                    <input type="text" id="volunteerSearch" class="search-input" placeholder="Search volunteers by name or email...">
                </div>
                <div class="directory-grid" id="volunteersGrid">
                    <?php if (empty($volunteers)): ?>
                        <div class="no-results">
                            <p>No other volunteers found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <div class="directory-card" data-name="<?= htmlspecialchars(strtolower($volunteer['member_name'] ?? $volunteer['member_email'])) ?>" data-email="<?= htmlspecialchars(strtolower($volunteer['member_email'])) ?>">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <h3 class="card-title"><?= htmlspecialchars($volunteer['member_name'] ?? 'Volunteer') ?></h3>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-envelope"></i> <strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($volunteer['member_email']) ?>" style="color: #FFD3B5; text-decoration: none;"><?= htmlspecialchars($volunteer['member_email']) ?></a>
                                </div>
                                <?php if (!empty($volunteer['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($volunteer['phone']) ?>" style="color: #FFD3B5; text-decoration: none;"><?= htmlspecialchars($volunteer['phone']) ?></a>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top: 10px; padding: 10px; background: #e9ecef; border-radius: 8px; font-size: 13px; color: #666;">
                                    <strong>Contact Info:</strong><br>
                                    Email: <?= htmlspecialchars($volunteer['member_email']) ?><br>
                                    <?php if (!empty($volunteer['phone'])): ?>
                                        Phone: <?= htmlspecialchars($volunteer['phone']) ?>
                                    <?php endif; ?>
                                </div>
                                <button class="contact-btn" onclick="openContactModal('<?= htmlspecialchars($volunteer['member_email']) ?>', 'volunteer')">
                                    <i class="bi bi-envelope"></i> Contact Volunteer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Contact Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal('contactModal')">&times;</span>
                <h2>Contact</h2>
            </div>
            <div id="contactModalContent"></div>
        </div>
    </div>

    <!-- Join Request Modal -->
    <div id="joinModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal('joinModal')">&times;</span>
                <h2><?= $is_logged_in ? 'Request to Join Organization' : 'Request Volunteer Account' ?></h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="org_email" id="join_org_email">
                <div class="input-group">
                    <label>Your Name *</label>
                    <div class="inputForm">
                        <input type="text" name="volunteer_name" class="input" placeholder="Enter your name" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Your Email *</label>
                    <div class="inputForm">
                        <input type="email" name="volunteer_email" class="input" placeholder="Enter your email" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Your Phone</label>
                    <div class="inputForm">
                        <input type="text" name="volunteer_phone" class="input" placeholder="Enter your phone">
                    </div>
                </div>
                <div class="input-group">
                    <label>Message</label>
                    <div class="inputForm">
                        <textarea name="message" class="textarea" placeholder="<?= $is_logged_in ? 'Tell them why you want to join...' : 'Tell them why you want to volunteer and request an account...' ?>"></textarea>
                    </div>
                </div>
                <button type="submit" name="request_to_join" class="button-submit"><?= $is_logged_in ? 'Send Join Request' : 'Request Account' ?></button>
            </form>
        </div>
    </div>

    <script>
        // Search functionality for volunteers
        document.getElementById('volunteerSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('#volunteersGrid .directory-card');
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const email = card.getAttribute('data-email') || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Search functionality for organizations
        document.getElementById('organizationSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('#organizationsGrid .directory-card');
            
            cards.forEach(card => {
                const email = card.getAttribute('data-email') || '';
                const cardText = card.textContent.toLowerCase();
                
                if (email.includes(searchTerm) || cardText.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Open contact modal - shows contact info
        function openContactModal(email, type) {
            const modal = document.getElementById('contactModal');
            const modalContent = document.getElementById('contactModalContent');
            
            // Get the card data
            const card = event.target.closest('.directory-card');
            const emailElement = card.querySelector('.card-info a[href^="mailto:"]');
            const phoneElement = card.querySelector('.card-info a[href^="tel:"]');
            
            const emailText = emailElement ? emailElement.textContent : email;
            const phoneText = phoneElement ? phoneElement.textContent : 'Not provided';
            
            modalContent.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #333; margin-bottom: 15px;">${type === 'volunteer' ? 'Volunteer' : 'Organization'} Contact Information</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                        <p style="margin: 10px 0;"><strong>Email:</strong> <a href="mailto:${email}" style="color: #FFD3B5;">${emailText}</a></p>
                        <p style="margin: 10px 0;"><strong>Phone:</strong> ${phoneText !== 'Not provided' ? '<a href="tel:' + phoneText + '" style="color: #FFD3B5;">' + phoneText + '</a>' : phoneText}</p>
                    </div>
                    <a href="mailto:${email}?subject=${encodeURIComponent(type === 'volunteer' ? 'Volunteer Collaboration Request' : 'Organization Contact Request')}&body=${encodeURIComponent('Hello,\n\nI would like to connect with you regarding volunteer opportunities and collaboration.\n\nBest regards')}" 
                       style="display: inline-block; padding: 12px 30px; background: #FFD3B5; color: #333; text-decoration: none; border-radius: 10px; font-weight: 500; margin-top: 10px;">
                        <i class="bi bi-envelope"></i> Send Email
                    </a>
                </div>
            `;
            modal.style.display = 'flex';
        }

        // Open join modal
        function openJoinModal(orgEmail) {
            document.getElementById('join_org_email').value = orgEmail;
            document.getElementById('joinModal').style.display = 'flex';
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

