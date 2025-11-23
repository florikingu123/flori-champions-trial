<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include 'config.php';
$conn->set_charset("utf8mb4");

$status = "";
$message = "";
$manager_email = $_SESSION['email'];

// Handle member deletion
if (isset($_POST['delete_member'])) {
    $member_id = $_POST['member_id'];
    $stmt = $conn->prepare("DELETE FROM family WHERE id = ? AND managers_email = ?");
    $stmt->bind_param("is", $member_id, $manager_email);
    if ($stmt->execute()) {
        $status = "success";
        $message = "Volunteer removed successfully!";
    } else {
        $status = "error";
        $message = "Error removing volunteer.";
    }
    $stmt->close();
}

// Handle member update
if (isset($_POST['update_member'])) {
    $member_id = $_POST['member_id'];
    $member_name = htmlspecialchars(trim($_POST['member_name']));
    $member_email = filter_var(trim($_POST['member_email']), FILTER_VALIDATE_EMAIL);
    
    if ($member_email && !empty($member_name)) {
        $stmt = $conn->prepare("UPDATE family SET member_name = ?, member_email = ? WHERE id = ? AND managers_email = ?");
        $stmt->bind_param("ssis", $member_name, $member_email, $member_id, $manager_email);
        if ($stmt->execute()) {
            $status = "success";
            $message = "Volunteer updated successfully!";
        } else {
            $status = "error";
            $message = "Error updating volunteer.";
        }
        $stmt->close();
    }
}

// Handle new member addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $member_name = htmlspecialchars(trim($_POST['member_name']));
    $member_email = filter_var(trim($_POST['member_email']), FILTER_VALIDATE_EMAIL);
    $member_pass = trim($_POST['member_pass']);

    if ($member_email && !empty($member_name) && !empty($member_pass)) {
        $hashed_pass = password_hash($member_pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $manager_email, $member_name, $member_email, $hashed_pass);
            if ($stmt->execute()) {
                $status = "success";
                $message = "Volunteer added successfully!";
            } else {
                $status = "error";
                $message = "Error: Could not add volunteer.";
            }
            $stmt->close();
        }
    }
}

