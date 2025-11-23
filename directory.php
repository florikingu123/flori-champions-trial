<?php
session_start();
include 'config.php';

// Ensure user is logged in as a volunteer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$current_user_email = $_SESSION['email'];

// Fetch all volunteers (excluding current user)
$volunteers = [];
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

// Fetch all organizations (users who are organization admins)
$organizations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT u.email, u.number as phone, COUNT(DISTINCT f.id) as volunteer_count
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

// Check if user has organization (before closing connection)
$check_org = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
$check_org->bind_param("s", $current_user_email);
$check_org->execute();
$org_result = $check_org->get_result();
$has_org = $org_result->num_rows > 0;
$check_org->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer & Organization Directory - VolunteerHub</title>
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
                    <li><a href="member.php" class="active">Volunteer Dashboard</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="rew.php">Achievements</a></li>
                    <li><a href="view_calendar.php">Events Calendar</a></li>
                    <li><a href="ai.php">Volunteer Support AI</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="directory-container">
            <div class="page-header">
                <h1>Volunteer & Organization Directory</h1>
                <p>Connect with other volunteers and organizations to coordinate, join events, or collaborate</p>
                <?php if (!$has_org): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 10px; margin-top: 20px; color: #856404;">
                        <strong>⚠ Notice:</strong> You are not currently assigned to any organization. Browse the organizations below and contact them to join, or ask your organization administrator to add you.
                    </div>
                <?php endif; ?>
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
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($volunteer['member_email']) ?>
                                </div>
                                <?php if (!empty($volunteer['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($volunteer['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($volunteer['member_email']) ?>', 'volunteer')">
                                    <i class="bi bi-envelope"></i> Contact Volunteer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Organizations Section -->
            <div class="directory-section">
                <h2 class="section-title">
                    <i class="bi bi-building"></i> Organizations
                </h2>
                <div class="search-box">
                    <input type="text" id="organizationSearch" class="search-input" placeholder="Search organizations by email...">
                </div>
                <div class="directory-grid" id="organizationsGrid">
                    <?php if (empty($organizations)): ?>
                        <div class="no-results">
                            <p>No organizations found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($organizations as $org): ?>
                            <div class="directory-card" data-email="<?= htmlspecialchars(strtolower($org['email'])) ?>">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <h3 class="card-title"><?= htmlspecialchars($org['email']) ?></h3>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($org['email']) ?>
                                </div>
                                <?php if (!empty($org['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($org['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="badge">
                                    <?= htmlspecialchars($org['volunteer_count']) ?> Volunteer(s)
                                </div>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($org['email']) ?>', 'organization')">
                                    <i class="bi bi-envelope"></i> Contact Organization
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
                
                if (email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Contact user function
        function contactUser(email, type) {
            const subject = type === 'volunteer' 
                ? 'Volunteer Collaboration Request' 
                : 'Organization Contact Request';
            const body = `Hello,\n\nI would like to connect with you regarding volunteer opportunities and collaboration.\n\nBest regards`;
            
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    </script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


include 'config.php';

// Ensure user is logged in as a volunteer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$current_user_email = $_SESSION['email'];

// Fetch all volunteers (excluding current user)
$volunteers = [];
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

// Fetch all organizations (users who are organization admins)
$organizations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT u.email, u.number as phone, COUNT(DISTINCT f.id) as volunteer_count
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

// Check if user has organization (before closing connection)
$check_org = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
$check_org->bind_param("s", $current_user_email);
$check_org->execute();
$org_result = $check_org->get_result();
$has_org = $org_result->num_rows > 0;
$check_org->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer & Organization Directory - VolunteerHub</title>
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
                    <li><a href="member.php" class="active">Volunteer Dashboard</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="rew.php">Achievements</a></li>
                    <li><a href="view_calendar.php">Events Calendar</a></li>
                    <li><a href="ai.php">Volunteer Support AI</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="directory-container">
            <div class="page-header">
                <h1>Volunteer & Organization Directory</h1>
                <p>Connect with other volunteers and organizations to coordinate, join events, or collaborate</p>
                <?php if (!$has_org): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 10px; margin-top: 20px; color: #856404;">
                        <strong>⚠ Notice:</strong> You are not currently assigned to any organization. Browse the organizations below and contact them to join, or ask your organization administrator to add you.
                    </div>
                <?php endif; ?>
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
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($volunteer['member_email']) ?>
                                </div>
                                <?php if (!empty($volunteer['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($volunteer['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($volunteer['member_email']) ?>', 'volunteer')">
                                    <i class="bi bi-envelope"></i> Contact Volunteer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Organizations Section -->
            <div class="directory-section">
                <h2 class="section-title">
                    <i class="bi bi-building"></i> Organizations
                </h2>
                <div class="search-box">
                    <input type="text" id="organizationSearch" class="search-input" placeholder="Search organizations by email...">
                </div>
                <div class="directory-grid" id="organizationsGrid">
                    <?php if (empty($organizations)): ?>
                        <div class="no-results">
                            <p>No organizations found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($organizations as $org): ?>
                            <div class="directory-card" data-email="<?= htmlspecialchars(strtolower($org['email'])) ?>">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <h3 class="card-title"><?= htmlspecialchars($org['email']) ?></h3>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($org['email']) ?>
                                </div>
                                <?php if (!empty($org['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($org['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="badge">
                                    <?= htmlspecialchars($org['volunteer_count']) ?> Volunteer(s)
                                </div>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($org['email']) ?>', 'organization')">
                                    <i class="bi bi-envelope"></i> Contact Organization
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
                
                if (email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Contact user function
        function contactUser(email, type) {
            const subject = type === 'volunteer' 
                ? 'Volunteer Collaboration Request' 
                : 'Organization Contact Request';
            const body = `Hello,\n\nI would like to connect with you regarding volunteer opportunities and collaboration.\n\nBest regards`;
            
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    </script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


include 'config.php';

// Ensure user is logged in as a volunteer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$current_user_email = $_SESSION['email'];

// Fetch all volunteers (excluding current user)
$volunteers = [];
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

// Fetch all organizations (users who are organization admins)
$organizations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT u.email, u.number as phone, COUNT(DISTINCT f.id) as volunteer_count
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

// Check if user has organization (before closing connection)
$check_org = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
$check_org->bind_param("s", $current_user_email);
$check_org->execute();
$org_result = $check_org->get_result();
$has_org = $org_result->num_rows > 0;
$check_org->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer & Organization Directory - VolunteerHub</title>
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
                    <li><a href="member.php" class="active">Volunteer Dashboard</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="rew.php">Achievements</a></li>
                    <li><a href="view_calendar.php">Events Calendar</a></li>
                    <li><a href="ai.php">Volunteer Support AI</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="directory-container">
            <div class="page-header">
                <h1>Volunteer & Organization Directory</h1>
                <p>Connect with other volunteers and organizations to coordinate, join events, or collaborate</p>
                <?php if (!$has_org): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 10px; margin-top: 20px; color: #856404;">
                        <strong>⚠ Notice:</strong> You are not currently assigned to any organization. Browse the organizations below and contact them to join, or ask your organization administrator to add you.
                    </div>
                <?php endif; ?>
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
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($volunteer['member_email']) ?>
                                </div>
                                <?php if (!empty($volunteer['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($volunteer['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($volunteer['member_email']) ?>', 'volunteer')">
                                    <i class="bi bi-envelope"></i> Contact Volunteer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Organizations Section -->
            <div class="directory-section">
                <h2 class="section-title">
                    <i class="bi bi-building"></i> Organizations
                </h2>
                <div class="search-box">
                    <input type="text" id="organizationSearch" class="search-input" placeholder="Search organizations by email...">
                </div>
                <div class="directory-grid" id="organizationsGrid">
                    <?php if (empty($organizations)): ?>
                        <div class="no-results">
                            <p>No organizations found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($organizations as $org): ?>
                            <div class="directory-card" data-email="<?= htmlspecialchars(strtolower($org['email'])) ?>">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <h3 class="card-title"><?= htmlspecialchars($org['email']) ?></h3>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($org['email']) ?>
                                </div>
                                <?php if (!empty($org['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($org['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="badge">
                                    <?= htmlspecialchars($org['volunteer_count']) ?> Volunteer(s)
                                </div>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($org['email']) ?>', 'organization')">
                                    <i class="bi bi-envelope"></i> Contact Organization
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
                
                if (email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Contact user function
        function contactUser(email, type) {
            const subject = type === 'volunteer' 
                ? 'Volunteer Collaboration Request' 
                : 'Organization Contact Request';
            const body = `Hello,\n\nI would like to connect with you regarding volunteer opportunities and collaboration.\n\nBest regards`;
            
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    </script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


include 'config.php';

// Ensure user is logged in as a volunteer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin1.php");
    exit();
}

$current_user_email = $_SESSION['email'];

// Fetch all volunteers (excluding current user)
$volunteers = [];
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

// Fetch all organizations (users who are organization admins)
$organizations = [];
$stmt = $conn->prepare("
    SELECT DISTINCT u.email, u.number as phone, COUNT(DISTINCT f.id) as volunteer_count
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

// Check if user has organization (before closing connection)
$check_org = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
$check_org->bind_param("s", $current_user_email);
$check_org->execute();
$org_result = $check_org->get_result();
$has_org = $org_result->num_rows > 0;
$check_org->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer & Organization Directory - VolunteerHub</title>
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
                    <li><a href="member.php" class="active">Volunteer Dashboard</a></li>
                    <li><a href="directory.php">Directory</a></li>
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="rew.php">Achievements</a></li>
                    <li><a href="view_calendar.php">Events Calendar</a></li>
                    <li><a href="ai.php">Volunteer Support AI</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="directory-container">
            <div class="page-header">
                <h1>Volunteer & Organization Directory</h1>
                <p>Connect with other volunteers and organizations to coordinate, join events, or collaborate</p>
                <?php if (!$has_org): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 10px; margin-top: 20px; color: #856404;">
                        <strong>⚠ Notice:</strong> You are not currently assigned to any organization. Browse the organizations below and contact them to join, or ask your organization administrator to add you.
                    </div>
                <?php endif; ?>
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
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($volunteer['member_email']) ?>
                                </div>
                                <?php if (!empty($volunteer['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($volunteer['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($volunteer['member_email']) ?>', 'volunteer')">
                                    <i class="bi bi-envelope"></i> Contact Volunteer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Organizations Section -->
            <div class="directory-section">
                <h2 class="section-title">
                    <i class="bi bi-building"></i> Organizations
                </h2>
                <div class="search-box">
                    <input type="text" id="organizationSearch" class="search-input" placeholder="Search organizations by email...">
                </div>
                <div class="directory-grid" id="organizationsGrid">
                    <?php if (empty($organizations)): ?>
                        <div class="no-results">
                            <p>No organizations found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($organizations as $org): ?>
                            <div class="directory-card" data-email="<?= htmlspecialchars(strtolower($org['email'])) ?>">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <h3 class="card-title"><?= htmlspecialchars($org['email']) ?></h3>
                                </div>
                                <div class="card-info">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($org['email']) ?>
                                </div>
                                <?php if (!empty($org['phone'])): ?>
                                    <div class="card-info">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($org['phone']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="badge">
                                    <?= htmlspecialchars($org['volunteer_count']) ?> Volunteer(s)
                                </div>
                                <button class="contact-btn" onclick="contactUser('<?= htmlspecialchars($org['email']) ?>', 'organization')">
                                    <i class="bi bi-envelope"></i> Contact Organization
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

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
                
                if (email.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Contact user function
        function contactUser(email, type) {
            const subject = type === 'volunteer' 
                ? 'Volunteer Collaboration Request' 
                : 'Organization Contact Request';
            const body = `Hello,\n\nI would like to connect with you regarding volunteer opportunities and collaboration.\n\nBest regards`;
            
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    </script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

