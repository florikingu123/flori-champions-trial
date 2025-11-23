<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$email = $_SESSION['email'];

// Get user's points (volunteer hours)
$stmt = $conn->prepare("SELECT points FROM family WHERE member_email = ?");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$user_points = $user_data['points'] ?? 0;

// Get completed missions count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM chore_verifications cv 
    JOIN chores c ON cv.chore_id = c.id 
    WHERE c.member_email = ? AND cv.status = 'approved'
");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
$completed_data = $result->fetch_assoc();
$stmt->close();

$completed_count = $completed_data['count'] ?? 0;

// Get mission history
$stmt = $conn->prepare("
    SELECT c.chore_name, c.points, cv.status
    FROM chores c
    LEFT JOIN chore_verifications cv ON c.id = cv.chore_id
    WHERE c.member_email = ?
    ORDER BY cv.verified_at DESC
    LIMIT 10
");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
$mission_history = [];
while ($row = $result->fetch_assoc()) {
    $mission_history[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VolunteerHub AI - Volunteer Support Assistant</title>
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
    body {
      background-color: #f8f9fa;
      font-family: 'Roboto', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .ai-features {
      padding: 60px 0;
      background: white;
    }

    .feature-card {
      background: #fff;
      border-radius: 15px;
      padding: 25px;
      height: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      border-top: 4px solid #FFD3B5;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .feature-icon {
      font-size: 2.5rem;
      color: #FFD3B5;
      margin-bottom: 20px;
    }

    .examples-section {
      padding: 60px 0;
      background: #f8f9fa;
    }

    .example-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      border-left: 4px solid #FFD3B5;
    }

    .example-card:hover {
      transform: translateX(5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }

    .example-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .example-icon {
      width: 45px;
      height: 45px;
      background: #FFD3B5;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: white;
      font-size: 1.5rem;
    }

    .example-card h3 {
      color: #333;
      font-size: 1.2rem;
      margin: 0;
    }

    .example-card p {
      color: #666;
      margin: 0;
      font-size: 1rem;
      line-height: 1.5;
    }

    .chat-container {
      max-width: 1000px;
      margin: 100px auto;
      padding: 20px;
    }

    .chat-box {
      background: #fff;
      padding: 20px;
      height: 400px;
      overflow-y: auto;
      overflow-x: hidden;
      border-radius: 15px;
      margin-top: 20px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      scroll-behavior: smooth;
    }

    .chat-box::-webkit-scrollbar {
      width: 8px;
    }
    
    .chat-box::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
      border-radius: 4px;
    }
    
    .chat-box::-webkit-scrollbar-thumb {
      background: #FFD3B5;
      border-radius: 4px;
    }

    .input-area {
      display: flex;
      margin-top: 20px;
      gap: 10px;
    }

    .input-field {
      flex: 1;
      padding: 15px;
      border-radius: 10px;
      border: 2px solid #eee;
      outline: none;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .input-field:focus {
      border-color: #FFD3B5;
      box-shadow: 0 0 0 3px rgba(255, 211, 181, 0.2);
    }

    .button {
      padding: 15px 30px;
      background: #FFD3B5;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .button:hover {
      background: #FFAAA5;
      transform: translateY(-2px);
    }

    .button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .loading-spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #ffffff;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .message {
      margin: 15px 0;
      padding: 15px;
      border-radius: 12px;
      max-width: 80%;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .message-you {
      background-color: #FFD3B5;
      color: white;
      margin-left: auto;
    }

    .message-gpt {
      background-color: #f8f9fa;
      border: 1px solid #eee;
      margin-right: auto;
    }

    .section-title {
      text-align: center;
      margin-bottom: 40px;
    }

    .section-title h2 {
      color: #333;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .section-title p {
      color: #666;
      font-size: 1.1rem;
    }

    .error-message {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.9rem;
      margin-top: 0.5rem;
      display: none;
    }

    .error-message.show {
      display: block;
    }

    @media (max-width: 768px) {
      .chat-box {
        height: 300px;
      }
    }

    .creative-ai-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    }
  </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">VolunteerHub</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
      <li><a href="member.php">Organization Center</a></li>      
        <li><a href="games.php">Engagement Zone</a></li>
        <li><a href="profile.php">My Profile</a></li>
        <li><a href="rew.php">Achievements</a></li>
        <li><a href="view_calendar.php">Events Calendar</a></li>
        <li><a href="ai.php" class="active">AI</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

  </div>
</header>

<body>
  <section id="hero" class="hero section dark-background">
    <div id="hero-carousel" class="carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
      <div class="container position-relative">
        <div class="carousel-item active">
          <div class="carousel-container">
            <h2>Welcome to VolunteerHub AI</h2>
            <p>Your intelligent volunteer support assistant that helps manage missions, track volunteer hours, and keep your organization organized. Get personalized suggestions and smart mission management at your fingertips.</p>
          </div>
        </div>

        <div class="carousel-item">
          <div class="carousel-container">
            <h2>Smart Mission Management</h2>
            <p>Let AI help you create balanced volunteer schedules, suggest mission assignments, and track completion. Make volunteer management effortless with intelligent automation.</p>
          </div>
        </div>

        <div class="carousel-item">
          <div class="carousel-container">
            <h2>Personalized Assistance</h2>
            <p>Get tailored recommendations for your organization's needs. From mission suggestions to achievement ideas, our AI adapts to your organization's unique goals and volunteer preferences.</p>
          </div>
        </div>

        <a class="carousel-control-prev" href="#hero-carousel" role="button" data-bs-slide="prev">
          <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
        </a>

        <a class="carousel-control-next" href="#hero-carousel" role="button" data-bs-slide="next">
          <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
        </a>

        <ol class="carousel-indicators"></ol>
      </div>
    </div>
  </section>

  <!-- Section 1: AI Chat Bot (AI-Powered) -->
  <section style="padding: 60px 0; background: white;">
    <div class="container">
      <div class="section-title">
        <h2>🤖 AI Chat Assistant</h2>
        <p>Powered by Google Gemini - Your intelligent volunteer support companion</p>
      </div>
      <div class="chat-container" style="max-width: 900px; margin: 0 auto;">
        <div id="chat-box" class="chat-box" style="background: #f8f9fa; padding: 20px; height: 400px; overflow-y: auto; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
          <div class="message message-gpt">
            <div style="color: #666; font-style: italic;">
              Hello! I'm your VolunteerHub AI assistant powered by Google Gemini. Ask me anything about volunteer management, missions, achievements, or get personalized advice!
            </div>
          </div>
        </div>
        <form id="chat-form" class="input-area" onsubmit="return false;">
          <input type="text" id="user-input" class="input-field" placeholder="Ask something about volunteering..." required style="flex: 1; padding: 15px; border-radius: 10px; border: 2px solid #e0e0e0; font-size: 1rem;" />
          <button type="submit" class="button" id="send-btn" style="background: #5c4d3c; color: white; border: none; padding: 15px 30px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">
            <i class="bi bi-send"></i> Send
          </button>
        </form>
        <div id="chat-error" class="error-message"></div>
      </div>
    </div>
  </section>

  <!-- Section 2: AI Mission Suggestions (AI-Powered) -->
  <section style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
      <div class="section-title">
        <h2>💡 AI Mission Suggestions</h2>
        <p>Get personalized volunteer mission recommendations based on your interests and skills</p>
      </div>
      <div style="max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <form id="mission-suggestion-form" style="margin-bottom: 30px;" onsubmit="return false;">
          <div style="margin-bottom: 20px;">
            <label style="display: block; color: #5c4d3c; font-weight: 600; margin-bottom: 8px;">Your Interests:</label>
            <input type="text" id="user-interests" placeholder="e.g., Environmental, Education, Healthcare" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;" />
          </div>
          <div style="margin-bottom: 20px;">
            <label style="display: block; color: #5c4d3c; font-weight: 600; margin-bottom: 8px;">Your Skills:</label>
            <input type="text" id="user-skills" placeholder="e.g., Teaching, Organizing, Communication" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;" />
          </div>
          <div style="margin-bottom: 20px;">
            <label style="display: block; color: #5c4d3c; font-weight: 600; margin-bottom: 8px;">Available Time:</label>
            <select id="available-time" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;">
              <option>1-2 hours per week</option>
              <option>3-5 hours per week</option>
              <option>6-10 hours per week</option>
              <option>10+ hours per week</option>
            </select>
          </div>
          <button type="submit" style="background: #5c4d3c; color: white; border: none; padding: 15px 40px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 1.1rem; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">
            <i class="bi bi-lightbulb"></i> Get AI Suggestions
          </button>
        </form>
        <div id="mission-suggestions-result" style="min-height: 100px; padding: 20px; background: #f8f9fa; border-radius: 10px; display: none;"></div>
      </div>
    </div>
  </section>

  <!-- Section 3: AI Impact Analysis (AI-Powered) -->
  <section style="padding: 60px 0; background: white;">
    <div class="container">
      <div class="section-title">
        <h2>📊 AI Impact Analysis</h2>
        <p>Analyze your volunteer impact with AI-powered insights and visualizations</p>
      </div>
      <div style="max-width: 800px; margin: 0 auto; background: #f8f9fa; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <form id="impact-analysis-form" style="margin-bottom: 30px;" onsubmit="return false;">
          <div style="margin-bottom: 20px;">
            <label style="display: block; color: #5c4d3c; font-weight: 600; margin-bottom: 8px;">Describe Your Volunteer Work:</label>
            <textarea id="volunteer-description" placeholder="Tell us about your missions, hours, and impact..." rows="5" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; resize: vertical;"></textarea>
          </div>
          <button type="submit" style="background: #5c4d3c; color: white; border: none; padding: 15px 40px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 1.1rem; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">
            <i class="bi bi-graph-up"></i> Analyze My Impact
          </button>
        </form>
        <div id="impact-analysis-result" style="min-height: 100px; padding: 20px; background: white; border-radius: 10px; display: none;"></div>
      </div>
    </div>
  </section>

  <!-- Section 4: AI Guide (Info Section) -->
  <section style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
      <div class="section-title">
        <h2>📖 AI Assistant Guide</h2>
        <p>Learn how to get the most out of VolunteerHub AI</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-6">
          <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); height: 100%;">
            <h3 style="color: #5c4d3c; margin-bottom: 20px;"><i class="bi bi-chat-dots"></i> Using the Chat Bot</h3>
            <ul style="color: #666; line-height: 2;">
              <li><strong>Ask specific questions:</strong> "How do I organize a food drive?"</li>
              <li><strong>Get step-by-step guides:</strong> "Give me a checklist for park cleanup"</li>
              <li><strong>Request advice:</strong> "What's the best way to motivate volunteers?"</li>
              <li><strong>Ask for examples:</strong> "Show me successful volunteer mission examples"</li>
              <li><strong>Get personalized tips:</strong> "How can I improve my volunteer impact?"</li>
            </ul>
          </div>
        </div>
        <div class="col-lg-6">
          <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); height: 100%;">
            <h3 style="color: #5c4d3c; margin-bottom: 20px;"><i class="bi bi-gear"></i> AI Technology</h3>
            <div style="color: #666; line-height: 1.8;">
              <p><strong>Powered by Google Gemini:</strong> Our AI uses Google's advanced Gemini 2.5 Flash model for intelligent, context-aware responses.</p>
              <p><strong>Real-time Processing:</strong> Get instant answers to your volunteer questions.</p>
              <p><strong>Personalized Responses:</strong> AI adapts to your specific needs and context.</p>
              <p><strong>Safe & Secure:</strong> Your data is processed securely through Google's infrastructure.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Section 5: Best Practices (Info Section) -->
  <section style="padding: 60px 0; background: white;">
    <div class="container">
      <div class="section-title">
        <h2>✨ Best Practices</h2>
        <p>Tips for effective volunteer management and engagement</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-4">
          <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; border-left: 4px solid #5c4d3c; height: 100%;">
            <h4 style="color: #5c4d3c; margin-bottom: 15px;"><i class="bi bi-check-circle"></i> Mission Planning</h4>
            <ul style="color: #666; line-height: 2;">
              <li>Set clear objectives</li>
              <li>Define roles and responsibilities</li>
              <li>Prepare necessary materials</li>
              <li>Plan for safety measures</li>
            </ul>
          </div>
        </div>
        <div class="col-lg-4">
          <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; border-left: 4px solid #FFD3B5; height: 100%;">
            <h4 style="color: #5c4d3c; margin-bottom: 15px;"><i class="bi bi-people"></i> Volunteer Engagement</h4>
            <ul style="color: #666; line-height: 2;">
              <li>Recognize achievements regularly</li>
              <li>Provide meaningful feedback</li>
              <li>Create team-building opportunities</li>
              <li>Celebrate milestones together</li>
            </ul>
          </div>
        </div>
        <div class="col-lg-4">
          <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; border-left: 4px solid #28a745; height: 100%;">
            <h4 style="color: #5c4d3c; margin-bottom: 15px;"><i class="bi bi-graph-up"></i> Impact Tracking</h4>
            <ul style="color: #666; line-height: 2;">
              <li>Document volunteer hours accurately</li>
              <li>Track mission completion rates</li>
              <li>Measure community impact</li>
              <li>Use data to improve strategies</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Old sections removed - keeping only the 5 sections above -->
  <section style="display: none;" class="examples-section" style="background: white; padding: 60px 0;">
    <div class="container">
      <div class="section-title">
        <h2>AI Quick Actions</h2>
        <p>Use these powerful AI tools powered by Gemini to enhance your volunteer experience</p>
      </div>
      <div class="row gy-3 mb-5">
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('mission-prep')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #5c4d3c, #FFD3B5); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-clipboard-check" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Mission Prep Guide</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Get step-by-step preparation</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('impact-story')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-book" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Impact Story Generator</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Create compelling stories</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('goal-setter')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #f093fb, #f5576c); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-bullseye" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Goal Setter</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Set & track volunteer goals</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('reflection-journal')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-journal-text" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Reflection Journal</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">AI-guided reflection</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('skill-matcher')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #fa709a, #fee140); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-diagram-3" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Skill Matcher</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Match skills to missions</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('impact-calculator')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #30cfd0, #330867); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-calculator" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Impact Calculator</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Calculate your impact</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('motivation-coach')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-heart" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Motivation Coach</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.8;">Get daily motivation</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('career-advice')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #ffecd2, #fcb69f); color: #333; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-briefcase" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Career Advisor</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.8;">Career guidance</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('journey-visualizer')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #a8c0ff, #3f5efb); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-graph-up-arrow" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Journey Visualizer</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Visualize your volunteer journey</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('impact-visualizer')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #fbc2eb, #a6c1ee); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-bar-chart" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Impact Visualizer</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Build impact visualizations</p>
          </button>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <button onclick="useAIFeature('motivation-generator')" class="ai-quick-btn" style="width: 100%; padding: 20px; background: linear-gradient(135deg, #ff9a9e, #fecfef); color: white; border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; text-align: center;">
            <i class="bi bi-lightning-charge" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
            <strong>Motivation Generator</strong>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; opacity: 0.9;">Get personalized motivation</p>
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Creative AI Features Section -->
  <section class="examples-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 80px 0;">
    <div class="container">
      <div class="section-title" style="color: white;">
        <h2 style="color: white;">✨ Creative AI Tools</h2>
        <p style="color: rgba(255,255,255,0.9);">Unique AI-powered features that make volunteering fun and creative!</p>
      </div>
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('photo-story')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">📸</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Mission Photo Story Creator</h4>
            <p style="color: #666;">Transform your mission photos into compelling visual stories with AI!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">CREATIVE</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('personality-battle')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">⚔️</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Volunteer Personality Battle</h4>
            <p style="color: #666;">Battle different volunteer personalities and see which one wins!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">FUN</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('comic-generator')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🎨</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Impact Story Comic Generator</h4>
            <p style="color: #666;">Create comic strips from your volunteer impact stories!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">ARTISTIC</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('time-machine')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🕰️</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Volunteer Time Machine</h4>
            <p style="color: #666;">Travel through your volunteer timeline with AI-powered insights!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">TIME TRAVEL</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('podcast-generator')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🎙️</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Mission Podcast Generator</h4>
            <p style="color: #666;">Generate podcast-style stories from your volunteer missions!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">AUDIO</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('avatar-creator')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">👤</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Volunteer Avatar Creator</h4>
            <p style="color: #666;">Create a custom avatar based on your volunteer personality!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">PERSONAL</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('treasure-hunt')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🗺️</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Mission Treasure Hunt</h4>
            <p style="color: #666;">AI generates clues leading to hidden volunteer missions!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">ADVENTURE</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('speedrun-analyzer')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">⚡</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Mission Speedrun Analyzer</h4>
            <p style="color: #666;">Analyze how to complete missions faster with AI optimization!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">OPTIMIZATION</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="creative-ai-card" onclick="useAIFeature('marketplace-ideas')" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: 100%;">
            <div style="font-size: 4rem; margin-bottom: 20px;">💡</div>
            <h4 style="color: #667eea; margin-bottom: 15px;">Mission Idea Marketplace</h4>
            <p style="color: #666;">AI generates and rates creative volunteer mission ideas!</p>
            <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem;">IDEAS</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="examples-section">
    <div class="container">
      <div class="section-title">
        <h2>Volunteer Mission Examples</h2>
        <p>Ask our AI assistant for step-by-step guidance on completing volunteer missions</p>
      </div>
      <div class="row">
        <div class="col-lg-6">
          <div class="example-card">
            <div class="example-header">
              <div class="example-icon">
                <i class="bi bi-people"></i>
              </div>
              <h3>Community Outreach</h3>
            </div>
            <p>"How do I organize a community food drive? What steps should I follow?"</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="example-card">
            <div class="example-header">
              <div class="example-icon">
                <i class="bi bi-tree"></i>
              </div>
              <h3>Environmental Cleanup</h3>
            </div>
            <p>"What's the best way to organize a park cleanup event? Can you give me a checklist?"</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="example-card">
            <div class="example-header">
              <div class="example-icon">
                <i class="bi bi-book"></i>
              </div>
              <h3>Educational Support</h3>
            </div>
            <p>"How do I tutor students effectively? What teaching methods work best?"</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="example-card">
            <div class="example-header">
              <div class="example-icon">
                <i class="bi bi-heart"></i>
              </div>
              <h3>Elderly Care</h3>
            </div>
            <p>"Can you explain how to assist elderly community members properly?"</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="example-card">
            <div class="example-header">
              <div class="example-icon">
                <i class="bi bi-shield-check"></i>
              </div>
              <h3>Event Planning</h3>
            </div>
            <p>"What do I need to do to organize a volunteer event? How should I coordinate with team members?"</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="example-card">
            <div class="example-header">
              <div class="example-icon">
                <i class="bi bi-building"></i>
              </div>
              <h3>Fundraising Mission</h3>
            </div>
            <p>"How do I plan a successful fundraising campaign? What's the best way to engage donors?"</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer id="footer" class="footer dark-background">

<div class="footer-newsletter">
  <div class="container">
    <div class="row justify-content-center text-center">
      <div class="col-lg-6">
        <h4>Join Our Newsletter</h4>
        <p>Subscribe to our newsletter and receive the latest news about our products and services!</p>
        <form action="newsletter.php" method="post" class="php-email-form">
          <div class="newsletter-form">
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="submit" value="Subscribe">
          </div>
          <div class="loading">Loading</div>
          <div class="sent-message" style="display:none;">Your subscription request has been sent. Thank you!</div>
        </form>
      </div>
    </div>
  </div>
</div>

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

    <div class="col-lg-2 col-md-3 footer-links">
      <h4>Useful Links</h4>
      <ul>
        <li><i class="bi bi-chevron-right"></i> <a href="index.html">Home</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="about.html" class="active">About</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="services.html">Services</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="portfolio.html">Portfolio</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="team.html">Team</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="blog.html">Blog</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="contact.html">Contact</a></li>
      </ul>
    </div>

    <div class="col-lg-2 col-md-3 footer-links">
      <h4>Our Services</h4>
      <ul>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Managing Your Volunteer Organization</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">How VolunteerHub Keeps Your Organization on Track</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Manage Chores and Rewards</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">Effortless Communication</a></li>
      </ul>
    </div>

    <div class="col-lg-4 col-md-12">
      <h4>Follow Us</h4>
      <div class="social-links d-flex">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
    </div>

  </div>
</div>

<div class="container copyright text-center mt-4">
  <p>© <span>Copyright</span> <strong class="px-1 sitename">VolunteerHub</strong> <span>All Rights Reserved</span></p>
</div>

</footer>

  <script>
    // Gemini API Configuration
    const GEMINI_KEY = 'AIzaSyAVD5YiIAilUzdm8x_CGKCMYI1Vmamd6TI';
    const GEMINI_MODEL = 'gemini-2.5-flash';
    const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_KEY}`;

    // DOM Elements - will be initialized when DOM is ready
    let form, input, chatBox, sendBtn, errorDiv;

    // AI Quick Actions
    const aiFeatures = {
      'mission-prep': {
        prompt: 'I need a comprehensive step-by-step preparation guide for a volunteer mission. Please provide:\n1. Pre-mission checklist\n2. What to bring\n3. Safety considerations\n4. Best practices\n5. What to expect\n\nMake it detailed and practical for any type of volunteer mission.',
        title: 'Mission Preparation Guide'
      },
      'impact-story': {
        prompt: 'Help me create a compelling impact story about my volunteer work. Please:\n1. Ask me about my recent volunteer mission\n2. Help me structure a story that highlights the impact\n3. Include emotional elements and community benefits\n4. Make it shareable for social media or reports\n\nGuide me through creating this story.',
        title: 'Impact Story Generator'
      },
      'goal-setter': {
        prompt: 'I want to set volunteer goals. Please help me:\n1. Assess my current volunteer activity\n2. Set realistic short-term and long-term goals\n3. Create an action plan to achieve them\n4. Suggest milestones and tracking methods\n5. Provide motivation strategies\n\nMake it personalized and achievable.',
        title: 'Volunteer Goal Setter'
      },
      'reflection-journal': {
        prompt: 'I want to reflect on my volunteer experience. Please guide me with:\n1. Thoughtful reflection questions about my recent mission\n2. Questions about what I learned\n3. How I felt during the experience\n4. What impact I think I made\n5. How this experience changed me\n\nMake the questions deep and meaningful.',
        title: 'Reflection Journal'
      },
      'skill-matcher': {
        prompt: 'Help me match my skills and interests to suitable volunteer missions. Please:\n1. Ask about my skills, interests, and experience\n2. Suggest volunteer missions that align with my profile\n3. Explain why these missions are a good fit\n4. Provide tips for success in these missions\n\nMake personalized recommendations.',
        title: 'Skill Matcher'
      },
      'impact-calculator': {
        prompt: 'Help me calculate and visualize the impact of my volunteer work. Please:\n1. Ask about my volunteer hours and mission types\n2. Calculate real-world impact (people helped, environmental benefits, etc.)\n3. Create "what if" scenarios\n4. Show comparisons and visualizations\n5. Provide impact projections\n\nMake it inspiring and data-driven.',
        title: 'Impact Calculator'
      },
      'motivation-coach': {
        prompt: 'I need motivation and encouragement for my volunteer journey. Please:\n1. Provide personalized motivational messages\n2. Share inspiring volunteer stories\n3. Help me overcome challenges or burnout\n4. Suggest ways to stay engaged\n5. Celebrate my achievements\n\nBe encouraging and supportive.',
        title: 'Motivation Coach'
      },
      'career-advice': {
        prompt: 'I want career advice based on my volunteer experience. Please help me:\n1. Identify transferable skills from volunteering\n2. Build a resume highlighting volunteer work\n3. Network effectively in the volunteer community\n4. Use volunteer experience in job interviews\n5. Find career paths aligned with my volunteer interests\n\nMake it practical and actionable.',
        title: 'Career Advisor'
      },
      'journey-visualizer': {
        prompt: 'I want to visualize my complete volunteer journey. Please:\n1. Analyze my volunteer history and create a comprehensive timeline\n2. Show my growth trajectory with milestones and achievements\n3. Create visual representations (charts, graphs, timelines) of my journey\n4. Highlight key moments, turning points, and significant achievements\n5. Predict future milestones based on my current trajectory\n6. Create an inspiring narrative of my volunteer evolution\n\nMake it visually rich and inspiring with detailed analysis.',
        title: 'Volunteer Journey Visualizer',
        requiresData: true
      },
      'impact-visualizer': {
        prompt: 'I want to create powerful visualizations of my volunteer impact. Please:\n1. Analyze my volunteer hours and mission types\n2. Create multiple visualization formats (charts, graphs, infographics)\n3. Calculate real-world impact metrics (people helped, environmental benefits, etc.)\n4. Generate "what if" scenarios showing potential future impact\n5. Create shareable visual content (social media graphics, reports)\n6. Build interactive visualizations that tell a compelling story\n\nMake it data-driven, visually stunning, and shareable.',
        title: 'Impact Visualization Builder',
        requiresData: true
      },
      'motivation-generator': {
        prompt: 'I need personalized motivation messages for my volunteer journey. Please:\n1. Analyze my current volunteer activity and achievements\n2. Generate multiple personalized motivation messages tailored to my journey\n3. Include inspiring quotes, encouragement, and recognition of my efforts\n4. Create messages for different contexts (daily motivation, milestone celebration, challenge encouragement)\n5. Make messages specific to my volunteer style and accomplishments\n6. Provide actionable encouragement that keeps me engaged\n\nMake it personal, inspiring, and motivating.',
        title: 'Mission Motivation Message Generator',
        requiresData: true
      },
      'photo-story': {
        prompt: 'I want to create a compelling visual story from my mission photos. Please:\n1. Help me structure a narrative from my volunteer mission photos\n2. Create engaging captions and descriptions for each photo\n3. Build a cohesive story that shows the impact and journey\n4. Suggest visual enhancements and storytelling techniques\n5. Create shareable formats (social media posts, blog posts, presentations)\n6. Make it emotionally engaging and inspiring\n\nTransform my photos into a powerful visual narrative.',
        title: 'Mission Photo Story Creator',
        requiresData: true
      },
      'personality-battle': {
        prompt: 'I want to see a fun battle between different volunteer personality types. Please:\n1. Create a battle scenario between different volunteer archetypes (Impact Warrior, Social Connector, Skill Seeker, etc.)\n2. Give each personality unique abilities and strengths\n3. Create an entertaining narrative of the battle\n4. Show which personality type would win in different scenarios\n5. Make it fun, creative, and engaging\n6. Include humor and personality quirks\n\nMake it an entertaining and creative battle!',
        title: 'Volunteer Personality Battle',
        requiresData: false
      },
      'comic-generator': {
        prompt: 'I want to create a comic strip from my volunteer impact story. Please:\n1. Help me create a comic strip narrative from my volunteer experience\n2. Design characters based on my volunteer style\n3. Create panels with dialogue and action\n4. Make it visually descriptive and engaging\n5. Include humor, emotion, and impact\n6. Format it as a shareable comic strip\n\nMake it creative, fun, and visually appealing!',
        title: 'Impact Story Comic Generator',
        requiresData: true
      },
      'time-machine': {
        prompt: 'I want to travel through my volunteer timeline. Please:\n1. Create a detailed timeline of my volunteer journey\n2. Show key moments, milestones, and turning points\n3. Create "what if" scenarios for different time periods\n4. Show how my past missions led to current achievements\n5. Predict future possibilities based on my journey\n6. Make it feel like time travel with vivid descriptions\n\nMake it an immersive time travel experience!',
        title: 'Volunteer Time Machine',
        requiresData: true
      },
      'podcast-generator': {
        prompt: 'I want to create a podcast-style story from my volunteer missions. Please:\n1. Create a podcast script format from my volunteer experiences\n2. Include engaging narration, interviews, and storytelling\n3. Add dramatic moments and emotional highlights\n4. Create episode structure with intro, main content, and outro\n5. Include sound effect suggestions and music cues\n6. Make it feel like a real podcast episode\n\nMake it engaging and podcast-ready!',
        title: 'Mission Podcast Generator',
        requiresData: true
      },
      'avatar-creator': {
        prompt: 'I want to create a custom volunteer avatar based on my personality. Please:\n1. Analyze my volunteer style and personality\n2. Design a unique avatar that represents my volunteer identity\n3. Include visual details (appearance, clothing, accessories, symbols)\n4. Create a backstory for my avatar\n5. Suggest customization options based on my achievements\n6. Make it personal and representative of my volunteer journey\n\nCreate a unique avatar that represents me!',
        title: 'Volunteer Avatar Creator',
        requiresData: true
      },
      'treasure-hunt': {
        prompt: 'I want AI to generate a treasure hunt for volunteer missions. Please:\n1. Create a series of clues leading to hidden volunteer missions\n2. Make clues creative, challenging, and fun\n3. Design a treasure hunt narrative with a story\n4. Include riddles, puzzles, and hints\n5. Create multiple difficulty levels\n6. Make it an exciting adventure\n\nGenerate an exciting treasure hunt!',
        title: 'Mission Treasure Hunt',
        requiresData: false
      },
      'speedrun-analyzer': {
        prompt: 'I want to optimize my mission completion speed. Please:\n1. Analyze my mission completion patterns\n2. Identify time-saving strategies and optimizations\n3. Create a speedrun guide for common mission types\n4. Suggest efficiency improvements\n5. Create leaderboard-style challenges\n6. Make it competitive and fun\n\nHelp me become a mission speedrun champion!',
        title: 'Mission Speedrun Analyzer',
        requiresData: true
      },
      'marketplace-ideas': {
        prompt: 'I want AI to generate creative volunteer mission ideas. Please:\n1. Generate unique and creative volunteer mission ideas\n2. Rate each idea for feasibility, impact, and creativity\n3. Create detailed descriptions and implementation guides\n4. Suggest variations and improvements\n5. Create a marketplace-style presentation\n6. Make ideas innovative and engaging\n\nGenerate amazing mission ideas!',
        title: 'Mission Idea Marketplace',
        requiresData: false
      }
    };

    // Volunteer data for AI features
    const volunteerData = {
      hours: <?= $user_points ?>,
      completedMissions: <?= $completed_count ?>,
      missionHistory: <?= json_encode($mission_history) ?>,
      email: '<?= htmlspecialchars($email) ?>'
    };

    function useAIFeature(featureKey) {
      const feature = aiFeatures[featureKey];
      if (!feature) return;
      
      // Scroll to chat
      document.getElementById('chat-box').scrollIntoView({ behavior: 'smooth', block: 'start' });
      
      // Build enhanced prompt with volunteer data if needed
      let enhancedPrompt = feature.prompt;
      if (feature.requiresData && volunteerData) {
        const dataContext = `
        
