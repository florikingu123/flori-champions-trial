<?php
session_start();
include 'config.php';

// Ensure only organization admins can access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$manager_email = $_SESSION['email'];

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_event') {
            $event_title = trim($_POST['event_title']);
            $event_date = trim($_POST['event_date']);
            $event_time = trim($_POST['event_time']);
            $event_type = trim($_POST['event_type']);
            $event_description = trim($_POST['event_description']);
            
            $stmt = $conn->prepare("INSERT INTO family_events (manager_email, event_title, event_date, event_time, event_type, event_description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $manager_email, $event_title, $event_date, $event_time, $event_type, $event_description);
            $stmt->execute();
            $stmt->close();
            
            echo "<script>alert('Event added successfully!'); window.location.href='family_calendar.php';</script>";
        } elseif ($_POST['action'] == 'delete_event' && isset($_POST['event_id'])) {
            $event_id = intval($_POST['event_id']);
            $stmt = $conn->prepare("DELETE FROM family_events WHERE id = ? AND manager_email = ?");
            $stmt->bind_param("is", $event_id, $manager_email);
            $stmt->execute();
            $stmt->close();
            
            echo "<script>alert('Event deleted successfully!'); window.location.href='family_calendar.php';</script>";
        }
    }
}

// Fetch all events
$events = [];
$stmt = $conn->prepare("SELECT * FROM family_events WHERE manager_email = ? ORDER BY event_date, event_time");
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
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Events Calendar - VolunteerHub</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        .header {
            --background-color: #FFDDC1 !important;
            background-color: #FFDDC1 !important;
        }

        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .event-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            border: 2px solid #FFD3B5;
            border-radius: 8px;
            padding: 12px;
        }
        
        .form-control:focus {
            border-color: #FFAAA5;
            box-shadow: 0 0 0 0.2rem rgba(255, 211, 181, 0.25);
        }
        
        .btn-primary {
            background: #FFD3B5;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: #FFAAA5;
            transform: translateY(-2px);
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

        .card {
            border: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            color: #FFD3B5;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #333;
            margin: 10px 0;
        }

        .stat-card p {
            color: #666;
            font-size: 1.1rem;
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
                    <li><a href="famify.php">Organization Center</a></li>
                    <li><a href="addfam.php" id="openModal">Add a Volunteer</a></li>
                    <li><a href="account.php">Your Account</a></li>
                    <li><a href="connect.html">Connect</a></li>
                    <li><a href="points_shop.php">Achievements</a></li>
                    <li><a href="family_calendar.php" class="active">Events Calendar</a></li>
                    <li><a href="donate.php">Donate</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container mt-5 pt-5">
            <div class="row">
                <div class="col-lg-8">
                    <div class="calendar-container">
                        <h2 class="text-center mb-4">Events Calendar</h2>
                        <div id="calendar"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="event-form">
                        <h3 class="text-center mb-4">Manage Events</h3>
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="action" value="add_event">
                            
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="event_title" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="event_date" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Time</label>
                                <input type="time" name="event_time" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Event Type</label>
                                <select name="event_type" class="form-control" required>
                                    <option value="school">School Day</option>
                                    <option value="busy">Busy Day</option>
                                    <option value="other">Other Event</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="event_description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> Add Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="calendar-container">
                        <h2 class="text-center mb-4">Upcoming Events</h2>
                        <div class="row">
                            <?php
                            // Get upcoming events (next 7 days)
                            $upcoming_events = array_filter($events, function($event) {
                                $event_date = strtotime($event['event_date']);
                                $today = strtotime('today');
                                $next_week = strtotime('+7 days');
                                return $event_date >= $today && $event_date <= $next_week;
                            });

                            if (empty($upcoming_events)): ?>
                                <div class="col-12 text-center">
                                    <p>No upcoming events in the next 7 days.</p>
                                </div>
                            <?php else:
                                foreach ($upcoming_events as $event): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($event['event_title']) ?></h5>
                                                <p class="card-text">
                                                    <i class="bi bi-calendar"></i> <?= date('F j, Y', strtotime($event['event_date'])) ?><br>
                                                    <i class="bi bi-clock"></i> <?= date('g:i A', strtotime($event['event_time'])) ?><br>
                                                    <i class="bi bi-tag"></i> <?= ucfirst($event['event_type']) ?>
                                                </p>
                                                <?php if (!empty($event['event_description'])): ?>
                                                    <p class="card-text"><?= htmlspecialchars($event['event_description']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Statistics Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="calendar-container">
                        <h2 class="text-center mb-4">Event Statistics</h2>
                        <div class="row text-center">
                            <div class="col-md-4 mb-4">
                                <div class="stat-card p-4">
                                    <i class="bi bi-calendar-check fs-1 mb-3"></i>
                                    <h3><?= count($events) ?></h3>
                                    <p>Total Events</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="stat-card p-4">
                                    <i class="bi bi-calendar-event fs-1 mb-3"></i>
                                    <h3><?= count(array_filter($events, function($event) {
                                        return $event['event_type'] === 'school';
                                    })) ?></h3>
                                    <p>School Events</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="stat-card p-4">
                                    <i class="bi bi-calendar-plus fs-1 mb-3"></i>
                                    <h3><?= count(array_filter($events, function($event) {
                                        return $event['event_type'] === 'busy';
                                    })) ?></h3>
                                    <p>Busy Days</p>
                                </div>
                            </div>
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

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Header scroll effect
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.header');
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });

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
                        'className' => 'event-' . $event['event_type']
                    ];
                }, $events)); ?>,
                eventClick: function(info) {
                    if (confirm('Delete this event?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="event_id" value="${info.event.id}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>
</html> 
</html> 
</html> 
</html> 