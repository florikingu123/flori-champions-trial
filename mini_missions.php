<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$member_email = $_SESSION['email'];

// Calculate level function
function calculateLevel($hours) {
    if ($hours <= 0) return 1;
    return floor(sqrt($hours / 10)) + 1;
}

// Fetch user data
$user_data = [];
$stmt = $conn->prepare("SELECT points, COALESCE(games_played, 0) as games_played FROM family WHERE member_email = ?");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
}

$user_points = $user_data['points'] ?? 0;
$user_level = calculateLevel($user_points);

// Fetch completed mini missions
$completed_mini_missions = [];
$stmt = $conn->prepare("SELECT mission_type, completed_at FROM mini_missions WHERE volunteer_email = ? ORDER BY completed_at DESC LIMIT 10");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $completed_mini_missions[] = $row;
        }
    }
    $stmt->close();
}

// Fetch completed task IDs for this user
$completed_task_ids = [];
$stmt = $conn->prepare("SELECT task_id FROM mini_mission_completions WHERE volunteer_email = ?");
if ($stmt) {
    $stmt->bind_param("s", $member_email);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $completed_task_ids[] = $row['task_id'];
        }
    }
    $stmt->close();
}

// Get random tasks for each mission type (excluding completed ones)
function getRandomTask($conn, $mission_type, $completed_ids) {
    if (empty($completed_ids)) {
        $sql = "SELECT * FROM mini_mission_tasks WHERE mission_type = ? ORDER BY RAND() LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $mission_type);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $task = $result->fetch_assoc();
                $stmt->close();
                return $task;
            }
            $stmt->close();
        }
    } else {
        $placeholders = str_repeat('?,', count($completed_ids) - 1) . '?';
        $sql = "SELECT * FROM mini_mission_tasks WHERE mission_type = ? AND id NOT IN (" . $placeholders . ") ORDER BY RAND() LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = 's' . str_repeat('i', count($completed_ids));
            $params = array_merge([$mission_type], $completed_ids);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $task = $result->fetch_assoc();
                $stmt->close();
                return $task;
            }
            $stmt->close();
        }
    }
    return null;
}

$social_task = getRandomTask($conn, 'social_share', $completed_task_ids);
$translation_task = getRandomTask($conn, 'translation', $completed_task_ids);
$poster_task = getRandomTask($conn, 'poster_creation', $completed_task_ids);
$file_task = getRandomTask($conn, 'file_creation', $completed_task_ids);

