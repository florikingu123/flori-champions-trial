<?php
session_start();
include 'config.php';

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$user_email = $_SESSION['email'];

// Check if user is a manager
$check_stmt = $conn->prepare("SELECT * FROM family WHERE managers_email = ?");
$check_stmt->bind_param("s", $user_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->fetch_assoc()) {
    // User is a manager, redirect to manager chat
    header("Location: manager_chat.php");
    exit();
}
$check_stmt->close();

// Check if user is a member
$check_stmt = $conn->prepare("SELECT * FROM family WHERE member_email = ?");
$check_stmt->bind_param("s", $user_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
if ($check_result->fetch_assoc()) {
    // User is a member, redirect to member chat
    header("Location: member_chat.php");
    exit();
}
$check_stmt->close();

// If user is neither manager nor member, redirect to directory
header("Location: browse_directory.php?message=no_org");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Organization Communication - VolunteerHub</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .header {
            --background-color: #FFDDC1 !important;
            background-color: #FFDDC1 !important;
        }
        
        .chat-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            max-width: 80%;
        }
        
        .message.sent {
            background: #FFD3B5;
            margin-left: auto;
        }
        
        .message.received {
            background: #f0f0f0;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .message-sender {
            font-weight: bold;
            color: #333;
        }
        
        .message-time {
            color: #666;
        }
        
        .message-content {
            color: #333;
            word-wrap: break-word;
        }
        
        .chat-input {
            display: flex;
            gap: 10px;
        }
        
        .chat-input textarea {
            flex-grow: 1;
            border: 2px solid #FFD3B5;
            border-radius: 10px;
            padding: 10px;
            resize: none;
            height: 60px;
        }
        
        .chat-input textarea:focus {
            outline: none;
            border-color: #FFB5A7;
        }
        
        .send-button {
            background: #FFD3B5;
            color: #333;
            border: none;
            border-radius: 10px;
            padding: 0 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .send-button:hover {
            background: #FFB5A7;
        }

        .chat-info-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .chat-info-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .chat-info-section p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .chat-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #FFD3B5;
        }

        .feature-card h3 {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
    </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>
<body class="index-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.html" class="logo d-flex align-items-center">
                <h1 class="sitename">VolunteerHub</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <?php if ($is_manager): ?>
                    <li><a href="famify.php">Organization Center</a></li>
                    <li><a href="add_points.php">Add Points</a></li>
                    <li><a href="points_shop.php">Points Shop</a></li>
                    <li><a href="family_calendar.php">Calendar</a></li>
                    <li><a href="family_chat.php" class="active">Family Chat</a></li>
                    <li><a href="ai.php">Chore ai</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                    <li><a href="member.php">Family center</a></li>
                    <li><a href="games.php">Games</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="rew.php">Rewards</a></li>
                    <li><a href="view_calendar.php">Calendar</a></li>
                    <li><a href="family_chat.php" class="active">Family Chat</a></li>
                    <li><a href="ai.php">Chore ai</a></li>
                    <li><a href="logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container mt-5 pt-5">
            <!-- Chat Info Section -->
            <div class="chat-info-section">
                <h2>Family Chat</h2>
                <p>Welcome to your family's chat room! This is a safe space for family members to communicate, share updates, and stay connected. The chat is private and only accessible to your family members.</p>
                
                <div class="chat-features">
                    <div class="feature-card">
                        <h3><i class="bi bi-shield-check"></i> Private & Secure</h3>
                        <p>Your conversations are private and only visible to your family members.</p>
                    </div>
                    <div class="feature-card">
                        <h3><i class="bi bi-clock-history"></i> Real-time Updates</h3>
                        <p>Messages are delivered instantly and the chat updates automatically.</p>
                    </div>
                    <div class="feature-card">
                        <h3><i class="bi bi-people"></i> Family Only</h3>
                        <p>Only family members can access and participate in the chat.</p>
                    </div>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages">
                    <?php foreach (array_reverse($messages) as $message): ?>
                        <div class="message <?php echo $message['user_id'] === $user_id ? 'sent' : 'received'; ?>">
                            <div class="message-header">
                                <span class="message-sender"><?php echo htmlspecialchars($message['name']); ?></span>
                                <span class="message-time"><?php echo date('M j, g:i a', strtotime($message['created_at'])); ?></span>
                            </div>
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="chat-input">
                    <textarea name="message" placeholder="Type your message..." required></textarea>
                    <button type="submit" class="send-button">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

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
                        <li><i class="bi bi-chevron-right"></i> <a href="about.html">About</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="services.html">Services</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="contact.html">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-3 footer-links">
                    <h4>Our Services</h4>
                    <ul>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Family Management</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Chore Tracking</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Rewards System</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Family Calendar</a></li>
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
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Handle form submission with AJAX
        const form = document.querySelector('form');
        const textarea = form.querySelector('textarea');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (textarea.value.trim() === '') {
                return;
            }

            const formData = new FormData(form);
            
            fetch('family_chat.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newMessages = doc.querySelector('.chat-messages').innerHTML;
                chatMessages.innerHTML = newMessages;
                chatMessages.scrollTop = chatMessages.scrollHeight;
                textarea.value = '';
                textarea.style.height = 'auto';
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });

        // Auto-resize textarea
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Refresh chat every 30 seconds
        setInterval(function() {
            fetch('family_chat.php')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newMessages = doc.querySelector('.chat-messages').innerHTML;
                    if (newMessages !== chatMessages.innerHTML) {
                        chatMessages.innerHTML = newMessages;
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                });
        }, 30000);
    </script>
</body>
</html> 
</body>
</html> 
</body>
</html> 
</body>
</html> 