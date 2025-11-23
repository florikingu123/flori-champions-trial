<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Resources - VolunteerHub</title>
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .section {
            padding: 60px 0;
        }
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .section-title h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }
        .tab-btn {
            padding: 10px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: #FFD3B5;
            color: white;
        }
        .tab-content {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .tab-content.active {
            display: block;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .tool-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .tool-card:hover {
            transform: translateY(-5px);
        }
        .tool-card h3 {
            color: #333;
            margin-bottom: 20px;
        }
        .tool-card ul {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }
        .tool-card li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        .tool-card li:before {
            content: "✓";
            color: #FFD3B5;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #FFD3B5;
            color: white;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }
        .accordion {
            max-width: 800px;
            margin: 0 auto;
        }
        .accordion-item {
            background: white;
            border-radius: 15px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .accordion-header {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .accordion-header h4 {
            margin: 0;
            color: #333;
        }
        .accordion-content {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .accordion-content.active {
            padding: 20px;
            max-height: 500px;
        }
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .resource-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .resource-card h3 {
            color: #333;
            margin-bottom: 20px;
        }
        .resource-card ul {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
            text-align: left;
        }
        .resource-card li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        .resource-card li:before {
            content: "✓";
            color: #FFD3B5;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .bg-light {
            background: #f8f9fa;
        }
        .download-section {
            text-align: center;
            margin-top: 40px;
        }
        .download-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        @media (max-width: 768px) {
            .tools-grid, .resources-grid {
                grid-template-columns: 1fr;
            }
            .tabs {
                flex-direction: column;
                align-items: center;
            }
        }
        /* Popup styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .popup-content h3 {
            color: #333;
            margin-bottom: 20px;
        }

        .popup-content ul {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .popup-content li {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .popup-content li:before {
            content: "•";
            color: #FFD3B5;
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .chat-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .chat-box {
            background: #fff;
            padding: 20px;
            height: 400px;
            overflow-y: auto;
            border-radius: 15px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
        }

        .button:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
        }

        .message {
            margin: 15px 0;
            padding: 15px;
            border-radius: 12px;
            max-width: 80%;
            animation: fadeIn 0.3s ease;
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

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
                    <li><a href="famify.php">Organization Center</a></li>
                    <li><a href="addfam.php">Add a Family Member</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="connect.html">Connect</a></li>
                    <li><a href="points_shop.php">Rewards</a></li>
                    <li><a href="donate.php">Donate</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-title dark-background">
        <div class="container position-relative">
            <h1>Parental Advice</h1>
            <p>Tools and resources to help you be a better parent</p>
        </div>
    </div>

    <!-- Section 1: Parental Control Tools -->
    <section class="section">
        <div class="section-title">
            <h2>Parental Control Tools</h2>
            <p>Keep your children safe and manage their digital activities</p>
        </div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="monitoring">Monitoring</button>
            <button class="tab-btn" data-tab="filtering">Filtering</button>
            <button class="tab-btn" data-tab="tracking">Tracking</button>
        </div>

        <div class="tab-content active" id="monitoring">
            <div class="tools-grid">
                <div class="tool-card">
                    <h3>Screen Time Monitor</h3>
                    <ul>
                        <li>Daily usage reports</li>
                        <li>App usage tracking</li>
                        <li>Screen time limits</li>
                        <li>Remote control features</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>Activity Monitor</h3>
                    <ul>
                        <li>Real-time activity tracking</li>
                        <li>Usage patterns analysis</li>
                        <li>Custom alerts</li>
                        <li>Detailed reports</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>Device Manager</h3>
                    <ul>
                        <li>Device inventory</li>
                        <li>Usage statistics</li>
                        <li>Remote management</li>
                        <li>Device restrictions</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="filtering">
            <div class="tools-grid">
                <div class="tool-card">
                    <h3>Content Filter</h3>
                    <ul>
                        <li>Web content filtering</li>
                        <li>Safe search enforcement</li>
                        <li>Social media monitoring</li>
                        <li>Real-time alerts</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>App Filter</h3>
                    <ul>
                        <li>App blocking</li>
                        <li>Age restrictions</li>
                        <li>Category filtering</li>
                        <li>Time-based access</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>Communication Filter</h3>
                    <ul>
                        <li>Message monitoring</li>
                        <li>Contact management</li>
                        <li>Call filtering</li>
                        <li>Emergency contacts</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="tracking">
            <div class="tools-grid">
                <div class="tool-card">
                    <h3>Location Tracker</h3>
                    <ul>
                        <li>Real-time location tracking</li>
                        <li>Geofencing alerts</li>
                        <li>Location history</li>
                        <li>Emergency SOS feature</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>Route Tracker</h3>
                    <ul>
                        <li>Route monitoring</li>
                        <li>Speed alerts</li>
                        <li>Route history</li>
                        <li>Safe zone alerts</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>Activity Tracker</h3>
                    <ul>
                        <li>Movement tracking</li>
                        <li>Activity patterns</li>
                        <li>Health monitoring</li>
                        <li>Sleep tracking</li>
                    </ul>
                    <div style="text-align: center;">
                        <a href="#" class="btn">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Expert Advice -->
    <section class="section bg-light">
        <div class="section-title">
            <h2>Expert Parenting Advice</h2>
            <p>Professional guidance for modern parenting challenges</p>
        </div>

        <div class="accordion">
            <div class="accordion-item">
                <div class="accordion-header">
                    <h4>Age-Appropriate Development</h4>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="accordion-content">
                    <p>Learn about your child's developmental milestones and how to support their growth at each stage. Our experts provide guidance on physical, cognitive, and emotional development.</p>
                    <ul>
                        <li>Developmental milestones by age</li>
                        <li>Activities to support growth</li>
                        <li>Warning signs to watch for</li>
                        <li>When to seek professional help</li>
                    </ul>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header">
                    <h4>Effective Communication</h4>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="accordion-content">
                    <p>Master the art of talking with your children and building strong relationships. Learn techniques for effective communication at different ages.</p>
                    <ul>
                        <li>Active listening techniques</li>
                        <li>Age-appropriate conversations</li>
                        <li>Conflict resolution strategies</li>
                        <li>Building trust through communication</li>
                    </ul>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header">
                    <h4>Digital Safety</h4>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="accordion-content">
                    <p>Keep your children safe online with expert tips and best practices. Learn how to protect your family in the digital age.</p>
                    <ul>
                        <li>Online safety guidelines</li>
                        <li>Social media monitoring</li>
                        <li>Cyberbullying prevention</li>
                        <li>Digital privacy protection</li>
                    </ul>
                </div>
            </div>

            <div class="accordion-item">
                <div class="accordion-header">
                    <h4>Emotional Well-being</h4>
                    <span class="toggle-icon">+</span>
                </div>
                <div class="accordion-content">
                    <p>Support your child's mental health and emotional development. Learn how to nurture emotional intelligence and resilience.</p>
                    <ul>
                        <li>Emotional intelligence development</li>
                        <li>Stress management techniques</li>
                        <li>Building self-esteem</li>
                        <li>Healthy coping mechanisms</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Parental AI Chat -->
    <section class="section bg-light">
        <div class="section-title">
            <h2>Parental AI Assistant</h2>
            <p>Get instant parenting advice and device recommendations</p>
        </div>

        <div class="chat-container">
            <div id="chat-box" class="chat-box">
            </div>

            <form id="chat-form" class="input-area">
                <input
                    type="text"
                    id="user-input"
                    class="input-field"
                    placeholder="Ask about parenting devices or get advice..."
                    required
                />
                <button type="submit" class="button">
                    <i class="bi bi-send"></i> Send
                </button>
            </form>
        </div>
    </section>

    <!-- Section 4: Download Guide -->
    <section class="section">
        <div class="section-title">
            <h2>Parenting Resources</h2>
            <p>Download our comprehensive guide for better parenting</p>
        </div>

        <div class="download-section">
            <div class="download-card">
                <h3>Download Our Guide</h3>
                <p>Get our comprehensive guide on parenting siblings and managing family dynamics. Learn effective strategies for:</p>
                <ul>
                    <li>Managing sibling relationships</li>
                    <li>Creating fair chore systems</li>
                    <li>Handling conflicts effectively</li>
                    <li>Building strong family bonds</li>
                </ul>
                <a href="Parenting_siblings.pdf" download class="btn">Download Guide</a>
            </div>
        </div>
    </section>

    <!-- Add popup overlays for Parental Control Tools -->
    <div class="popup-overlay" id="screenTimeMonitorPopup">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <h3>Screen Time Monitor</h3>
            <p>Our advanced screen time monitoring system helps you manage your children's digital device usage effectively. With real-time tracking and customizable settings, you can ensure healthy screen time habits for your family.</p>
            <ul>
                <li>Daily usage reports with detailed analytics</li>
                <li>App usage tracking with time breakdown</li>
                <li>Customizable screen time limits</li>
                <li>Remote control features for instant management</li>
                <li>Family dashboard for overview of all devices</li>
                <li>Weekly and monthly usage reports</li>
            </ul>
        </div>
    </div>

    <div class="popup-overlay" id="contentFilterPopup">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <h3>Content Filter</h3>
            <p>Protect your children from inappropriate content with our advanced filtering system. Customize settings based on age and family values.</p>
            <ul>
                <li>Advanced web content filtering</li>
                <li>Safe search enforcement across browsers</li>
                <li>Social media monitoring and alerts</li>
                <li>Real-time content blocking</li>
                <li>Custom filter lists for your family</li>
                <li>Category-based content management</li>
            </ul>
        </div>
    </div>

    <div class="popup-overlay" id="locationTrackerPopup">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <h3>Location Tracker</h3>
            <p>Keep track of your family's whereabouts with our reliable location tracking system. Get peace of mind knowing where your children are at all times.</p>
            <ul>
                <li>Real-time location tracking</li>
                <li>Geofencing with custom safe zones</li>
                <li>Detailed location history</li>
                <li>Emergency SOS feature</li>
                <li>Battery monitoring</li>
                <li>Family location sharing</li>
            </ul>
        </div>
    </div>

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
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Parental Controls</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Family Activities</a></li>
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Rewards System</a></li>
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
    <script src="assets/js/main.js"></script>
    <script>
        // Tab functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons and contents
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Add active class to clicked button and corresponding content
                btn.classList.add('active');
                const tabId = btn.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Accordion functionality
        const accordionHeaders = document.querySelectorAll('.accordion-header');

        accordionHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const content = header.nextElementSibling;
                const icon = header.querySelector('.toggle-icon');

                // Toggle active class
                content.classList.toggle('active');
                
                // Toggle icon
                if (content.classList.contains('active')) {
                    icon.textContent = '-';
                } else {
                    icon.textContent = '+';
                }

                // Close other accordions
                accordionHeaders.forEach(otherHeader => {
                    if (otherHeader !== header) {
                        const otherContent = otherHeader.nextElementSibling;
                        const otherIcon = otherHeader.querySelector('.toggle-icon');
                        otherContent.classList.remove('active');
                        otherIcon.textContent = '+';
                    }
                });
            });
        });

        // Chat functionality
        const form = document.getElementById('chat-form');
        const input = document.getElementById('user-input');
        const chatBox = document.getElementById('chat-box');
        let loadingMessage;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const userMessage = input.value;
            addMessage('You', userMessage);
            input.value = '';

            loadingMessage = addMessage('Parental AI', 'Typing...');

            try {
                const response = await fetch(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyCDGdoZrW4V10rnTCCGRu6vWNs-AddIsfY",
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            contents: [
                                {
                                    parts: [{ text: userMessage }]
                                }
                            ]
                        })
                    }
                );
                
                const data = await response.json();
                
                if (data.candidates && data.candidates[0] && data.candidates[0].content) {
                    const aiResponse = data.candidates[0].content.parts[0].text;
                    loadingMessage.textContent = `Parental AI: ${aiResponse}`;
                } else {
                    loadingMessage.textContent = 'Parental AI: Sorry, I could not process your request.';
                }
            } catch (error) {
                console.error("Error:", error);
                loadingMessage.textContent = 'Parental AI: Sorry, there was an error processing your request.';
            }
        });

        function addMessage(sender, message) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', sender === 'You' ? 'message-you' : 'message-gpt');
            messageElement.textContent = `${sender}: ${message}`;
            chatBox.appendChild(messageElement);
            chatBox.scrollTop = chatBox.scrollHeight;
            return messageElement;
        }

        // Popup functionality for Parental Control Tools
        document.querySelectorAll('.tool-card .btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const cardTitle = button.closest('.tool-card').querySelector('h3').textContent;
                const popupId = cardTitle.toLowerCase().replace(/\s+/g, '') + 'Popup';
                const popup = document.getElementById(popupId);
                if (popup) {
                    popup.style.display = 'flex';
                }
            });
        });

        // Close popup when clicking the close button
        document.querySelectorAll('.popup-close').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.popup-overlay').style.display = 'none';
            });
        });

        // Close popup when clicking outside
        document.querySelectorAll('.popup-overlay').forEach(popup => {
            popup.addEventListener('click', (e) => {
                if (e.target === popup) {
                    popup.style.display = 'none';
                }
            });
        });

        // Close popup when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.popup-overlay').forEach(popup => {
                    popup.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html> 
</html> 
</html> 
</html> 