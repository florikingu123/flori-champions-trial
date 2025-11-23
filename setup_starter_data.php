<?php
include 'config.php';

// This script creates 15+ starter volunteers and 7 starter organizations
// Run this once to populate the directory

echo "<h2>Setting up starter volunteers and organizations...</h2>";

// Create 5+ starter organizations (organization admins)
$organizations = [
    ['email' => 'communitycare@volunteerhub.com', 'name' => 'Community Care Organization', 'phone' => '+1 555-0101', 'password' => 'Org123!@#'],
    ['email' => 'greenearth@volunteerhub.com', 'name' => 'Green Earth Initiative', 'phone' => '+1 555-0102', 'password' => 'Org123!@#'],
    ['email' => 'youthsupport@volunteerhub.com', 'name' => 'Youth Support Network', 'phone' => '+1 555-0103', 'password' => 'Org123!@#'],
    ['email' => 'healthfirst@volunteerhub.com', 'name' => 'Health First Foundation', 'phone' => '+1 555-0104', 'password' => 'Org123!@#'],
    ['email' => 'educationplus@volunteerhub.com', 'name' => 'Education Plus Network', 'phone' => '+1 555-0105', 'password' => 'Org123!@#'],
    ['email' => 'animalrescue@volunteerhub.com', 'name' => 'Animal Rescue Alliance', 'phone' => '+1 555-0106', 'password' => 'Org123!@#'],
    ['email' => 'seniorcare@volunteerhub.com', 'name' => 'Senior Care Services', 'phone' => '+1 555-0107', 'password' => 'Org123!@#']
];

