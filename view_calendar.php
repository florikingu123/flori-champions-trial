<?php
session_start();
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$member_email = $_SESSION['email'];
$manager_email = null;

// Get the organization admin's email
$stmt = $conn->prepare("SELECT managers_email FROM family WHERE member_email = ?");
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("s", $member_email);
$stmt->execute();
$stmt->bind_result($manager_email);
$stmt->fetch();
$stmt->close();

if (!$manager_email) {
    die("Error: Could not find organization administrator for this volunteer.");
}

// Fetch all events for the organization
$events = [];
$stmt = $conn->prepare("SELECT * FROM family_events WHERE manager_email = ? ORDER BY event_date, event_time");
if ($stmt === false) {
    die("Error preparing events query: " . $conn->error);
}

$stmt->bind_param("s", $manager_email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Calendar - VolunteerHub</title>
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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    
    <style>
        .header {
            --background-color: #FFDDC1 !important;
            background-color: #FFDDC1 !important;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .event-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .fc-event {
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
        }
        
        .event-school {
            background: #FFD3B5;
            border-color: #FFD3B5;
        }
        
        .event-busy {
            background: #FFAAA5;
            border-color: #FFAAA5;
        }
        
        .event-other {
            background: #A5D8FF;
            border-color: #A5D8FF;
        }
        
        .event-info {
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .event-info h4 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .event-info p {
            color: #666;
            margin-bottom: 10px;
        }
        
        .event-type-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .type-school {
            background: #FFD3B5;
            color: #333;
        }
        
        .type-busy {
            background: #FFAAA5;
            color: white;
        }
        
        .type-other {
            background: #A5D8FF;
            color: #333;
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

        /* Header background color */
        .header {
            background: #FFD3B5;
        }

        /* Remove all custom navigation styles and let main.css handle it */
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
                    <li><a href="member.php">Organization Center</a></li>      
                    <li><a href="games.php">Engagement Zone</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="rew.php">Achievements</a></li>
                    <li><a href="view_calendar.php" class="active">Events Calendar</a></li>
                    <li><a href="ai.php">AI</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container mt-5 pt-5">
            <div class="section-title mb-4">
                <h2>Events Calendar</h2>
                <p>Keep track of all your volunteer events, activities, and important dates in one place.</p>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <div class="calendar-container">
                        <div id="calendar"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="event-details">
                        <h3>Event Details</h3>
                        <div id="eventInfo" class="event-info">
                            <p class="text-center text-muted">Click on an event to view details</p>
                        </div>
                    </div>
                </div>
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
                        <li><i class="bi bi-chevron-right"></i> <a href="#">Manage Missions and Achievements</a></li>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode(array_map(function($event) {
                    return [
                        'id' => $event['id'],
                        'title' => $event['event_title'],
                        'start' => $event['event_date'] . 'T' . $event['event_time'],
                        'description' => $event['event_description'],
                        'type' => $event['event_type'],
                        'className' => 'event-' . $event['event_type']
                    ];
                }, $events)); ?>,
                eventClick: function(info) {
                    const eventInfo = document.getElementById('eventInfo');
                    const typeClass = {
                        'school': 'type-school',
                        'busy': 'type-busy',
                        'other': 'type-other'
                    }[info.event.extendedProps.type];
                    
                    const typeText = {
                        'school': 'School Day',
                        'busy': 'Busy Day',
                        'other': 'Other Event'
                    }[info.event.extendedProps.type];
                    
                    eventInfo.innerHTML = `
                        <span class="event-type-badge ${typeClass}">${typeText}</span>
                        <h4>${info.event.title}</h4>
                        <p><strong>Date:</strong> ${info.event.start.toLocaleDateString()}</p>
                        <p><strong>Time:</strong> ${info.event.start.toLocaleTimeString()}</p>
                        <p><strong>Description:</strong> ${info.event.extendedProps.description || 'No description provided'}</p>
                    `;
                }
            });
            calendar.render();
        });
    </script>
</body>
</html> 