// Handle mini mission completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_mini_mission'])) {
    $task_id = intval($_POST['task_id'] ?? 0);
    $mission_type = $_POST['mission_type'] ?? '';
    $mission_data = json_decode($_POST['mission_data'] ?? '{}', true);
    
    // Validate task_id
    if ($task_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit();
    }
    
    // Get task details to get points
    $task_stmt = $conn->prepare("SELECT points_earned FROM mini_mission_tasks WHERE id = ?");
    $points_earned = 5;
    if ($task_stmt) {
        $task_stmt->bind_param("i", $task_id);
        if ($task_stmt->execute()) {
            $task_result = $task_stmt->get_result();
            $task_row = $task_result->fetch_assoc();
            if ($task_row) {
                $points_earned = $task_row['points_earned'];
            } else {
                // Task doesn't exist
                $task_stmt->close();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit();
            }
        }
        $task_stmt->close();
    }
    
    // Insert into mini_missions table
    $stmt = $conn->prepare("INSERT INTO mini_missions (volunteer_email, mission_type, mission_data, points_earned) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $mission_data_json = json_encode($mission_data);
        $stmt->bind_param("sssi", $member_email, $mission_type, $mission_data_json, $points_earned);
        if ($stmt->execute()) {
            // Mark task as completed (ignore duplicate key errors)
            $completion_stmt = $conn->prepare("INSERT IGNORE INTO mini_mission_completions (volunteer_email, task_id) VALUES (?, ?)");
            if ($completion_stmt) {
                $completion_stmt->bind_param("si", $member_email, $task_id);
                $completion_stmt->execute();
                $completion_stmt->close();
            }
            
            // Update user points
            $update_stmt = $conn->prepare("UPDATE family SET points = points + ? WHERE member_email = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("is", $points_earned, $member_email);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'points_earned' => $points_earned]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mini Missions - Online Volunteering - VolunteerHub</title>
  <meta name="description" content="">
  <meta name="keywords" content="">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    :root {
        --primary-color: #5c4d3c;
        --accent-color: #FFD3B5;
        --heading-color: #333;
        --default-color: #666;
    }
    
    .mini-mission-hero {
        background: linear-gradient(135deg, #FFD3B5 0%, #FFAAA5 100%);
        color: #333;
        padding: 80px 0 60px;
        text-align: center;
        margin-top: 80px;
    }
    
    .mini-mission-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 3px solid transparent;
    }
    
    .mini-mission-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        border-color: var(--accent-color);
    }
    
    .mission-icon {
        width: 80px;
        height: 80px;
        background: var(--accent-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: var(--primary-color);
        margin: 0 auto 20px;
    }
    
    .mission-badge {
        display: inline-block;
        padding: 8px 16px;
        background: var(--primary-color);
        color: white;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin: 10px 5px;
    }
    
    .ai-feature-card {
        background: linear-gradient(135deg, #FFD3B5 0%, #FFAAA5 100%);
        color: #333;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(255, 211, 181, 0.3);
    }
    
    .ai-feature-card h3 {
        color: white;
        margin-bottom: 20px;
    }
    
    .btn-mission {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .btn-mission:hover {
        background: #4a3d30;
        transform: scale(1.05);
        color: white;
    }
    
    .btn-ai {
        background: var(--accent-color);
        color: var(--primary-color);
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .btn-ai:hover {
        background: #FFAAA5;
        transform: scale(1.05);
        color: var(--primary-color);
    }
    
    .modal-content {
        border-radius: 20px;
        border: none;
        padding: 30px;
    }
    
    .social-share-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 20px;
    }
    
    .social-btn {
        flex: 1;
        min-width: 120px;
        padding: 12px;
        border-radius: 10px;
        border: none;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .social-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .poster-canvas {
        border: 2px dashed #ddd;
        border-radius: 10px;
        padding: 20px;
        background: #f8f9fa;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .translation-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin: 15px 0;
        min-height: 150px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    
    .stat-box {
        background: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 10px 0;
    }
  </style>
</head>
<body class="index-page">

<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">VolunteerHub</h1>
    </a>
    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="#">Level <?= $user_level ?? 1 ?> | <?= htmlspecialchars($user_points ?? 0) ?> hrs</a></li>
        <li><a href="member.php">Organization Center</a></li>
        <li><a href="games.php">Engagement Zone</a></li>
        <li><a href="profile.php">My Profile</a></li>
        <li><a href="rew.php">Achievements</a></li>
        <li><a href="view_calendar.php">Events Calendar</a></li>
        <li><a href="mini_missions.php" class="active">Mini Missions</a></li>
        <li><a href="ai.php">AI</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>
  </div>
</header>

<main class="main">
  <!-- Hero Section -->
  <section class="mini-mission-hero">
    <div class="container">
      <h1 style="font-size: 3rem; margin-bottom: 20px; font-weight: 700;">
        <i class="bi bi-rocket-takeoff"></i> Mini Missions
      </h1>
      <p style="font-size: 1.3rem; opacity: 0.9;">Complete quick online tasks and earn points instantly!</p>
      <div class="stats-grid" style="max-width: 800px; margin: 40px auto;">
        <div class="stat-box">
          <div class="stat-number"><?= count($completed_mini_missions) ?></div>
          <div>Completed</div>
        </div>
        <div class="stat-box">
          <div class="stat-number"><?= $user_points ?></div>
          <div>Total Points</div>
        </div>
        <div class="stat-box">
          <div class="stat-number"><?= $user_level ?></div>
          <div>Your Level</div>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="row">
        <!-- Mini Missions -->
        <div class="col-lg-8">
          <h2 style="color: var(--heading-color); margin-bottom: 30px; font-weight: 700;">
            <i class="bi bi-list-check"></i> Available Mini Missions
          </h2>
          
          <!-- Social Media Share Mission -->
          <div class="mini-mission-card" data-aos="fade-up">
            <div class="mission-icon">
              <i class="bi bi-share-fill"></i>
            </div>
            <h3 style="color: var(--heading-color); text-align: center; margin-bottom: 15px;">Share Event on Social Media</h3>
            <?php if ($social_task): ?>
              <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <p style="color: var(--heading-color); font-weight: 600; margin-bottom: 10px;">Your Task:</p>
                <p style="color: var(--default-color); margin: 0;"><?= htmlspecialchars($social_task['task_title']) ?></p>
                <p style="color: var(--default-color); margin-top: 10px; white-space: pre-wrap; font-size: 0.9rem;"><?= htmlspecialchars($social_task['task_content']) ?></p>
              </div>
            <?php else: ?>
              <p style="text-align: center; color: var(--default-color); margin-bottom: 20px;">No tasks available. All tasks completed!</p>
            <?php endif; ?>
            <div style="text-align: center;">
              <span class="mission-badge"><i class="bi bi-clock"></i> 2-5 min</span>
              <span class="mission-badge"><i class="bi bi-star"></i> <?= $social_task ? $social_task['points_earned'] : 10 ?> points</span>
              <span class="mission-badge"><i class="bi bi-globe"></i> Online</span>
            </div>
            <div style="text-align: center; margin-top: 25px;">
              <button class="btn btn-mission" onclick="openSocialShareModal(<?= $social_task ? $social_task['id'] : 0 ?>, <?= $social_task ? json_encode($social_task['task_content'] ?? '') : '""' ?>)" <?= !$social_task ? 'disabled' : '' ?>>
                <i class="bi bi-share"></i> Start Mission
              </button>
            </div>
          </div>

          <!-- Translation Mission -->
          <div class="mini-mission-card" data-aos="fade-up" data-aos-delay="100">
            <div class="mission-icon">
              <i class="bi bi-translate"></i>
            </div>
            <h3 style="color: var(--heading-color); text-align: center; margin-bottom: 15px;">Translate Volunteer Content</h3>
            <?php if ($translation_task): ?>
              <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <p style="color: var(--heading-color); font-weight: 600; margin-bottom: 10px;">Your Task:</p>
                <p style="color: var(--default-color); margin: 0; white-space: pre-wrap;"><?= htmlspecialchars($translation_task['task_title']) ?></p>
                <p style="color: #667eea; margin-top: 10px; font-weight: 600;"><?= htmlspecialchars($translation_task['task_instructions']) ?></p>
              </div>
            <?php else: ?>
              <p style="text-align: center; color: var(--default-color); margin-bottom: 20px;">No tasks available. All tasks completed!</p>
            <?php endif; ?>
            <div style="text-align: center;">
              <span class="mission-badge"><i class="bi bi-clock"></i> 5-10 min</span>
              <span class="mission-badge"><i class="bi bi-star"></i> <?= $translation_task ? $translation_task['points_earned'] : 15 ?> points</span>
              <span class="mission-badge"><i class="bi bi-globe"></i> Online</span>
            </div>
            <div style="text-align: center; margin-top: 25px;">
              <button class="btn btn-mission" onclick="openTranslationModal(<?= $translation_task ? $translation_task['id'] : 0 ?>, <?= $translation_task ? json_encode($translation_task['task_content'] ?? '') : '""' ?>, <?= $translation_task ? json_encode($translation_task['task_instructions'] ?? '') : '""' ?>)" <?= !$translation_task ? 'disabled' : '' ?>>
                <i class="bi bi-translate"></i> Start Mission
              </button>
            </div>
          </div>

          <!-- Poster Creation Mission -->
          <div class="mini-mission-card" data-aos="fade-up" data-aos-delay="200">
            <div class="mission-icon">
              <i class="bi bi-image"></i>
            </div>
            <h3 style="color: var(--heading-color); text-align: center; margin-bottom: 15px;">Create Event Poster</h3>
            <?php if ($poster_task): 
              $poster_data = json_decode($poster_task['task_content'], true);
            ?>
              <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <p style="color: var(--heading-color); font-weight: 600; margin-bottom: 10px;">Your Task:</p>
                <p style="color: var(--default-color); margin: 0;"><?= htmlspecialchars($poster_task['task_title']) ?></p>
                <?php if ($poster_data): ?>
                  <p style="color: var(--default-color); margin-top: 10px; font-size: 0.9rem;"><strong>Event:</strong> <?= htmlspecialchars($poster_data['title'] ?? '') ?></p>
                  <p style="color: var(--default-color); font-size: 0.9rem;"><strong>Date:</strong> <?= htmlspecialchars($poster_data['date'] ?? '') ?></p>
                  <p style="color: var(--default-color); font-size: 0.9rem;"><strong>Description:</strong> <?= htmlspecialchars($poster_data['description'] ?? '') ?></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <p style="text-align: center; color: var(--default-color); margin-bottom: 20px;">No tasks available. All tasks completed!</p>
            <?php endif; ?>
            <div style="text-align: center;">
              <span class="mission-badge"><i class="bi bi-clock"></i> 10-15 min</span>
              <span class="mission-badge"><i class="bi bi-star"></i> <?= $poster_task ? $poster_task['points_earned'] : 20 ?> points</span>
              <span class="mission-badge"><i class="bi bi-globe"></i> Online</span>
            </div>
            <div style="text-align: center; margin-top: 25px;">
              <button class="btn btn-mission" onclick="openPosterModal(<?= $poster_task ? $poster_task['id'] : 0 ?>, <?= $poster_task ? htmlspecialchars(json_encode($poster_data ?? []), ENT_QUOTES) : '{}' ?>)" <?= !$poster_task ? 'disabled' : '' ?>>
                <i class="bi bi-palette"></i> Start Mission
              </button>
            </div>
          </div>

          <!-- File Creation Mission -->
          <div class="mini-mission-card" data-aos="fade-up" data-aos-delay="300">
            <div class="mission-icon">
              <i class="bi bi-file-earmark-text"></i>
            </div>
            <h3 style="color: var(--heading-color); text-align: center; margin-bottom: 15px;">Create Volunteer Document</h3>
            <?php if ($file_task): 
              $file_data = json_decode($file_task['task_content'], true);
            ?>
              <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <p style="color: var(--heading-color); font-weight: 600; margin-bottom: 10px;">Your Task:</p>
                <p style="color: var(--default-color); margin: 0;"><?= htmlspecialchars($file_task['task_title']) ?></p>
                <?php if ($file_data): ?>
                  <p style="color: var(--default-color); margin-top: 10px; font-size: 0.9rem;"><strong>Type:</strong> <?= htmlspecialchars($file_data['type'] ?? '') ?></p>
                  <p style="color: var(--default-color); font-size: 0.9rem;"><strong>Topic:</strong> <?= htmlspecialchars($file_data['topic'] ?? '') ?></p>
                  <p style="color: var(--default-color); font-size: 0.9rem;"><strong>Details:</strong> <?= htmlspecialchars($file_data['details'] ?? '') ?></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <p style="text-align: center; color: var(--default-color); margin-bottom: 20px;">No tasks available. All tasks completed!</p>
            <?php endif; ?>
            <div style="text-align: center;">
              <span class="mission-badge"><i class="bi bi-clock"></i> 10-20 min</span>
              <span class="mission-badge"><i class="bi bi-star"></i> <?= $file_task ? $file_task['points_earned'] : 25 ?> points</span>
              <span class="mission-badge"><i class="bi bi-globe"></i> Online</span>
            </div>
            <div style="text-align: center; margin-top: 25px;">
              <button class="btn btn-mission" onclick="openFileCreatorModal(<?= $file_task ? $file_task['id'] : 0 ?>, <?= $file_task ? htmlspecialchars(json_encode($file_data ?? []), ENT_QUOTES) : '{}' ?>)" <?= !$file_task ? 'disabled' : '' ?>>
                <i class="bi bi-file-plus"></i> Start Mission
              </button>
            </div>
          </div>
        </div>

        <!-- AI Features Sidebar -->
        <div class="col-lg-4">
          <h2 style="color: var(--heading-color); margin-bottom: 30px; font-weight: 700;">
            <i class="bi bi-magic"></i> AI-Powered Features
          </h2>

          <!-- AI Content Generator -->
          <div class="ai-feature-card" data-aos="fade-left">
            <h3><i class="bi bi-stars"></i> AI Content Generator</h3>
            <p>Generate social media posts, event descriptions, and volunteer content instantly with AI</p>
            <button class="btn btn-ai" onclick="openAIContentModal()">
              <i class="bi bi-magic"></i> Generate Content
            </button>
          </div>


          <!-- AI Writing Assistant -->
          <div class="ai-feature-card" data-aos="fade-left" data-aos-delay="100">
            <h3><i class="bi bi-pencil-square"></i> AI Writing Assistant</h3>
            <p>Get help writing emails, proposals, and volunteer communications</p>
            <button class="btn btn-ai" onclick="openAIWritingModal()">
              <i class="bi bi-pencil"></i> Get Writing Help
            </button>
          </div>

          <!-- AI Mission Suggestions -->
          <div class="ai-feature-card" data-aos="fade-left" data-aos-delay="200">
            <h3><i class="bi bi-lightbulb"></i> AI Mission Ideas</h3>
            <p>Get personalized mini mission suggestions based on your skills and interests</p>
            <button class="btn btn-ai" onclick="openAIMissionIdeasModal()">
              <i class="bi bi-lightbulb-fill"></i> Get Ideas
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Social Share Modal -->
<div id="socialShareModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 600px;">
    <span class="close" onclick="closeModal('socialShareModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-share"></i> Share Event on Social Media</h2>
    <div class="form-group">
      <label>Event Title</label>
      <input type="text" id="eventTitle" class="form-control" placeholder="Enter event title">
    </div>
    <div class="form-group">
      <label>Event Description</label>
      <textarea id="eventDescription" class="form-control" rows="4" placeholder="Describe the event..."></textarea>
    </div>
    <div class="form-group">
      <label>Generated Social Media Post</label>
      <div class="translation-box" id="generatedPost" style="min-height: 100px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
        <p style="color: #999; margin: 0;">Your post will appear here...</p>
      </div>
      <button class="btn btn-sm" style="background: var(--accent-color); margin-top: 10px;" onclick="generateSocialPost()">
        <i class="bi bi-magic"></i> Generate with AI
      </button>
    </div>
    <div class="social-share-buttons">
      <button class="social-btn" style="background: #1877f2;" onclick="shareToFacebook()">
        <i class="bi bi-facebook"></i> Facebook
      </button>
      <button class="social-btn" style="background: #1da1f2;" onclick="shareToTwitter()">
        <i class="bi bi-twitter"></i> Twitter
      </button>
      <button class="social-btn" style="background: #0077b5;" onclick="shareToLinkedIn()">
        <i class="bi bi-linkedin"></i> LinkedIn
      </button>
      <button class="social-btn" style="background: #e4405f;" onclick="shareToInstagram()">
        <i class="bi bi-instagram"></i> Instagram
      </button>
    </div>
    <div style="text-align: center; margin-top: 25px;">
      <button class="btn btn-mission" onclick="completeSocialShare()">
        <i class="bi bi-check-circle"></i> Complete Mission
      </button>
    </div>
  </div>
</div>

<!-- Translation Modal -->
<div id="translationModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 700px;">
    <span class="close" onclick="closeModal('translationModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-translate"></i> Translate Volunteer Content</h2>
    <div class="form-group">
      <label>Original Text</label>
      <textarea id="originalText" class="form-control" rows="5" placeholder="Enter text to translate..."></textarea>
    </div>
    <div class="form-group">
      <label>Target Language</label>
      <select id="targetLanguage" class="form-control">
        <option value="es">Spanish</option>
        <option value="fr">French</option>
        <option value="de">German</option>
        <option value="it">Italian</option>
        <option value="pt">Portuguese</option>
        <option value="zh">Chinese</option>
        <option value="ja">Japanese</option>
        <option value="ar">Arabic</option>
      </select>
    </div>
    <button class="btn btn-sm" style="background: var(--accent-color); margin-bottom: 15px;" onclick="translateText()">
      <i class="bi bi-magic"></i> Translate with AI
    </button>
    <div class="form-group">
      <label>Translated Text</label>
      <div class="translation-box" id="translatedText" style="min-height: 150px;">
        <p style="color: #999; margin: 0;">Translation will appear here...</p>
      </div>
    </div>
    <div style="text-align: center; margin-top: 25px;">
      <button class="btn btn-mission" onclick="completeTranslation()">
        <i class="bi bi-check-circle"></i> Complete Mission
      </button>
    </div>
  </div>
</div>

<!-- Poster Creation Modal -->
<div id="posterModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 900px;">
    <span class="close" onclick="closeModal('posterModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-image"></i> Create Event Poster</h2>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Poster Title</label>
          <input type="text" id="posterTitle" class="form-control" placeholder="Event title">
        </div>
        <div class="form-group">
          <label>Event Date</label>
          <input type="date" id="posterDate" class="form-control">
        </div>
        <div class="form-group">
          <label>Event Description</label>
          <textarea id="posterDescription" class="form-control" rows="4" placeholder="Describe the event..."></textarea>
        </div>
        <div class="form-group">
          <label>Poster Style</label>
          <select id="posterStyle" class="form-control">
            <option value="modern">Modern</option>
            <option value="vintage">Vintage</option>
            <option value="minimalist">Minimalist</option>
            <option value="colorful">Colorful</option>
            <option value="professional">Professional</option>
          </select>
        </div>
        <button class="btn btn-sm" style="background: var(--accent-color); margin-bottom: 15px;" onclick="generatePoster()">
          <i class="bi bi-magic"></i> Generate Poster with AI
        </button>
      </div>
      <div class="col-md-6">
        <label>Poster Preview</label>
        <div class="poster-canvas" id="posterCanvas">
          <p style="color: #999;">Your poster will appear here</p>
        </div>
        <button class="btn btn-sm" style="background: var(--primary-color); color: white; width: 100%; margin-top: 10px;" onclick="downloadPoster()">
          <i class="bi bi-download"></i> Download Poster
        </button>
      </div>
    </div>
    <div style="text-align: center; margin-top: 25px;">
      <button class="btn btn-mission" onclick="completePoster()">
        <i class="bi bi-check-circle"></i> Complete Mission
      </button>
    </div>
  </div>
</div>

<!-- File Creator Modal -->
<div id="fileCreatorModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 800px;">
    <span class="close" onclick="closeModal('fileCreatorModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-file-earmark-text"></i> Create Volunteer Document</h2>
    <div class="form-group">
      <label>Document Type</label>
      <select id="documentType" class="form-control">
        <option value="guide">Volunteer Guide</option>
        <option value="template">Email Template</option>
        <option value="checklist">Checklist</option>
        <option value="faq">FAQ Document</option>
        <option value="training">Training Material</option>
      </select>
    </div>
    <div class="form-group">
      <label>Topic/Subject</label>
      <input type="text" id="documentTopic" class="form-control" placeholder="What should the document be about?">
    </div>
    <div class="form-group">
      <label>Additional Details</label>
      <textarea id="documentDetails" class="form-control" rows="4" placeholder="Any specific requirements or details..."></textarea>
    </div>
    <button class="btn btn-sm" style="background: var(--accent-color); margin-bottom: 15px;" onclick="generateDocument()">
      <i class="bi bi-magic"></i> Generate Document with AI
    </button>
    <div class="form-group">
      <label>Generated Document</label>
      <div class="translation-box" id="generatedDocument" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
        <p style="color: #999; margin: 0;">Your document will appear here...</p>
      </div>
    </div>
    <div style="text-align: center; margin-top: 25px;">
      <button class="btn btn-mission" onclick="completeFileCreator()">
        <i class="bi bi-check-circle"></i> Complete Mission
      </button>
    </div>
  </div>
</div>

<!-- AI Content Generator Modal -->
<div id="aiContentModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 700px;">
    <span class="close" onclick="closeModal('aiContentModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-stars"></i> AI Content Generator</h2>
    <div class="form-group">
      <label>Content Type</label>
      <select id="contentType" class="form-control">
        <option value="social">Social Media Post</option>
        <option value="email">Email</option>
        <option value="blog">Blog Post</option>
        <option value="announcement">Announcement</option>
      </select>
    </div>
    <div class="form-group">
      <label>Topic/Subject</label>
      <input type="text" id="contentTopic" class="form-control" placeholder="What should the content be about?">
    </div>
    <div class="form-group">
      <label>Additional Instructions</label>
      <textarea id="contentInstructions" class="form-control" rows="3" placeholder="Any specific tone, style, or requirements..."></textarea>
    </div>
    <button class="btn btn-ai" style="width: 100%;" onclick="generateAIContent()">
      <i class="bi bi-magic"></i> Generate Content
    </button>
    <div class="form-group" style="margin-top: 20px;">
      <label>Generated Content</label>
      <div class="translation-box" id="generatedAIContent" style="min-height: 200px;">
        <p style="color: #999; margin: 0;">Generated content will appear here...</p>
      </div>
    </div>
  </div>
</div>

<!-- AI Writing Assistant Modal -->
<div id="aiWritingModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 700px;">
    <span class="close" onclick="closeModal('aiWritingModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-pencil-square"></i> AI Writing Assistant</h2>
    <div class="form-group">
      <label>What do you need help writing?</label>
      <select id="writingType" class="form-control">
        <option value="email">Email</option>
        <option value="proposal">Proposal</option>
        <option value="report">Report</option>
        <option value="announcement">Announcement</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="form-group">
      <label>Your Draft or Instructions</label>
      <textarea id="writingInput" class="form-control" rows="6" placeholder="Paste your draft or describe what you need help with..."></textarea>
    </div>
    <button class="btn btn-ai" style="width: 100%;" onclick="getWritingHelp()">
      <i class="bi bi-magic"></i> Get Writing Help
    </button>
    <div class="form-group" style="margin-top: 20px;">
      <label>Improved/Generated Text</label>
      <div class="translation-box" id="improvedWriting" style="min-height: 200px;">
        <p style="color: #999; margin: 0;">Improved text will appear here...</p>
      </div>
    </div>
  </div>
</div>

<!-- AI Mission Ideas Modal -->
<div id="aiMissionIdeasModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 700px;">
    <span class="close" onclick="closeModal('aiMissionIdeasModal')" style="color: #333;">&times;</span>
    <h2 style="color: var(--heading-color); margin-bottom: 20px;"><i class="bi bi-lightbulb"></i> AI Mission Ideas</h2>
    <div class="form-group">
      <label>Your Interests/Skills</label>
      <textarea id="missionInterests" class="form-control" rows="3" placeholder="What are you interested in? What skills do you have?"></textarea>
    </div>
    <button class="btn btn-ai" style="width: 100%;" onclick="getMissionIdeas()">
      <i class="bi bi-magic"></i> Get Personalized Mission Ideas
    </button>
    <div class="form-group" style="margin-top: 20px;">
      <label>Suggested Mission Ideas</label>
      <div id="missionIdeasList" style="min-height: 200px;">
        <p style="color: #999; margin: 0;">Mission ideas will appear here...</p>
      </div>
    </div>
  </div>
</div>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Gemini API Configuration (same as ai.php)
const GEMINI_KEY = 'AIzaSyAVD5YiIAilUzdm8x_CGKCMYI1Vmamd6TI';
const GEMINI_MODEL = 'gemini-2.5-flash';
const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_KEY}`;

// Store current task IDs
let currentTaskIds = {
    social: 0,
    translation: 0,
    poster: 0,
    file: 0
};

// Modal functions
function openSocialShareModal(taskId, taskContent) {
    currentTaskIds.social = taskId;
    const eventTitleEl = document.getElementById('eventTitle');
    const eventDescriptionEl = document.getElementById('eventDescription');
    const modal = document.getElementById('socialShareModal');
    
    if (!modal || !eventTitleEl || !eventDescriptionEl) return;
    
    if (taskContent && taskContent !== '""' && taskContent !== '') {
        const content = typeof taskContent === 'string' ? taskContent : '';
        const lines = content.split('\n');
        eventTitleEl.value = lines[0] || '';
        eventDescriptionEl.value = lines.slice(1).join('\n') || '';
    } else {
        eventTitleEl.value = '';
        eventDescriptionEl.value = '';
    }
    modal.style.display = 'flex';
}

function openTranslationModal(taskId, taskContent, instructions) {
    currentTaskIds.translation = taskId;
    const originalTextEl = document.getElementById('originalText');
    const targetLanguageEl = document.getElementById('targetLanguage');
    const modal = document.getElementById('translationModal');
    
    if (!modal || !originalTextEl || !targetLanguageEl) return;
    
    if (taskContent && taskContent !== '""' && taskContent !== '') {
        originalTextEl.value = typeof taskContent === 'string' ? taskContent : '';
    } else {
        originalTextEl.value = '';
    }
    
    if (instructions && instructions !== '""' && instructions !== '') {
        const inst = typeof instructions === 'string' ? instructions : '';
        const langMatch = inst.match(/to (\w+)/i);
        if (langMatch) {
            const langMap = {
                'Spanish': 'es',
                'French': 'fr',
                'German': 'de',
                'Italian': 'it',
                'Portuguese': 'pt',
                'Chinese': 'zh',
                'Japanese': 'ja',
                'Arabic': 'ar'
            };
            const lang = langMap[langMatch[1]] || 'es';
            targetLanguageEl.value = lang;
        }
    }
    modal.style.display = 'flex';
}

function openPosterModal(taskId, taskData) {
    currentTaskIds.poster = taskId;
    const posterTitleEl = document.getElementById('posterTitle');
    const posterDateEl = document.getElementById('posterDate');
    const posterDescriptionEl = document.getElementById('posterDescription');
    const modal = document.getElementById('posterModal');
    
    if (!modal || !posterTitleEl || !posterDateEl || !posterDescriptionEl) return;
    
    if (taskData && typeof taskData === 'object' && Object.keys(taskData).length > 0) {
        posterTitleEl.value = taskData.title || '';
        posterDateEl.value = taskData.date || '';
        posterDescriptionEl.value = taskData.description || '';
    } else {
        posterTitleEl.value = '';
        posterDateEl.value = '';
        posterDescriptionEl.value = '';
    }
    modal.style.display = 'flex';
}

function openFileCreatorModal(taskId, taskData) {
    currentTaskIds.file = taskId;
    const documentTypeEl = document.getElementById('documentType');
    const documentTopicEl = document.getElementById('documentTopic');
    const documentDetailsEl = document.getElementById('documentDetails');
    const modal = document.getElementById('fileCreatorModal');
    
    if (!modal || !documentTypeEl || !documentTopicEl || !documentDetailsEl) return;
    
    if (taskData && typeof taskData === 'object' && Object.keys(taskData).length > 0) {
        documentTypeEl.value = taskData.type || 'guide';
        documentTopicEl.value = taskData.topic || '';
        documentDetailsEl.value = taskData.details || '';
    } else {
        documentTypeEl.value = 'guide';
        documentTopicEl.value = '';
        documentDetailsEl.value = '';
    }
    modal.style.display = 'flex';
}

function openAIContentModal() {
    const modal = document.getElementById('aiContentModal');
    if (modal) modal.style.display = 'flex';
}

function openAIWritingModal() {
    const modal = document.getElementById('aiWritingModal');
    if (modal) modal.style.display = 'flex';
}

function openAIMissionIdeasModal() {
    const modal = document.getElementById('aiMissionIdeasModal');
    if (modal) modal.style.display = 'flex';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// AI Functions (same format as ai.php)
async function callGeminiAPI(prompt) {
    try {
        if (!GEMINI_KEY || GEMINI_KEY === 'YOUR_API_KEY_HERE') {
            return 'Error: API key is missing. Please configure your Gemini API key.';
        }

        const response = await fetch(GEMINI_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contents: [{
                    parts: [{
                        text: prompt
                    }]
                }]
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error('API request failed: ' + response.status + ' - ' + (errorData.error?.message || ''));
        }
        
        const data = await response.json();
        if (data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts && data.candidates[0].content.parts[0]) {
            return data.candidates[0].content.parts[0].text;
        }
        if (data.error) {
            console.error('Gemini API Error:', data.error);
            return 'Error: ' + (data.error.message || 'API error occurred');
        }
        return 'Error generating content. Please try again.';
    } catch (error) {
        console.error('Error:', error);
        return 'Error generating content: ' + error.message;
    }
}

// Social Share Functions
async function generateSocialPost() {
    const titleEl = document.getElementById('eventTitle');
    const descriptionEl = document.getElementById('eventDescription');
    const generatedPostEl = document.getElementById('generatedPost');
    
    if (!titleEl || !descriptionEl || !generatedPostEl) return;
    
    const title = titleEl.value;
    const description = descriptionEl.value;
    
    if (!title || !description) {
        alert('Please fill in event title and description');
        return;
    }
    
    generatedPostEl.innerHTML = '<p style="color: #667eea; margin: 0;">Generating post...</p>';
    
    const prompt = `Create an engaging social media post for a volunteer event. Title: ${title}. Description: ${description}. Make it exciting and encourage people to participate. Include relevant hashtags.`;
    const generatedPost = await callGeminiAPI(prompt);
    
    if (generatedPost && !generatedPost.includes('Error')) {
        generatedPostEl.innerHTML = '<p style="margin: 0; white-space: pre-wrap;">' + generatedPost + '</p>';
    } else {
        generatedPostEl.innerHTML = '<p style="color: #dc3545; margin: 0;">' + generatedPost + '</p>';
    }
}

function shareToFacebook() {
    const postEl = document.getElementById('generatedPost');
    if (!postEl) return;
    const post = postEl.innerText;
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}&quote=${encodeURIComponent(post)}`;
    window.open(url, '_blank');
}

function shareToTwitter() {
    const postEl = document.getElementById('generatedPost');
    if (!postEl) return;
    const post = postEl.innerText;
    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(post)}`;
    window.open(url, '_blank');
}

function shareToLinkedIn() {
    const postEl = document.getElementById('generatedPost');
    if (!postEl) return;
    const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`;
    window.open(url, '_blank');
}

function shareToInstagram() {
    const postEl = document.getElementById('generatedPost');
    if (!postEl) return;
    alert('Copy the post text and share it on Instagram!');
    const post = postEl.innerText;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(post);
    }
}

async function completeSocialShare() {
    const titleEl = document.getElementById('eventTitle');
    if (!titleEl) return;
    const title = titleEl.value;
    if (!title) {
        alert('Please complete the mission first');
        return;
    }
    
    await completeMission('social_share', { title: title }, currentTaskIds.social);
}

// Translation Functions
async function translateText() {
    const originalTextEl = document.getElementById('originalText');
    const targetLanguageEl = document.getElementById('targetLanguage');
    const translatedTextEl = document.getElementById('translatedText');
    
    if (!originalTextEl || !targetLanguageEl || !translatedTextEl) return;
    
    const originalText = originalTextEl.value;
    const targetLanguage = targetLanguageEl.value;
    const languageNames = {
        'es': 'Spanish',
        'fr': 'French',
        'de': 'German',
        'it': 'Italian',
        'pt': 'Portuguese',
        'zh': 'Chinese',
        'ja': 'Japanese',
        'ar': 'Arabic'
    };
    
    if (!originalText) {
        alert('Please enter text to translate');
        return;
    }
    
    translatedTextEl.innerHTML = '<p style="color: #667eea; margin: 0;">Translating...</p>';
    
    const prompt = `Translate the following text to ${languageNames[targetLanguage]}. Keep the meaning and tone the same. Only provide the translation, no explanations. Text: ${originalText}`;
    const translated = await callGeminiAPI(prompt);
    
    if (translated && !translated.includes('Error')) {
        translatedTextEl.innerHTML = '<p style="margin: 0; white-space: pre-wrap;">' + translated + '</p>';
    } else {
        translatedTextEl.innerHTML = '<p style="color: #dc3545; margin: 0;">' + translated + '</p>';
    }
}

async function completeTranslation() {
    const originalTextEl = document.getElementById('originalText');
    const translatedTextEl = document.getElementById('translatedText');
    
    if (!originalTextEl || !translatedTextEl) return;
    
    const originalText = originalTextEl.value;
    const translatedText = translatedTextEl.innerText;
    
    if (!originalText || !translatedText || translatedText.includes('Translation will appear')) {
        alert('Please translate the text first');
        return;
    }
    
    await completeMission('translation', { 
        original: originalText, 
        translated: translatedText 
    }, currentTaskIds.translation);
}

// Poster Functions
async function generatePoster() {
    const titleEl = document.getElementById('posterTitle');
    const dateEl = document.getElementById('posterDate');
    const descriptionEl = document.getElementById('posterDescription');
    const styleEl = document.getElementById('posterStyle');
    const canvas = document.getElementById('posterCanvas');
    
    if (!titleEl || !canvas) return;
    
    const title = titleEl.value;
    const date = dateEl ? dateEl.value : '';
    const description = descriptionEl ? descriptionEl.value : '';
    const style = styleEl ? styleEl.value : 'modern';
    
    if (!title) {
        alert('Please enter a poster title');
        return;
    }
    
    canvas.innerHTML = `
        <div style="background: linear-gradient(135deg, #FFD3B5 0%, #FFAAA5 100%); padding: 40px; border-radius: 15px; color: #333; text-align: center; width: 100%;">
            <h1 style="font-size: 2.5rem; margin: 0 0 20px 0; color: #5c4d3c;">${title}</h1>
            ${date ? '<p style="font-size: 1.2rem; margin: 10px 0; color: #5c4d3c;"><i class="bi bi-calendar"></i> ' + date + '</p>' : ''}
            ${description ? '<p style="font-size: 1rem; margin: 20px 0; color: #333;">' + description + '</p>' : ''}
            <div style="margin-top: 30px;">
                <span style="background: #5c4d3c; color: white; padding: 10px 20px; border-radius: 25px; font-weight: 600;">Join Us!</span>
            </div>
        </div>
    `;
}

function downloadPoster() {
    const canvas = document.getElementById('posterCanvas');
    if (!canvas) return;
    // Simple download - convert to image
    const htmlContent = canvas.innerHTML;
    const blob = new Blob([htmlContent], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'volunteer-poster.html';
    link.click();
    alert('Poster saved! You can take a screenshot or print it.');
}

async function completePoster() {
    const titleEl = document.getElementById('posterTitle');
    if (!titleEl) return;
    const title = titleEl.value;
    if (!title) {
        alert('Please create a poster first');
        return;
    }
    
    await completeMission('poster_creation', { title: title }, currentTaskIds.poster);
}

// File Creator Functions
async function generateDocument() {
    const docTypeEl = document.getElementById('documentType');
    const topicEl = document.getElementById('documentTopic');
    const detailsEl = document.getElementById('documentDetails');
    const generatedDocumentEl = document.getElementById('generatedDocument');
    
    if (!docTypeEl || !topicEl || !generatedDocumentEl) return;
    
    const docType = docTypeEl.value;
    const topic = topicEl.value;
    const details = detailsEl ? detailsEl.value : '';
    
    if (!topic) {
        alert('Please enter a topic');
        return;
    }
    
    generatedDocumentEl.innerHTML = '<p style="color: #667eea; margin: 0;">Generating document...</p>';
    
    const typeNames = {
        'guide': 'Volunteer Guide',
        'template': 'Email Template',
        'checklist': 'Checklist',
        'faq': 'FAQ Document',
        'training': 'Training Material'
    };
    
    const prompt = `Create a ${typeNames[docType]} about ${topic}. ${details ? 'Additional details: ' + details : ''}. Make it comprehensive and useful for volunteers.`;
    const document = await callGeminiAPI(prompt);
    
    if (document && !document.includes('Error')) {
        generatedDocumentEl.innerHTML = '<div style="white-space: pre-wrap; line-height: 1.6;">' + document + '</div>';
    } else {
        generatedDocumentEl.innerHTML = '<p style="color: #dc3545; margin: 0;">' + document + '</p>';
    }
}

async function completeFileCreator() {
    const topicEl = document.getElementById('documentTopic');
    const docTypeEl = document.getElementById('documentType');
    
    if (!topicEl || !docTypeEl) return;
    
    const topic = topicEl.value;
    const docType = docTypeEl.value;
    
    if (!topic) {
        alert('Please generate a document first');
        return;
    }
    
    await completeMission('file_creation', { type: docType, topic: topic }, currentTaskIds.file);
}

// AI Content Generator
async function generateAIContent() {
    const contentTypeEl = document.getElementById('contentType');
    const topicEl = document.getElementById('contentTopic');
    const instructionsEl = document.getElementById('contentInstructions');
    const generatedAIContentEl = document.getElementById('generatedAIContent');
    
    if (!contentTypeEl || !topicEl || !generatedAIContentEl) return;
    
    const contentType = contentTypeEl.value;
    const topic = topicEl.value;
    const instructions = instructionsEl ? instructionsEl.value : '';
    
    if (!topic) {
        alert('Please enter a topic');
        return;
    }
    
    generatedAIContentEl.innerHTML = '<p style="color: #667eea;">Generating content...</p>';
    
    const typeNames = {
        'social': 'social media post',
        'email': 'email',
        'blog': 'blog post',
        'announcement': 'announcement'
    };
    
    const prompt = `Create a ${typeNames[contentType]} about ${topic}. ${instructions ? 'Instructions: ' + instructions : ''}. Make it engaging and professional.`;
    const content = await callGeminiAPI(prompt);
    
    if (content && !content.includes('Error')) {
        generatedAIContentEl.innerHTML = '<div style="white-space: pre-wrap; line-height: 1.6;">' + content + '</div>';
    } else {
        generatedAIContentEl.innerHTML = '<p style="color: #dc3545;">' + content + '</p>';
    }
}

// AI Writing Assistant
async function getWritingHelp() {
    const writingTypeEl = document.getElementById('writingType');
    const inputEl = document.getElementById('writingInput');
    const improvedWritingEl = document.getElementById('improvedWriting');
    
    if (!writingTypeEl || !inputEl || !improvedWritingEl) return;
    
    const writingType = writingTypeEl.value;
    const input = inputEl.value;
    
    if (!input) {
        alert('Please enter your draft or instructions');
        return;
    }
    
    improvedWritingEl.innerHTML = '<p style="color: #667eea;">Improving your text...</p>';
    
    const typeNames = {
        'email': 'email',
        'proposal': 'proposal',
        'report': 'report',
        'announcement': 'announcement',
        'other': 'document'
    };
    
    const prompt = `Help improve and rewrite this ${typeNames[writingType]}. Make it professional, clear, and engaging. Original text: ${input}`;
    const improved = await callGeminiAPI(prompt);
    
    if (improved && !improved.includes('Error')) {
        improvedWritingEl.innerHTML = '<div style="white-space: pre-wrap; line-height: 1.6;">' + improved + '</div>';
    } else {
        improvedWritingEl.innerHTML = '<p style="color: #dc3545;">' + improved + '</p>';
    }
}

// AI Mission Ideas
async function getMissionIdeas() {
    const interestsEl = document.getElementById('missionInterests');
    const missionIdeasListEl = document.getElementById('missionIdeasList');
    
    if (!interestsEl || !missionIdeasListEl) return;
    
    const interests = interestsEl.value;
    
    if (!interests) {
        alert('Please enter your interests or skills');
        return;
    }
    
    missionIdeasListEl.innerHTML = '<p style="color: #667eea;">Generating ideas...</p>';
    
    const prompt = `Based on these interests and skills: ${interests}, suggest 5 unique and creative mini volunteer missions that can be done online. Make them specific and actionable. Format as a numbered list.`;
    const ideas = await callGeminiAPI(prompt);
    
    if (ideas && !ideas.includes('Error')) {
        missionIdeasListEl.innerHTML = '<div style="white-space: pre-wrap; line-height: 1.6;">' + ideas + '</div>';
    } else {
        missionIdeasListEl.innerHTML = '<p style="color: #dc3545;">' + ideas + '</p>';
    }
}

// Complete Mission Function
async function completeMission(missionType, missionData, taskId) {
    try {
        const response = await fetch('mini_missions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                complete_mini_mission: '1',
                mission_type: missionType,
                mission_data: JSON.stringify(missionData),
                task_id: taskId
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert(`Mission completed! You earned ${result.points_earned} points!`);
            location.reload();
        } else {
            alert('Error completing mission. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error completing mission. Please try again.');
    }
}

// Initialize AOS
AOS.init();
</script>

</body>
</html>