// Create 15+ starter volunteers distributed across organizations
$volunteers = [
    // Community Care Organization (3 volunteers)
    ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@volunteerhub.com', 'phone' => '+1 555-1001', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Michael Chen', 'email' => 'michael.chen@volunteerhub.com', 'phone' => '+1 555-1002', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Emily Rodriguez', 'email' => 'emily.rodriguez@volunteerhub.com', 'phone' => '+1 555-1003', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    
    // Green Earth Initiative (3 volunteers)
    ['name' => 'David Kim', 'email' => 'david.kim@volunteerhub.com', 'phone' => '+1 555-1004', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'Jessica Martinez', 'email' => 'jessica.martinez@volunteerhub.com', 'phone' => '+1 555-1005', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'James Wilson', 'email' => 'james.wilson@volunteerhub.com', 'phone' => '+1 555-1006', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    
    // Youth Support Network (3 volunteers)
    ['name' => 'Amanda Brown', 'email' => 'amanda.brown@volunteerhub.com', 'phone' => '+1 555-1007', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Robert Taylor', 'email' => 'robert.taylor@volunteerhub.com', 'phone' => '+1 555-1008', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@volunteerhub.com', 'phone' => '+1 555-1009', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    
    // Health First Foundation (2 volunteers)
    ['name' => 'Mark Thompson', 'email' => 'mark.thompson@volunteerhub.com', 'phone' => '+1 555-1010', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    ['name' => 'Jennifer Lee', 'email' => 'jennifer.lee@volunteerhub.com', 'phone' => '+1 555-1011', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    
    // Education Plus Network (2 volunteers)
    ['name' => 'Christopher Davis', 'email' => 'christopher.davis@volunteerhub.com', 'phone' => '+1 555-1012', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    ['name' => 'Maria Garcia', 'email' => 'maria.garcia@volunteerhub.com', 'phone' => '+1 555-1013', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    
    // Animal Rescue Alliance (2 volunteers)
    ['name' => 'Daniel White', 'email' => 'daniel.white@volunteerhub.com', 'phone' => '+1 555-1014', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    ['name' => 'Sophia Miller', 'email' => 'sophia.miller@volunteerhub.com', 'phone' => '+1 555-1015', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    
    // Senior Care Services (2 volunteers)
    ['name' => 'Andrew Harris', 'email' => 'andrew.harris@volunteerhub.com', 'phone' => '+1 555-1016', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com'],
    ['name' => 'Olivia Clark', 'email' => 'olivia.clark@volunteerhub.com', 'phone' => '+1 555-1017', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com']
];

$success_count = 0;
$error_count = 0;

// Create organizations
echo "<h3>Creating Organizations...</h3>";
foreach ($organizations as $org) {
    // Check if organization already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $org['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create organization admin user
        $hashed_password = password_hash($org['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->bind_param("sss", $org['email'], $hashed_password, $org['phone']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created organization: {$org['name']} ({$org['email']})</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating organization: {$org['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Organization already exists: {$org['email']}</p>";
    }
    $check->close();
}

// Create volunteers
echo "<h3>Creating Volunteers...</h3>";
foreach ($volunteers as $vol) {
    // Check if volunteer already exists
    $check = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
    $check->bind_param("s", $vol['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create volunteer user account (if not exists)
        $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $user_check->bind_param("s", $vol['email']);
        $user_check->execute();
        $user_result = $user_check->get_result();
        
        if ($user_result->num_rows == 0) {
            $hashed_password = password_hash($vol['password'], PASSWORD_BCRYPT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())");
            $user_stmt->bind_param("sss", $vol['email'], $hashed_password, $vol['phone']);
            $user_stmt->execute();
            $user_stmt->close();
        }
        $user_check->close();
        
        // Add volunteer to family table (assigned to organization)
        $hashed_pass = password_hash($vol['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass, points) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $vol['org'], $vol['name'], $vol['email'], $hashed_pass);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created volunteer: {$vol['name']} ({$vol['email']}) - Assigned to {$vol['org']}</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating volunteer: {$vol['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Volunteer already exists: {$vol['email']}</p>";
    }
    $check->close();
}

echo "<hr>";
echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Successfully created:</strong> {$success_count} items</p>";
echo "<p><strong>Errors:</strong> {$error_count} items</p>";
echo "<p><strong>Note:</strong> Default passwords for all accounts are 'Org123!@#' for organizations and 'Vol123!@#' for volunteers</p>";
echo "<p><a href='browse_directory.php'>View Directory</a> | <a href='index.html'>Go to Home</a></p>";

$conn->close();
?>



// This script creates 15+ starter volunteers and 7 starter organizations
// Run this once to populate the directory

echo "<h2>Setting up starter volunteers and organizations...</h2>";

// Create 5+ starter organizations (organization admins)
$organizations = [
    ['email' => 'communitycare@volunteerhub.com', 'name' => 'Community Care Organization', 'phone' => '+1 555-0101', 'password' => 'Org123!@#'],
    ['email' => 'greenearth@volunteerhub.com', 'name' => 'Green Earth Initiative', 'phone' => '+1 555-0102', 'password' => 'Org123!@#'],
    ['email' => 'youthsupport@volunteerhub.com', 'name' => 'Youth Support Network', 'phone' => '+1 555-0103', 'password' => 'Org123!@#'],
    ['email' => 'healthfirst@volunteerhub.com', 'name' => 'Health First Foundation', 'phone' => '+1 555-0104', 'password' => 'Org123!@#'],
    ['email' => 'educationplus@volunteerhub.com', 'name' => 'Education Plus Network', 'phone' => '+1 555-0105', 'password' => 'Org123!@#'],
    ['email' => 'animalrescue@volunteerhub.com', 'name' => 'Animal Rescue Alliance', 'phone' => '+1 555-0106', 'password' => 'Org123!@#'],
    ['email' => 'seniorcare@volunteerhub.com', 'name' => 'Senior Care Services', 'phone' => '+1 555-0107', 'password' => 'Org123!@#']
];

// Create 15+ starter volunteers distributed across organizations
$volunteers = [
    // Community Care Organization (3 volunteers)
    ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@volunteerhub.com', 'phone' => '+1 555-1001', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Michael Chen', 'email' => 'michael.chen@volunteerhub.com', 'phone' => '+1 555-1002', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Emily Rodriguez', 'email' => 'emily.rodriguez@volunteerhub.com', 'phone' => '+1 555-1003', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    
    // Green Earth Initiative (3 volunteers)
    ['name' => 'David Kim', 'email' => 'david.kim@volunteerhub.com', 'phone' => '+1 555-1004', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'Jessica Martinez', 'email' => 'jessica.martinez@volunteerhub.com', 'phone' => '+1 555-1005', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'James Wilson', 'email' => 'james.wilson@volunteerhub.com', 'phone' => '+1 555-1006', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    
    // Youth Support Network (3 volunteers)
    ['name' => 'Amanda Brown', 'email' => 'amanda.brown@volunteerhub.com', 'phone' => '+1 555-1007', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Robert Taylor', 'email' => 'robert.taylor@volunteerhub.com', 'phone' => '+1 555-1008', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@volunteerhub.com', 'phone' => '+1 555-1009', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    
    // Health First Foundation (2 volunteers)
    ['name' => 'Mark Thompson', 'email' => 'mark.thompson@volunteerhub.com', 'phone' => '+1 555-1010', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    ['name' => 'Jennifer Lee', 'email' => 'jennifer.lee@volunteerhub.com', 'phone' => '+1 555-1011', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    
    // Education Plus Network (2 volunteers)
    ['name' => 'Christopher Davis', 'email' => 'christopher.davis@volunteerhub.com', 'phone' => '+1 555-1012', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    ['name' => 'Maria Garcia', 'email' => 'maria.garcia@volunteerhub.com', 'phone' => '+1 555-1013', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    
    // Animal Rescue Alliance (2 volunteers)
    ['name' => 'Daniel White', 'email' => 'daniel.white@volunteerhub.com', 'phone' => '+1 555-1014', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    ['name' => 'Sophia Miller', 'email' => 'sophia.miller@volunteerhub.com', 'phone' => '+1 555-1015', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    
    // Senior Care Services (2 volunteers)
    ['name' => 'Andrew Harris', 'email' => 'andrew.harris@volunteerhub.com', 'phone' => '+1 555-1016', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com'],
    ['name' => 'Olivia Clark', 'email' => 'olivia.clark@volunteerhub.com', 'phone' => '+1 555-1017', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com']
];

$success_count = 0;
$error_count = 0;

// Create organizations
echo "<h3>Creating Organizations...</h3>";
foreach ($organizations as $org) {
    // Check if organization already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $org['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create organization admin user
        $hashed_password = password_hash($org['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->bind_param("sss", $org['email'], $hashed_password, $org['phone']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created organization: {$org['name']} ({$org['email']})</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating organization: {$org['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Organization already exists: {$org['email']}</p>";
    }
    $check->close();
}

// Create volunteers
echo "<h3>Creating Volunteers...</h3>";
foreach ($volunteers as $vol) {
    // Check if volunteer already exists
    $check = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
    $check->bind_param("s", $vol['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create volunteer user account (if not exists)
        $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $user_check->bind_param("s", $vol['email']);
        $user_check->execute();
        $user_result = $user_check->get_result();
        
        if ($user_result->num_rows == 0) {
            $hashed_password = password_hash($vol['password'], PASSWORD_BCRYPT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())");
            $user_stmt->bind_param("sss", $vol['email'], $hashed_password, $vol['phone']);
            $user_stmt->execute();
            $user_stmt->close();
        }
        $user_check->close();
        
        // Add volunteer to family table (assigned to organization)
        $hashed_pass = password_hash($vol['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass, points) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $vol['org'], $vol['name'], $vol['email'], $hashed_pass);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created volunteer: {$vol['name']} ({$vol['email']}) - Assigned to {$vol['org']}</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating volunteer: {$vol['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Volunteer already exists: {$vol['email']}</p>";
    }
    $check->close();
}

echo "<hr>";
echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Successfully created:</strong> {$success_count} items</p>";
echo "<p><strong>Errors:</strong> {$error_count} items</p>";
echo "<p><strong>Note:</strong> Default passwords for all accounts are 'Org123!@#' for organizations and 'Vol123!@#' for volunteers</p>";
echo "<p><a href='browse_directory.php'>View Directory</a> | <a href='index.html'>Go to Home</a></p>";

$conn->close();
?>



// This script creates 15+ starter volunteers and 7 starter organizations
// Run this once to populate the directory

echo "<h2>Setting up starter volunteers and organizations...</h2>";

// Create 5+ starter organizations (organization admins)
$organizations = [
    ['email' => 'communitycare@volunteerhub.com', 'name' => 'Community Care Organization', 'phone' => '+1 555-0101', 'password' => 'Org123!@#'],
    ['email' => 'greenearth@volunteerhub.com', 'name' => 'Green Earth Initiative', 'phone' => '+1 555-0102', 'password' => 'Org123!@#'],
    ['email' => 'youthsupport@volunteerhub.com', 'name' => 'Youth Support Network', 'phone' => '+1 555-0103', 'password' => 'Org123!@#'],
    ['email' => 'healthfirst@volunteerhub.com', 'name' => 'Health First Foundation', 'phone' => '+1 555-0104', 'password' => 'Org123!@#'],
    ['email' => 'educationplus@volunteerhub.com', 'name' => 'Education Plus Network', 'phone' => '+1 555-0105', 'password' => 'Org123!@#'],
    ['email' => 'animalrescue@volunteerhub.com', 'name' => 'Animal Rescue Alliance', 'phone' => '+1 555-0106', 'password' => 'Org123!@#'],
    ['email' => 'seniorcare@volunteerhub.com', 'name' => 'Senior Care Services', 'phone' => '+1 555-0107', 'password' => 'Org123!@#']
];

// Create 15+ starter volunteers distributed across organizations
$volunteers = [
    // Community Care Organization (3 volunteers)
    ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@volunteerhub.com', 'phone' => '+1 555-1001', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Michael Chen', 'email' => 'michael.chen@volunteerhub.com', 'phone' => '+1 555-1002', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Emily Rodriguez', 'email' => 'emily.rodriguez@volunteerhub.com', 'phone' => '+1 555-1003', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    
    // Green Earth Initiative (3 volunteers)
    ['name' => 'David Kim', 'email' => 'david.kim@volunteerhub.com', 'phone' => '+1 555-1004', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'Jessica Martinez', 'email' => 'jessica.martinez@volunteerhub.com', 'phone' => '+1 555-1005', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'James Wilson', 'email' => 'james.wilson@volunteerhub.com', 'phone' => '+1 555-1006', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    
    // Youth Support Network (3 volunteers)
    ['name' => 'Amanda Brown', 'email' => 'amanda.brown@volunteerhub.com', 'phone' => '+1 555-1007', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Robert Taylor', 'email' => 'robert.taylor@volunteerhub.com', 'phone' => '+1 555-1008', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@volunteerhub.com', 'phone' => '+1 555-1009', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    
    // Health First Foundation (2 volunteers)
    ['name' => 'Mark Thompson', 'email' => 'mark.thompson@volunteerhub.com', 'phone' => '+1 555-1010', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    ['name' => 'Jennifer Lee', 'email' => 'jennifer.lee@volunteerhub.com', 'phone' => '+1 555-1011', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    
    // Education Plus Network (2 volunteers)
    ['name' => 'Christopher Davis', 'email' => 'christopher.davis@volunteerhub.com', 'phone' => '+1 555-1012', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    ['name' => 'Maria Garcia', 'email' => 'maria.garcia@volunteerhub.com', 'phone' => '+1 555-1013', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    
    // Animal Rescue Alliance (2 volunteers)
    ['name' => 'Daniel White', 'email' => 'daniel.white@volunteerhub.com', 'phone' => '+1 555-1014', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    ['name' => 'Sophia Miller', 'email' => 'sophia.miller@volunteerhub.com', 'phone' => '+1 555-1015', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    
    // Senior Care Services (2 volunteers)
    ['name' => 'Andrew Harris', 'email' => 'andrew.harris@volunteerhub.com', 'phone' => '+1 555-1016', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com'],
    ['name' => 'Olivia Clark', 'email' => 'olivia.clark@volunteerhub.com', 'phone' => '+1 555-1017', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com']
];

$success_count = 0;
$error_count = 0;

// Create organizations
echo "<h3>Creating Organizations...</h3>";
foreach ($organizations as $org) {
    // Check if organization already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $org['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create organization admin user
        $hashed_password = password_hash($org['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->bind_param("sss", $org['email'], $hashed_password, $org['phone']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created organization: {$org['name']} ({$org['email']})</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating organization: {$org['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Organization already exists: {$org['email']}</p>";
    }
    $check->close();
}

// Create volunteers
echo "<h3>Creating Volunteers...</h3>";
foreach ($volunteers as $vol) {
    // Check if volunteer already exists
    $check = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
    $check->bind_param("s", $vol['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create volunteer user account (if not exists)
        $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $user_check->bind_param("s", $vol['email']);
        $user_check->execute();
        $user_result = $user_check->get_result();
        
        if ($user_result->num_rows == 0) {
            $hashed_password = password_hash($vol['password'], PASSWORD_BCRYPT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())");
            $user_stmt->bind_param("sss", $vol['email'], $hashed_password, $vol['phone']);
            $user_stmt->execute();
            $user_stmt->close();
        }
        $user_check->close();
        
        // Add volunteer to family table (assigned to organization)
        $hashed_pass = password_hash($vol['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass, points) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $vol['org'], $vol['name'], $vol['email'], $hashed_pass);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created volunteer: {$vol['name']} ({$vol['email']}) - Assigned to {$vol['org']}</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating volunteer: {$vol['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Volunteer already exists: {$vol['email']}</p>";
    }
    $check->close();
}

echo "<hr>";
echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Successfully created:</strong> {$success_count} items</p>";
echo "<p><strong>Errors:</strong> {$error_count} items</p>";
echo "<p><strong>Note:</strong> Default passwords for all accounts are 'Org123!@#' for organizations and 'Vol123!@#' for volunteers</p>";
echo "<p><a href='browse_directory.php'>View Directory</a> | <a href='index.html'>Go to Home</a></p>";

$conn->close();
?>



// This script creates 15+ starter volunteers and 7 starter organizations
// Run this once to populate the directory

echo "<h2>Setting up starter volunteers and organizations...</h2>";

// Create 5+ starter organizations (organization admins)
$organizations = [
    ['email' => 'communitycare@volunteerhub.com', 'name' => 'Community Care Organization', 'phone' => '+1 555-0101', 'password' => 'Org123!@#'],
    ['email' => 'greenearth@volunteerhub.com', 'name' => 'Green Earth Initiative', 'phone' => '+1 555-0102', 'password' => 'Org123!@#'],
    ['email' => 'youthsupport@volunteerhub.com', 'name' => 'Youth Support Network', 'phone' => '+1 555-0103', 'password' => 'Org123!@#'],
    ['email' => 'healthfirst@volunteerhub.com', 'name' => 'Health First Foundation', 'phone' => '+1 555-0104', 'password' => 'Org123!@#'],
    ['email' => 'educationplus@volunteerhub.com', 'name' => 'Education Plus Network', 'phone' => '+1 555-0105', 'password' => 'Org123!@#'],
    ['email' => 'animalrescue@volunteerhub.com', 'name' => 'Animal Rescue Alliance', 'phone' => '+1 555-0106', 'password' => 'Org123!@#'],
    ['email' => 'seniorcare@volunteerhub.com', 'name' => 'Senior Care Services', 'phone' => '+1 555-0107', 'password' => 'Org123!@#']
];

// Create 15+ starter volunteers distributed across organizations
$volunteers = [
    // Community Care Organization (3 volunteers)
    ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@volunteerhub.com', 'phone' => '+1 555-1001', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Michael Chen', 'email' => 'michael.chen@volunteerhub.com', 'phone' => '+1 555-1002', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    ['name' => 'Emily Rodriguez', 'email' => 'emily.rodriguez@volunteerhub.com', 'phone' => '+1 555-1003', 'password' => 'Vol123!@#', 'org' => 'communitycare@volunteerhub.com'],
    
    // Green Earth Initiative (3 volunteers)
    ['name' => 'David Kim', 'email' => 'david.kim@volunteerhub.com', 'phone' => '+1 555-1004', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'Jessica Martinez', 'email' => 'jessica.martinez@volunteerhub.com', 'phone' => '+1 555-1005', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    ['name' => 'James Wilson', 'email' => 'james.wilson@volunteerhub.com', 'phone' => '+1 555-1006', 'password' => 'Vol123!@#', 'org' => 'greenearth@volunteerhub.com'],
    
    // Youth Support Network (3 volunteers)
    ['name' => 'Amanda Brown', 'email' => 'amanda.brown@volunteerhub.com', 'phone' => '+1 555-1007', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Robert Taylor', 'email' => 'robert.taylor@volunteerhub.com', 'phone' => '+1 555-1008', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@volunteerhub.com', 'phone' => '+1 555-1009', 'password' => 'Vol123!@#', 'org' => 'youthsupport@volunteerhub.com'],
    
    // Health First Foundation (2 volunteers)
    ['name' => 'Mark Thompson', 'email' => 'mark.thompson@volunteerhub.com', 'phone' => '+1 555-1010', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    ['name' => 'Jennifer Lee', 'email' => 'jennifer.lee@volunteerhub.com', 'phone' => '+1 555-1011', 'password' => 'Vol123!@#', 'org' => 'healthfirst@volunteerhub.com'],
    
    // Education Plus Network (2 volunteers)
    ['name' => 'Christopher Davis', 'email' => 'christopher.davis@volunteerhub.com', 'phone' => '+1 555-1012', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    ['name' => 'Maria Garcia', 'email' => 'maria.garcia@volunteerhub.com', 'phone' => '+1 555-1013', 'password' => 'Vol123!@#', 'org' => 'educationplus@volunteerhub.com'],
    
    // Animal Rescue Alliance (2 volunteers)
    ['name' => 'Daniel White', 'email' => 'daniel.white@volunteerhub.com', 'phone' => '+1 555-1014', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    ['name' => 'Sophia Miller', 'email' => 'sophia.miller@volunteerhub.com', 'phone' => '+1 555-1015', 'password' => 'Vol123!@#', 'org' => 'animalrescue@volunteerhub.com'],
    
    // Senior Care Services (2 volunteers)
    ['name' => 'Andrew Harris', 'email' => 'andrew.harris@volunteerhub.com', 'phone' => '+1 555-1016', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com'],
    ['name' => 'Olivia Clark', 'email' => 'olivia.clark@volunteerhub.com', 'phone' => '+1 555-1017', 'password' => 'Vol123!@#', 'org' => 'seniorcare@volunteerhub.com']
];

$success_count = 0;
$error_count = 0;

// Create organizations
echo "<h3>Creating Organizations...</h3>";
foreach ($organizations as $org) {
    // Check if organization already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $org['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create organization admin user
        $hashed_password = password_hash($org['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->bind_param("sss", $org['email'], $hashed_password, $org['phone']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created organization: {$org['name']} ({$org['email']})</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating organization: {$org['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Organization already exists: {$org['email']}</p>";
    }
    $check->close();
}

// Create volunteers
echo "<h3>Creating Volunteers...</h3>";
foreach ($volunteers as $vol) {
    // Check if volunteer already exists
    $check = $conn->prepare("SELECT id FROM family WHERE member_email = ?");
    $check->bind_param("s", $vol['email']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows == 0) {
        // Create volunteer user account (if not exists)
        $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $user_check->bind_param("s", $vol['email']);
        $user_check->execute();
        $user_result = $user_check->get_result();
        
        if ($user_result->num_rows == 0) {
            $hashed_password = password_hash($vol['password'], PASSWORD_BCRYPT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, number, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())");
            $user_stmt->bind_param("sss", $vol['email'], $hashed_password, $vol['phone']);
            $user_stmt->execute();
            $user_stmt->close();
        }
        $user_check->close();
        
        // Add volunteer to family table (assigned to organization)
        $hashed_pass = password_hash($vol['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO family (managers_email, member_name, member_email, member_pass, points) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $vol['org'], $vol['name'], $vol['email'], $hashed_pass);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Created volunteer: {$vol['name']} ({$vol['email']}) - Assigned to {$vol['org']}</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error creating volunteer: {$vol['email']} - " . $stmt->error . "</p>";
            $error_count++;
        }
        $stmt->close();
    } else {
        echo "<p style='color: orange;'>⚠ Volunteer already exists: {$vol['email']}</p>";
    }
    $check->close();
}

echo "<hr>";
echo "<h3>Setup Complete!</h3>";
echo "<p><strong>Successfully created:</strong> {$success_count} items</p>";
echo "<p><strong>Errors:</strong> {$error_count} items</p>";
echo "<p><strong>Note:</strong> Default passwords for all accounts are 'Org123!@#' for organizations and 'Vol123!@#' for volunteers</p>";
echo "<p><a href='browse_directory.php'>View Directory</a> | <a href='index.html'>Go to Home</a></p>";

$conn->close();
?>