// Fetch all family members with full details
$stmt = $conn->prepare("
    SELECT f.id, f.member_name, f.member_email, f.points,
           COUNT(DISTINCT CASE WHEN c.status = 'completed' OR c.status = 'redeemed' THEN c.id END) as completed_missions,
           COUNT(DISTINCT c.id) as total_missions,
           COUNT(DISTINCT ar.id) as rewards_redeemed
    FROM family f
    LEFT JOIN chores c ON f.member_email = c.member_email
    LEFT JOIN assigned_rewards ar ON f.member_email = ar.member_email AND ar.status = 'redeemed'
    WHERE f.managers_email = ?
    GROUP BY f.id, f.member_name, f.member_email, f.points
    ORDER BY f.points DESC
");
$stmt->bind_param("s", $manager_email);
$stmt->execute();
$result = $stmt->get_result();
$family_members = [];
while ($row = $result->fetch_assoc()) {
    // Get level
    $hours = $row['points'] ?? 0;
    $row['level'] = $hours <= 0 ? 1 : floor(sqrt($hours / 10)) + 1;
    $family_members[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Management - VolunteerHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .members-list {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #FFD3B5;
            color: #333;
        }

        .btn-primary:hover {
            background: #FFAAA5;
            color: #333;
        }

        .btn-danger {
            background: #8B4513;
            color: white;
        }

        .btn-danger:hover {
            background: #654321;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .member-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .member-info {
            flex-grow: 1;
        }

        .member-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .member-email {
            color: #666;
            font-size: 14px;
        }

        .member-actions {
            display: flex;
            gap: 10px;
        }

        .edit-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .edit-form.active {
            display: block;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            .member-card {
                flex-direction: column;
                text-align: center;
            }
            .member-actions {
                margin-top: 15px;
            }
        }
    </style>
  <?php include 'includes/theme_includes.php'; ?>
</head>
<body>
    <div class="container">
        <h1>Volunteer Management</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $status; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard">
            <!-- Section 1: Volunteer Management (Add/Remove/Edit) -->
            <div class="members-list" style="background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h2 class="form-title" style="color: #5c4d3c; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <i class="bi bi-people"></i> Volunteer Management
                </h2>
                <p style="color: #666; margin-bottom: 25px;">Add, remove, or edit volunteer accounts here.</p>
                
                <!-- Add Volunteer Form -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                    <h3 style="color: #5c4d3c; margin-bottom: 15px; font-size: 1.3rem;">Add New Volunteer</h3>
                    <form method="POST" style="display: grid; gap: 15px;">
                        <div class="form-group">
                            <label style="display: block; color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Volunteer Name</label>
                            <input type="text" name="member_name" class="form-control" placeholder="Enter volunteer name" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label style="display: block; color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Email Address</label>
                            <input type="email" name="member_email" class="form-control" placeholder="Enter email address" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label style="display: block; color: #5c4d3c; font-weight: 500; margin-bottom: 8px;">Password</label>
                            <input type="password" name="member_pass" class="form-control" placeholder="Enter password" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                        </div>
                        <button type="submit" name="add_member" class="btn btn-primary" style="background: #5c4d3c; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 500; cursor: pointer;">
                            <i class="bi bi-plus-circle"></i> Add Volunteer
                        </button>
                    </form>
                </div>

                <!-- Existing Volunteers List (for editing/removing) -->
                <h3 style="color: #5c4d3c; margin-bottom: 20px; font-size: 1.3rem;">Manage Existing Volunteers</h3>
                <?php if (empty($family_members)): ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No volunteers added yet. Add your first volunteer above.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 15px;">
                        <?php foreach ($family_members as $member): ?>
                            <div style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; border-left: 3px solid #5c4d3c;">
                                <div style="flex: 1;">
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($member['member_name']); ?>
                                    </div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($member['member_email']); ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button class="btn btn-primary" onclick="toggleEditForm(<?php echo $member['id']; ?>)" style="background: #5c4d3c; color: white; padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="delete_member" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this volunteer?')" style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500;">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <div id="edit-form-<?php echo $member['id']; ?>" class="edit-form" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 15px; display: none;">
                            <form method="POST">
                                <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="member_name" class="form-control" value="<?php echo htmlspecialchars($member['member_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="member_email" class="form-control" value="<?php echo htmlspecialchars($member['member_email']); ?>" required>
                                </div>
                                <button type="submit" name="update_member" class="btn btn-primary">Update</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?php echo $member['id']; ?>)">Cancel</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 2: Volunteer Statistics -->
            <div class="members-list" style="background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <h2 class="form-title" style="color: #5c4d3c; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                    <i class="bi bi-graph-up"></i> Volunteer Statistics
                </h2>
                <p style="color: #666; margin-bottom: 25px;">View detailed statistics for all volunteers including hours, missions, and rewards.</p>
                
                <?php if (empty($family_members)): ?>
                    <p style="color: #666; text-align: center; padding: 40px;">No volunteers to display statistics for yet.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 20px;">
                        <?php foreach ($family_members as $member): ?>
                            <div style="background: #f8f9fa; border-radius: 15px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #5c4d3c;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; flex-wrap: wrap;">
                                    <div>
                                        <h3 style="color: #333; font-size: 1.4rem; margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($member['member_name']); ?>
                                        </h3>
                                        <p style="color: #666; margin: 0;">
                                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($member['member_email']); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">
                                            Level <?php echo $member['level']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 8px; font-weight: 500;">Volunteer Hours</div>
                                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">
                                            <?php echo number_format($member['points'] ?? 0); ?>
                                        </div>
                                        <div style="color: #999; font-size: 0.8rem; margin-top: 5px;">hours</div>
                                    </div>
                                    
                                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 8px; font-weight: 500;">Completed Missions</div>
                                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">
                                            <?php echo $member['completed_missions'] ?? 0; ?>
                                        </div>
                                        <div style="color: #999; font-size: 0.8rem; margin-top: 5px;">missions</div>
                                    </div>
                                    
                                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 8px; font-weight: 500;">Total Missions</div>
                                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">
                                            <?php echo $member['total_missions'] ?? 0; ?>
                                        </div>
                                        <div style="color: #999; font-size: 0.8rem; margin-top: 5px;">assigned</div>
                                    </div>
                                    
                                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 8px; font-weight: 500;">Rewards Redeemed</div>
                                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">
                                            <?php echo $member['rewards_redeemed'] ?? 0; ?>
                                        </div>
                                        <div style="color: #999; font-size: 0.8rem; margin-top: 5px;">rewards</div>
                                    </div>
                                    
                                    <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <div style="color: #666; font-size: 0.9rem; margin-bottom: 8px; font-weight: 500;">Completion Rate</div>
                                        <div style="font-size: 2rem; font-weight: bold; color: #5c4d3c;">
                                            <?php 
                                            $total = $member['total_missions'] ?? 0;
                                            $completed = $member['completed_missions'] ?? 0;
                                            $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
                                            echo $rate;
                                            ?>%
                                        </div>
                                        <div style="color: #999; font-size: 0.8rem; margin-top: 5px;">success rate</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <button class="btn btn-secondary" onclick="window.location.href='famify.php'">Back to Family Center</button>
    </div>

    <script>
        function toggleEditForm(memberId) {
            const editForm = document.getElementById(`edit-form-${memberId}`);
            editForm.classList.toggle('active');
        }
    </script>
</body>
</html>
