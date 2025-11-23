<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "famify"; // Note: Database can be renamed to "volunteer_platform" via SQL

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
