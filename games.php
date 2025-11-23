<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "famify");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user's points and game stats
$email = $_SESSION['email'];

// First, ensure game stats columns exist (MySQL doesn't support IF NOT EXISTS in ALTER TABLE, so we check first)
$check_columns = $conn->query("SHOW COLUMNS FROM family LIKE 'games_played'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE family ADD COLUMN games_played INT DEFAULT 0");
}
$check_columns = $conn->query("SHOW COLUMNS FROM family LIKE 'games_won'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE family ADD COLUMN games_won INT DEFAULT 0");
}
$check_columns = $conn->query("SHOW COLUMNS FROM family LIKE 'game_points'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE family ADD COLUMN game_points INT DEFAULT 0");
}

$stats_query = "SELECT points, COALESCE(games_played, 0) as games_played, COALESCE(games_won, 0) as games_won, COALESCE(game_points, 0) as game_points FROM family WHERE member_email = ?";
$stmt = $conn->prepare($stats_query);
if (!$stmt) {
    // If columns don't exist, try without them
    $stats_query = "SELECT points FROM family WHERE member_email = ?";
    $stmt = $conn->prepare($stats_query);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user_points = 0;
$games_played = 0;
$games_won = 0;
$game_points = 0;
if ($row = $result->fetch_assoc()) {
    $user_points = $row['points'] ?? 0;
    $games_played = $row['games_played'] ?? 0;
    $games_won = $row['games_won'] ?? 0;
    $game_points = $row['game_points'] ?? 0;
}
$stmt->close();

// Calculate win rate
$win_rate = $games_played > 0 ? round(($games_won / $games_played) * 100) : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Engagement Zone - VolunteerHub</title>
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
    .game-buttons {
        padding: 40px 0;
    }

    .btn-game {
        display: inline-block;
        background: white;
        padding: 20px;
        margin: 10px;
        border-radius: 15px;
        text-decoration: none;
        color: #333;
        width: 250px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .btn-game:hover {
        transform: translateY(-5px);
        color: #333;
    }

    .game-icon {
        font-size: 2.5rem;
        color: #FFD3B5;
        margin-bottom: 10px;
    }

    .game-title {
        display: block;
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .game-description {
        display: block;
        font-size: 0.9rem;
        color: #666;
    }

    .game-stats-section {
        padding: 60px 0;
        background: #f8f9fa;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        height: 100%;
    }

    .stat-icon {
        font-size: 2.5rem;
        color: #FFD3B5;
        margin-bottom: 20px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin: 10px 0;
    }

    .points-section {
        padding: 60px 0;
        background: white;
    }

    .points-card {
        background: linear-gradient(135deg, #FFD3B5, #FFAAA5);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .points-value {
        font-size: 3rem;
        font-weight: bold;
        margin: 20px 0;
    }

    .fun-section {
        padding: 60px 0;
        background: #f8f9fa;
    }

    .fun-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        height: 100%;
    }

    .fun-icon {
        font-size: 2.5rem;
        color: #FFD3B5;
        margin-bottom: 20px;
    }

    .creative-game-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border: 2px solid #FFD3B5;
        position: relative;
        overflow: hidden;
        height: 100%;
        min-height: 250px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .creative-game-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border-color: #5c4d3c;
    }

    .game-icon-large {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .creative-game-card h4 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #5c4d3c;
    }

    .creative-game-card p {
        font-size: 1rem;
        margin-bottom: 15px;
        color: #666;
    }

    .game-badge {
        display: inline-block;
        background: #FFD3B5;
        color: #5c4d3c;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
  </style>
</head>
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
    <a href="index.html" class="logo d-flex align-items-center">
      <h1 class="sitename">VolunteerHub</h1>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="member.php">Organization Center</a></li>      
        <li><a href="games.php" class="active">Engagement Zone</a></li>
        <li><a href="profile.php">My Profile</a></li>
        <li><a href="rew.php">Achievements</a></li>
        <li><a href="view_calendar.php">Events Calendar</a></li>
        <li><a href="ai.php">AI</a></li>
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
                        <h2>Engagement Zone</h2>
                        <p>Engage with fun activities and team-building games! Participate in community challenges, test your skills, and earn volunteer credits.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Games Section -->
    <section style="padding: 60px 0; background: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="color: #5c4d3c; font-size: 2.5rem; margin-bottom: 15px;">🎮 Entertainment Zone</h2>
                <p style="color: #666; font-size: 1.2rem;">Discover who you are and have fun while learning!</p>
            </div>
            
            <!-- Featured: What Kind of Volunteer Are You? -->
            <div class="row mb-5">
                <div class="col-12">
                    <div style="background: white; border-radius: 20px; padding: 40px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid #FFD3B5;">
                        <h3 style="color: #5c4d3c; font-size: 2rem; margin-bottom: 20px;">🔬 What Kind of Volunteer Are You?</h3>
                        <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">Take our interactive personality test to discover your unique volunteer DNA!</p>
                        <button onclick="window.startVolunteerDNAProfiler()" style="background: #5c4d3c; color: white; border: none; padding: 20px 50px; border-radius: 50px; font-size: 1.2rem; font-weight: 600; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                            <i class="bi bi-dna"></i> Discover Your Volunteer DNA
                        </button>
                    </div>
                </div>
            </div>

            <!-- Creative Games Grid -->
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.selectDifficulty()">
                        <div class="game-icon-large">🔐</div>
                        <h4>Mission Escape Room</h4>
                        <p>Solve puzzles with difficulty levels! Easy (10pts), Medium (25pts), Hard (50pts)</p>
                        <span class="game-badge">ENHANCED</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startImpactChainReaction()">
                        <div class="game-icon-large">⚡</div>
                        <h4>Impact Chain Reaction</h4>
                        <p>Create cascading impact by connecting missions strategically!</p>
                        <span class="game-badge">STRATEGY</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startVolunteerCardBattle()">
                        <div class="game-icon-large">🃏</div>
                        <h4>Volunteer Card Battle</h4>
                        <p>Collect and battle with volunteer-themed cards!</p>
                        <span class="game-badge">COLLECTIBLE</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startCommunityBuilder()">
                        <div class="game-icon-large">🏙️</div>
                        <h4>Community Builder</h4>
                        <p>Build a virtual city by completing volunteer missions!</p>
                        <span class="game-badge">SIMULATION</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startMissionTimeTraveler()">
                        <div class="game-icon-large">⏰</div>
                        <h4>Mission Time Traveler</h4>
                        <p>Travel through time to see your volunteer journey!</p>
                        <span class="game-badge">ADVENTURE</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startTriviaGame()">
                        <div class="game-icon-large">❓</div>
                        <h4>Volunteer Trivia</h4>
                        <p>Test your knowledge and earn bonus hours!</p>
                        <span class="game-badge">QUIZ</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startImpactVisualizer()">
                        <div class="game-icon-large">📊</div>
                        <h4>Impact Visualizer</h4>
                        <p>Paint your impact with interactive visualization tools!</p>
                        <span class="game-badge">CREATIVE</span>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="creative-game-card" onclick="window.startMissionRoulette()">
                        <div class="game-icon-large">🎰</div>
                        <h4>Mission Roulette</h4>
                        <p>Spin the wheel for random mission challenges!</p>
                        <span class="game-badge">LUCKY</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trivia Game Modal -->
    <div id="triviaModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); overflow: auto;">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 600px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <span class="close" onclick="window.closeTriviaModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2 style="color: #5c4d3c; margin-bottom: 20px;"><i class="bi bi-question-circle"></i> Volunteer Trivia Challenge</h2>
            <div id="trivia-game-container">
                <div id="trivia-stats" style="display: flex; justify-content: space-around; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;" id="trivia-score">0</div>
                        <div style="color: #666; font-size: 0.9rem;">Score</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;" id="trivia-question-num">1/10</div>
                        <div style="color: #666; font-size: 0.9rem;">Question</div>
                    </div>
                </div>
                <div id="trivia-question-container" style="min-height: 200px;"></div>
                <div id="trivia-results" style="display: none; text-align: center; padding: 20px;">
                    <h3 style="color: #5c4d3c; margin-bottom: 15px;">Game Complete!</h3>
                    <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c; margin: 20px 0;" id="final-score">0</div>
                    <p style="color: #666; margin-bottom: 20px;" id="trivia-message"></p>
                    <button onclick="window.startTriviaGame()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-weight: 500; margin-right: 10px;">Play Again</button>
                    <button onclick="window.closeTriviaModal()" style="background: #f8f9fa; color: #333; border: 2px solid #e0e0e0; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-weight: 500;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <section class="game-stats-section">
        <div class="container">
            <h2 class="section-title">Your Game Stats</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="bi bi-trophy stat-icon"></i>
                        <h3>Games Played</h3>
                        <div class="stat-value" id="games-played-display"><?php echo $games_played; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="bi bi-star stat-icon"></i>
                        <h3>Total Points</h3>
                        <div class="stat-value" id="total-points-display"><?php echo $user_points; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="bi bi-graph-up stat-icon"></i>
                        <h3>Win Rate</h3>
                        <div class="stat-value" id="win-rate-display"><?php echo $win_rate; ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Points Display Section -->
    <section class="points-section">
        <div class="container">
            <div class="points-card">
                <h2 style="color: white; margin-bottom: 20px;">Your Game Points</h2>
                <div class="points-value" id="user-points-display"><?php echo $game_points; ?></div>
                <p style="color: white; font-size: 1.1rem; opacity: 0.9;">Earn points by playing games and completing challenges!</p>
                <div style="margin-top: 30px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold;">10-50</div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Points per Game</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold;">2x</div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Perfect Score Bonus</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold;">1.5x</div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Speed Bonus</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Unique Informational Sections -->
    <section style="padding: 60px 0; background: white;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="color: #5c4d3c; font-size: 2.5rem; margin-bottom: 15px;">📚 Volunteer Resources</h2>
                <p style="color: #666; font-size: 1.2rem;">Learn, grow, and discover your volunteer potential</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div style="background: #f8f9fa; border-radius: 15px; padding: 30px; height: 100%; border-left: 4px solid #5c4d3c;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">💡</div>
                        <h3 style="color: #5c4d3c; margin-bottom: 15px;">Volunteer Tips & Tricks</h3>
                        <ul style="color: #666; line-height: 2;">
                            <li>Set realistic goals for your volunteer hours</li>
                            <li>Track your impact to stay motivated</li>
                            <li>Join team missions for better connections</li>
                            <li>Balance different types of missions</li>
                            <li>Celebrate small achievements along the way</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div style="background: #f8f9fa; border-radius: 15px; padding: 30px; height: 100%; border-left: 4px solid #FFD3B5;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">🌟</div>
                        <h3 style="color: #5c4d3c; margin-bottom: 15px;">Impact Stories</h3>
                        <div style="color: #666; line-height: 1.8;">
                            <p><strong>Sarah's Journey:</strong> "Started with 10 hours, now at 500+! The level system kept me motivated."</p>
                            <p><strong>Community Impact:</strong> Our volunteers have contributed over 10,000 hours this year!</p>
                            <p><strong>Success Tip:</strong> Consistency beats intensity - small regular contributions add up.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div style="background: #f8f9fa; border-radius: 15px; padding: 30px; height: 100%; border-left: 4px solid #28a745;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">🎯</div>
                        <h3 style="color: #5c4d3c; margin-bottom: 15px;">Mission Categories</h3>
                        <div style="color: #666; line-height: 2;">
                            <div><strong>🌳 Environmental:</strong> Cleanup, planting, conservation</div>
                            <div><strong>📚 Education:</strong> Tutoring, mentoring, workshops</div>
                            <div><strong>❤️ Healthcare:</strong> Support, awareness, assistance</div>
                            <div><strong>🤝 Community:</strong> Events, organizing, outreach</div>
                            <div><strong>🎨 Creative:</strong> Arts, design, content creation</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section style="padding: 60px 0; background: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 style="color: #5c4d3c; font-size: 2.5rem; margin-bottom: 15px;">🏆 Achievement Guide</h2>
                <p style="color: #666; font-size: 1.2rem;">How to maximize your volunteer experience</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="color: #5c4d3c; margin-bottom: 20px;">📊 Leveling System</h3>
                        <div style="color: #666; line-height: 1.8;">
                            <p><strong>Level 1-5:</strong> Beginner (0-100 points) - Focus on learning and exploration</p>
                            <p><strong>Level 6-10:</strong> Intermediate (101-400 points) - Build consistency and skills</p>
                            <p><strong>Level 11-15:</strong> Advanced (401-900 points) - Lead and mentor others</p>
                            <p><strong>Level 16+:</strong> Expert (900+ points) - Community leader and influencer</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="color: #5c4d3c; margin-bottom: 20px;">🎮 Game Strategy</h3>
                        <div style="color: #666; line-height: 1.8;">
                            <p><strong>Trivia Challenge:</strong> Answer correctly for 10-20 points per question. Perfect scores get 2x multiplier!</p>
                            <p><strong>Escape Room:</strong> Choose difficulty wisely - Hard mode offers 50 points per puzzle but tight time limits.</p>
                            <p><strong>Chain Reaction:</strong> Connect compatible missions for combo multipliers and bonus points.</p>
                            <p><strong>Card Battle:</strong> Strategic card selection can maximize your win rate and points earned.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="points-section" style="display: none;">
        <div class="container">
            <div class="points-card">
                <h2>Your Points Balance</h2>
                <div class="points-value"><?php echo $user_points; ?></div>
                <p>Complete games to earn more points!</p>
            </div>
        </div>
    </section>

    <section class="fun-section">
        <div class="container">
            <h2>Fun Facts</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="fun-card">
                        <i class="bi bi-trophy fun-icon"></i>
                        <h3>Daily Streak</h3>
                        <p>Play games daily to maintain your streak and earn bonus points!</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fun-card">
                        <i class="bi bi-star fun-icon"></i>
                        <h3>High Scores</h3>
                        <p>Compete with family members for the highest scores!</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fun-card">
                        <i class="bi bi-gift fun-icon"></i>
                        <h3>Rewards</h3>
                        <p>Use your points to redeem exciting rewards!</p>
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
        <li><i class="bi bi-chevron-right"></i> <a href="#">Keeping Your Family Organized</a></li>
        <li><i class="bi bi-chevron-right"></i> <a href="#">How Famify Keeps Your Family on Track</a></li>
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

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
    // Volunteer Trivia Challenge
    const triviaQuestions = [
        {
            question: "What is the primary goal of volunteer work?",
            options: ["Earn money", "Make a positive impact on the community", "Get famous", "Avoid responsibilities"],
            correct: 1,
            explanation: "Volunteer work is about making a positive impact and contributing to the community!"
        },
        {
            question: "Which of these is NOT a common volunteer activity?",
            options: ["Environmental cleanup", "Tutoring students", "Selling products for profit", "Food bank assistance"],
            correct: 2,
            explanation: "Volunteer work is unpaid and focused on community service, not profit-making."
        },
        {
            question: "How many hours of volunteer work per week is typically recommended?",
            options: ["1-2 hours", "2-5 hours", "5-10 hours", "It depends on your availability"],
            correct: 3,
            explanation: "Any amount of volunteer work is valuable! It's best to volunteer what you can reasonably commit to."
        },
        {
            question: "What is a key benefit of volunteering?",
            options: ["Only earning points", "Building skills, networking, and personal growth", "Getting free food", "Avoiding work"],
            correct: 1,
            explanation: "Volunteering helps you develop skills, meet new people, and grow personally while helping others!"
        },
        {
            question: "What should you do before starting a volunteer mission?",
            options: ["Just show up", "Understand the mission requirements and prepare accordingly", "Ask for payment", "Bring friends unannounced"],
            correct: 1,
            explanation: "It's important to understand what's expected and prepare properly for any volunteer mission."
        },
        {
            question: "Which volunteer activity helps the environment?",
            options: ["Tree planting", "All of these", "Beach cleanup", "Recycling programs"],
            correct: 1,
            explanation: "All of these activities help protect and improve our environment!"
        },
        {
            question: "What should you do if you can't complete a volunteer mission?",
            options: ["Just don't show up", "Notify the organization administrator as soon as possible", "Blame someone else", "Ignore it"],
            correct: 1,
            explanation: "Communication is key! Always notify organizers if you can't fulfill your commitment."
        },
        {
            question: "What is the best way to track your volunteer impact?",
            options: ["Guess", "Keep a journal or use a platform like VolunteerHub", "Ask friends", "Don't track it"],
            correct: 1,
            explanation: "Tracking your volunteer hours helps you see your impact and can be useful for resumes and applications!"
        },
        {
            question: "What makes a great volunteer?",
            options: ["Only showing up sometimes", "Reliability, enthusiasm, and a positive attitude", "Complaining often", "Working alone"],
            correct: 1,
            explanation: "Great volunteers are reliable, enthusiastic, and maintain a positive attitude!"
        },
        {
            question: "How can volunteering benefit your career?",
            options: ["It doesn't", "By developing skills, expanding your network, and showing commitment", "By avoiding real work", "By earning money"],
            correct: 1,
            explanation: "Volunteering helps you develop valuable skills, meet professionals, and demonstrates your commitment to employers!"
        }
    ];

    let currentQuestion = 0;
    let score = 0;
    let triviaGameActive = false;

    window.startTriviaGame = function() {
        document.getElementById('triviaModal').style.display = 'block';
        currentQuestion = 0;
        score = 0;
        triviaGameActive = true;
        document.getElementById('trivia-results').style.display = 'none';
        document.getElementById('trivia-question-container').style.display = 'block';
        showTriviaQuestion();
    };

    function showTriviaQuestion() {
        if (currentQuestion >= triviaQuestions.length) {
            endTriviaGame();
            return;
        }

        const question = triviaQuestions[currentQuestion];
        const container = document.getElementById('trivia-question-container');
        document.getElementById('trivia-question-num').textContent = `${currentQuestion + 1}/${triviaQuestions.length}`;
        document.getElementById('trivia-score').textContent = score;

        container.innerHTML = `
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h3 style="color: #5c4d3c; margin-bottom: 20px; font-size: 1.3rem;">${question.question}</h3>
                <div id="trivia-options">
                    ${question.options.map((option, index) => `
                        <button onclick="selectTriviaAnswer(${index})" 
                                style="display: block; width: 100%; padding: 15px; margin: 10px 0; background: white; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; text-align: left; transition: all 0.3s ease; font-size: 1rem;"
                                class="trivia-option">
                            ${option}
                        </button>
                    `).join('')}
                </div>
            </div>
            <div id="trivia-explanation" style="display: none; background: #FFD3B5; padding: 15px; border-radius: 8px; margin-top: 15px; color: #333;"></div>
        `;
    }

    function selectTriviaAnswer(selectedIndex) {
        if (!triviaGameActive) return;
        
        const question = triviaQuestions[currentQuestion];
        const options = document.querySelectorAll('.trivia-option');
        const explanationDiv = document.getElementById('trivia-explanation');
        
        // Disable all buttons
        options.forEach(btn => btn.disabled = true);
        triviaGameActive = false;

        // Show correct/incorrect
        if (selectedIndex === question.correct) {
            score++;
            options[selectedIndex].style.background = '#28a745';
            options[selectedIndex].style.color = 'white';
            options[selectedIndex].style.borderColor = '#28a745';
        } else {
            options[selectedIndex].style.background = '#dc3545';
            options[selectedIndex].style.color = 'white';
            options[selectedIndex].style.borderColor = '#dc3545';
            const correctOption = options[question.correct];
            correctOption.style.background = '#28a745';
            correctOption.style.color = 'white';
            correctOption.style.borderColor = '#28a745';
        }

        // Show explanation
        explanationDiv.innerHTML = `<strong>${selectedIndex === question.correct ? '✓ Correct! ' : '✗ Incorrect. '}</strong>${question.explanation}`;
        explanationDiv.style.display = 'block';

        // Move to next question after 2 seconds
        setTimeout(() => {
            currentQuestion++;
            if (currentQuestion < triviaQuestions.length) {
                triviaGameActive = true;
                showTriviaQuestion();
            } else {
                endTriviaGame();
            }
        }, 2000);
    }

    function endTriviaGame() {
        document.getElementById('trivia-question-container').style.display = 'none';
        document.getElementById('trivia-results').style.display = 'block';
        
        const percentage = Math.round((score / triviaQuestions.length) * 100);
        // Award points based on performance with multipliers
        let pointsEarned = score * 10; // Base: 10 points per correct answer
        if (percentage === 100) {
            pointsEarned *= 2; // Perfect score bonus: 2x multiplier
        } else if (percentage >= 80) {
            pointsEarned = Math.floor(pointsEarned * 1.5); // Excellent: 1.5x multiplier
        } else if (percentage >= 60) {
            pointsEarned = Math.floor(pointsEarned * 1.2); // Good: 1.2x multiplier
        }
        
        document.getElementById('final-score').textContent = `${score}/${triviaQuestions.length} (${percentage}%)`;
        
        let message = '';
        if (percentage === 100) {
            message = `Perfect score! 🌟 You earned ${pointsEarned} points! (2x bonus multiplier applied)`;
        } else if (percentage >= 80) {
            message = `Excellent work! 🎉 You earned ${pointsEarned} points! (1.5x bonus multiplier applied)`;
        } else if (percentage >= 60) {
            message = `Good job! 👍 You earned ${pointsEarned} points! (1.2x bonus multiplier applied)`;
        } else {
            message = `Keep learning! 📚 You earned ${pointsEarned} points!`;
        }
        
        document.getElementById('trivia-message').textContent = message;
        
        // Award points via AJAX
        if (pointsEarned > 0) {
            const gameWon = percentage >= 60; // Consider 60%+ as a win
            fetch('add_points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    points: pointsEarned,
                    game_won: gameWon,
                    increment_games: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateGameStats(data);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }
    
    function updatePointsDisplay() {
        // Points are updated via add_points.php which returns updated stats
        // This function will be called after points are added
    }
    
    function updateGameStats(stats) {
        if (stats) {
            if (document.getElementById('total-points-display')) {
                document.getElementById('total-points-display').textContent = stats.total_points || 0;
            }
            if (document.getElementById('games-played-display')) {
                document.getElementById('games-played-display').textContent = stats.games_played || 0;
            }
            if (document.getElementById('win-rate-display')) {
                document.getElementById('win-rate-display').textContent = (stats.win_rate || 0) + '%';
            }
            if (document.getElementById('user-points-display')) {
                document.getElementById('user-points-display').textContent = stats.game_points || 0;
            }
        }
    }

    window.closeTriviaModal = function() {
        document.getElementById('triviaModal').style.display = 'none';
        triviaGameActive = false;
    };

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('triviaModal');
        if (event.target === modal) {
            window.closeTriviaModal();
        }
    };

    // Volunteer DNA Profiler Game
    const dnaQuestions = [
        {
            question: "What motivates you most to volunteer?",
            options: ["Making a visible impact", "Learning new skills", "Meeting new people", "Following my passion"],
            traits: { impact: 2, learning: 1, social: 1, passion: 0 }
        },
        {
            question: "How do you prefer to volunteer?",
            options: ["Solo missions", "Small teams (2-5 people)", "Large groups", "Flexible - either way"],
            traits: { solo: 2, small: 1, large: 0, flexible: 1 }
        },
        {
            question: "What type of mission appeals to you most?",
            options: ["Environmental/Community cleanup", "Education/Tutoring", "Healthcare/Support", "Creative/Arts"],
            traits: { environmental: 2, education: 1, healthcare: 1, creative: 0 }
        },
        {
            question: "How do you handle challenges during missions?",
            options: ["Problem-solve independently", "Collaborate with others", "Ask for guidance", "Adapt and improvise"],
            traits: { independent: 2, collaborative: 1, guidance: 0, adaptive: 1 }
        },
        {
            question: "What's your ideal mission duration?",
            options: ["Quick tasks (1-2 hours)", "Half-day (3-5 hours)", "Full day (6+ hours)", "Multi-day projects"],
            traits: { quick: 2, halfday: 1, fullday: 0, multiday: 0 }
        },
        {
            question: "How do you measure success in volunteering?",
            options: ["Number of people helped", "Skills I've learned", "Community connections made", "Personal fulfillment"],
            traits: { people: 2, skills: 1, connections: 1, fulfillment: 0 }
        },
        {
            question: "What's your communication style?",
            options: ["Direct and action-oriented", "Supportive and encouraging", "Analytical and detailed", "Creative and inspiring"],
            traits: { direct: 2, supportive: 1, analytical: 1, creative: 0 }
        },
        {
            question: "How do you prefer to track your impact?",
            options: ["Quantitative metrics (hours, people)", "Qualitative stories", "Visual progress", "Personal reflection"],
            traits: { quantitative: 2, qualitative: 1, visual: 1, reflection: 0 }
        }
    ];

    const volunteerTypes = {
        'Impact Warrior': {
            description: 'You\'re driven by making tangible, visible changes in your community. You thrive on seeing immediate results and love missions that show clear impact.',
            strengths: ['Results-driven', 'Goal-oriented', 'High impact focus'],
            bestMissions: ['Community cleanup', 'Food bank assistance', 'Direct service'],
            icon: '⚔️',
            color: '#dc3545'
        },
        'Social Connector': {
            description: 'You excel at building relationships and bringing people together. Your strength is in collaboration and creating community bonds.',
            strengths: ['Team player', 'Great communicator', 'Community builder'],
            bestMissions: ['Event organizing', 'Team missions', 'Community outreach'],
            icon: '🤝',
            color: '#28a745'
        },
        'Skill Seeker': {
            description: 'You volunteer to learn and grow. Every mission is an opportunity to develop new skills and expand your knowledge.',
            strengths: ['Quick learner', 'Adaptable', 'Growth-oriented'],
            bestMissions: ['Skill-based missions', 'Training programs', 'Diverse experiences'],
            icon: '📚',
            color: '#007bff'
        },
        'Passion Pioneer': {
            description: 'You follow your heart and volunteer in areas that align with your personal values and passions.',
            strengths: ['Passionate', 'Dedicated', 'Values-driven'],
            bestMissions: ['Cause-specific missions', 'Long-term projects', 'Advocacy work'],
            icon: '❤️',
            color: '#ff6b6b'
        },
        'Balanced Volunteer': {
            description: 'You have a well-rounded approach to volunteering, balancing impact, learning, and connection.',
            strengths: ['Versatile', 'Balanced', 'Reliable'],
            bestMissions: ['Variety of missions', 'Flexible opportunities', 'Mixed experiences'],
            icon: '⚖️',
            color: '#6c757d'
        }
    };

    let dnaCurrentQuestion = 0;
    let dnaScores = { impact: 0, learning: 0, social: 0, passion: 0, solo: 0, small: 0, large: 0, flexible: 0, environmental: 0, education: 0, healthcare: 0, creative: 0, independent: 0, collaborative: 0, guidance: 0, adaptive: 0, quick: 0, halfday: 0, fullday: 0, multiday: 0, people: 0, skills: 0, connections: 0, fulfillment: 0, direct: 0, supportive: 0, analytical: 0, creative: 0, quantitative: 0, qualitative: 0, visual: 0, reflection: 0 };

    window.startVolunteerDNAProfiler = function() {
        document.getElementById('dnaModal').style.display = 'block';
        dnaCurrentQuestion = 0;
        dnaScores = Object.keys(dnaScores).reduce((acc, key) => { acc[key] = 0; return acc; }, {});
        showDNAQuestion();
    };

    function showDNAQuestion() {
        if (dnaCurrentQuestion >= dnaQuestions.length) {
            calculateDNAProfile();
            return;
        }

        const question = dnaQuestions[dnaCurrentQuestion];
        const container = document.getElementById('dna-question-container');
        
        document.getElementById('dna-question-num').textContent = `${dnaCurrentQuestion + 1}/${dnaQuestions.length}`;

        container.innerHTML = `
            <div style="background: white; border: 2px solid #FFD3B5; padding: 30px; border-radius: 15px; margin-bottom: 20px;">
                <h3 style="color: #5c4d3c; margin-bottom: 25px; font-size: 1.4rem; font-weight: 600;">${question.question}</h3>
                <div id="dna-options">
                    ${question.options.map((option, index) => `
                        <button onclick="selectDNAAnswer(${index})" 
                                style="display: block; width: 100%; padding: 15px; margin: 10px 0; background: white; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; text-align: left; transition: all 0.3s ease; font-size: 1rem; color: #333;"
                                class="dna-option">
                            ${option}
                        </button>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function selectDNAAnswer(selectedIndex) {
        const question = dnaQuestions[dnaCurrentQuestion];
        const options = document.querySelectorAll('.dna-option');
        
        // Disable all buttons
        options.forEach(btn => btn.disabled = true);

        // Update scores based on selected answer
        Object.keys(question.traits).forEach(trait => {
            if (selectedIndex === Object.keys(question.traits).indexOf(trait)) {
                dnaScores[trait] += question.traits[trait];
            }
        });

        // Highlight selected
        options[selectedIndex].style.background = 'rgba(255,255,255,0.4)';
        options[selectedIndex].style.borderColor = 'white';

        // Move to next question
        setTimeout(() => {
            dnaCurrentQuestion++;
            showDNAQuestion();
        }, 500);
    }

    function calculateDNAProfile() {
        // Calculate profile type based on scores
        const impactScore = dnaScores.impact + dnaScores.people + dnaScores.quantitative;
        const socialScore = dnaScores.social + dnaScores.small + dnaScores.large + dnaScores.collaborative + dnaScores.connections;
        const learningScore = dnaScores.learning + dnaScores.skills + dnaScores.analytical;
        const passionScore = dnaScores.passion + dnaScores.fulfillment + dnaScores.creative + dnaScores.reflection;

        let profileType = 'Balanced Volunteer';
        let maxScore = Math.max(impactScore, socialScore, learningScore, passionScore);

        if (impactScore === maxScore && impactScore > 0) {
            profileType = 'Impact Warrior';
        } else if (socialScore === maxScore && socialScore > 0) {
            profileType = 'Social Connector';
        } else if (learningScore === maxScore && learningScore > 0) {
            profileType = 'Skill Seeker';
        } else if (passionScore === maxScore && passionScore > 0) {
            profileType = 'Passion Pioneer';
        }

        const profile = volunteerTypes[profileType];
        const container = document.getElementById('dna-question-container');
        
        container.innerHTML = `
            <div style="text-align: center; padding: 30px;">
                <div style="font-size: 5rem; margin-bottom: 20px;">${profile.icon}</div>
                <h2 style="color: ${profile.color}; margin-bottom: 20px; font-size: 2rem;">${profileType}</h2>
                <p style="color: #666; margin-bottom: 30px; font-size: 1.1rem; line-height: 1.8;">${profile.description}</p>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h4 style="color: #333; margin-bottom: 15px;">Your Strengths:</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                        ${profile.strengths.map(strength => `
                            <span style="background: ${profile.color}; color: white; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem;">${strength}</span>
                        `).join('')}
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h4 style="color: #333; margin-bottom: 15px;">Best Mission Types for You:</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                        ${profile.bestMissions.map(mission => `
                            <span style="background: white; color: ${profile.color}; border: 2px solid ${profile.color}; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem;">${mission}</span>
                        `).join('')}
                    </div>
                </div>

                <button onclick="window.startVolunteerDNAProfiler()" style="background: ${profile.color}; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-weight: 500; margin-right: 10px;">Retake Test</button>
                <button onclick="window.closeDNAModal()" style="background: #f8f9fa; color: #333; border: 2px solid #e0e0e0; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-weight: 500;">Close</button>
            </div>
        `;
    }

    window.closeDNAModal = function() {
        document.getElementById('dnaModal').style.display = 'none';
    };

    // Mission Escape Room Game - ENHANCED WITH DIFFICULTY LEVELS
    let escapeRoomState = { currentPuzzle: 0, totalReward: 0, attempts: 0, difficulty: 'medium', level: 1 };
    const escapePuzzles = {
        easy: [
            { clue: "I help the environment. What am I? (Hint: I'm green and grow)", answer: "tree", reward: 10, hints: ["It's a plant", "It produces oxygen", "It has leaves"], timeLimit: 60 },
            { clue: "I'm a place where volunteers gather. What am I? (Hint: Starts with C)", answer: "community", reward: 10, hints: ["People come together here", "It's a group", "Starts with 'com'"], timeLimit: 60 },
            { clue: "I measure your volunteer impact. What am I? (Hint: I count time)", answer: "hours", reward: 10, hints: ["It's a unit of time", "60 minutes", "You earn these"], timeLimit: 60 }
        ],
        medium: [
            { clue: "I'm what you earn by helping others. What am I? (Hint: Starts with P)", answer: "points", reward: 25, hints: ["You get these for missions", "They add up", "Starts with 'p'"], timeLimit: 45 },
            { clue: "I'm a task you complete for the community. What am I? (Hint: Starts with M)", answer: "mission", reward: 25, hints: ["It's a volunteer task", "You get assigned these", "Starts with 'm'"], timeLimit: 45 },
            { clue: "I represent your volunteer achievements. What am I? (Hint: Starts with A)", answer: "achievement", reward: 25, hints: ["You unlock these", "They show progress", "Starts with 'ach'"], timeLimit: 45 },
            { clue: "I track your volunteer progress. What am I? (Hint: Starts with L)", answer: "leaderboard", reward: 25, hints: ["Shows rankings", "Competitive element", "Starts with 'lead'"], timeLimit: 45 }
        ],
        hard: [
            { clue: "Solve: If you volunteer 3 hours per week for 4 weeks, how many total hours? (Answer as number)", answer: "12", reward: 50, hints: ["3 × 4 = ?", "Multiplication", "Double digits"], timeLimit: 30 },
            { clue: "What volunteer organization helps globally? (Hint: Starts with R, 3 words)", answer: "red cross", reward: 50, hints: ["Medical aid", "International", "Red symbol"], timeLimit: 30 },
            { clue: "Anagram: 'VOLUNTEER' - Rearrange to form a word meaning 'to help' (6 letters)", answer: "helper", reward: 50, hints: ["Starts with H", "Ends with R", "Synonym for assistant"], timeLimit: 30 },
            { clue: "Code: A=1, B=2... What word does 20-15-12-21-14-20-5-5-18 spell?", answer: "volunteer", reward: 50, hints: ["V=22, O=15", "Add 2 to each", "It's what you are"], timeLimit: 30 },
            { clue: "Riddle: I'm earned through service, tracked by missions, and shown on profiles. What am I?", answer: "points", reward: 50, hints: ["You accumulate these", "They represent value", "Starts with P"], timeLimit: 30 }
        ]
    };
    
    window.selectDifficulty = function() {
        const modal = document.createElement('div');
        modal.id = 'difficulty-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 500px; width: 90%; text-align: center;">
                <h2 style="color: #5c4d3c; margin-bottom: 30px;">Select Difficulty</h2>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <button onclick="window.startEscapeRoomWithDifficulty('easy')" style="background: #28a745; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 600; cursor: pointer;">Easy (10 points/puzzle, 60s limit)</button>
                    <button onclick="window.startEscapeRoomWithDifficulty('medium')" style="background: #ffc107; color: #333; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 600; cursor: pointer;">Medium (25 points/puzzle, 45s limit)</button>
                    <button onclick="window.startEscapeRoomWithDifficulty('hard')" style="background: #dc3545; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 600; cursor: pointer;">Hard (50 points/puzzle, 30s limit)</button>
                </div>
                <button onclick="document.getElementById('difficulty-modal').remove()" style="background: #f8f9fa; color: #333; border: 2px solid #e0e0e0; padding: 10px 20px; border-radius: 8px; margin-top: 20px; cursor: pointer;">Cancel</button>
            </div>
        `;
        document.body.appendChild(modal);
    };
    
    window.startEscapeRoomWithDifficulty = function(difficulty) {
        document.getElementById('difficulty-modal').remove();
        escapeRoomState = { currentPuzzle: 0, totalReward: 0, attempts: 0, difficulty: difficulty, level: 1, timeRemaining: 0 };
        window.startMissionEscapeRoom();
    };
    
    window.startMissionEscapeRoom = function() {
        if (!escapeRoomState.difficulty) {
            window.selectDifficulty();
            return;
        }
        const modal = document.createElement('div');
        modal.id = 'escape-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 600px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5; display: flex; flex-direction: column; max-height: 90vh;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; flex-shrink: 0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-lock"></i> Mission Escape Room</h2>
                    <span onclick="document.getElementById('escape-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; line-height: 1;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${escapeRoomState.totalReward}</div>
                            <div style="color: #666; font-size: 0.9rem;">Points</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${escapeRoomState.currentPuzzle + 1}/${escapePuzzles[escapeRoomState.difficulty].length}</div>
                            <div style="color: #666; font-size: 0.9rem;">Puzzle</div>
                        </div>
                    </div>
                </div>
                <div id="escape-puzzle-container" style="flex: 1; overflow-y: auto;"></div>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0; flex-shrink: 0;">
                    <button onclick="document.getElementById('escape-modal').remove()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        window.showEscapePuzzle();
    };
    
    window.showEscapePuzzle = function() {
        const container = document.getElementById('escape-puzzle-container');
        if (!container) return;
        
        const currentPuzzles = escapePuzzles[escapeRoomState.difficulty];
        if (escapeRoomState.currentPuzzle >= currentPuzzles.length) {
            container.innerHTML = `
                <div style="text-align: center; padding: 30px;">
                    <div style="width: 80px; height: 80px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2.5rem;">✓</div>
                    <h3 style="color: #5c4d3c; margin-bottom: 15px; font-size: 1.8rem; font-weight: 700;">Escape Room Complete!</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; display: inline-block;">
                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">Total Points Earned</div>
                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">${escapeRoomState.totalReward} points</div>
                    </div>
                    <p style="color: #666; margin-top: 20px;">Total Attempts: ${escapeRoomState.attempts}</p>
                    <div style="margin-top: 30px;">
                        <button onclick="window.awardEscapeRoomPoints()" style="background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: all 0.3s;" onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">Claim ${escapeRoomState.totalReward} Points</button>
                        <button onclick="window.selectDifficulty()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Play Again</button>
                        <button onclick="document.getElementById('escape-modal').remove()" style="background: #f8f9fa; color: #333; border: 2px solid #e0e0e0; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#5c4d3c'" onmouseout="this.style.borderColor='#e0e0e0'">Close</button>
                    </div>
                </div>
            `;
            return;
        }
        const puzzle = currentPuzzles[escapeRoomState.currentPuzzle];
        const hintIndex = Math.min(escapeRoomState.attempts, puzzle.hints.length - 1);
        escapeRoomState.timeRemaining = puzzle.timeLimit;
        
        // Start timer
        const timerInterval = setInterval(() => {
            escapeRoomState.timeRemaining--;
            const timerEl = document.getElementById('escape-timer');
            if (timerEl) {
                timerEl.textContent = escapeRoomState.timeRemaining;
                if (escapeRoomState.timeRemaining <= 10) {
                    timerEl.style.color = '#dc3545';
                }
            }
            if (escapeRoomState.timeRemaining <= 0) {
                clearInterval(timerInterval);
                window.checkEscapeAnswer('', 0, true); // Time's up
            }
        }, 1000);
        
        container.innerHTML = `
            <div style="background: #f8f9fa; border-left: 4px solid #5c4d3c; padding: 25px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <h3 style="color: #5c4d3c; margin: 0; font-size: 1.3rem; font-weight: 600;">Puzzle ${escapeRoomState.currentPuzzle + 1} of ${currentPuzzles.length} (${escapeRoomState.difficulty.toUpperCase()})</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="background: #FFD3B5; color: #5c4d3c; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">${puzzle.reward} points</span>
                        <span id="escape-timer" style="background: #5c4d3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">${escapeRoomState.timeRemaining}s</span>
                    </div>
                </div>
                <p style="color: #333; font-size: 1.1rem; margin-bottom: 20px; line-height: 1.6;">${puzzle.clue}</p>
                ${escapeRoomState.attempts > 0 ? `<div style="background: #FFD3B5; padding: 12px; border-radius: 8px; margin-top: 15px;"><p style="color: #5c4d3c; font-size: 0.9rem; margin: 0;"><strong>Hint:</strong> ${puzzle.hints[hintIndex]}</p></div>` : ''}
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" id="escape-answer" placeholder="Type your answer here..." 
                       style="width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: all 0.3s;"
                           onkeypress="if(event.key==='Enter') window.checkEscapeAnswer('${puzzle.answer}', ${puzzle.reward})"
                       onfocus="this.style.borderColor='#5c4d3c'; this.style.boxShadow='0 0 0 3px rgba(92, 77, 60, 0.1)'"
                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
            </div>
            <div style="display: flex; gap: 10px;">
                    <button onclick="window.checkEscapeAnswer('${puzzle.answer}', ${puzzle.reward})"
                        style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; flex: 1; transition: all 0.3s;"
                        onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Submit Answer</button>
                    ${escapeRoomState.attempts < puzzle.hints.length ? `<button onclick="window.showEscapeHint()" style="background: #f8f9fa; color: #5c4d3c; border: 2px solid #FFD3B5; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#5c4d3c'" onmouseout="this.style.borderColor='#FFD3B5'">Get Hint</button>` : ''}
            </div>
            <div id="escape-feedback" style="margin-top: 15px;"></div>
        `;
    }
    
    window.checkEscapeAnswer = function(answer, reward, timeUp = false) {
        const userAnswer = document.getElementById('escape-answer')?.value.toLowerCase().trim() || '';
        const feedback = document.getElementById('escape-feedback');
        if (!timeUp) escapeRoomState.attempts++;
        
        if (timeUp) {
            feedback.innerHTML = `<div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600;"><i class="bi bi-clock"></i> Time's up! Moving to next puzzle...</div>`;
            setTimeout(() => {
                escapeRoomState.currentPuzzle++;
                escapeRoomState.attempts = 0;
                feedback.innerHTML = '';
                showEscapePuzzle();
            }, 2000);
        } else if (userAnswer === answer) {
            // Bonus for speed: if time remaining > 50%, get 1.5x points
            const speedBonus = escapeRoomState.timeRemaining > (escapePuzzles[escapeRoomState.difficulty][escapeRoomState.currentPuzzle].timeLimit * 0.5) ? 1.5 : 1;
            const finalReward = Math.floor(reward * speedBonus);
            escapeRoomState.totalReward += finalReward;
            escapeRoomState.currentPuzzle++;
            escapeRoomState.attempts = 0;
            const bonusText = speedBonus > 1 ? ` (Speed bonus: ${speedBonus}x)` : '';
            feedback.innerHTML = `<div style="background: #28a745; color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600;"><i class="bi bi-check-circle"></i> Correct! You earned ${finalReward} points!${bonusText}</div>`;
            setTimeout(() => {
                feedback.innerHTML = '';
                showEscapePuzzle();
            }, 2000);
        } else {
            feedback.innerHTML = `<div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600;"><i class="bi bi-x-circle"></i> Incorrect. Try again!</div>`;
            setTimeout(() => {
                feedback.innerHTML = '';
            }, 2000);
        }
    };
    
    window.showEscapeHint = function() {
        escapeRoomState.attempts++;
        showEscapePuzzle();
    };
    
    window.awardEscapeRoomPoints = function() {
        if (escapeRoomState.totalReward > 0) {
            const currentPuzzles = escapePuzzles[escapeRoomState.difficulty];
            const completedAll = escapeRoomState.currentPuzzle >= currentPuzzles.length;
            fetch('add_points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    points: escapeRoomState.totalReward,
                    game_won: completedAll,
                    increment_games: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully earned ${escapeRoomState.totalReward} points!`);
                    updateGameStats(data);
                    document.getElementById('escape-modal').remove();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    };

    // Impact Chain Reaction Game - FULLY PLAYABLE
    let chainGameState = { selected: [], score: 0, chains: 0 };
    const chainMissions = [
        { icon: '🌳', name: 'Environmental', type: 'env', connects: ['env', 'community'] },
        { icon: '📚', name: 'Education', type: 'edu', connects: ['edu', 'community'] },
        { icon: '❤️', name: 'Healthcare', type: 'health', connects: ['health', 'community'] },
        { icon: '🏥', name: 'Medical', type: 'health', connects: ['health', 'env'] },
        { icon: '🌍', name: 'Community', type: 'community', connects: ['env', 'edu', 'health'] },
        { icon: '🤝', name: 'Social', type: 'community', connects: ['community', 'edu'] }
    ];
    
    window.startImpactChainReaction = function() {
        chainGameState = { selected: [], score: 0, chains: 0 };
        const modal = document.createElement('div');
        modal.id = 'chain-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 700px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5; max-height: 90vh; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-lightning-charge"></i> Impact Chain Reaction</h2>
                    <span onclick="document.getElementById('chain-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${chainGameState.score}</div>
                            <div style="color: #666; font-size: 0.9rem;">Score</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${chainGameState.chains}</div>
                            <div style="color: #666; font-size: 0.9rem;">Chains</div>
                        </div>
                    </div>
                </div>
                <p style="color: #666; margin-bottom: 25px; text-align: center; font-size: 1rem; flex-shrink: 0;">Select compatible missions to create chain reactions and maximize your impact score!</p>
                <div id="chain-game" style="background: #f8f9fa; padding: 30px; border-radius: 15px; flex: 1; overflow-y: auto;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        ${chainMissions.map((mission, i) => `
                            <div onclick="window.selectChainMission(${i})" 
                                 id="chain-mission-${i}"
                                 style="background: white; border: 2px solid #e0e0e0; padding: 25px; border-radius: 10px; cursor: pointer; font-size: 2.5rem; text-align: center; transition: all 0.3s; min-height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center;" 
                                 class="chain-mission"
                                 onmouseover="if(!this.style.background.includes('#FFD3B5')) this.style.borderColor='#5c4d3c'"
                                 onmouseout="if(!this.style.background.includes('#FFD3B5')) this.style.borderColor='#e0e0e0'">
                                <div style="margin-bottom: 10px;">${mission.icon}</div>
                                <div style="font-size: 0.85rem; color: #666; font-weight: 500;">${mission.name}</div>
                            </div>
                        `).join('')}
                    </div>
                    <div id="chain-result" style="margin-top: 25px; text-align: center; min-height: 60px; padding: 15px;"></div>
                </div>
                <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #f0f0f0; flex-shrink: 0;">
                    <button onclick="window.checkChainReaction()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Check Chain</button>
                    <button onclick="window.resetChainGame()" style="background: #f8f9fa; color: #333; border: 2px solid #e0e0e0; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: all 0.3s;" onmouseover="this.style.borderColor='#5c4d3c'" onmouseout="this.style.borderColor='#e0e0e0'">Reset Selection</button>
                    ${chainGameState.score > 0 ? `<button onclick="window.claimChainPoints()" style="background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: all 0.3s;" onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">Claim ${chainGameState.score} Points</button>` : ''}
                    <button onclick="document.getElementById('chain-modal').remove()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    window.selectChainMission = function(index) {
        const missionEl = document.getElementById(`chain-mission-${index}`);
        if (chainGameState.selected.includes(index)) {
            chainGameState.selected = chainGameState.selected.filter(i => i !== index);
            missionEl.style.background = 'white';
            missionEl.style.borderColor = '#e0e0e0';
        } else {
            if (chainGameState.selected.length < 6) {
                chainGameState.selected.push(index);
                missionEl.style.background = '#FFD3B5';
                missionEl.style.borderColor = '#5c4d3c';
            }
        }
    };
    
    window.checkChainReaction = function() {
        if (chainGameState.selected.length < 2) {
            document.getElementById('chain-result').innerHTML = '<div style="color: #dc3545; padding: 10px;">Select at least 2 missions to create a chain!</div>';
            return;
        }
        
        let validChains = 0;
        const selectedMissions = chainGameState.selected.map(i => chainMissions[i]);
        
        for (let i = 0; i < selectedMissions.length - 1; i++) {
            const current = selectedMissions[i];
            const next = selectedMissions[i + 1];
            if (current.connects.includes(next.type) || next.connects.includes(current.type)) {
                validChains++;
            }
        }
        
        if (validChains > 0) {
            const points = validChains * 10 * chainGameState.selected.length;
            chainGameState.score += points;
            chainGameState.chains += validChains;
            document.getElementById('chain-result').innerHTML = `
                <div style="background: #28a745; color: white; padding: 20px; border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-check-circle"></i> Chain Reaction Created!<br>
                    <div style="font-size: 1.5rem; margin-top: 10px;">+${points} points</div>
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 5px;">${validChains} valid connection${validChains > 1 ? 's' : ''}</div>
                </div>
            `;
            chainGameState.selected = [];
            setTimeout(() => {
                chainMissions.forEach((_, i) => {
                    const el = document.getElementById(`chain-mission-${i}`);
                    if (el) {
                        el.style.background = 'white';
                        el.style.borderColor = '#e0e0e0';
                    }
                });
                document.getElementById('chain-result').innerHTML = '';
                updateChainStats();
            }, 2500);
        } else {
            document.getElementById('chain-result').innerHTML = '<div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; font-weight: 600;"><i class="bi bi-x-circle"></i> No valid chain! Select compatible missions.</div>';
        }
    };
    
    window.resetChainGame = function() {
        chainGameState.selected = [];
        chainMissions.forEach((_, i) => {
            const el = document.getElementById(`chain-mission-${i}`);
            if (el) {
                el.style.background = 'white';
                el.style.borderColor = '#e0e0e0';
            }
        });
        document.getElementById('chain-result').innerHTML = '';
    };
    
    window.claimChainPoints = function() {
        if (chainGameState.score > 0) {
            fetch('add_points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    points: chainGameState.score,
                    game_won: chainGameState.chains > 0,
                    increment_games: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully earned ${chainGameState.score} points!`);
                    updateGameStats(data);
                    chainGameState.score = 0;
                    chainGameState.chains = 0;
                    document.getElementById('chain-modal').remove();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    };
    
    function updateChainStats() {
        const modal = document.getElementById('chain-modal');
        if (modal) {
            const stats = modal.querySelectorAll('.chain-mission').length > 0 ? modal.querySelector('.chain-mission').parentElement.previousElementSibling : null;
            if (stats) {
                stats.innerHTML = `
                    <div style="display: flex; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${chainGameState.score}</div>
                            <div style="color: #666; font-size: 0.9rem;">Score</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${chainGameState.chains}</div>
                            <div style="color: #666; font-size: 0.9rem;">Chains</div>
                        </div>
                    </div>
                `;
            }
        }
    }

    // Volunteer Card Battle - FULLY PLAYABLE
    let cardBattleState = { playerDeck: [], opponentDeck: [], playerCard: null, opponentCard: null, wins: 0, losses: 0, round: 1 };
    const allCards = [
        { name: 'Environmental Warrior', power: 85, icon: '🌳', type: 'env' },
        { name: 'Education Hero', power: 80, icon: '📚', type: 'edu' },
        { name: 'Healthcare Champion', power: 90, icon: '❤️', type: 'health' },
        { name: 'Community Builder', power: 75, icon: '🤝', type: 'community' },
        { name: 'Social Connector', power: 82, icon: '💬', type: 'community' },
        { name: 'Skill Seeker', power: 78, icon: '📖', type: 'edu' },
        { name: 'Impact Warrior', power: 88, icon: '⚔️', type: 'env' },
        { name: 'Passion Pioneer', power: 83, icon: '🔥', type: 'community' }
    ];
    
    window.startVolunteerCardBattle = function() {
        // Shuffle and deal cards
        const shuffled = [...allCards].sort(() => Math.random() - 0.5);
        cardBattleState.playerDeck = shuffled.slice(0, 4);
        cardBattleState.opponentDeck = shuffled.slice(4, 8);
        cardBattleState.playerCard = null;
        cardBattleState.opponentCard = null;
        cardBattleState.wins = 0;
        cardBattleState.losses = 0;
        cardBattleState.round = 1;
        
        const modal = document.createElement('div');
        modal.id = 'card-battle-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 800px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-suit-spade"></i> Volunteer Card Battle</h2>
                    <span onclick="document.getElementById('card-battle-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">Round ${cardBattleState.round}</div>
                            <div style="color: #666; font-size: 0.9rem;">Battle</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">${cardBattleState.wins}</div>
                            <div style="color: #666; font-size: 0.9rem;">Wins</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #dc3545;">${cardBattleState.losses}</div>
                            <div style="color: #666; font-size: 0.9rem;">Losses</div>
                        </div>
                    </div>
                </div>
                <div id="card-battle-container"></div>
            </div>
        `;
        document.body.appendChild(modal);
        renderCardBattle();
    }
    
    function renderCardBattle() {
        const container = document.getElementById('card-battle-container');
        if (!container) return;
        
            if (cardBattleState.playerDeck.length === 0 || cardBattleState.opponentDeck.length === 0) {
            const winner = cardBattleState.wins > cardBattleState.losses ? 'Victory!' : cardBattleState.wins < cardBattleState.losses ? 'Defeat' : 'Draw';
            const winnerColor = cardBattleState.wins > cardBattleState.losses ? '#28a745' : cardBattleState.wins < cardBattleState.losses ? '#dc3545' : '#5c4d3c';
            const gameWon = cardBattleState.wins > cardBattleState.losses;
            const pointsEarned = cardBattleState.wins * 5; // 5 points per win
            
            // Award points for card battle
            if (pointsEarned > 0) {
                fetch('add_points.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        points: pointsEarned,
                        game_won: gameWon,
                        increment_games: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateGameStats(data);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
            
            container.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="width: 100px; height: 100px; background: ${winnerColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; font-size: 3rem; color: white;">${cardBattleState.wins > cardBattleState.losses ? '🏆' : cardBattleState.wins < cardBattleState.losses ? '😔' : '🤝'}</div>
                    <h3 style="color: #5c4d3c; margin-bottom: 20px; font-size: 2rem; font-weight: 700;">Game Over</h3>
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 25px 0; display: inline-block;">
                        <div style="font-size: 2rem; font-weight: bold; color: ${winnerColor}; margin-bottom: 10px;">${winner}</div>
                        <div style="color: #666; font-size: 1rem;">Final Score</div>
                        <div style="display: flex; gap: 30px; margin-top: 15px; justify-content: center;">
                            <div>
                                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">${cardBattleState.wins}</div>
                                <div style="color: #666; font-size: 0.9rem;">Wins</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5rem; font-weight: bold; color: #dc3545;">${cardBattleState.losses}</div>
                                <div style="color: #666; font-size: 0.9rem;">Losses</div>
                            </div>
                        </div>
                        ${pointsEarned > 0 ? `<div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #e0e0e0;">
                            <div style="color: #5c4d3c; font-weight: 600;">Points Earned: ${pointsEarned}</div>
                        </div>` : ''}
                    </div>
                    <button onclick="window.startVolunteerCardBattle()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 20px; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Play Again</button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div style="margin-bottom: 30px;">
                <h4 style="color: #5c4d3c; margin-bottom: 20px; font-size: 1.2rem; font-weight: 600;">Your Cards (${cardBattleState.playerDeck.length} remaining)</h4>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px;">
                    ${cardBattleState.playerDeck.map((card, i) => `
                        <div onclick="window.selectPlayerCard(${i})" 
                             style="background: ${cardBattleState.playerCard === i ? '#FFD3B5' : 'white'}; border: 2px solid ${cardBattleState.playerCard === i ? '#5c4d3c' : '#e0e0e0'}; padding: 20px; border-radius: 12px; cursor: pointer; text-align: center; min-width: 140px; transition: all 0.3s; box-shadow: ${cardBattleState.playerCard === i ? '0 5px 15px rgba(0,0,0,0.1)' : 'none'};"
                             onmouseover="if(${cardBattleState.playerCard !== i}) this.style.borderColor='#5c4d3c'"
                             onmouseout="if(${cardBattleState.playerCard !== i}) this.style.borderColor='#e0e0e0'">
                            <div style="font-size: 2.5rem; margin-bottom: 10px;">${card.icon}</div>
                            <div style="font-size: 0.9rem; color: #333; margin-bottom: 8px; font-weight: 600;">${card.name}</div>
                            <div style="font-size: 0.85rem; color: #5c4d3c; background: #f8f9fa; padding: 5px 10px; border-radius: 15px; display: inline-block;">Power: ${card.power}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
            ${cardBattleState.playerCard !== null ? `
                <div style="text-align: center; margin: 40px 0; padding: 30px; background: #f8f9fa; border-radius: 15px;">
                    <div style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; gap: 20px;">
                        <div style="background: #FFD3B5; border: 3px solid #5c4d3c; padding: 25px; border-radius: 15px; min-width: 200px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            <div style="font-size: 3.5rem; margin-bottom: 15px;">${cardBattleState.playerDeck[cardBattleState.playerCard].icon}</div>
                            <h4 style="color: #5c4d3c; margin: 10px 0; font-size: 1.1rem; font-weight: 600;">${cardBattleState.playerDeck[cardBattleState.playerCard].name}</h4>
                            <div style="background: white; padding: 8px 15px; border-radius: 20px; display: inline-block; margin-top: 10px;">
                                <span style="color: #5c4d3c; font-weight: 600;">Power: ${cardBattleState.playerDeck[cardBattleState.playerCard].power}</span>
                            </div>
                        </div>
                        <div style="font-size: 2.5rem; color: #5c4d3c; font-weight: bold;">VS</div>
                        <div id="opponent-card-display" style="background: white; border: 3px solid #e0e0e0; padding: 25px; border-radius: 15px; min-width: 200px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            <div style="font-size: 3.5rem; margin-bottom: 15px;">❓</div>
                            <h4 style="color: #666; margin: 10px 0; font-size: 1.1rem;">Opponent Card</h4>
                            <div style="background: #f8f9fa; padding: 8px 15px; border-radius: 20px; display: inline-block; margin-top: 10px;">
                                <span style="color: #666;">Hidden</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="window.executeBattle()" style="background: #5c4d3c; color: white; border: none; padding: 15px 50px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 30px; font-size: 1.1rem; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#5c4d3c'; this.style.transform='scale(1)'">Battle!</button>
                </div>
            ` : '<div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 15px;"><p style="color: #666; font-size: 1.1rem;">Select a card from your deck to start the battle!</p></div>'}
            <div id="battle-result" style="margin-top: 25px; text-align: center; min-height: 60px; padding: 15px;"></div>
        `;
    }
    
    window.selectPlayerCard = function(index) {
        cardBattleState.playerCard = index;
        renderCardBattle();
    };
    
    window.executeBattle = function() {
        if (cardBattleState.playerCard === null) return;
        
        const playerCard = cardBattleState.playerDeck[cardBattleState.playerCard];
        const opponentCard = cardBattleState.opponentDeck[Math.floor(Math.random() * cardBattleState.opponentDeck.length)];
        cardBattleState.opponentCard = opponentCard;
        
        const playerPower = playerCard.power + (Math.random() * 10 - 5); // Add randomness
        const opponentPower = opponentCard.power + (Math.random() * 10 - 5);
        
        const resultDiv = document.getElementById('battle-result');
        const opponentDiv = document.getElementById('opponent-card-display');
        
        opponentDiv.innerHTML = `
            <div style="font-size: 3rem;">${opponentCard.icon}</div>
            <h4 style="color: #5c4d3c; margin: 10px 0;">${opponentCard.name}</h4>
            <p style="color: #666;">Power: ${opponentCard.power}</p>
        `;
        opponentDiv.style.background = '#FFD3B5';
        opponentDiv.style.borderColor = '#5c4d3c';
        
        setTimeout(() => {
            if (playerPower > opponentPower) {
                cardBattleState.wins++;
                resultDiv.innerHTML = `
                    <div style="background: #28a745; color: white; padding: 20px; border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-trophy"></i> Victory!<br>
                        <div style="font-size: 1.1rem; margin-top: 10px; opacity: 0.95;">${playerCard.name} defeated ${opponentCard.name}!</div>
                    </div>
                `;
            } else {
                cardBattleState.losses++;
                resultDiv.innerHTML = `
                    <div style="background: #dc3545; color: white; padding: 20px; border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-x-circle"></i> Defeat<br>
                        <div style="font-size: 1.1rem; margin-top: 10px; opacity: 0.95;">${opponentCard.name} defeated ${playerCard.name}!</div>
                    </div>
                `;
            }
            
            cardBattleState.playerDeck.splice(cardBattleState.playerCard, 1);
            const opponentIndex = cardBattleState.opponentDeck.findIndex(c => c.name === opponentCard.name);
            if (opponentIndex !== -1) cardBattleState.opponentDeck.splice(opponentIndex, 1);
            
            cardBattleState.playerCard = null;
            cardBattleState.round++;
            
            setTimeout(() => {
                resultDiv.innerHTML = '';
                renderCardBattle();
            }, 2000);
        }, 1000);
    };

    // Community Builder - FULLY PLAYABLE
    let communityState = {
        buildings: [
            { icon: '🏠', name: 'Housing', unlocked: true, cost: 0, level: 1 },
            { icon: '🏫', name: 'School', unlocked: false, cost: 10, level: 0 },
            { icon: '🌳', name: 'Park', unlocked: false, cost: 15, level: 0 },
            { icon: '🏥', name: 'Hospital', unlocked: false, cost: 20, level: 0 },
            { icon: '🎨', name: 'Arts Center', unlocked: false, cost: 25, level: 0 },
            { icon: '🏛️', name: 'Library', unlocked: false, cost: 30, level: 0 },
            { icon: '🌉', name: 'Bridge', unlocked: false, cost: 40, level: 0 },
            { icon: '🎪', name: 'Community Center', unlocked: false, cost: 50, level: 0 }
        ],
        hours: <?= $user_points ?>,
        totalBuildings: 1
    };
    
    window.startCommunityBuilder = function() {
        const modal = document.createElement('div');
        modal.id = 'community-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 800px; width: 90%; max-height: 90vh; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5; display: flex; flex-direction: column; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; flex-shrink: 0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-building"></i> Community Builder</h2>
                    <span onclick="document.getElementById('community-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; line-height: 1;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; flex-shrink: 0;">
                    <div style="display: flex; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${communityState.hours}</div>
                            <div style="color: #666; font-size: 0.9rem;">Volunteer Hours</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${communityState.totalBuildings}</div>
                            <div style="color: #666; font-size: 0.9rem;">Buildings</div>
                        </div>
                    </div>
                </div>
                <p style="color: #666; margin-bottom: 25px; text-align: center; font-size: 1rem; flex-shrink: 0;">Use your volunteer hours to unlock and upgrade buildings in your virtual community!</p>
                <div id="community-grid" style="background: #f8f9fa; padding: 30px; border-radius: 15px; flex: 1; overflow-y: auto;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                        ${communityState.buildings.map((building, i) => `
                            <div onclick="window.buildCommunity(${i})" 
                                 style="background: ${building.unlocked ? '#FFD3B5' : 'white'}; border: 2px solid ${building.unlocked ? '#5c4d3c' : '#e0e0e0'}; padding: 25px; border-radius: 12px; cursor: ${building.unlocked || communityState.hours >= building.cost ? 'pointer' : 'not-allowed'}; text-align: center; transition: all 0.3s; opacity: ${building.unlocked ? '1' : building.unlocked || communityState.hours >= building.cost ? '0.8' : '0.5'}; min-height: 140px; display: flex; flex-direction: column; justify-content: center; align-items: center;" 
                                 title="${building.unlocked ? 'Level ' + building.level + ' - Click to upgrade' : 'Cost: ' + building.cost + ' hours'}"
                                 onmouseover="if(${building.unlocked || communityState.hours >= building.cost}) this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)'"
                                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                <div style="font-size: 3rem; margin-bottom: 12px;">${building.icon}</div>
                                <div style="font-size: 0.9rem; color: #333; margin-bottom: 8px; font-weight: 600;">${building.name}</div>
                                ${building.unlocked ? `
                                    <div style="background: #5c4d3c; color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">Level ${building.level}</div>
                                    <div style="color: #666; font-size: 0.75rem; margin-top: 5px;">Upgrade: ${building.level * 10} hrs</div>
                                ` : `
                                    <div style="background: ${communityState.hours >= building.cost ? '#28a745' : '#dc3545'}; color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">${building.cost} hrs</div>
                                    ${communityState.hours < building.cost ? `<div style="color: #666; font-size: 0.75rem; margin-top: 5px;">Need ${building.cost - communityState.hours} more</div>` : ''}
                                `}
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div id="community-message" style="margin-top: 20px; text-align: center; min-height: 50px; padding: 15px; flex-shrink: 0;"></div>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0; flex-shrink: 0;">
                    <button onclick="document.getElementById('community-modal').remove()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    window.buildCommunity = function(index) {
        // Track game completion when building is constructed
        if (!communityState.buildings[index].unlocked && communityState.hours >= communityState.buildings[index].cost) {
            // Award points for building construction
            const pointsEarned = communityState.buildings[index].cost * 2; // 2 points per hour spent
            fetch('add_points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    points: pointsEarned,
                    game_won: true,
                    increment_games: false // Don't increment games for each building, only when game ends
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateGameStats(data);
                }
            })
            .catch(error => console.error('Error:', error));
        }
        const building = communityState.buildings[index];
        const messageDiv = document.getElementById('community-message');
        
        if (!building.unlocked) {
            if (communityState.hours >= building.cost) {
                building.unlocked = true;
                building.level = 1;
                communityState.hours -= building.cost;
                communityState.totalBuildings++;
                messageDiv.innerHTML = `
                    <div style="background: #28a745; color: white; padding: 20px; border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-check-circle"></i> ${building.name} Unlocked!<br>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 5px;">-${building.cost} volunteer hours</div>
                    </div>
                `;
                setTimeout(() => window.startCommunityBuilder(), 2000);
            } else {
                messageDiv.innerHTML = `
                    <div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-x-circle"></i> Insufficient Hours<br>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 5px;">Need ${building.cost - communityState.hours} more hours</div>
                    </div>
                `;
            }
        } else {
            const upgradeCost = building.level * 10;
            if (communityState.hours >= upgradeCost) {
                building.level++;
                communityState.hours -= upgradeCost;
                messageDiv.innerHTML = `
                    <div style="background: #28a745; color: white; padding: 20px; border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-arrow-up-circle"></i> ${building.name} Upgraded!<br>
                        <div style="font-size: 1.2rem; margin-top: 10px;">Level ${building.level}</div>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 5px;">-${upgradeCost} volunteer hours</div>
                    </div>
                `;
                setTimeout(() => window.startCommunityBuilder(), 2000);
            } else {
                messageDiv.innerHTML = `
                    <div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; font-weight: 600;">
                        <i class="bi bi-x-circle"></i> Insufficient Hours<br>
                        <div style="font-size: 0.9rem; opacity: 0.9; margin-top: 5px;">Need ${upgradeCost} hours to upgrade</div>
                    </div>
                `;
            }
        }
    };

    // Mission Time Traveler - FULLY PLAYABLE
    window.startMissionTimeTraveler = function() {
        const timelineData = [
            { year: 'Start', event: 'Your First Mission', hours: 0, description: 'Where your journey began' },
            { year: 'Month 1', event: 'First Milestone', hours: 10, description: 'Reached 10 volunteer hours!' },
            { year: 'Month 3', event: 'Level Up', hours: 30, description: 'Level 2 achieved!' },
            { year: 'Month 6', event: 'Community Impact', hours: 60, description: 'Made significant difference' },
            { year: 'Present', event: 'Current Status', hours: <?= $user_points ?>, description: 'Keep going strong!' }
        ];
        
        let currentTimeIndex = timelineData.length - 1;
        
        const modal = document.createElement('div');
        modal.id = 'time-travel-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 700px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-clock-history"></i> Mission Time Traveler</h2>
                    <span onclick="document.getElementById('time-travel-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; line-height: 1;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;" id="time-display">${timelineData[currentTimeIndex].year}</div>
                    <div style="color: #666; font-size: 0.9rem;">Time Period</div>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 20px; justify-content: center;">
                    <button onclick="window.travelTime(-1)" style="background: #5c4d3c; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">◀ Past</button>
                    <button onclick="window.travelTime(1)" style="background: #5c4d3c; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Future ▶</button>
                </div>
                <div id="time-travel-content" style="background: #FFD3B5; padding: 30px; border-radius: 15px; min-height: 300px;">
                    ${renderTimeTravelContent(timelineData[currentTimeIndex])}
                </div>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <button onclick="document.getElementById('time-travel-modal').remove()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        window.travelTime = function(direction) {
            currentTimeIndex += direction;
            if (currentTimeIndex < 0) currentTimeIndex = 0;
            if (currentTimeIndex >= timelineData.length) currentTimeIndex = timelineData.length - 1;
            
            document.getElementById('time-display').textContent = timelineData[currentTimeIndex].year;
            document.getElementById('time-travel-content').innerHTML = renderTimeTravelContent(timelineData[currentTimeIndex]);
        };
        
        function renderTimeTravelContent(data) {
            return `
                <div style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">${data.year === 'Present' ? '🎯' : data.year === 'Start' ? '🌱' : '⭐'}</div>
                    <h3 style="color: #5c4d3c; margin-bottom: 15px;">${data.event}</h3>
                    <p style="color: #666; font-size: 1.1rem; margin-bottom: 20px;">${data.description}</p>
                    <div style="background: white; padding: 15px; border-radius: 10px; display: inline-block;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;">${data.hours}</div>
                        <div style="color: #666; font-size: 0.9rem;">Volunteer Hours</div>
                    </div>
                </div>
            `;
        }
    }

    // Impact Visualizer - FULLY PLAYABLE
    window.startImpactVisualizer = function() {
        let isDrawing = false;
        let canvas, ctx;
        
        const modal = document.createElement('div');
        modal.id = 'visualizer-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 700px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-bar-chart"></i> Impact Visualizer</h2>
                    <span onclick="document.getElementById('visualizer-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; line-height: 1;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <p style="color: #666; margin-bottom: 20px; text-align: center;">Draw your impact visualization on the canvas!</p>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; justify-content: center;">
                        <button onclick="window.setColor('#5c4d3c')" style="background: #5c4d3c; width: 40px; height: 40px; border: 2px solid #333; border-radius: 50%; cursor: pointer;"></button>
                        <button onclick="window.setColor('#FFD3B5')" style="background: #FFD3B5; width: 40px; height: 40px; border: 2px solid #333; border-radius: 50%; cursor: pointer;"></button>
                        <button onclick="window.setColor('#28a745')" style="background: #28a745; width: 40px; height: 40px; border: 2px solid #333; border-radius: 50%; cursor: pointer;"></button>
                        <button onclick="window.setColor('#007bff')" style="background: #007bff; width: 40px; height: 40px; border: 2px solid #333; border-radius: 50%; cursor: pointer;"></button>
                        <button onclick="window.clearCanvas()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Clear</button>
                    </div>
                    <canvas id="impact-canvas" width="600" height="400" style="background: white; border: 2px solid #e0e0e0; border-radius: 10px; cursor: crosshair; display: block; margin: 0 auto;"></canvas>
                </div>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <button onclick="window.saveVisualization()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-right: 10px; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Save</button>
                    <button onclick="document.getElementById('visualizer-modal').remove()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        canvas = document.getElementById('impact-canvas');
        ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#5c4d3c';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        
        canvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        });
        
        canvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.stroke();
        });
        
        canvas.addEventListener('mouseup', () => isDrawing = false);
        canvas.addEventListener('mouseleave', () => isDrawing = false);
        
        window.setColor = function(color) {
            ctx.strokeStyle = color;
        };
        
        window.clearCanvas = function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        };
        
        window.saveVisualization = function() {
            const dataURL = canvas.toDataURL();
            const link = document.createElement('a');
            link.download = 'impact-visualization.png';
            link.href = dataURL;
            link.click();
            
            // Award points for creating visualization
            const pointsEarned = 15; // Fixed points for creating visualization
            fetch('add_points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    points: pointsEarned,
                    game_won: true,
                    increment_games: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateGameStats(data);
                    alert('Visualization saved! You earned ' + pointsEarned + ' points!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Visualization saved!');
            });
        };
    }

    // Mission Roulette - FULLY PLAYABLE
    window.startMissionRoulette = function() {
        const missions = [
            { name: 'Environmental Cleanup', hours: 3, icon: '🌳' },
            { name: 'Tutoring', hours: 2, icon: '📚' },
            { name: 'Food Bank', hours: 4, icon: '🍎' },
            { name: 'Community Event', hours: 5, icon: '🎉' },
            { name: 'Healthcare Support', hours: 3, icon: '❤️' },
            { name: 'Park Maintenance', hours: 2, icon: '🌲' },
            { name: 'Senior Care', hours: 4, icon: '👴' },
            { name: 'Youth Mentoring', hours: 3, icon: '👦' }
        ];
        
        let isSpinning = false;
        let currentRotation = 0;
        
        const modal = document.createElement('div');
        modal.id = 'roulette-modal';
        modal.style.cssText = 'position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;';
        modal.innerHTML = `
            <div style="background: white; padding: 40px; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid #FFD3B5; text-align: center;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <h2 style="color: #5c4d3c; margin: 0; font-size: 1.8rem; font-weight: 700;"><i class="bi bi-arrow-repeat"></i> Mission Roulette</h2>
                    <span onclick="document.getElementById('roulette-modal').remove()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; line-height: 1;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#aaa'">&times;</span>
                </div>
                <div style="position: relative; width: 300px; height: 300px; margin: 30px auto;">
                    <div id="roulette-wheel" style="width: 300px; height: 300px; border-radius: 50%; background: conic-gradient(
                        ${missions.map((m, i) => `${360 / missions.length * i}deg ${360 / missions.length * (i + 1)}deg ${i % 2 === 0 ? '#FFD3B5' : '#5c4d3c'}`).join(', ')}
                    ); position: relative; border: 10px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.2); transition: transform 3s cubic-bezier(0.17, 0.67, 0.12, 0.99);">
                        ${missions.map((m, i) => {
                            const angle = (360 / missions.length) * i + (360 / missions.length / 2);
                            const rad = angle * Math.PI / 180;
                            const x = 150 + 100 * Math.cos(rad);
                            const y = 150 + 100 * Math.sin(rad);
                            return `<div style="position: absolute; left: ${x}px; top: ${y}px; transform: translate(-50%, -50%); font-size: 1.5rem;">${m.icon}</div>`;
                        }).join('')}
                    </div>
                    <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 15px solid transparent; border-right: 15px solid transparent; border-top: 30px solid #5c4d3c; z-index: 10;"></div>
                </div>
                <button id="spin-btn" onclick="window.spinRouletteWheel()" style="background: #5c4d3c; color: white; border: none; padding: 15px 40px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1.1rem; margin: 20px 10px; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Spin!</button>
                <div id="roulette-result" style="font-size: 1.2rem; font-weight: 600; margin-top: 20px; color: #5c4d3c; min-height: 60px; padding: 15px; background: #f8f9fa; border-radius: 10px;"></div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <button onclick="document.getElementById('roulette-modal').remove()" style="background: #5c4d3c; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#4a3d30'" onmouseout="this.style.background='#5c4d3c'">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        window.spinRouletteWheel = function() {
            if (isSpinning) return;
            isSpinning = true;
            document.getElementById('spin-btn').disabled = true;
            document.getElementById('spin-btn').textContent = 'Spinning...';
            
            const spins = 5 + Math.random() * 5; // 5-10 full rotations
            const selectedIndex = Math.floor(Math.random() * missions.length);
            const targetAngle = (360 / missions.length) * selectedIndex;
            currentRotation += spins * 360 - (currentRotation % 360) + (360 - targetAngle);
            
            const wheel = document.getElementById('roulette-wheel');
            wheel.style.transform = `rotate(${currentRotation}deg)`;
            
            setTimeout(() => {
                const result = missions[selectedIndex];
                const pointsEarned = result.hours * 3; // 3 points per hour
                document.getElementById('roulette-result').innerHTML = `
                    <div style="font-size: 2rem; margin-bottom: 10px;">${result.icon}</div>
                    <div>${result.name}</div>
                    <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">${result.hours} volunteer hours</div>
                    <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                        <div style="color: #5c4d3c; font-weight: 600;">Points Earned: ${pointsEarned}</div>
                    </div>
                `;
                
                // Award points
                fetch('add_points.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        points: pointsEarned,
                        game_won: true,
                        increment_games: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateGameStats(data);
                    }
                })
                .catch(error => console.error('Error:', error));
                
                isSpinning = false;
                document.getElementById('spin-btn').disabled = false;
                document.getElementById('spin-btn').textContent = 'Spin Again!';
            }, 3000);
        };
    };
    </script>

    <!-- DNA Profiler Modal -->
    <div id="dnaModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); overflow: auto;">
        <div class="modal-content" style="background: white; margin: 3% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 700px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <span class="close" onclick="window.closeDNAModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2 style="color: #5c4d3c; margin-bottom: 20px;"><i class="bi bi-dna"></i> Volunteer DNA Profiler</h2>
            <div id="dna-stats" style="display: flex; justify-content: space-around; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <div style="text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #5c4d3c;" id="dna-question-num">1/8</div>
                    <div style="color: #666; font-size: 0.9rem;">Question</div>
                </div>
            </div>
            <div id="dna-question-container">            </div>
        </div>
    </div>
    </script>
</body>
</html>

















































