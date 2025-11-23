<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$user_email = $_SESSION['email'];

// Check if user is a manager
$check_stmt = $conn->prepare("SELECT managers_email FROM family WHERE member_email = ?");
$check_stmt->bind_param("s", $user_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$user_data = $check_result->fetch_assoc();
$check_stmt->close();

$is_manager = false;
$manager_email = null;

// Check if user is a manager
$manager_check = $conn->prepare("SELECT id FROM family WHERE managers_email = ?");
$manager_check->bind_param("s", $user_email);
$manager_check->execute();
$manager_result = $manager_check->get_result();
if ($manager_result->num_rows > 0) {
    $is_manager = true;
    $manager_email = $user_email;
} else if ($user_data) {
    $manager_email = $user_data['managers_email'];
}
$manager_check->close();

// Get volunteer's mission history for AI context
$history_stmt = $conn->prepare("
    SELECT c.chore_name, c.points, cv.status, cv.verified_at
    FROM chores c
    LEFT JOIN chore_verifications cv ON c.id = cv.chore_id
    WHERE c.member_email = ? OR (c.manager_email = ? AND ? = 1)
    ORDER BY cv.verified_at DESC
    LIMIT 20
");
$is_manager_int = $is_manager ? 1 : 0;
$history_stmt->bind_param("ssi", $user_email, $user_email, $is_manager_int);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$mission_history = [];
while ($row = $history_result->fetch_assoc()) {
    $mission_history[] = $row;
}
$history_stmt->close();

// Get volunteer stats
$stats_stmt = $conn->prepare("SELECT points as hours FROM family WHERE member_email = ?");
$stats_stmt->bind_param("s", $user_email);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$user_stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$user_hours = $user_stats['hours'] ?? 0;

// Get organization members (for managers)
$org_members = [];
if ($is_manager) {
    $members_stmt = $conn->prepare("SELECT member_name, member_email, points FROM family WHERE managers_email = ?");
    $members_stmt->bind_param("s", $manager_email);
    $members_stmt->execute();
    $members_result = $members_stmt->get_result();
    while ($row = $members_result->fetch_assoc()) {
        $org_members[] = $row;
    }
    $members_stmt->close();
}

// Handle AI suggestion request
$suggestions = [];
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_suggestions'])) {
    $context = $_POST['context'] ?? '';
    $volunteer_email = $_POST['volunteer_email'] ?? $user_email;
    
    // Build context for AI
    $ai_context = "You are a volunteer mission suggestion assistant for VolunteerHub. ";
    $ai_context .= "The user has completed " . $user_hours . " volunteer hours. ";
    
    if (!empty($mission_history)) {
        $ai_context .= "Recent mission history: ";
        $completed = array_filter($mission_history, function($m) { return $m['status'] === 'approved'; });
        $ai_context .= count($completed) . " completed missions. ";
        if (count($completed) > 0) {
            $recent_types = array_slice(array_column($completed, 'chore_name'), 0, 5);
            $ai_context .= "Recent mission types: " . implode(", ", $recent_types) . ". ";
        }
    }
    
    $ai_context .= "User request: " . $context . " ";
    $ai_context .= "Please suggest 3-5 specific volunteer missions that would be appropriate. ";
    $ai_context .= "For each mission, provide: 1) Mission name, 2) Brief description, 3) Estimated hours (1-10), 4) Category (Community Service, Environmental, Education, Healthcare, etc.). ";
    $ai_context .= "Format as JSON array with fields: name, description, hours, category. ";
    $ai_context .= "Make suggestions relevant to volunteer work and community service.";
    
    // Call Gemini API
    $GEMINI_KEY = 'AIzaSyAVD5YiIAilUzdm8x_CGKCMYI1Vmamd6TI';
    $GEMINI_MODEL = 'gemini-2.5-flash';
    $GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_KEY}";
    
    $ch = curl_init($GEMINI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [[
            'parts' => [[
                'text' => $ai_context
            ]]
        ]],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2000
        ]
    ]));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_text = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Try to extract JSON from response
            if (preg_match('/\[.*\]/s', $ai_text, $matches)) {
                $suggestions = json_decode($matches[0], true);
                if (!is_array($suggestions)) {
                    $suggestions = [];
                }
            } else {
                // Parse text response manually
                $lines = explode("\n", $ai_text);
                foreach ($lines as $line) {
                    if (stripos($line, 'mission') !== false || stripos($line, 'volunteer') !== false) {
                        // Extract mission info
                        if (preg_match('/(\d+)\s*hours?/i', $line, $hour_match)) {
                            $hours = (int)$hour_match[1];
                        } else {
                            $hours = 2;
                        }
                        
                        $suggestions[] = [
                            'name' => substr($line, 0, 50),
                            'description' => $line,
                            'hours' => $hours,
                            'category' => 'Community Service'
                        ];
                    }
                }
            }
            
            // Ensure we have at least some suggestions
            if (empty($suggestions)) {
                // Default suggestions
                $suggestions = [
                    [
                        'name' => 'Community Garden Maintenance',
                        'description' => 'Help maintain and beautify local community gardens',
                        'hours' => 3,
                        'category' => 'Environmental'
                    ],
                    [
                        'name' => 'Food Bank Assistance',
                        'description' => 'Sort and organize donations at the local food bank',
                        'hours' => 4,
                        'category' => 'Community Service'
                    ],
                    [
                        'name' => 'Tutoring Students',
                        'description' => 'Provide academic support to students in need',
                        'hours' => 2,
                        'category' => 'Education'
                    ]
                ];
            }
        } else {
            $error_message = "AI response format error. Please try again.";
        }
    } else {
        $error_message = "Failed to get AI suggestions. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>AI Mission Suggestions - VolunteerHub</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .header {
            background-color: #FFD3B5;
        }
        
        .suggestions-container {
            padding: 100px 0 60px;
        }
        
        .suggestion-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #FFD3B5;
            transition: all 0.3s ease;
        }
        
        .suggestion-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .suggestion-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .suggestion-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .suggestion-category {
            background: #FFD3B5;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .suggestion-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .suggestion-hours {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            color: #333;
        }
        
        .use-suggestion-btn {
            background: #FFD3B5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .use-suggestion-btn:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }
        
        .request-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FFD3B5;
            box-shadow: 0 0 0 3px rgba(255, 211, 181, 0.2);
        }
        
        .submit-btn {
            background: #FFD3B5;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background: linear-gradient(135deg, #FFD3B5, #FFAAA5);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
    </style>
    <?php include 'includes/theme_includes.php'; ?>
</head>
<body>
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.html" class="logo d-flex align-items-center">
                <h1 class="sitename">VolunteerHub</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="member.php">Volunteer Dashboard</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="ai_mission_suggestions.php" class="active">AI Suggestions</a></li>
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container suggestions-container">
            <div class="info-card">
                <h2><i class="bi bi-lightbulb"></i> AI Mission Suggestions</h2>
                <p style="margin: 10px 0 0;">Get personalized volunteer mission suggestions based on your experience and preferences. Our AI analyzes your mission history and suggests relevant opportunities.</p>
            </div>

            <div class="request-form">
                <h3 style="margin-bottom: 20px;">Request Mission Suggestions</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="context">What kind of missions are you interested in?</label>
                        <textarea id="context" name="context" class="form-control" rows="3" 
                                  placeholder="e.g., I'm interested in environmental work, or I want to help with education programs, or suggest missions based on my history..."></textarea>
                    </div>
                    
                    <?php if ($is_manager): ?>
                    <div class="form-group">
                        <label for="volunteer_email">Suggest for (optional):</label>
                        <select id="volunteer_email" name="volunteer_email" class="form-control">
                            <option value="<?= $user_email ?>">Myself</option>
                            <?php foreach ($org_members as $member): ?>
                                <option value="<?= htmlspecialchars($member['member_email']) ?>">
                                    <?= htmlspecialchars($member['member_name']) ?> (<?= $member['points'] ?> hours)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="get_suggestions" class="submit-btn">
                        <i class="bi bi-magic"></i> Get AI Suggestions
                    </button>
                </form>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($suggestions)): ?>
                <h3 style="margin-bottom: 20px;">Suggested Missions</h3>
                <?php foreach ($suggestions as $index => $suggestion): ?>
                    <div class="suggestion-card">
                        <div class="suggestion-header">
                            <h4 class="suggestion-name">
                                <?= htmlspecialchars($suggestion['name'] ?? 'Mission ' . ($index + 1)) ?>
                            </h4>
                            <span class="suggestion-category">
                                <?= htmlspecialchars($suggestion['category'] ?? 'Community Service') ?>
                            </span>
                        </div>
                        <p class="suggestion-description">
                            <?= htmlspecialchars($suggestion['description'] ?? 'A great volunteer opportunity') ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="suggestion-hours">
                                <i class="bi bi-clock"></i> 
                                <?= htmlspecialchars($suggestion['hours'] ?? 2) ?> hours
                            </span>
                            <?php if ($is_manager): ?>
                                <a href="assign_chores.php?name=<?= urlencode($suggestion['name']) ?>&points=<?= $suggestion['hours'] ?>&description=<?= urlencode($suggestion['description']) ?>" 
                                   class="use-suggestion-btn">
                                    <i class="bi bi-plus-circle"></i> Use This Mission
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="container footer-top">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6 footer-about">
                    <a href="index.html" class="d-flex align-items-center">
                        <span class="sitename">VolunteerHub</span>
                    </a>
                    <div class="footer-contact pt-3">
                        <p>1234 Elm Street</p>
                        <p>Los Angeles, CA 90001</p>
                        <p class="mt-3"><strong>Phone:</strong> <span>+1 2345 6789 01</span></p>
                        <p><strong>Email:</strong> <span>volunteerhub@info.com</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container copyright text-center mt-4">
            <p>© <span>Copyright</span> <strong class="px-1 sitename">VolunteerHub</strong> <span>All Rights Reserved</span></p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