MY VOLUNTEER DATA:
- Total Volunteer Hours: ${volunteerData.hours}
- Completed Missions: ${volunteerData.completedMissions}
- Recent Mission History:
${volunteerData.missionHistory.slice(0, 10).map((m, i) => `${i+1}. ${m.chore_name || 'Mission'} - ${m.points || 0} hours - Status: ${m.status || 'pending'}`).join('\n')}

Please use this data to provide personalized and accurate insights.`;
        enhancedPrompt = feature.prompt + dataContext;
      }
      
      // Set input value
      const input = document.getElementById('user-input');
      input.value = enhancedPrompt;
      input.focus();
      
      // Show notification
      const notification = document.createElement('div');
      notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #5c4d3c; color: white; padding: 15px 20px; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 12px rgba(0,0,0,0.2);';
      notification.innerHTML = `<i class="bi bi-check-circle"></i> ${feature.title} ready! Click send to start.`;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initChatbot);
    } else {
      initChatbot();
    }

    function initChatbot() {
      console.log('Initializing VolunteerHub AI chatbot...');

      // Get DOM elements
      form = document.getElementById('chat-form');
      input = document.getElementById('user-input');
      chatBox = document.getElementById('chat-box');
      sendBtn = document.getElementById('send-btn');
      errorDiv = document.getElementById('chat-error');

      // Validate API key
      if (!GEMINI_KEY || GEMINI_KEY === 'YOUR_API_KEY_HERE') {
        if (errorDiv) {
          showError('API key is missing. Please configure your Gemini API key.');
        }
        return;
      }

      // Event handlers for chat form
      if (form) {
        form.addEventListener('submit', handleSubmit);
      }
      if (input) {
        input.addEventListener('keypress', (e) => {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
          }
        });
      }

      console.log('Chatbot initialized successfully');
    }

    async function handleSubmit(e) {
      e.preventDefault();
      e.stopPropagation();

      if (!input) {
        input = document.getElementById('user-input');
      }
      if (!chatBox) {
        chatBox = document.getElementById('chat-box');
      }
      if (!sendBtn) {
        sendBtn = document.getElementById('send-btn');
      }
      if (!errorDiv) {
        errorDiv = document.getElementById('chat-error');
      }

      const message = input.value.trim();
      if (!message) {
        console.log('Empty message, not sending');
        return;
      }

      console.log('Sending message to Gemini API...');

      // Clear input and hide errors
      input.value = '';
      hideError();

      // Add user message to chat
      addMessage(message, 'user');

      // Update button state
      sendBtn.disabled = true;
      sendBtn.innerHTML = '<span class="loading-spinner"></span> Sending...';

      // Add loading message
      const loadingMessage = addMessage('Typing...', 'bot', true);

      try {
        // Make API request to Google Gemini
        const response = await fetch(GEMINI_API_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            contents: [{
              parts: [{
                text: message
              }]
            }],
            generationConfig: {
              temperature: 0.7,      // Creativity (0-1, higher = more creative)
              topK: 40,             // Diversity
              topP: 0.95,           // Nucleus sampling
              maxOutputTokens: 2000 // Maximum response length
            }
          })
        });

        console.log('Response status:', response.status);

        // Check for HTTP errors
        if (!response.ok) {
          const errorData = await response.json().catch(() => ({ 
            error: { message: `HTTP ${response.status}: ${response.statusText}` } 
          }));
          console.error('API error response:', errorData);
          throw new Error(errorData.error?.message || `API error: ${response.status} ${response.statusText}`);
        }

        // Parse response
        let data;
        try {
          const responseText = await response.text();
          console.log('Raw API response received');
          data = JSON.parse(responseText);
          console.log('Parsed API response data');
        } catch (parseError) {
          console.error('Failed to parse JSON:', parseError);
          throw new Error('Invalid JSON response from API');
        }

        // Extract AI message from response
        let aiMessage = '';

        // Check for API errors
        if (data.error) {
          throw new Error(data.error.message || data.error || 'API returned an error');
        }

        // Validate response structure
        if (!data.candidates || !Array.isArray(data.candidates) || data.candidates.length === 0) {
          console.error('Candidates array not found. Response keys:', Object.keys(data));
          throw new Error('Candidates array not found in response');
        }

        const candidate = data.candidates[0];

        // Handle different finish reasons
        if (candidate.finishReason === 'MAX_TOKENS') {
          // Response was cut off due to token limit
          if (candidate.content && candidate.content.parts && Array.isArray(candidate.content.parts) && candidate.content.parts.length > 0) {
            const part = candidate.content.parts[0];
            if (part && part.text) {
              aiMessage = part.text + '\n\n[Note: Response was cut off due to length limit]';
              console.warn('Response was cut off but partial text available');
            } else {
              throw new Error('Response was cut off during thinking phase. Please try a shorter question.');
            }
          } else {
            throw new Error('Response was cut off before any text could be generated. Please try a shorter question.');
          }
        } else if (candidate.finishReason === 'SAFETY') {
          throw new Error('Response was blocked by safety filters. Please try rephrasing your question.');
        }

        // Extract text from parts array
        if (!aiMessage) {
          if (!candidate.content) {
            throw new Error('Content property not found in candidate');
          }

          if (!candidate.content.parts || !Array.isArray(candidate.content.parts) || candidate.content.parts.length === 0) {
            console.error('Content structure:', candidate.content);
            throw new Error('Parts array not found in content. Finish reason: ' + (candidate.finishReason || 'unknown'));
          }

          const part = candidate.content.parts[0];
          if (!part) {
            throw new Error('First part is missing from parts array');
          }

          if (part.text) {
            aiMessage = part.text;
          } else if (typeof part === 'string') {
            aiMessage = part;
          } else {
            console.error('Part structure:', part);
            throw new Error('Text property not found in part');
          }
        }

        // Validate we have a message
        if (!aiMessage || aiMessage.trim() === '') {
          throw new Error('Empty response from API. Response may have been cut off.');
        }

        console.log('AI message extracted successfully');
        
        // Remove loading message and add AI response
        loadingMessage.remove();
        addMessage(aiMessage, 'bot');

      } catch (error) {
        console.error('Error:', error);
        
        // Remove loading message
        loadingMessage.remove();
        
        // Show error to user
        const errorMsg = error.message || 'Failed to get response from AI';
        showError(errorMsg);
        addMessage('Sorry, I encountered an error: ' + errorMsg, 'bot');
      } finally {
        // Reset button state
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="bi bi-send"></i> Send';
      }
    }

    function addMessage(text, type, isLoading = false) {
      if (!text || !text.trim()) return null;

      const messageElement = document.createElement('div');
      messageElement.classList.add('message', type === 'user' ? 'message-you' : 'message-gpt');
      
      // Use textNode for security (prevents XSS)
      const textNode = document.createTextNode(text);
      messageElement.appendChild(textNode);
      
      // Add loading class if needed
      if (isLoading) {
        messageElement.style.opacity = '0.7';
        messageElement.style.fontStyle = 'italic';
      }

      chatBox.appendChild(messageElement);
      
      // Auto-scroll to bottom
      setTimeout(() => {
        chatBox.scrollTop = chatBox.scrollHeight;
      }, 10);

      console.log('Message added to chat:', text.substring(0, 50) + '...', type);
      return messageElement;
    }

    function showError(message) {
      if (!errorDiv) {
        errorDiv = document.getElementById('chat-error');
      }
      if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.add('show');
      }
      console.error('Error displayed:', message);
    }

    function hideError() {
      if (!errorDiv) {
        errorDiv = document.getElementById('chat-error');
      }
      if (errorDiv) {
        errorDiv.classList.remove('show');
        errorDiv.textContent = '';
      }
    }

    // Make useAIFeature globally accessible
    window.useAIFeature = useAIFeature;

    // Initialize form handlers when DOM is ready
    function initFormHandlers() {
      // Handle Mission Suggestion Form
      const missionSuggestionForm = document.getElementById('mission-suggestion-form');
      if (missionSuggestionForm) {
        missionSuggestionForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          e.stopPropagation();
          const interests = document.getElementById('user-interests').value.trim();
          const skills = document.getElementById('user-skills').value.trim();
          const time = document.getElementById('available-time').value;
          const resultDiv = document.getElementById('mission-suggestions-result');
          if (!interests && !skills) {
            alert('Please fill in at least your interests or skills.');
            return;
          }
          resultDiv.style.display = 'block';
          resultDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><div class="loading-spinner"></div> <p>Getting AI suggestions...</p></div>';
          const prompt = `Based on the following information, suggest 3-5 specific volunteer missions:\n- Interests: ${interests || 'Not specified'}\n- Skills: ${skills || 'Not specified'}\n- Available Time: ${time}\n\nFor each mission, provide:\n1. Mission name\n2. Brief description\n3. Why it matches their profile\n4. Estimated time commitment\n5. Skills needed\n\nFormat the response clearly with numbered missions.`;
          try {
            const response = await fetch(GEMINI_API_URL, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                contents: [{ parts: [{ text: prompt }] }],
                generationConfig: { temperature: 0.7, maxOutputTokens: 2000 }
              })
            });
            if (!response.ok) throw new Error('API request failed');
            const data = await response.json();
            if (data.error) throw new Error(data.error.message);
            const aiMessage = data.candidates[0]?.content?.parts[0]?.text || 'No suggestions available.';
            resultDiv.innerHTML = `<div style="background: white; padding: 20px; border-radius: 10px; border-left: 4px solid #5c4d3c;"><h4 style="color: #5c4d3c; margin-bottom: 15px;">AI Mission Suggestions:</h4><div style="white-space: pre-wrap; line-height: 1.6; color: #333;">${aiMessage.replace(/\n/g, '<br>')}</div></div>`;
          } catch (error) {
            resultDiv.innerHTML = `<div style="background: #fee; padding: 20px; border-radius: 10px; color: #c33;">Error: ${error.message}</div>`;
          }
        });
      }
    }

    // Handle Impact Analysis Form
    function initImpactForm() {
      const impactAnalysisForm = document.getElementById('impact-analysis-form');
      if (impactAnalysisForm) {
        impactAnalysisForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          e.stopPropagation();
          const description = document.getElementById('volunteer-description').value.trim();
          const resultDiv = document.getElementById('impact-analysis-result');
          if (!description) {
            alert('Please describe your volunteer work.');
            return;
          }
          resultDiv.style.display = 'block';
          resultDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><div class="loading-spinner"></div> <p>Analyzing your impact...</p></div>';
          const prompt = `Analyze the following volunteer work and provide:\n1. Impact Summary: Key achievements and outcomes\n2. Metrics: Quantifiable impact (people helped, hours contributed, etc.)\n3. Skills Developed: What skills were gained or used\n4. Community Benefits: How the community benefited\n5. Personal Growth: How this experience contributed to personal development\n6. Recommendations: Suggestions for future volunteer work\n\nVolunteer Work Description:\n${description}\n\nMake the analysis detailed, inspiring, and data-driven.`;
          try {
            const response = await fetch(GEMINI_API_URL, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                contents: [{ parts: [{ text: prompt }] }],
                generationConfig: { temperature: 0.7, maxOutputTokens: 2000 }
              })
            });
            if (!response.ok) throw new Error('API request failed');
            const data = await response.json();
            if (data.error) throw new Error(data.error.message);
            const aiMessage = data.candidates[0]?.content?.parts[0]?.text || 'No analysis available.';
            resultDiv.innerHTML = `<div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #5c4d3c;"><h4 style="color: #5c4d3c; margin-bottom: 15px;">AI Impact Analysis:</h4><div style="white-space: pre-wrap; line-height: 1.6; color: #333;">${aiMessage.replace(/\n/g, '<br>')}</div></div>`;
          } catch (error) {
            resultDiv.innerHTML = `<div style="background: #fee; padding: 20px; border-radius: 10px; color: #c33;">Error: ${error.message}</div>`;
          }
        });
      }
    }

    // Initialize all form handlers when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() {
        initFormHandlers();
        initImpactForm();
      });
    } else {
      initFormHandlers();
      initImpactForm();
    }
  </script>
</body>
</html>